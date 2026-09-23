<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Plugin\SalesRule;

use Magenable\CouponExpiry\Model\CouponExpiryResource;
use Magenable\CouponExpiry\Model\ExpiryPolicy;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\ValidateCoupon;

class ValidateCouponPlugin
{
    public function __construct(
        private readonly CouponExpiryResource $metadata,
        private readonly ExpiryPolicy $policy
    ) {
    }
    public function afterExecute(ValidateCoupon $subject, bool $result, Rule $rule, Address $address, ?string $couponCode = null): bool
    {
        if (!$result || !$couponCode) {
            return $result;
        }
        $row = $this->metadata->getByCodes([$couponCode])[$couponCode] ?? null;
        if ($row && $this->policy->isExpired($row)) {
            return false;
        }
        return true;
    }
}
