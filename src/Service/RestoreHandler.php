<?php

declare(strict_types=1);

namespace Recover\Service;

defined('ABSPATH') || exit;

use Recover\Contract\HasHooks;
use Recover\Model\AbandonedCart;
use Recover\Repository\CartRepository;

/**
 * Handles the one-click, tokenised restore link from recovery emails.
 *
 * The token is a 64-char cryptographically random value stored against the cart
 * row; it is the only thing that authorises a restore, so there is no IDOR risk
 * (no sequential ids in the URL) and no PII is exposed in the link.
 */
final class RestoreHandler implements HasHooks
{
    public const QUERY_VAR = 'recover_token';

    public function __construct(
        private readonly CartRepository $repository,
    ) {
    }

    public function registerHooks(): void
    {
        add_action('wp_loaded', [$this, 'maybeRestore']);
    }

    /**
     * Build the public restore URL for a cart token.
     */
    public static function url(string $token): string
    {
        return add_query_arg(self::QUERY_VAR, rawurlencode($token), wc_get_cart_url());
    }

    public function maybeRestore(): void
    {
        // Read-only, token-authorised public link from an email; no nonce is
        // possible (the link is emailed) — the unguessable token is the auth.
        if (! isset($_GET[self::QUERY_VAR])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $token = sanitize_text_field(wp_unslash((string) $_GET[self::QUERY_VAR])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        // Tokens are exactly 64 hex chars; reject anything else cheaply.
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) {
            $this->redirectToCart();
            return;
        }

        if (! function_exists('WC') || ! WC() instanceof \WooCommerce || WC()->cart === null) {
            return;
        }

        $cart = $this->repository->findByToken($token);
        if ($cart === null || $cart->status === AbandonedCart::STATUS_RECOVERED) {
            $this->redirectToCart();
            return;
        }

        $this->repopulate($cart);

        // A successful restore counts as a recovery for analytics; the order may
        // still not complete, but the customer returned via our link.
        $this->repository->markRecovered($cart->id);

        $this->redirectToCart();
    }

    private function repopulate(AbandonedCart $cart): void
    {
        $wc = WC();
        if (! $wc instanceof \WooCommerce || $wc->cart === null) {
            return;
        }

        $wc->cart->empty_cart();

        foreach ($cart->cartContents as $item) {
            if (! is_array($item) || empty($item['product_id'])) {
                continue;
            }

            $productId   = (int) $item['product_id'];
            $variationId = (int) ($item['variation_id'] ?? 0);
            $quantity    = max(1, (int) ($item['quantity'] ?? 1));
            $variation   = is_array($item['variation'] ?? null) ? $item['variation'] : [];

            $product = wc_get_product($variationId > 0 ? $variationId : $productId);
            if (! $product instanceof \WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock()) {
                continue;
            }

            $wc->cart->add_to_cart($productId, $quantity, $variationId, $variation);
        }
    }

    private function redirectToCart(): void
    {
        wp_safe_redirect(wc_get_cart_url());
        exit;
    }
}
