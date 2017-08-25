<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Sermon Class
 *
 * The Sermon Manager sermon class handles individual sermon data.
 */
class SM_Sermon extends SM_Data {

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'sermon';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'sermon';

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	protected $cache_group = 'sermons';

	/**
	 * Stores sermon data.
	 *
	 * @var array
	 */
	protected $data = array(
		'title'         => '',
		'slug'          => '',
		'date_created'  => null,
		'date_modified' => null,
		# ... TODO
	);

	/**
	 * Supported features
	 *
	 * @var array
	 */
	protected $supports = array();

	/**
	 * Get the sermon if ID is passed, otherwise the sermon is new and empty.
	 * This class should NOT be instantiated, but the sm_get_sermon() function
	 * should be used. It is possible, but the sm_get_sermon() is preferred.
	 *
	 * @param int|SM_Sermon|object $sermon Sermon to init.
	 */
	public function __construct( $sermon = 0 ) {
		if ( is_numeric( $sermon ) && $sermon > 0 ) {
			$this->set_id( $sermon );
		} elseif ( $sermon instanceof self ) {
			$this->set_id( absint( $sermon->get_id() ) );
		} elseif ( ! empty( $sermon->ID ) ) {
			$this->set_id( absint( $sermon->ID ) );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = SM_Data_Store::load( 'sermon' );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Get internal type. Should return string and *should be overridden* by child classes.
	 *
	 * @since 3.0.0
	 * @return string
	 */
	public function get_type() {
		return isset( $this->product_type ) ? $this->product_type : 'sermon';
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the sermon object.
	*/

	/**
	 * Get sermon name.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $context
	 *
	 * @return string
	 */
	public function get_title( $context = 'view' ) {
		return $this->get_prop( 'title', $context );
	}

	/**
	 * Get sermon slug.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $context
	 *
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->get_prop( 'slug', $context );
	}

	/**
	 * Get sermon created date.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $context
	 *
	 * @return SM_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get sermon modified date.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $context
	 *
	 * @return SM_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting sermon data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	*/

	/**
	 * Set sermon name.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name Sermon name.
	 */
	public function set_title( $name ) {
		$this->set_prop( 'title', $name );
	}

	/**
	 * Set sermon slug.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug Sermon slug.
	 */
	public function set_slug( $slug ) {
		$this->set_prop( 'slug', $slug );
	}

	/**
	 * Set sermon created date.
	 *
	 * @since 3.0.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or
	 *                                  offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set sermon modified date.
	 *
	 * @since 3.0.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or
	 *                                  offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/*
	|--------------------------------------------------------------------------
	| Other Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Save data (either create or update depending on if we are working on an existing sermon).
	 *
	 * @since 3.0.0
	 */
	public function save() {
		if ( $this->data_store ) {
			// Trigger action before saving to the DB. Use a pointer to adjust object props before save.
			do_action( 'sm_before_' . $this->object_type . '_object_save', $this, $this->data_store );

			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}

			return $this->get_id();
		}

		return null;
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	*/

	/**
	 * Check if a sermon supports a given feature.
	 *
	 * Sermon classes should override this to declare support (or lack of support) for a feature.
	 *
	 * @param string $feature string The name of a feature to test support for.
	 *
	 * @return bool True if the sermon supports the feature, false otherwise.
	 * @since 3.0.0
	 */
	public function supports( $feature ) {
		return apply_filters( 'sm_sermon_supports', in_array( $feature, $this->supports ) ? true : false, $feature, $this );
	}

	/**
	 * Returns whether or not the sermon post exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return false !== $this->get_status();
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Sermon permalink.
	 *
	 * @return string
	 */
	public function get_permalink() {
		return get_permalink( $this->get_id() );
	}

	/**
	 * Returns the main sermon image.
	 *
	 * @param string $size        (default: 'sermon_thumbnail')
	 * @param array  $attr
	 * @param bool   $placeholder True to return $placeholder if no image is found, or false to return an empty string.
	 *
	 * @return string
	 */
	public function get_image( $size = 'sermon_thumbnail', $attr = array(), $placeholder = true ) {
		if ( has_post_thumbnail( $this->get_id() ) ) {
			$image = get_the_post_thumbnail( $this->get_id(), $size, $attr );
		} elseif ( $placeholder ) {
			$image = sm_placeholder_img( $size );
		} else {
			$image = '';
		}

		return str_replace( array( 'https://', 'http://' ), '//', $image );
	}

	/**
	 * Returns a single sermon attribute as a string.
	 *
	 * @param  string $attribute to get.
	 *
	 * @return string
	 */
	public function get_attribute( $attribute ) {
		$attributes = $this->get_attributes();
		$attribute  = sanitize_title( $attribute );

		if ( isset( $attributes[ $attribute ] ) ) {
			$attribute_object = $attributes[ $attribute ];
		} elseif ( isset( $attributes[ 'sa_' . $attribute ] ) ) {
			$attribute_object = $attributes[ 'sa_' . $attribute ];
		} else {
			return '';
		}

		return $attribute_object->is_taxonomy() ? implode( ', ', sm_get_sermon_terms( $this->get_id(), $attribute_object->get_name(), array( 'fields' => 'names' ) ) ) : sm_implode_text_attributes( $attribute_object->get_options() );
	}
}
