<?php
/**
 * Load assets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SM_Admin_Assets', false ) ) :

	/**
	 * SM_Admin_Assets Class.
	 */
	class SM_Admin_Assets {

		/**
		 * Hook in tabs.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		}

		/**
		 * Enqueue styles.
		 */
		public function admin_styles() {
			global $wp_scripts;

			$screen         = get_current_screen();
			$screen_id      = $screen ? $screen->id : '';
			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.11.4';

			// Register admin styles
			wp_register_style( 'sm_admin_styles', SM()->plugin_url() . '/assets/css/admin.css', array(), SM_VERSION );
			wp_register_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.min.css', array(), $jquery_version );

			// Admin styles for Sermon Manager pages only
			if ( in_array( $screen_id, sm_get_screen_ids() ) ) {
				wp_enqueue_style( 'sm_admin_styles' );
				wp_enqueue_style( 'jquery-ui-style' );
				wp_enqueue_style( 'wp-color-picker' );
			}
		}

		/**
		 * Enqueue scripts.
		 */
		public function admin_scripts() {
			global $wp_query, $post;

			$screen       = get_current_screen();
			$screen_id    = $screen ? $screen->id : '';
			$sm_screen_id = sanitize_title( __( 'Sermon Manager', 'sermon-manager' ) );
			$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Register scripts
			wp_register_script( 'sm_admin', SM()->plugin_url() . '/assets/js/admin/sm_admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), SM_VERSION );
			wp_register_script( 'jquery-blockui', SM()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
			wp_register_script( 'jquery-tiptip', SM()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), SM_VERSION, true );
			wp_register_script( 'stupidtable', SM()->plugin_url() . '/assets/js/stupidtable/stupidtable' . $suffix . '.js', array( 'jquery' ), SM_VERSION );
			wp_register_script( 'sm-admin-meta-boxes', SM()->plugin_url() . '/assets/js/admin/meta-boxes' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'sm-enhanced-select', 'plupload-all', 'stupidtable', 'jquery-tiptip' ), SM_VERSION );
			wp_register_script( 'select2', SM()->plugin_url() . '/assets/js/select2/select2.full' . $suffix . '.js', array( 'jquery' ), '4.0.3' );
			wp_register_script( 'sm-enhanced-select', SM()->plugin_url() . '/assets/js/admin/sm-enhanced-select' . $suffix . '.js', array( 'jquery', 'select2' ), SM_VERSION );
			wp_localize_script( 'sm-enhanced-select', 'sm_enhanced_select_params', array(
				'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'sermon-manager' ),
				'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'sermon-manager' ),
				'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'sermon-manager' ),
				'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'sermon-manager' ),
				'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'sermon-manager' ),
				'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'sermon-manager' ),
				'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'sermon-manager' ),
				'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'sermon-manager' ),
				'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'sermon-manager' ),
				'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'sermon-manager' ),
				'ajax_url'                  => admin_url( 'admin-ajax.php' ),
				'search_products_nonce'     => wp_create_nonce( 'search-sermons' ),
			) );

			// Sermon Manager admin pages
			if ( in_array( $screen_id, sm_get_screen_ids() ) ) {
				wp_enqueue_script( 'iris' );
				wp_enqueue_script( 'sm_admin' );
				wp_enqueue_script( 'sm-enhanced-select' );
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script( 'jquery-ui-autocomplete' );


				$params = array(
					'strings' => array(
						'import_sermon' => __( 'Import', 'sermon-manager' ),
						'export_sermon' => __( 'Export', 'sermon-manager' ),
					),
					'urls' => array(
						'import_sermon' => esc_url_raw( admin_url( 'edit.php?post_type=sermon&page=sermon_importer' ) ),
						'export_sermon' => esc_url_raw( admin_url( 'edit.php?post_type=sermon&page=sermon_exporter' ) ),
					),
				);

				wp_localize_script( 'sm_admin', 'sm_admin', $params );
			}

			// Sermons
			if ( in_array( $screen_id, array( 'edit-sermon' ) ) ) {
				wp_enqueue_script( 'sm_quick-edit', SM()->plugin_url() . '/assets/js/admin/quick-edit' . $suffix . '.js', array( 'jquery', 'sm_admin' ), SM_VERSION );
			}

			// Meta boxes
			if ( in_array( $screen_id, array( 'sermon', 'edit-sermon' ) ) ) {
				wp_enqueue_media();
				wp_register_script( 'sm-admin-sermon-meta-boxes', SM()->plugin_url() . '/assets/js/admin/meta-boxes-sermon' . $suffix . '.js', array( 'sm-admin-meta-boxes', 'media-models' ), SM_VERSION );

				wp_enqueue_script( 'sm-admin-sermon-meta-boxes' );
			}

			// API settings
			if ( $sm_screen_id . '_page_sm-settings' === $screen_id && isset( $_GET['section'] ) && 'keys' == $_GET['section'] ) {
				wp_register_script( 'sm-api-keys', SM()->plugin_url() . '/assets/js/admin/api-keys' . $suffix . '.js', array( 'jquery', 'sm_admin', 'underscore', 'backbone', 'wp-util' ), SM_VERSION, true );
				wp_enqueue_script( 'sm-api-keys' );
				wp_localize_script(
					'sm-api-keys',
					'sm_admin_api_keys',
					array(
						'ajax_url'         => admin_url( 'admin-ajax.php' ),
						'update_api_nonce' => wp_create_nonce( 'update-api-key' ),
					)
				);
			}

			// System status.
			if ( $sm_screen_id . '_page_sm-status' === $screen_id ) {
				wp_register_script( 'sm-admin-system-status', SM()->plugin_url() . '/assets/js/admin/system-status' . $suffix . '.js', array(), SM_VERSION );
				wp_enqueue_script( 'sm-admin-system-status' );
				wp_localize_script(
					'sm-admin-system-status',
					'sm_admin_system_status',
					array(
						'delete_log_confirmation' => esc_js( __( 'Are you sure you want to delete this log?', 'sermon-manager' ) ),
					)
				);
			}
		}
	}

endif;

return new SM_Admin_Assets();
