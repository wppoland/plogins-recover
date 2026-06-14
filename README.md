# Recover - Abandoned Cart Recovery for WooCommerce

Capture WooCommerce carts that are left behind and email customers a secure,
one-click link to finish checkout. Self-hosted, privacy-minded, no third-party
service.

## What it does

- Snapshots the cart whenever it changes and captures the customer email early
  (logged-in users automatically; guests via a consent-gated checkout capture).
- Marks carts **abandoned** if checkout is not completed within a configurable
  window.
- Emails a recovery message on a WordPress cron schedule containing a secure,
  tokenised one-click restore link that repopulates the cart.
- Tracks pending / abandoned / recovered carts and your recovery rate.

## Architecture

- `recover.php` — bootstrap. PHP/WooCommerce guards, HPOS + Blocks compatibility,
  boots on `init:0`, fires `recover/booted`, schedules/clears the cron event.
- `src/Plugin.php` — DI container + boot orchestration.
- `src/Service/CartTracker.php` — cart snapshots + early email capture (AJAX).
- `src/Service/RestoreHandler.php` — tokenised one-click restore link handler.
- `src/Service/RecoveryMailer.php` — composes/sends the recovery email via `wp_mail`.
- `src/Service/CronWorker.php` — idempotent sweep + send worker.
- `src/Repository/CartRepository.php` — custom `{prefix}_recover_carts` table.
- `src/Admin/SettingsPage.php`, `src/Admin/CartsPage.php` — WooCommerce submenu UI.

## Privacy & security

- Restore links are 64-char cryptographically random tokens — no ids, no PII in
  the URL (no IDOR / enumeration).
- Guest email capture is gated behind an explicit consent checkbox.
- One-click per-email data wipe; uninstall drops the table and clears the cron.
- All output escaped, all input sanitised, nonces on every form/AJAX call,
  `manage_woocommerce` capability on all admin pages.

## Development

```bash
composer install
composer cs        # PHP_CodeSniffer (WPCS subset)
composer analyse   # PHPStan level 6
```

A PRO companion lives in `wppoland/recover-pro` and boots via the
`recover/booted` action.

## License

GPL-2.0-or-later.
