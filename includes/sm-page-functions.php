<?php
/**
 * Page Functions
 *
 * Functions related to pages and menus.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Retrieve page ids. returns -1 if no page is found.
 *
 * @param string $page
 *
 * @return int
 */
function sm_get_page_id( $page ) {
	$page = apply_filters( 'sm_get_' . $page . '_page_id', get_option( 'sm_' . $page . '_page_id' ) );

	return $page ? absint( $page ) : - 1;
}