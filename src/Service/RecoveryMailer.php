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
     */
    public function send(AbandonedCart $cart): bool
    {
        if ($cart->email === null || ! is_email($cart->email)) {
            return false;
        }

        $html = $this->render([
            'heading'     => $this->settings->emailHeading(),
            'body'        => $this->settings->emailBody(),
            'button'      => $this->settings->emailButton(),
            'restore_url' => RestoreHandler::url($cart->token),
            'site_name'   => get_bloginfo('name'),
        ]);

        $args = [
            'to'      => $cart->email,
            'subject' => $this->settings->emailSubject(),
            'message' => $html,
            'headers' => ['Content-Type: text/html; charset=UTF-8'],
        ];

        /**
         * Filters the recovery email arguments before they are handed to wp_mail().
         *
         * Add-ons (e.g. Recover Pro) use this to enrich the message — for example,
         * injecting a single-use recovery coupon — without modifying core.
         *
         * @param array{to:string, subject:string, message:string, headers:list<string>} $args  Mail arguments.
         * @param AbandonedCart                                                           $cart  The cart being recovered.
         */
        $args = apply_filters('recover/email/args', $args, $cart);

        return (bool) wp_mail(
            (string) $args['to'],
            (string) $args['subject'],
            (string) $args['message'],
            $args['headers'],
        );
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

        ob_start();
        include PLUGIN_DIR . '/templates/emails/recovery.php';

        return (string) ob_get_clean();
    }
}
