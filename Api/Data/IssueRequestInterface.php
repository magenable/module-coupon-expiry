<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Api\Data;

interface IssueRequestInterface
{
    public const RULE_ID = 'rule_id';
    public const REQUEST_ID = 'request_id';
    /** @return int */
    public function getRuleId();
    /** @param int $ruleId
     * @return $this
     */
    public function setRuleId($ruleId);
    /** @return string */
    public function getRequestId();
    /** @param string $requestId
     * @return $this
     */
    public function setRequestId($requestId);
}