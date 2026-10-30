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

/**
 * Remove the abandoned-cart table, the options and the scheduled worker from the current site.
 */
function recover_uninstall_site(): void
{
    global $wpdb;

    // Drop the abandoned-cart table. Table name is from $wpdb->prefix and cannot be
    // parameterised; it is a trusted, plugin-owned identifier.
    $recover_table = $wpdb->prefix . 'recover_carts';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name from $wpdb->prefix, cannot be parameterised.
    $wpdb->query( "DROP TABLE IF EXISTS {$recover_table}" );

    // Remove options.
    delete_option( 'recover_settings' );
    delete_option( 'recover_db_version' );

    // Clear the scheduled recovery worker: the WP-Cron fallback and the
    // Action Scheduler recurring action.
    wp_clear_scheduled_hook( 'recover_process_carts' );
    if ( function_exists( 'as_unschedule_all_actions' ) ) {
        as_unschedule_all_actions( 'recover_process_carts', array(), 'plogins-recover' );
    }
}

// Everything above is stored per site, so a network uninstall has to visit
// every site.
if (is_multisite()) {
    foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $recover_site_id) {
        switch_to_blog((int) $recover_site_id);
        recover_uninstall_site();
        restore_current_blog();
    }
    unset($recover_site_id);
} else {
    recover_uninstall_site();
}

// The PRO banner's dismissal is stored per user, so it belongs to the
// plugin rather than to the site content. User meta is global, not
// per-site, which is why this uses delete_metadata's \$delete_all rather
// than a loop over the users of one blog.
delete_metadata('user', 0, 'recover_pro_banner_dismissed', '', true);
