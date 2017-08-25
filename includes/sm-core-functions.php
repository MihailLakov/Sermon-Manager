<?php
/**
 * Core Functions
 *
 * General core functions available on both the front-end and admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include core functions
include_once 'sm-page-functions.php';

/**
 * Get an image size.
 *
 * Variable is filtered by sm_get_image_size_{image_size}.
 *
 * @param array|string $image_size
 *
 * @return array
 * @since 3.0.0
 */
function sm_get_image_size( $image_size ) {
	if ( is_array( $image_size ) ) {
		$width  = isset( $image_size[0] ) ? $image_size[0] : 300;
		$height = isset( $image_size[1] ) ? $image_size[1] : 200;
		$crop   = isset( $image_size[2] ) ? $image_size[2] : true;

		$size = array(
			'width'  => $width,
			'height' => $height,
			'crop'   => $crop,
		);

		$image_size = $width . '_' . $height;

	} elseif ( in_array( $image_size, array( 'sermon_small', 'sermon_medium', 'sermon_wide' ) ) ) {
		// reset variables
		$w = $h = $c = null;

		switch ( $image_size ) {
			case 'sermon_small':
				$w = 75;
				$h = 75;
				$c = true;

				break;
			case 'sermon_medium':
				$w = 300;
				$h = 200;
				$c = true;

				break;
			case 'sermon_wide':
				$w = 940;
				$h = 350;
				$c = true;

				break;
		}

		$size           = get_option( $image_size . '_image_size', array() );
		$size['width']  = isset( $size['width'] ) ? $size['width'] : $w;
		$size['height'] = isset( $size['height'] ) ? $size['height'] : $h;
		$size['crop']   = isset( $size['crop'] ) ? $size['crop'] : $c;

	} else {
		$size = array(
			'width'  => 300,
			'height' => 200,
			'crop'   => true,
		);
	}

	return apply_filters( 'sm_get_image_size_' . $image_size, $size );
}

/**
 * Get permalink settings for Sermon Manager independent of the user locale.
 *
 * @return array
 * @since 3.0.0
 */
function sm_get_permalink_structure() {
	if ( did_action( 'admin_init' ) ) {
		sm_switch_to_site_locale();
	}

	$permalinks = wp_parse_args( (array) get_option( 'sm_permalinks', array() ), array(
		'preachers'              => '',
		'sermon_series'          => '',
		'sermon_topics'          => '',
		'bible_book'             => '',
		'service_type'           => '',
		'sermon'                 => '',
		'use_verbose_page_rules' => false,
	) );

	// Ensure rewrite slugs are set.
	$permalinks['preachers']     = untrailingslashit( empty( $permalinks['preachers'] ) ? _x( 'preachers', 'slug', 'sermon-manager' ) : $permalinks['preachers'] );
	$permalinks['sermon_series'] = untrailingslashit( empty( $permalinks['sermon_series'] ) ? _x( 'sermon_series', 'slug', 'sermon-manager' ) : $permalinks['sermon_series'] );
	$permalinks['sermon_topics'] = untrailingslashit( empty( $permalinks['sermon_topics'] ) ? _x( 'sermon_topics', 'slug', 'sermon-manager' ) : $permalinks['sermon_topics'] );
	$permalinks['bible_book']    = untrailingslashit( empty( $permalinks['bible_book'] ) ? _x( 'bible_book', 'slug', 'sermon-manager' ) : $permalinks['bible_book'] );
	$permalinks['service_type']  = untrailingslashit( empty( $permalinks['service_type'] ) ? _x( 'service_type', 'slug', 'sermon-manager' ) : $permalinks['service_type'] );
	$permalinks['sermon']        = untrailingslashit( empty( $permalinks['sermon'] ) ? _x( 'sermon', 'slug', 'sermon-manager' ) : $permalinks['sermon'] );

	if ( did_action( 'admin_init' ) ) {
		sm_restore_locale();
	}

	return $permalinks;
}

/**
 * Switch Sermon Manager to site language.
 *
 * @since 3.0.0
 */
function sm_switch_to_site_locale() {
	if ( function_exists( 'switch_to_locale' ) ) {
		switch_to_locale( get_locale() );

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		// Init Sermon Manager locale.
		SM()->load_plugin_textdomain();
	}
}

/**
 * Switch Sermon Manager language to original.
 *
 * @since 3.0.0
 */
function sm_restore_locale() {
	if ( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		// Remove filter.
		remove_filter( 'plugin_locale', 'get_locale' );

		// Init Sermon Manager locale.
		SM()->load_plugin_textdomain();
	}
}

/**
 * Output any queued javascript code in the footer.
 *
 * @since 3.0.0
 */
function sm_print_js() {
	global $sm_queued_js;

	if ( ! empty( $sm_queued_js ) ) {
		// Sanitize.
		$sm_queued_js = wp_check_invalid_utf8( $sm_queued_js );
		$sm_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $sm_queued_js );
		$sm_queued_js = str_replace( "\r", '', $sm_queued_js );

		$js = "<!-- Sermon Manager JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $sm_queued_js });\n</script>\n";

		/**
		 * sm_queued_js filter.
		 *
		 * @param string $js JavaScript code.
		 */
		echo apply_filters( 'sm_queued_js', $js );

		unset( $sm_queued_js );
	}
}

/**
 * Get a shared logger instance.
 *
 * Use the sm_logging_class filter to change the logging class. You may provide one of the following:
 *     - a class name which will be instantiated as `new $class` with no arguments
 *     - an instance which will be used directly as the logger
 * In either case, the class or instance *must* implement SM_Logger_Interface.
 *
 * @see   SM_Logger_Interface
 *
 * @return SM_Logger
 * @since 3.0.0
 */
function sm_get_logger() {
	static $logger = null;
	if ( null === $logger ) {
		$class      = apply_filters( 'sm_logging_class', 'SM_Logger' );
		$implements = class_implements( $class );
		if ( is_array( $implements ) && in_array( 'SM_Logger_Interface', $implements ) ) {
			if ( is_object( $class ) ) {
				$logger = $class;
			} else {
				$logger = new $class;
			}
		} else {
			sm_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					__( 'The class <code>%s</code> provided by sm_logging_class filter must implement <code>SM_Logger_Interface</code>.', 'sermon-manager' ),
					esc_html( is_object( $class ) ? get_class( $class ) : $class )
				),
				'3.0.0'
			);
			$logger = new SM_Logger();
		}
	}

	return $logger;
}

/**
 * Sermon Manager Core Supported Themes.
 *
 * @return string[]
 * @since 3.0.0
 */
function sm_get_core_supported_themes() {
	return array( 'twentyseventeen', 'twentysixteen' );
}

/**
 * Display a Sermon Manager help tip.
 *
 * @param  string $tip        Help tip text
 * @param  bool   $allow_html Allow sanitized HTML if true or escape
 *
 * @return string
 * @since 3.0.0
 */
function sm_help_tip( $tip, $allow_html = false ) {
	if ( $allow_html ) {
		$tip = sm_sanitize_tooltip( $tip );
	} else {
		$tip = esc_attr( $tip );
	}

	return '<span class="sm-help-tip" data-tip="' . $tip . '"></span>';
}

/**
 * Queue some JavaScript code to be output in the footer.
 *
 * @param string $code
 */
function sm_enqueue_js( $code ) {
	global $sm_queued_js;

	if ( empty( $sm_queued_js ) ) {
		$sm_queued_js = '';
	}

	$sm_queued_js .= "\n" . $code . "\n";
}