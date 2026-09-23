<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model;

/**
 * Invocation-scoped generation context shared by a native generation batch.
 */
class IssuanceContext
{
    /**
     * @var array<int, array{store_id: int, source: string, rule_id: int, validity_days: int|null}>
     */
    private array $stack = [];

    public function enter(int $storeId, string $source, int $ruleId, ?int $validityDays): void
    {
        $this->stack[] = [
            'store_id' => $storeId,
            'source' => $source,
            'rule_id' => $ruleId,
            'validity_days' => $validityDays,
        ];
    }

    public function leave(): void
    {
        array_pop($this->stack);
    }

    public function getStoreId(): ?int
    {
        return $this->current()['store_id'] ?? null;
    }

    public function getSource(): ?string
    {
        return $this->current()['source'] ?? null;
    }

    public function getValidityDays(int $ruleId): ?int
    {
        $context = $this->current();
        if ($context === null || $context['rule_id'] !== $ruleId) {
            return null;
        }

        return $context['validity_days'];
    }

    public function hasRule(int $ruleId): bool
    {
        $context = $this->current();
        return $context !== null && $context['rule_id'] === $ruleId;
    }

    /**
     * @return array{store_id: int, source: string, rule_id: int, validity_days: int|null}|null
     */
    private function current(): ?array
    {
        $context = end($this->stack);
        return $context === false ? null : $context;
    }
}