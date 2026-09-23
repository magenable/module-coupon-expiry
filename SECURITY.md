# Security Policy

## Supported versions

Security fixes are provided for the current supported release line listed in `docs/COMPATIBILITY.md`.

## Reporting a vulnerability

Do not disclose suspected vulnerabilities in a public issue or discussion. Use the private security-advisory feature of the source repository or the private support contact published by Magenable or the marketplace listing.

Include:

- Affected extension and Magento versions.
- Reproduction steps or proof of concept.
- Required permissions and attack preconditions.
- Observed and expected behavior.
- Potential confidentiality, integrity or availability impact.

Do not include live customer records, production coupon codes, access tokens or credentials. Use disposable fixtures and redact sensitive logs.

## Operational guidance

- Grant `Magenable_CouponExpiry::read` and `Magenable_CouponExpiry::issue` only to integrations that need them.
- Use HTTPS and Magento-supported OAuth/integration authentication.
- Keep Magento, PHP and this extension on supported security-patch levels.
- Protect message brokers and database queues with private networking and least-privilege credentials.
- Treat malformed managed metadata as an incident; do not bypass fail-closed validation.
- Rotate any credential accidentally included in a request, log or support bundle.
