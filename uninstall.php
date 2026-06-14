<?php

/**
 * Recover uninstall routine.
 *
 * Drops the plugin table, removes options, and clears the scheduled cron event
 * when the user deletes the plugin from the WordPress admin.
 *
 * @package Recover
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

// Drop the abandoned-cart table. Table name is from $wpdb->prefix and cannot be
// parameterised; it is a trusted, plugin-owned identifier.
$recover_table = $wpdb->prefix . 'recover_carts';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name from $wpdb->prefix, cannot be parameterised.
$wpdb->query( "DROP TABLE IF EXISTS {$recover_table}" );

// Remove options.
delete_option( 'recover_settings' );
delete_option( 'recover_db_version' );

// Clear the scheduled recovery worker.
wp_clear_scheduled_hook( 'recover_process_carts' );
