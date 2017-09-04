<?php
/**
 * Setup menus in WP admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SM_Admin_Menus', false ) ) :

	/**
	 * SM_Admin_Menus Class.
	 */
	class SM_Admin_Menus {

		/**
		 * Hook in tabs.
		 */
		public function __construct() {
			// Add menus
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
			add_action( 'admin_menu', array( $this, 'stats_menu' ), 20 );
			add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );
			add_action( 'admin_menu', array( $this, 'status_menu' ), 60 );

			if ( apply_filters( 'sm_show_addons_page', true ) ) {
				add_action( 'admin_menu', array( $this, 'addons_menu' ), 70 );
			}

			add_action( 'admin_head', array( $this, 'menu_highlight' ) );
			add_filter( 'menu_order', array( $this, 'menu_order' ) );
			add_filter( 'custom_menu_order', array( $this, 'custom_menu_order' ) );

			// Add endpoints custom URLs in Appearance > Menus > Pages.
			add_action( 'admin_head-nav-menus.php', array( $this, 'add_nav_menu_meta_boxes' ) );

			// Admin bar menus
			if ( apply_filters( 'sm_show_admin_bar', true ) ) {
				add_action( 'admin_bar_menu', array( $this, 'admin_bar_menus' ), 31 );
			}
		}

		/**
		 * Add menu items.
		 */
		public function admin_menu() {
			global $menu;

			if ( current_user_can( 'manage_sermons' ) ) {
				$menu[] = array( '', 'read', 'separator-sm', '', 'wp-menu-separator sm' );
			}

			add_menu_page( __( 'Sermon Manager', 'sermon-manager' ), __( 'Sermon Manager', 'sermon-manager' ), 'manage_sermons', 'sermon-manager', null, null, '55.5' );

			add_submenu_page( 'edit.php?post_type=sermon', __( 'Sermons', 'sermon-manager' ), __( 'Sermons', 'sermon-manager' ), 'manage_sermon_terms', 'sermon_attributes', array(
				$this,
				'attributes_page'
			) );
		}

		/**
		 * Add menu item.
		 */
		public function stats_menu() {
			if ( current_user_can( 'manage_sermons' ) ) {
				add_submenu_page( 'sermon-manager', __( 'Stats', 'sermon-manager' ), __( 'Stats', 'sermon-manager' ), 'view_sm_stats', 'sm-stats', array(
					$this,
					'stats_page'
				) );
			}
		}

		/**
		 * Add menu item.
		 */
		public function settings_menu() {
			$settings_page = add_submenu_page( 'sermon-manager', __( 'Sermon Manager settings', 'sermon-manager' ), __( 'Settings', 'sermon-manager' ), 'manage_sermons', 'sm-settings', array(
				$this,
				'settings_page'
			) );
		}

		/**
		 * Add menu item.
		 */
		public function status_menu() {
			add_submenu_page( 'sermon-manager', __( 'Sermon Manager status', 'sermon-manager' ), __( 'Status', 'sermon-manager' ), 'manage_sermons', 'sm-status', array(
				$this,
				'status_page'
			) );
		}

		/**
		 * Addons menu item.
		 */
		public function addons_menu() {
			add_submenu_page( 'sermon-manager', __( 'Sermon Manager extensions', 'sermon-manager' ), __( 'Extensions', 'sermon-manager' ), 'manage_sermons', 'sm-addons', array(
				$this,
				'addons_page'
			) );
		}

		/**
		 * Highlights the correct top level admin menu item for post type add screens.
		 */
		public function menu_highlight() {
			global $parent_file, $submenu_file, $post_type;

			// todo
		}

		/**
		 * Reorder the Sermon Manager menu items in admin.
		 *
		 * @param mixed $menu_order
		 *
		 * @return array
		 */
		public function menu_order( $menu_order ) {
			global $submenu;

			if ( isset( $submenu['sermon-manager'] ) ) {
				// Remove 'Sermon Manager' sub menu item
				unset( $submenu['sermon-manager'][0] );
			}

			// Initialize our custom order array
			$sm_menu_order = array();

			// Get the index of our custom separator
			$sm_separator = array_search( 'separator-sm', $menu_order );

			// Get index of sermon menu
			$sm_sermon = array_search( 'edit.php?post_type=sermon', $menu_order );

			// Loop through menu order and do some rearranging
			foreach ( $menu_order as $index => $item ) {

				if ( ( ( 'sermon-manager' ) == $item ) ) {
					$sm_menu_order[] = 'separator-sm';
					$sm_menu_order[] = $item;
					$sm_menu_order[] = 'edit.php?post_type=sermon';
					unset( $menu_order[ $sm_separator ] );
					unset( $menu_order[ $sm_sermon ] );
				} elseif ( ! in_array( $item, array( 'separator-sm' ) ) ) {
					$sm_menu_order[] = $item;
				}
			}

			// Return order
			return $sm_menu_order;
		}

		/**
		 * Custom menu order.
		 *
		 * @return bool
		 */
		public function custom_menu_order() {
			return current_user_can( 'manage_sermons' );
		}

		/**
		 * Init the stats page.
		 */
		public function stats_page() {
			SM_Admin_Stats::output();
		}

		/**
		 * Init the settings page.
		 */
		public function settings_page() {
			SM_Admin_Settings::output();
		}

		/**
		 * Init the status page.
		 */
		public function status_page() {
			SM_Admin_Status::output();
		}

		/**
		 * Init the addons page.
		 */
		public function addons_page() {
			#SM_Admin_Addons::output(); TODO
		}

		/**
		 * Add custom nav meta box.
		 *
		 * Adapted from http://www.johnmorrisonline.com/how-to-add-a-fully-functional-custom-meta-box-to-wordpress-navigation-menus/.
		 */
		public function add_nav_menu_meta_boxes() {
			add_meta_box( 'sm_endpoints_nav_link', __( 'Sermon Manager endpoints', 'sermon-manager' ), array(
				$this,
				'nav_menu_links'
			), 'nav-menus', 'side', 'low' );
		}

		/**
		 * Add the "Visit Store" link in admin bar main menu.
		 *
		 * @since 3.0.0
		 *
		 * @param WP_Admin_Bar $wp_admin_bar
		 */
		public function admin_bar_menus( $wp_admin_bar ) {
			global $post_type;

			if ( ! is_admin() || ! is_user_logged_in() ) {
				return;
			}

			// Show only when the user is a member of this site, or they're a super admin.
			if ( ! is_user_member_of_blog() && ! is_super_admin() ) {
				return;
			}

			// If we are on single page and if it's sermon
			if ( $post_type === 'sermon' && is_single() ) {
				// Add an option to visit the store.
				$wp_admin_bar->add_node( array(
					'parent' => '',
					'id'     => 'sermon-manager',
					'title'  => __( 'Edit sermon', 'sermon-manager' ),
					'href'   => SM()->sermon_factory->get_sermon( 0 )->get_permalink(), // todo
				) );
			}
		}
	}

endif;

return new SM_Admin_Menus();
