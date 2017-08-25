<?php
/**
 * Setup Wizard Class
 *
 * Welcomes users to SMv3, maybe even does something else
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SM_Admin_Setup_Wizard class.
 */
class SM_Admin_Setup_Wizard {

	/** @var string Current Step */
	private $step = '';

	/** @var array Steps for the setup wizard */
	private $steps = array();

	/** @var array Tweets user can optionally send after install */
	private $tweets = array(
		'Test tweet',
	);

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		if ( apply_filters( 'sm_enable_setup_wizard', true ) && current_user_can( 'manage_sermons' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'admin_init', array( $this, 'setup_wizard' ) );
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'sm-setup', '' );
	}

	/**
	 * Show the setup wizard.
	 */
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || 'sm-setup' !== $_GET['page'] ) {
			return;
		}
		$default_steps = array(
			'introduction' => array(
				'name'    => __( 'Introduction', 'sermon-manager' ),
				'view'    => array( $this, 'sm_setup_introduction' ),
				'handler' => '',
			),
			'pages'        => array(
				'name'    => __( 'Page setup', 'sermon-manager' ),
				'view'    => array( $this, 'sm_setup_pages' ),
				'handler' => array( $this, 'sm_setup_pages_save' ),
			),
			'theme'        => array(
				'name'    => __( 'Theme', 'sermon-manager' ),
				'view'    => array( $this, 'sm_setup_theme' ),
				'handler' => '',
			),
			'next_steps'   => array(
				'name'    => __( 'Ready!', 'sermon-manager' ),
				'view'    => array( $this, 'sm_setup_ready' ),
				'handler' => '',
			),
		);

		// Hide theme step if using a WPFC theme or user cannot modify themes.
		if ( ! current_user_can( 'install_themes' ) || ! current_user_can( 'switch_themes' ) || is_multisite() || current_theme_supports( 'sermon-manager' ) ) {
			unset( $default_steps['theme'] );
		}

		$this->steps = apply_filters( 'sm_setup_wizard_steps', $default_steps );
		$this->step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );
		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'jquery-blockui', SM()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'select2', SM()->plugin_url() . '/assets/js/select2/select2.full' . $suffix . '.js', array( 'jquery' ), '4.0.3' );
		wp_register_script( 'sm-enhanced-select', SM()->plugin_url() . '/assets/js/admin/sm-enhanced-select' . $suffix . '.js', array(
			'jquery',
			'select2'
		), SM_VERSION );
		wp_localize_script( 'sm-enhanced-select', 'sm_enhanced_select_params', array(
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'sermon-manager' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'sermon-manager' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'sermon-manager' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'sermon-manager' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'sermon-manager' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'sermon-manager' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'sermon-manager' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'sermon-manager' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'sermon-manager' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'sermon-manager' ),
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'search_sermons_nonce'      => wp_create_nonce( 'search-sermons' ),
		) );
		wp_enqueue_style( 'sm_admin_styles', SM()->plugin_url() . '/assets/css/admin.css', array(), SM_VERSION );
		wp_enqueue_style( 'sm-setup', SM()->plugin_url() . '/assets/css/sm-setup.css', array(
			'dashicons',
			'install'
		), SM_VERSION );

		wp_register_script( 'sm-setup', SM()->plugin_url() . '/assets/js/admin/sm-setup' . $suffix . '.js', array(
			'jquery',
			'sm-enhanced-select',
			'jquery-blockui'
		), SM_VERSION );
		wp_localize_script( 'sm-setup', 'sm_setup_params', array(
			'locale_info' => json_encode( include( SM()->plugin_path() . '/i18n/locale-info.php' ) ),
		) );

		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	/**
	 * Get the URL for the next step's screen.
	 *
	 * @param string $step slug (default: current step)
	 *
	 * @return string       URL for next step if a next step exists.
	 *                      Admin URL if it's the last step.
	 *                      Empty string on failure.
	 * @since 3.0.0
	 */
	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );
		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index + 1 ] );
	}

	/**
	 * Setup Wizard Header.
	 */
public function setup_wizard_header() {
	?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta name="viewport" content="width=device-width"/>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?php esc_html_e( 'Sermon Manager &rsaquo; Setup Wizard', 'sermon-manager' ); ?></title>
		<?php wp_print_scripts( 'sm-setup' ); ?>
		<?php do_action( 'admin_print_styles' ); ?>
		<?php do_action( 'admin_head' ); ?>
    </head>
    <body class="sm-setup wp-core-ui">
    <h1 id="sm-logo"><a href="https://wpforchurch.com/"><img
                    src="<?php echo SM()->plugin_url(); ?>/assets/images/sm_logo.png" alt="Sermon Manager"/></a></h1>
	<?php
	}

	/**
	 * Setup Wizard Footer.
	 */
	public function setup_wizard_footer() {
	?>
	<?php if ( 'next_steps' === $this->step ) : ?>
        <a class="sm-return-to-dashboard"
           href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Return to the WordPress Dashboard', 'sermon-manager' ); ?></a>
	<?php endif; ?>
    </body>
    </html>
	<?php
}

	/**
	 * Output the steps.
	 */
	public function setup_wizard_steps() {
		$ouput_steps = $this->steps;
		array_shift( $ouput_steps );
		?>
        <ol class="sm-setup-steps">
			<?php foreach ( $ouput_steps as $step_key => $step ) : ?>
                <li class="<?php
				if ( $step_key === $this->step ) {
					echo 'active';
				} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
					echo 'done';
				}
				?>"><?php echo esc_html( $step['name'] ); ?></li>
			<?php endforeach; ?>
        </ol>
		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	public function setup_wizard_content() {
		echo '<div class="sm-setup-content">';
		call_user_func( $this->steps[ $this->step ]['view'], $this );
		echo '</div>';
	}

	/**
	 * Introduction step.
	 */
	public function sm_setup_introduction() {
		?>
        <h1><?php esc_html_e( 'Welcome to Sermon Manager', 'sermon-manager' ); ?></h1>
        <p><?php _e( 'Thank you for choosing Sermon Manager! This quick setup wizard will help you configure the basic settings. <strong>It’s completely optional and shouldn’t take longer than five minutes.</strong>', 'sermon-manager' ); ?></p>
        <p><?php esc_html_e( 'No time right now? If you don’t want to go through the wizard, you can skip and return to the WordPress dashboard. Come back anytime if you change your mind!', 'sermon-manager' ); ?></p>
        <p class="sm-setup-actions step">
            <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
               class="button-primary button button-large button-next"><?php esc_html_e( "Let's go!", 'sermon-manager' ); ?></a>
            <a href="<?php echo esc_url( admin_url() ); ?>"
               class="button button-large"><?php esc_html_e( 'Not right now', 'sermon-manager' ); ?></a>
        </p>
		<?php
	}

	/**
	 * Page setup.
	 */
	public function sm_setup_pages() {
		?>
        <h1><?php esc_html_e( 'Page setup', 'sermon-manager' ); ?></h1>
        <form method="post">
            <p><?php printf( __( 'Your website needs a few essential <a href="%s" target="_blank">pages</a>. The following will be created automatically (if they do not already exist):', 'sermon-manager' ), esc_url( admin_url( 'edit.php?post_type=page' ) ) ); ?></p>
            <table class="sm-setup-pages" cellspacing="0">
                <thead>
                <tr>
                    <th class="page-name"><?php esc_html_e( 'Page name', 'sermon-manager' ); ?></th>
                    <th class="page-description"><?php esc_html_e( 'Description', 'sermon-manager' ); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="page-name"><?php echo _x( 'Sermons Archive View', 'Page title', 'sermon-manager' ); ?></td>
                    <td><?php esc_html_e( 'The archive view page will display your sermons.', 'sermon-manager' ); ?></td>
                </tr>
                </tbody>
            </table>

            <p><?php printf( __( 'Once created, these pages can be managed from your admin dashboard on the <a href="%1$s" target="_blank">Pages screen</a>. You can control which pages are shown on your website via <a href="%2$s" target="_blank">Appearance > Menus</a>.', 'sermon-manager' ), esc_url( admin_url( 'edit.php?post_type=page' ) ), esc_url( admin_url( 'nav-menus.php' ) ) ); ?></p>

            <p class="sm-setup-actions step">
                <input type="submit" class="button-primary button button-large button-next"
                       value="<?php esc_attr_e( 'Continue', 'sermon-manager' ); ?>" name="save_step"/>
                <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
                   class="button button-large button-next"><?php esc_html_e( 'Skip this step', 'sermon-manager' ); ?></a>
				<?php wp_nonce_field( 'sm-setup' ); ?>
            </p>
        </form>
		<?php
	}

	/**
	 * Save Page Settings.
	 */
	public function sm_setup_pages_save() {
		check_admin_referer( 'sm-setup' );

		SM_Install::create_pages();
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Theme step.
	 */
	private function sm_setup_theme() {
		?>
        <form method="post" class="sm-wizard-multiply">
            <p class="sm-wizard-storefront-intro">
				<?php echo wp_kses_post( __( '<strong>Multiply</strong> is WordPress theme built and maintained by the makers of Sermon Manager.', 'sermon-manager' ) ); ?>
                <img src="<?php echo esc_url( SM()->plugin_url() . '/assets/images/multiply-intro.png' ); ?>"
                     alt="Multiply"/>
            </p>

            <ul class="sm-wizard-multiply-features">
                <li class="sm-wizard-multiply-feature sm-wizard-multiply-feature__bulletproof first"><?php echo wp_kses_post( __( '<strong>Bulletproof Sermon Manager integration:</strong> Rest assured the integration between Sermon Manager, Sermon Manager extensions and Multiply theme is water-tight.', 'sermon-manager' ) ); ?></li>
            </ul>
            <p class="sm-setup-actions step">
                <a href="https://wpforchurch.com/wordpress-themes/multiply-theme/"
                   class="button-primary button button-large button-next"><?php esc_attr_e( 'View Multiply', 'sermon-manager' ); ?></a>
                <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
                   class="button button-large button-next"><?php esc_html_e( 'Continue', 'sermon-manager' ); ?></a>
				<?php wp_nonce_field( 'sm-setup' ); ?>
            </p>
        </form>
		<?php

	}

	/**
	 * Actions on the final step.
	 */
	private function sm_setup_ready_actions() {
		SM_Admin_Notices::remove_notice( 'install' );

		if ( isset( $_GET['sm_tracker_optin'] ) && isset( $_GET['sm_tracker_nonce'] ) && wp_verify_nonce( $_GET['sm_tracker_nonce'], 'sm_tracker_optin' ) ) {
			update_option( 'sm_allow_tracking', 'yes' );
			SM_Tracker::send_tracking_data( true );

		} elseif ( isset( $_GET['sm_tracker_optout'] ) && isset( $_GET['sm_tracker_nonce'] ) && wp_verify_nonce( $_GET['sm_tracker_nonce'], 'sm_tracker_optout' ) ) {
			update_option( 'sm_allow_tracking', 'no' );
		}
	}

	/**
	 * Final step.
	 */
	public function sm_setup_ready() {
		$this->sm_setup_ready_actions();
		shuffle( $this->tweets );
		?>
        <a href="https://twitter.com/share" class="twitter-share-button" data-url="https://wpforchurch.com/"
           data-text="<?php echo esc_attr( $this->tweets[0] ); ?>" data-via="wpforchurch" data-size="large">Tweet</a>
        <script>!function (d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (!d.getElementById(id)) {
                    js = d.createElement(s);
                    js.id = id;
                    js.src = "//platform.twitter.com/widgets.js";
                    fjs.parentNode.insertBefore(js, fjs);
                }
            }(document, "script", "twitter-wjs");</script>

        <h1><?php esc_html_e( 'Sermon Manager is ready!', 'sermon-manager' ); ?></h1>

		<?php if ( 'unknown' === get_option( 'sm_allow_tracking', 'unknown' ) ) : ?>
            <div class="sm-message sm-tracker">
                <p><?php printf( __( 'Want to help make Sermon Manager even more awesome? Allow Sermon Manager to collect non-sensitive diagnostic data and usage information. %1$sFind out more%2$s.', 'sermon-manager' ), '<a href="#" target="_blank">', '</a>' ); ?></p>
                <p class="submit">
                    <a class="button-primary button button-large"
                       href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'sm_tracker_optin', 'true' ), 'sm_tracker_optin', 'sm_tracker_nonce' ) ); ?>"><?php esc_html_e( 'Allow', 'sermon-manager' ); ?></a>
                    <a class="button-secondary button button-large skip"
                       href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'sm_tracker_optout', 'true' ), 'sm_tracker_optout', 'sm_tracker_nonce' ) ); ?>"><?php esc_html_e( 'No thanks', 'sermon-manager' ); ?></a>
                </p>
            </div>
		<?php endif; ?>

        <div class="sm-setup-next-steps">
            <div class="sm-setup-next-steps-first">
                <h2><?php esc_html_e( 'Next steps', 'sermon-manager' ); ?></h2>
                <ul>
                    <li class="setup-sermon"><a class="button button-primary button-large"
                                                href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sermon&tutorial=true' ) ); ?>"><?php esc_html_e( 'Create your first sermon!', 'sermon-manager' ); ?></a>
                    </li>
                    <li class="setup-sermon"><a class="button button-large"
                                                href="<?php echo esc_url( admin_url( 'edit.php?post_type=sermon&page=sermon_importer' ) ); ?>"><?php esc_html_e( 'Import sermons from a CSV file or another plugin!', 'sermon-manager' ); ?></a>
                    </li>
                </ul>
            </div>
            <div class="sm-setup-next-steps-last">
                <h2><?php _e( 'Learn more', 'sermon-manager' ); ?></h2>
                <ul>
                    <li class="documentation"><a
                                href="https://wpforchurch.com/my/knowledgebase/12/Sermon-Manager/?utm_source=setupwizard&utm_medium=product&utm_content=docs&utm_campaign=sermonmanagerplugin"><?php esc_html_e( 'View documentation', 'sermon-manager' ); ?></a>
                    </li>
                </ul>
            </div>
        </div>
		<?php
	}
}

new SM_Admin_Setup_Wizard();
