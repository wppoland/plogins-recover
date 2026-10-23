<?php

declare(strict_types=1);

namespace Recover\Service;

defined('ABSPATH') || exit;

use Recover\Model\AbandonedCart;
use Recover\Settings;

use const Recover\PLUGIN_DIR;

/**
 * Composes and sends a recovery email for a single abandoned cart via the site's
 * own WordPress mailer (wp_mail). No third-party service is contacted.
 */
final class RecoveryMailer
{
    public function __construct(
        private readonly Settings $settings,
    ) {
    }

    /**
     * Send the recovery email. Returns true on a successful hand-off to wp_mail.
     *
     * @param int $step Zero-based index of the email in the recovery sequence.
     */
    public function send(AbandonedCart $cart, int $step = 0): bool
    {
        if ($cart->email === null || ! is_email($cart->email)) {
            return false;
        }

        $items = $this->items($cart);

        $templateArgs = [
            'subject'     => $this->settings->emailSubject(),
            'heading'     => $this->settings->emailHeading(),
            'body'        => $this->settings->emailBody(),
            'button'      => $this->settings->emailButton(),
            'restore_url' => RestoreHandler::url($cart->token),
            'site_name'   => get_bloginfo('name'),
            'items'       => $items,
            // The stored total still counts lines dropped since capture, so it
            // is shown only when every captured line is listed above it.
            'total'       => function_exists('wc_price') && count($items) === count($cart->cartContents) ? wc_price($cart->cartTotal, $this->priceArgs($cart)) : '',
        ];

        /**
         * Filters the template variables used to render the recovery email.
         *
         * `items` is a list of {name, quantity, image, line_total}; `line_total`
         * and `total` are price HTML from wc_price().
         *
         * @param array{subject:string, heading:string, body:string, button:string, restore_url:string, site_name:string, items:list<array{name:string, quantity:int, image:string, line_total:string}>, total:string} $templateArgs
         * @param AbandonedCart                                                                                              $cart
         * @param int                                                                                                        $step Zero-based sequence index.
         */
        $templateArgs = apply_filters('recover/email/template_args', $templateArgs, $cart, $step);

        $html = $this->render($templateArgs);

        $args = [
            'to'      => $cart->email,
            'subject' => (string) $templateArgs['subject'],
            'message' => $html,
            'headers' => ['Content-Type: text/html; charset=UTF-8'],
        ];

        /**
         * Filters the recovery email arguments before they are handed to wp_mail().
         *
         * Add-ons (e.g. Recover Pro) use this to enrich the message, for example,
         * injecting a single-use recovery coupon, without modifying core.
         *
         * @param array{to:string, subject:string, message:string, headers:list<string>} $args  Mail arguments.
         * @param AbandonedCart                                                           $cart  The cart being recovered.
         * @param int                                                                     $step  Zero-based sequence index.
         */
        $args = apply_filters('recover/email/args', $args, $cart, $step);

        return (bool) wp_mail(
            (string) $args['to'],
            (string) $args['subject'],
            (string) $args['message'],
            $args['headers'],
        );
    }

    /**
     * Cart lines for the email. Products deleted or trashed since the cart was
     * captured are skipped. Line prices use the product's current price.
     *
     * @return list<array{name:string, quantity:int, image:string, line_total:string}>
     */
    public function items(AbandonedCart $cart): array
    {
        if (! function_exists('wc_get_product')) {
            return [];
        }

        $items = [];

        foreach ($cart->cartContents as $line) {
            $productId = (int) ($line['variation_id'] ?? 0);
            if ($productId <= 0) {
                $productId = (int) ($line['product_id'] ?? 0);
            }

            $product = $productId > 0 ? wc_get_product($productId) : null;
            if (! $product instanceof \WC_Product || $product->get_status() === 'trash') {
                continue;
            }

            $quantity = max(1, (int) ($line['quantity'] ?? 1));
            $imageId  = (int) $product->get_image_id();
            $image    = $imageId > 0 ? wp_get_attachment_image_url($imageId, 'thumbnail') : false;

            $items[] = [
                'name'       => $product->get_name(),
                'quantity'   => $quantity,
                'image'      => is_string($image) && $image !== '' ? $image : wc_placeholder_img_src('thumbnail'),
                'line_total' => wc_price(
                    (float) wc_get_price_to_display($product, ['qty' => $quantity]),
                    $this->priceArgs($cart),
                ),
            ];
        }

        return $items;
    }

    /**
     * Format prices in the currency the cart was captured in.
     *
     * @return array<string, string>
     */
    private function priceArgs(AbandonedCart $cart): array
    {
        return $cart->currency !== null ? ['currency' => $cart->currency] : [];
    }

    /**
     * Render the bundled recovery email template to an HTML string.
     *
     * @param array<string, mixed> $args
     */
    private function render(array $args): string
    {
        $recover_heading     = (string) $args['heading'];
        $recover_body        = (string) $args['body'];
        $recover_button      = (string) $args['button'];
        $recover_restore_url = (string) $args['restore_url'];
        $recover_site_name   = (string) $args['site_name'];
        $recover_items       = is_array($args['items'] ?? null) ? $args['items'] : [];
        $recover_total       = (string) ($args['total'] ?? '');

        ob_start();
        include PLUGIN_DIR . '/templates/emails/recovery.php';

        return (string) ob_get_clean();
    }
}
