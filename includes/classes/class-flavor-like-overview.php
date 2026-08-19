<?php
/**
 * Overview screen data, health checks, and settings import/export.
 *
 * @package WP_Flavor Like
 * @since   5.0.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Flavor_Like_Overview' ) ) {

	/**
	 * Overview page and related admin helpers.
	 */
	class Flavor_Like_Overview {

		/**
		 * About admin screen URL.
		 *
		 * @return string
		 */
		public static function get_about_url() {
			return admin_url( 'admin.php?page=flavor-like-about' );
		}

		/**
		 * Settings screen URL with Optiwich tab slug.
		 *
		 * @param string $tab     Section id (e.g. general, content-types).
		 * @param string $section Optional nested section id (e.g. posts_group).
		 * @return string
		 */
		public static function get_settings_url( $tab = 'general', $section = '' ) {
			$query = array(
				'page'          => 'flavor-like-settings',
				'settings-page' => sanitize_key( $tab ),
			);

			if ( ! empty( $section ) ) {
				$query['settings-section'] = sanitize_key( $section );
			}

			return admin_url( 'admin.php?' . http_build_query( $query, '', '&' ) );
		}

		/**
		 * Pro upsell content removed - returns empty array.
		 *
		 * @param array $health Health report.
		 * @return array
		 */
		public static function get_pro_upsell_content( $health ) {
			return array();
		}

		/**
		 * View-model for the About admin template (free + Pro via filters).
		 *
		 * @return array
		 */
		public static function get_about_view_data() {
			$health     = self::get_health_report();
			$health     = self::apply_live_tables_health( $health );
			$is_pro     = false;
			$pro_label  = '';

			$quick_actions = array(
				array(
					'label'  => esc_html__( 'Settings', 'flavor-like' ),
					'url'    => self::get_settings_url( 'content-types' ),
					'icon'   => 'admin-settings',
					'primary'=> false,
				),
				array(
					'label'  => esc_html__( 'Customize buttons', 'flavor-like' ),
					'url'    => admin_url( 'admin.php?page=flavor-like-customize' ),
					'icon'   => 'admin-appearance',
					'primary'=> false,
				),
				array(
					'label'  => esc_html__( 'Statistics', 'flavor-like' ),
					'url'    => admin_url( 'admin.php?page=flavor-like-statistics' ),
					'icon'   => 'chart-bar',
					'primary'=> false,
				),
			);

			if ( ! empty( $health['preview_url'] ) ) {
				$quick_actions[] = array(
					'label'   => esc_html__( 'View on site', 'flavor-like' ),
					'url'     => $health['preview_url'],
					'icon'    => 'visibility',
					'primary' => true,
					'external'=> true,
				);
			}

			if ( $is_pro ) {
				$quick_actions[] = array(
					'label'   => esc_html__( 'Pro tools', 'flavor-like' ),
					'url'     => admin_url( 'admin.php?page=flavor-like-pro-tools' ),
					'icon'    => 'admin-tools',
					'primary' => false,
				);
			}

			$quick_actions = apply_filters( 'flavor_like_about_quick_actions', $quick_actions, $health );

			// Pro can inject optional module cards via filter; none by default.
			$pro_modules = apply_filters( 'flavor_like_about_pro_modules', array(), $health );

			$status_rows = array(
				array(
					'group'  => 'engagement',
					'label'  => esc_html__( 'Votes today', 'flavor-like' ),
					'value'  => number_format_i18n( (int) ( $health['today_votes'] ?? 0 ) ),
					'state'  => ( (int) ( $health['today_votes'] ?? 0 ) ) > 0 ? 'good' : 'neutral',
				),
				array(
					'group'  => 'engagement',
					'label'  => esc_html__( 'New since last visit', 'flavor-like' ),
					'value'  => number_format_i18n( (int) ( $health['new_votes'] ?? 0 ) ),
					'state'  => ( (int) ( $health['new_votes'] ?? 0 ) ) > 0 ? 'good' : 'neutral',
				),
				array(
					'group'  => 'engagement',
					'label'  => esc_html__( 'Total votes', 'flavor-like' ),
					'value'  => number_format_i18n( (int) ( $health['log_count'] ?? 0 ) ),
					'state'  => ( (int) ( $health['log_count'] ?? 0 ) ) > 0 ? 'good' : 'neutral',
					'hint'   => esc_html__( 'Snapshot only—open Statistics for charts.', 'flavor-like' ),
				),
				array(
					'group'  => 'setup',
					'label'  => esc_html__( 'Posts', 'flavor-like' ),
					'value'  => ! empty( $health['auto_display'] ) ? esc_html__( 'Auto-display on', 'flavor-like' ) : esc_html__( 'Off / manual', 'flavor-like' ),
					'state'  => ! empty( $health['auto_display'] ) ? 'good' : 'neutral',
				),
				array(
					'group'  => 'setup',
					'label'  => esc_html__( 'Comments', 'flavor-like' ),
					'value'  => ! empty( $health['comments_auto_display'] ) ? esc_html__( 'Auto-display on', 'flavor-like' ) : esc_html__( 'Off', 'flavor-like' ),
					'state'  => ! empty( $health['comments_auto_display'] ) ? 'good' : 'neutral',
				),
				array(
					'group'  => 'setup',
					'label'  => esc_html__( 'Database', 'flavor-like' ),
					'value'  => ! empty( $health['tables_ok'] ) ? esc_html__( 'Ready', 'flavor-like' ) : esc_html__( 'Needs attention', 'flavor-like' ),
					'state'  => ! empty( $health['tables_ok'] ) ? 'good' : 'bad',
					'hint'   => ! empty( $health['missing_tables'] )
						? sprintf(
							/* translators: %s: comma-separated table labels */
							esc_html__( 'Missing: %s', 'flavor-like' ),
							esc_html( implode( ', ', (array) $health['missing_tables'] ) )
						)
						: '',
				),
			);

			// Minimal Site Health signal (replaces the old Overview Health card).
			if ( class_exists( 'Flavor_Like_Health' ) ) {
				$glance = Flavor_Like_Health::get_overview_glance_status();
				if ( ! empty( $glance ) ) {
					$status_rows[] = array(
						'group'  => 'setup',
						'label'  => esc_html__( 'Status', 'flavor-like' ),
						'value'  => $glance['value'],
						'state'  => $glance['state'],
						'hint'   => $glance['hint'],
					);
				}
			}

			if ( ! empty( $health['cache_enabled'] ) ) {
				$status_rows[] = array(
					'group'  => 'setup',
					'label'  => esc_html__( 'Caching', 'flavor-like' ),
					'value'  => esc_html__( 'Compatibility mode on', 'flavor-like' ),
					'state'  => 'good',
				);
			}

			if ( class_exists( 'Flavor_Like_Pulse_Admin' ) ) {
				$storage_upgrade = Flavor_Like_Pulse_Admin::get_help_card_data();
				if ( ! empty( $storage_upgrade ) && 'migrate' === ( $storage_upgrade['phase'] ?? '' ) ) {
					$status_rows[] = array(
						'group'  => 'setup',
						'label'  => esc_html__( 'Like storage', 'flavor-like' ),
						'value'  => $storage_upgrade['status'] ?? '',
						'state'  => $storage_upgrade['state'] ?? 'neutral',
						'hint'   => $storage_upgrade['progress'] ?? '',
					);
				}
			} else {
				$storage_upgrade = null;
			}

			$status_rows = apply_filters( 'flavor_like_about_status_rows', $status_rows, $health );

			// Supplementary setup hint last (free installs with no votes yet).
			if ( empty( $health['is_pro'] ) && (int) ( $health['log_count'] ?? 0 ) === 0 ) {
				$status_rows[] = array(
					'group'  => 'setup',
					'label'  => esc_html__( 'Quick check', 'flavor-like' ),
					'value'  => esc_html__( 'Test on a single post, not the homepage', 'flavor-like' ),
					'state'  => 'neutral',
					'hint'   => ! empty( $health['post_template_name'] )
						? sprintf(
							/* translators: 1: template name, 2: button position */
							esc_html__( 'Template: %1$s · Position: %2$s', 'flavor-like' ),
							$health['post_template_name'],
							$health['post_button_position'] ?? ''
						)
						: '',
				);
			}

			$help_links = array(
				array(
					'title' => esc_html__( 'Documentation', 'flavor-like' ),
					'desc'  => esc_html__( 'Setup, shortcodes, and developer hooks.', 'flavor-like' ),
					'url'   => 'https://github.com/Jacky088/flavor-like?utm_source=about-page&utm_medium=wp-dash',
					'icon'  => 'book',
				),
				array(
					'title' => esc_html__( 'Free vs Pro breakdown', 'flavor-like' ),
					'desc'  => esc_html__( 'Compare free and Pro features before you upgrade.', 'flavor-like' ),
					'url'   => add_query_arg(
						array(
							'utm_source'   => 'about-page',
							'utm_campaign' => 'free-vs-pro',
							'utm_medium'   => 'wp-dash',
						),
						FLAVOR_LIKE_PLUGIN_URI . 'upgrade/'
					),
					'icon'  => 'star-filled',
				),
				array(
					'title' => esc_html__( 'Support', 'flavor-like' ),
					'desc'  => esc_html__( 'Get help from the Flavor Like team.', 'flavor-like' ),
					'url'   => add_query_arg(
						array(
							'utm_source'   => 'about-page',
							'utm_campaign' => 'help-link',
							'utm_medium'   => 'wp-dash',
						),
						FLAVOR_LIKE_PLUGIN_URI . 'support/'
					),
					'icon'  => 'sos',
				),
				array(
					'title' => esc_html__( 'Leave a review', 'flavor-like' ),
					'desc'  => esc_html__( 'Share feedback on WordPress.org.', 'flavor-like' ),
					'url'   => 'https://wordpress.org/support/plugin/flavor-like/reviews/?filter=5',
					'icon'  => 'star-filled',
				),
			);

			$help_links = apply_filters( 'flavor_like_about_help_links', $help_links );

			$upsell = ! $is_pro ? self::get_pro_upsell_content( $health ) : array();

			$summary = apply_filters( 'flavor_like_about_summary', self::get_overview_summary( $health ), $health );

			if ( ! isset( $storage_upgrade ) ) {
				$storage_upgrade = class_exists( 'Flavor_Like_Pulse_Admin' ) ? Flavor_Like_Pulse_Admin::get_help_card_data() : null;
			}

			return array(
				'health'                 => $health,
				'is_pro'                 => $is_pro,
				'pro_version'            => $pro_label,
				'summary'                => $summary,
				'status_groups'          => self::get_status_group_labels(),
				'quick_actions'          => $quick_actions,
				'pro_modules'            => $pro_modules,
				'status_rows'            => $status_rows,
				'storage_upgrade'        => $storage_upgrade,
				'help_links'             => $help_links,
				'troubleshooting'        => self::get_troubleshooting_tips( $health ),
				'sidebar_meta'           => apply_filters( 'flavor_like_about_sidebar_meta', self::get_default_sidebar_meta( $health ), $health ),
				'wp_version'             => get_bloginfo( 'version' ),
				'import_nonce'           => wp_create_nonce( 'flavor_like_import_settings' ),
				'export_url'             => wp_nonce_url( admin_url( 'admin-ajax.php?action=flavor_like_export_settings' ), 'flavor_like_export_settings' ),
				'show_pro_upsell'        => apply_filters( 'flavor_like_about_show_pro_upsell', ! $is_pro ),
				'pro_upsell'             => $upsell,
				'upgrade_url'            => add_query_arg(
					array(
						'utm_source'   => 'overview',
						'utm_campaign' => 'gopro',
						'utm_medium'   => 'wp-dash',
					),
					FLAVOR_LIKE_PLUGIN_URI . 'upgrade/'
				),
				'repair_tables_url'      => wp_nonce_url(
					admin_url( 'admin-post.php?action=flavor_like_repair_tables' ),
					'flavor_like_repair_tables'
				),
				'flush_stats_cache_url'  => wp_nonce_url(
					admin_url( 'admin-post.php?action=flavor_like_flush_stats_cache' ),
					'flavor_like_flush_stats_cache'
				),
				'backup_intro'           => apply_filters(
					'flavor_like_backup_intro',
					__( 'Download your settings and customizer values as JSON.', 'flavor-like' )
				),
				'backup_import_confirm'  => apply_filters(
					'flavor_like_backup_import_confirm',
					__( 'Import will replace your current Flavor Like settings and customizer values. Continue?', 'flavor-like' )
				),
			);
		}

		/**
		 * Short troubleshooting tips for the Overview advanced panel.
		 *
		 * @param array $health Health report.
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_troubleshooting_tips( $health ) {
			$content_types_url = self::get_settings_url( 'content-types' );
			$general_url       = self::get_settings_url( 'general' );

			$tips = array(
				array(
					'text' => esc_html( 'No votes yet? Open a published post (not the homepage) and click the like button once to confirm everything works.' ),
					'url'  => ! empty( $health['preview_url'] ) ? $health['preview_url'] : '',
					'link' => esc_html__( 'View sample post', 'flavor-like' ),
				),
				array(
					'text' => esc_html( 'Likes usually show on single posts, not on the homepage or archives. Test on a post or change display in Settings.' ),
					'url'  => $content_types_url,
					'link' => esc_html__( 'Content Types', 'flavor-like' ),
				),
				array(
					'text' => sprintf(
						/* translators: %s: Automatic Display setting label */
						esc_html__( 'No button on the page? Add [flavor_like] or the Flavor Like block, or enable “%s” under Settings → Posts.', 'flavor-like' ),
						esc_html__( 'Automatic Display', 'flavor-like' )
					),
					'url'  => $content_types_url,
					'link' => esc_html__( 'Content Types', 'flavor-like' ),
				),
				array(
					'text' => sprintf(
						/* translators: %s: Site Uses Caching setting label */
						esc_html__( 'Stale vote counts with a cache plugin? Enable “%s” in Settings → General, then purge cache.', 'flavor-like' ),
						esc_html__( 'Site Uses Caching', 'flavor-like' )
					),
					'url'  => $general_url,
					'link' => esc_html__( 'General', 'flavor-like' ),
				),
				array(
					'text' => sprintf(
						/* translators: %s: Hide Plugin Admin Notices setting label */
						esc_html__( 'Too many admin notices? Turn on “%s” in Settings → General.', 'flavor-like' ),
						esc_html__( 'Hide Plugin Admin Notices', 'flavor-like' )
					),
					'url'  => $general_url,
					'link' => esc_html__( 'General', 'flavor-like' ),
				),
			);

			if ( empty( $health['tables_ok'] ) ) {
				$tips[] = array(
					'text' => esc_html( 'Database tables may be incomplete. Use “Repair database tables” on Overview, or deactivate and reactivate Flavor Like once.' ),
					'url'  => self::get_about_url(),
					'link' => esc_html__( 'Open Overview', 'flavor-like' ),
				);
			}

			return apply_filters( 'flavor_like_overview_troubleshooting_tips', $tips, $health );
		}

		/**
		 * Bootstrap hooks.
		 *
		 * @return void
		 */
		public static function init() {
			if ( ! is_admin() ) {
				return;
			}

			add_action( 'wp_ajax_flavor_like_export_settings', array( __CLASS__, 'handle_export_settings' ) );
			add_action( 'admin_post_flavor_like_import_settings', array( __CLASS__, 'handle_import_settings' ) );
			add_action( 'admin_post_flavor_like_repair_tables', array( __CLASS__, 'handle_repair_tables' ) );
		}

		/**
		 * AJAX: download settings JSON.
		 *
		 * @return void
		 */
		public static function handle_export_settings() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'flavor-like' ) );
			}

			check_admin_referer( 'flavor_like_export_settings' );

			$filename = 'flavor-like-settings-' . gmdate( 'Y-m-d' ) . '.json';

			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

			echo self::export_settings_json(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON export
			exit;
		}

		/**
		 * Import settings from uploaded JSON.
		 *
		 * @return void
		 */
		public static function handle_import_settings() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'flavor-like' ) );
			}

			check_admin_referer( 'flavor_like_import_settings' );

			$redirect = admin_url( 'admin.php?page=flavor-like-about' );

			if (
				empty( $_FILES['settings_file']['tmp_name'] )
				|| ! is_uploaded_file( $_FILES['settings_file']['tmp_name'] )
				|| ( isset( $_FILES['settings_file']['error'] ) && UPLOAD_ERR_OK !== (int) $_FILES['settings_file']['error'] )
			) {
				wp_safe_redirect( add_query_arg( 'flavor_like_import', 'error_upload', $redirect ) );
				exit;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- user upload.
			$raw     = file_get_contents( $_FILES['settings_file']['tmp_name'] );
			$payload = json_decode( $raw, true );

			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
				wp_safe_redirect( add_query_arg( 'flavor_like_import', 'error_json', $redirect ) );
				exit;
			}

			$result = self::import_settings( $payload );

			wp_safe_redirect(
				add_query_arg(
					'flavor_like_import',
					is_wp_error( $result ) ? 'error_payload' : 'success',
					$redirect
				)
			);
			exit;
		}

		/**
		 * Repair missing database tables from Help.
		 *
		 * @return void
		 */
		public static function handle_repair_tables() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'flavor-like' ) );
			}

			check_admin_referer( 'flavor_like_repair_tables' );

			$report = self::repair_database_tables();
			$status = ! empty( $report['tables_ok'] ) ? 'success' : 'failed';

			wp_safe_redirect(
				add_query_arg(
					'flavor_like_repair',
					$status,
					self::get_about_url()
				)
			);
			exit;
		}

		/**
		 * Clear versioned statistics caches from Help.
		 *
		 * @return void
		 */
		public static function handle_flush_stats_cache() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'flavor-like' ) );
			}

			check_admin_referer( 'flavor_like_flush_stats_cache' );

			Flavor_Like_Query_Cache::flush_stats();
			delete_transient( self::get_health_report_cache_key() );

			wp_safe_redirect(
				add_query_arg(
					'flavor_like_stats_cache',
					'flushed',
					self::get_about_url()
				)
			);
			exit;
		}

		/**
		 * Create any missing Flavor Like database tables.
		 *
		 * @return array{tables_ok: bool, missing_tables: string[]}
		 */
		public static function repair_database_tables() {
			flavor_like_activator::get_instance()->install_tables();
			delete_transient( self::get_health_report_cache_key() );

			return self::get_tables_health();
		}

		/**
		 * Required database tables (health, repair, Site Health).
		 *
		 * @return array<string, string> Label => full table name.
		 */
		public static function get_required_tables() {
			if ( flavor_like_use_pulse_queries() ) {
				return Flavor_Like_Pulse_Log_Bridge::get_storage_tables();
			}

			return Flavor_Like_Pulse_Registry::legacy_health_tables();
		}

		/**
		 * Check whether a table exists.
		 *
		 * @param string $table_name Full table name.
		 * @return bool
		 */
		public static function table_exists( $table_name ) {
			global $wpdb;

			$result = $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
			);

			return $result === $table_name;
		}

		/**
		 * Plain-language Overview summary (may contain safe HTML links).
		 *
		 * @param array $health Health report.
		 * @return string
		 */
		public static function get_overview_summary( $health ) {
			$today     = (int) ( $health['today_votes'] ?? 0 );
			$new       = (int) ( $health['new_votes'] ?? 0 );
			$total     = (int) ( $health['log_count'] ?? 0 );
			$stats_url = esc_url( $health['statistics_url'] ?? admin_url( 'admin.php?page=flavor-like-statistics' ) );

			if ( $new > 0 ) {
				return wp_kses_post(
					sprintf(
						/* translators: 1: vote count, 2: statistics admin URL */
						__( 'You have <strong>%1$s</strong> new votes since your last visit to Statistics. <a href="%2$s">Open Statistics</a> for charts and filters.', 'flavor-like' ),
						number_format_i18n( $new ),
						$stats_url
					)
				);
			}

			if ( $today > 0 ) {
				return wp_kses_post(
					sprintf(
						/* translators: 1: votes today, 2: statistics admin URL */
						__( '<strong>%1$s</strong> votes today on your site. Numbers below are a quick snapshot—<a href="%2$s">Statistics</a> has the full breakdown.', 'flavor-like' ),
						number_format_i18n( $today ),
						$stats_url
					)
				);
			}

			if ( ! empty( $health['auto_display'] ) && $total > 0 ) {
				return sprintf(
					/* translators: %s: total vote count */
					esc_html( 'Buttons are active on posts and you have %s total votes stored. Use Statistics when you need date ranges and detailed reports.' ),
					number_format_i18n( $total )
				);
			}

			if ( empty( $health['auto_display'] ) ) {
				return esc_html( 'Like buttons are not on posts automatically yet. Turn on auto-display in Settings, or add the Flavor Like block / shortcode where you want votes.' );
			}

			if ( (int) ( $health['log_count'] ?? 0 ) === 0 ) {
				$preview          = ! empty( $health['preview_url'] ) ? $health['preview_url'] : '';
				$content_types_url = esc_url( self::get_settings_url( 'content-types' ) );

				if ( $preview ) {
					return wp_kses_post(
						sprintf(
							/* translators: 1: sample post URL, 2: Content Types settings URL */
							__( 'No votes recorded yet. Test the button on a <a href="%1$s" target="_blank" rel="noopener noreferrer">single post</a> (not the homepage). Adjust display under <a href="%2$s">Settings → Content Types → Posts</a>.', 'flavor-like' ),
							esc_url( $preview ),
							$content_types_url
						)
					);
				}

				return wp_kses_post(
					sprintf(
						/* translators: %s: Content Types settings URL */
						__( 'No votes recorded yet. Buttons show on single posts by default, not on the homepage. Check display under <a href="%s">Settings → Content Types → Posts</a>.', 'flavor-like' ),
						$content_types_url
					)
				);
			}

			return esc_html__( 'Your setup looks ready. Configure buttons below or open Statistics when you start receiving votes.', 'flavor-like' );
		}

		/**
		 * Status row group labels for the Overview grid.
		 *
		 * @return array<string, string>
		 */
		public static function get_status_group_labels() {
			return apply_filters(
				'flavor_like_about_status_group_labels',
				array(
					'engagement' => esc_html__( 'Engagement snapshot', 'flavor-like' ),
					'setup'      => esc_html__( 'Site setup', 'flavor-like' ),
					'pro'        => esc_html__( 'Flavor Like Pro', 'flavor-like' ),
				)
			);
		}

		/**
		 * Plugins / features detected on this site (for sidebar).
		 *
		 * @return array<int, string>
		 */
		public static function get_detected_integrations() {
			$active = array(
				esc_html__( 'Posts', 'flavor-like' ),
				esc_html__( 'Comments', 'flavor-like' ),
			);

			if ( class_exists( 'WooCommerce' ) ) {
				$active[] = 'WooCommerce';
			}
			if ( function_exists( 'buddypress' ) ) {
				$active[] = 'BuddyPress';
			}
			if ( function_exists( 'is_bbpress' ) ) {
				$active[] = 'bbPress';
			}
			if ( class_exists( 'Easy_Digital_Downloads' ) ) {
				$active[] = 'EDD';
			}

			return apply_filters( 'flavor_like_about_detected_integrations', $active );
		}

		/**
		 * Default sidebar meta rows (before Pro filter).
		 *
		 * @param array $health Health report.
		 * @return array<int, array<string, string>>
		 */
		public static function get_default_sidebar_meta( $health ) {
			$meta = array(
				array(
					'label' => esc_html__( 'PHP', 'flavor-like' ),
					'value' => PHP_VERSION,
				),
			);

			$integrations = self::get_detected_integrations();
			if ( ! empty( $integrations ) ) {
				$meta[] = array(
					'label' => esc_html__( 'Detected on site', 'flavor-like' ),
					'value' => implode( ', ', $integrations ),
				);
			}

			if ( empty( $health['is_pro'] ) && ! empty( $health['active_theme'] ) ) {
				$meta[] = array(
					'label' => esc_html__( 'Active theme', 'flavor-like' ),
					'value' => $health['active_theme'],
				);
			}

			if ( ! empty( $health['cache_enabled'] ) ) {
				$meta[] = array(
					'label' => esc_html__( 'Caching helper', 'flavor-like' ),
					'value' => esc_html__( 'On (Settings → General)', 'flavor-like' ),
					'url'   => self::get_settings_url( 'general' ),
				);
			}

			$meta[] = array(
				'label' => esc_html__( 'Documentation', 'flavor-like' ),
				'value' => esc_html__( 'Setup & hooks', 'flavor-like' ),
				'url'   => 'https://github.com/Jacky088/flavor-like?utm_source=overview-sidebar&utm_medium=wp-dash',
			);

			return $meta;
		}

		/**
		 * Group status rows for template rendering.
		 *
		 * @param array $rows Status rows.
		 * @return array<string, array<int, array>>
		 */
		public static function group_status_rows( $rows ) {
			$grouped = array();

			foreach ( (array) $rows as $row ) {
				$key = isset( $row['group'] ) ? $row['group'] : 'setup';
				if ( ! isset( $grouped[ $key ] ) ) {
					$grouped[ $key ] = array();
				}
				$grouped[ $key ][] = $row;
			}

			return $grouped;
		}

		/**
		 * Label for posts logging method.
		 *
		 * @param string $method Raw method key.
		 * @return string
		 */
		public static function format_logging_method_label( $method ) {
			return flavor_like_get_logging_method_label( $method );
		}

		/**
		 * Human-readable post button placement summary for Help.
		 *
		 * @return string
		 */
		public static function get_post_display_summary() {
			if ( ! flavor_like_setting_repo::isAutoDisplayOn( 'post' ) ) {
				return esc_html__( 'Manual (shortcode or block)', 'flavor-like' );
			}

			$parts   = array( esc_html__( 'Auto on posts', 'flavor-like' ) );
			$hidden  = flavor_like_setting_repo::getPostAutoDisplayFilters();
			$hide_map = array(
				'home'     => esc_html__( 'homepage hidden', 'flavor-like' ),
				'single'   => esc_html__( 'singular views filtered', 'flavor-like' ),
				'archive'  => esc_html__( 'archives hidden', 'flavor-like' ),
				'category' => esc_html__( 'categories hidden', 'flavor-like' ),
				'search'   => esc_html__( 'search hidden', 'flavor-like' ),
				'tag'      => esc_html__( 'tags hidden', 'flavor-like' ),
				'author'   => esc_html__( 'author pages hidden', 'flavor-like' ),
			);

			foreach ( (array) $hidden as $key ) {
				if ( isset( $hide_map[ $key ] ) ) {
					$parts[] = $hide_map[ $key ];
				}
			}

			$post_types = flavor_like_setting_repo::getPostTypesFilterList();
			if ( ! empty( $post_types ) && is_array( $post_types ) ) {
				$parts[] = sprintf(
					/* translators: %s: comma-separated post type slugs */
					esc_html__( 'exceptions: %s', 'flavor-like' ),
					implode( ', ', array_map( 'sanitize_key', $post_types ) )
				);
			}

			return implode( ' · ', $parts );
		}

		/**
		 * Label for posts auto-display position setting.
		 *
		 * @return string
		 */
		public static function get_post_button_position_label() {
			return flavor_like_get_post_auto_display_position_label(
				flavor_like_get_option( 'posts_group|auto_display_position', 'bottom' )
			);
		}

		/**
		 * Active post template display name.
		 *
		 * @return string
		 */
		public static function get_post_template_label() {
			$key       = flavor_like_get_option( 'posts_group|template', 'flavorlike-default' );
			$templates = function_exists( 'flavor_like_generate_templates_list' ) ? flavor_like_generate_templates_list() : array();

			if ( isset( $templates[ $key ]['name'] ) ) {
				return $templates[ $key ]['name'];
			}

			return $key;
		}

		/**
		 * Merge a live table check into a health report (Help / Site Health must not use stale cache).
		 *
		 * @param array $health Health report.
		 * @return array
		 */
		public static function apply_live_tables_health( $health ) {
			$tables_health = self::get_tables_health();

			$health['tables_ok']      = $tables_health['tables_ok'];
			$health['missing_tables'] = $tables_health['missing_tables'];

			return $health;
		}

		/**
		 * Transient key for cached Help page health report.
		 *
		 * @return string
		 */
	private static function get_health_report_cache_key() {
		// Version the key with the pulse cache version so any vote/engagement
		// bump (Flavor_Like_Query_Cache::bump) invalidates the health report
		// immediately instead of waiting up to 5 minutes.
		$version = class_exists( 'Flavor_Like_Query_Cache' ) ? Flavor_Like_Query_Cache::version() : 1;
		return 'flavor_like_health_report_v1_' . $version;
	}

		/**
		 * Lightweight database table check (Site Health and similar).
		 *
		 * @return array{tables_ok: bool, missing_tables: string[]}
		 */
		public static function get_tables_health() {
			$tables_ok = true;
			$missing   = array();

			foreach ( self::get_required_tables() as $label => $table_name ) {
				if ( ! self::table_exists( $table_name ) ) {
					$tables_ok = false;
					$missing[] = $label;
				}
			}

			return array(
				'tables_ok'      => $tables_ok,
				'missing_tables' => $missing,
			);
		}

		/**
		 * Run health diagnostics for the About page Quick Start section.
		 *
		 * @param bool $force_refresh Bypass cached report.
		 * @return array
		 */
		public static function get_health_report( $force_refresh = false ) {
			if ( ! $force_refresh ) {
				$cached = get_transient( self::get_health_report_cache_key() );
				if ( is_array( $cached ) ) {
					return $cached;
				}
			}

			$tables_health = self::get_tables_health();

			$auto_display = flavor_like_setting_repo::isAutoDisplayOn( 'post' );
			$sample_post  = get_posts(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'no_found_rows'  => true,
				)
			);
			$preview_url  = ! empty( $sample_post[0] ) ? get_permalink( $sample_post[0]->ID ) : '';

			$new_votes = 0;
			if ( function_exists( 'flavor_like_get_number_of_new_likes' ) ) {
				$new_votes = (int) flavor_like_get_number_of_new_likes();
			}

			$cache_enabled = false;
			if ( function_exists( 'flavor_like_get_option' ) ) {
				$cache_enabled = flavor_like_is_true( flavor_like_get_option( 'cache_exist', false ) );
			}

			$theme = wp_get_theme();

			$report = array(
				'tables_ok'              => $tables_health['tables_ok'],
				'missing_tables'         => $tables_health['missing_tables'],
				'is_pro'                 => false,
				'auto_display'           => $auto_display,
				'comments_auto_display'  => flavor_like_setting_repo::isAutoDisplayOn( 'comment' ),
				'preview_url'            => $preview_url,
				'plugin_version'         => FLAVOR_LIKE_VERSION,
				'db_version'             => get_option( 'flavor_like_dbVersion', FLAVOR_LIKE_DB_VERSION ),
				'log_count'              => flavor_like_count_all_logs(),
				'today_votes'            => flavor_like_count_all_logs( 'today' ),
				'new_votes'              => $new_votes,
				'cache_enabled'          => $cache_enabled,
				'statistics_url'         => admin_url( 'admin.php?page=flavor-like-statistics' ),
				'post_display_summary'   => self::get_post_display_summary(),
				'post_template_name'     => self::get_post_template_label(),
				'post_button_position'   => self::get_post_button_position_label(),
				'logging_method'         => flavor_like_setting_repo::getMethod( 'post' ),
				'toast_notices'          => flavor_like_is_true( flavor_like_get_option( 'enable_toast_notice', true ) ),
				'active_theme'           => $theme->get( 'Name' ),
				'content_types_settings_url' => self::get_settings_url( 'content-types' ),
				'general_settings_url'       => self::get_settings_url( 'general' ),
			);

			set_transient( self::get_health_report_cache_key(), $report, 5 * MINUTE_IN_SECONDS );

			return $report;
		}

		/**
		 * Export plugin settings as JSON (settings API option blob).
		 *
		 * @return string
		 */
		public static function export_settings_json() {
			$settings  = get_option( 'flavor_like_settings', array() );
			$customize = get_option( 'flavor_like_customize', array() );

			if ( class_exists( 'flavor_like_settings_api' ) ) {
				$settings_api = new flavor_like_settings_api();
				$settings     = $settings_api->get_settings();
			}

			if ( class_exists( 'flavor_like_customizer_api' ) ) {
				$customizer_api = new flavor_like_customizer_api();
				$customize      = $customizer_api->get_values();
			}

			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			if ( ! is_array( $customize ) ) {
				$customize = array();
			}

			$data = array(
				'version'   => FLAVOR_LIKE_VERSION,
				'exported'  => gmdate( 'c' ),
				'settings'  => $settings,
				'customize' => $customize,
			);

			/**
			 * Extend the Help backup export payload.
			 *
			 * @param array $data Export payload (settings, customize, optional extensions).
			 */
			$data = apply_filters( 'flavor_like_backup_export_payload', $data );

			return wp_json_encode( $data, JSON_PRETTY_PRINT );
		}

		/**
		 * Import settings from decoded JSON array.
		 *
		 * @param array $payload Import payload.
		 * @return true|WP_Error
		 */
		public static function import_settings( $payload ) {
			if ( ! array_key_exists( 'settings', $payload ) || ! is_array( $payload['settings'] ) ) {
				return new WP_Error( 'invalid_payload', esc_html__( 'Invalid settings file. Expected a JSON export from Help → Settings backup.', 'flavor-like' ) );
			}

			$settings = $payload['settings'];

			if ( class_exists( 'flavor_like_settings_api' ) ) {
				$settings_api = new flavor_like_settings_api();
				$settings     = $settings_api->sanitize_import_values( $settings );
			}

			update_option( 'flavor_like_settings', $settings );

			if ( ! empty( $payload['customize'] ) && is_array( $payload['customize'] ) ) {
				$customize = $payload['customize'];

				if ( class_exists( 'flavor_like_customizer_api' ) ) {
					$customizer_api = new flavor_like_customizer_api();
					$customize      = $customizer_api->sanitize_import_values( $customize );
				}

				update_option( 'flavor_like_customize', $customize );
			}

			/**
			 * Import extension data bundled with the backup payload.
			 *
			 * @param true|WP_Error $result  Import result. Return WP_Error to abort.
			 * @param array         $payload Full decoded import payload.
			 */
			$extension_result = apply_filters( 'flavor_like_backup_import_extensions', true, $payload );

			if ( is_wp_error( $extension_result ) ) {
				return $extension_result;
			}

			delete_transient( self::get_health_report_cache_key() );

			/**
			 * Fires after a successful Help backup import.
			 *
			 * @param array $payload Full decoded import payload.
			 */
			do_action( 'flavor_like_backup_imported', $payload );

			return true;
		}
	}
}
