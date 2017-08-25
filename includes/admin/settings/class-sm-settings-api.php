<?php
/**
 * Sermon Manager API Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SM_Settings_Rest_API', false ) ) :

	/**
	 * SM_Settings_Rest_API.
	 */
	class SM_Settings_Rest_API extends SM_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->id = 'api';
			/** @noinspection PhpUndefinedFieldInspection */
			$this->label = __( 'API', 'sermon-manager' );

			add_filter( 'sm_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'sm_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'sm_sections_' . $this->id, array( $this, 'output_sections' ) );
			add_action( 'sm_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );
			add_action( 'sm_settings_save_' . $this->id, array( $this, 'save' ) );

			$this->notices();
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				''     => __( 'Settings', 'sermon-manager' ),
				'keys' => __( 'Keys/Apps', 'sermon-manager' ),
			);

			return apply_filters( 'sm_get_sections_' . $this->id, $sections );
		}

		/**
		 * Get settings array.
		 *
		 * @param string $current_section
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {
			$settings = array();

			if ( '' === $current_section ) {
				$settings = apply_filters( 'sm_settings_rest_api', array(
					array(
						'title' => __( 'General options', 'sermon-manager' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'general_options',
					),

					array(
						'title'   => __( 'API', 'sermon-manager' ),
						'desc'    => __( 'Enable the REST API', 'sermon-manager' ),
						'id'      => 'sm_api_enabled',
						'type'    => 'checkbox',
						'default' => 'yes',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'general_options',
					),
				) );
			}

			return apply_filters( 'sm_get_settings_' . $this->id, $settings, $current_section );
		}

		/**
		 * Form method.
		 *
		 * @param  string $method
		 *
		 * @return string
		 */
		public function form_method( $method ) {
			global $current_section;

			if ( 'keys' == $current_section ) {
				if ( isset( $_GET['create-key'] ) || isset( $_GET['edit-key'] ) ) {
					return 'post';
				}

				return 'get';
			}

			return 'post';
		}

		/**
		 * Notices.
		 */
		private function notices() {
			if ( isset( $_GET['section'] ) && 'keys' == $_GET['section'] ) {
				SM_Admin_API_Keys::notices();
			}
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $current_section;

			if ( 'keys' === $current_section ) {
				SM_Admin_API_Keys::page_output();
			} else {
				$settings = $this->get_settings( $current_section );
				SM_Admin_Settings::output_fields( $settings );
			}
		}

		/**
		 * Save settings.
		 */
		public function save() {
			global $current_section;

			if ( apply_filters( 'sm_rest_api_valid_to_save', ! in_array( $current_section, array( 'keys' ) ) ) ) {
				$settings = $this->get_settings();
				SM_Admin_Settings::save_fields( $settings );
			}
		}
	}

endif;

return new SM_Settings_Rest_API();
