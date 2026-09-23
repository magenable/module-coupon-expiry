<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Api;

use Magenable\CouponExpiry\Api\Data\IssueRequestInterface;

interface CouponExpiryManagementInterface
{
    /**
     * @param \Magenable\CouponExpiry\Api\Data\IssueRequestInterface $request
     * @return \Magenable\CouponExpiry\Api\Data\CouponExpiryInterface
     */
    public function issue(IssueRequestInterface $request);
}