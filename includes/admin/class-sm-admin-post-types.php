<?php
/**
 * Post Types Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SM_Admin_Post_Types', false ) ) {

	/**
	 * SM_Admin_Post_Types Class.
	 *
	 * Handles the edit posts views and some functionality on the edit post screen for Sermon Manager post types.
	 */
	class SM_Admin_Post_Types {

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
			add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );

			// WP List table columns. Defined here so they are always available for events such as inline editing.
			add_filter( 'manage_sermon_posts_columns', array( $this, 'sermon_columns' ) );
			add_action( 'manage_sermon_posts_custom_column', array( $this, 'render_sermon_columns' ), 2 );
			add_filter( 'manage_edit-sermon_sortable_columns', array( $this, 'sermon_sortable_columns' ) );

			add_filter( 'list_table_primary_column', array( $this, 'list_table_primary_column' ), 10, 2 );
			add_filter( 'post_row_actions', array( $this, 'row_actions' ), 100, 2 );

			// Views
			add_filter( 'views_edit-sermon', array( $this, 'sermon_views' ) );

			// Bulk / quick edit
			add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit' ), 10, 2 );
			add_action( 'quick_edit_custom_box', array( $this, 'quick_edit' ), 10, 2 );
			add_action( 'save_post', array( $this, 'bulk_and_quick_edit_hook' ), 10, 2 );
			add_action( 'sm_sermon_bulk_and_quick_edit', array( $this, 'bulk_and_quick_edit_save_post' ), 10, 2 );

			// Filters
			add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
			add_filter( 'request', array( $this, 'request_query' ) );
			add_filter( 'parse_query', array( $this, 'sermon_filters_query' ) );

			// Edit post screens
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );

			include_once 'class-sm-admin-meta-boxes.php';

			// Show blank state
			add_action( 'manage_posts_extra_tablenav', array( $this, 'maybe_render_blank_state' ) );
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @param  array $messages
		 *
		 * @return array
		 */
		public function post_updated_messages( $messages ) {
			global $post, $post_ID;

			$messages['sermon'] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf( __( 'Sermon updated. <a href="%s">View Sermon</a>', 'sermon-manager' ), esc_url( get_permalink( $post_ID ) ) ),
				2  => __( 'Custom field updated.', 'sermon-manager' ),
				3  => __( 'Custom field deleted.', 'sermon-manager' ),
				4  => __( 'Sermon updated.', 'sermon-manager' ),
				/* translators: %s: revision title */
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Sermon restored to revision from %s', 'sermon-manager' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				/* translators: %s: sermon url */
				6  => sprintf( __( 'Sermon published. <a href="%s">View Sermon</a>', 'sermon-manager' ), esc_url( get_permalink( $post_ID ) ) ),
				7  => __( 'Sermon saved.', 'sermon-manager' ),
				/* translators: %s: sermon url */
				8  => sprintf( __( 'Sermon submitted. <a target="_blank" href="%s">Preview sermon</a>', 'sermon-manager' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
				/* translators: 1: date 2: sermon url */
				9  => sprintf( __( 'Sermon scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview sermon</a>', 'sermon-manager' ),
					date_i18n( __( 'M j, Y @ G:i', 'sermon-manager' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
				/* translators: %s: sermon url */
				10 => sprintf( __( 'Sermon draft updated. <a target="_blank" href="%s">Preview sermon</a>', 'sermon-manager' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			);

			return $messages;
		}

		/**
		 * Specify custom bulk actions messages for different post types.
		 *
		 * @param  array $bulk_messages
		 * @param  array $bulk_counts
		 *
		 * @return array
		 */
		public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {

			$bulk_messages['sermon'] = array(
				/* translators: %s: sermon count */
				'updated'   => _n( '%s sermon updated.', '%s sermons updated.', $bulk_counts['updated'], 'sermon-manager' ),
				/* translators: %s: sermon count */
				'locked'    => _n( '%s sermon not updated, somebody is editing it.', '%s sermons not updated, somebody is editing them.', $bulk_counts['locked'], 'sermon-manager' ),
				/* translators: %s: sermon count */
				'deleted'   => _n( '%s sermon permanently deleted.', '%s sermons permanently deleted.', $bulk_counts['deleted'], 'sermon-manager' ),
				/* translators: %s: sermon count */
				'trashed'   => _n( '%s sermon moved to the Trash.', '%s sermons moved to the Trash.', $bulk_counts['trashed'], 'sermon-manager' ),
				/* translators: %s: sermon count */
				'untrashed' => _n( '%s sermon restored from the Trash.', '%s sermons restored from the Trash.', $bulk_counts['untrashed'], 'sermon-manager' ),
			);

			return $bulk_messages;
		}

		/**
		 * Define custom columns for sermons.
		 *
		 * @param  array $existing_columns
		 *
		 * @return array
		 */
		public function sermon_columns( $existing_columns ) {
			if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
				$existing_columns = array();
			}

			$columns           = array();
			$columns['cb']     = '<input type="checkbox" />';
			$columns['thumb']  = '<span class="sm-image tips" data-tip="' . esc_attr__( 'Image', 'sermon-manager' ) . '">' . __( 'Image', 'sermon-manager' ) . '</span>';
			$columns['book']   = __( 'Bible book', 'sermon-manager' );
			$columns['series'] = __( 'Series', 'sermon-manager' );
			$columns['date']   = __( 'Date', 'ermon-manage' );

			return array_merge( $columns, $existing_columns );

		}

		/**
		 * Ouput custom columns for sermons.
		 *
		 * @param string $column
		 */
		public function render_sermon_columns( $column ) {
			global $post, $the_sermon;

			if ( empty( $the_sermon ) || $the_sermon->get_id() != $post->ID ) {
				$the_sermon = SM()->sermon_factory->get_sermon( $post );
			}

			// Only continue if we have a sermon.
			if ( empty( $the_sermon ) ) {
				return;
			}

			switch ( $column ) {
				case 'thumb' :
					echo '<a href="' . get_edit_post_link( $post->ID ) . '">' . $the_sermon->get_image( 'thumbnail' ) . '</a>';
					break;
				case 'name' :
					$edit_link = get_edit_post_link( $post->ID );
					$title     = _draft_or_post_title();

					echo '<strong><a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';

					_post_states( $post );

					echo '</strong>';
					break;
				default :
					break;
			}
		}

		/**
		 * Make columns sortable - https://gist.github.com/906872.
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		public function sermon_sortable_columns( $columns ) {
			$custom = array(
				'title' => 'title',
			);

			return wp_parse_args( $custom, $columns );
		}

		/**
		 * Set list table primary column
		 * Support for WordPress 4.3.
		 *
		 * @param  string $default
		 * @param  string $screen_id
		 *
		 * @return string
		 */
		public function list_table_primary_column( $default, $screen_id ) {

			if ( 'edit-sermon' === $screen_id ) {
				return 'title';
			}

			return $default;
		}

		/**
		 * Set row actions for sermons and orders.
		 *
		 * @param  array   $actions
		 * @param  WP_Post $post
		 *
		 * @return array
		 */
		public function row_actions( $actions, $post ) {
			if ( 'sermon' === $post->post_type ) {
				return array_merge( array( 'id' => 'ID: ' . $post->ID ), $actions );
			}

			return $actions;
		}

		/**
		 * Change views on the edit sermon screen.
		 *
		 * @param  array $views
		 *
		 * @return array
		 */
		public function sermon_views( $views ) {
			global $wp_query;

			// Add sorting link.
			if ( current_user_can( 'edit_others_pages' ) ) {
				$class            = ( isset( $wp_query->query['orderby'] ) && 'date' === $wp_query->query['orderby'] ) ? 'current' : '';
				$query_string     = remove_query_arg( array( 'orderby', 'order' ) );
				$query_string     = add_query_arg( 'orderby', urlencode( 'date' ), $query_string );
				$query_string     = add_query_arg( 'order', urlencode( 'DESC' ), $query_string );
				$views['byorder'] = '<a href="' . esc_url( $query_string ) . '" class="' . esc_attr( $class ) . '">' . __( 'Sorting', 'sermon-manager' ) . '</a>';
			}

			return $views;
		}

		/**
		 * Custom bulk edit - form.
		 *
		 * @param mixed $column_name
		 * @param mixed $post_type
		 */
		public function bulk_edit( $column_name, $post_type ) {

			if ( 'sermon' !== $post_type ) {
				return;
			}

			include( SM()->plugin_path() . '/includes/admin/views/html-bulk-edit-sermon.php' );
		}

		/**
		 * Custom quick edit - form.
		 *
		 * @param mixed $column_name
		 * @param mixed $post_type
		 */
		public function quick_edit( $column_name, $post_type ) {

			if ( 'sermon' !== $post_type ) {
				return;
			}

			include( SM()->plugin_path() . '/includes/admin/views/html-quick-edit-sermon.php' );
		}

		/**
		 * Offers a way to hook into save post without causing an infinite loop
		 * when quick/bulk saving product info.
		 *
		 * @since 3.0.0
		 *
		 * @param int    $post_id
		 * @param object $post
		 */
		public function bulk_and_quick_edit_hook( $post_id, $post ) {
			remove_action( 'save_post', array( $this, 'bulk_and_quick_edit_hook' ) );
			do_action( 'sm_sermon_bulk_and_quick_edit', $post_id, $post );
			add_action( 'save_post', array( $this, 'bulk_and_quick_edit_hook' ), 10, 2 );
		}

		/**
		 * Quick and bulk edit saving.
		 *
		 * @param int     $post_id
		 * @param WP_Post $post
		 *
		 * @return int
		 */
		public function bulk_and_quick_edit_save_post( $post_id, $post ) {
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}

			// Don't save revisions and autosaves
			if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
				return $post_id;
			}

			// Check post type is sermon
			if ( 'sermon' !== $post->post_type ) {
				return $post_id;
			}

			// Check user permission
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}

			// Check nonces
			if ( ! isset( $_REQUEST['sm_quick_edit_nonce'] ) && ! isset( $_REQUEST['sm_bulk_edit_nonce'] ) ) {
				return $post_id;
			}
			if ( isset( $_REQUEST['sm_quick_edit_nonce'] ) && ! wp_verify_nonce( $_REQUEST['sm_quick_edit_nonce'], 'sm_quick_edit_nonce' ) ) {
				return $post_id;
			}
			if ( isset( $_REQUEST['sm_bulk_edit_nonce'] ) && ! wp_verify_nonce( $_REQUEST['sm_bulk_edit_nonce'], 'sm_bulk_edit_nonce' ) ) {
				return $post_id;
			}

			// Get the sermon and save
			$sermon = SM()->sermon_factory->get_sermon( $post );

			if ( ! empty( $_REQUEST['sm_quick_edit'] ) ) {
				$this->quick_edit_save( $post_id, $sermon );
			} else {
				$this->bulk_edit_save( $post_id, $sermon );
			}

			return $post_id;
		}

		/**
		 * Quick edit.
		 *
		 * @param integer   $post_id
		 * @param SM_Sermon $sermon
		 */
		private function quick_edit_save( $post_id, $sermon ) {
			do_action( 'sm_sermon_quick_edit_save', $sermon );
		}

		/**
		 * Bulk edit.
		 *
		 * @param integer   $post_id
		 * @param SM_Sermon $sermon
		 */
		public function bulk_edit_save( $post_id, $sermon ) {
			do_action( 'sm_sermon_bulk_edit_save', $sermon );
		}

		/**
		 * Filters for post types.
		 */
		public function restrict_manage_posts() {
			global $typenow;

			if ( 'sermon' == $typenow ) {
				$this->sermon_filters();
			}
		}

		/**
		 * Show a category filter box.
		 */
		public function sermon_filters() {
			global $wp_query;

			echo apply_filters( 'sm_sermon_filters', '' );
		}

		/**
		 * Filters and sorting handler.
		 *
		 * @param  array $vars
		 *
		 * @return array
		 */
		public function request_query( $vars ) {
			global $typenow, $wp_query, $wp_post_statuses;

			if ( 'sermon' === $typenow ) {
				// Sorting
				if ( isset( $vars['orderby'] ) ) {
					if ( 'foo' == $vars['orderby'] ) {
						$vars = array_merge( $vars, array(
							'meta_key' => 'bar',
							'orderby'  => 'meta_value',
						) );
					}
				}
			}

			return $vars;
		}

		/**
		 * Filter the sermons in admin based on options.
		 *
		 * @param mixed $query
		 */
		public function sermon_filters_query( $query ) {
			global $typenow, $wp_query;

			if ( 'sermon' == $typenow ) {
				if ( isset( $query->query_vars['foo'] ) ) {
					$query->query_vars['meta_value'] = 'bar';
					$query->query_vars['meta_key']   = 'foobar';
				}
			}
		}

		/**
		 * Change title boxes in admin.
		 *
		 * @param  string $text
		 * @param  object $post
		 *
		 * @return string
		 */
		public function enter_title_here( $text, $post ) {
			if ( $post->post_type === 'sermon' ) {
				$text = __( 'Sermon title', 'sermon-manager' );
			}

			return $text;
		}

		/**
		 * Show blank slate.
		 *
		 * @param string $which
		 */
		public function maybe_render_blank_state( $which ) {
			global $post_type;

			if ( $post_type === 'sermon' && 'bottom' === $which ) {
				$counts = (array) wp_count_posts( $post_type );
				unset( $counts['auto-draft'] );
				$count = array_sum( $counts );

				if ( 0 < $count ) {
					return;
				}

				?>
                <div class="sm-BlankState">
                    <h2 class="sm-BlankState-message">
						<?php _e( 'title', 'sermon-manager' ); ?></h2>
                    <a class="sm-BlankState-cta button-primary button"
                       href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sermon&tutorial=true' ) ); ?>"><?php _e( 'Create your first sermon!', 'sermon-manager' ); ?></a>
                    <a class="sm-BlankState-cta button"
                       href="<?php echo esc_url( admin_url( 'edit.php?post_type=sermon&page=sermon_importer' ) ); ?>"><?php _e( 'Import sermons from a CSV file or another plugin!', 'sermon-manager' ); ?></a>

                    <!--suppress CssUnusedSymbol -->
                    <style type="text/css">
                        /*noinspection CssUnusedSymbol*/
                        #posts-filter .wp-list-table,
                        #posts-filter .tablenav.top,
                        .tablenav.bottom .actions,
                        .wrap .subsubsub {
                            display: none;
                        }
                    </style>
                </div>
				<?php
			}
		}
	}
}

new SM_Admin_Post_Types();
