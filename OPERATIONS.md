# Magenable CouponExpiry operations and rollback

This runbook applies to Magenable CouponExpiry 1.0.0. Commands assume the Magento project root and must be adapted to the deployment platform's release, maintenance and ownership model.

## Verified and project-qualified scope

| Area | Status |
|---|---|
| Mage-OS 3.5.0 / Magento 2.4.9 base, PHP 8.3 | Supported and tested |
| Native generated coupons: admin queue, native REST generation, repository save, custom REST issue | Supported |
| Luma and open-source Hyvä Theme cart/totals behavior | Supported and tested with full-JavaScript default Magento checkout; Hyvä uses the open-source Luma checkout fallback, while Hyvä Checkout is a separate, untested product |
| Guest REST, registered REST and guest GraphQL checkout submission | Supported and tested; expiry after coupon application rejected submission and created no order |
| Standard synchronous `QuoteManagement` submission guard used by normal storefront/API checkout | Supported |
| Admin order creation | Native `AdminOrder\Create::createOrder()` submission route tested with a stale contributing expired coupon; submission was blocked and no order was created |
| Customer reorder | Supported and tested with a disposable registered customer/order: the historical order remained unchanged, native reorder omitted the historical coupon, and a managed coupon reapplied before expiry was blocked after expiry without creating an invalid order |
| Optional Commerce features and third-party checkout replacements | Require explicit project qualification |
| Commerce or third-party multi-coupon checkout | Require explicit project qualification; the verified target uses one native quote coupon code |
| Arbitrary SQL/import writers | Unsupported unless an issuance adapter stamps metadata atomically |

No compatibility claim is made outside this matrix. Run checkout acceptance against the site's actual payment and checkout integrations before production deployment.

## Pre-deployment checks

1. Back up the database and verify that the backup can be read. Keep it outside the web root and source control.
2. Confirm `magenable/module-coupon-expiry` is present in `composer.lock` and that no duplicate `app/code/Magenable/CouponExpiry` copy exists.
3. Confirm application and database clocks are synchronized to UTC.
4. Record currently configured managed rules and coupon counts:

```sql
SELECT rule_id, name, is_active, magenable_coupon_validity_days
FROM salesrule
WHERE magenable_coupon_validity_days IS NOT NULL;

SELECT COUNT(*) AS managed_coupon_count,
       MIN(issued_at) AS first_issue_utc,
       MAX(rolling_expires_at) AS last_expiry_utc
FROM magenable_coupon_expiry;
```

5. Put the deployment into its normal maintenance/drain mode. Stop queue consumers only for the deployment window; do not delete queued messages.

## Install or upgrade

```bash
bin/magento module:enable Magenable_CouponExpiry
bin/magento setup:upgrade --keep-generated
bin/magento setup:di:compile
bin/magento cache:flush
```

Restore the platform-specific writable ownership and permissions on `generated`, `var`, `pub/static`, `pub/media`, and `app/etc`. Do not make the whole application tree world-writable.

Validate:

```bash
bin/magento module:status Magenable_CouponExpiry
bin/magento indexer:status
```

Then check the admin rule form and coupon grid, issue and replay a disposable custom API request, and exercise coupon apply plus checkout submission in every deployed storefront/checkout combination. Remove only the disposable fixture records after verification.

## Queue consumer prerequisite

Native admin mass generation publishes `sales_rule.codegenerator`; it does not create coupons until `codegeneratorProcessor` consumes that message. Production must provide either a supervised dedicated consumer or a working native cron consumer runner. Consumer count, maximum messages, wait behavior and restart policy belong to deployment infrastructure rather than this package.

Before retrying generation, inspect the exact topic and status instead of publishing a duplicate request:

```bash
bin/magento queue:consumers:list
bin/magento queue:consumers:start codegeneratorProcessor --single-thread --max-messages=1000
```

For the database backend, status `2` is new/pending, `3` is in progress, `4` is complete, `5` requires retry, `6` is an error, and `7` is marked for deletion. Native generation is not idempotent: broker or operator redelivery can generate a second batch, although already committed coupons retain immutable expiry metadata. Do not run both an unbounded duplicate consumer and the cron runner without intentional process supervision.

## Monitoring and integrity checks

A managed coupon must have exactly one immutable metadata row. Missing metadata means an unmanaged native coupon; malformed metadata fails closed.

```sql
-- Managed rows whose native coupon is unexpectedly absent (normally zero; FK prevents this).
SELECT e.coupon_id
FROM magenable_coupon_expiry AS e
LEFT JOIN salesrule_coupon AS c ON c.coupon_id = e.coupon_id
WHERE c.coupon_id IS NULL;

-- Invalid snapshots (must be zero).
SELECT coupon_id, issued_at, validity_days_at_issue, rolling_expires_at, issuance_source
FROM magenable_coupon_expiry
WHERE validity_days_at_issue NOT BETWEEN 1 AND 3650
   OR rolling_expires_at <> DATE_ADD(issued_at, INTERVAL validity_days_at_issue DAY)
   OR issuance_source NOT IN ('admin', 'native_api', 'custom_api');

-- Ledger tombstones are retained deliberately and must not be deleted to permit reissue.
SELECT COUNT(*) AS deleted_request_tombstones
FROM magenable_coupon_expiry_request
WHERE deleted_at IS NOT NULL OR coupon_id IS NULL;
```

Treat integrity-query results as an incident. Do not “repair” issue timestamps from native `created_at` or delete idempotency rows without an audited business decision.

## Safe functional rollback

Disabling or removing the PHP module stops expiry enforcement and can revive otherwise-valid native coupons. Therefore **never disable this module while a managed parent rule remains able to discount a cart**.

1. Stop coupon-generation consumers and block custom/native issuance traffic.
2. In the admin, deactivate every rule with `magenable_coupon_validity_days` populated. If the admin is unavailable, use a reviewed application-level maintenance script; avoid ad-hoc schema changes.
3. Recollect or invalidate active carts according to site policy so stale discounts cannot proceed.
4. Verify no managed rule remains active:

```sql
SELECT rule_id, name
FROM salesrule
WHERE is_active = 1
  AND magenable_coupon_validity_days IS NOT NULL;
```

5. Only after the query returns zero, disable the module and rebuild generated configuration:

```bash
bin/magento module:disable Magenable_CouponExpiry
bin/magento setup:di:compile
bin/magento cache:flush
```

6. Keep `magenable_coupon_expiry`, `magenable_coupon_expiry_request`, and the rule policy column intact during a routine code rollback. They are needed for audit, tombstone semantics, and safe re-enable. Do not use `module:uninstall` or manually drop the tables as a rollback shortcut.
7. Restore writable ownership, verify storefront/admin health, and document the disabled rules and customer-impact decision.

## Re-enable after rollback

Deploy a corrected module version, run the install/upgrade sequence, verify integrity and checkout acceptance, then reactivate reviewed parent rules. Existing managed expiry timestamps remain authoritative; expired coupons must not be reissued or extended.

## Data-destructive uninstall

A permanent uninstall requires a separate approved migration and retention plan. Export the policy, metadata, and request ledger first; deactivate affected rules; decide how native coupons will be invalidated; and preserve idempotency tombstones for the required audit period. This release intentionally provides no automatic destructive uninstall script.
