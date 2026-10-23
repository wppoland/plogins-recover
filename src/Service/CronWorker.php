<?php

declare(strict_types=1);

namespace Recover\Service;

defined('ABSPATH') || exit;

use Recover\Contract\HasHooks;
use Recover\Model\AbandonedCart;
use Recover\Repository\CartRepository;
use Recover\Settings;

use const Recover\CRON_HOOK;

/**
 * Recovery worker. Runs every 10 minutes on Action Scheduler (bundled with
 * WooCommerce), falling back to the hourly WP-Cron event when Action Scheduler
 * is not available. Two idempotent passes per run:
 *   1. Sweep: mark pending carts inactive past the configured window as abandoned.
 *   2. Send: email a recovery link for each abandoned cart that is due and has
 *      not been emailed yet.
 *
 * Idempotency: a cart is only marked abandoned once (conditional status
 * transition), and each email step is claimed with a conditional update on
 * emails_sent before it is sent, so two overlapping runs never double-send.
 * Both passes are bounded by a LIMIT per run.
 */
final class CronWorker implements HasHooks
{
    public const GROUP    = 'plogins-recover';
    public const INTERVAL = 600;

    public function __construct(
        private readonly CartRepository $repository,
        private readonly Settings $settings,
        private readonly RecoveryMailer $mailer,
    ) {
    }

    public function registerHooks(): void
    {
        add_action(CRON_HOOK, [$this, 'run']);

        // Self-heal and upgrade path: ensure the schedule exists even if
        // activation was missed (e.g. plugin updated via file copy). Admin and
        // cron requests are enough for that and keep the storefront query-free.
        if (is_admin() || wp_doing_cron()) {
            self::schedule();
        }
    }

    /**
     * Schedule the worker: a 10-minute Action Scheduler recurring action when
     * Action Scheduler is available, otherwise the hourly WP-Cron event.
     * Moving to Action Scheduler also removes the old hourly WP-Cron event.
     */
    public static function schedule(): void
    {
        if (function_exists('as_schedule_recurring_action') && function_exists('as_has_scheduled_action')) {
            // Action Scheduler's store is not ready before its own init.
            if (! did_action('action_scheduler_init')) {
                if (! has_action('action_scheduler_init', [self::class, 'schedule'])) {
                    add_action('action_scheduler_init', [self::class, 'schedule']);
                }
                return;
            }

            if (wp_next_scheduled(CRON_HOOK)) {
                wp_clear_scheduled_hook(CRON_HOOK);
            }

            if (! as_has_scheduled_action(CRON_HOOK, [], self::GROUP)) {
                as_schedule_recurring_action(time() + 60, self::INTERVAL, CRON_HOOK, [], self::GROUP, true);
            }
            return;
        }

        if (! wp_next_scheduled(CRON_HOOK)) {
            wp_schedule_event(time() + 300, 'hourly', CRON_HOOK);
        }
    }

    /**
     * Remove both possible schedules (deactivation).
     */
    public static function unschedule(): void
    {
        wp_clear_scheduled_hook(CRON_HOOK);

        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions(CRON_HOOK, [], self::GROUP);
        }
    }

    public function run(): void
    {
        if (! $this->settings->enabled()) {
            return;
        }

        $this->sweepAbandoned();
        $this->sendDue();
    }

    private function sweepAbandoned(): void
    {
        $cutoff = $this->utcMinutesAgo($this->settings->abandonAfterMinutes());

        foreach ($this->repository->findPendingOlderThan($cutoff, 200) as $cart) {
            $this->repository->markAbandoned($cart->id);
        }
    }

    private function sendDue(): void
    {
        // The merchant's own setting is the default. An add-on may raise it
        // further through the filter, but nothing here caps what this plugin
        // can already do on its own.
        $maxEmails = max(1, (int) apply_filters('recover/max_emails', $this->settings->emailCount()));
        $due       = $this->repository->findDueForEmail(50, $maxEmails);

        foreach ($due as $cart) {
            $step = $cart->emailsSent;

            if (! $this->isStepDue($cart, $step)) {
                continue;
            }

            // Claim the step first: a run that overlaps this one sees the
            // incremented counter and skips the cart.
            if (! $this->repository->claimEmailStep($cart->id, $step)) {
                continue;
            }

            if (! $this->mailer->send($cart, $step)) {
                $this->repository->releaseEmailStep($cart->id, $step, $cart->lastEmailAt);
                continue;
            }

            /**
             * Fires after a recovery email is accepted by wp_mail.
             *
             * @param AbandonedCart $cart The cart that received the email.
             * @param int           $step Zero-based sequence index that was sent.
             */
            do_action('recover/email_sent', $cart, $step);
        }
    }

    private function isStepDue(AbandonedCart $cart, int $step): bool
    {
        $delay = (int) apply_filters(
            'recover/email_step_delay',
            $this->settings->emailDelayMinutes(),
            $cart,
            $step,
        );
        $delay = max(0, $delay);

        $anchor = ($step === 0 || $cart->lastEmailAt === null)
            ? $cart->abandonedAt
            : $cart->lastEmailAt;

        if ($anchor === null) {
            return false;
        }

        $dueAt = $anchor->modify('+' . $delay . ' minutes');

        return $dueAt <= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    private function utcMinutesAgo(int $minutes): string
    {
        return gmdate('Y-m-d H:i:s', time() - ($minutes * MINUTE_IN_SECONDS));
    }
}
