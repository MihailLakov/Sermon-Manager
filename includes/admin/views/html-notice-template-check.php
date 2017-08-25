<?php
/**
 * Admin View: Notice - Template Check
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$theme = wp_get_theme();
?>
<div id="message" class="updated sm-message">
    <a class="sm-message-close notice-dismiss"
       href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'sm-hide-notice', 'template_files' ), 'sm_hide_notices_nonce', '_sm_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'sermon-manager' ); ?></a>

    <p><?php printf( __( '<strong>Your theme (%1$s) contains outdated copies of some Sermon Manager template files.</strong> These files may need updating to ensure they are compatible with the current version of Sermon Manager. You can see which files are affected from the <a href="%2$s">system status page</a>. If in doubt, check with the author of the theme.', 'sermon-manager' ), esc_html( $theme['Name'] ), esc_url( admin_url( 'admin.php?page=sm-status' ) ) ); ?></p>
    <p class="submit"><a class="button-primary" href="#"
                         target="_blank"><?php _e( 'Learn more about templates', 'sermon-manager' ); ?></a></p>
</div>
