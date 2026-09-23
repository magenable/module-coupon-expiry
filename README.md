# Magenable CouponExpiry

Magenable CouponExpiry adds rolling, per-coupon validity to generated Magento cart price rule coupons. Each managed coupon receives an immutable issue timestamp, validity snapshot and expiry timestamp when it is created.

## Features

- Rule-level **Coupon validity (days)** policy from 1 to 3650 days.
- Exact consecutive 24-hour validity periods calculated in UTC.
- Native Admin queued coupon generation and native coupon-management support.
- Atomic coupon and expiry-metadata persistence.
- Immutable issued-coupon expiry snapshots.
- Enforcement during totals collection and immediately before order submission.
- Persisted checkout review protection when a discount expires between checkout requests.
- Luma and Hyvä Theme storefront behavior.
- Admin coupon-grid issue time, expiry, status, filtering and export columns.
- Idempotent authenticated REST issuance API.
- Typed REST read and paginated search APIs with separate ACL permissions.
- Database-backed and broker-backed native Magento queue compatibility.

## Package

- Composer name: `magenable/module-coupon-expiry`
- Magento module: `Magenable_CouponExpiry`
- Package type: `magento2-module`
- Current version: `1.0.4`
- Public distribution repository: `https://github.com/magenable/module-coupon-expiry`

## Installation

Install through the Composer repository from which the package was supplied:

```bash
composer require magenable/module-coupon-expiry:^1.0
bin/magento module:enable Magenable_CouponExpiry
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Production deployments must follow the site's normal maintenance, static-content deployment, ownership and zero-downtime release procedures. See [Installation and Upgrade](docs/INSTALLATION.md).

## Queue prerequisite

Admin mass coupon generation publishes the native `sales_rule.codegenerator` topic. Magento's `codegeneratorProcessor` consumer must be processed by the deployment's normal queue supervisor or native cron consumer runner. The module does not start operating-system processes or replace Magento queue infrastructure.

Before resubmitting a generation request, operators should inspect the pending queue. Native batch generation is not idempotent; submitting the request twice can create two batches.

## Documentation

- [Merchant and Administrator Guide](ADMIN_GUIDE.md)
- [Installation and Upgrade](docs/INSTALLATION.md)
- [Compatibility and Support Matrix](docs/COMPATIBILITY.md)
- [Magento Open Source 2.4.9 Acceptance Report](docs/MAGENTO_OPEN_SOURCE_2.4.9_TEST_REPORT.md)
- [Magento Open Source 2.4.8-p5 Acceptance Report](docs/MAGENTO_OPEN_SOURCE_2.4.8-P5_TEST_REPORT.md)
- [Magento Open Source 2.4.7-p10 Acceptance Report](docs/MAGENTO_OPEN_SOURCE_2.4.7-P10_TEST_REPORT.md)
- [Adobe Commerce 2.4.8 Test Note](docs/ADOBE_COMMERCE_2.4.8_TEST_NOTE.md)
- [Adobe MFTF Report Analysis — 2026-09-21](docs/ADOBE_MFTF_REPORT_2026-09-21.md)
- [REST API Guide](API.md)
- [Operations and Rollback Runbook](OPERATIONS.md)
- [Security Policy](SECURITY.md)
- [Support](SUPPORT.md)
- [Release Notes](CHANGELOG.md)

## Compatibility

Version 1.0.4 is tested and supported on the same runtime matrix as 1.0.3; 1.0.4 changes licensing and documentation only:

- **Magento Open Source 2.4.9** with PHP 8.5.10, Luma/standard Magento checkout, RabbitMQ, Varnish, Valkey, OpenSearch 3, and Adobe's July–September 2026 isolated security patches plus APSB26-146.
- **Magento Open Source 2.4.8-p5** with PHP 8.4, Luma/standard checkout, RabbitMQ 4.3, Varnish 8, Valkey 8.1, OpenSearch 3, and Adobe's July–September 2026 isolated security patches plus APSB26-146.
- **Magento Open Source 2.4.7-p10** with PHP 8.3, Luma/standard checkout, RabbitMQ 4.3, Varnish 8, Valkey 8.1, OpenSearch 3, and Adobe's July–September 2026 isolated security patches plus APSB26-146.
- **Adobe Commerce 2.4.8**, successfully tested in a seller-provided Adobe Commerce environment. The exact patch level, PHP version and infrastructure topology were not supplied for the public test report.
- **Mage-OS 3.5.0** (Magento 2.4.9 base) with PHP 8.3, Luma, and Hyvä Theme using the standard Magento/Luma checkout fallback.

Magento Open Source acceptance includes synchronous and queued issuance, REST and GraphQL APIs, full JavaScript checkout expiry/review behavior, Admin grid and exports, and native customer reorder. See the [Magento Open Source 2.4.9 Acceptance Report](docs/MAGENTO_OPEN_SOURCE_2.4.9_TEST_REPORT.md) and [Compatibility and Support Matrix](docs/COMPATIBILITY.md).

Adobe Commerce 2.4.8 compatibility was separately confirmed by seller testing. Other Adobe Commerce versions and optional Commerce-only features are not claimed unless explicitly listed in the [Compatibility and Support Matrix](docs/COMPATIBILITY.md).

## Security model

- Administrative REST routes use Magento authentication and dedicated read/issue ACL resources.
- Idempotency keys are scoped to the authenticated principal and retained as tombstones.
- Managed metadata is immutable and malformed managed metadata fails closed.
- Coupon codes and request IDs should be redacted from edge and access logs where required by policy.
- No credentials, telemetry endpoints or external callbacks are bundled.

Report security issues according to [SECURITY.md](SECURITY.md).

## License

Copyright 2026 Magenable. Released under the MIT License. See [LICENSE.md](LICENSE.md).
