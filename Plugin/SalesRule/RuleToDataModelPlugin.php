<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Plugin\SalesRule;

use Magento\SalesRule\Api\Data\RuleExtensionFactory;
use Magento\SalesRule\Model\Converter\ToDataModel;
use Magento\SalesRule\Model\Data\Rule as RuleData;
use Magento\SalesRule\Model\Rule;

class RuleToDataModelPlugin
{
    public function __construct(private readonly RuleExtensionFactory $extensionFactory)
    {
    }
    public function afterToDataModel(ToDataModel $subject, RuleData $result, Rule $model): RuleData
    {
        $ext = $result->getExtensionAttributes() ?: $this->extensionFactory->create();
        $ext->setMagenableCouponValidityDays($model->getData('magenable_coupon_validity_days'));
        $result->setExtensionAttributes($ext);
        return $result;
    }
}
