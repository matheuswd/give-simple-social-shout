<?php
/**
 * Plugin Name: Simple Social Shout for GiveWP
 * Plugin URI:  https://github.com/impress-org/give-simple-social-shout
 * Description: Add simple sharing options to your GiveWP Donation Receipt page.
 * Version:     1.0
 * Author:      Matt Cromwell
 * Author URI:  https://www.mattcromwell.com
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sss-4-givewp
 *
 *
 * Hattip to the following online resources:
 * 1. This Codepen on doing the social links with pure HTML/CSS:
 * https://codepen.io/asheabbott/pen/GoMrzW
 *
 * 2. Socicon for the really easy social icon library with brand colors too:
 * http://www.socicon.com
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIMPLE_SOCIAL_SHARE_4_GIVEWP
 */
final class SIMPLE_SOCIAL_SHARE_4_GIVEWP {
	/**
	 * Instance.
	 *
	 * @since
	 * @access private
	 * @var SIMPLE_SOCIAL_SHARE_4_GIVEWP
	 */
	private static $instance;

	/**
	 * Singleton pattern.
	 *
	 * @since
	 * @access private
	 */
	private function __construct() {
	}


	/**
	 * Get instance.
	 *
	 * @return SIMPLE_SOCIAL_SHARE_4_GIVEWP
	 * @since
	 * @access public
	 *
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof SIMPLE_SOCIAL_SHARE_4_GIVEWP ) ) {
			self::$instance = new SIMPLE_SOCIAL_SHARE_4_GIVEWP();
			self::$instance->setup();
		}

		return self::$instance;
	}


	/**
	 * Setup
	 *
	 * @since
	 * @access private
	 */
	private function setup() {
		self::$instance->setup_constants();

		register_activation_hook( SIMPLE_SOCIAL_SHARE_4_GIVEWP_FILE, array( $this, 'install' ) );
		add_action( 'give_init', array( $this, 'init' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'check_environment' ), 999 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
		add_action( 'wp_enqueue_scripts', array($this, 'load_styles') );
		add_filter( 'give-settings_get_settings_pages', array( $this, 'register_setting_page' ) );
	}


	/**
	 * Setup constants
	 *
	 * Defines useful constants to use throughout the add-on.
	 *
	 * @since
	 * @access private
	 */
	private function setup_constants() {

		// Defines addon version number for easy reference.
		if ( ! defined( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_VERSION' ) ) {
			define( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_VERSION', '1.0' );
		}

		// Set it to latest.
		if ( ! defined( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_MIN_GIVE_VERSION' ) ) {
			define( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_MIN_GIVE_VERSION', '2.5' );
		}

		if ( ! defined( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_FILE' ) ) {
			define( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_FILE', __FILE__ );
		}

		if ( ! defined( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_DIR' ) ) {
			define( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_DIR', plugin_dir_path( SIMPLE_SOCIAL_SHARE_4_GIVEWP_FILE ) );
		}

		if ( ! defined( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_URL' ) ) {
			define( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_URL', plugin_dir_url( SIMPLE_SOCIAL_SHARE_4_GIVEWP_FILE ) );
		}

		if ( ! defined( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_BASENAME' ) ) {
			define( 'SIMPLE_SOCIAL_SHARE_4_GIVEWP_BASENAME', plugin_basename( SIMPLE_SOCIAL_SHARE_4_GIVEWP_FILE ) );
		}
	}

	/**
	 * Notices (array)
	 *
	 * @var array
	 */
	public $notices = array();

	/**
	 * Plugin installation
	 *
	 * @since
	 * @access public
	 */
	public function install() {
		// Bailout.
		if ( ! self::$instance->check_environment() ) {
			return;
		}
	}

	/**
	 * Plugin installation
	 *
	 * @param Give $give
	 *
	 * @return void
	 * @since
	 * @access public
	 *
	 */
	public function init( $give ) {

		load_plugin_textdomain( 'sss-4-givewp', false, dirname( SIMPLE_SOCIAL_SHARE_4_GIVEWP_BASENAME ) . '/languages' );
		
		// Don't hook anything else in the plugin if we're in an incompatible environment.
		if ( ! $this->get_environment_warning() ) {
			return;
		}

		self::$instance->load_files();
	}


	/**
	 * Check plugin environment.
	 *
	 * @since  2.1.1
	 * @access public
	 *
	 * @return bool
	 */
	public function check_environment() {
		// Flag to check whether plugin file is loaded or not.
		$is_working = true;
		// Load plugin helper functions.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		/* Check to see if GiveWP is activated, if it isn't deactivate and show a banner. */
		
		$is_give_active = defined( 'GIVE_PLUGIN_BASENAME' ) ? is_plugin_active( GIVE_PLUGIN_BASENAME ) : false;

		if ( empty( $is_give_active ) ) {
			// Show admin notice.
			$this->add_admin_notice( 'prompt_give_activate', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">GiveWP</a> plugin installed and activated for Simple Social Share for GiveWP to activate.', 'sss-4-givewp' ), 'https://givewp.com' ) );

			// Deactivate plugin.
			deactivate_plugins( SIMPLE_SOCIAL_SHARE_4_GIVEWP_BASENAME );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}

			$is_working = false;
		}
		return $is_working;
	}

	/**
	 * Check plugin for Give environment.
	 *
	 * @since  2.1.1
	 * @access public
	 *
	 * @return bool
	 */
	public function get_environment_warning() {
		// Flag to check whether plugin file is loaded or not.
		$is_working = true;
		// Verify dependency cases.
		if (
			defined( 'GIVE_VERSION' )
			&& version_compare( GIVE_VERSION, SIMPLE_SOCIAL_SHARE_4_GIVEWP_MIN_GIVE_VERSION, '<' )
		) {
			/* Min. Give. plugin version. */
			// Show admin notice.
			$this->add_admin_notice( 'prompt_give_incompatible', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">GiveWP</a> core version %s for the Simple Social Share for GiveWP add-on to activate.', 'sss-4-givewp' ), 'https://givewp.com', SIMPLE_SOCIAL_SHARE_4_GIVEWP_MIN_GIVE_VERSION ) );
			$is_working = false;
		}
		
		return $is_working;
	}

	/**
	 * Allow this class and other classes to add notices.
	 *
	 * @param string $slug Notice Slug.
	 * @param string $class Notice Class.
	 * @param string $message Notice Message.
	 */
	public function add_admin_notice( $slug, $class, $message ) {
		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message,
		);
	}

	/**
	 * Display admin notices.
	 */
	public function admin_notices() {
		$allowed_tags = array(
			'a'      => array(
				'href'  => array(),
				'title' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'br'     => array(),
			'em'     => array(),
			'span'   => array(
				'class' => array(),
			),
			'strong' => array(),
		);
		foreach ( (array) $this->notices as $notice_key => $notice ) {
			echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
			echo wp_kses( $notice['message'], $allowed_tags );
			echo '</p></div>';
		}
	}

	/**
	 * Register setting page.
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	function register_setting_page( $settings ) {
		require_once SIMPLE_SOCIAL_SHARE_4_GIVEWP_DIR . 'includes/admin/settings.php';

		$settings[] = new SSS_4_GiveWP_Admin_Settings();

		return $settings;
	}

	/**
	 * Load plugin files.
	 *
	 * @since
	 * @access private
	 */
	private function load_files() {
		require_once SIMPLE_SOCIAL_SHARE_4_GIVEWP_DIR . 'includes/main-functions.php';
		require_once SIMPLE_SOCIAL_SHARE_4_GIVEWP_DIR . 'includes/admin/form-settings.php';
	}


	/**
	 * Setup hooks
	 *
	 * @since
	 * @access private
	 */
	public function load_styles() {
        wp_enqueue_style( 'sss4givewp', SIMPLE_SOCIAL_SHARE_4_GIVEWP_URL . 'assets/sss4givewp-frontend.css', array(), SIMPLE_SOCIAL_SHARE_4_GIVEWP_VERSION, 'all' );
        wp_enqueue_style( 'sss4givewp-socicon', 'https://s3.amazonaws.com/icomoon.io/114779/Socicon/style.css?u8vidh', array(), '1.0', 'all' );
	}
}

/**
 * The main function responsible for returning the one true SIMPLE_SOCIAL_SHARE_4_GIVEWP instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $recurring = SIMPLE_SOCIAL_SHARE_4_GIVEWP(); ?>
 *
 * @return SIMPLE_SOCIAL_SHARE_4_GIVEWP|bool
 * @since 1.0
 *
 */
function SIMPLE_SOCIAL_SHARE_4_GIVEWP() {
	return SIMPLE_SOCIAL_SHARE_4_GIVEWP::get_instance();
}

SIMPLE_SOCIAL_SHARE_4_GIVEWP();
