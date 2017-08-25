<?php
/**
 * Post Types
 *
 * Registers post types and taxonomies.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SM_Post_Types
 */
class SM_Post_Types {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
		add_action( 'init', array( __CLASS__, 'support_jetpack_omnisearch' ) );
		add_filter( 'rest_api_allowed_post_types', array( __CLASS__, 'rest_api_allowed_post_types' ) );
		add_action( 'sm_flush_rewrite_rules', array( __CLASS__, 'flush_rewrite_rules' ) );
	}

	/**
	 * Register core taxonomies.
	 */
	public static function register_taxonomies() {

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( taxonomy_exists( 'wpfc_preacher' ) ) {
			return;
		}

		do_action( 'sm_register_taxonomy' );

		$permalinks = sm_get_permalink_structure();

		register_taxonomy( 'preachers',
			apply_filters( 'sm_taxonomy_objects_preachers', array( 'sermon' ) ),
			apply_filters( 'sm_taxonomy_args_preachers', array(
				'hierarchical' => false,
				'label'        => __( 'Preachers', 'sermon-manager' ),
				'labels'       => array(
					'name'              => __( 'Preachers', 'sermon-manager' ),
					'singular_name'     => __( 'Preacher', 'sermon-manager' ),
					'menu_name'         => _x( 'Preachers', 'Admin menu name', 'sermon-manager' ),
					'search_items'      => __( 'Search preachers', 'sermon-manager' ),
					'all_items'         => __( 'All preachers', 'sermon-manager' ),
					'parent_item'       => __( 'Parent preacher', 'sermon-manager' ),
					'parent_item_colon' => __( 'Parent preacher:', 'sermon-manager' ),
					'edit_item'         => __( 'Edit preacher', 'sermon-manager' ),
					'update_item'       => __( 'Update preacher', 'sermon-manager' ),
					'add_new_item'      => __( 'Add new preacher', 'sermon-manager' ),
					'new_item_name'     => __( 'New preacher name', 'sermon-manager' ),
					'not_found'         => __( 'No preachers found', 'sermon-manager' ),
				),
				'show_ui'      => true,
				'query_var'    => true,
				'capabilities' => array(
					'manage_terms' => 'manage_sermon_terms',
					'edit_terms'   => 'edit_sermon_terms',
					'delete_terms' => 'delete_sermon_terms',
					'assign_terms' => 'assign_sermon_terms',
				),
				'rewrite'      => $permalinks['preachers'],
			) ) );

		register_taxonomy( 'sermon_series',
			apply_filters( 'sm_taxonomy_objects_sermon_series', array( 'sermon' ) ),
			apply_filters( 'sm_taxonomy_args_sermon_series', array(
				'hierarchical' => false,
				'label'        => __( 'Series', 'sermon-manager' ),
				'labels'       => array(
					'name'              => __( 'Series', 'sermon-manager' ),
					'singular_name'     => __( 'Series', 'sermon-manager' ),
					'menu_name'         => _x( 'Series', 'Admin menu name', 'sermon-manager' ),
					'search_items'      => __( 'Search series', 'sermon-manager' ),
					'all_items'         => __( 'All series', 'sermon-manager' ),
					'parent_item'       => __( 'Parent series', 'sermon-manager' ),
					'parent_item_colon' => __( 'Parent series:', 'sermon-manager' ),
					'edit_item'         => __( 'Edit series', 'sermon-manager' ),
					'update_item'       => __( 'Update series', 'sermon-manager' ),
					'add_new_item'      => __( 'Add new series', 'sermon-manager' ),
					'new_item_name'     => __( 'New series name', 'sermon-manager' ),
					'not_found'         => __( 'No series found', 'sermon-manager' ),
				),
				'show_ui'      => true,
				'query_var'    => true,
				'capabilities' => array(
					'manage_terms' => 'manage_sermon_terms',
					'edit_terms'   => 'edit_sermon_terms',
					'delete_terms' => 'delete_sermon_terms',
					'assign_terms' => 'assign_sermon_terms',
				),
				'rewrite'      => $permalinks['sermon_series'],
			) ) );

		register_taxonomy( 'sermon_topics',
			apply_filters( 'sm_taxonomy_objects_sermon_topics', array( 'sermon' ) ),
			apply_filters( 'sm_taxonomy_args_sermon_topics', array(
				'hierarchical' => false,
				'label'        => __( 'Topics', 'sermon-manager' ),
				'labels'       => array(
					'name'              => __( 'Topics', 'sermon-manager' ),
					'singular_name'     => __( 'Topic', 'sermon-manager' ),
					'menu_name'         => _x( 'Topics', 'Admin menu name', 'sermon-manager' ),
					'search_items'      => __( 'Search topics', 'sermon-manager' ),
					'all_items'         => __( 'All topics', 'sermon-manager' ),
					'parent_item'       => __( 'Parent topic', 'sermon-manager' ),
					'parent_item_colon' => __( 'Parent topic:', 'sermon-manager' ),
					'edit_item'         => __( 'Edit topic', 'sermon-manager' ),
					'update_item'       => __( 'Update topic', 'sermon-manager' ),
					'add_new_item'      => __( 'Add new topic', 'sermon-manager' ),
					'new_item_name'     => __( 'New topic name', 'sermon-manager' ),
					'not_found'         => __( 'No topics found', 'sermon-manager' ),
				),
				'show_ui'      => true,
				'query_var'    => true,
				'capabilities' => array(
					'manage_terms' => 'manage_sermon_terms',
					'edit_terms'   => 'edit_sermon_terms',
					'delete_terms' => 'delete_sermon_terms',
					'assign_terms' => 'assign_sermon_terms',
				),
				'rewrite'      => $permalinks['sermon_topics'],
			) ) );

		register_taxonomy( 'bible_book',
			apply_filters( 'sm_taxonomy_objects_bible_book', array( 'sermon' ) ),
			apply_filters( 'sm_taxonomy_args_bible_book', array(
				'hierarchical' => false,
				'label'        => __( 'Books', 'sermon-manager' ),
				'labels'       => array(
					'name'              => __( 'Bible books', 'sermon-manager' ),
					'singular_name'     => __( 'Book', 'sermon-manager' ),
					'menu_name'         => _x( 'Books', 'Admin menu name', 'sermon-manager' ),
					'search_items'      => __( 'Search books', 'sermon-manager' ),
					'all_items'         => __( 'All books', 'sermon-manager' ),
					'parent_item'       => __( 'Parent book', 'sermon-manager' ),
					'parent_item_colon' => __( 'Parent book:', 'sermon-manager' ),
					'edit_item'         => __( 'Edit book', 'sermon-manager' ),
					'update_item'       => __( 'Update book', 'sermon-manager' ),
					'add_new_item'      => __( 'Add new book', 'sermon-manager' ),
					'new_item_name'     => __( 'New book name', 'sermon-manager' ),
					'not_found'         => __( 'No books found', 'sermon-manager' ),
				),
				'show_ui'      => true,
				'query_var'    => true,
				'capabilities' => array(
					'manage_terms' => 'manage_sermon_terms',
					'edit_terms'   => 'edit_sermon_terms',
					'delete_terms' => 'delete_sermon_terms',
					'assign_terms' => 'assign_sermon_terms',
				),
				'rewrite'      => $permalinks['bible_book'],
			) ) );

		register_taxonomy( 'service_type',
			apply_filters( 'sm_taxonomy_objects_service_type', array( 'sermon' ) ),
			apply_filters( 'sm_taxonomy_args_service_type', array(
				'hierarchical' => false,
				'label'        => __( 'Service Types', 'sermon-manager' ),
				'labels'       => array(
					'name'              => __( 'Service Types', 'sermon-manager' ),
					'singular_name'     => __( 'Type', 'sermon-manager' ),
					'menu_name'         => _x( 'Types', 'Admin menu name', 'sermon-manager' ),
					'search_items'      => __( 'Search types', 'sermon-manager' ),
					'all_items'         => __( 'All types', 'sermon-manager' ),
					'parent_item'       => __( 'Parent type', 'sermon-manager' ),
					'parent_item_colon' => __( 'Parent type:', 'sermon-manager' ),
					'edit_item'         => __( 'Edit type', 'sermon-manager' ),
					'update_item'       => __( 'Update type', 'sermon-manager' ),
					'add_new_item'      => __( 'Add new type', 'sermon-manager' ),
					'new_item_name'     => __( 'New type name', 'sermon-manager' ),
					'not_found'         => __( 'No types found', 'sermon-manager' ),
				),
				'show_ui'      => true,
				'query_var'    => true,
				'capabilities' => array(
					'manage_terms' => 'manage_sermon_terms',
					'edit_terms'   => 'edit_sermon_terms',
					'delete_terms' => 'delete_sermon_terms',
					'assign_terms' => 'assign_sermon_terms',
				),
				'rewrite'      => $permalinks['service_type'],
			) ) );

		do_action( 'sm_after_register_taxonomy' );
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {
		if ( ! is_blog_installed() || post_type_exists( 'sermon' ) ) {
			return;
		}

		do_action( 'sm_register_post_type' );

		$permalinks = sm_get_permalink_structure();

		register_post_type( 'sermon', apply_filters( 'sm_register_post_type_sermon', array(
			'labels'              => array(
				'name'                  => __( 'Sermons', 'sermon-manager' ),
				'singular_name'         => __( 'Sermon', 'sermon-manager' ),
				'all_items'             => __( 'All Sermons', 'sermon-manager' ),
				'menu_name'             => _x( 'Sermons', 'Admin menu name', 'sermon-manager' ),
				'add_new'               => __( 'Add New', 'sermon-manager' ),
				'add_new_item'          => __( 'Add new sermon', 'sermon-manager' ),
				'edit'                  => __( 'Edit', 'sermon-manager' ),
				'edit_item'             => __( 'Edit sermon', 'sermon-manager' ),
				'new_item'              => __( 'New sermon', 'sermon-manager' ),
				'view'                  => __( 'View sermon', 'sermon-manager' ),
				'view_item'             => __( 'View sermon', 'sermon-manager' ),
				'search_items'          => __( 'Search sermon', 'sermon-manager' ),
				'not_found'             => __( 'No sermons found', 'sermon-manager' ),
				'not_found_in_trash'    => __( 'No sermons found in trash', 'sermon-manager' ),
				'featured_image'        => __( 'Sermon image', 'sermon-manager' ),
				'set_featured_image'    => __( 'Set sermon image', 'sermon-manager' ),
				'remove_featured_image' => __( 'Remove sermon image', 'sermon-manager' ),
				'use_featured_image'    => __( 'Use as sermon image', 'sermon-manager' ),
				'insert_into_item'      => __( 'INSERT INTO sermon', 'sermon-manager' ),
				'uploaded_to_this_item' => __( 'Uploaded to this sermon', 'sermon-manager' ),
				'filter_items_list'     => __( 'Filter sermon', 'sermon-manager' ),
				'items_list_navigation' => __( 'Sermon navigation', 'sermon-manager' ),
				'items_list'            => __( 'Sermon list', 'sermon-manager' ),
			),
			'description'         => __( 'This is where you can add new sermons to your website.', 'sermon-manager' ),
			'public'              => true,
			'show_ui'             => true,
			'capability_type'     => 'sermon',
			'map_meta_cap'        => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_in_menu'        => current_user_can( 'manage_sermons' ) ? 'sermon-manager' : true,
			'hierarchical'        => false,
			'rewrite'             => $permalinks['sermon'],
			'query_var'           => true,
			'show_in_nav_menus'   => true,
			'show_in_rest'        => true,
			'has_archive'         => ( $sermon_page_id = sm_get_page_id( 'sermon' ) ) && get_post( $sermon_page_id ) ? urldecode( get_page_uri( $sermon_page_id ) ) : 'sermon',
			'supports'            => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				//'custom-fields',
				'publicize',
				'wpcom-markdown',
				'comments'
			)
		) ) );

		do_action( 'sm_after_register_post_type' );
	}

	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 * Add Sermon Support to Jetpack Omnisearch.
	 */
	public static function support_jetpack_omnisearch() {
		if ( class_exists( 'Jetpack_Omnisearch_Posts' ) ) {
			new Jetpack_Omnisearch_Posts( 'sermon' );
		}
	}

	/**
	 * Add sermon support for Jetpack related posts.
	 *
	 * @param  array $post_types
	 *
	 * @return array
	 */
	public static function rest_api_allowed_post_types( $post_types ) {
		$post_types[] = 'sermon';

		return $post_types;
	}
}

SM_Post_Types::init();