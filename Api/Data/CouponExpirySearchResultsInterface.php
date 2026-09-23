<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface CouponExpirySearchResultsInterface extends SearchResultsInterface
{
    /** @return \Magenable\CouponExpiry\Api\Data\CouponExpiryInterface[] */
    public function getItems();
    /**
     * @param \Magenable\CouponExpiry\Api\Data\CouponExpiryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}