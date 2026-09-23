<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface CouponExpiryRepositoryInterface
{
    /**
     * @param int $couponId
     * @return \Magenable\CouponExpiry\Api\Data\CouponExpiryInterface
     */
    public function getById($couponId);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magenable\CouponExpiry\Api\Data\CouponExpirySearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}