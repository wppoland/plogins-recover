<?php

declare(strict_types=1);

namespace Recover;

defined('ABSPATH') || exit;

/**
 * Idempotent schema/version migrations, run on every boot. Compares a stored
 * option against VERSION and applies forward steps as needed.
 */
final class Migrator
{
    private const OPTION = 'recover_db_version';

    /** Ordered list of migration class short-names under Recover\Migration. */
    private const MIGRATIONS = [
        'Migration_0_1_0',
    ];

    public function maybeMigrate(): void
    {
        $applied = (string) get_option(self::OPTION, '0');

        foreach (self::MIGRATIONS as $name) {
            if (version_compare($this->versionOf($name), $applied, '>')) {
                /** @var class-string $class */
                $class = 'Recover\\Migration\\' . $name;
                if (class_exists($class) && method_exists($class, 'migrate')) {
                    $class::migrate();
                }
            }
        }

        $latest = self::MIGRATIONS[count(self::MIGRATIONS) - 1];
        update_option(self::OPTION, $this->versionOf($latest), false);
    }

    private function versionOf(string $migrationName): string
    {
        // "Migration_0_1_0" -> "0.1.0".
        return str_replace('_', '.', substr($migrationName, strlen('Migration_')));
    }
}
