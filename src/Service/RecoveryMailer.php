<?php

declare(strict_types=1);

namespace Recover\Service;

defined('ABSPATH') || exit;

use Recover\Model\AbandonedCart;
use Recover\Settings;
use Recover\Util\TemplateLoader;

/**
 * Composes and sends a recovery email for a single abandoned cart via the site's
 * own WordPress mailer (wp_mail). No third-party service is contacted.
 */
final class RecoveryMailer
{
    public function __construct(
        private readonly Settings $settings,
        private readonly TemplateLoader $templates,
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

        $restoreUrl = RestoreHandler::url($cart->token);

        $subject = $this->settings->emailSubject();

        $html = $this->templates->render('emails/recovery', [
            'heading'     => $this->settings->emailHeading(),
            'body'        => $this->settings->emailBody(),
            'button'      => $this->settings->emailButton(),
            'restore_url' => $restoreUrl,
            'cart'        => $cart,
            'site_name'   => get_bloginfo('name'),
        ]);

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        /**
         * Filter the recovery email arguments before sending.
         *
         * @param array{to:string, subject:string, message:string, headers:list<string>} $args
         * @param AbandonedCart                                                            $cart
         */
        $args = apply_filters('recover/email/args', [
            'to'      => $cart->email,
            'subject' => $subject,
            'message' => $html,
            'headers' => $headers,
        ], $cart);

        return (bool) wp_mail($args['to'], $args['subject'], $args['message'], $args['headers']);
    }
}
