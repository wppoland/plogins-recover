<?php
/**
 * Runnable without WordPress: php -d zend.assertions=1 tests/retention-check.php
 *
 * The worker deletes carts past the retention period on every run, also with
 * recovery switched off, and deletes nothing when retention is 0. An install
 * that ran Recover before retention existed keeps its carts until the merchant
 * sets a period; a fresh install gets the default.
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
    define('Recover\\CRON_HOOK', 'recover_process_carts');
    define('MINUTE_IN_SECONDS', 60);

    $GLOBALS['opts'] = [];
    function get_option($k, $d = false) { return $GLOBALS['opts'][$k] ?? $d; }
    function apply_filters($h, $v) { return $v; }
    function update_option($k, $v) { $GLOBALS['opts'][$k] = $v; return true; }

    class wpdb
    {
        public string $prefix = 'wp_';
        public array $queries = [];
        public function prepare($sql, ...$args) { return vsprintf(str_replace(['%i', '%s'], ['`%1$s`', "'%2\$s'"], $sql), $args); }
        public function query($sql) { $this->queries[] = $sql; return 3; }
    }

    require dirname(__DIR__) . '/src/Contract/HasHooks.php';
    require dirname(__DIR__) . '/src/Settings.php';
    require dirname(__DIR__) . '/src/Model/AbandonedCart.php';
    require dirname(__DIR__) . '/src/Repository/CartRepository.php';
    require dirname(__DIR__) . '/src/Service/RecoveryMailer.php';
    require dirname(__DIR__) . '/src/Service/CronWorker.php';
    require dirname(__DIR__) . '/src/Migration/Migration_1_4_1.php';

    $db      = new wpdb();
    $repo    = new Recover\Repository\CartRepository($db);
    $mailer  = (new ReflectionClass(Recover\Service\RecoveryMailer::class))->newInstanceWithoutConstructor();
    $worker  = new Recover\Service\CronWorker($repo, new Recover\Settings(), $mailer);

    $GLOBALS['opts']['recover_settings'] = ['enabled' => false, 'retention_days' => 30];
    $worker->run();
    assert(count($db->queries) === 1 && str_starts_with($db->queries[0], 'DELETE FROM `wp_recover_carts` WHERE updated_at <'), 'retention deletes old carts even with recovery off');
    $cutoff = substr($db->queries[0], -20, 19);
    assert(abs(strtotime($cutoff . ' UTC') - (time() - 30 * 86400)) < 5, 'cutoff is 30 days ago');

    $db->queries = [];
    $GLOBALS['opts']['recover_settings'] = ['enabled' => false, 'retention_days' => 0];
    $worker->run();
    assert($db->queries === [], 'retention 0 keeps every cart');

    // Upgrade: a schema version exists, the stored settings predate retention_days.
    $GLOBALS['opts'] = ['recover_db_version' => '0.1.0', 'recover_settings' => ['enabled' => true]];
    Recover\Migration\Migration_1_4_1::migrate();
    assert($GLOBALS['opts']['recover_settings'] === ['enabled' => true, 'retention_days' => 0], 'upgrade stores retention 0');
    $db->queries = [];
    $GLOBALS['opts']['recover_settings']['enabled'] = false;
    $worker->run();
    assert($db->queries === [], 'first worker run after an upgrade deletes nothing');

    // Upgrade without any stored settings (never saved) is also kept.
    $GLOBALS['opts'] = ['recover_db_version' => '0.1.0'];
    Recover\Migration\Migration_1_4_1::migrate();
    assert(($GLOBALS['opts']['recover_settings']['retention_days'] ?? null) === 0, 'never-saved upgrade stores retention 0');

    // A period the merchant chose is left alone.
    $GLOBALS['opts'] = ['recover_db_version' => '0.1.0', 'recover_settings' => ['retention_days' => 30]];
    Recover\Migration\Migration_1_4_1::migrate();
    assert($GLOBALS['opts']['recover_settings']['retention_days'] === 30, 'chosen period kept');

    // Fresh install: nothing stored, the default 90 applies.
    $GLOBALS['opts'] = [];
    Recover\Migration\Migration_1_4_1::migrate();
    assert(! isset($GLOBALS['opts']['recover_settings']) && (new Recover\Settings())->retentionDays() === 90, 'fresh install keeps default 90');

    echo "OK: retention purge\n";
}
