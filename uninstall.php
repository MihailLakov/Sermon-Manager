<?php
/**
 * Sermon Manager Uninstall
 *
 * Uninstalling Sermon Manager deletes user roles, pages, tables, and options.
 *
 * @since 3.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb, $wp_version;

// clear_scheduled_hook() - if necessary

/*
 * Only remove ALL sermons and settings data if SM_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'SM_REMOVE_ALL_DATA' ) && true === SM_REMOVE_ALL_DATA ) {
	// do the delete

	// Clear any cached data that has been removed
	wp_cache_flush();
}
