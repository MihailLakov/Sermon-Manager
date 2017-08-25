<?php
/**
 * Admin Stats
 *
 * Functions used for displaying analytics and stats in admin area
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SM_Admin_Stats', false ) ) {

	/**
	 * SM_Admin_Stats Class.
	 */
	class SM_Admin_Stats {

		/**
		 * Handles output of the stats page in admin.
		 */
		public static function output() {
			$stats       = self::get_stats();
			$first_tab   = array_keys( $stats );
			$current_tab = ! empty( $_GET['tab'] ) ? sanitize_title( $_GET['tab'] ) : $first_tab[0];

			#include_once 'stats/class-sm-admin-stats.php';
			include_once 'views/html-admin-page-stats.php';
		}

		/**
		 * Returns the definitions for the statistics to show in admin.
		 *
		 * @return array
		 */
		public static function get_stats() {
			return array();
		}
	}
}