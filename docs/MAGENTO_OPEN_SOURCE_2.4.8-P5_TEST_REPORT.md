# Magento Open Source 2.4.8-p5 Acceptance Report

**Extension:** Magenable CouponExpiry 1.0.4

**Platform:** Magento Open Source 2.4.8-p5 / PHP 8.4.25  
**Security:** July–September 2026 cumulative isolated CE patches plus APSB26-146  
**Infrastructure:** MariaDB 11.8, OpenSearch 3.1.0, Valkey 8.1, RabbitMQ 4.3.6, Varnish 8.0.2

The exact 1.0.4 release ZIP (SHA-256 `381b9c82863f0d078478471ce7e32f69ab4369736af875c3784e6458b441076d`) was installed through a persistent Composer artifact repository and revalidated on 2026-09-21. A file-by-file comparison confirmed that its 47 runtime files are byte-identical to fully accepted 1.0.3; 1.0.4 changes licensing/documentation only. PHP/XML lint, Magento PHPCS, 21 PHPUnit tests/78 assertions, setup upgrade, DI compile, static deployment, indexing and writable-directory checks passed. CVT reported all four required CE patches, zero missing/unknown patches and no vulnerable/unknown CVEs. Composer audit reported zero advisories.

Synchronous and RabbitMQ generation, immutable 14-day UTC metadata, totals/final-submit rejection, Luma cart and JavaScript checkout behavior, GraphQL full checkout with specific expiry messaging and zero-order delta, REST issue/replay/409/read/search/tombstone behavior, Admin status/filter/CSV/XML paths and registered reorder invariance passed. Disposable fixtures were removed with zero scoped rules, coupons/orphans, ledgers, quotes and orders remaining.

Adobe Commerce, Commerce staging, third-party checkout, multi-coupon and `Magento_AsyncOrder` remain unqualified/out of scope.
