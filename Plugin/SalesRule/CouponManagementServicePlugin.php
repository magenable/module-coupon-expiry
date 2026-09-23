<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Plugin\SalesRule;

use Magenable\CouponExpiry\Model\IssuanceContext;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\SalesRule\Api\Data\CouponGenerationSpecInterface;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Service\CouponManagementService;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Keeps the queue's store/source and one rule-policy snapshot for an entire batch.
 */
class CouponManagementServicePlugin
{
    public function __construct(
        private readonly IssuanceContext $context,
        private readonly RuleFactory $ruleFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $state
    ) {
    }

    /**
     * @return string[]
     */
    public function aroundGenerate(
        CouponManagementService $subject,
        callable $proceed,
        CouponGenerationSpecInterface $couponSpec
    ): array {
        $attributes = $couponSpec->getExtensionAttributes();
        $storeId = $attributes?->getMagenableStoreId();
        $source = $attributes?->getMagenableIssuanceSource();
        $ruleId = (int)$couponSpec->getRuleId();
        $rule = $this->ruleFactory->create()->load($ruleId);
        $rawDays = $rule->getData('magenable_coupon_validity_days');
        $validityDays = $rawDays === null || $rawDays === '' ? null : (int)$rawDays;
        $trustedAdminContext = $source === 'admin' && !$this->isApiExecution();

        $this->context->enter(
            $trustedAdminContext && $storeId !== null
                ? (int)$storeId
                : (int)$this->storeManager->getStore()->getId(),
            $trustedAdminContext || $this->isAdminArea() ? 'admin' : 'native_api',
            $ruleId,
            $validityDays
        );
        try {
            return $proceed($couponSpec);
        } finally {
            $this->context->leave();
        }
    }

    private function isApiExecution(): bool
    {
        try {
            return in_array(
                $this->state->getAreaCode(),
                [Area::AREA_WEBAPI_REST, Area::AREA_WEBAPI_SOAP, Area::AREA_GRAPHQL],
                true
            );
        } catch (\Throwable) {
            return false;
        }
    }

    private function isAdminArea(): bool
    {
        try {
            return in_array($this->state->getAreaCode(), [Area::AREA_ADMINHTML, 'crontab'], true);
        } catch (\Throwable) {
            return true;
        }
    }
}