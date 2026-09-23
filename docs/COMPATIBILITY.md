# Compatibility and Support Matrix

## Version 1.0.4

Version 1.0.4 contains the same runtime code as 1.0.3; it changes licensing and documentation only. The detailed 1.0.3 acceptance reports therefore remain applicable to 1.0.4.

| Platform | Package compatibility | Runtime verification | Claim status |
|---|---|---|---|
| Mage-OS 3.5.0 / Magento 2.4.9 base, PHP 8.3 | Yes | Complete local acceptance | Supported |
| Magento Open Source 2.4.9, PHP 8.5 | Yes | Complete local acceptance on `2.4.9-2026-sep` plus APSB26-146 | Supported |
| Magento Open Source 2.4.8-p5, PHP 8.4 | Yes | Complete local acceptance | Supported |
| Magento Open Source 2.4.7-p10, PHP 8.3 | Yes | Complete local acceptance | Supported |
| Adobe Commerce 2.4.8 | Yes | Successful seller-provided Adobe Commerce test; exact patch level, PHP version and topology not recorded in this repository | Supported |

The package requires Magento component contracts at the 2.4.7 line or newer and PHP 8.3, 8.4 or 8.5. Mage-OS 3.5.0 satisfies these contracts through Composer package replacement.

Adobe Commerce compatibility is limited to version 2.4.8. Adobe Commerce Cloud and other Adobe Commerce versions are not implied by that result. See the [Adobe Commerce 2.4.8 Test Note](ADOBE_COMMERCE_2.4.8_TEST_NOTE.md) for the recorded claim boundary. Do not claim Marketplace compatibility for Magento Open Source or Adobe Commerce versions that have not completed appropriate installation and functional testing.

## Verified functional scope

- Specific Coupon cart price rules with auto generation.
- Native Admin queued generation.
- Native synchronous coupon-management generation.
- Custom idempotent REST issuance and managed-coupon reads.
- Luma storefront and standard Magento checkout.
- Hyvä Theme with the standard Magento/Luma checkout fallback.
- Guest and registered storefront checkout.
- REST and GraphQL submission.
- Native Admin order creation.
- Native customer reorder.
- Database-backed native Magento queue on Mage-OS.
- RabbitMQ-backed native Magento queue on Magento Open Source.
- Magento Admin coupon grid, filters, CSV export and Excel XML export.

## Requires project-specific qualification

- Custom and third-party checkout implementations.
- Commerce or third-party multi-coupon implementations.
- Custom coupon importers or direct database writers.
- Alternative message brokers and topology customizations.
- Third-party SalesRule replacements or broad preferences.
- Sites that modify native coupon generation, totals collection or quote submission internals.

## Explicit product scope

Adobe Commerce-only features that are not part of Magento Open Source or Mage-OS are not required for the base product. Compatibility with an optional Commerce feature is claimed only when that feature is explicitly listed and tested in a licensed Adobe Commerce environment.
