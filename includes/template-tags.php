<?php
defined( 'ABSPATH' ) or die; // exit if accessed directly

/*
 * Template selection
 */
// Check plugin options to decide what to do
if ( \SermonManager::getOption( 'template' ) ) {
	add_filter( 'template_include', 'sermon_template_include' );
	add_filter( 'template_include', 'preacher_template_include' );
	add_filter( 'template_include', 'series_template_include' );
	add_filter( 'template_include', 'service_type_template_include' );
	add_filter( 'template_include', 'bible_book_template_include' );
	add_filter( 'template_include', 'sermon_topics_template_include' );
}
add_action( 'sermon_media', 'wpfc_sermon_media', 5 );
add_action( 'sermon_audio', 'wpfc_sermon_audio', 5 );
add_action( 'sermon_single', 'wpfc_sermon_single' );
add_action( 'sermon_excerpt', 'wpfc_sermon_excerpt' );
// Add sermon content
add_filter( 'the_content', 'add_wpfc_sermon_content' );

// Include template for displaying sermons
function sermon_template_include( $template ) {
	if ( get_query_var( 'post_type' ) == 'wpfc_sermon' ) {
		if ( is_archive() || is_search() ) :
			if ( file_exists( get_stylesheet_directory() . '/archive-wpfc_sermon.php' ) ) {
				return get_stylesheet_directory() . '/archive-wpfc_sermon.php';
			}

			return SERMON_MANAGER_PATH . 'views/archive-wpfc_sermon.php';
		else :
			if ( file_exists( get_stylesheet_directory() . '/single-wpfc_sermon.php' ) ) {
				return get_stylesheet_directory() . '/single-wpfc_sermon.php';
			}

			return SERMON_MANAGER_PATH . 'views/single-wpfc_sermon.php';
		endif;
	}

	return $template;
}

// Include template for displaying sermon topics
function sermon_topics_template_include( $template ) {
	if ( get_query_var( 'taxonomy' ) == 'wpfc_sermon_topics' ) {
		if ( file_exists( get_stylesheet_directory() . '/taxonomy-wpfc_sermon_topics.php' ) ) {
			return get_stylesheet_directory() . '/taxonomy-wpfc_sermon_topics.php';
		}

		return SERMON_MANAGER_PATH . 'views/taxonomy-wpfc_sermon_topics.php';
	}

	return $template;
}

// Include template for displaying sermons by Preacher
function preacher_template_include( $template ) {
	if ( get_query_var( 'taxonomy' ) == 'wpfc_preacher' ) {
		if ( file_exists( get_stylesheet_directory() . '/taxonomy-wpfc_preacher.php' ) ) {
			return get_stylesheet_directory() . '/taxonomy-wpfc_preacher.php';
		}

		return SERMON_MANAGER_PATH . 'views/taxonomy-wpfc_preacher.php';
	}

	return $template;
}

// Include template for displaying sermon series
function series_template_include( $template ) {
	if ( get_query_var( 'taxonomy' ) == 'wpfc_sermon_series' ) {
		if ( file_exists( get_stylesheet_directory() . '/taxonomy-wpfc_sermon_series.php' ) ) {
			return get_stylesheet_directory() . '/taxonomy-wpfc_sermon_series.php';
		}

		return SERMON_MANAGER_PATH . 'views/taxonomy-wpfc_sermon_series.php';
	}

	return $template;
}

// Include template for displaying service types
function service_type_template_include( $template ) {
	if ( get_query_var( 'taxonomy' ) == 'wpfc_service_type' ) {
		if ( file_exists( get_stylesheet_directory() . '/taxonomy-wpfc_service_type.php' ) ) {
			return get_stylesheet_directory() . '/taxonomy-wpfc_service_type.php';
		}

		return SERMON_MANAGER_PATH . 'views/taxonomy-wpfc_service_type.php';
	}

	return $template;
}

// Include template for displaying sermons by book
function bible_book_template_include( $template ) {
	if ( get_query_var( 'taxonomy' ) == 'wpfc_bible_book' ) {
		if ( file_exists( get_stylesheet_directory() . '/taxonomy-wpfc_bible_book.php' ) ) {
			return get_stylesheet_directory() . '/taxonomy-wpfc_bible_book.php';
		}

		return SERMON_MANAGER_PATH . 'views/taxonomy-wpfc_bible_book.php';
	}

	return $template;
}

// render archive entry; depreciated - use render_wpfc_sermon_excerpt() instead
function render_wpfc_sermon_archive() {
	global $post; ?>
    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <h2 class="sermon-title"><a href="<?php the_permalink(); ?>"
                                    title="<?php printf( esc_attr__( 'Permalink to %s', 'sermon-manager' ), the_title_attribute( 'echo=0' ) ); ?>"
                                    rel="bookmark"><?php the_title(); ?></a></h2>
        <div class="wpfc_sermon_image">
			<?php render_sermon_image( 'thumbnail' ); ?>
        </div>
        <div class="wpfc_sermon_meta cf">
            <p>
				<?php
				wpfc_sermon_date( get_option( 'date_format' ), '<span class="sermon_date">', '</span> ' );
				echo the_terms( $post->ID, 'wpfc_service_type', ' <span class="service_type">(', ' ', ')</span>' );
				?></p>
            <p><?php

				wpfc_sermon_meta( 'bible_passage', '<span class="bible_passage">' . __( 'Bible Text: ', 'sermon-manager' ), '</span> | ' );
				echo the_terms( $post->ID, 'wpfc_preacher', '<span class="preacher_name">', ' ', '</span>' );
				echo the_terms( $post->ID, 'wpfc_sermon_series', '<p><span class="sermon_series">' . __( 'Series: ', 'sermon-manager' ), ' ', '</span></p>' );
				?>
            </p>
        </div>
    </div>

<?php }

/**
 * Render sermon sorting
 *
 * @param array $args Display options. See the 'sermon_sort_fields' shortcode for array items
 *
 * @see   WPFC_Shortcodes->displaySermonSorting()
 *
 * @return string the HTML
 *
 * @since 2.5.0 added $args
 */
function render_wpfc_sorting( $args = array() ) {
	// reset values
	$hidden = array();

	// handle current page. We don't need "page" var in URL
	if ( is_archive() && get_post_type() === 'wpfc_sermon' ) {
		$action = get_site_url() . '/' . generate_wpfc_slug()['slug'];
	} else {
		$action = get_site_url();
	}

	// we need it for taxonomy name conversion function
	$shortcodes = new WPFC_Shortcodes();

	// add other filtering fields
	foreach ( array( 'wpfc_preacher', 'wpfc_sermon_series', 'wpfc_sermon_topics', 'wpfc_bible_book' ) as $filter ) {
		// Force shortcode defined argument if set
		if ( ! empty( $args[ $shortcodes->convertTaxonomyName( $filter, false ) ] ) &&
		     $value = $args[ $shortcodes->convertTaxonomyName( $filter, false ) ] ) {
			$hidden[ $filter ] = "<input type='hidden' name='$filter' value='$value'>" . PHP_EOL;

			continue;
		}

		if ( get_query_var( $filter ) !== '' && $value = get_query_var( $filter ) ) {
			$hidden[ $filter ] = "<input type='hidden' name='$filter' value='$value'>" . PHP_EOL;
		}
	}

	// Filters HTML fields data
	$filters = array(
		array(
			'className' => 'sortPreacher',
			'taxonomy'  => 'wpfc_preacher',
			'title'     => 'Sort by ' . \SermonManager::getOption( 'preacher_label' ) ?: 'Preacher',
		),
		array(
			'className' => 'sortSeries',
			'taxonomy'  => 'wpfc_sermon_series',
			'title'     => 'Sort by Series'
		),
		array(
			'className' => 'sortTopics',
			'taxonomy'  => 'wpfc_sermon_topics',
			'title'     => 'Sort by Topic'
		),
		array(
			'className' => 'sortBooks',
			'taxonomy'  => 'wpfc_bible_book',
			'title'     => 'Sort by Book'
		),
	);

	ob_start(); ?>
    <div id="wpfc_sermon_sorting">
		<?php foreach ( $filters as $filter ): ?>
			<?php if ( ! empty( $hidden ) && ! empty( $hidden[ $filter ] ) ) {
				unset( $hidden[ $filter ] );
			} ?>
			<?php if ( ( ! empty( $args[ $filter['taxonomy'] ] ) && $args['visibility'] !== 'none' ) || empty( $args[ $filter['taxonomy'] ] ) ): ?>
                <span class="<?php echo $filter['className'] ?>">
                    <form action="<?php echo $action; ?>">
                        <select name="<?php echo $filter['taxonomy'] ?>"
                                title="<?php echo $filter['title'] ?>"
                                id="<?php echo $filter['taxonomy'] ?>"
                                onchange="return this.form.submit()"
	                        <?php echo ! empty( $args[ $filter['taxonomy'] ] ) && $args['visibility'] === 'disable' ? 'disabled' : '' ?>>
                            <option value=""><?php echo $filter['title'] ?></option>
	                        <?php echo wpfc_get_term_dropdown( $filter['taxonomy'], ! empty( $args[ $filter['taxonomy'] ] ) ? $args[ $filter['taxonomy'] ] : '' ); ?>
                        </select>
                        <noscript>
                            <div><input type="submit" value="Submit"/></div>
                        </noscript>
	                    <?php echo implode( $hidden ); ?>
                    </form>
                </span>
			<?php endif; ?>
		<?php endforeach; ?>
    </div>
	<?php
	return ob_get_clean();
}

// echo any sermon meta
function wpfc_sermon_meta( $args, $before = '', $after = '' ) {
	global $post;
	$data = get_post_meta( $post->ID, $args, true );
	if ( $data != '' ) {
		echo $before . $data . $after;
	}

	echo '';
}

// return any sermon meta
function get_wpfc_sermon_meta( $args ) {
	global $post;
	$data = get_post_meta( $post->ID, $args, true );
	if ( $data != '' ) {
		return $data;
	}

	return null;
}

function process_wysiwyg_output( $meta_key, $post_id = 0 ) {
	global $wp_embed;

	$post_id = $post_id ? $post_id : get_the_id();

	$content = get_post_meta( $post_id, $meta_key, true );
	$content = $wp_embed->autoembed( $content );
	$content = $wp_embed->run_shortcode( $content );
	$content = wpautop( $content );
	$content = do_shortcode( $content );

	return $content;
}

// render sermon description
function wpfc_sermon_description( $before = '', $after = '' ) {
	global $post;
	$data = process_wysiwyg_output( 'sermon_description', get_the_ID() );
	if ( $data != '' ) {
		echo $before . wpautop( $data ) . $after;
	}
}

// render any sermon date
function wpfc_sermon_date( $args, $before = '', $after = '' ) {
	global $post;

	echo $before . date_i18n( $args, get_the_date( 'U', $post->ID ) ) . $after;
}

// Change the_author to the preacher on frontend display
function wpfc_sermon_author_filter() {
	global $post;
	$preacher = the_terms( $post->ID, 'wpfc_preacher', '', ', ', ' ' );

	return $preacher;
}

//add_filter('the_author', 'wpfc_sermon_author_filter');

// render sermon image - loops through featured image, series image, speaker image, none
function render_sermon_image( $size ) {
	//$size = any defined image size in WordPress
	if ( has_post_thumbnail() ) :
		the_post_thumbnail( $size );
    elseif ( apply_filters( 'sermon-images-list-the-terms', '', array( 'taxonomy' => 'wpfc_sermon_series', ) ) ) :
		// get series image
		print apply_filters( 'sermon-images-list-the-terms', '', array(
			'image_size'   => $size,
			'taxonomy'     => 'wpfc_sermon_series',
			'after'        => '',
			'after_image'  => '',
			'before'       => '',
			'before_image' => ''
		) );
    elseif ( ! has_post_thumbnail() && ! apply_filters( 'sermon-images-list-the-terms', '', array( 'taxonomy' => 'wpfc_sermon_series', ) ) ) :
		// get speaker image
		print apply_filters( 'sermon-images-list-the-terms', '', array(
			'image_size'   => $size,
			'taxonomy'     => 'wpfc_preacher',
			'after'        => '',
			'after_image'  => '',
			'before'       => '',
			'before_image' => ''
		) );
	endif;
}

/*
 * render media files section
 * for template files use
 * do_action ('sermon_media');
 *
 */
function wpfc_sermon_media() {
	$html = '';

	if ( get_wpfc_sermon_meta( 'sermon_video_link' ) ) {
		$html .= '<div class="wpfc_sermon-video-link cf">';
		$html .= process_wysiwyg_output( 'sermon_video_link', get_the_ID() );
		$html .= '</div>';
	} else {
		$html .= '<div class="wpfc_sermon-video cf">';
		$html .= do_shortcode( get_wpfc_sermon_meta( 'sermon_video' ) );
		$html .= '</div>';
	}

	if ( get_wpfc_sermon_meta( 'sermon_audio' ) ) {
		$html .= '<div class="wpfc_sermon-audio cf">';
		$html .= wpfc_render_audio( get_wpfc_sermon_meta( 'sermon_audio' ) );
		$html .= '</div>';
	}

	return $html;
}

/**
 * Renders the audio player
 *
 * @param string $url The URL of the audio file
 *
 * @return string Audio player HTML
 */
function wpfc_render_audio( $url = '' ) {
	if ( ! is_string( $url ) || trim( $url ) === '' ) {
		return '';
	}

	if ( \SermonManager::getOption( 'use_old_player' ) ) {
		$attr = array(
			'src'     => $url,
			'preload' => 'none'
		);

		$output = wp_audio_shortcode( $attr );
	} else {
		$output = '<audio controls preload="metadata" class="wpfc-sermon-player">';
		$output .= '<source src="' . $url . '">';
		$output .= '</audio>';
	}

	return $output;
}

// legacy function
function wpfc_sermon_files() {
	do_action( 'sermon_media' );
}

// just get the sermon audio
function wpfc_sermon_audio() {
	$html = '';
	$html .= '<div class="wpfc_sermon-audio cf">';
	$html .= wpfc_render_audio( get_wpfc_sermon_meta( 'sermon_audio' ) );
	$html .= '</div>';

	return $html;
}

// render additional files
function wpfc_sermon_attachments() {
	global $post;
	$html = '<div id="wpfc-attachments" class="cf">';
	$html .= '<p><strong>' . __( 'Download Files', 'sermon-manager' ) . '</strong>';
	if ( get_wpfc_sermon_meta( 'sermon_audio' ) ) {
		$html .= '<a href="' . get_wpfc_sermon_meta( 'sermon_audio' ) . '" class="sermon-attachments" download><span class="dashicons dashicons-media-audio"></span>' . __( 'MP3', 'sermon-manager' ) . '</a>';
	}
	if ( get_wpfc_sermon_meta( 'sermon_notes' ) ) {
		$html .= '<a href="' . get_wpfc_sermon_meta( 'sermon_notes' ) . '" class="sermon-attachments"><span class="dashicons dashicons-media-document"></span>' . __( 'Notes', 'sermon-manager' ) . '</a>';
	}
	if ( get_wpfc_sermon_meta( 'sermon_bulletin' ) ) {
		$html .= '<a href="' . get_wpfc_sermon_meta( 'sermon_bulletin' ) . '" class="sermon-attachments"><span class="dashicons dashicons-media-document"></span>' . __( 'Bulletin', 'sermon-manager' ) . '</a>';
	}
	$html .= '</p>';
	$html .= '</div>';

	return $html;
}

// legacy function
function render_wpfc_sermon_single() {
	do_action( 'sermon_single' );
}

// single sermon action
function wpfc_sermon_single() {
	global $post; ?>
    <div class="wpfc_sermon_wrap cf">
        <div class="wpfc_sermon_image">
			<?php render_sermon_image( 'sermon_small' ); ?>
        </div>
        <div class="wpfc_sermon_meta cf">
            <p>
				<?php
				wpfc_sermon_date( get_option( 'date_format' ), '<span class="sermon_date">', '</span> ' );
				the_terms( $post->ID, 'wpfc_service_type', ' <span class="service_type">(', ' ', ')</span>' );
				?></p>
            <p><?php
				wpfc_sermon_meta( 'bible_passage', '<span class="bible_passage">' . __( 'Bible Text: ', 'sermon-manager' ), '</span> | ' );
				the_terms( $post->ID, 'wpfc_preacher', '<span class="preacher_name">', ', ', '</span>' );
				the_terms( $post->ID, 'wpfc_sermon_series', '<p><span class="sermon_series">' . __( 'Series: ', 'sermon-manager' ), ' ', '</span></p>' );
				?>
            </p>
        </div>
    </div>
    <div class="wpfc_sermon cf">

		<?php echo wpfc_sermon_media(); ?>

		<?php wpfc_sermon_description(); ?>

		<?php echo wpfc_sermon_attachments(); ?>

		<?php echo the_terms( $post->ID, 'wpfc_sermon_topics', '<p class="sermon_topics">' . __( 'Sermon Topics: ', 'sermon-manager' ), ',', '', '</p>' ); ?>

    </div>
	<?php
}

// render single sermon entry
function render_wpfc_sermon_excerpt() {
	do_action( 'sermon_excerpt' );
}

function wpfc_sermon_excerpt() {
	global $post; ?>
    <div class="wpfc_sermon_wrap cf">
        <div class="wpfc_sermon_image">
			<?php render_sermon_image( 'sermon_small' ); ?>
        </div>
        <div class="wpfc_sermon_meta cf">
            <p>
				<?php
				wpfc_sermon_date( get_option( 'date_format' ), '<span class="sermon_date">', '</span> ' );
				echo the_terms( $post->ID, 'wpfc_service_type', ' <span class="service_type">(', ' ', ')</span>' );
				?></p>
            <p><?php
				wpfc_sermon_meta( 'bible_passage', '<span class="bible_passage">' . __( 'Bible Text: ', 'sermon-manager' ), '</span> | ' );
				echo the_terms( $post->ID, 'wpfc_preacher', '<span class="preacher_name">', ', ', '</span>' );
				echo the_terms( $post->ID, 'wpfc_sermon_series', '<p><span class="sermon_series">' . __( 'Series: ', 'sermon-manager' ), ' ', '</span></p>' );
				?>
            </p>
        </div>
		<?php if ( \SermonManager::getOption( 'archive_player' ) ): ?>
            <div class="wpfc_sermon cf">
				<?php echo wpfc_sermon_media(); ?>
            </div>
		<?php endif; ?>
    </div>
	<?php
}

function add_wpfc_sermon_content( $content ) {
	if ( 'wpfc_sermon' == get_post_type() && in_the_loop() == true ) {
		if ( ! is_feed() && ( is_archive() || is_search() ) ) {
			$new_content = render_wpfc_sermon_excerpt();
		} elseif ( is_singular() && is_main_query() ) {
			$new_content = wpfc_sermon_single();
		}
		$content = $new_content;
	}

	return $content;
}

//Podcast Feed URL
function wpfc_podcast_url( $feed_type = false ) {
	if ( $feed_type == false ) { //return URL to feed page
		return home_url() . '/feed/podcast';
	} else { //return URL to itpc itunes-loaded feed page
		$itunes_url = str_replace( "http", "itpc", home_url() );

		return $itunes_url . '/feed/podcast';
	}
}

/**
 * Display series info on an individual sermon
 */
function wpfc_footer_series() {
	global $post;
	$terms = get_the_terms( $post->ID, 'wpfc_sermon_series' );
	if ( $terms ) {
		foreach ( $terms as $term ) {
			if ( $term->description ) {
				echo '<div class="single_sermon_info_box series clearfix">';
				echo '<div class="sermon-footer-description clearfix">';
				echo '<h3 class="single-preacher-name"><a href="' . get_term_link( $term->slug, 'wpfc_sermon_series' ) . '">' . $term->name . '</a></h3>';
				/* Image */
				print apply_filters( 'sermon-images-list-the-terms', '', array(
					'attr'         => array(
						'class' => 'alignleft',
					),
					'image_size'   => 'thumbnail',
					'taxonomy'     => 'wpfc_sermon_series',
					'after'        => '</div>',
					'after_image'  => '',
					'before'       => '<div class="sermon-footer-image">',
					'before_image' => ''
				) );
				/* Description */
				echo $term->description . '</div>';
				echo '</div>';
			}
		}
	}
}

/**
 * Display preacher info on an individual sermon
 */
function wpfc_footer_preacher() {
	global $post;
	$terms = get_the_terms( $post->ID, 'wpfc_preacher' );
	if ( $terms ) {
		foreach ( $terms as $term ) {
			if ( $term->description ) {
				echo '<div class="single_sermon_info_box preacher clearfix">';
				echo '<div class="sermon-footer-description clearfix">';
				echo '<h3 class="single-preacher-name"><a href="' . get_term_link( $term->slug, 'wpfc_preacher' ) . '">' . $term->name . '</a></h3>';
				/* Image */
				print apply_filters( 'sermon-images-list-the-terms', '', array(
					'attr'         => array(
						'class' => 'alignleft',
					),
					'image_size'   => 'thumbnail',
					'taxonomy'     => 'wpfc_preacher',
					'after'        => '</div>',
					'after_image'  => '',
					'before'       => '<div class="sermon-footer-image">',
					'before_image' => ''
				) );
				/* Description */
				echo $term->description . '</div>';
				echo '</div>';
			}
		}
	}
}
