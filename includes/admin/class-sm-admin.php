<?php
/**
 * Sermon Manager Admin
 *
 * @class    SM_Admin
 * @category Admin
 * @package  SermonManager/Admin
 * @version  3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class SM_Admin
 */
class SM_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'current_screen', array( $this, 'conditional_includes' ) );
		add_action( 'admin_init', array( $this, 'buffer' ), 1 );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'admin_footer', 'sm_print_js', 25 );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Output buffering allows admin screens to make redirects later on.
	 *
	 * @since 3.0.0
	 */
	public function buffer() {
		ob_start();
	}

	/**
	 * Include any classes we need within admin.
	 *
	 * @since 3.0.0
	 */
	public function includes() {
		include_once 'sm-admin-functions.php';
		include_once 'sm-meta-box-functions.php';
		include_once 'class-sm-admin-post-types.php';
		#include_once 'class-sm-admin-taxonomies.php';
		include_once 'class-sm-admin-menus.php';
		#include_once 'class-sm-admin-customize.php';
		include_once 'class-sm-admin-notices.php';
		include_once 'class-sm-admin-assets.php';
		include_once 'class-sm-admin-api-keys.php';

		// Help Tabs
		if ( apply_filters( 'sm_enable_admin_help_tab', true ) ) {
			#include_once 'class-sm-admin-help.php';
		}

		// Setup/welcome
		if ( ! empty( $_GET['page'] ) ) {
			switch ( $_GET['page'] ) {
				case 'sm-setup' :
					include_once 'class-sm-admin-setup-wizard.php';
					break;
			}
		}
	}

	/**
	 * Include admin files conditionally.
	 *
	 * @since 3.0.0
	 */
	public function conditional_includes() {
		if ( ! $screen = get_current_screen() ) {
			return;
		}

		switch ( $screen->id ) {
			case 'dashboard' :
				#include 'class-sm-admin-dashboard.php'; TODO
				break;
			case 'options-permalink' :
				#include 'class-sm-admin-permalink-settings.php'; TODO
				break;
			case 'users' :
			case 'user' :
			case 'profile' :
			case 'user-edit' :
		}
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the
	 * network/bulk plugin updaters.
	 *
	 * @since 3.0.0
	 */
	public function admin_redirects() {
		// Setup wizard redirect
		if ( get_transient( '_sm_activation_redirect' ) ) {
			delete_transient( '_sm_activation_redirect' );

			if ( ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], array( 'sm-setup' ) ) ) ||
			     is_network_admin() ||
			     isset( $_GET['activate-multi'] ) ||
			     ! current_user_can( 'manage_sermons' ) ||
			     apply_filters( 'sm_prevent_automatic_wizard_redirect', false ) ) {
				return;
			}

			// If the user needs to install, send them to the setup wizard
			if ( SM_Admin_Notices::has_notice( 'install' ) ) {
				wp_safe_redirect( admin_url( 'index.php?page=sm-setup' ) );
				exit;
			}
		}
	}

	/**
	 * Change the admin footer text on Sermon Manager admin pages.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $footer_text
	 *
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		if ( ! current_user_can( 'manage_sermons' ) || ! function_exists( 'sm_get_screen_ids' ) ) {
			return $footer_text;
		}
		$current_screen = get_current_screen();
		$sm_pages       = sm_get_screen_ids();

		// Set only SM pages.
		$sm_pages = array_diff( $sm_pages, array( 'profile', 'user-edit' ) );

		// Check to make sure we're on a Sermon Manager admin page.
		if ( isset( $current_screen->id ) && apply_filters( 'sm_display_admin_footer_text', in_array( $current_screen->id, $sm_pages ) ) ) {
			// Change the footer text
			if ( ! get_option( 'sm_admin_footer_text_rated' ) ) {
				/* translators: %s: five stars */
				$footer_text = sprintf( __( 'If you like <strong>Sermon Manager</strong> please leave us a %s rating. A huge thanks in advance!', 'sermon-manager' ), '<a href="https://wordpress.org/support/plugin/sermon-manager-for-wordpress/reviews?rate=5#new-post" target="_blank" class="sm-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'sermon-manager' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>' );
				sm_enqueue_js( "
					jQuery( 'a.sm-rating-link' ).click( function() {
						jQuery.post( '" . SM()->ajax_url() . "', { action: 'sm_rated' } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});
				" );
			} else {
				$footer_text = __( 'Thank you for managing sermons with Sermon Manager.', 'sermon-manager' );
			}
		}

		return $footer_text;
	}
}

return new SM_Admin();