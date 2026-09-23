<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Plugin\SalesRule;

use Magento\SalesRule\Model\Converter\ToModel;
use Magento\SalesRule\Model\Data\Rule as RuleData;
use Magento\SalesRule\Model\Rule;

class RuleToModelPlugin
{
    public function afterToModel(ToModel $subject, Rule $result, RuleData $dataModel): Rule
    {
        $extensionAttributes = $dataModel->getExtensionAttributes();
        if ($extensionAttributes !== null) {
            $result->setData(
                'magenable_coupon_validity_days',
                $extensionAttributes->getMagenableCouponValidityDays()
            );
        }
        return $result;
    }
}
