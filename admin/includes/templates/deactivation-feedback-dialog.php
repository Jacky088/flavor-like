<?php
/**
 * Hidden markup for deactivation feedback modal.
 *
 * @package WP_Flavor Like
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reasons       = class_exists( 'Flavor_Like_Deactivation_Feedback' ) ? Flavor_Like_Deactivation_Feedback::get_reasons() : array();
$location_chips = class_exists( 'Flavor_Like_Deactivation_Feedback' ) ? Flavor_Like_Deactivation_Feedback::get_location_chips() : array();
$total_votes   = function_exists( 'flavor_like_count_all_logs' ) ? (int) flavor_like_count_all_logs() : 0;
?>
<div id="flavor-like-deactivate-feedback-dialog-wrapper" hidden>
	<div class="flavor-like-deactivate-feedback">
		<h2 class="flavor-like-deactivate-feedback__title"><?php esc_html_e( 'Deactivate Flavor Like', 'flavor-like' ); ?></h2>
		<p class="flavor-like-deactivate-feedback__lead">
			<?php esc_html_e( 'Mind sharing why? It helps us improve Flavor Like. Totally optional — or skip below.', 'flavor-like' ); ?>
		</p>
		<?php if ( $total_votes > 0 ) : ?>
			<p class="flavor-like-deactivate-feedback__note">
				<?php esc_html_e( 'Your vote data stays on this site if you reactivate later.', 'flavor-like' ); ?>
			</p>
		<?php endif; ?>
		<form id="flavor-like-deactivate-feedback-dialog-form" class="flavor-like-deactivate-feedback-form">
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Deactivation reason', 'flavor-like' ); ?></legend>
				<p class="flavor-like-deactivate-feedback-reason-error" hidden role="alert">
					<?php esc_html_e( 'Please select a reason before submitting.', 'flavor-like' ); ?>
				</p>
				<p class="flavor-like-deactivate-feedback-note-error" hidden role="alert">
					<?php esc_html_e( 'Please add a short note so we can improve Flavor Like.', 'flavor-like' ); ?>
				</p>
				<?php foreach ( $reasons as $reason_key => $reason ) : ?>
					<p>
						<label>
							<input
								type="radio"
								name="reason_key"
								value="<?php echo esc_attr( $reason_key ); ?>"
								<?php echo ! empty( $reason['require_note'] ) ? 'data-require-note="1"' : ''; ?>
							/>
							<?php echo esc_html( $reason['title'] ?? '' ); ?>
						</label>
					</p>
					<?php if ( 'not_working' === $reason_key && ! empty( $location_chips ) ) : ?>
						<div class="flavor-like-deactivate-feedback-chips" data-reason="not_working" hidden>
							<p class="flavor-like-deactivate-feedback-chips__label">
								<?php esc_html_e( 'Where did it fail?', 'flavor-like' ); ?>
							</p>
							<div class="flavor-like-deactivate-feedback-chips__list" role="group" aria-label="<?php esc_attr_e( 'Where did it fail?', 'flavor-like' ); ?>">
								<?php foreach ( $location_chips as $chip_key => $chip_label ) : ?>
									<label class="flavor-like-deactivate-feedback-chip">
										<input type="checkbox" name="locations[]" value="<?php echo esc_attr( $chip_key ); ?>" />
										<span><?php echo esc_html( $chip_label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $reason['placeholder'] ) ) : ?>
						<p class="flavor-like-deactivate-feedback-details" data-reason="<?php echo esc_attr( $reason_key ); ?>" hidden>
							<input
								type="text"
								name="details"
								class="regular-text widefat"
								placeholder="<?php echo esc_attr( $reason['placeholder'] ); ?>"
								autocomplete="off"
							/>
						</p>
					<?php endif; ?>
					<?php if ( 'not_working' === $reason_key ) : ?>
						<p class="flavor-like-deactivate-feedback-context" data-reason="not_working" hidden>
							<?php
							echo wp_kses_post(
								sprintf(
									/* translators: %s: Overview admin page link */
									__( 'Buttons appear on single posts by default—not on the homepage. %s for a quick setup checklist.', 'flavor-like' ),
									'<a href="' . esc_url( class_exists( 'Flavor_Like_Overview' ) ? Flavor_Like_Overview::get_about_url() : admin_url( 'admin.php?page=flavor-like-about' ) ) . '">' . esc_html__( 'Open Overview', 'flavor-like' ) . '</a>'
								)
							);
							?>
						</p>
					<?php endif; ?>
				<?php endforeach; ?>
			</fieldset>
		</form>
	</div>
</div>
