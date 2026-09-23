# Adobe MFTF Report Analysis — 2026-09-21

## Scope

- **Tested Marketplace package:** `magenable/module-coupon-expiry` 1.0.3
- **Report archive SHA-256:** `e96f0af620379d9e77a766f264eef9d601cf423ab3abdc992d006919e506ae0e`
- **Report editions:** Magento Open Source (`CE`) and Adobe Commerce (`EE`)
- **Runtime release after analysis:** 1.0.4 has the same PHP/runtime code as 1.0.3; it changes licensing and product documentation only.

## Results

| Edition | Passed | Failed | Total |
|---|---:|---:|---:|
| CE | 20 | 0 | 20 |
| EE | 19 | 2 | 21 |

Composer installation, Cloud build, Cloud deploy, post-deploy, Varnish configuration, indexer configuration, and initial cache flush commands all completed with exit code 0 in both editions.

## EE failures

### MC-84: Admin should be able to create a configurable product with attributes

The category, configurable product, child products, custom attribute, and Admin assertions completed successfully. The final storefront step failed because this selector was absent:

```text
//nav//li[a[span[contains(., 'ApiCategory…')]]]
```

### MC-26602: Admin Create Grouped Product

The category, simple products, grouped product, associated-product configuration, save, grid lookup, and Admin edit assertions completed successfully. The final storefront step failed because this selector was absent:

```text
//nav//li[a[span[contains(., 'simpleCategory…')]]]
```

## Evidence

- Both category-create API requests returned successful category records.
- Each category was active, had `parent_id=2`, had `include_in_menu=true`, and had a generated URL key/path.
- Both failure screenshots show a normal Luma home page with no category navigation entries at all.
- The saved failure DOM contains an empty storefront menu list:

```html
<ul id="ui-id-1" role="menu" class="ui-menu ui-widget ui-widget-content"></ul>
```

- The failure DOM contains no `Magenable` or `CouponExpiry` markup and no PHP exception.
- The same two tests passed in the CE run.
- Nineteen other EE tests passed, including native category and product-management tests.

## Extension impact audit

CouponExpiry has no plugin, observer, preference, schema change, layout change, or dependency for:

- `Magento_Catalog`
- catalog categories
- top-menu/navigation generation
- block/page-cache invalidation
- Varnish invalidation

Its global message-queue publisher plugin returns immediately without mutation unless the topic is exactly `sales_rule.codegenerator` and the payload is a coupon-generation specification.

## Local reproduction

The Adobe category payload was reproduced on the Varnish-backed Magento Open Source 2.4.9 test environment with CouponExpiry enabled and every Magento cache type enabled:

1. Create an active category through `POST /rest/V1/categories` without explicitly supplying `parent_id` or `include_in_menu`.
2. Confirm the response uses `parent_id=2` and `include_in_menu=true`.
3. Load the storefront through the configured Varnish endpoint.
4. Confirm the category is present in rendered navigation.
5. Delete the fixture category.

Result: passed; fixture cleanup returned zero residual category data.

## Conclusion and resubmission decision

The two failures do not identify a CouponExpiry defect. They are consistent with stale or empty EE storefront navigation state, cache invalidation, or test-environment root-category configuration. Adding Catalog/cache behavior to CouponExpiry would be unrelated, invasive, and unsupported by the evidence.

The appropriate action is to resubmit the independently validated 1.0.4 artifact so Adobe reruns its automation. If the same EE-only failures repeat, provide this report to Adobe Marketplace support and request review of the EE test environment or core MFTF isolation rather than adding unrelated production code.
