<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Plugin\GraphQl;

use Magenable\CouponExpiry\Exception\ExpiredCouponException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Exposes only the extension's reviewed, customer-safe expiry message to GraphQL clients.
 */
class OrderErrorProcessorPlugin
{
    public function aroundExecute(
        object $subject,
        callable $proceed,
        LocalizedException $exception,
        Field $field,
        ContextInterface $context,
        ResolveInfo $info
    ): void {
        if ($exception instanceof ExpiredCouponException) {
            throw new GraphQlInputException(__($exception->getMessage()), $exception);
        }
        $proceed($exception, $field, $context, $info);
    }
}
