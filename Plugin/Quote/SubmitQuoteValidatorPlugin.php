<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Plugin\Quote;

use Magenable\CouponExpiry\Exception\ExpiredCouponException;
use Magenable\CouponExpiry\Model\CouponExpiryResource;
use Magenable\CouponExpiry\Model\ExpiryPolicy;
use Magenable\CouponExpiry\Model\Validation\ContributionResolver;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\SubmitQuoteValidator;

class SubmitQuoteValidatorPlugin
{
    public function __construct(
        private readonly CouponExpiryResource $metadata,
        private readonly ExpiryPolicy $policy,
        private readonly ContributionResolver $contributionResolver
    ) {
    }
    public function beforeValidateQuote(SubmitQuoteValidator $subject, Quote $quote): array
    {
        if ((bool)$quote->getData('magenable_coupon_expiry_review_required')) {
            $quote->setTriggerRecollect(1);
            $code = (string)$quote->getData('magenable_expired_coupon_code');
            throw new ExpiredCouponException(
                __('Coupon code "%1" expired. Refresh the cart totals and review the updated order.', $code)
            );
        }
        $codes = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', (string)$quote->getCouponCode()) ?: [])));
        if (!$codes) {
            return [$quote];
        }
        $rows = $this->metadata->getByCodes($codes);
        foreach ($codes as $code) {
            if (!isset($rows[$code]) || !$this->policy->isExpired($rows[$code])) {
                continue;
            }
            $ruleId = (int)$rows[$code]['rule_id'];
            if ($this->contributionResolver->couponRuleContributed($quote, $ruleId)) {
                $quote->setTriggerRecollect(1);
                throw new ExpiredCouponException(__('Coupon code "%1" expired. Refresh the cart totals and review the updated order.', $code));
            }
        }
        return [$quote];
    }
}
