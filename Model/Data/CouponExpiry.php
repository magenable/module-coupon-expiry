<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model\Data;

use Magenable\CouponExpiry\Api\Data\CouponExpiryInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class CouponExpiry extends AbstractSimpleObject implements CouponExpiryInterface
{
    public function getCouponId() { return (int)$this->_get('coupon_id'); }
    public function setCouponId($value) { return $this->setData('coupon_id', $value); }
    public function getRuleId() { return (int)$this->_get('rule_id'); }
    public function setRuleId($value) { return $this->setData('rule_id', $value); }
    public function getCode() { return (string)$this->_get('code'); }
    public function setCode($value) { return $this->setData('code', $value); }
    public function getIssuedAt() { return (string)$this->_get('issued_at'); }
    public function setIssuedAt($value) { return $this->setData('issued_at', $value); }
    public function getValidityDaysAtIssue() { return (int)$this->_get('validity_days_at_issue'); }
    public function setValidityDaysAtIssue($value) { return $this->setData('validity_days_at_issue', $value); }
    public function getRollingExpiresAt() { return (string)$this->_get('rolling_expires_at'); }
    public function setRollingExpiresAt($value) { return $this->setData('rolling_expires_at', $value); }
    public function getIssuanceSource() { return (string)$this->_get('issuance_source'); }
    public function setIssuanceSource($value) { return $this->setData('issuance_source', $value); }
    public function getIsExpired() { return (bool)$this->_get('is_expired'); }
    public function setIsExpired($value) { return $this->setData('is_expired', $value); }
    public function getEvaluatedAt() { return (string)$this->_get('evaluated_at'); }
    public function setEvaluatedAt($value) { return $this->setData('evaluated_at', $value); }
    public function getReplayed() { return (bool)$this->_get('replayed'); }
    public function setReplayed($value) { return $this->setData('replayed', $value); }
}