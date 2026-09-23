# Installation and Upgrade

## Requirements

- A compatible Magento Open Source, Adobe Commerce or Mage-OS release listed in `COMPATIBILITY.md`.
- PHP version supported by that platform release.
- Composer 2.
- A functioning Magento cron and message-queue consumer configuration.
- Database and application-file backups tested before deployment.

## Composer installation

Configure the Composer repository supplied by Magenable or the marketplace, then run:

```bash
composer require magenable/module-coupon-expiry:^1.0
bin/magento module:enable Magenable_CouponExpiry
bin/magento setup:upgrade
```

For production mode, use the deployment sequence appropriate to the site, including dependency injection compilation and static-content deployment:

```bash
bin/magento setup:di:compile
bin/magento setup:static-content:deploy --force
bin/magento cache:flush
```

Do not run production deployment commands as an account that leaves Magento writable directories owned by the wrong user.

## Verify installation

```bash
bin/magento module:status Magenable_CouponExpiry
bin/magento queue:consumers:list
bin/magento indexer:status
```

Expected results:

- `Magenable_CouponExpiry` is enabled.
- `codegeneratorProcessor` is present in the consumer list.
- Indexers are ready or completing normally.
- Admin and storefront pages render without exceptions.

Create a disposable eligible cart price rule, generate one coupon, allow the standard consumer to process it, and verify that the Manage Coupon Codes grid displays its issue and rolling-expiry values.

## Upgrades

1. Review `CHANGELOG.md` and `docs/COMPATIBILITY.md`.
2. Back up the database and current package lock file.
3. Stop new coupon-issuance traffic for the deployment window.
4. Update the package:

```bash
composer require magenable/module-coupon-expiry:^1.0 --with-all-dependencies
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy --force
bin/magento cache:flush
```

5. Restore normal cron/consumer supervision.
6. Run the verification steps and a checkout smoke test.

## Uninstall and rollback

Disabling the PHP module can allow otherwise-valid native coupon codes to bypass rolling-expiry enforcement. Do not disable or remove it while managed parent rules remain active.

Use the non-destructive rollback procedure in `OPERATIONS.md`. This release intentionally does not drop audit metadata or idempotency tombstones automatically.
