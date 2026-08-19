<?php
/**
 * Wp Flavor Like FrontEnd Scripts Class.
 * // @echo HEADER
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'flavor_like_frontend_assets' ) ) {
	/**
	 *  Class to load and print the front-end scripts
	 */
	class flavor_like_frontend_assets {

	  	private $hook;

		/**
		 * Whether a like button has been rendered on this page.
		 *
		 * @var boolean
		 */
		private static $assets_needed = false;

	  	/**
	   	 * __construct
	   	 */
	  	function __construct() {
			// Register handles early (no output cost by itself).
			add_action( 'wp_enqueue_scripts', array( $this, 'register' ) );
			// Enqueue late: after templates have rendered, so the on-demand
			// strategy knows whether the page actually contains a button.
			// Priority 5 keeps us before _wp_footer_scripts (priority 20)
			// so late-enqueued styles/scripts are still printed.
			add_action( 'wp_footer', array( $this, 'maybe_enqueue' ), 5 );
	  	}

		/**
		 * Mark that a like button is present and frontend assets are required.
		 * Called from flavor_like_display_button() when a template is rendered.
		 *
		 * @return void
		 */
		public static function mark_assets_needed() {
			self::$assets_needed = true;
		}

		/**
		 * Register (not enqueue) all handles with their inline data.
		 *
		 * @return void
		 */
		public function register() {
			$this->register_styles();
			$this->register_scripts();
		}

		/**
		 * Enqueue assets depending on strategy.
		 *
		 * global   — legacy behaviour, enqueue everywhere (except pages
		 *            excluded via "Disable Assets On").
		 * on_demand — enqueue only when flavor_like_display_button() rendered
		 *            at least one button on this request.
		 *
		 * @return void
		 */
		public function maybe_enqueue() {
			// If user has been disabled this page in options, then return.
			if( ! is_flavor_like( flavor_like_get_option( 'disable_plugin_files' ), array(), true ) ) {
				return;
			}

			$strategy = flavor_like_get_option( 'assets_load_strategy', 'global' );
			if ( 'on_demand' === $strategy && ! self::$assets_needed ) {
				return;
			}

			wp_enqueue_style( FLAVOR_LIKE_SLUG );
			if ( wp_style_is( FLAVOR_LIKE_SLUG . '-custom', 'registered' ) ) {
				wp_enqueue_style( FLAVOR_LIKE_SLUG . '-custom' );
			}

			if ( ! defined( 'FLAVOR_LIKE_PRO_VERSION' ) || version_compare( FLAVOR_LIKE_PRO_VERSION, '1.5.3', '<' ) ) {
				wp_enqueue_script( 'flavor_like' );
			}
		}

	  	/**
	  	 * Styles for admin
	   	 *
	   	 * @return void
	   	 */
	  	public function register_styles() {

	        // @if DEV
	        /*
	        // @endif
	        wp_register_style( FLAVOR_LIKE_SLUG, FLAVOR_LIKE_ASSETS_URL . '/css/flavor-like.min.css', array(), FLAVOR_LIKE_VERSION );
	        // @if DEV
	        */
	        // @endif
	        // @if DEV
			wp_register_style( FLAVOR_LIKE_SLUG, FLAVOR_LIKE_ASSETS_URL . '/css/flavor-like.css', array(), FLAVOR_LIKE_VERSION );
			// @endif

			// Check user preference for CSS delivery method
			$user_prefers_inline = flavor_like_is_true( flavor_like_get_option( 'enable_inline_custom_css', false ) );
			$directory_not_writable = flavor_like_is_true( get_option( 'flavor_like_use_inline_custom_css', true ) );

			// Use inline CSS if user prefers it OR directory is not writable (fallback)
			if( $user_prefers_inline || $directory_not_writable ){
				//add your custom style from setting panel (now includes customizer CSS).
				wp_add_inline_style( FLAVOR_LIKE_SLUG, flavor_like_get_custom_style() );
			} else {
				wp_register_style( FLAVOR_LIKE_SLUG . '-custom', FLAVOR_LIKE_CUSTOM_URL . '/custom.css', array( FLAVOR_LIKE_SLUG ), FLAVOR_LIKE_VERSION );
			}

	  	}

	    /**
	     * Scripts for admin
	     *
	     * @return void
	     */
	  	public function register_scripts() {
			// Return if pro assets exist (Pro >= 1.5.3 includes free scripts, so don't load free version).
			if ( defined( 'FLAVOR_LIKE_PRO_VERSION' ) && version_compare( FLAVOR_LIKE_PRO_VERSION, '1.5.3', '>=' ) ) {
				return;
			}

			// @if DEV
			/*
			// @endif
			//Add flavor_like script file with special functions.
			$this->register_script_with_defer( 'flavor_like', FLAVOR_LIKE_ASSETS_URL . '/js/flavor-like.min.js', array(), FLAVOR_LIKE_VERSION );
			// @if DEV
			*/
			// @endif
			// @if DEV
			$this->register_script_with_defer( 'flavor_like', FLAVOR_LIKE_ASSETS_URL . '/js/flavor-like.js', array(), FLAVOR_LIKE_VERSION );
			// @endif

			flavor_like_add_inline_script_data( 'flavor_like', 'flavor_like_params', $this->get_frontend_script_params() );
	  	}

		/**
		 * Register + immediately enqueue styles.
		 *
		 * Kept for external callers (block editor assets in
		 * includes/blocks/index.php) that need the assets right away,
		 * bypassing the deferred maybe_enqueue() decision.
		 *
		 * @return void
		 */
		public function load_styles() {
			$this->register_styles();
			wp_enqueue_style( FLAVOR_LIKE_SLUG );
			if ( wp_style_is( FLAVOR_LIKE_SLUG . '-custom', 'registered' ) ) {
				wp_enqueue_style( FLAVOR_LIKE_SLUG . '-custom' );
			}
		}

		/**
		 * Register + immediately enqueue scripts.
		 *
		 * Kept for external callers (block editor assets in
		 * includes/blocks/index.php).
		 *
		 * @return void
		 */
		public function load_scripts() {
			$this->register_scripts();
			if ( ! defined( 'FLAVOR_LIKE_PRO_VERSION' ) || version_compare( FLAVOR_LIKE_PRO_VERSION, '1.5.3', '<' ) ) {
				wp_enqueue_script( 'flavor_like' );
			}
		}

		/**
		 * Register a script with defer strategy when supported.
		 *
		 * @param string $handle Script handle.
		 * @param string $src    Script URL.
		 * @param array  $deps   Dependencies.
		 * @param string $ver    Version.
		 * @return void
		 */
		private function register_script_with_defer( $handle, $src, $deps = array(), $ver = false ) {
			if ( function_exists( 'wp_register_script' ) && version_compare( get_bloginfo( 'version' ), '6.3', '>=' ) ) {
				wp_register_script(
					$handle,
					$src,
					$deps,
					$ver,
					array(
						'in_footer' => true,
						'strategy'  => 'defer',
					)
				);
				return;
			}

			wp_register_script( $handle, $src, $deps, $ver, true );
		}

		/**
		 * Frontend params for the free voting script.
		 *
		 * @return array<string, mixed>
		 */
		private function get_frontend_script_params() {
			return array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'notifications' => flavor_like_get_option( 'enable_toast_notice', true ),
				'ajax_error'    => flavor_like_setting_repo::getAjaxErrorNotice(),
			);
		}


	}

}
