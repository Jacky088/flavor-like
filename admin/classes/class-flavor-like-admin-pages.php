<?php
/**
 * Flavor Like Admin Pages Class.
 *
 * Handles registration of admin menus and submenus for Flavor Like plugin.
 *
 * @package WP_Flavor Like
 * @since 1.0.0
 */

// No direct access allowed.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'flavor_like_admin_pages' ) ) {
	/**
	 * Class to register admin menus and submenus.
	 *
	 * @since 1.0.0
	 */
	class flavor_like_admin_pages {

		/**
		 * Menu position constant.
		 *
		 * @since 1.0.0
		 * @var int
		 */
		const MENU_POSITION = 90;

		/**
		 * Submenus array.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		private $submenus = array();

		/**
		 * Views array mapping hook suffixes to template paths.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		private $views = array();

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'menus' ) );
			add_action( 'admin_head', array( $this, 'admin_menu_icon_styles' ) );
			add_action( 'admin_init', array( $this, 'handle_legacy_about_redirect' ) );
		}

		/**
		 * Redirect legacy Overview screen to Settings System & Backup tab.
		 *
		 * @since 1.1.0
		 * @return void
		 */
		public function handle_legacy_about_redirect() {
			if ( isset( $_GET['page'] ) && 'flavor-like-about' === $_GET['page'] ) {
				$query = $_GET;
				unset( $query['page'] );
				$target = admin_url( 'admin.php?page=flavor-like-settings&settings-page=system_status&settings-section=system_tools' );
				if ( ! empty( $query ) ) {
					$target = add_query_arg( $query, $target );
				}
				wp_safe_redirect( $target );
				exit;
			}
			if ( isset( $_GET['page'] ) && 'flavor-like-customize' === $_GET['page'] ) {
				$query = $_GET;
				unset( $query['page'] );
				$target = admin_url( 'admin.php?page=flavor-like-settings' );
				if ( ! empty( $query ) ) {
					$target = add_query_arg( $query, $target );
				}
				wp_safe_redirect( $target );
				exit;
			}
		}

		/**
		 * Register admin menus and submenus.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function menus() {
			// Apply filter to get submenus - moved here so filters registered in admin-hooks.php are available.
			$this->submenus = apply_filters(
				'flavor_like_admin_pages',
				$this->get_default_submenus()
			);

			// Register parent menu (Statistics as primary dashboard).
			$parent_hook = $this->register_parent_menu();

			// Register statistics submenu (replaces first auto-generated submenu).
			$this->register_statistics_submenu( $parent_hook );

			// Register filtered submenus (Customize, Settings).
			$this->register_submenus();

			// Add menu badge if needed.
			$this->menu_badge();
		}

		/**
		 * Get default submenus configuration.
		 *
		 * @since 1.0.0
		 * @return array Default submenus array.
		 */
		private function get_default_submenus() {
			return array(
				'settings' => array(
					'title'       => esc_html__( 'Settings', 'flavor-like' ),
					'parent_slug' => 'flavor-like-statistics',
					'capability'  => 'manage_options',
					'path'        => FLAVOR_LIKE_ADMIN_DIR . '/includes/templates/optiwich.php',
					'menu_slug'   => 'flavor-like-settings',
				),
			);
		}

		/**
		 * Register parent menu page.
		 *
		 * Points primary menu directly to the Statistics dashboard.
		 *
		 * @since 1.0.0
		 * @return string|false The resulting page's hook_suffix, or false on failure.
		 */
		private function register_parent_menu() {
			$capability = $this->get_menu_capability( 'stats' );
			$page_title = apply_filters( 'flavor_like_plugin_name', esc_html__( 'Flavor Like', 'flavor-like' ) );
			$menu_title = apply_filters( 'flavor_like_plugin_name', esc_html__( 'Flavor Like', 'flavor-like' ) );
			// Icon rendered via CSS mask in admin.css (inherits menu link color in all states).
			$menu_icon  = 'none';

			$parent_hook = add_menu_page(
				$page_title,
				$menu_title,
				$capability,
				'flavor-like-statistics',
				array( $this, 'load_template' ),
				$menu_icon,
				self::MENU_POSITION
			);

			// Store parent menu template path (Statistics).
			if ( $parent_hook ) {
				$this->views[ $parent_hook ] = FLAVOR_LIKE_ADMIN_DIR . '/includes/templates/statistics.php';
			}

			return $parent_hook;
		}

		/**
		 * Register statistics submenu page.
		 *
		 * Replaces auto-generated first submenu item with 'Statistics'.
		 *
		 * @since 1.0.0
		 * @param string|false $parent_hook Parent menu hook suffix.
		 * @return void
		 */
		private function register_statistics_submenu( $parent_hook ) {
			if ( ! $parent_hook ) {
				return;
			}

			$capability = $this->get_menu_capability( 'stats' );

			$stats_submenu_hook = add_submenu_page(
				'flavor-like-statistics',
				esc_html__( 'Statistics', 'flavor-like' ),
				esc_html__( 'Statistics', 'flavor-like' ),
				$capability,
				'flavor-like-statistics',
				array( $this, 'load_template' )
			);

			// Store statistics submenu template path.
			if ( $stats_submenu_hook ) {
				$this->views[ $stats_submenu_hook ] = FLAVOR_LIKE_ADMIN_DIR . '/includes/templates/statistics.php';
			}
		}

		/**
		 * Register filtered submenus.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function register_submenus() {
			foreach ( $this->submenus as $key => $args ) {
				$this->register_single_submenu( $args );
			}
		}

		/**
		 * Register a single submenu page.
		 *
		 * @since 1.0.0
		 * @param array $args Submenu arguments.
		 * @return string|false The resulting page's hook_suffix, or false on failure.
		 */
		private function register_single_submenu( $args ) {
			// Extract variables explicitly per WordPress coding standards.
			$parent_slug = isset( $args['parent_slug'] ) ? $args['parent_slug'] : '';
			$title       = isset( $args['title'] ) ? $args['title'] : '';
			$menu_slug   = isset( $args['menu_slug'] ) ? $args['menu_slug'] : '';
			$path        = isset( $args['path'] ) ? $args['path'] : '';

			// Resolve capability.
			$capability = $this->resolve_capability( $args );

			// Validate required arguments.
			if ( empty( $parent_slug ) || empty( $menu_slug ) ) {
				return false;
			}

			$hook_suffix = add_submenu_page(
				$parent_slug,
				$title,
				apply_filters( 'flavor_like_admin_sub_menu_title', $title, $menu_slug ),
				$capability,
				$menu_slug,
				array( $this, 'load_template' )
			);

			// Store template path.
			if ( $hook_suffix && ! empty( $path ) ) {
				$this->views[ $hook_suffix ] = $path;
			}

			return $hook_suffix;
		}

		/**
		 * Resolve capability from arguments.
		 *
		 * Only resolves custom capability types we recognize (like 'stats', 'logs').
		 * Empty string means always visible (no capability check).
		 * Otherwise uses the capability as-is.
		 *
		 * @since 1.0.0
		 * @param array $args Menu arguments.
		 * @return string Resolved capability.
		 */
		private function resolve_capability( $args ) {
			$capability_arg = isset( $args['capability'] ) ? $args['capability'] : 'manage_options';

			// Empty capability means always visible.
			if ( empty( $capability_arg ) ) {
				return 'read'; // Use 'read' capability which all logged-in users have.
			}

			// Recognized custom capability types that should use flavor_like_get_user_access_capability.
			$recognized_types = array( 'stats', 'logs' );

			// If it's a recognized capability type, resolve it using our method.
			if ( is_string( $capability_arg ) && in_array( $capability_arg, $recognized_types, true ) && function_exists( 'flavor_like_get_user_access_capability' ) ) {
				return flavor_like_get_user_access_capability( $capability_arg );
			}

			// Otherwise use the capability as-is (like 'manage_options').
			return $capability_arg;
		}

		/**
		 * Get menu capability.
		 *
		 * Resolves capability based on type using flavor_like_get_user_access_capability.
		 * Only for recognized custom types like 'stats'.
		 *
		 * @since 1.0.0
		 * @param string $capability_type Capability type ('stats', 'logs', etc.).
		 * @return string Resolved capability string for WordPress menu system.
		 */
		private function get_menu_capability( $capability_type ) {
			// Recognized custom capability types.
			$recognized_types = array( 'stats', 'logs' );

			if ( in_array( $capability_type, $recognized_types, true ) && function_exists( 'flavor_like_get_user_access_capability' ) ) {
				$capability = flavor_like_get_user_access_capability( $capability_type );
				// Ensure we always return a valid capability string.
				if ( ! empty( $capability ) && is_string( $capability ) ) {
					return $capability;
				}
			}

			// Fallback to manage_options for security (requires administrator role).
			return 'manage_options';
		}

		/**
		 * Add custom badges to a menu name.
		 *
		 * Finds the menu item by menu slug instead of position number for better reliability.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function menu_badge() {
			global $menu;

			if ( ! isset( $menu ) || ! is_array( $menu ) || ! function_exists( 'flavor_like_badge_count_format' ) ) {
				return;
			}

			$badge_count = apply_filters( 'flavor_like_menu_badge_count', 0 );

			if ( 0 === $badge_count ) {
				return;
			}

			// Find menu item by slug instead of position number.
			foreach ( $menu as $key => $menu_item ) {
				if ( isset( $menu_item[2] ) && in_array( $menu_item[2], array( 'flavor-like-statistics', 'flavor-like-settings' ), true ) ) {
					$menu[ $key ][0] .= flavor_like_badge_count_format( $badge_count );
					break;
				}
			}
		}

		/**
		 * Load admin templates.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function load_template() {
			$current_filter = current_filter();

			if ( empty( $current_filter ) || ! isset( $this->views[ $current_filter ] ) ) {
				return;
			}

			$template_path = $this->views[ $current_filter ];

			// Validate template file exists.
			if ( ! empty( $template_path ) && file_exists( $template_path ) ) {
				load_template( $template_path );
			}
		}

		/**
		 * Inject admin menu icon CSS mask styles globally across all wp-admin screens.
		 *
		 * Ensures the menu icon displays on Dashboard, Plugins, and all admin pages,
		 * strictly as pure lines inheriting the admin menu color scheme via currentColor.
		 *
		 * @since 1.1.0
		 * @return void
		 */
		public function admin_menu_icon_styles() {
			$icon_url = FLAVOR_LIKE_ASSETS_URL . '/img/menu-icon.svg?ver=' . FLAVOR_LIKE_VERSION;
			?>
			<style id="flavor-like-admin-menu-icon-css">
				#adminmenu #toplevel_page_flavor-like-settings .wp-menu-image,
				#adminmenu #toplevel_page_flavor-like-statistics .wp-menu-image {
					background: none !important;
				}
				#adminmenu #toplevel_page_flavor-like-settings .wp-menu-image::before,
				#adminmenu #toplevel_page_flavor-like-statistics .wp-menu-image::before {
					content: '' !important;
					display: inline-block !important;
					width: 20px !important;
					height: 20px !important;
					padding: 7px 0 !important;
					background-color: currentColor !important;
					speak: never !important;
					-webkit-mask: url('<?php echo esc_url( $icon_url ); ?>') no-repeat center / 20px 20px !important;
					mask: url('<?php echo esc_url( $icon_url ); ?>') no-repeat center / 20px 20px !important;
					transition: all 0.1s ease-in-out !important;
				}
				#adminmenu #toplevel_page_flavor-like-settings:hover .wp-menu-image::before,
				#adminmenu #toplevel_page_flavor-like-statistics:hover .wp-menu-image::before,
				#adminmenu #toplevel_page_flavor-like-settings.wp-has-current-submenu .wp-menu-image::before,
				#adminmenu #toplevel_page_flavor-like-statistics.wp-has-current-submenu .wp-menu-image::before,
				#adminmenu #toplevel_page_flavor-like-settings.current .wp-menu-image::before,
				#adminmenu #toplevel_page_flavor-like-statistics.current .wp-menu-image::before {
					background-color: currentColor !important;
				}
			</style>
			<?php
		}

	}

}