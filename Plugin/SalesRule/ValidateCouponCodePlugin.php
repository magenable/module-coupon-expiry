<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Plugin\SalesRule;

use Magenable\CouponExpiry\Model\CouponExpiryResource;
use Magenable\CouponExpiry\Model\ExpiryPolicy;
use Magento\SalesRule\Model\ValidateCouponCode;

class ValidateCouponCodePlugin
{
    public function __construct(private readonly CouponExpiryResource $metadata, private readonly ExpiryPolicy $policy)
    {
    }
    public function afterExecute(ValidateCouponCode $subject, array $result): array
    {
        $rows = $this->metadata->getByCodes(array_values($result));
        foreach ($result as $ruleId => $code) {
            if (isset($rows[$code]) && $this->policy->isExpired($rows[$code])) {
                unset($result[$ruleId]);
            }
        }
        return $result;
    }
}
