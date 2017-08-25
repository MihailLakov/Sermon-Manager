<?php
/**
 * Sermon Manager API
 *
 * Handles SM-API endpoint requests.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SM_API {
	/**
	 * Setup class.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		// Add query vars.
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

		// Register API endpoints.
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );

		// Handle sm-api endpoint requests.
		add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );

		// WP REST API.
		$this->rest_api_init();
	}

	/**
	 * Init WP REST API.
	 *
	 * @since 3.0.0
	 */
	private function rest_api_init() {
		// REST API was included starting WordPress 4.4.
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		$this->rest_api_includes();

		// Init REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Include REST API classes.
	 *
	 * @since 3.0.0
	 */
	private function rest_api_includes() {
		// Exception handler.
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-exception.php' );

		// Authentication.
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-authentication.php' );

		// WP-API classes and functions.
		include_once( dirname( __FILE__ ) . '/vendor/wp-rest-functions.php' );
		if ( ! class_exists( 'WP_REST_Controller' ) ) {
			include_once( dirname( __FILE__ ) . '/vendor/abstract-wp-rest-controller.php' );
		}

		// Abstract controllers.
		include_once( dirname( __FILE__ ) . '/abstracts/abstract-sm-rest-controller.php' );
		include_once( dirname( __FILE__ ) . '/abstracts/abstract-sm-rest-posts-controller.php' );
		include_once( dirname( __FILE__ ) . '/abstracts/abstract-sm-rest-crud-controller.php' );
		include_once( dirname( __FILE__ ) . '/abstracts/abstract-sm-rest-terms-controller.php' );
		include_once( dirname( __FILE__ ) . '/abstracts/abstract-sm-settings-api.php' );

		// REST API v2 controllers.
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-sermon-attribute-terms-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-sermon-attributes-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-sermon-categories-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-sermon-tags-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-sermons-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-stats-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-settings-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-setting-options-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-tax-classes-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-system-status-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-sm-rest-system-status-tools-controller.php' );
	}

	/**
	 * Sermon Manager API
	 *
	 * @since 3.0.0
	 */
	public static function add_endpoint() {
		add_rewrite_endpoint( 'sm-api', EP_ALL );
	}

	/**
	 * Add new query vars.
	 *
	 * @since 3.0.0
	 *
	 * @param array $vars
	 *
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'sm-api';

		return $vars;
	}

	/**
	 * API request - Trigger any API requests.
	 *
	 * @since   3.0.0
	 * @version 2.4
	 */
	public function handle_api_requests() {
		global $wp;

		if ( ! empty( $_GET['sm-api'] ) ) {
			$wp->query_vars['sm-api'] = $_GET['sm-api'];
		}

		// sm-api endpoint requests.
		if ( ! empty( $wp->query_vars['sm-api'] ) ) {

			// Buffer, we won't want any output here.
			ob_start();

			// No cache headers.
			nocache_headers();

			// Clean the API request.
			$api_request = strtolower( sm_clean( $wp->query_vars['sm-api'] ) );

			// Trigger generic action before request hook.
			do_action( 'sm_api_request', $api_request );

			// Is there actually something hooked into this API request? If not trigger 400 - Bad request.
			status_header( has_action( 'sm_api_' . $api_request ) ? 200 : 400 );

			// Trigger an action which plugins can hook into to fulfill the request.
			do_action( 'sm_api_' . $api_request );

			// Done, clear buffer and exit.
			ob_end_clean();
			die( '-1' );
		}
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 3.0.0
	 */
	public function register_rest_routes() {
		// Register settings to the REST API.
		$this->register_wp_admin_settings();

		$controllers = array(
			// v2 controllers.
			'SM_REST_Sermon_Attribute_Terms_Controller',
			'SM_REST_Sermon_Attributes_Controller',
			'SM_REST_Sermon_Categories_Controller',
			'SM_REST_Sermon_Tags_Controller',
			'SM_REST_Sermons_Controller',
			'SM_REST_Stats_Controller',
			'SM_REST_Settings_Controller',
			'SM_REST_Tax_Classes_Controller',
			'SM_REST_System_Status_Controller',
			'SM_REST_System_Status_Tools_Controller',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}

	/**
	 * Register Sermon Manager settings from WP-API to the REST API.
	 *
	 * @since 3.0.0
	 */
	public function register_wp_admin_settings() {
		$pages = SM_Admin_Settings::get_settings_pages();
		foreach ( $pages as $page ) {
			new SM_Register_WP_Admin_Settings( $page, 'page' );
		}
	}

}
