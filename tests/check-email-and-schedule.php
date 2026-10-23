<?php
/**
 * Runnable checks without WordPress:
 * php -d zend.assertions=1 tests/check-email-and-schedule.php
 *
 * 1. Scheduling: WP-Cron hourly fallback without Action Scheduler; with it, a
 *    10-minute recurring action in group plogins-recover, the old hourly event
 *    removed, and no duplicate on a second call; deferral before AS init.
 * 2. Email: cart lines render (thumbnail, name, quantity, line price, total in
 *    the stored currency) and a product that no longer exists is skipped.
 *
 * @package Recover
 */

declare(strict_types=1);

namespace {
    if (ini_get('zend.assertions') !== '1') {
        fwrite(STDERR, "Run with: php -d zend.assertions=1 -d assert.exception=1 " . __FILE__ . "\n");
        exit(1);
    }
    ini_set('assert.exception', '1');

    define('ABSPATH', __DIR__ . '/');
    define('Recover\\PLUGIN_DIR', dirname(__DIR__));
    define('Recover\\CRON_HOOK', 'recover_process_carts');

    $GLOBALS['t'] = ['cron' => [], 'as' => [], 'actions' => [], 'did' => [], 'mail' => null];

    // WordPress stubs.
    function did_action($h) { return $GLOBALS['t']['did'][$h] ?? 0; }
    function add_action($h, $cb) { $GLOBALS['t']['actions'][$h][] = $cb; }
    function has_action($h, $cb) { return in_array($cb, $GLOBALS['t']['actions'][$h] ?? [], true); }
    function wp_next_scheduled($h) { return $GLOBALS['t']['cron'][$h]['time'] ?? false; }
    function wp_schedule_event($ts, $rec, $h) { $GLOBALS['t']['cron'][$h] = ['time' => $ts, 'recurrence' => $rec]; }
    function wp_clear_scheduled_hook($h) { unset($GLOBALS['t']['cron'][$h]); }
    function apply_filters($h, $v) { return $v; }
    function is_email($e) { return str_contains((string) $e, '@'); }
    function get_bloginfo($k = '') { return $k === 'name' ? 'Shop' : 'en-US'; }
    function get_option($k, $d = false) { return $d; }
    function __($s) { return $s; }
    function esc_html__($s) { return htmlspecialchars($s); }
    function esc_html($s) { return htmlspecialchars((string) $s); }
    function esc_attr($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
    function esc_url($s) { return (string) $s; }
    function wp_kses_post($s) { return (string) $s; }
    function add_query_arg($k, $v, $u) { return $u . '?' . $k . '=' . $v; }
    function wc_get_cart_url() { return 'https://shop.test/cart/'; }
    function wp_mail($to, $subject, $message, $headers) { $GLOBALS['t']['mail'] = $message; return true; }

    // WooCommerce stubs.
    class WC_Product
    {
        public function __construct(private string $name, private float $price, private int $image, private string $status = 'publish') {}
        public function get_name() { return $this->name; }
        public function get_status() { return $this->status; }
        public function get_image_id() { return $this->image; }
        public function get_price() { return $this->price; }
    }
    function wc_get_product($id)
    {
        return [
            10 => new WC_Product('Blue Mug', 12.5, 7),
            11 => new WC_Product('Trashed Cap', 9.0, 0, 'trash'),
            21 => new WC_Product('Tee - Large', 20.0, 0),
        ][$id] ?? false;
    }
    function wc_get_price_to_display($p, $args) { return $p->get_price() * $args['qty']; }
    function wp_get_attachment_image_url($id) { return 'https://shop.test/img/' . $id . '-150x150.jpg'; }
    function wc_placeholder_img_src() { return 'https://shop.test/placeholder.png'; }
    function wc_price($amount, $args = []) { return ($args['currency'] ?? 'USD') . ' ' . number_format((float) $amount, 2); }

    $root = dirname(__DIR__);
    require $root . '/src/Contract/HasHooks.php';
    require $root . '/src/Settings.php';
    require $root . '/src/Model/AbandonedCart.php';
    require $root . '/src/Repository/CartRepository.php';
    require $root . '/src/Service/RestoreHandler.php';
    require $root . '/src/Service/RecoveryMailer.php';
    require $root . '/src/Service/CronWorker.php';
}

namespace Recover\Tests {
    use Recover\Model\AbandonedCart;
    use Recover\Service\CronWorker;
    use Recover\Service\RecoveryMailer;
    use Recover\Settings;

    $t = &$GLOBALS['t'];

    // 1a. No Action Scheduler: hourly WP-Cron fallback.
    CronWorker::schedule();
    assert(($t['cron']['recover_process_carts']['recurrence'] ?? null) === 'hourly', 'fallback schedules hourly WP-Cron');

    // Action Scheduler appears (WooCommerce loaded it).
    function_exists('as_schedule_recurring_action') || eval('
        function as_has_scheduled_action($h, $a = [], $g = "") { return isset($GLOBALS["t"]["as"][$g . "|" . $h]); }
        function as_schedule_recurring_action($ts, $int, $h, $a = [], $g = "", $u = false) { $GLOBALS["t"]["as"][$g . "|" . $h] = $int; return 1; }
        function as_unschedule_all_actions($h, $a = [], $g = "") { unset($GLOBALS["t"]["as"][$g . "|" . $h]); }
    ');

    // 1b. Before action_scheduler_init: defers instead of touching the store.
    CronWorker::schedule();
    CronWorker::schedule();
    assert($t['as'] === [], 'nothing scheduled before AS init');
    assert(count($t['actions']['action_scheduler_init']) === 1, 'deferred exactly once');

    // 1c. After init: 10-minute recurring action, old hourly event migrated away.
    $t['did']['action_scheduler_init'] = 1;
    CronWorker::schedule();
    CronWorker::schedule();
    assert(($t['as']['plogins-recover|recover_process_carts'] ?? null) === 600, 'AS recurring every 600s in group plogins-recover');
    assert(count($t['as']) === 1, 'no duplicate action');
    assert(! isset($t['cron']['recover_process_carts']), 'old hourly WP-Cron event removed');

    // 1d. Deactivation clears both.
    $t['cron']['recover_process_carts'] = ['time' => 1, 'recurrence' => 'hourly'];
    CronWorker::unschedule();
    assert($t['as'] === [] && $t['cron'] === [], 'unschedule clears AS and WP-Cron');

    // 2. Email items: product 99 is gone, 11 is trashed, 21 is a variation.
    $now  = new \DateTimeImmutable('2026-10-23 10:00:00');
    $cart = new AbandonedCart(
        id: 1, token: 'abc', sessionKey: null, userId: 1, email: 'a@b.test',
        cartContents: [
            ['product_id' => 10, 'variation_id' => 0, 'quantity' => 2],
            ['product_id' => 99, 'variation_id' => 0, 'quantity' => 1],
            ['product_id' => 11, 'variation_id' => 0, 'quantity' => 1],
            ['product_id' => 20, 'variation_id' => 21, 'quantity' => 1],
        ],
        currency: 'EUR', cartTotal: 45.0, itemCount: 5, status: AbandonedCart::STATUS_ABANDONED,
        consent: true, emailsSent: 0, createdAt: $now, updatedAt: $now, abandonedAt: $now,
        recoveredAt: null, lastEmailAt: null,
    );

    $mailer = new RecoveryMailer(new Settings());
    $items  = $mailer->items($cart);
    assert(array_column($items, 'name') === ['Blue Mug', 'Tee - Large'], 'missing and trashed products skipped');
    assert($items[0]['line_total'] === 'EUR 25.00', 'line price = qty x price in stored currency');
    assert($items[1]['image'] === 'https://shop.test/placeholder.png', 'placeholder when no image');

    assert($mailer->send($cart) === true, 'mail handed to wp_mail');
    $html = (string) $t['mail'];
    assert(str_contains($html, '<img src="https://shop.test/img/7-150x150.jpg" width="64" height="64" alt="Blue Mug"'), 'thumbnail with absolute src, size, alt');
    assert(str_contains($html, 'Quantity: 2'), 'quantity rendered');
    assert(! str_contains($html, 'Cart total'), 'no stored total when a captured line was dropped');
    assert(! str_contains($html, 'Trashed Cap'), 'trashed product not rendered');

    // Every captured line still listed: the stored total is shown.
    $full = new AbandonedCart(1, 'abc', null, 1, 'a@b.test', [['product_id' => 10, 'quantity' => 2]], 'EUR', 45.0, 2, 'abandoned', true, 0, $now, $now, $now, null, null);
    $mailer->send($full);
    assert(str_contains((string) $t['mail'], 'EUR 45.00'), 'cart total in stored currency');

    // Empty cart after deletions: no item table, no total.
    $gone = new AbandonedCart(1, 'abc', null, 1, 'a@b.test', [['product_id' => 99]], 'EUR', 9.0, 1, 'abandoned', true, 0, $now, $now, $now, null, null);
    $mailer->send($gone);
    assert(! str_contains((string) $t['mail'], 'Cart total'), 'no table when every product is gone');

    echo "OK: schedule and email item checks passed\n";
}
