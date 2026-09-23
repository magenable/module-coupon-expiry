<?php
declare(strict_types=1);
namespace Magenable\CouponExpiry\Model;

use Magenable\CouponExpiry\Api\CouponExpiryManagementInterface;
use Magenable\CouponExpiry\Api\Data\IssueRequestInterface;
use Magenable\CouponExpiry\Api\Exception\IdempotencyConflictException;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\Coupon\Codegenerator;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Store\Model\StoreManagerInterface;

class CouponExpiryManagement implements CouponExpiryManagementInterface
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly UserContextInterface $userContext,
        private readonly StoreManagerInterface $storeManager,
        private readonly RuleFactory $ruleFactory,
        private readonly CouponFactory $couponFactory,
        private readonly Codegenerator $codegenerator,
        private readonly IssuanceContext $context,
        private readonly CouponExpiryRepository $repository
    ) {
    }

    public function issue(IssueRequestInterface $request)
    {
        $principalId = (int)$this->userContext->getUserId();
        $principalType = (int)$this->userContext->getUserType();
        if ($principalId < 1 || $principalType < 1) {
            throw new AuthorizationException(__('An authenticated integration or administrator is required.'));
        }
        $ruleId = $request->getRuleId();
        $requestId = $request->getRequestId();
        if ($ruleId < 1) {
            throw new InputException(__('rule_id must be a positive integer.'));
        }
        if ($requestId === '' || strlen($requestId) > 128 || preg_match('/[\x00-\x1F\x7F]/', $requestId)) {
            throw new InputException(__('request_id must contain 1 to 128 non-control characters.'));
        }
        $store = $this->storeManager->getStore();
        $storeId = (int)$store->getId();
        $hash = hash('sha256', json_encode(['rule_id' => $ruleId, 'store_id' => $storeId], JSON_THROW_ON_ERROR));
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('magenable_coupon_expiry_request');
        $connection->beginTransaction();
        try {
            try {
                $connection->insert($table, [
                    'principal_type' => $principalType, 'principal_id' => $principalId,
                    'request_id' => $requestId, 'store_id' => $storeId,
                    'rule_id' => $ruleId, 'payload_hash' => $hash,
                ]);
            } catch (\Throwable $insertFailure) {
                $existing = $this->loadRequest($principalType, $principalId, $requestId, true);
                if (!$existing) {
                    throw $insertFailure;
                }
                $item = $this->replay($existing, $hash);
                $connection->commit();
                return $item;
            }
            $requestEntityId = (int)$connection->lastInsertId($table);
            $rule = $this->ruleFactory->create()->load($ruleId);
            $days = $this->validateRule($rule, (int)$store->getWebsiteId());
            $coupon = $this->createCoupon($ruleId, $storeId, $days);
            $connection->update($table, ['coupon_id' => (int)$coupon->getId()], ['request_entity_id = ?' => $requestEntityId]);
            $connection->commit();
            return $this->repository->getById((int)$coupon->getId());
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function replay(array $row, string $hash)
    {
        if (!hash_equals((string)$row['payload_hash'], $hash)) {
            throw new IdempotencyConflictException(__('The request_id was already used with a different payload or store.'));
        }
        if (empty($row['coupon_id'])) {
            throw new NoSuchEntityException(__('The original issuance did not produce an available coupon.'));
        }
        try {
            $item = $this->repository->getById((int)$row['coupon_id']);
        } catch (NoSuchEntityException) {
            throw new NoSuchEntityException(__('The originally issued coupon was deleted; this request cannot issue a replacement.'));
        }
        $item->setReplayed(true);
        return $item;
    }

    private function validateRule(Rule $rule, int $websiteId): int
    {
        if (!$rule->getId()) {
            throw NoSuchEntityException::singleField('ruleId', $rule->getId());
        }
        $days = (int)$rule->getData('magenable_coupon_validity_days');
        $today = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if (!(bool)$rule->getIsActive() || ((string)$rule->getFromDate() !== '' && $today < new \DateTimeImmutable((string)$rule->getFromDate(), new \DateTimeZone('UTC')))
            || ((string)$rule->getToDate() !== '' && $today >= new \DateTimeImmutable((string)$rule->getToDate() . ' +1 day', new \DateTimeZone('UTC')))
        ) {
            throw new InputException(__('The cart price rule is not currently active.'));
        }
        if ((int)$rule->getCouponType() !== Rule::COUPON_TYPE_SPECIFIC || !(bool)$rule->getUseAutoGeneration() || $days < 1 || $days > 3650) {
            throw new InputException(__('The rule must use auto-generated specific coupons with rolling validity configured.'));
        }
        if (!in_array($websiteId, array_map('intval', (array)$rule->getWebsiteIds()), true)) {
            throw new InputException(__('The cart price rule is not assigned to the requested store website.'));
        }
        return $days;
    }

    private function createCoupon(int $ruleId, int $storeId, int $days): Coupon
    {
        $this->codegenerator->setData(['length' => 16, 'format' => 'alphanum', 'delimiter_at_every' => 0]);
        $this->context->enter($storeId, 'custom_api', $ruleId, $days);
        try {
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $coupon = $this->couponFactory->create();
                $coupon->setRuleId($ruleId)->setType(Coupon::TYPE_GENERATED)->setCode($this->codegenerator->generateCode());
                try {
                    $coupon->save();
                    return $coupon;
                } catch (\Magento\Framework\Exception\AlreadyExistsException) {
                    continue;
                }
            }
        } finally {
            $this->context->leave();
        }
        throw new InputException(__('A unique coupon code could not be generated.'));
    }

    private function loadRequest(int $type, int $id, string $requestId, bool $forUpdate): array|false
    {
        $select = $this->resource->getConnection()->select()
            ->from($this->resource->getTableName('magenable_coupon_expiry_request'))
            ->where('principal_type = ?', $type)->where('principal_id = ?', $id)->where('request_id = ?', $requestId);
        if ($forUpdate) {
            $select->forUpdate(true);
        }
        return $this->resource->getConnection()->fetchRow($select);
    }
}