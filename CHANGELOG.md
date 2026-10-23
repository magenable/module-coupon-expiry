# Release Notes

All notable changes to Magenable CouponExpiry are documented here. Versions follow Semantic Versioning.

## Unreleased

### Documentation

- Document Adobe's 2026-09-21 MFTF results: CE passed 20/20; two EE core product tests failed because the entire storefront category menu was empty despite successful category creation.
- Record the extension-impact audit and local Varnish-backed reproduction showing no CouponExpiry Catalog/navigation integration or reproducible module defect.
- Resize the Marketplace product icon to Adobe's required 240 × 240 dimensions.
- Prepare release-only publication to `magenable/module-coupon-expiry` using a single-repository fine-grained token and clean tagged snapshots.
- Add future-release synchronization of private `main` and immutable tags to GitLab through a dedicated write deploy key.

## 1.0.5 — 2026-10-23

- Tested with Magento v2.4.10-alpha1

## 1.0.4 — 2026-09-21

### Changed

- Re-license the package from a proprietary license to the MIT License.
- Update Composer metadata and Adobe Commerce Marketplace metadata to use the standard `MIT`/`mit` license identifiers.

### Documentation

- Record successful seller testing on Adobe Commerce 2.4.8 and define the claim boundary for unrecorded environment details and Commerce-only features.
- Rewrite the Marketplace long description to follow Adobe's required opening-paragraph, Features-heading and vertical-bullet structure.
- Refresh the Marketplace user-guide PDF and submission-readiness checklist.
- Add a compliant product icon and three gallery images based on disposable Admin and storefront fixtures.

## 1.0.3 — 2026-09-19

### Fixed

- Keep the Magento 2.4.9 `OrderErrorProcessor` plugin loadable on Magento 2.4.7-p10 and 2.4.8-p5, where that core class does not exist and the older resolver already returns the extension's localized domain message.
- Remove the unnecessary concrete subject type from the plugin and its unit tests; DI still targets only Magento 2.4.9's exact processor class.

## 1.0.2 — 2026-09-19

### Fixed

- Widen Magento component constraints to the verified 2.4.7-p10 floor so Composer can install the same package on Magento Open Source 2.4.7-p10, 2.4.8-p5 and 2.4.9 without bypassing platform or dependency checks.

### Compatibility

- Add Magento Open Source 2.4.7-p10 and 2.4.8-p5 matrix qualification; Adobe Commerce remains unqualified.

## 1.0.1 — 2026-09-18

### Fixed

- Preserve the reviewed expired-coupon message for Magento 2.4.9 GraphQL `placeOrder` responses.
- Use a dedicated expiry exception so only CouponExpiry's customer-safe message bypasses Magento's generic internal-order-error masking; unrelated exceptions remain masked.

### Compatibility

- Add an explicit Magento QuoteGraphQl package/module dependency for the supported GraphQL contract.
- Qualify Magento Open Source 2.4.9 with PHP 8.5 and Adobe July–September 2026 isolated security patches.

## 1.0.0 — 2026-09-16

Initial product release.

### Added

- Rolling validity policy for auto-generated specific cart price rule coupons.
- Immutable issue time, validity snapshot, rolling expiry and issuance-source metadata.
- Native synchronous and queued coupon-generation integration.
- Totals-time validation and final quote-submission protection.
- Persisted customer-review marker for coupons that expire during multi-request checkout.
- Admin coupon-grid status, timezone-aware timestamps, filters and CSV/Excel XML exports.
- Principal-scoped idempotent REST issuance with replay, conflict and deletion-tombstone behavior.
- Managed-coupon read and deterministic paginated search APIs.
- Luma, Hyvä Theme, REST, GraphQL, Admin order and native reorder verification.
- Installation, merchant, API, operations, compatibility and security documentation.
