<?php
/**
 * Deactivation feedback modal on the Plugins screen.
 *
 * @package WP_Flavor Like
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Flavor_Like_Deactivation_Feedback' ) ) {

	/**
	 * Quick feedback before deactivating Flavor Like.
	 */
	class Flavor_Like_Deactivation_Feedback {

		const SCRIPT_HANDLE = 'flavor-like-deactivation-feedback';

		/**
		 * Reason keys accepted locally. Keys unknown to the remote audit API are
		 * mapped in send_to_api() (e.g. too_complicated → other).
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function get_reasons() {
			// Actionable reasons first; generic exit reasons last (reduces low-signal bias).
			$reasons = array(
				'not_working'    => array(
					'title'        => __( "I couldn't get the plugin to work", 'flavor-like' ),
					'placeholder'  => __( 'What happened? e.g. button missing, vote fails, theme conflict', 'flavor-like' ),
					'require_note' => true,
				),
				'found_better'   => array(
					'title'       => __( 'I found a better plugin', 'flavor-like' ),
					'placeholder' => __( 'Which plugin?', 'flavor-like' ),
				),
				'temporary'      => array(
					'title'       => __( "It's a temporary deactivation", 'flavor-like' ),
					'placeholder' => '',
				),
				'no_longer_need' => array(
					'title'       => __( 'I no longer need the plugin', 'flavor-like' ),
					'placeholder' => '',
				),
				'other'          => array(
					'title'       => __( 'Other', 'flavor-like' ),
					'placeholder' => __( 'Tell us more (optional)', 'flavor-like' ),
				),
			);

			return $reasons;
		}

		/**
		 * Location chips for not_working feedback.
		 *
		 * @return array<string, string>
		 */
		public static function get_location_chips() {
			return array(
				'home'    => __( 'Homepage', 'flavor-like' ),
				'archive' => __( 'Archive / list', 'flavor-like' ),
				'page'    => __( 'Page', 'flavor-like' ),
				'single'  => __( 'Single post', 'flavor-like' ),
				'theme'   => __( 'Theme issue', 'flavor-like' ),
			);
		}

		/**
		 * Deactivation feedback API (TWT audit endpoint).
		 *
		 * @return string
		 */
		public static function get_api_url() {
			return '';
		}

		/**
		 * @return void
		 */
		public static function init() {
			if ( ! is_admin() ) {
				return;
			}

			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'admin_footer', array( __CLASS__, 'render_dialog' ) );
			add_action( 'wp_ajax_flavor_like_deactivation_feedback', array( __CLASS__, 'ajax_send_feedback' ) );
		}

		/**
		 * @param string $hook Admin hook.
		 * @return void
		 */
		public static function enqueue_assets( $hook ) {
			if ( ! in_array( $hook, array( 'plugins.php', 'plugins-network.php' ), true ) ) {
				return;
			}

			$js_path = FLAVOR_LIKE_ADMIN_DIR . '/assets/js/deactivation-feedback.js';
			$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : FLAVOR_LIKE_VERSION;

			wp_enqueue_script(
				self::SCRIPT_HANDLE,
				FLAVOR_LIKE_ADMIN_URL . '/assets/js/deactivation-feedback.js',
				array(),
				$js_ver,
				true
			);

			wp_localize_script(
				self::SCRIPT_HANDLE,
				'flavorLikeDeactivationFeedback',
				array(
					'slug'        => FLAVOR_LIKE_SLUG,
					'pluginFile'  => FLAVOR_LIKE_BASENAME,
					'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
					'pluginsUrl'  => admin_url( 'plugins.php' ),
					'nonce'       => wp_create_nonce( 'flavor_like_deactivation_feedback' ),
					'i18n'        => array(
						'submit'           => __( 'Submit & deactivate', 'flavor-like' ),
						'skip'             => __( 'Skip & deactivate', 'flavor-like' ),
						'noteRequired'     => __( 'Please add a short note so we can improve Flavor Like.', 'flavor-like' ),
						'locationRequired' => __( 'Select where it failed, or add a short note.', 'flavor-like' ),
					),
				)
			);
		}

		/**
		 * @return void
		 */
		public static function render_dialog() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) ) {
				return;
			}

			$template = FLAVOR_LIKE_ADMIN_DIR . '/includes/templates/deactivation-feedback-dialog.php';
			if ( is_readable( $template ) ) {
				include $template;
			}
		}

		/**
		 * @return void
		 */
		public static function ajax_send_feedback() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( null, 403 );
			}

			check_ajax_referer( 'flavor_like_deactivation_feedback', 'nonce' );

			$reason_key = isset( $_POST['reason_key'] ) ? sanitize_key( wp_unslash( $_POST['reason_key'] ) ) : '';
			$allowed    = array_keys( self::get_reasons() );

			if ( ! in_array( $reason_key, $allowed, true ) ) {
				wp_send_json_error( null, 400 );
			}

			$details = '';
			if ( isset( $_POST['details'] ) ) {
				$details = sanitize_text_field( wp_unslash( $_POST['details'] ) );
			}

			$locations = array();
			if ( isset( $_POST['locations'] ) ) {
				$raw = wp_unslash( $_POST['locations'] );
				if ( is_array( $raw ) ) {
					$chip_keys = array_keys( self::get_location_chips() );
					foreach ( $raw as $loc ) {
						$loc = sanitize_key( $loc );
						if ( in_array( $loc, $chip_keys, true ) ) {
							$locations[] = $loc;
						}
					}
				} elseif ( is_string( $raw ) && '' !== $raw ) {
					foreach ( explode( ',', $raw ) as $loc ) {
						$loc = sanitize_key( $loc );
						if ( isset( self::get_location_chips()[ $loc ] ) ) {
							$locations[] = $loc;
						}
					}
				}
			}

			if ( ! empty( $locations ) ) {
				$details = trim( 'locations:' . implode( ',', $locations ) . ( '' !== $details ? ' | ' . $details : '' ) );
			}

			self::send_to_api( $reason_key, $details );

			wp_send_json_success();
		}

		/**
		 * Sanitize a version string for the feedback API.
		 *
		 * @param string $value Raw version.
		 * @return string
		 */
		private static function sanitize_version( $value ) {
			$value = sanitize_text_field( (string) $value );
			if ( '' === $value ) {
				return '';
			}

			if ( ! preg_match( '/^[\d.A-Za-z\-]+$/', $value ) ) {
				return '';
			}

			return substr( $value, 0, 50 );
		}

		/**
		 * Environment metadata sent with voluntary deactivation feedback.
		 *
		 * @return array<string, string>
		 */
		private static function get_environment_payload() {
			global $wp_version;

			return array(
				'plugin_version' => self::sanitize_version( defined( 'FLAVOR_LIKE_VERSION' ) ? FLAVOR_LIKE_VERSION : '' ),
				'wp_version'     => self::sanitize_version( isset( $wp_version ) ? $wp_version : get_bloginfo( 'version' ) ),
				'php_version'    => self::sanitize_version( PHP_VERSION ),
			);
		}

		/**
		 * @param string $reason_key Reason.
		 * @param string $details    Details.
		 * @return array<string, string>
		 */
		private static function build_api_payload( $reason_key, $details ) {
			$payload = array_merge(
				array(
					'plugin_slug' => 'flavor-like',
					'site_url'    => home_url(),
					'reason_key'  => $reason_key,
					'details'     => $details,
				),
				self::get_environment_payload()
			);

			$days = flavor_like_get_days_since_activation();

			if ( null !== $days ) {
				$payload['days_since_activation'] = $days;
			}

			return $payload;
		}

		/**
		 * @param string $reason_key Reason.
		 * @param string $details    Details.
		 * @return void
		 */
		private static function send_to_api( $reason_key, $details ) {
			// No remote endpoint configured in this fork — skip external send.
			if ( '' === self::get_api_url() ) {
				return;
			}

			$body = self::build_api_payload( $reason_key, $details );

			wp_remote_post(
				self::get_api_url(),
				array(
					'timeout' => 15,
					'headers' => array(
						'Content-Type' => 'application/json; charset=utf-8',
					),
					'body'    => wp_json_encode( $body ),
				)
			);
		}
	}
}
