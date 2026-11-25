<?php

declare(strict_types=1);

namespace Recover;

defined('ABSPATH') || exit;

/**
 * Typed accessor over the `recover_settings` option, with sane defaults so the
 * plugin works out of the box and never fatals on a missing/garbled option.
 */
final class Settings
{
    public const OPTION = 'recover_settings';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $stored = get_option(self::OPTION, []);

        return array_merge($this->defaults(), is_array($stored) ? $stored : []);
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'enabled'             => true,
            'capture_guests'      => true,
            'abandon_after'       => 60,   // Minutes of inactivity before a cart is "abandoned".
            'email_delay'         => 30,   // Minutes after abandonment before the recovery email.
            'email_count'         => 1,    // How many reminders to send, spaced by email_delay.
            'require_consent'     => true,
            'consent_label'       => '',
            'consent_selector'    => '',
            'match_wc_emails'     => false, // Colours and logo of the shop's WooCommerce emails instead of the bundled warm look.
            'retention_days'      => 90,   // Days a cart (and its email) is kept after its last change; 0 keeps it.
            'email_subject'       => '',
            'email_heading'       => '',
            'email_body'          => '',
            'email_button'        => '',
        ];
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $fallback;
    }

    public function enabled(): bool
    {
        return (bool) $this->get('enabled', true);
    }

    public function captureGuests(): bool
    {
        return (bool) $this->get('capture_guests', true);
    }

    public function requireConsent(): bool
    {
        return (bool) $this->get('require_consent', true);
    }

    public function abandonAfterMinutes(): int
    {
        return max(5, (int) $this->get('abandon_after', 60));
    }

    public function emailDelayMinutes(): int
    {
        return max(0, (int) $this->get('email_delay', 30));
    }

    /**
     * How many reminders a shopper receives, spaced by the email delay.
     *
     * The sequence engine has always been here; until 1.0.11 the count was
     * pinned to 1 in code with no way for a merchant to change it, which makes
     * it a built-in feature the plugin refused to run. It is a setting now.
     */
    public function emailCount(): int
    {
        return max(1, min(5, (int) $this->get('email_count', 1)));
    }

    public function consentLabel(): string
    {
        $value = (string) $this->get('consent_label', '');

        return $value !== ''
            ? $value
            : __('Email me a link to recover my cart if I do not complete my order.', 'plogins-recover');
    }

    /**
     * CSS selector of a consent checkbox another plugin already draws at
     * checkout (a marketing consent, say). When set, Recover draws no box of
     * its own and reads that one, so the shopper ticks one box, not two.
     */
    public function consentSelector(): string
    {
        return (string) $this->get('consent_selector', '');
    }

    /** Style the email with the colours and header image set in WooCommerce > Settings > Emails. */
    public function matchWooEmails(): bool
    {
        return (bool) $this->get('match_wc_emails', false);
    }

    /** Days a cart and its email are kept after the last change; 0 keeps them. */
    public function retentionDays(): int
    {
        return max(0, (int) $this->get('retention_days', 90));
    }

    public function emailSubject(): string
    {
        $value = (string) $this->get('email_subject', '');

        return $value !== '' ? $value : __('You left something in your cart', 'plogins-recover');
    }

    public function emailHeading(): string
    {
        $value = (string) $this->get('email_heading', '');

        return $value !== '' ? $value : __('Still thinking it over?', 'plogins-recover');
    }

    public function emailBody(): string
    {
        $value = (string) $this->get('email_body', '');

        return $value !== ''
            ? $value
            : __('We saved your cart for you. Click the button below to pick up right where you left off.', 'plogins-recover');
    }

    public function emailButton(): string
    {
        $value = (string) $this->get('email_button', '');

        return $value !== '' ? $value : __('Complete my order', 'plogins-recover');
    }
}
