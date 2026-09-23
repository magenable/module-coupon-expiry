<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;

class IssuanceSourceResolver
{
    public function __construct(
        private readonly State $state,
        private readonly IssuanceContext $context
    ) {
    }

    public function resolve(): string
    {
        $contextSource = $this->context->getSource();
        if ($contextSource !== null) {
            return $contextSource;
        }

        try {
            $area = $this->state->getAreaCode();
        } catch (\Throwable) {
            return 'admin';
        }
        return $area === Area::AREA_ADMINHTML || $area === 'crontab' ? 'admin' : 'native_api';
    }
}
