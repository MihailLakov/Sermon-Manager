<?php
/**
 * Admin View: Page - Status
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : 'status';
$tabs        = array(
	'status' => __( 'System status', 'sermon-manager' ),
	'tools'  => __( 'Tools', 'sermon-manager' ),
	'logs'   => __( 'Logs', 'sermon-manager' ),
);
$tabs        = apply_filters( 'sm_admin_status_tabs', $tabs );
?>
<div class="wrap sm">
    <nav class="nav-tab-wrapper sm-nav-tab-wrapper">
		<?php
		foreach ( $tabs as $name => $label ) {
			echo '<a href="' . admin_url( 'admin.php?page=sm-status&tab=' . $name ) . '" class="nav-tab ';
			if ( $current_tab == $name ) {
				echo 'nav-tab-active';
			}
			echo '">' . $label . '</a>';
		}
		?>
    </nav>
    <h1 class="screen-reader-text"><?php echo esc_html( $tabs[ $current_tab ] ); ?></h1>
	<?php
	switch ( $current_tab ) {
		case "tools" :
			SM_Admin_Status::status_tools();
			break;
		case "logs" :
			SM_Admin_Status::status_logs();
			break;
		default :
			if ( array_key_exists( $current_tab, $tabs ) && has_action( 'sm_admin_status_content_' . $current_tab ) ) {
				do_action( 'sm_admin_status_content_' . $current_tab );
			} else {
				SM_Admin_Status::status_report();
			}
			break;
	}
	?>
</div>
