<?php
/**
 * Installation related functions and actions.
 *
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SM_Install {
	/** @var array DB updates and callbacks that need to be run per version */
	private static $db_updates = array(
		'3.0.0'
	);

	/** @var object Background update class */
	private static $background_updater;

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		/** @noinspection PhpUndefinedConstantInspection */
		add_action( 'in_plugin_update_message-' . SM_ABSPATH . '/' . basename( SM_PLUGIN_FILE ), array(
			__CLASS__,
			'in_plugin_update_message'
		) );
		/** @noinspection PhpUndefinedConstantInspection */
		add_filter( 'plugin_action_links_' . SM_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
	}

	/**
	 * Init background updates
	 */
	public static function init_background_updater() {
		include_once( dirname( __FILE__ ) . '/class-sm-background-updater.php' );
		self::$background_updater = new SM_Background_Updater();
	}

	/**
	 * Check Sermon Manager version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && get_option( 'sm_version' ) !== SM()->version ) {
			self::install();
			do_action( 'sm_updated' );
		}
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_sm'] ) ) {
			self::update();
			SM_Admin_Notices::add_notice( 'update' );
		}
		if ( ! empty( $_GET['force_update_sm'] ) ) {
			do_action( 'wp_sm_updater_cron' );
			wp_safe_redirect( admin_url( 'admin.php?page=sm-settings' ) );
			exit;
		}
	}

	/**
	 * Install Sermon Manager.
	 */
	public static function install() {
		global $wpdb;

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( ! defined( 'SM_INSTALLING' ) ) {
			define( 'SM_INSTALLING', true );
		}

		// Ensure needed classes are loaded
		include_once( dirname( __FILE__ ) . '/admin/class-sm-admin-notices.php' );

		self::create_options();
		self::create_tables();
		self::create_roles();

		// Register post types
		SM_Post_types::register_post_types();
		SM_Post_types::register_taxonomies();

		// Also register endpoints - this needs to be done prior to rewrite rule flush
		SM_API::add_endpoint();
		SM_Auth::add_endpoint();

		self::create_files();

		// Queue upgrades/setup wizard
		$current_sm_version = get_option( 'sm_version', null );
		$current_db_version = get_option( 'sm_db_version', null );

		SM_Admin_Notices::remove_all_notices();

		// No versions? This is a new install :)
		if ( is_null( $current_sm_version ) && is_null( $current_db_version ) && apply_filters( 'sm_enable_setup_wizard', true ) ) {
			SM_Admin_Notices::add_notice( 'install' );
			set_transient( '_sm_activation_redirect', 1, 30 );

			// No page? Let user run wizard again..
		} elseif ( ! get_option( 'sm_sermon_page_id' ) ) {
			SM_Admin_Notices::add_notice( 'install' );
		}

		if ( ! is_null( $current_db_version ) && version_compare( $current_db_version, max( array_keys( self::$db_updates ) ), '<' ) ) {
			SM_Admin_Notices::add_notice( 'update' );
		} else {
			self::update_db_version();
		}

		self::update_sm_version();

		// Flush rules after install
		do_action( 'sm_flush_rewrite_rules' );

		/*
		 * Deletes all expired transients. The multi-table delete syntax is used
		 * to delete the transient record from table a, and the corresponding
		 * transient_timeout record from table b.
		 *
		 * Based on code inside core's upgrade_network() function.
		 */
		/** @noinspection SqlNoDataSourceInspection */
		$sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
			AND b.option_value < %d";
		$wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) );

		// Trigger action
		do_action( 'sm_installed' );
	}

	/**
	 * Update SM version to current.
	 */
	private static function update_sm_version() {
		delete_option( 'sm_version' );
		add_option( 'sm_version', SM()->version );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {
		$current_db_version = get_option( 'sm_db_version' );
		$logger             = sm_get_logger();
		$update_queued      = false;

		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					$logger->info(
						sprintf( 'Queuing %s - %s', $version, $update_callback ),
						array( 'source' => 'sm_db_updates' )
					);
					self::$background_updater->push_to_queue( $update_callback );
					$update_queued = true;
				}
			}
		}

		if ( $update_queued ) {
			self::$background_updater->save()->dispatch();
		}
	}

	/**
	 * Update DB version to current.
	 *
	 * @param string $version
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'sm_db_version' );
		add_option( 'sm_db_version', is_null( $version ) ? SM()->version : $version );
	}

	/**
	 * Add more cron schedules.
	 *
	 * @param  array $schedules
	 *
	 * @return array
	 */
	public static function cron_schedules( $schedules ) {
		$schedules['monthly'] = array(
			'interval' => 2635200,
			'display'  => __( 'Monthly', 'sermon-manager' ),
		);

		return $schedules;
	}

	/**
	 * Create pages that the plugin relies on, storing page IDs in variables.
	 */
	public static function create_pages() {
		include_once( dirname( __FILE__ ) . '/admin/sm-admin-functions.php' );

		$pages = apply_filters( 'sm_create_pages', array(
			'sermons' => array(
				'name'    => _x( 'sermons', 'Page slug', 'sermon-manager' ),
				'title'   => _x( 'Sermons', 'Page title', 'sermon-manager' ),
				'content' => '',
			),
		) );

		foreach ( $pages as $key => $page ) {
			sm_create_page( esc_sql( $page['name'] ), 'sm_' . $key . '_page_id', $page['title'], $page['content'], ! empty( $page['parent'] ) ? sm_get_page_id( $page['parent'] ) : '' );
		}

		delete_transient( 'sm_cache_excluded_uris' );
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function create_options() {
		// Include settings so that we can run through defaults
		include_once( dirname( __FILE__ ) . '/admin/class-sm-admin-settings.php' );

		$settings = SM_Admin_Settings::get_settings_pages();

		foreach ( $settings as $section ) {
			if ( ! method_exists( $section, 'get_settings' ) ) {
				continue;
			}
			$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );

			foreach ( $subsections as $subsection ) {
				foreach ( $section->get_settings( $subsection ) as $value ) {
					if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
						$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
						add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
					}
				}
			}
		}
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * Tables:
	 *        sm_termmeta - Term meta table - sadly WordPress does not have termmeta so we need our own
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( self::get_schema() );
	}

	/**
	 * Get Table schema.
	 *
	 * A note on indexes; Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about
	 * that. As of WordPress 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an
	 * index which used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191
	 * characters.
	 *
	 * Changing indexes may cause duplicate index notices in logs due to https://core.trac.wordpress.org/ticket/34870
	 * but dropping indexes first causes too much load on some servers/larger DB.
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		/** @noinspection SqlNoDataSourceInspection */
		$tables = "
CREATE TABLE {$wpdb->prefix}sm_api_keys (
  key_id BIGINT UNSIGNED NOT NULL auto_increment,
  user_id BIGINT UNSIGNED NOT NULL,
  description varchar(200) NULL,
  permissions varchar(10) NOT NULL,
  consumer_key char(64) NOT NULL,
  consumer_secret char(43) NOT NULL,
  nonces longtext NULL,
  truncated_key char(7) NOT NULL,
  last_access datetime NULL default null,
  PRIMARY KEY  (key_id),
  KEY consumer_key (consumer_key),
  KEY consumer_secret (consumer_secret)
) $collate;
CREATE TABLE {$wpdb->prefix}sm_log (
  log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  timestamp datetime NOT NULL,
  level smallint(4) NOT NULL,
  source varchar(200) NOT NULL,
  message longtext NOT NULL,
  context longtext NULL,
  PRIMARY KEY (log_id),
  KEY level (level)
) $collate;
		";

		return $tables;
	}

	/**
	 * Create roles and capabilities.
	 */
	public static function create_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		// Sermon manager role
		add_role( 'sermon_manager', __( 'Sermon manager', 'sermon-manager' ), array(
			'level_9'                => true,
			'level_8'                => true,
			'level_7'                => true,
			'level_6'                => true,
			'level_5'                => true,
			'level_4'                => true,
			'level_3'                => true,
			'level_2'                => true,
			'level_1'                => true,
			'level_0'                => true,
			'read'                   => true,
			'read_private_pages'     => true,
			'read_private_posts'     => true,
			'edit_users'             => true,
			'edit_posts'             => true,
			'edit_pages'             => true,
			'edit_published_posts'   => true,
			'edit_published_pages'   => true,
			'edit_private_pages'     => true,
			'edit_private_posts'     => true,
			'edit_others_posts'      => true,
			'edit_others_pages'      => true,
			'publish_posts'          => true,
			'publish_pages'          => true,
			'delete_posts'           => true,
			'delete_pages'           => true,
			'delete_private_pages'   => true,
			'delete_private_posts'   => true,
			'delete_published_pages' => true,
			'delete_published_posts' => true,
			'delete_others_posts'    => true,
			'delete_others_pages'    => true,
			'manage_categories'      => true,
			'manage_links'           => true,
			'moderate_comments'      => true,
			'unfiltered_html'        => true,
			'upload_files'           => true,
			'export'                 => true,
			'import'                 => true,
			'list_users'             => true,
		) );

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'sermon_manager', $cap );
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}

	/**
	 * Get capabilities for Sermon Manager - these are assigned to sermon manager role during installation or reset.
	 *
	 * @return array
	 */
	private static function get_core_capabilities() {
		$capabilities = array();

		$capabilities['core'] = array(
			'manage_sermons',
			'manage_sermon_terms',
			'view_sm_stats',
		);

		$capability_types = array( 'sermon', 'sermon_preacher', 'sermon_series', 'sermon_topic', 'sermon_book' );

		foreach ( $capability_types as $capability_type ) {

			$capabilities[ $capability_type ] = array(
				// Post type
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",
			);
		}

		return $capabilities;
	}

	/**
	 * sm_remove_roles function.
	 */
	public static function remove_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->remove_cap( 'sermon_manager', $cap );
				$wp_roles->remove_cap( 'administrator', $cap );
			}
		}

		remove_role( 'sermon_manager' );
	}

	/**
	 * Create files/directories.
	 */
	private static function create_files() {
		// Install files and folders for uploading files and prevent hotlinking
		$upload_dir      = wp_upload_dir();
		$download_method = get_option( 'sm_file_download_method', 'force' );

		/** @noinspection PhpUndefinedConstantInspection */
		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/sm_uploads',
				'file'    => 'index.html',
				'content' => '',
			),
			array(
				'base'    => SM_LOG_DIR,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
			array(
				'base'    => SM_LOG_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		if ( 'redirect' !== $download_method ) {
			$files[] = array(
				'base'    => $upload_dir['basedir'] . '/sm_uploads',
				'file'    => '.htaccess',
				'content' => 'deny from all',
			);
		}

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}


	/**
	 * Show plugin changes. Code adapted from W3 Total Cache.
	 *
	 * @param array $args
	 */
	public static function in_plugin_update_message( $args ) {
		$transient_name = 'sm_upgrade_notice_' . $args['Version'];

		if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {
			$response = wp_safe_remote_get( 'https://plugins.svn.wordpress.org/sermon-manager-for-wordpress/trunk/readme.txt' );

			if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
				$upgrade_notice = self::parse_update_notice( $response['body'], $args['new_version'] );
				set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
			}
		}

		echo apply_filters( 'sm_in_plugin_update_message', wp_kses_post( $upgrade_notice ) );
	}

	/**
	 * Parse update notice from readme file.
	 *
	 * @param  string $content
	 * @param  string $new_version
	 *
	 * @return string
	 */
	private static function parse_update_notice( $content, $new_version ) {
		// Output Upgrade Notice.
		$matches = null;
		/** @noinspection PhpUndefinedConstantInspection */
		$regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( SM_VERSION ) . '\s*=|$)~Uis';
		$upgrade_notice = '';

		if ( preg_match( $regexp, $content, $matches ) ) {
			$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

			// Convert the full version strings to minor versions.
			$notice_version_parts = explode( '.', trim( $matches[1] ) );
			/** @noinspection PhpUndefinedConstantInspection */
			$current_version_parts = explode( '.', SM_VERSION );

			if ( 3 !== sizeof( $notice_version_parts ) ) {
				return '';
			}

			$notice_version  = $notice_version_parts[0] . '.' . $notice_version_parts[1];
			$current_version = $current_version_parts[0] . '.' . $current_version_parts[1];

			// Check the latest stable version and ignore trunk.
			if ( version_compare( $current_version, $notice_version, '<' ) ) {

				$upgrade_notice .= '</p><p class="sm_plugin_upgrade_notice">';

				foreach ( $notices as $index => $line ) {
					$upgrade_notice .= preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line );
				}
			}
		}

		return wp_kses_post( $upgrade_notice );
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param    mixed $links Plugin Action links
	 *
	 * @return    array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=sm-settings' ) . '" aria-label="' . esc_attr__( 'View Sermon Manager settings', 'sermon-manager' ) . '">' . esc_html__( 'Settings', 'sermon-manager' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param    mixed $links Plugin Row Meta
	 * @param    mixed $file  Plugin Base file
	 *
	 * @return    array
	 */
	public static function plugin_row_meta( $links, $file ) {
		/** @noinspection PhpUndefinedConstantInspection */
		if ( SM_PLUGIN_BASENAME == $file ) {
			$row_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'sm_docs_url', '#' ) ) . '" aria-label="' . esc_attr__( 'View Sermon Manager documentation', 'sermon-manager' ) . '">' . esc_html__( 'Docs', 'sermon-manager' ) . '</a>',
				'apidocs' => '<a href="' . esc_url( apply_filters( 'sm_apidocs_url', '#' ) ) . '" aria-label="' . esc_attr__( 'View Sermon Manager API docs', 'sermon-manager' ) . '">' . esc_html__( 'API docs', 'sermon-manager' ) . '</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'sm_support_url', '#' ) ) . '" aria-label="' . esc_attr__( 'Visit premium customer support', 'sermon-manager' ) . '">' . esc_html__( 'Premium support', 'sermon-manager' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}
}


SM_Install::init();