<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Model\Quote;

/**
 * Persists the minimum evidence needed to require customer totals review.
 */
class QuoteReviewMarkerResource
{
    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    public function mark(Quote $quote, string $couponCode, int $ruleId): void
    {
        $data = [
            'magenable_coupon_expiry_review_required' => 1,
            'magenable_expired_coupon_code' => $couponCode,
            'magenable_expired_coupon_rule_id' => $ruleId,
        ];
        $quote->addData($data);
        if (!$quote->getId()) {
            return;
        }
        $this->resource->getConnection()->update(
            $this->resource->getTableName('quote'),
            $data,
            ['entity_id = ?' => (int)$quote->getId()]
        );
    }
}
