<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sermon Factory Class
 *
 * The Sermon Manager sermon factory creating the right sermon object.
 */
class SM_Sermon_Factory {

	/**
	 * Get a sermon
	 *
	 * @param mixed $sermon_id (default: false)
	 *
	 * @return SM_Sermon|bool Sermon object or null if the sermon cannot be loaded.
	 */
	public function get_sermon( $sermon_id = false ) {
		if ( ! $sermon_id = $this->get_sermon_id( $sermon_id ) ) {
			return false;
		}

		try {
			return new SM_Sermon( $sermon_id );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get the sermon ID depending on what was passed.
	 *
	 * @param  mixed $sermon
	 *
	 * @return int|bool false on failure
	 * @since 3.0.0
	 */
	private function get_sermon_id( $sermon ) {
		if ( false === $sermon && isset( $GLOBALS['post'], $GLOBALS['post']->ID ) && 'sermom' === get_post_type( $GLOBALS['post']->ID ) ) {
			return $GLOBALS['post']->ID;
		} elseif ( is_numeric( $sermon ) ) {
			return $sermon;
		} elseif ( $sermon instanceof SM_Sermon ) {
			return $sermon->get_id();
		} elseif ( ! empty( $sermon->ID ) ) {
			return $sermon->ID;
		} else {
			return false;
		}
	}
}
