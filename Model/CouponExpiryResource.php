<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model;

use Magento\Framework\App\ResourceConnection;

class CouponExpiryResource
{
    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    public function insert(int $couponId, \DateTimeImmutable $issuedAt, int $days, \DateTimeImmutable $expiresAt, string $source): void
    {
        $this->connection()->insert($this->table(), [
            'coupon_id' => $couponId,
            'issued_at' => $issuedAt->format('Y-m-d H:i:s'),
            'validity_days_at_issue' => $days,
            'rolling_expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'issuance_source' => $source,
        ]);
    }

    public function getByCodes(array $codes): array
    {
        $codes = array_values(array_filter(array_unique(array_map('strval', $codes))));
        if (!$codes) {
            return [];
        }
        $select = $this->connection()->select()->from(['c' => $this->resource->getTableName('salesrule_coupon')], ['code','coupon_id','rule_id'])
            ->joinInner(['e' => $this->table()], 'e.coupon_id = c.coupon_id', ['issued_at','validity_days_at_issue','rolling_expires_at','issuance_source'])
            ->where('c.code IN (?)', $codes);
        $rows = $this->connection()->fetchAll($select);
        $normalizedRows = [];
        foreach ($rows as $row) {
            $normalizedRows[strtolower((string)$row['code'])] = $row;
        }
        $result = [];
        foreach ($codes as $code) {
            $row = $normalizedRows[strtolower($code)] ?? null;
            if ($row !== null) {
                $result[$code] = $row;
            }
        }
        return $result;
    }

    public function getManagedRuleId(int $couponId, bool $forUpdate = false): ?int
    {
        $select = $this->connection()->select()
            ->from(['c' => $this->resource->getTableName('salesrule_coupon')], ['rule_id'])
            ->joinInner(['e' => $this->table()], 'e.coupon_id = c.coupon_id', [])
            ->where('c.coupon_id = ?', $couponId);
        if ($forUpdate) {
            $select->forUpdate(true);
        }
        $ruleId = $this->connection()->fetchOne($select);
        return $ruleId === false ? null : (int)$ruleId;
    }

    private function connection(): \Magento\Framework\DB\Adapter\AdapterInterface
    {
        return $this->resource->getConnection();
    }
    private function table(): string
    {
        return $this->resource->getTableName('magenable_coupon_expiry');
    }
}
