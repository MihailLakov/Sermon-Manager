<?php
/**
 * Functions for formatting data.
 */


/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var
 *
 * @return string|array
 * @since 3.0.0
 */
function sm_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'sm_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

/**
 * Sanitize a string destined to be a tooltip.
 *
 * @param string $var
 *
 * @return string
 * @since 3.0.0
 */
function sm_sanitize_tooltip( $var ) {
	return htmlspecialchars( wp_kses( html_entity_decode( $var ), array(
		'br'     => array(),
		'em'     => array(),
		'strong' => array(),
		'small'  => array(),
		'span'   => array(),
		'ul'     => array(),
		'li'     => array(),
		'ol'     => array(),
		'p'      => array(),
	) ) );
}

/**
 * Sermon Manager Date Format - Allows to change date format for everything Sermon Manager.
 *
 * @return string
 */
function sm_date_format() {
	return apply_filters( 'sm_date_format', get_option( 'date_format' ) );
}

/**
 * Sermon Manager Time Format - Allows to change time format for everything Sermon Manager.
 *
 * @return string
 */
function sm_time_format() {
	return apply_filters( 'sm_time_format', get_option( 'time_format' ) );
}