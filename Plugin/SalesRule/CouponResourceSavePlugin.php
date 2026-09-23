<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Plugin\SalesRule;

use Magenable\CouponExpiry\Model\Clock\UtcClockInterface;
use Magenable\CouponExpiry\Model\CouponExpiryResource;
use Magenable\CouponExpiry\Model\ExpiryPolicy;
use Magenable\CouponExpiry\Model\IssuanceContext;
use Magenable\CouponExpiry\Model\IssuanceSourceResolver;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Model\AbstractModel;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\ResourceModel\Coupon as CouponResource;

class CouponResourceSavePlugin
{
    public function __construct(
        private readonly RuleFactory $ruleFactory,
        private readonly CouponExpiryResource $metadata,
        private readonly ExpiryPolicy $policy,
        private readonly UtcClockInterface $clock,
        private readonly IssuanceSourceResolver $source,
        private readonly IssuanceContext $context
    ) {
    }

    public function aroundSave(CouponResource $subject, callable $proceed, AbstractModel $object): CouponResource
    {
        $isNew = !$object->getId();
        if (!$isNew) {
            return $this->saveExisting($subject, $proceed, $object);
        }
        if ((int)$object->getData('type') !== Coupon::TYPE_GENERATED) {
            return $proceed($object);
        }
        $ruleId = (int)$object->getData('rule_id');
        $days = $this->context->getValidityDays($ruleId);
        if (!$this->context->hasRule($ruleId)) {
            $rule = $this->ruleFactory->create()->load($ruleId);
            $days = (int)$rule->getData('magenable_coupon_validity_days');
        }
        if ($days === null || $days < 1) {
            return $proceed($object);
        }
        $connection = $subject->getConnection();
        $connection->beginTransaction();
        try {
            $result = $proceed($object);
            $issuedAt = $this->clock->now();
            $this->metadata->insert((int)$object->getId(), $issuedAt, $days, $this->policy->calculate($issuedAt, $days), $this->source->resolve());
            $connection->commit();
            return $result;
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function saveExisting(CouponResource $subject, callable $proceed, AbstractModel $object): CouponResource
    {
        $connection = $subject->getConnection();
        $connection->beginTransaction();
        try {
            $persistedRuleId = $this->metadata->getManagedRuleId((int)$object->getId(), true);
            if ($persistedRuleId !== null && $persistedRuleId !== (int)$object->getData('rule_id')) {
                throw new InputException(__('A managed coupon cannot be assigned to a different cart price rule.'));
            }
            $result = $proceed($object);
            $connection->commit();
            return $result;
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
