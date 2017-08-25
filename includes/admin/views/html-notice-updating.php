<?php
/**
 * Admin View: Notice - Updating
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated sm-message sm-connect">
    <p><strong><?php _e( 'Sermon Manager data update', 'sermon-manager' ); ?></strong>
        &#8211; <?php _e( 'Your database is being updated in the background.', 'sermon-manager' ); ?> <a
                href="<?php echo esc_url( add_query_arg( 'force_update_sm', 'true', admin_url( 'admin.php?page=sm-settings' ) ) ); ?>"><?php _e( 'Taking a while? Click here to run it now.', 'sermon-manager' ); ?></a>
    </p>
</div>
