<?php
/**
 * Admin View: Page - Status Database Logs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
    <form method="get" id="mainform" action="">

		<?php $log_table_list->display(); ?>

        <input type="hidden" name="page" value="sm-status"/>
        <input type="hidden" name="tab" value="logs"/>

		<?php submit_button( __( 'Flush all logs', 'sermon-manager' ), 'delete', 'flush-logs' ); ?>
		<?php wp_nonce_field( 'sm-status-logs' ); ?>
    </form>
<?php
sm_enqueue_js( "
	jQuery( '#flush-logs' ).click( function() {
		if ( window.confirm('" . esc_js( __( 'Are you sure you want to clear all logs from the database?', 'sermon-manager' ) ) . "') ) {
			return true;
		}
		return false;
	});
" );
