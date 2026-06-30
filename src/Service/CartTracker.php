<?php

declare(strict_types=1);

namespace Recover\Service;

defined('ABSPATH') || exit;

use Recover\Contract\HasHooks;
use Recover\Repository\CartRepository;
use Recover\Settings;

/**
 * Captures the live cart as a recoverable snapshot and records the customer
 * email as early as possible (logged-in users immediately, guests via a small
 * consent-gated AJAX capture on the checkout email field).
 */
final class CartTracker implements HasHooks
{
    private const SESSION_TOKEN_KEY = 'recover_token';
    private const AJAX_ACTION       = 'recover_capture_email';
    private const NONCE_ACTION      = 'recover_capture';

    public function __construct(
        private readonly CartRepository $repository,
        private readonly Settings $settings,
    ) {
    }

    public function registerHooks(): void
    {
        if (! $this->settings->enabled()) {
            return;
        }

        // Snapshot the cart whenever it changes.
        add_action('woocommerce_cart_updated', [$this, 'snapshotCart']);
        add_action('woocommerce_add_to_cart', [$this, 'snapshotCart']);
        add_action('woocommerce_cart_item_removed', [$this, 'snapshotCart']);

        // Mark the cart recovered the moment an order is placed.
        add_action('woocommerce_checkout_order_processed', [$this, 'onOrderProcessed']);
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'onOrderProcessed']);

        // Early email capture on the checkout email field.
        add_action('wp_enqueue_scripts', [$this, 'enqueueCaptureScript']);
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'handleCapture']);
        add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [$this, 'handleCapture']);
    }

    /**
     * Persist the current cart snapshot. Idempotent (upsert by session/user).
     */
    public function snapshotCart(): void
    {
        if (! function_exists('WC') || is_admin()) {
            return;
        }

        $wc = WC();
        if (! $wc instanceof \WooCommerce || $wc->cart === null || $wc->cart->is_empty()) {
            return;
        }

        // Guests are only captured when allowed.
        $userId = get_current_user_id() ?: null;
        if ($userId === null && ! $this->settings->captureGuests()) {
            return;
        }

        $contents = $this->extractContents($wc->cart);
        if ($contents === []) {
            return;
        }

        $email   = $this->currentEmail($userId);
        $consent = $userId !== null && ! $this->settings->requireConsent();

        $token = $this->repository->upsert(
            $this->sessionKey(),
            $userId,
            $email,
            $contents,
            get_woocommerce_currency(),
            (float) $wc->cart->get_total('edit'),
            $wc->cart->get_cart_contents_count(),
            $consent,
        );

        if ($token !== null) {
            $this->storeToken($token);
        }
    }

    public function onOrderProcessed(mixed $orderId): void
    {
        $userId = get_current_user_id() ?: null;
        $this->repository->markRecoveredBySessionOrUser($this->sessionKey(), $userId);
        unset($orderId);
    }

    public function enqueueCaptureScript(): void
    {
        if (! is_checkout() || ! $this->settings->captureGuests()) {
            return;
        }

        $plugin = \Recover\Plugin::instance();

        wp_enqueue_script(
            'recover-capture',
            $plugin->url('assets/js/capture.js'),
            [],
            \Recover\VERSION,
            true,
        );

        wp_localize_script(
            'recover-capture',
            'RecoverCapture',
            [
                'ajaxUrl'       => admin_url('admin-ajax.php'),
                'action'        => self::AJAX_ACTION,
                'nonce'         => wp_create_nonce(self::NONCE_ACTION),
                'requireConsent' => $this->settings->requireConsent(),
                'consentLabel'  => $this->settings->consentLabel(),
            ],
        );
    }

    /**
     * AJAX: record the email (and consent) the shopper typed at checkout.
     */
    public function handleCapture(): void
    {
        if (! check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'plogins-recover')], 400);
        }

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash((string) $_POST['email'])) : '';
        if ($email === '' || ! is_email($email)) {
            wp_send_json_error(['message' => __('Provide a valid email address.', 'plogins-recover')], 422);
        }

        $consent = isset($_POST['consent']) && '1' === sanitize_text_field(wp_unslash((string) $_POST['consent']));
        if ($this->settings->requireConsent() && ! $consent) {
            // No consent: do not store the email. Silently succeed (no nagging).
            wp_send_json_success(['stored' => false]);
        }

        if (! function_exists('WC') || ! WC() instanceof \WooCommerce || WC()->cart === null || WC()->cart->is_empty()) {
            wp_send_json_success(['stored' => false]);
        }

        $userId   = get_current_user_id() ?: null;
        $wc       = WC();
        $contents = $this->extractContents($wc->cart);

        $this->repository->upsert(
            $this->sessionKey(),
            $userId,
            $email,
            $contents,
            get_woocommerce_currency(),
            (float) $wc->cart->get_total('edit'),
            $wc->cart->get_cart_contents_count(),
            true,
        );

        wp_send_json_success(['stored' => true]);
    }

    /**
     * @return array<int, array{product_id:int, variation_id:int, quantity:int, variation:array<string,mixed>}>
     */
    private function extractContents(\WC_Cart $cart): array
    {
        $items = [];

        foreach ($cart->get_cart() as $item) {
            if (! is_array($item) || empty($item['product_id'])) {
                continue;
            }

            $items[] = [
                'product_id'   => (int) $item['product_id'],
                'variation_id' => (int) ($item['variation_id'] ?? 0),
                'quantity'     => (int) ($item['quantity'] ?? 1),
                'variation'    => is_array($item['variation'] ?? null) ? $item['variation'] : [],
            ];
        }

        return $items;
    }

    private function currentEmail(?int $userId): ?string
    {
        if ($userId === null) {
            return null;
        }

        $user = get_userdata($userId);

        return ($user instanceof \WP_User && is_email($user->user_email)) ? $user->user_email : null;
    }

    private function sessionKey(): ?string
    {
        if (! function_exists('WC') || ! WC() instanceof \WooCommerce || WC()->session === null) {
            return null;
        }

        $key = WC()->session->get_customer_id();

        return is_string($key) && $key !== '' ? $key : null;
    }

    private function storeToken(string $token): void
    {
        if (function_exists('WC') && WC() instanceof \WooCommerce && WC()->session !== null) {
            WC()->session->set(self::SESSION_TOKEN_KEY, $token);
        }
    }
}
