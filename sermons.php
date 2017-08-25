<?php
/**
 * Plugin Name: Sermon Manager V3
 * Plugin URI: http://www.wpforchurch.com/products/sermon-manager-for-wordpress/
 * Description: Add audio and video sermons, manage speakers, series, and more.
 * Version: 3.0.0-alpha
 * Author: WP for Church
 * Author URI: http://www.wpforchurch.com/
 * Requires at least: 4.4
 * Tested up to: 4.8
 *
 * Text Domain: sermon-manager
 * Domain Path: /i18n/languages/
 *
 * @package  Sermon Manager
 * @category Core
 * @author   WP For Church
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'SermonManager' ) ) :
	/**
	 * Main Sermon Manager Class.
	 *
	 * @class    SermonManager
	 * @since    3.0.0
	 */
	final class SermonManager {
		/**
		 * Sermon Manager version
		 *
		 * @var string
		 * @since 3.0.0
		 */
		public $version;

		/**
		 * Query instance.
		 *
		 * @var SM_Query
		 */
		public $query = null;

		/**
		 * REST API instance
		 *
		 * @var SM_API
		 * @since 3.0.0
		 */
		public $api = null;

		/**
		 * Sermon factory instance.
		 *
		 * @var SM_Sermon_Factory
		 * @since 3.0.0
		 */
		public $sermon_factory = null;

		/**
		 * The single instance of the class
		 *
		 * @var SermonManager
		 * @since 3.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main SermonManager Instance
		 *
		 * Ensures only one instance of SermonManager is loaded or can be loaded
		 *
		 * @static
		 * @return SermonManager
		 * @since 3.0.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Cloning is forbidden
		 *
		 * @since 3.0.0
		 */
		public function __clone() {
			sm_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'sermon-manager' ), '3.0.0' );
		}

		/**
		 * Unserializing instances of this class is forbidden
		 *
		 * @since 3.0.0
		 */
		public function __wakeup() {
			sm_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'sermon-manager' ), '3.0.0' );
		}

		/**
		 * SermonManager Constructor
		 */
		public function __construct() {
			$this->_define_constants();
			$this->_includes();
			$this->_init_hooks();

			do_action( 'sm_loaded' );
		}

		/**
		 * Hook into actions and filters
		 *
		 * @access private
		 * @since  3.0.0
		 */
		private function _init_hooks() {
			register_activation_hook( __FILE__, array( 'SM_Install', 'install' ) );
			add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
			add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
			add_action( 'init', array( $this, 'init' ), 0 );
			add_action( 'init', array( 'SM_Shortcodes', 'init' ) );
		}

		/**
		 * Define constants
		 *
		 * @access private
		 * @since  3.0.0
		 */
		private function _define_constants() {
			$upload_dir = wp_upload_dir();

			$this->_define( 'SM_PLUGIN_FILE', __FILE__ );
			$this->_define( 'SM_ABSPATH', dirname( __FILE__ ) . '/' );
			$this->_define( 'SM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			$this->_define( 'SM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			$this->_define( 'SM_VERSION', $this->version );
			$this->_define( 'SM_LOG_DIR', $upload_dir['basedir'] . '/sm-logs/' );
			$this->_define( 'SM_TEMPLATE_DEBUG_MODE', false );
		}

		/**
		 * Define constant if not already set
		 *
		 * @param  string $name
		 * @param  mixed  $value
		 *
		 * @access private
		 * @since  3.0.0
		 */
		private function _define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * What type of request is this?
		 *
		 * @param  string $type admin, ajax, cron or frontend
		 *
		 * @access private
		 * @return bool|null Null if $type not set
		 * @since  3.0.0
		 */
		private function _is_request( $type ) {
			switch ( $type ) {
				case 'admin' :
					return is_admin();
				case 'ajax' :
					return defined( 'DOING_AJAX' );
				case 'cron' :
					return defined( 'DOING_CRON' );
				case 'frontend' :
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}

			return null;
		}

		/**
		 * Include required core files used in admin and on the frontend
		 *
		 * @access private
		 * @since  3.0.0
		 */
		private function _includes() {
			/**
			 * Autoloader
			 */
			include_once 'includes/class-sm-autoloader.php';

			/**
			 * Interfaces
			 */
			include_once 'includes/interfaces/class-sm-log-handler-interface.php';

			/**
			 * Abstracts
			 */
			include_once 'includes/abstracts/abstract-sm-settings-api.php';
			include_once 'includes/abstracts/abstract-sm-log-handler.php';

			/**
			 * Core classes
			 */
			include_once 'includes/sm-core-functions.php';
			include_once 'includes/class-sm-post-types.php';
			include_once 'includes/class-sm-install.php';
			include_once 'includes/class-sm-post-data.php';
			include_once 'includes/class-sm-ajax.php';
			include_once 'includes/class-sm-exception.php';
			include_once 'includes/class-sm-query.php';
			include_once 'includes/class-sm-deprecated-action-hooks.php';
			include_once 'includes/class-sm-deprecated-filter-hooks.php';
			include_once 'includes/sm-deprecated-functions.php';

			/**
			 * REST API
			 */
			include_once 'includes/class-sm-api.php';
			include_once 'includes/class-sm-auth.php';

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				include_once 'includes/class-sm-cli.php';
			}

			if ( $this->_is_request( 'admin' ) ) {
				include_once 'includes/admin/class-sm-admin.php';
			}

			if ( $this->_is_request( 'frontend' ) ) {
				$this->_frontend_includes();
			}

			if ( $this->_is_request( 'cron' ) && 'yes' === get_option( 'sm_allow_tracking', 'no' ) ) {
				include_once 'includes/class-sm-tracker.php';
			}

			$this->query = new SM_Query();
			$this->api   = new SM_API();
		}

		/**
		 * Include required frontend files
		 *
		 * @access private
		 * @since  3.0.0
		 */
		private function _frontend_includes() {
			include_once 'includes/sm-notice-functions.php';
			include_once 'includes/class-sm-shortcodes.php';
		}

		/**
		 * Function used to Init Sermon Manager Template Functions - This makes them pluggable by plugins and themes
		 *
		 * @since 3.0.0
		 */
		public function include_template_functions() {
			include_once 'includes/sm-template-functions.php';
		}

		/**
		 * Init Sermon Manager when WordPress Initialises
		 *
		 * @since 3.0.0
		 */
		public function init() {
			// Before init action.
			do_action( 'before_sm_init' );

			// Set up localisation.
			$this->load_plugin_textdomain();

			// Load class instances.
			$this->sermon_factory = new SM_Sermon_Factory(); // Sermon Factory to create new sermon instances

			// Init action.
			do_action( 'sm_init' );
		}

		/**
		 * Load Localisation files.
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present
		 *
		 * Locales found in:
		 *      - WP_LANG_DIR/sermon-manager/sermon-manager-LOCALE.mo
		 *      - WP_LANG_DIR/plugins/sermon-manager-LOCALE.mo
		 *
		 * @since 3.0.0
		 */
		public function load_plugin_textdomain() {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
			$locale = apply_filters( 'plugin_locale', $locale, 'sermon-manager' );

			unload_textdomain( 'sermon-manager' );
			load_textdomain( 'sermon-manager', WP_LANG_DIR . '/sermon-manager/sermon-manager-' . $locale . '.mo' );
			load_plugin_textdomain( 'sermon-manager', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
		}

		/**
		 * Ensure theme and server variable compatibility and setup image sizes.
		 *
		 * @since 3.0.0
		 */
		public function setup_environment() {
			$this->_add_thumbnail_support();
			$this->_add_image_sizes();
		}

		/**
		 * Ensure post thumbnail support is turned on.
		 *
		 * @access private
		 * @since  3.0.0
		 */
		private function _add_thumbnail_support() {
			if ( ! current_theme_supports( 'post-thumbnails' ) ) {
				add_theme_support( 'post-thumbnails' );
			}

			add_post_type_support( 'wpfc_sermon', 'thumbnail' );
		}

		/**
		 * Add image sizes to WP.
		 *
		 * @access private
		 * @since  3.0.0
		 */
		private function _add_image_sizes() {
			$sermon_small  = sm_get_image_size( 'sermon_small' );
			$sermon_medium = sm_get_image_size( 'sermon_medium' );
			$sermon_wide   = sm_get_image_size( 'sermon_wide' );

			add_image_size( 'sermon_small', $sermon_small['width'], $sermon_small['height'], $sermon_small['crop'] );
			add_image_size( 'sermon_medium', $sermon_medium['width'], $sermon_medium['height'], $sermon_medium['crop'] );
			add_image_size( 'sermon_wide', $sermon_wide['width'], $sermon_wide['height'], $sermon_wide['crop'] );
		}

		/**
		 * Get the plugin url.
		 *
		 * @return string
		 * @since 3.0.0
		 */
		public function plugin_url() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 * @since 3.0.0
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Get Ajax URL.
		 *
		 * @return string
		 * @since 3.0.0
		 */
		public function ajax_url() {
			return admin_url( 'admin-ajax.php', 'relative' );
		}

		/**
		 * Return the SM API URL for a given request.
		 *
		 * @param string    $request
		 * @param null|bool $ssl Set null to let WP decide, true to force SSL, false to force no SSL
		 *
		 * @return string
		 * @since 3.0.0
		 */
		public function api_request_url( $request, $ssl = null ) {
			if ( is_null( $ssl ) ) {
				$scheme = parse_url( home_url(), PHP_URL_SCHEME );
			} elseif ( $ssl ) {
				$scheme = 'https';
			} else {
				$scheme = 'http';
			}

			if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
				$api_request_url = trailingslashit( home_url( '/index.php/sm-api/' . $request, $scheme ) );
			} elseif ( get_option( 'permalink_structure' ) ) {
				$api_request_url = trailingslashit( home_url( '/sm-api/' . $request, $scheme ) );
			} else {
				$api_request_url = add_query_arg( 'sm-api', $request, trailingslashit( home_url( '', $scheme ) ) );
			}

			return esc_url_raw( apply_filters( 'sm_api_request_url', $api_request_url, $request, $ssl ) );
		}
	}
endif;

/**
 * Main instance of Sermon Manager.
 *
 * Returns the main instance of SM to prevent the need to use globals.
 *
 * @since 3.0.0
 * @return SermonManager
 */
function SM() {
	return SermonManager::instance();
}

$GLOBALS['SM'] = SM();