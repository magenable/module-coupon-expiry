<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Plugin\SalesRule;

use Magenable\CouponExpiry\Exception\ExpiredCouponException;
use Magenable\CouponExpiry\Model\CouponExpiryResource;
use Magenable\CouponExpiry\Model\ExpiryPolicy;
use Magenable\CouponExpiry\Model\QuoteReviewMarkerResource;
use Magenable\CouponExpiry\Model\Validation\ContributionResolver;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\Validator;

/**
 * Persists expiry evidence before native totals validation clears the coupon code.
 */
class ValidatorPlugin
{
    public function __construct(
        private readonly CouponExpiryResource $metadata,
        private readonly ExpiryPolicy $policy,
        private readonly ContributionResolver $contributionResolver,
        private readonly QuoteReviewMarkerResource $reviewMarker
    ) {
    }

    public function aroundInitFromQuote(Validator $subject, callable $proceed, CartInterface $cart): Validator
    {
        if (!$cart instanceof Quote) {
            return $proceed($cart);
        }
        $codes = array_values(array_filter(array_map(
            'trim',
            preg_split('/[,;]+/', (string)$cart->getCouponCode()) ?: []
        )));
        $expiredCode = null;
        if ($codes) {
            $rows = $this->metadata->getByCodes($codes);
            foreach ($codes as $code) {
                $row = $rows[$code] ?? null;
                if ($row
                    && $this->policy->isExpired($row)
                    && $this->contributionResolver->couponRuleContributed($cart, (int)$row['rule_id'])
                ) {
                    $this->reviewMarker->mark($cart, $code, (int)$row['rule_id']);
                    $expiredCode = $code;
                    break;
                }
            }
        }
        try {
            $result = $proceed($cart);
        } catch (\Throwable $exception) {
            if ($expiredCode !== null) {
                throw new ExpiredCouponException(
                    __('Coupon code "%1" expired. Refresh the cart totals and review the updated order.', $expiredCode),
                    $exception
                );
            }
            throw $exception;
        }
        if ($expiredCode !== null) {
            throw new ExpiredCouponException(
                __('Coupon code "%1" expired. Refresh the cart totals and review the updated order.', $expiredCode)
            );
        }
        return $result;
    }
}
