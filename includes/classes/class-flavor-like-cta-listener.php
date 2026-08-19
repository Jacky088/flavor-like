<?php
/**
 * Flavor Like CTA Listener
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class flavor_like_cta_listener extends flavor_like_ajax_listener_base {

	private $response = array(
		'message'     => NULL,
		'btnText'     => NULL,
		'messageType' => 'info',
		'status'      => 0,
		'data'        => NULL
	);

	public function __construct(){
		parent::__construct();
		$this->setFormData();
		$this->setInfoData();
		$this->updateButton();
	}

	/**
	 * Set form data
	 *
	 * @return void
	 */
	private function setFormData(){
		$this->data['id']             = isset( $_POST['id'] ) ? absint($_POST['id']) : NULL;
		$this->data['type']           = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : NULL;
		$this->data['nonce']          = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : NULL;
		$this->data['factor']         = isset( $_POST['factor'] ) ? sanitize_text_field( wp_unslash( $_POST['factor'] ) ) : NULL;
		$this->data['template']       = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'flavorlike-default';
		$this->data['displayLikers']  = isset( $_POST['displayLikers'] ) ? sanitize_text_field( wp_unslash( $_POST['displayLikers'] ) ) : false;
		$this->data['likersTemplate'] = isset( $_POST['likersTemplate'] ) ? sanitize_text_field( wp_unslash( $_POST['likersTemplate'] ) ) : 'popover';
	}

	/**
	 * Set more info data
	 *
	 * @return void
	 */
	private function setInfoData(){
		$this->data['client_address'] = flavor_like_get_user_ip();
		// make filter for data args
		$this->data = apply_filters( 'flavor_like_listener_data', $this->data );
	}

	/**
	 * Update button
	 *
	 * @return void
	 */
	private function updateButton(){
		try {
			// start actions
			$this->beforeUpdateAction( $this->data );
			// Validate inputs
			$this->validates();
			// get settings info
			$this->settings_type = flavor_like_setting_type::get_instance( $this->data['type'] );

			if( empty( $this->settings_type->getType() ) ){
				throw new \Exception( esc_html__( 'Invalid item type.', 'flavor-like' ) );
			}

			// Acquire lock
			$lock_name = flavor_like_acquire_lock( $this->data['type'], $this->data['id'] );
			if ( false === $lock_name ) {
				throw new \Exception( esc_html__( 'Unable to obtain lock for this request.', 'flavor-like' ) );
			}

			try {
				$process  = new flavor_like_cta_process( array(
					'item_id'       => $this->data['id'],
					'item_type'     => $this->settings_type->getType(),
					'item_factor'   => $this->data['factor'],
					'item_template' => $this->data['template'],
					'user_ip'       => $this->data['client_address']
				) );

				if( flavor_like_setting_repo::requireLogin( $this->settings_type->getType() ) && ! $this->user ){
					$this->response['message']      = flavor_like_setting_repo::getLoginNotice();
					$this->response['status']       = 4;
					$this->response['requireLogin'] = true;
				} else {
					// Start process
					$has_permission = $process->update();
					// Check permission
					if( ! $has_permission ){
						$this->response['message']     = flavor_like_setting_repo::getPermissionNotice();
						$this->response['btnText']     = flavor_like_setting_repo::getButtonText( $this->settings_type->getType(), 'unlike' );
						$this->response['status']      = 5;
						$this->response['messageType'] = 'warning';
					} else {
						$this->response['status'] = $process->getStatusCode();
						$counter_value = $process->getCounterValue();

						switch ( $this->response['status'] ){
							case 1:
								$this->response['message']     = flavor_like_setting_repo::getLikeNotice();
								$this->response['messageType'] = 'success';
								$this->response['btnText'] = flavor_like_setting_repo::getButtonText( $this->settings_type->getType(), 'like' );
								$this->response['data'] = apply_filters( 'flavor_like_respond_for_not_liked_data', $counter_value, $this->data['id'] );
								break;
							case 2:
								$this->response['message']     = flavor_like_setting_repo::getUnLikeNotice();
								$this->response['messageType'] = 'info';
								$this->response['btnText'] = flavor_like_setting_repo::getButtonText( $this->settings_type->getType(), 'like' );
								$this->response['data'] = apply_filters( 'flavor_like_respond_for_unliked_data', $counter_value, $this->data['id'] );
								break;
							case 3:
								$this->response['message']     = flavor_like_setting_repo::getLikeNotice();
								$this->response['messageType'] = 'success';
								$this->response['btnText'] = flavor_like_setting_repo::getButtonText( $this->settings_type->getType(), 'unlike' );
								$this->response['data'] = apply_filters( 'flavor_like_respond_for_liked_data', $counter_value, $this->data['id'] );
								break;
							case 4:
								$this->response['message']     = flavor_like_setting_repo::getLikeNotice();
								$this->response['messageType'] = 'success';
								$this->response['btnText'] = flavor_like_setting_repo::getButtonText( $this->settings_type->getType(), 'unlike' );
								$this->response['data'] = apply_filters( 'flavor_like_respond_for_no_limit_data', $counter_value, $this->data['id'] );
								break;
						}
					}
				}

				// Display likers
				if( $this->data['displayLikers'] && ( ! flavor_like_setting_repo::restrictLikersBox( $this->settings_type->getType() ) || $this->user ) && ! in_array( $this->response['status'], array(4,5) ) ){
					$template = flavor_like_get_likers_template_for_type(
						$this->settings_type->getItemType(),
						$this->data['id'],
						array(
							'style' => $this->data['likersTemplate']
						)
					);
					$this->response['likers'] = ! empty( $template ) ? array(
						'template' => $this->data['likersTemplate'] != 'popover' ? $template :  sprintf(
						'<div class="flavor_like_likers_wrapper wp_%s_likers_%s">%s</div>', $this->settings_type->getType(), $this->data['id'], $template )
					) : array( 'template' => '' );
				}

				// Display toasts condition
				$this->response['hasToast'] = flavor_like_setting_repo::hasToast( $this->settings_type->getType() );

				// Hide data when counter is not visible
				if( ! flavor_like_setting_repo::isCounterBoxVisible( $this->settings_type->getType() ) ){
					$this->response['data'] = '';
				}

				$response = apply_filters( 'flavor_like_ajax_respond', $this->response, $this->data['id'], $this->response['status'], $process->getAjaxProcessAtts() );

				$this->afterUpdateAction( $process->getActionAtts() );
			} finally {
				// Always release the mutex, including when an exception is
				// thrown mid-process; the outer catch re-throws after cleanup.
				flavor_like_release_lock( $lock_name, $this->data['type'], $this->data['id'] );
			}

			// success
			$this->response( $response );

		} catch ( \Exception $e ){
			// Log error for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf(
					'Flavor Like Process Error: %s | Item Type: %s | Item ID: %s | User IP: %s',
					$e->getMessage(),
					isset( $this->data['type'] ) ? $this->data['type'] : 'unknown',
					isset( $this->data['id'] ) ? $this->data['id'] : 'unknown',
					isset( $this->data['client_address'] ) ? wp_privacy_anonymize_ip( $this->data['client_address'] ) : 'unknown'
				) );
			}

			return $this->sendError( array(
				'message'     => $e->getMessage(),
				'messageType' => 'error',
				'hasToast'    => flavor_like_setting_repo::hasToast( $this->data['type'] )
			) );
		}
	}

	/**
	* Before Update Action
	* Provides hook for performing actions before a like/dislike
	*/
	private function beforeUpdateAction( $args = array() ){
		do_action_ref_array('flavor_like_before_process', $args );
	}

	/**
	* After Update Action
	* Provides hook for performing actions after a like/dislike
	*/
	private function afterUpdateAction( $args = array() ){
		do_action_ref_array( 'flavor_like_after_process', $args );
	}

	/**
	* Validate the Favorite
	*/
	private function validates(){
		// Verify nonce to prevent CSRF attacks
		if( empty( $this->data['nonce'] ) || ! wp_verify_nonce( $this->data['nonce'], $this->data['type'] . $this->data['id'] ) ){
			throw new \Exception( flavor_like_setting_repo::getValidationNotice() );
		}
		// Return false when ID not exist
		if( empty( $this->data['id'] ) || empty( $this->data['type'] ) ){
			throw new \Exception( flavor_like_setting_repo::getValidationNotice() );
		}
		// check blacklist
		if( ! flavor_like_blacklist_validator::isValid( array( $this->data['client_address'] ) ) ){
			throw new \Exception( flavor_like_setting_repo::getValidationNotice() );
		}
	}
}