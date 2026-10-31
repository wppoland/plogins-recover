<?php

/**
 * The settings screen is shown to manage_woocommerce, so saving it must not
 * need manage_options. options.php checks
 * apply_filters("option_page_capability_{$group}", 'manage_options') for the
 * group posted by settings_fields(); without that filter a shop manager sees
 * the screen and every save dies with "Sorry, you are not allowed to manage
 * options for this site."
 *
 * Run: php tests/settings-capability-check.php
 */

declare(strict_types=1);

namespace Recover\Contract {
    if (! interface_exists(HasHooks::class)) {
        interface HasHooks
        {
            public function registerHooks(): void;
        }
    }
}

namespace {
    define('ABSPATH', sys_get_temp_dir() . '/');

    $GLOBALS['hooks'] = [];
    function add_action(string $hook, callable|array $cb): void {}
    function add_filter(string $hook, callable $cb): void
    {
        $GLOBALS['hooks'][$hook][] = $cb;
    }
    function apply_filters(string $hook, mixed $value): mixed
    {
        foreach ($GLOBALS['hooks'][$hook] ?? [] as $cb) {
            $value = $cb($value);
        }
        return $value;
    }

    $root  = dirname(__DIR__);
    $class = 'Recover\\Admin\\SettingsPage';
    $file  = $root . '/src/Admin/SettingsPage.php';
    require $root . '/src/Admin/ProUpsell.php';
    require $file;

    $fail = static function (string $msg): void {
        fwrite(STDERR, 'FAIL: ' . $msg . "\n");
        exit(1);
    };

    $code = (string) file_get_contents($file);
    if (! preg_match("/add_submenu_page\([^;]*?'manage_woocommerce'/s", $code)) {
        $fail('the page is no longer gated on manage_woocommerce; revisit this check');
    }
    if (! preg_match('/settings_fields\(self::(\w+)\)/', $code, $m)) {
        $fail('no settings_fields(self::CONST) call found');
    }
    $ref   = new ReflectionClass($class);
    $group = (string) $ref->getConstant($m[1]);

    $ref->newInstanceWithoutConstructor()->registerHooks();

    $cap = apply_filters('option_page_capability_' . $group, 'manage_options');
    if ('manage_woocommerce' !== $cap) {
        $fail("options.php would check '{$cap}' for group '{$group}', shop managers cannot save");
    }

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI script, not a page.
    echo "OK: group '{$group}' saves with manage_woocommerce\n";
}
