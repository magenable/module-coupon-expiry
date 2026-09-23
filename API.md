# Magenable CouponExpiry REST API

Base path: `/rest/{store_code}`. Authentication uses the site's supported Magento integration OAuth flow. Standalone bearer-token behavior is not enabled or required by this module. Request and response bodies are JSON.

## ACL resources

- `Magenable_CouponExpiry::issue` — issue one managed coupon.
- `Magenable_CouponExpiry::read` — retrieve and search managed coupons.

Both are administrative integration permissions. The selected store must belong to a website assigned to the rule.

## Issue

`POST /V1/magenable/coupon-expiry/issue`

```json
{"request":{"rule_id":42,"request_id":"crm-welcome-20260910-000123"}}
```

`request_id` must contain 1–128 non-control characters. It should be unique and contain no personal data. The rule must be active, assigned to the route's store website, configured as Specific Coupon with auto generation, and have rolling validity configured.

Success is HTTP 200. A retry by the same authenticated principal with the same request ID, rule and store returns the original coupon with `replayed: true`. Reusing the key for a different rule or store returns HTTP 409. Replaying after coupon deletion returns HTTP 404 and never creates a replacement.

```json
{
  "coupon_id": 123,
  "rule_id": 42,
  "code": "REDACTED-EXAMPLE",
  "issued_at": "2026-09-10T05:30:00Z",
  "validity_days_at_issue": 14,
  "rolling_expires_at": "2026-09-24T05:30:00Z",
  "issuance_source": "custom_api",
  "is_expired": false,
  "evaluated_at": "2026-09-10T05:30:00Z",
  "replayed": false
}
```

## Read

`GET /V1/magenable/coupon-expiry/coupons/{couponId}` returns one managed coupon or HTTP 404. Dynamic `is_expired` and `evaluated_at` values are recalculated for every read.

## Search

`GET /V1/magenable/coupon-expiry/coupons?searchCriteria[...]`

Supported fields are `coupon_id`, exact `code`, `rule_id`, `issued_at`, and `rolling_expires_at`. Supported conditions are `eq`, `gteq`, and `lteq`. Page size defaults to 20 and is capped at 100. Results always sort by coupon ID ascending for stable pagination. Coupon codes in URLs and request IDs must be redacted by edge/access logging configuration.

Example rule filter:

```text
searchCriteria[filter_groups][0][filters][0][field]=rule_id
searchCriteria[filter_groups][0][filters][0][value]=42
searchCriteria[page_size]=100
```

## Status mapping

- 200 — success or same-payload replay
- 400 — malformed request or ineligible rule/store
- 401/403 — native authentication or ACL rejection
- 404 — managed coupon/rule not found, including deleted-coupon replay
- 409 — idempotency key reused for another payload/store
- 500 — unexpected server failure; SQL and credentials are not returned by the module