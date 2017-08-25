<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sermon Manager Autoloader.
 *
 * @class          SM_Autoloader
 * @version        3.0.0
 * @package        SermonManager/Classes
 * @category       Class
 */
class SM_Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 * @access private
	 * @since  3.0.0
	 */
	private $include_path = '';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( SM_PLUGIN_FILE ) ) . '/includes/';
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class The class name
	 *
	 * @return string File name
	 * @access private
	 * @since  3.0.0
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @param string $path The path to include
	 *
	 * @return bool Successful or not
	 * @access private
	 * @since  3.0.0
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			/** @noinspection PhpIncludeInspection */
			include_once( $path );

			return true;
		}

		return false;
	}

	/**
	 * Auto-load SM classes on demand to reduce memory consumption.
	 *
	 * @param string $class
	 *
	 * @since 3.0.0
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );

		if ( 0 !== strpos( $class, 'sm_' ) ) {
			return;
		}

		$file = $this->get_file_name_from_class( $class );
		$path = '';

		if ( strpos( $class, 'sm_shortcode_' ) === 0 ) {
			$path = $this->include_path . 'shortcodes/';
		} elseif ( strpos( $class, 'sm_meta_box' ) === 0 ) {
			$path = $this->include_path . 'admin/meta-boxes/';
		} elseif ( strpos( $class, 'sm_admin' ) === 0 ) {
			$path = $this->include_path . 'admin/';
		} elseif ( strpos( $class, 'sm_log_handler_' ) === 0 ) {
			$path = $this->include_path . 'log-handlers/';
		}

		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new SM_Autoloader();
