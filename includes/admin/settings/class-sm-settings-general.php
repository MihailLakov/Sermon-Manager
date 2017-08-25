<?php
/**
 * Sermon Manager General Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SM_Settings_General', false ) ) :

	/**
	 * SM_Settings_General.
	 */
	class SM_Settings_General extends SM_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id    = 'general';
			$this->label = __( 'General', 'sermon-manager' );

			add_filter( 'sm_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'sm_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'sm_settings_save_' . $this->id, array( $this, 'save' ) );
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {
			$settings = apply_filters( 'sm_general_settings', array(

				array(
					'title' => __( 'General options', 'sermon-manager' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_options'
				),

				array( 'type' => 'sectionend', 'id' => 'general_options' ),
			) );

			return apply_filters( 'sm_get_settings_' . $this->id, $settings );
		}

		/**
		 * Output a color picker input box.
		 *
		 * @param mixed  $name
		 * @param string $id
		 * @param mixed  $value
		 * @param string $desc (default: '')
		 */
		public function color_picker( $name, $id, $value, $desc = '' ) {
			echo '<div class="color_box">' . sm_help_tip( $desc ) . '
			<input name="' . esc_attr( $id ) . '" id="' . esc_attr( $id ) . '" type="text" value="' . esc_attr( $value ) . '" class="colorpick" /> <div id="colorPickerDiv_' . esc_attr( $id ) . '" class="colorpickdiv"></div>
		</div>';
		}

		/**
		 * Save settings.
		 */
		public function save() {
			$settings = $this->get_settings();

			SM_Admin_Settings::save_fields( $settings );
		}
	}

endif;

return new SM_Settings_General();
