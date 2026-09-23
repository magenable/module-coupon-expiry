<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Api\Exception;

use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception;

/**
 * Deliberately maps payload reuse to HTTP 409, which core's constructor does not expose.
 */
class IdempotencyConflictException extends Exception
{
    public function __construct(Phrase $phrase)
    {
        parent::__construct($phrase, 0, self::HTTP_BAD_REQUEST);
    }

    public function getHttpCode(): int
    {
        return 409;
    }
}