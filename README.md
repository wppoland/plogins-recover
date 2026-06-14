# Recover - Abandoned Cart Recovery for WooCommerce

Recover captures WooCommerce carts that are left behind and emails customers a secure, one-click link to finish checkout. It is fully self-hosted and privacy-minded — no third-party service and no customer data leaving your site.

## Features

- Snapshots the cart whenever it changes and captures the customer email early (logged-in users automatically; guests via a consent-gated checkout capture).
- Marks carts abandoned if checkout is not completed within a configurable window.
- Emails a recovery message on a WordPress cron schedule with a secure one-click restore link that repopulates the cart.
- Tracks pending, abandoned and recovered carts and shows your recovery rate.
- One-click per-email data wipe, with a clean uninstall that removes its data and scheduled task.

## Privacy and security

- Restore links use 64-character cryptographically random tokens — no ids and no personal data in the URL, so there is no enumeration risk.
- Guest email capture is gated behind an explicit consent checkbox.
- All output is escaped, all input sanitised, nonces protect every form and AJAX call, and admin pages require the `manage_woocommerce` capability.

## Installation

1. Upload the plugin to `/wp-content/plugins/recover`, or install it via **Plugins → Add New**.
2. Activate it. WooCommerce must be active.
3. Configure the abandonment window and email under **WooCommerce → Recover**.

## Frequently Asked Questions

**Is any cart data sent to a third party?**
No. Everything is stored on your own site and emails are sent through WordPress, so no data leaves your store.

**Is the restore link safe to share by email?**
Yes. Each link carries only an unguessable 64-character token — no customer id or email — so a cart cannot be restored or enumerated without it.

Built by WPPoland — https://plogins.com

License: GPL-2.0-or-later
