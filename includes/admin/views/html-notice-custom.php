<?php
/**
 * Admin View: Custom Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated sm-message">
    <a class="sm-message-close notice-dismiss"
       href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'sm-hide-notice', $notice ), 'sm_hide_notices_nonce', '_sm_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'sermon-manager' ); ?></a>
	<?php echo wp_kses_post( wpautop( $notice_html ) ); ?>
</div>
