# Plogins Recover - wp.org submission packet

Next in the queue now that Plogins Withdraw was approved (2026-09-08) and the
review slot is free. Only one plugin can sit in review at a time.

Not part of the shipped zip (`.distignore` excludes `/SUBMISSION.md`).

## Upload

- **Zip:** `/tmp/plogins-recover.zip`, built with `bash scripts/build-zip.sh` honouring `.distignore`.
- **Add your plugin:** https://wordpress.org/plugins/developers/add/
- **Requested slug:** `plogins-recover` (matches the text domain, so no TextDomainMismatch).
- **Version in this package:** 1.0.10

## Short description (150 chars max)

Recover abandoned WooCommerce carts: capture the email early, save the cart, email a secure one-click link to finish checkout.

(127 characters, and the same line the readme carries, so the two agree.)

## One-paragraph description (paste into the submission form)

Recover saves a private snapshot of a WooCommerce cart as soon as a shopper puts
something in it, captures the customer email early (automatically for logged-in
customers, and for guests only after they tick a consent checkbox you can edit or
switch off), and marks the cart abandoned if checkout is not completed within a
window the shop sets. A WordPress cron job then sends one recovery email through
the site's own `wp_mail()`, carrying a tokenised restore link that puts every
item back into the cart and returns the shopper to checkout. The link holds a
64-character random token and nothing else: no customer id, no email in the URL.
Everything runs on the shop's own server, with cart data in a single custom table
and no third-party service, SDK or remote endpoint of any kind. An admin screen
lists abandoned, recovered and pending carts with a recovery rate, and erases
every stored cart for one email address in a click. The worker is idempotent, so
a re-run never sends a second email for the same cart. Compatible with HPOS and
the Cart/Checkout Blocks; uninstalling drops the table, removes both options and
clears the scheduled task. Tested on WordPress 7.1 with WooCommerce 11.1.

## Listing copy

- **Display name:** Plogins Recover - Abandoned Cart for WooCommerce
- **Full description / FAQ / changelog:** `readme.txt`, which the directory renders.

## Pre-submission checks run

- Official **Plugin Check** against the built package in wp-env with WooCommerce
  active: **0 errors at severity 7**, the reviewer's actual gate. One warning
  remains, see below.
- Package audit: no `vendor/`, `tests/`, `.wordpress-org/`, `.po`, `.mo`,
  `composer.json` or `SUBMISSION.md` in the zip. The `.pot` ships alone, which is
  what WordPress.org wants; language packs come from translate.wordpress.org.
- **Top folder is `plogins-recover`**, matching the text domain and the requested
  slug. This was broken until today: `build-zip.sh` named the folder after the
  checkout directory (`recover`), which is a TextDomainMismatch at review. The
  same bug was in 29 repos here and all of them are fixed.
- Version agreement: header, `const VERSION` and `Stable tag` all read 1.0.10.
- `.pot` regenerated and compared: no drift from the code.
- pl_PL, de_DE and es_ES catalogues are complete, 80 of 80 strings each. They are
  staged for the GlotPress import, not bundled.
- readme: 5 tags (the maximum), short description 127 chars, `Requires Plugins:
  woocommerce` present, `Tested up to` in readme.txt only.
- `.wordpress-org/` carries both icon sizes, both banners, two screenshots and a
  Playground blueprint. `icon-128x128.png` was missing and has been generated.

## The one remaining warning, and why it stays

`WordPress.NamingConventions.PrefixAllGlobals` flags the `recover/email` hook
name as an invalid PHP prefix. It is a hook string, not a PHP symbol, so the
sniff is misreading it, and it is a warning rather than an error.

Renaming the hooks to `plogins_recover/...` would be the tidier answer, but the
paid add-on boots by listening for `recover/booted`, so the two would have to
change in lockstep or PRO would silently never boot. Not worth doing under a
warning the reviewer does not gate on.

## Still to do

- Upload the zip. That needs a WordPress.org login, so it is the user's to run.

## After approval

- Add `recover:plogins-recover` to the `PUBLISHED` map in
  `scripts/release/wporg-release.sh`. Without it a later bump silently never ships.
- Registry: set `status: "live"` **and** `wpOrgLive: true` together, change the
  badge off "coming soon" in all four locales, and add at least two `notFor`
  bullets. `check-claims` fails the build on the badge, the hedging in the
  install docs, and the missing bullets, which is exactly what it caught for
  Withdraw.
- Deploy the store so the wp.org link and `/go/plogins-recover/` resolve.
- `wporg-release.sh` now creates the GitHub release itself, so nothing extra
  there.
