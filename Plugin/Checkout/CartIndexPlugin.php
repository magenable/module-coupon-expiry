<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Plugin\Checkout;

use Magenable\CouponExpiry\Model\CouponExpiryResource;
use Magenable\CouponExpiry\Model\ExpiryPolicy;
use Magento\Checkout\Controller\Cart\Index;
use Magento\Checkout\Model\Session;
use Magento\Framework\Message\Factory as MessageFactory;
use Magento\Framework\Message\MessageInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

/**
 * Recollects an expired cart before rendering and reports the changed totals.
 */
class CartIndexPlugin
{
    public function __construct(
        private readonly Session $checkoutSession,
        private readonly CouponExpiryResource $metadata,
        private readonly ExpiryPolicy $policy,
        private readonly MessageFactory $messageFactory,
        private readonly CartRepositoryInterface $quoteRepository
    ) {
    }

    public function beforeExecute(Index $subject): void
    {
        $quote = $this->checkoutSession->getQuote();
        if ((bool)$quote->getData('magenable_coupon_expiry_review_required')) {
            $code = (string)$quote->getData('magenable_expired_coupon_code');
            $this->refreshAfterExpiry($quote, $code);
            return;
        }
        $couponCode = trim((string)$quote->getCouponCode());
        if ($couponCode === '') {
            return;
        }
        $row = $this->metadata->getByCodes([$couponCode])[$couponCode] ?? null;
        if (!$row || !$this->policy->isExpired($row)) {
            return;
        }
        $this->refreshAfterExpiry($quote, $couponCode);
    }

    private function refreshAfterExpiry(Quote $quote, string $couponCode): void
    {
        $quote->setCouponCode('');
        $quote->setData('magenable_coupon_expiry_review_required', 0);
        $quote->setData('magenable_expired_coupon_code', null);
        $quote->setData('magenable_expired_coupon_rule_id', null);
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $this->quoteRepository->save($quote);
        $messages = (array)$quote->getMessages();
        $messages[] = $this->messageFactory->create(
            MessageInterface::TYPE_NOTICE,
            __('Coupon code "%1" expired and its discount was removed. Review the updated totals.', $couponCode)
        );
        $quote->setMessages($messages);
    }
}
