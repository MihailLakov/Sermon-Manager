<?php
/**
 * Admin View: Notice - Install
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated sm-message sm-connect">
    <p><?php _e( '<strong>Welcome to Sermon Manager</strong> &#8211; You&lsquo;re almost ready to start preaching :)', 'sermon-manager' ); ?></p>
    <p class="submit"><a href="<?php echo esc_url( admin_url( 'admin.php?page=sm-setup' ) ); ?>"
                         class="button-primary"><?php _e( 'Run the Setup Wizard', 'sermon-manager' ); ?></a> <a
                class="button-secondary skip"
                href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'sm-hide-notice', 'install' ), 'sm_hide_notices_nonce', '_sm_notice_nonce' ) ); ?>"><?php _e( 'Skip setup', 'sermon-manager' ); ?></a>
    </p>
</div>
