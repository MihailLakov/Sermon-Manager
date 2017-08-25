<?php
/**
 * Admin View: Notice - Updated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated sm-message sm-connect sm-message--success">
    <a class="sm-message-close notice-dismiss"
       href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'sm-hide-notice', 'update', remove_query_arg( 'do_update_sm' ) ), 'sm_hide_notices_nonce', '_sm_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'sermon-manager' ); ?></a>

    <p><?php _e( 'Sermon Manager data update complete. Thank you for updating to the latest version!', 'sermon-manager' ); ?></p>
</div>
