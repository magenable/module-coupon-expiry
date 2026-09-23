<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Safe domain exception for an expired coupon that previously affected quote totals.
 */
class ExpiredCouponException extends LocalizedException
{
}
