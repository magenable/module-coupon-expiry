<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model;

use Magenable\CouponExpiry\Api\CouponExpiryRepositoryInterface;
use Magenable\CouponExpiry\Model\Clock\UtcClockInterface;
use Magenable\CouponExpiry\Model\Data\CouponExpiryFactory;
use Magenable\CouponExpiry\Model\Data\CouponExpirySearchResultsFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class CouponExpiryRepository implements CouponExpiryRepositoryInterface
{
    private const FILTERS = [
        'coupon_id' => 'c.coupon_id',
        'code' => 'c.code',
        'rule_id' => 'c.rule_id',
        'issued_at' => 'e.issued_at',
        'rolling_expires_at' => 'e.rolling_expires_at',
    ];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly CouponExpiryFactory $itemFactory,
        private readonly CouponExpirySearchResultsFactory $resultsFactory,
        private readonly ExpiryPolicy $policy,
        private readonly UtcClockInterface $clock
    ) {
    }

    public function getById($couponId)
    {
        $select = $this->baseSelect()->where('c.coupon_id = ?', (int)$couponId);
        $row = $this->connection()->fetchRow($select);
        if (!$row) {
            throw NoSuchEntityException::singleField('couponId', $couponId);
        }
        return $this->createItem($row, false);
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $select = $this->baseSelect();
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $conditions = [];
            foreach ($group->getFilters() as $filter) {
                $field = self::FILTERS[$filter->getField()] ?? null;
                if ($field === null) {
                    throw new InputException(__('Filtering by "%1" is not supported.', $filter->getField()));
                }
                $conditions[] = $this->condition($field, (string)$filter->getConditionType(), $filter->getValue());
            }
            if ($conditions) {
                $select->where(implode(' OR ', $conditions));
            }
        }
        $countSelect = clone $select;
        $countSelect->reset(Select::COLUMNS)->reset(Select::ORDER)->reset(Select::LIMIT_COUNT)
            ->reset(Select::LIMIT_OFFSET)->columns('COUNT(*)');
        $total = (int)$this->connection()->fetchOne($countSelect);
        $pageSize = min(100, max(1, (int)($searchCriteria->getPageSize() ?: 20)));
        $currentPage = max(1, (int)($searchCriteria->getCurrentPage() ?: 1));
        $select->order('c.coupon_id ASC')->limitPage($currentPage, $pageSize);
        $items = array_map(fn(array $row) => $this->createItem($row, false), $this->connection()->fetchAll($select));
        return $this->resultsFactory->create()
            ->setSearchCriteria($searchCriteria)->setTotalCount($total)->setItems($items);
    }

    public function createItem(array $row, bool $replayed)
    {
        $evaluatedAt = $this->clock->now();
        return $this->itemFactory->create()
            ->setCouponId((int)$row['coupon_id'])->setRuleId((int)$row['rule_id'])
            ->setCode((string)$row['code'])->setIssuedAt($this->externalDate((string)$row['issued_at']))
            ->setValidityDaysAtIssue((int)$row['validity_days_at_issue'])
            ->setRollingExpiresAt($this->externalDate((string)$row['rolling_expires_at']))
            ->setIssuanceSource((string)$row['issuance_source'])
            ->setIsExpired($this->policy->isExpired($row))
            ->setEvaluatedAt($evaluatedAt->format('Y-m-d\TH:i:s\Z'))->setReplayed($replayed);
    }

    private function baseSelect(): Select
    {
        return $this->connection()->select()
            ->from(['c' => $this->resource->getTableName('salesrule_coupon')], ['coupon_id', 'rule_id', 'code'])
            ->joinInner(['e' => $this->resource->getTableName('magenable_coupon_expiry')], 'e.coupon_id = c.coupon_id', ['issued_at', 'validity_days_at_issue', 'rolling_expires_at', 'issuance_source']);
    }

    private function condition(string $field, string $type, mixed $value): string
    {
        $type = strtolower($type ?: 'eq');
        return match ($type) {
            'eq' => $this->connection()->quoteInto($field . ' = ?', $value),
            'gteq' => $this->connection()->quoteInto($field . ' >= ?', $value),
            'lteq' => $this->connection()->quoteInto($field . ' <= ?', $value),
            default => throw new InputException(__('Filter condition "%1" is not supported.', $type)),
        };
    }

    private function externalDate(string $value): string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));
        return $date === false ? $value : $date->format('Y-m-d\TH:i:s\Z');
    }

    private function connection(): \Magento\Framework\DB\Adapter\AdapterInterface
    {
        return $this->resource->getConnection();
    }
}