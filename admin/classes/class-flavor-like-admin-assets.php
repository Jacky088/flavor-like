<?php
/**
 * Wp Flavor Like Admin Scripts Class.
 * // @echo HEADER
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'flavor_like_admin_assets' ) ) {
	/**
	 *  Class to load and print the admin panel scripts
	 */
	class flavor_like_admin_assets {

		private $hook;

	  	/**
	   	 * __construct
	   	 */
	  	function __construct() {
			// general assets
        	add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	  	}

		public function enqueue( $hook ){
			$this->hook = $hook;
			// general assets
			$this->load_styles();
			$this->load_notices_script();
			$this->load_statistics_app();
			$this->load_optiwich_app();
		}

		/**
		 * Admin notice interactions (dismiss / CTA).
		 *
		 * @return void
		 */
		public function load_notices_script() {
			wp_enqueue_script(
				'flavor-like-admin-notices',
				FLAVOR_LIKE_ADMIN_URL . '/assets/js/notices.js',
				array(),
				FLAVOR_LIKE_VERSION,
				true
			);
		}


		/**
		 * Styles for admin
		 *
		 * @return void
		 */
		public function load_styles() {
			// Enqueue admin styles
			wp_enqueue_style(
				'flavor-like-admin',
				FLAVOR_LIKE_ADMIN_URL . '/assets/css/admin.css',
				array(),
				FLAVOR_LIKE_VERSION
			);

			// 暗色模式兼容 - 支持 prefers-color-scheme: dark、.dark-mode、.flavor-dark 等
			$dark_css = '
@media (prefers-color-scheme: dark) {
  .flavor-like-about,
  .flavor-like-about-card {
    color: #f0f0f1;
    background: #2c3338;
    border-color: #3c434a;
  }
  .flavor-like-about-card__title {
    color: #f0f0f1;
  }
  .flavor-like-about__lead,
  .flavor-like-about-summary,
  .flavor-like-about-status__label,
  .flavor-like-about-status__value,
  .flavor-like-about-status__hint,
  .flavor-like-about-meta dt,
  .flavor-like-about-meta dd,
  .flavor-like-about-backup__intro {
    color: #dcdcde;
  }
  .flavor-like-about__badge {
    background: #3c434a;
    color: #f0f0f1;
  }
  .flavor-like-about-status__item {
    border-color: #3c434a;
  }
  .flavor-like-about-card--details summary {
    color: #f0f0f1;
  }
  .flavor-like-about-card--muted {
    background: #32383e;
  }
  .notice {
    background: #32383e;
    border-color: #3c434a;
    color: #f0f0f1;
  }
}
body.flavor-dark .flavor-like-about,
body.flavor-dark .flavor-like-about-card,
body.dark-mode .flavor-like-about,
body.dark-mode .flavor-like-about-card,
html[data-darkreader-mode] .flavor-like-about,
html[data-darkreader-mode] .flavor-like-about-card {
  color: #f0f0f1;
  background: #2c3338;
  border-color: #3c434a;
}
body.flavor-dark .flavor-like-about-card__title,
body.dark-mode .flavor-like-about-card__title,
html[data-darkreader-mode] .flavor-like-about-card__title {
  color: #f0f0f1;
}
body.flavor-dark .flavor-like-about__lead,
body.flavor-dark .flavor-like-about-summary,
body.flavor-dark .flavor-like-about-meta dt,
body.flavor-dark .flavor-like-about-meta dd,
body.dark-mode .flavor-like-about__lead,
body.dark-mode .flavor-like-about-summary,
body.dark-mode .flavor-like-about-meta dt,
body.dark-mode .flavor-like-about-meta dd,
html[data-darkreader-mode] .flavor-like-about__lead,
html[data-darkreader-mode] .flavor-like-about-summary,
html[data-darkreader-mode] .flavor-like-about-meta dt,
html[data-darkreader-mode] .flavor-like-about-meta dd {
  color: #dcdcde;
}
';
			wp_add_inline_style( 'flavor-like-admin', $dark_css );

			// Scripts is only can be load on flavor_like pages.
			if ( strpos( $this->hook, FLAVOR_LIKE_SLUG ) === false ) {
				return;
			}

			// Enqueue third-party styles
			wp_enqueue_style(
				'flavor-like-admin-plugins',
				FLAVOR_LIKE_ADMIN_URL . '/assets/css/plugins.css',
				array(),
				FLAVOR_LIKE_VERSION
			);
		}


		function load_statistics_app(){
			if (
				strpos( $this->hook, FLAVOR_LIKE_SLUG ) === false
				|| strpos( $this->hook, 'statistics' ) === false
			) {
				return;
			}

			wp_dequeue_script( 'svg-painter' );

		wp_enqueue_style(
			'flavor_like_admin_react',
			FLAVOR_LIKE_ADMIN_URL . '/includes/statistics/stats.css',
			array(),
			FLAVOR_LIKE_VERSION
		);

		// 修复：覆盖统计页面 CSS 中的 overflow:hidden，恢复页面滚动
		// 同时确保暗色模式在 WordPress 暗色插件下正确触发
		$stats_inline = '
html:has(.flavor-like-stats-app){overflow:auto!important}
body:has(.flavor-like-stats-app){overflow:auto!important}
body:has(.flavor-like-stats-app) #wpcontent{overflow:visible!important}
body.dark-mode .flavor-like-stats-app,
body.flavor-dark .flavor-like-stats-app,
html[data-darkreader-mode] .flavor-like-stats-app {
  --flavor-like-stats-color-text: #f6f7f7;
  --flavor-like-stats-color-text-secondary: #dcdcde;
  --flavor-like-stats-color-text-muted: #c3c4c7;
  --flavor-like-stats-color-bg: #2d333b;
  --flavor-like-stats-color-bg-secondary: #32383f;
  --flavor-like-stats-color-bg-canvas: #24292f;
  --flavor-like-stats-color-bg-hover: #424a54;
  --flavor-like-stats-color-bg-active: #424a54;
  --flavor-like-stats-color-border: #4b535e;
  --flavor-like-stats-color-header-bg: #2d333b;
}
';
		wp_add_inline_style( 'flavor_like_admin_react', $stats_inline );

		wp_enqueue_script(
			'flavor_like_admin_react',
			FLAVOR_LIKE_ADMIN_URL . '/includes/statistics/stats.js',
			array(),
			FLAVOR_LIKE_VERSION,
			true
		);

			flavor_like_add_inline_script_data(
					'flavor_like_admin_react',
					'StatsAppConfig',
					array(
						'nonce'     => wp_create_nonce( FLAVOR_LIKE_SLUG ),
						'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
						'logo'      => FLAVOR_LIKE_ASSETS_URL . '/img/icon.svg',
						'title'     => esc_html__( 'Metrics Dashboard', 'flavor-like' ),
						'buildType' => 'free',
						'loaderSvg' => $this->get_loader_svg(),
					'userPrefs' => class_exists( 'Flavor_Like_Stats_User_Prefs' )
						? Flavor_Like_Stats_User_Prefs::get_app_config()
						: array(),
					'migrationNotice' => $this->get_migration_notice_config(),
				)
			);
	}

	/**
	 * Build the in-app migration nudge config, or null when not applicable.
	 *
	 * Statistics are correct in every storage mode (legacy/dual/pulse) because
	 * vote reads route through the mode-aware Pulse_Query. This is a soft
	 * performance/cleanup nudge shown inside the React app.
	 *
	 * @return array<string,mixed>|null
	 */
	private function get_migration_notice_config() {
		$url = class_exists( 'Flavor_Like_Pulse_Admin' )
			? Flavor_Like_Pulse_Admin::get_page_url()
			: admin_url( 'admin.php?page=flavor-like-pulse' );

		// After full Pulse cutover, nudge cleanup when legacy tables remain.
		if (
			class_exists( 'Flavor_Like_Pulse_Config' )
			&& Flavor_Like_Pulse_Config::MODE_PULSE === Flavor_Like_Pulse_Config::mode()
			&& class_exists( 'Flavor_Like_Pulse_Legacy_Cleanup' )
			&& Flavor_Like_Pulse_Legacy_Cleanup::legacy_tables_exist()
			&& ! Flavor_Like_Pulse_Config::is_admin_dismissed()
		) {
			return array(
				'id'       => 'free_pulse_cleanup',
				'title'    => esc_html__( 'Free up disk space.', 'flavor-like' ),
				'message'  => esc_html__( 'Like records already use the faster storage. Remove the old log tables when you are ready to reclaim disk space.', 'flavor-like' ),
				'ctaLabel' => esc_html__( 'Review cleanup', 'flavor-like' ),
				'ctaUrl'   => esc_url( $url ),
			);
		}

		$pending = ( function_exists( 'flavor_like_pulse_reads_legacy_votes' ) && flavor_like_pulse_reads_legacy_votes() )
			|| ( function_exists( 'flavor_like_pulse_needs_migration' ) && flavor_like_pulse_needs_migration() );

		if ( ! $pending ) {
			return null;
		}

		return array(
			'id'       => 'free_pulse_migration',
			'title'    => esc_html__( 'Faster statistics with Pulse storage.', 'flavor-like' ),
			'message'  => esc_html__( 'Your statistics are already complete — Flavor Like reads both legacy and Pulse data automatically. Migrating fully to Pulse storage makes charts faster and lets you clean up the old tables.', 'flavor-like' ),
			'ctaLabel' => esc_html__( 'Upgrade like storage', 'flavor-like' ),
			'ctaUrl'   => esc_url( $url ),
		);
	}

		/**
		 * Load Optiwich settings app
		 *
		 * @return void
		 */
		function load_optiwich_app(){
			// only load on settings menu page or customizer
			if ( strpos( $this->hook, FLAVOR_LIKE_SLUG ) !== false && preg_match("/(settings|customize)/i", $this->hook )  ) {
				// Enqueue WordPress media library (required for upload fields)
				wp_enqueue_media();

				// Enqueue Optiwich CSS
				wp_enqueue_style(
					'flavor-like-optiwich',
					FLAVOR_LIKE_ADMIN_URL . '/includes/optiwich/style.css',
					array(),
					FLAVOR_LIKE_VERSION
				);

				// Enqueue Optiwich JS
				wp_enqueue_script(
					'flavor-like-optiwich',
					FLAVOR_LIKE_ADMIN_URL . '/includes/optiwich/optiwich.umd.js',
					array(),
					FLAVOR_LIKE_VERSION,
					true
				);

				// Get translations from settings API
				$translations = array();
				if ( class_exists( 'flavor_like_settings_api' ) ) {
					$settings_api = new flavor_like_settings_api();
					$translations = $settings_api->get_translations();
				}

				// Pass the app config to the frontend
				flavor_like_add_inline_script_data(
					'flavor-like-optiwich',
					'OptiwichConfig',
					array(
						'nonce'     => wp_create_nonce( FLAVOR_LIKE_SLUG ),
						'title'     => FLAVOR_LIKE_NAME,
						'logo'      => FLAVOR_LIKE_ASSETS_URL . '/img/logo.svg',
						'slug'      => FLAVOR_LIKE_SLUG,
						'loaderSvg' => $this->get_loader_svg(),
						'actions' => array(
							'schema'            => 'flavor_like_schema_api',
							'settings'          => 'flavor_like_settings_api',
							'save'              => 'flavor_like_save_settings_api',
							'customizerSchema'  => 'flavor_like_customizer_schema_api',
							'customizerValues'  => 'flavor_like_customizer_values_api',
							'customizerSave'    => 'flavor_like_save_customizer_api',
							'customizerPreview' => 'flavor_like_customizer_preview_api',
						),
						'translations' => $translations,
					)
				);
			}
		}

		/**
		 * Flavor Like logo SVG loader.
		 *
		 * @return string
		 */
		private function get_loader_svg() {
			return '<svg width="386" height="204" viewBox="0 0 386 204" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M368.646 20.3446C345.509 -2.76261 308.086 -2.76261 285.149 20.3446L261.609 43.8543L282.735 64.9524L305.671 41.8451C311.104 36.4198 318.347 33.2045 325.993 32.8027C334.242 32.6018 342.289 35.817 347.923 41.8451C358.989 53.6998 358.587 71.985 346.917 83.2376L331.827 98.3075C324.785 105.34 313.518 105.34 306.476 98.3075C292.794 84.644 228.008 19.9428 228.008 19.9428C216.943 8.89117 202.054 2.66214 186.159 2.66214C170.465 2.66214 155.577 8.89117 144.31 19.9428C139.682 24.5645 135.658 29.9898 132.842 36.0179L131.634 38.6299L155.979 62.9432L157.589 55.5087C161.009 39.6345 176.501 29.588 192.396 33.0036C198.03 34.2091 203.06 36.8216 207.084 40.8399L297.623 131.261L256.177 172.654L195.414 112.172C194.207 110.967 189.177 105.742 163.021 79.4196L100.851 17.3309C77.713 -5.77696 40.2899 -5.77696 17.3535 17.3309C-5.78451 40.4381 -5.78451 77.8122 17.3535 100.719L110.106 193.35C115.136 198.374 121.977 201.187 129.019 201.187C136.262 201.187 142.901 198.374 148.133 193.35L186.763 154.771L165.637 133.673L129.22 170.041L38.6804 79.6205C27.4131 67.9667 27.8155 49.4806 39.4852 38.228C50.7524 27.3773 68.6593 27.3773 79.926 38.228L141.292 99.5131C142.298 100.719 143.505 102.126 144.712 103.331L237.264 195.761C242.294 200.986 249.135 204 256.579 204C256.78 204 256.981 204 256.981 204C264.224 204 270.864 201.187 276.095 196.164L368.646 103.733C391.785 80.8266 391.785 43.2515 368.646 20.3446Z" fill="#ee5e60"/></svg>';
		}

	}

}