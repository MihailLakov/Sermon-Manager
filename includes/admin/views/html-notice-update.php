<?php
/**
 * Admin View: Notice - Update
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated sm-message sm-connect">
    <p><strong><?php _e( 'Sermon Manager data update', 'sermon-manager' ); ?></strong>
        &#8211; <?php _e( 'We need to update your sermon database to the latest version.', 'sermon-manager' ); ?></p>
    <p class="submit"><a
                href="<?php echo esc_url( add_query_arg( 'do_update_sm', 'true', admin_url( 'admin.php?page=sm-settings' ) ) ); ?>"
                class="sm-update-now button-primary"><?php _e( 'Run the updater', 'sermon-manager' ); ?></a></p>
</div>
<script type="text/javascript">
    jQuery('.sm-update-now').click('click', function () {
        return window.confirm('<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'sermon-manager' ) ); ?>');
    });
</script>
