<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Api\Data;

interface CouponExpiryInterface
{
    /** @return int */
    public function getCouponId();
    /** @param int $value
     * @return $this
     */
    public function setCouponId($value);
    /** @return int */
    public function getRuleId();
    /** @param int $value
     * @return $this
     */
    public function setRuleId($value);
    /** @return string */
    public function getCode();
    /** @param string $value
     * @return $this
     */
    public function setCode($value);
    /** @return string */
    public function getIssuedAt();
    /** @param string $value
     * @return $this
     */
    public function setIssuedAt($value);
    /** @return int */
    public function getValidityDaysAtIssue();
    /** @param int $value
     * @return $this
     */
    public function setValidityDaysAtIssue($value);
    /** @return string */
    public function getRollingExpiresAt();
    /** @param string $value
     * @return $this
     */
    public function setRollingExpiresAt($value);
    /** @return string */
    public function getIssuanceSource();
    /** @param string $value
     * @return $this
     */
    public function setIssuanceSource($value);
    /** @return bool */
    public function getIsExpired();
    /** @param bool $value
     * @return $this
     */
    public function setIsExpired($value);
    /** @return string */
    public function getEvaluatedAt();
    /** @param string $value
     * @return $this
     */
    public function setEvaluatedAt($value);
    /** @return bool */
    public function getReplayed();
    /** @param bool $value
     * @return $this
     */
    public function setReplayed($value);
}
