<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model\Data;

use Magenable\CouponExpiry\Api\Data\CouponExpirySearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class CouponExpirySearchResults extends SearchResults implements CouponExpirySearchResultsInterface
{
}