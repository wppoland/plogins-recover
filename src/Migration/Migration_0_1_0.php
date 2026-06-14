<?php

declare(strict_types=1);

namespace Recover\Migration;

defined('ABSPATH') || exit;

/**
 * Creates the recover abandoned-cart table.
 */
final class Migration_0_1_0
{
    public static function migrate(): void
    {
        global $wpdb;

        // Table name is built from $wpdb->prefix so it lives in this site's table
        // namespace; it cannot be parameterised in CREATE TABLE.
        $table          = $wpdb->prefix . 'recover_carts';
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT,
            token CHAR(64) NOT NULL,
            session_key VARCHAR(191) DEFAULT NULL,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            email VARCHAR(191) DEFAULT NULL,
            cart_contents LONGTEXT DEFAULT NULL,
            currency VARCHAR(10) DEFAULT NULL,
            cart_total DECIMAL(19,4) NOT NULL DEFAULT 0,
            item_count INT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            consent TINYINT(1) NOT NULL DEFAULT 0,
            emails_sent SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            abandoned_at DATETIME DEFAULT NULL,
            recovered_at DATETIME DEFAULT NULL,
            last_email_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_token (token),
            KEY idx_session (session_key),
            KEY idx_status (status),
            KEY idx_status_updated (status, updated_at)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }
}
