<?php
/**
 * Deprecated functions
 *
 * Function graveyard
 *
 * @category    Core
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wrapper for _doing_it_wrong
 *
 * @since 3.0.0
 *
 * @param  string $function The function that is incorrectly called
 * @param  string $message  Message to show
 * @param  string $version  Version when this message was added
 */
function sm_doing_it_wrong( $function, $message, $version ) {
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

	if ( is_ajax() ) {
		do_action( 'doing_it_wrong_run', $function, $message, $version );
		error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
	} else {
		_doing_it_wrong( $function, $message, $version );
	}
}