<?php

declare(strict_types=1);

namespace Recover\Migration;

defined('ABSPATH') || exit;

use Recover\Settings;

/**
 * Cart retention is opt-in for a shop that ran Recover before it existed.
 *
 * 1.3.0 added "Delete carts after (days)" with a default of 90 and no migration,
 * so an updated shop deleted every cart older than 90 days on the next worker
 * run without anybody choosing that. An install that already has a schema
 * version and no stored retention value now stores 0 (keep carts); a fresh
 * install keeps the documented default of 90.
 */
final class Migration_1_4_1
{
    public static function migrate(): void
    {
        // Read before the Migrator writes the new version: '0' means a fresh install.
        if ((string) get_option('recover_db_version', '0') === '0') {
            return;
        }

        $stored = get_option(Settings::OPTION, []);
        $stored = is_array($stored) ? $stored : [];

        if (array_key_exists('retention_days', $stored)) {
            return;
        }

        $stored['retention_days'] = 0;
        update_option(Settings::OPTION, $stored);
    }
}
