<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model;

use Magenable\CouponExpiry\Model\Clock\UtcClockInterface;

class ExpiryPolicy
{
    public function __construct(private readonly UtcClockInterface $clock)
    {
    }

    public function calculate(\DateTimeImmutable $issuedAt, int $days): \DateTimeImmutable
    {
        if ($days < 1 || $days > 3650) {
            throw new \InvalidArgumentException('Coupon validity days must be between 1 and 3650.');
        }
        return $issuedAt->modify(sprintf('+%d seconds', $days * 86400));
    }

    public function isExpired(array $metadata): bool
    {
        if (empty($metadata['rolling_expires_at']) || empty($metadata['issued_at']) || empty($metadata['validity_days_at_issue'])) {
            return true;
        }
        try {
            $issuedAt = $this->parseDatabaseDate((string)$metadata['issued_at']);
            $expiresAt = $this->parseDatabaseDate((string)$metadata['rolling_expires_at']);
            $days = filter_var($metadata['validity_days_at_issue'], FILTER_VALIDATE_INT);
            if ($issuedAt === null || $expiresAt === null || $days === false
                || $days < 1 || $days > 3650 || $this->calculate($issuedAt, $days) != $expiresAt
            ) {
                return true;
            }
        } catch (\Throwable) {
            return true;
        }
        return $this->clock->now() >= $expiresAt;
    }

    private function parseDatabaseDate(string $value): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));
        return $date !== false && $date->format('Y-m-d H:i:s') === $value ? $date : null;
    }
}
