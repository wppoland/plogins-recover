<?php

/**
 * Deleting the plugin on a network must clean every site, not only the one
 * the uninstall runs in: options, tables and scheduled events live per site,
 * and each site's tables carry that site's prefix. User meta is global and is
 * deleted once.
 *
 * Run: php tests/uninstall-multisite-check.php
 */

declare(strict_types=1);

define('ABSPATH', sys_get_temp_dir() . '/');
define('WP_UNINSTALL_PLUGIN', 'recover/plogins-recover.php');

$blog    = 1;
$deleted = [];
$px      = static fn (int $id): string => 1 === $id ? 'wp_' : "wp_{$id}_";

final class FakeWpdb
{
    public string $prefix  = 'wp_';
    public string $options = 'wp_options';
    public string $postmeta = 'wp_postmeta';

    public function query(string $sql): int
    {
        $GLOBALS['deleted'][] = $GLOBALS['blog'] . ':query:' . $sql;

        return 0;
    }

    public function get_var(string $sql): ?string
    {
        return null;
    }

    public function prepare(string $sql, ...$args): string
    {
        foreach ($args as $arg) {
            $sql = (string) preg_replace('/%[is]/', str_replace('\\', '\\\\', (string) $arg), $sql, 1);
        }

        return $sql;
    }

    public function esc_like(string $text): string
    {
        return $text;
    }
}

$wpdb = new FakeWpdb();

function is_multisite(): bool { return true; }
function get_sites(array $args): array { return [1, 2, 3]; }
function switch_to_blog(int $id): void
{
    $GLOBALS['blog'] = $id;
    $GLOBALS['wpdb']->prefix   = $GLOBALS['px']($id);
    $GLOBALS['wpdb']->options  = $GLOBALS['wpdb']->prefix . 'options';
    $GLOBALS['wpdb']->postmeta = $GLOBALS['wpdb']->prefix . 'postmeta';
}
function restore_current_blog(): void { switch_to_blog(1); }
function delete_option(string $name): void { $GLOBALS['deleted'][] = $GLOBALS['blog'] . ':' . $name; }
function delete_metadata(string $type, int $id, string $key, string $value = '', bool $all = false): void { $GLOBALS['deleted'][] = 'user:' . $key; }
function wp_clear_scheduled_hook(string $hook): int { $GLOBALS['deleted'][] = $GLOBALS['blog'] . ':cron:' . $hook; return 0; }
function as_unschedule_all_actions(string $hook, array $args = [], string $group = ''): void { $GLOBALS['deleted'][] = $GLOBALS['blog'] . ':as:' . $hook; }

require __DIR__ . '/../uninstall.php';

$expected = ['user:recover_pro_banner_dismissed'];
foreach ([1, 2, 3] as $site) {
    array_push($expected, "{$site}:query:DROP TABLE IF EXISTS {$px($site)}recover_carts", "{$site}:recover_settings", "{$site}:recover_db_version", "{$site}:cron:recover_process_carts", "{$site}:as:recover_process_carts");
}
sort($expected);
sort($deleted);

if ($deleted !== $expected) {
    echo "FAIL: uninstall did not clean every site the same way.\n  missing: " . implode(' | ', array_diff($expected, $deleted))
        . "\n  extra:   " . implode(' | ', array_diff($deleted, $expected)) . "\n";
    exit(1);
}

echo "OK: uninstall cleans every site of the network\n";
