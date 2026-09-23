<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model\Clock;

interface UtcClockInterface
{
    public function now(): \DateTimeImmutable;
}
