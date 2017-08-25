<?php
/**
 * REST API SM System Status Tools Controller
 *
 * Handles requests to the /system_status/tools/* endpoints.
 *
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package SermonManager/API
 * @extends SM_REST_Controller
 */
class SM_REST_System_Status_Tools_Controller extends SM_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'sm/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'system_status/tools';

	/**
	 * Register the routes for /system_status/tools/*.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\w-]+)', array(
			'args'   => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'sermon-manager' ),
					'type'        => 'string',
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check whether a given request has permission to view system status tools.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! sm_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new WP_Error( 'sm_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'sermon-manager' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check whether a given request has permission to view a specific system status tool.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! sm_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new WP_Error( 'sm_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'sermon-manager' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check whether a given request has permission to execute a specific system status tool.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! sm_rest_check_manager_permissions( 'system_status', 'edit' ) ) {
			return new WP_Error( 'sm_rest_cannot_update', __( 'Sorry, you cannot update resource.', 'sermon-manager' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * A list of avaiable tools for use in the system status section.
	 * 'button' becomes 'action' in the API.
	 *
	 * @return array
	 */
	public function get_tools() {
		$tools = array(
			'clear_transients'         => array(
				'name'   => __( 'SM transients', 'sermon-manager' ),
				'button' => __( 'Clear transients', 'sermon-manager' ),
				'desc'   => __( 'This tool will clear the sermons transients cache.', 'sermon-manager' ),
			),
			'clear_expired_transients' => array(
				'name'   => __( 'Expired transients', 'sermon-manager' ),
				'button' => __( 'Clear expired transients', 'sermon-manager' ),
				'desc'   => __( 'This tool will clear ALL expired transients from WordPress.', 'sermon-manager' ),
			),
			'reset_roles'              => array(
				'name'   => __( 'Capabilities', 'sermon-manager' ),
				'button' => __( 'Reset capabilities', 'sermon-manager' ),
				'desc'   => __( 'This tool will reset the admin roles to default. Use this if your users cannot access all of the Sermon Manager admin pages.', 'sermon-manager' ),
			),
			'install_pages'            => array(
				'name'   => __( 'Install Sermon Manager pages', 'sermon-manager' ),
				'button' => __( 'Install pages', 'sermon-manager' ),
				'desc'   => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'sermon-manager' ),
					__( 'This tool will install all the missing Sermon Manager pages. Pages already defined and set up will not be replaced.', 'sermon-manager' )
				),
			),
			'reset_tracking'           => array(
				'name'   => __( 'Reset usage tracking settings', 'sermon-manager' ),
				'button' => __( 'Reset usage tracking settings', 'sermon-manager' ),
				'desc'   => __( 'This will reset your usage tracking settings, causing it to show the opt-in banner again and not sending any data.', 'sermon-manager' ),
			),
		);

		return apply_filters( 'sm_debug_tools', $tools );
	}

	/**
	 * Get a list of system status tools.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$tools = array();
		foreach ( $this->get_tools() as $id => $tool ) {
			$tools[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( array(
				'id'          => $id,
				'name'        => $tool['name'],
				'action'      => $tool['button'],
				'description' => $tool['desc'],
			), $request ) );
		}

		$response = rest_ensure_response( $tools );

		return $response;
	}

	/**
	 * Return a single tool.
	 *
	 * @param  WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$tools = $this->get_tools();
		if ( empty( $tools[ $request['id'] ] ) ) {
			return new WP_Error( 'sm_rest_system_status_tool_invalid_id', __( 'Invalid tool ID.', 'sermon-manager' ), array( 'status' => 404 ) );
		}
		$tool = $tools[ $request['id'] ];

		return rest_ensure_response( $this->prepare_item_for_response( array(
			'id'          => $request['id'],
			'name'        => $tool['name'],
			'action'      => $tool['button'],
			'description' => $tool['desc'],
		), $request ) );
	}

	/**
	 * Update (execute) a tool.
	 *
	 * @param  WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$tools = $this->get_tools();
		if ( empty( $tools[ $request['id'] ] ) ) {
			return new WP_Error( 'sm_rest_system_status_tool_invalid_id', __( 'Invalid tool ID.', 'sermon-manager' ), array( 'status' => 404 ) );
		}

		$tool = $tools[ $request['id'] ];
		$tool = array(
			'id'          => $request['id'],
			'name'        => $tool['name'],
			'action'      => $tool['button'],
			'description' => $tool['desc'],
		);

		$execute_return = $this->execute_tool( $request['id'] );
		$tool           = array_merge( $tool, $execute_return );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $tool, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Prepare a tool item for serialization.
	 *
	 * @param  array           $item    Object.
	 * @param  WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$context = empty( $request['context'] ) ? 'view' : $request['context'];
		$data    = $this->add_additional_fields_to_object( $item, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item['id'] ) );

		return $response;
	}

	/**
	 * Get the system status tools schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'system_status_tool',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'A unique identifier for the tool.', 'sermon-manager' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'name'        => array(
					'description' => __( 'Tool name.', 'sermon-manager' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'action'      => array(
					'description' => __( 'What running the tool will do.', 'sermon-manager' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'description' => array(
					'description' => __( 'Tool description.', 'sermon-manager' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'success'     => array(
					'description' => __( 'Did the tool run successfully?', 'sermon-manager' ),
					'type'        => 'boolean',
					'context'     => array( 'edit' ),
				),
				'message'     => array(
					'description' => __( 'Tool return message.', 'sermon-manager' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	protected function prepare_links( $id ) {
		$base  = '/' . $this->namespace . '/' . $this->rest_base;
		$links = array(
			'item' => array(
				'href'       => rest_url( trailingslashit( $base ) . $id ),
				'embeddable' => true,
			),
		);

		return $links;
	}

	/**
	 * Get any query params needed.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}

	/**
	 * Actually executes a a tool.
	 *
	 * @param  string $tool
	 *
	 * @return array
	 */
	public function execute_tool( $tool ) {
		global $wpdb;
		$ran = true;
		switch ( $tool ) {
			case 'clear_transients' :
				sm_delete_sermon_transients();
				$message = __( 'Sermon transients cleared', 'sermon-manager' );
				break;
			case 'clear_expired_transients' :
				/*
				 * Deletes all expired transients. The multi-table delete syntax is used.
				 * to delete the transient record from table a, and the corresponding.
				 * transient_timeout record from table b.
				 *
				 * Based on code inside core's upgrade_network() function.
				 */
				$sql  = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
					WHERE a.option_name LIKE %s
					AND a.option_name NOT LIKE %s
					AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
					AND b.option_value < %d";
				$rows = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) );

				$sql   = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
					WHERE a.option_name LIKE %s
					AND a.option_name NOT LIKE %s
					AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
					AND b.option_value < %d";
				$rows2 = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_site_transient_' ) . '%', $wpdb->esc_like( '_site_transient_timeout_' ) . '%', time() ) );

				$message = sprintf( __( '%d transients rows cleared', 'sermon-manager' ), $rows + $rows2 );
				break;
			case 'reset_roles' :
				// Remove then re-add caps and roles
				SM_Install::remove_roles();
				SM_Install::create_roles();
				$message = __( 'Roles successfully reset', 'sermon-manager' );
				break;
			case 'install_pages' :
				SM_Install::create_pages();
				$message = __( 'All missing Sermon Manager pages successfully installed', 'sermon-manager' );
				break;
			case 'reset_tracking' :
				delete_option( 'sm_allow_tracking' );
				SM_Admin_Notices::add_notice( 'tracking' );
				$message = __( 'Usage tracking settings successfully reset.', 'sermon-manager' );
				break;
			default :
				$tools = $this->get_tools();
				if ( isset( $tools[ $tool ]['callback'] ) ) {
					$callback = $tools[ $tool ]['callback'];
					$return   = call_user_func( $callback );
					if ( is_string( $return ) ) {
						$message = $return;
					} elseif ( false === $return ) {
						$callback_string = is_array( $callback ) ? get_class( $callback[0] ) . '::' . $callback[1] : $callback;
						$ran             = false;
						$message         = sprintf( __( 'There was an error calling %s', 'sermon-manager' ), $callback_string );
					} else {
						$message = __( 'Tool ran.', 'sermon-manager' );
					}
				} else {
					$ran     = false;
					$message = __( 'There was an error calling this tool. There is no callback present.', 'sermon-manager' );
				}
				break;
		}

		return array( 'success' => $ran, 'message' => $message );
	}
}
