<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model\Clock;

class SystemUtcClock implements UtcClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
