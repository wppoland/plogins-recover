<?php
/**
 * Constants bootstrap for PHPStan. Mirrors the runtime constants defined in
 * recover.php so static analysis can resolve them without loading WordPress.
 *
 * @package Recover
 */

declare(strict_types=1);

namespace Recover;

const VERSION         = '0.1.0';
const PLUGIN_FILE     = __FILE__;
const PLUGIN_DIR      = __DIR__;
const MIN_PHP_VERSION = '8.1.0';
const MIN_WC_VERSION  = '8.0.0';
const CRON_HOOK       = 'recover_process_carts';
