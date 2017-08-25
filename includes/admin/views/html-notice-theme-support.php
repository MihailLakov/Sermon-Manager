<?php
/**
 * Admin View: Notice - Theme Support
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated sm-message sm-connect">
    <a class="sm-message-close notice-dismiss"
       href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'sm-hide-notice', 'theme_support' ), 'sm_hide_notices_nonce', '_sm_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'sermon-manager' ); ?></a>

    <p><?php printf( __( '<strong>Your theme does not declare Sermon Manager support</strong> &#8211; Please read our <a href="%1$s" target="_blank">integration</a> guide or check out our <a href="%2$s" target="_blank">Multiply</a> theme which is designed specifically for use by Churches and Sermon Manager.', 'sermon-manager' ), esc_url( apply_filters( 'sm_docs_url', 'https://wpforchurch.com/my/knowledgebase/12/Sermon-Manager', 'theme-compatibility' ) ), esc_url( admin_url( 'theme-install.php?theme=' ) ) ); ?></p>
    <p class="submit">
        <a href="https://wpforchurch.com/wordpress-themes/multiply-theme/?utm_source=notice&amp;utm_medium=product&amp;utm_content=storefront&amp;utm_campaign=sermonmanagerplugin"
           class="button-primary" target="_blank"><?php _e( 'Read more about Multiply', 'sermon-manager' ); ?></a>
        <a href="<?php echo esc_url( apply_filters( 'sm_docs_url', 'https://wpforchurch.com/my/knowledgebase/12/Sermon-Manager/?utm_source=notice&utm_medium=product&utm_content=themecompatibility&utm_campaign=sermonmanagerplugin' ) ); ?>"
           class="button-secondary" target="_blank"><?php _e( 'Theme integration guide', 'sermon-manager' ); ?></a>
    </p>
</div>
