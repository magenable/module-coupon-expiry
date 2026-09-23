<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model\Validation;

use Magento\Quote\Model\Quote;

class ContributionResolver
{
    private const MINIMUM_DISCOUNT = 0.0001;

    public function couponRuleContributed(Quote $quote, int $ruleId): bool
    {
        $entities = array_merge([$quote], $quote->getAllAddresses(), $quote->getAllItems());
        foreach ($entities as $entity) {
            if ($this->hasContribution(
                (string)$entity->getAppliedRuleIds(),
                (float)$entity->getDiscountAmount(),
                $ruleId
            ) || $this->hasContribution(
                (string)$entity->getOrigData('applied_rule_ids'),
                (float)$entity->getOrigData('discount_amount'),
                $ruleId
            )) {
                return true;
            }
        }
        return false;
    }

    private function hasContribution(string $appliedRuleIds, float $discountAmount, int $ruleId): bool
    {
        return $this->containsRule($appliedRuleIds, $ruleId)
            && abs($discountAmount) > self::MINIMUM_DISCOUNT;
    }

    private function containsRule(string $appliedRuleIds, int $ruleId): bool
    {
        $ids = array_map('trim', array_filter(explode(',', $appliedRuleIds), 'strlen'));
        return in_array((string)$ruleId, $ids, true);
    }
}
