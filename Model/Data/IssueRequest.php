<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model\Data;

use Magenable\CouponExpiry\Api\Data\IssueRequestInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class IssueRequest extends AbstractSimpleObject implements IssueRequestInterface
{
    public function getRuleId() { return (int)$this->_get(self::RULE_ID); }
    public function setRuleId($ruleId) { return $this->setData(self::RULE_ID, $ruleId); }
    public function getRequestId() { return (string)$this->_get(self::REQUEST_ID); }
    public function setRequestId($requestId) { return $this->setData(self::REQUEST_ID, $requestId); }
}