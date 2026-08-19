<?php
/**
 * Hidden markup for the activation welcome pointer.
 *
 * @package WP_Flavor Like
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$preview_url = '';
if ( class_exists( 'Flavor_Like_Overview' ) ) {
	$health      = Flavor_Like_Overview::get_health_report();
	$preview_url = ! empty( $health['preview_url'] ) ? $health['preview_url'] : '';
}

if ( empty( $preview_url ) ) {
	$sample_post = get_posts(
		array(
			'numberposts' => 1,
			'post_status' => 'publish',
			'post_type'   => 'post',
		)
	);
	if ( ! empty( $sample_post[0] ) ) {
		$preview_url = get_permalink( $sample_post[0]->ID );
	}
}

$settings_url = class_exists( 'Flavor_Like_Overview' )
	? Flavor_Like_Overview::get_settings_url( 'content-types' )
	: admin_url( 'admin.php?page=flavor-like-settings&settings-page=content-types' );
?>
<div id="flavor-like-activation-pointer-template" hidden>
	<div class="flavor-like-activation-pointer__panel">
		<button type="button" class="flavor-like-activation-pointer__close" aria-label="<?php esc_attr_e( 'Dismiss', 'flavor-like' ); ?>">
			<span aria-hidden="true">&times;</span>
		</button>
		<h3 class="flavor-like-activation-pointer__title"><?php esc_html_e( 'Thanks for installing Flavor Like!', 'flavor-like' ); ?></h3>
		<p class="flavor-like-activation-pointer__lead">
			<?php esc_html_e( 'Like buttons appear on single posts by default. Home and blog lists stay off until you enable them under Content Types. Open a post to try a button, then adjust where they show.', 'flavor-like' ); ?>
		</p>
		<p class="flavor-like-activation-pointer__links">
			<?php
			printf(
				/* translators: 1: Overview page URL, 2: Documentation URL. */
				wp_kses_post( __( 'Need a hand? Visit <a href="%1$s">Overview</a> in this menu or read the <a href="%2$s" target="_blank" rel="noopener noreferrer">documentation</a>.', 'flavor-like' ) ),
				esc_url( admin_url( 'admin.php?page=flavor-like-about' ) ),
				esc_url( add_query_arg(
					array(
						'utm_source'   => 'activation-pointer',
						'utm_campaign' => 'plugin-uri',
						'utm_medium'   => 'wp-dash',
					),
					'https://github.com/Jacky088/flavor-like'
				) )
			);
			?>
		</p>
		<p class="flavor-like-activation-pointer__actions">
			<?php if ( ! empty( $preview_url ) ) : ?>
				<a class="button button-primary" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'View a sample post', 'flavor-like' ); ?>
				</a>
				<a class="button button-secondary" href="<?php echo esc_url( $settings_url ); ?>">
					<?php esc_html_e( 'Open Settings', 'flavor-like' ); ?>
				</a>
			<?php else : ?>
				<a class="button button-primary" href="<?php echo esc_url( $settings_url ); ?>">
					<?php esc_html_e( 'Open Settings', 'flavor-like' ); ?>
				</a>
			<?php endif; ?>
			<button type="button" class="button button-secondary flavor-like-activation-pointer__dismiss">
				<?php esc_html_e( 'Got it', 'flavor-like' ); ?>
			</button>
		</p>
	</div>
</div>
