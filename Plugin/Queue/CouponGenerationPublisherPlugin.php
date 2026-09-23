<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Plugin\Queue;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\MessageQueue\Publisher;
use Magento\SalesRule\Api\Data\CouponGenerationSpecExtensionFactory;
use Magento\SalesRule\Api\Data\CouponGenerationSpecInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adds durable admin context before the coupon-generation DTO is serialized.
 */
class CouponGenerationPublisherPlugin
{
    private const TOPIC = 'sales_rule.codegenerator';

    public function __construct(
        private readonly CouponGenerationSpecExtensionFactory $extensionFactory,
        private readonly RequestInterface $request,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @return array{string, mixed}
     */
    public function beforePublish(Publisher $subject, string $topicName, mixed $data): array
    {
        if ($topicName !== self::TOPIC || !$data instanceof CouponGenerationSpecInterface) {
            return [$topicName, $data];
        }

        $attributes = $data->getExtensionAttributes() ?? $this->extensionFactory->create();
        $attributes->setMagenableStoreId($this->resolveStoreId());
        $attributes->setMagenableIssuanceSource('admin');
        $data->setExtensionAttributes($attributes);

        return [$topicName, $data];
    }

    private function resolveStoreId(): int
    {
        $requestedStore = $this->request->getParam('store');
        if ($requestedStore !== null && $requestedStore !== '') {
            try {
                return (int)$this->storeManager->getStore($requestedStore)->getId();
            } catch (\Throwable) {
                // Fall back to the current admin store; native validation still controls the rule.
            }
        }

        return (int)$this->storeManager->getStore()->getId();
    }
}