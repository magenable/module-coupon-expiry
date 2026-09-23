<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Model\Rule;

class ValidateRulePolicy implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        /** @var Rule $rule */ $rule = $observer->getData('rule');
        $raw = $rule->getData('magenable_coupon_validity_days');
        if ($raw === null || $raw === '') {
            $rule->setData('magenable_coupon_validity_days', null);
            return;
        }
        if (filter_var($raw, FILTER_VALIDATE_INT) === false || (int)$raw < 1 || (int)$raw > 3650) {
            throw new LocalizedException(__('Coupon validity must be a whole number from 1 to 3650 days.'));
        }
        if ((int)$rule->getCouponType() !== Rule::COUPON_TYPE_SPECIFIC || !(bool)$rule->getUseAutoGeneration()) {
            throw new LocalizedException(__('Coupon validity requires Specific Coupon with Use Auto Generation enabled.'));
        }
        $rule->setData('magenable_coupon_validity_days', (int)$raw);
    }
}
