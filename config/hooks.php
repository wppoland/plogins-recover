<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Recover\Contract\HasHooks.
 *
 * @package Recover
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Recover\Admin\CartsPage;
use Recover\Admin\SettingsPage;
use Recover\Service\CartTracker;
use Recover\Service\CronWorker;
use Recover\Service\RestoreHandler;

defined('ABSPATH') || exit;

return is_admin()
    ? [
        CartTracker::class,
        RestoreHandler::class,
        CronWorker::class,
        SettingsPage::class,
        CartsPage::class,
    ]
    : [
        CartTracker::class,
        RestoreHandler::class,
        CronWorker::class,
    ];
