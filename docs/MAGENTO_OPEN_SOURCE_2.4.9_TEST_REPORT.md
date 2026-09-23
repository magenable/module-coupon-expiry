# Magento Open Source 2.4.9 Acceptance Report

**Extension:** Magenable CouponExpiry 1.0.4
**Platform:** Magento Open Source 2.4.9 / PHP 8.5.10  
**Security state:** Adobe July–September 2026 isolated patches plus APSB26-146  
**Infrastructure:** MariaDB 12.3.3, OpenSearch 3.1.0, Valkey 9.0.6, RabbitMQ 4.3.6, Varnish 8.0.2

## Installation

The Marketplace-style 1.0.4 ZIP (SHA-256 `381b9c82863f0d078478471ce7e32f69ab4369736af875c3784e6458b441076d`) was installed as Composer package `magenable/module-coupon-expiry`, not copied to `app/code`, and revalidated on 2026-09-21. Its 47 runtime files are byte-identical to the fully accepted 1.0.3 artifact; 1.0.4 changes licensing/documentation only.

- Composer install/upgrade: passed
- Module enablement and declarative `setup:upgrade`: passed
- Dependency-injection compilation: passed
- Static-content deployment: passed
- Adobe CVT after Composer operations: four required patches applied, zero missing/unknown/vulnerable

## Source quality

- PHP syntax: passed
- XML parsing: passed
- PHPUnit 12 / PHP 8.5: **21 tests, 102 assertions**
- Magento Coding Standard v41 error gate: zero errors
- GitHub extension-quality workflow: passed

## Generation and metadata

- Native synchronous generation created four coupons and four immutable metadata rows.
- RabbitMQ `sales_rule.codegenerator` message created three additional coupons through `codegeneratorProcessor`.
- Every generated coupon had a 14-day snapshot and an exact `14 × 86,400`-second UTC interval.
- Queued and synchronous Admin contexts recorded `issuance_source=admin`.

## Expiry and checkout

- A valid managed coupon visibly contributed a 10% discount to sample product `24-MB01`.
- Expiry after contribution blocked totals and final quote submission, persisted the review marker and created zero invalid orders.
- Luma cart review displayed the expiry/removal warning, removed the coupon/discount and cleared the marker.
- Full JavaScript guest checkout blocked Place Order after expiry, created zero invalid orders, then allowed exactly one full-price order after explicit cart review.
- The fixture order and quotes were removed after verification.

## GraphQL

Magento 2.4.9's native `OrderErrorProcessor` masks ordinary `LocalizedException` messages. The dedicated expiry exception and narrow GraphQL mapper introduced in 1.0.1 remain verified in 1.0.3:

- Expired GraphQL `placeOrder`: blocked with zero orders.
- Client response includes `Coupon code "…" expired. Refresh the cart totals and review the updated order.`
- Unrelated localized order exceptions continue through Magento's native generic masking.

## REST APIs

- Issue: HTTP 200
- Same-payload replay: HTTP 200, same coupon, `replayed=true`
- Changed payload with reused request ID: HTTP 409
- Read by coupon ID: HTTP 200
- Deterministic filtered search: HTTP 200
- Deleted-coupon replay: HTTP 404 with no replacement coupon

## Admin

Real Admin browser session verified:

- Issued At column
- Rolling Expiry column
- Coupon Validity Status column
- Expired and Within coupon validity states
- CSV export: HTTP 200, non-empty, metadata headings present
- Excel XML export: HTTP 200, non-empty, metadata headings present

## Reorder

A disposable registered customer and discounted historical order were created through native services.

- Historical order retained original subtotal, discount, grand total, coupon and status.
- Native `Reorder::execute()` did not copy the historical coupon.
- Reapplying the current coupon discounted the reorder quote.
- Expiring it blocked totals and final submission.
- Order count remained one; no invalid reorder was created.
- Customer, order and quotes were fixture-scoped and removed.

## Cleanup and integrity

Final fixture checks returned zero for:

- acceptance rules
- `MAGACC`/`MAGQUE` coupons
- test quotes and review markers
- test orders/customers
- REST request-ledger fixtures
- orphan expiry metadata

No production or unrelated sample entities were modified.
