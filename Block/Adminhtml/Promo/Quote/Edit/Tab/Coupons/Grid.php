<?php
declare(strict_types=1);

namespace Magenable\CouponExpiry\Block\Adminhtml\Promo\Quote\Edit\Tab\Coupons;

use Magenable\CouponExpiry\Model\Clock\UtcClockInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\SalesRule\Model\RegistryConstants;
use Magento\SalesRule\Model\ResourceModel\Coupon\Collection;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory;

/**
 * Adds immutable rolling-expiry metadata to the native coupon grid and exports.
 */
class Grid extends \Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\Tab\Coupons\Grid
{
    private const STATUS_UNMANAGED = 0;
    private const STATUS_WITHIN_VALIDITY = 1;
    private const STATUS_EXPIRED = 2;

    private string $statusExpression = '';

    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        CollectionFactory $salesRuleCoupon,
        Registry $coreRegistry,
        private readonly TimezoneInterface $timezone,
        private readonly UtcClockInterface $clock,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $salesRuleCoupon, $coreRegistry, $data);
    }

    /**
     * Join the side table once so HTML and both native export actions share values.
     */
    protected function _prepareCollection(): static
    {
        $priceRule = $this->_coreRegistry->registry(RegistryConstants::CURRENT_SALES_RULE);
        /** @var Collection $collection */
        $collection = $this->_salesRuleCoupon->create()
            ->addRuleToFilter($priceRule)
            ->addGeneratedCouponsFilter();
        $connection = $collection->getConnection();
        $this->statusExpression = (string)$connection->getCaseSql(
            '',
            [
                $connection->quoteIdentifier('coupon_expiry.coupon_id') . ' IS NULL' => self::STATUS_UNMANAGED,
                $connection->quoteIdentifier('coupon_expiry.rolling_expires_at')
                    . ' <= ' . $connection->quote($this->clock->now()->format('Y-m-d H:i:s'))
                        => self::STATUS_EXPIRED,
            ],
            self::STATUS_WITHIN_VALIDITY
        );
        $collection->getSelect()->joinLeft(
            ['coupon_expiry' => $collection->getTable('magenable_coupon_expiry')],
            'main_table.coupon_id = coupon_expiry.coupon_id',
            [
                'magenable_issued_at' => 'issued_at',
                'magenable_rolling_expires_at' => 'rolling_expires_at',
                'magenable_time_status' => new Expression($this->statusExpression),
            ]
        );
        $collection->addFilterToMap('magenable_issued_at', 'coupon_expiry.issued_at');
        $collection->addFilterToMap('magenable_rolling_expires_at', 'coupon_expiry.rolling_expires_at');

        if ($this->_isExport && $this->getMassactionBlock()->isAvailable()) {
            $itemIds = $this->getMassactionBlock()->getSelected();
            if (!empty($itemIds)) {
                $collection->addFieldToFilter('main_table.coupon_id', ['in' => $itemIds]);
            }
        }

        $this->setCollection($collection);
        return \Magento\Backend\Block\Widget\Grid\Extended::_prepareCollection();
    }

    /**
     * Preserve native columns and append timezone-labelled rolling-expiry fields.
     */
    protected function _prepareColumns(): static
    {
        parent::_prepareColumns();
        $timezone = $this->timezone->getConfigTimezone();
        $dateColumn = [
            'type' => 'datetime',
            'align' => 'center',
            'width' => '180',
            'filter_time' => true,
            'default' => __('Not managed'),
        ];
        $this->addColumnAfter(
            'magenable_issued_at',
            array_merge(
                $dateColumn,
                [
                    'header' => __('Issued At (%1)', $timezone),
                    'index' => 'magenable_issued_at',
                ]
            ),
            'created_at'
        );
        $this->addColumnAfter(
            'magenable_rolling_expires_at',
            array_merge(
                $dateColumn,
                [
                    'header' => __('Rolling Expiry (%1)', $timezone),
                    'index' => 'magenable_rolling_expires_at',
                ]
            ),
            'magenable_issued_at'
        );
        $this->addColumnAfter(
            'magenable_time_status',
            [
                'header' => __('Coupon Validity Status'),
                'index' => 'magenable_time_status',
                'type' => 'options',
                'options' => $this->getStatusOptions(),
                'filter_condition_callback' => [$this, 'filterStatus'],
            ],
            'magenable_rolling_expires_at'
        );

        return $this;
    }

    /**
     * Filter the computed status without relying on a SELECT alias in WHERE.
     */
    public function filterStatus(Collection $collection, Column $column): void
    {
        $value = $column->getFilter()->getValue();
        if ($value === null || $value === '') {
            return;
        }
        $collection->getSelect()->where('(' . $this->statusExpression . ') = ?', (int)$value);
    }

    /**
     * @return array<int, \Magento\Framework\Phrase>
     */
    private function getStatusOptions(): array
    {
        return [
            self::STATUS_UNMANAGED => __('Not managed'),
            self::STATUS_WITHIN_VALIDITY => __('Within coupon validity'),
            self::STATUS_EXPIRED => __('Expired'),
        ];
    }
}