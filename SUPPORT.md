# Support

Magenable CouponExpiry is licensed under the MIT License. Support is provided through the private channel associated with the customer's marketplace order or a separate support agreement.

## Included support scope

- Installation and upgrade questions on versions listed as supported in `docs/COMPATIBILITY.md`.
- Reproduction and correction of defects in documented extension behavior.
- Guidance for native Magento cron, queue and consumer prerequisites.
- Clarification of merchant, API and rollback documentation.

## Project-specific services

Compatibility work for custom checkouts, multi-coupon implementations, direct database integrations, third-party SalesRule replacements, custom queue topologies and optional Commerce features requires separate assessment.

## Information to include

- Extension, Magento and PHP versions.
- Deployment mode and relevant enabled third-party modules.
- Reproduction steps using redacted test data.
- Exception report and relevant log excerpts.
- Queue topic/status when reporting delayed generation.
- Whether the issue occurs on an unmodified Luma checkout.

Never send production credentials, access tokens, private keys, full database dumps, customer personal data or live coupon-code exports. Use disposable fixtures and redact request IDs and coupon codes according to site policy.
