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

			// SaaS Blue 现代化风格 + 两边对称布局注入
			$saas_admin_css = '
:root {
  --flavor-like-primary: #2563EB;
  --flavor-like-primary-hover: #1D4ED8;
  --flavor-like-primary-active: #1E40AF;
  --flavor-like-primary-surface: #EFF6FF;
  --flavor-like-border: #E2E8F0;
}
.flavor-like-btn {
  background-color: #2563EB !important;
  border-color: #1D4ED8 !important;
  border-radius: 8px !important;
  font-weight: 600 !important;
  box-shadow: 0 1px 3px rgba(37, 99, 235, 0.2) !important;
  transition: all 0.15s ease !important;
}
.flavor-like-btn:hover, .flavor-like-btn:focus {
  background-color: #1D4ED8 !important;
  border-color: #1E40AF !important;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3) !important;
  transform: translateY(-1px) !important;
}
.flavor-like-settings-notice.flavor-like-notice-control {
  border-radius: 10px !important;
  border: 1px solid #E2E8F0 !important;
  border-inline-start: 4px solid #2563EB !important;
}
.wrap.flavor-like-about, .flavor-like-about {
  max-width: 100% !important;
  width: 100% !important;
  margin: 16px 0 32px 0 !important;
  padding-right: 24px !important;
  box-sizing: border-box !important;
}
.flavor-like-about__layout {
  gap: 24px !important;
}
.flavor-like-about-card {
  border-radius: 12px !important;
  border: 1px solid #E2E8F0 !important;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important;
}
.flavor-like-about__badge {
  background: #EFF6FF !important;
  color: #2563EB !important;
  border: 1px solid #BFDBFE !important;
  border-radius: 9999px !important;
}
.flavor-like-about-card__link {
  color: #2563EB !important;
}
.flavor-like-about-actions__btn.button-primary {
  background: #2563EB !important;
  border-color: #1D4ED8 !important;
  border-radius: 8px !important;
}
';
			wp_add_inline_style( 'flavor-like-admin', $saas_admin_css );

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
:root, .flavor-like-stats-app {
  --flavor-like-stats-color-primary: #2563EB !important;
  --flavor-like-stats-color-primary-rgb: 37, 99, 235 !important;
  --flavor-like-stats-color-primary-darker-10: #1D4ED8 !important;
  --flavor-like-stats-color-primary-darker-20: #1E40AF !important;
  --flavor-like-stats-color-bg-canvas: #F8FAFC !important;
  --flavor-like-stats-color-bg: #FFFFFF !important;
  --flavor-like-stats-color-border: #E2E8F0 !important;
  --flavor-like-stats-color-card-bg: #FFFFFF !important;
  --flavor-like-stats-color-header-bg: #FFFFFF !important;
  --flavor-like-stats-color-sidebar-bg: #FFFFFF !important;
  --flavor-like-stats-color-text: #0F172A !important;
  --flavor-like-stats-color-text-secondary: #475569 !important;
  --flavor-like-stats-color-primary-surface: #EFF6FF !important;
  --flavor-like-stats-color-primary-surface-hover: #DBEAFE !important;
  --flavor-like-stats-color-link: #2563EB !important;
  --flavor-like-stats-color-link-hover: #1D4ED8 !important;
}
html:has(.flavor-like-stats-app){overflow:auto!important}
body:has(.flavor-like-stats-app){overflow:auto!important}
body:has(.flavor-like-stats-app) #wpcontent{overflow:visible!important}
.flavor-like-stats-card, .flavor-like-stats-section { border-radius: 12px !important; }
.flavor-like-stats-pro-preview-btn--primary { background: #2563EB !important; border-radius: 8px !important; }
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
						'logo'      => FLAVOR_LIKE_ASSETS_URL . '/img/icon.svg?ver=' . FLAVOR_LIKE_VERSION,
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

				// SaaS Blue 现代化风格 + 两边对称布局注入（以左边为准：左右间距严格一致 24px，铺满 100% 容器）
				$optiwich_saas_css = '
:root, #optiwich-root {
  --optiwich-color-primary: #2563EB !important;
  --optiwich-color-primary-rgb: 37, 99, 235 !important;
  --optiwich-color-primary-darker-10: #1D4ED8 !important;
  --optiwich-color-primary-darker-20: #1E40AF !important;
  --optiwich-border-width-focus: 2px !important;
  --optiwich-color-primary-surface: #EFF6FF !important;
  --optiwich-color-primary-surface-hover: #DBEAFE !important;
  --optiwich-shadow-focus: 0 0 0 1px #FFFFFF, 0 0 0 3px rgba(37, 99, 235, 0.22) !important;
}
.optiwich-app, #optiwich-root .optiwich-app { background-color: #F8FAFC !important; color: #0F172A !important; }
.optiwich-header { background: #FFFFFF !important; border-bottom: 1px solid #E2E8F0 !important; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important; }
.optiwich-header-content { padding: 0 24px !important; max-width: 100% !important; }
.optiwich-title { color: #0F172A !important; font-weight: 700 !important; }
.optiwich-settings-wrap, #optiwich-root .optiwich-settings-wrap { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 24px 24px 40px !important; box-sizing: border-box !important; }
.optiwich-settings .optiwich-layout { display: grid !important; grid-template-columns: var(--optiwich-settings-sidebar-width, 280px) minmax(0, 1fr) !important; gap: 24px !important; width: 100% !important; box-sizing: border-box !important; }
.optiwich-settings .optiwich-sidebar { background: #FFFFFF !important; border: 1px solid #E2E8F0 !important; border-radius: 12px !important; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important; }
.optiwich-settings .optiwich-sidebar-header { padding: 16px 18px !important; border-bottom: 1px solid #E2E8F0 !important; }
.optiwich-nav-search { padding: 12px 14px 10px !important; border-bottom: 1px solid #F1F5F9 !important; }
.optiwich-nav-search-input { border: 1px solid #E2E8F0 !important; border-radius: 8px !important; background: #F8FAFC !important; }
.optiwich-nav-search-input:focus { background: #FFFFFF !important; border-color: #2563EB !important; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important; }
.optiwich-settings .optiwich-nav { padding: 8px 10px 14px !important; }
.optiwich-settings .optiwich-nav-item { border-radius: 8px !important; margin: 3px 0 !important; padding: 9px 12px !important; color: #475569 !important; font-size: 13.5px !important; font-weight: 500 !important; border: 1px solid transparent !important; }
.optiwich-settings .optiwich-nav-item:hover:not(.active) { background: #F1F5F9 !important; color: #0F172A !important; }
.optiwich-settings .optiwich-nav-item.active { background: #EFF6FF !important; border-color: #BFDBFE !important; color: #2563EB !important; font-weight: 600 !important; }
.optiwich-settings .optiwich-nav-item.active .optiwich-nav-label,
.optiwich-settings .optiwich-nav-item.active .optiwich-nav-icon,
.optiwich-settings .optiwich-nav-item.active .optiwich-nav-arrow { color: #2563EB !important; }
.optiwich-settings .optiwich-content { background: #FFFFFF !important; border: 1px solid #E2E8F0 !important; border-radius: 12px !important; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important; padding: 28px 28px 36px !important; width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; }
.optiwich-settings .optiwich-page { max-width: 100% !important; width: 100% !important; margin: 0 !important; }
.optiwich-settings .optiwich-page-header { border-bottom: 1px solid #E2E8F0 !important; }
.optiwich-settings .optiwich-page-title { color: #0F172A !important; font-weight: 700 !important; }
.optiwich-settings .optiwich-page-lead { color: #64748B !important; max-width: 100% !important; }
.optiwich-image-select, #optiwich-root .optiwich-image-select { display: flex !important; gap: 16px !important; flex-wrap: wrap !important; width: 100% !important; align-items: stretch !important; }
.optiwich-image-select-item, #optiwich-root .optiwich-image-select-item { width: 14rem !important; min-height: 6.25rem !important; border: 1.5px solid #E2E8F0 !important; border-radius: 12px !important; background: #FFFFFF !important; padding: 14px 18px !important; cursor: pointer !important; position: relative !important; display: flex !important; align-items: center !important; justify-content: center !important; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important; box-sizing: border-box !important; }
@media (max-width: 767px) { .optiwich-image-select-item, #optiwich-root .optiwich-image-select-item { width: calc(50% - 8px) !important; min-height: 5.5rem !important; padding: 10px !important; } }
.optiwich-image-select-item:hover:not(.locked) { border-color: #93C5FD !important; background: #F8FAFC !important; transform: translateY(-2px) !important; box-shadow: 0 8px 18px rgba(37, 99, 235, 0.1) !important; }
.optiwich-image-select-item.active { border: 2px solid #2563EB !important; background: #EFF6FF !important; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18), 0 6px 16px rgba(37, 99, 235, 0.12) !important; border-radius: 12px !important; transform: translateY(-1px) !important; }
.optiwich-image-select-item.active:after { content: "\\2713" !important; position: absolute !important; top: 6px !important; right: 6px !important; width: 18px !important; height: 18px !important; border-radius: 50% !important; background: #2563EB !important; color: #FFFFFF !important; font-size: 11px !important; font-weight: 700 !important; display: flex !important; align-items: center !important; justify-content: center !important; line-height: 1 !important; border: none !important; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.35) !important; z-index: 2 !important; }
[dir="rtl"] .optiwich-image-select-item.active:after { right: auto !important; left: 6px !important; }
.optiwich-image-select-item-image, #optiwich-root .optiwich-image-select-item-image { width: 100% !important; height: 100% !important; display: flex !important; align-items: center !important; justify-content: center !important; }
.optiwich-image-select-item img, #optiwich-root .optiwich-image-select-item img { display: block !important; max-width: 11rem !important; max-height: 3.4rem !important; width: 100% !important; height: auto !important; object-fit: contain !important; filter: drop-shadow(0 2px 5px rgba(15, 23, 42, 0.08)) !important; transition: transform 0.2s ease !important; }
.optiwich-image-select-item:hover img { transform: scale(1.05) !important; }
.optiwich-switcher-slider { background-color: #CBD5E1 !important; border-radius: 9999px !important; }
.optiwich-switcher-input:checked + .optiwich-switcher-slider { background: #2563EB !important; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3) !important; }
.optiwich-settings .optiwich-input, .optiwich-settings .optiwich-textarea, .optiwich-settings .optiwich-select, #optiwich-root input.optiwich-input, #optiwich-root textarea.optiwich-textarea, #optiwich-root select.optiwich-select { border: 1px solid #E2E8F0 !important; border-radius: 8px !important; background: #FFFFFF !important; padding: 8px 12px !important; font-size: 13.5px !important; }
.optiwich-settings .optiwich-input:focus, .optiwich-settings .optiwich-textarea:focus, .optiwich-settings .optiwich-select:focus { border-color: #2563EB !important; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18) !important; }
.optiwich-settings .optiwich-settings-footer { background: #F8FAFC !important; border: 1px solid #E2E8F0 !important; border-radius: 10px !important; padding: 14px 20px !important; }
.optiwich-settings .optiwich-settings-footer--dirty { background: #EFF6FF !important; border-color: #BFDBFE !important; }
.optiwich-settings .optiwich-wp-button { border-radius: 8px !important; font-size: 13.5px !important; font-weight: 500 !important; padding: 7px 16px !important; }
.optiwich-settings .optiwich-wp-button--primary { background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%) !important; border: 1px solid #1D4ED8 !important; color: #FFFFFF !important; font-weight: 600 !important; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25) !important; }
.optiwich-settings .optiwich-wp-button--primary:hover:not(:disabled) { background: linear-gradient(135deg, #1D4ED8 0%, #1E40AF 100%) !important; border-color: #1E40AF !important; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35) !important; transform: translateY(-1px) !important; }
.optiwich-settings .optiwich-wp-button--secondary { background: #FFFFFF !important; border: 1px solid #CBD5E1 !important; color: #334155 !important; }
';
				wp_add_inline_style( 'flavor-like-optiwich', $optiwich_saas_css );

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
						'logo'      => FLAVOR_LIKE_ASSETS_URL . '/img/logo.svg?ver=' . FLAVOR_LIKE_VERSION,
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
			return '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/></svg>';
		}

	}

}