<?php
/**
 * Custom Templates
 * // @echo HEADER
 */

 // If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

if( ! function_exists( 'flavor_like_generate_templates_list' ) ){
	/**
	 * Generate templates list
	 * Performance optimization: Cache result to avoid regenerating for each button
	 *
	 * @author       	Alimir
	 * @since 1.0.0
	 * @return			Array
	 */
	function flavor_like_generate_templates_list(){
		// Performance optimization: Cache template list - it never changes during a request
		static $cached_templates = null;
		if ( $cached_templates !== null ) {
			return $cached_templates;
		}
		$default = array(
			'flavorlike-default' => array(
				'name'            => '简约拇指',
				'callback'        => 'flavor_like_set_default_template',
				'symbol'          => FLAVOR_LIKE_ASSETS_URL . '/img/templates/simple.svg',
				'is_text_support' => true
			),
			'flavorlike-heart' => array(
				'name'            => '简约爱心',
				'callback'        => 'flavor_like_set_simple_heart_template',
				'symbol'          => FLAVOR_LIKE_ASSETS_URL . '/img/templates/heart.svg',
				'is_text_support' => true
			),
			'flavorlike-robeen' => array(
				'name'            => 'Twitter 爱心',
				'callback'        => 'flavor_like_set_robeen_template',
				'symbol'          => FLAVOR_LIKE_ASSETS_URL . '/img/templates/twitter.svg',
				'is_text_support' => true
			),
			'flavorlike-animated-heart' => array(
				'name'            => '动态爱心',
				'callback'        => 'flavor_like_set_animated_heart_template',
				'symbol'          => FLAVOR_LIKE_ASSETS_URL . '/img/templates/animated-heart.svg',
				'is_text_support' => false
			),
			'flavorlike-star-thumb' => array(
				'name'            => '星星拇指',
				'callback'        => 'flavor_like_set_star_thumb_template',
				'symbol'          => FLAVOR_LIKE_ASSETS_URL . '/img/templates/starThumb.svg',
				'is_text_support' => true
			),
			'flavorlike-check-mark' => array(
				'name'            => '对勾标记',
				'callback'        => 'flavor_like_set_check_mark_template',
				'symbol'          => FLAVOR_LIKE_ASSETS_URL . '/img/templates/checkMark.svg',
				'is_text_support' => true
			),
			'flavorlike-clapping' => array(
				'name'            => '鼓掌按钮',
				'callback'        => 'flavor_like_set_clapping_template',
				'symbol'          => FLAVOR_LIKE_ASSETS_URL . '/img/templates/clapping.svg',
				'is_text_support' => true
			),
			'flavorlike-badge-thumb' => array(
				'name'            => '徽章拇指',
				'callback'        => 'flavor_like_set_badge_thumb_template',
				'symbol'          => FLAVOR_LIKE_ASSETS_URL . '/img/templates/badgeThumb.svg',
				'is_text_support' => true
			),
			'flavorlike-fave-star' => array(
				'name'            => '收藏星',
				'callback'        => 'flavor_like_set_fave_star_template',
				'symbol'          => FLAVOR_LIKE_ASSETS_URL . '/img/templates/faveStar.svg',
				'is_text_support' => true
			),
			'flavorlike-pin' => array(
				'name'            => '图钉按钮',
				'callback'        => 'flavor_like_set_pin_template',
				'symbol'          => FLAVOR_LIKE_ASSETS_URL . '/img/templates/pin.svg',
				'is_text_support' => true
			),
		);

		// Apply filter to allow additional templates
		$cached_templates = apply_filters( 'flavor_like_add_templates_list', $default );
		return $cached_templates;
	}
}

if( ! function_exists( 'flavor_like_set_default_template' ) ){
	/**
	 * Create simple default template (简约拇指)
	 *
	 * @author       	Alimir
	 * @since 1.0.0
	 * @return			Void
	 */
	function flavor_like_set_default_template( array $flavor_like_template ){
		ob_start();
		do_action( 'flavor_like_before_template', $flavor_like_template );
		extract( $flavor_like_template );
		$label_text = ! empty( $button_text ) ? $button_text : '点赞';
		$count_val  = ( $formatted_total_likes !== '' && $formatted_total_likes !== null ) ? $formatted_total_likes : '0';
	?>
		<div class="flavorlike flavorlike-default <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo flavor_like_setting_repo::getLikeAriaLabel(); ?>"
					data-flavor-like-id="<?php echo $ID; ?>"
					data-flavor-like-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
					data-flavor-like-type="<?php echo $type; ?>"
					data-flavor-like-template="<?php echo $style; ?>"
					data-flavor-like-display-likers="<?php echo $display_likers; ?>"
					data-flavor-like-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<span class="flavor-like-icon">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/></svg>
					</span>
					<span class="flavor-like-label"><?php echo esc_html( $label_text ); ?></span>
					<?php
						echo $up_vote_inner_text;
						do_action( 'flavor_like_inside_like_button', $flavor_like_template );
					?>
				</button>
				<?php
					echo $display_counters ? sprintf( '<span class="count-box flavor_like_counter_up" data-flavor-like-counter-value="%s">%s</span>', esc_attr( $count_val ), esc_html( $count_val ) ) : '';
					do_action( 'flavor_like_after_up_vote_button', $flavor_like_template );
				?>
			</div>
		<?php
			do_action( 'flavor_like_inside_template', $flavor_like_template );
		?>
		</div>
	<?php
		do_action( 'flavor_like_after_template', $flavor_like_template );
		return ob_get_clean();
	}
}

if( ! function_exists( 'flavor_like_set_simple_heart_template' ) ){
	/**
	 * Create simple heart template (简约爱心)
	 *
	 * @author       	Alimir
	 * @since 1.0.0
	 * @return			Void
	 */
	function flavor_like_set_simple_heart_template( array $flavor_like_template ){
		ob_start();
		do_action( 'flavor_like_before_template', $flavor_like_template );
		extract( $flavor_like_template );
		$label_text = ! empty( $button_text ) ? $button_text : '喜欢';
		$count_val  = ( $formatted_total_likes !== '' && $formatted_total_likes !== null ) ? $formatted_total_likes : '0';
	?>
		<div class="flavorlike flavorlike-heart <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo flavor_like_setting_repo::getLikeAriaLabel(); ?>"
					data-flavor-like-id="<?php echo $ID; ?>"
					data-flavor-like-nonce="<?php echo wp_create_nonce( $type  . $ID ); ?>"
					data-flavor-like-type="<?php echo $type; ?>"
					data-flavor-like-template="<?php echo $style; ?>"
					data-flavor-like-display-likers="<?php echo $display_likers; ?>"
					data-flavor-like-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<span class="flavor-like-icon">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
					</span>
					<span class="flavor-like-label"><?php echo esc_html( $label_text ); ?></span>
					<?php
						echo $up_vote_inner_text;
						do_action( 'flavor_like_inside_like_button', $flavor_like_template );
					?>
				</button>
				<?php
					echo $display_counters ? sprintf( '<span class="count-box flavor_like_counter_up" data-flavor-like-counter-value="%s">%s</span>', esc_attr( $count_val ), esc_html( $count_val ) ) : '';
					do_action( 'flavor_like_after_up_vote_button', $flavor_like_template );
				?>
			</div>
		<?php
			do_action( 'flavor_like_inside_template', $flavor_like_template );
		?>
		</div>
	<?php
		do_action( 'flavor_like_after_template', $flavor_like_template );
		return ob_get_clean();
	}
}

if( ! function_exists( 'flavor_like_set_robeen_template' ) ){
	/**
	 * Create Robeen (Twitter 爱心) template
	 *
	 * @author       	Alimir
	 * @since 1.0.0
	 * @return			Void
	 */
	function flavor_like_set_robeen_template( array $flavor_like_template ){
		ob_start();
		do_action( 'flavor_like_before_template', $flavor_like_template );
		extract( $flavor_like_template );
		$label_text = ! empty( $button_text ) ? $button_text : '心动';
		$count_val  = ( $formatted_total_likes !== '' && $formatted_total_likes !== null ) ? $formatted_total_likes : '0';
	?>
		<div class="flavorlike flavorlike-robeen <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo flavor_like_setting_repo::getLikeAriaLabel(); ?>"
					data-flavor-like-id="<?php echo $ID; ?>"
					data-flavor-like-nonce="<?php echo wp_create_nonce( $type  . $ID ); ?>"
					data-flavor-like-type="<?php echo $type; ?>"
					data-flavor-like-template="<?php echo $style; ?>"
					data-flavor-like-display-likers="<?php echo $display_likers; ?>"
					data-flavor-like-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<span class="flavor-like-icon">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
					</span>
					<span class="flavor-like-label"><?php echo esc_html( $label_text ); ?></span>
					<?php
						echo $up_vote_inner_text;
						do_action( 'flavor_like_inside_like_button', $flavor_like_template );
					?>
				</button>
				<?php
					echo $display_counters ? sprintf( '<span class="count-box flavor_like_counter_up" data-flavor-like-counter-value="%s">%s</span>', esc_attr( $count_val ), esc_html( $count_val ) ) : '';
					do_action( 'flavor_like_after_up_vote_button', $flavor_like_template );
				?>
			</div>
		<?php
			do_action( 'flavor_like_inside_template', $flavor_like_template );
		?>
		</div>
	<?php
		do_action( 'flavor_like_after_template', $flavor_like_template );
		return ob_get_clean();
	}
}

if( ! function_exists( 'flavor_like_set_animated_heart_template' ) ){
	/**
	 * Create Animated Heart template (动态爱心)
	 *
	 * @author       	Alimir
	 * @since 1.0.0
	 * @return			Void
	 */
	function flavor_like_set_animated_heart_template( array $flavor_like_template ){
		ob_start();
		do_action( 'flavor_like_before_template', $flavor_like_template );
		extract( $flavor_like_template );
		$count_val = ( $formatted_total_likes !== '' && $formatted_total_likes !== null ) ? $formatted_total_likes : '0';
	?>
		<div class="flavorlike flavorlike-animated-heart <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo flavor_like_setting_repo::getLikeAriaLabel(); ?>"
					data-flavor-like-id="<?php echo $ID; ?>"
					data-flavor-like-nonce="<?php echo wp_create_nonce( $type  . $ID ); ?>"
					data-flavor-like-type="<?php echo $type; ?>"
					data-flavor-like-template="<?php echo $style; ?>"
					data-flavor-like-display-likers="<?php echo $display_likers; ?>"
					data-flavor-like-likers-style="<?php echo $likers_style; ?>"
					data-flavor-like-append="<?php echo esc_attr( '<svg class="flavorlike-svg-heart flavorlike-svg-heart-pop one" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="flavorlike-svg-heart flavorlike-svg-heart-pop two" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="flavorlike-svg-heart flavorlike-svg-heart-pop three" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="flavorlike-svg-heart flavorlike-svg-heart-pop four" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="flavorlike-svg-heart flavorlike-svg-heart-pop five" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="flavorlike-svg-heart flavorlike-svg-heart-pop six" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="flavorlike-svg-heart flavorlike-svg-heart-pop seven" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="flavorlike-svg-heart flavorlike-svg-heart-pop eight" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="flavorlike-svg-heart flavorlike-svg-heart-pop nine" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg>' ); ?>"
					class="<?php echo $button_class; ?>">
					<?php
						echo $up_vote_inner_text;
						do_action( 'flavor_like_inside_like_button', $flavor_like_template );
					?>
					<svg class="flavorlike-svg-heart flavorlike-svg-heart-icon" viewBox="0 -28 512.00002 512" xmlns="http://www.w3.org/2000/svg">
					<path
						d="m471.382812 44.578125c-26.503906-28.746094-62.871093-44.578125-102.410156-44.578125-29.554687 0-56.621094 9.34375-80.449218 27.769531-12.023438 9.300781-22.917969 20.679688-32.523438 33.960938-9.601562-13.277344-20.5-24.660157-32.527344-33.960938-23.824218-18.425781-50.890625-27.769531-80.445312-27.769531-39.539063 0-75.910156 15.832031-102.414063 44.578125-26.1875 28.410156-40.613281 67.222656-40.613281 109.292969 0 43.300781 16.136719 82.9375 50.78125 124.742187 30.992188 37.394531 75.535156 75.355469 127.117188 119.3125 17.613281 15.011719 37.578124 32.027344 58.308593 50.152344 5.476563 4.796875 12.503907 7.4375 19.792969 7.4375 7.285156 0 14.316406-2.640625 19.785156-7.429687 20.730469-18.128907 40.707032-35.152344 58.328125-50.171876 51.574219-43.949218 96.117188-81.90625 127.109375-119.304687 34.644532-41.800781 50.777344-81.4375 50.777344-124.742187 0-42.066407-14.425781-80.878907-40.617188-109.289063zm0 0" />
					</svg>
				</button>
				<?php
					echo $display_counters ? sprintf( '<span class="count-box flavor_like_counter_up" data-flavor-like-counter-value="%s">%s</span>', esc_attr( $count_val ), esc_html( $count_val ) ) : '';
					do_action( 'flavor_like_after_up_vote_button', $flavor_like_template );
				?>
			</div>
		<?php
			do_action( 'flavor_like_inside_template', $flavor_like_template );
		?>
		</div>
	<?php
		do_action( 'flavor_like_after_template', $flavor_like_template );
		return ob_get_clean();
	}
}

/**
 * 星星拇指模板
 */
if( ! function_exists( 'flavor_like_set_star_thumb_template' ) ){
	function flavor_like_set_star_thumb_template( array $flavor_like_template ){
		ob_start();
		do_action( 'flavor_like_before_template', $flavor_like_template );
		extract( $flavor_like_template );
		$label_text = ! empty( $button_text ) ? $button_text : '点赞';
		$count_val  = ( $formatted_total_likes !== '' && $formatted_total_likes !== null ) ? $formatted_total_likes : '0';
	?>
		<div class="flavorlike flavorlike-star-thumb <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo flavor_like_setting_repo::getLikeAriaLabel(); ?>"
					data-flavor-like-id="<?php echo $ID; ?>"
					data-flavor-like-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
					data-flavor-like-type="<?php echo $type; ?>"
					data-flavor-like-template="<?php echo $style; ?>"
					data-flavor-like-display-likers="<?php echo $display_likers; ?>"
					data-flavor-like-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<span class="flavor-like-icon">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/></svg>
					</span>
					<span class="flavor-like-label"><?php echo esc_html( $label_text ); ?></span>
					<?php
						echo $up_vote_inner_text;
						do_action( 'flavor_like_inside_like_button', $flavor_like_template );
					?>
				</button>
				<?php
					echo $display_counters ? sprintf( '<span class="count-box flavor_like_counter_up" data-flavor-like-counter-value="%s">%s</span>', esc_attr( $count_val ), esc_html( $count_val ) ) : '';
					do_action( 'flavor_like_after_up_vote_button', $flavor_like_template );
				?>
			</div>
		<?php do_action( 'flavor_like_inside_template', $flavor_like_template ); ?>
		</div>
	<?php
		do_action( 'flavor_like_after_template', $flavor_like_template );
		return ob_get_clean();
	}
}

/**
 * 对勾标记模板
 */
if( ! function_exists( 'flavor_like_set_check_mark_template' ) ){
	function flavor_like_set_check_mark_template( array $flavor_like_template ){
		ob_start();
		do_action( 'flavor_like_before_template', $flavor_like_template );
		extract( $flavor_like_template );
		$label_text = ! empty( $button_text ) ? $button_text : '已认可';
		$count_val  = ( $formatted_total_likes !== '' && $formatted_total_likes !== null ) ? $formatted_total_likes : '0';
	?>
		<div class="flavorlike flavorlike-check-mark <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo flavor_like_setting_repo::getLikeAriaLabel(); ?>"
					data-flavor-like-id="<?php echo $ID; ?>"
					data-flavor-like-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
					data-flavor-like-type="<?php echo $type; ?>"
					data-flavor-like-template="<?php echo $style; ?>"
					data-flavor-like-display-likers="<?php echo $display_likers; ?>"
					data-flavor-like-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<span class="flavor-like-icon">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="16 9 10.5 14.5 8 12"></polyline></svg>
					</span>
					<span class="flavor-like-label"><?php echo esc_html( $label_text ); ?></span>
					<?php
						echo $up_vote_inner_text;
						do_action( 'flavor_like_inside_like_button', $flavor_like_template );
					?>
				</button>
				<?php
					echo $display_counters ? sprintf( '<span class="count-box flavor_like_counter_up" data-flavor-like-counter-value="%s">%s</span>', esc_attr( $count_val ), esc_html( $count_val ) ) : '';
					do_action( 'flavor_like_after_up_vote_button', $flavor_like_template );
				?>
			</div>
		<?php do_action( 'flavor_like_inside_template', $flavor_like_template ); ?>
		</div>
	<?php
		do_action( 'flavor_like_after_template', $flavor_like_template );
		return ob_get_clean();
	}
}

/**
 * 鼓掌按钮模板
 */
if( ! function_exists( 'flavor_like_set_clapping_template' ) ){
	function flavor_like_set_clapping_template( array $flavor_like_template ){
		ob_start();
		do_action( 'flavor_like_before_template', $flavor_like_template );
		extract( $flavor_like_template );
		$label_text = ! empty( $button_text ) ? $button_text : '鼓掌';
		$count_val  = ( $formatted_total_likes !== '' && $formatted_total_likes !== null ) ? $formatted_total_likes : '0';
	?>
		<div class="flavorlike flavorlike-clapping <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo flavor_like_setting_repo::getLikeAriaLabel(); ?>"
					data-flavor-like-id="<?php echo $ID; ?>"
					data-flavor-like-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
					data-flavor-like-type="<?php echo $type; ?>"
					data-flavor-like-template="<?php echo $style; ?>"
					data-flavor-like-display-likers="<?php echo $display_likers; ?>"
					data-flavor-like-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<span class="flavor-like-icon flavorlike-clap-icon">👏</span>
					<span class="flavor-like-label"><?php echo esc_html( $label_text ); ?></span>
					<?php
						echo $up_vote_inner_text;
						do_action( 'flavor_like_inside_like_button', $flavor_like_template );
					?>
				</button>
				<?php
					echo $display_counters ? sprintf( '<span class="count-box flavor_like_counter_up" data-flavor-like-counter-value="%s">%s</span>', esc_attr( $count_val ), esc_html( $count_val ) ) : '';
					do_action( 'flavor_like_after_up_vote_button', $flavor_like_template );
				?>
			</div>
		<?php do_action( 'flavor_like_inside_template', $flavor_like_template ); ?>
		</div>
	<?php
		do_action( 'flavor_like_after_template', $flavor_like_template );
		return ob_get_clean();
	}
}

/**
 * 徽章拇指模板
 */
if( ! function_exists( 'flavor_like_set_badge_thumb_template' ) ){
	function flavor_like_set_badge_thumb_template( array $flavor_like_template ){
		ob_start();
		do_action( 'flavor_like_before_template', $flavor_like_template );
		extract( $flavor_like_template );
		$label_text = ! empty( $button_text ) ? $button_text : '荣誉';
		$count_val  = ( $formatted_total_likes !== '' && $formatted_total_likes !== null ) ? $formatted_total_likes : '0';
	?>
		<div class="flavorlike flavorlike-badge-thumb <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo flavor_like_setting_repo::getLikeAriaLabel(); ?>"
					data-flavor-like-id="<?php echo $ID; ?>"
					data-flavor-like-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
					data-flavor-like-type="<?php echo $type; ?>"
					data-flavor-like-template="<?php echo $style; ?>"
					data-flavor-like-display-likers="<?php echo $display_likers; ?>"
					data-flavor-like-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<span class="flavor-like-icon">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 5.5l2 4 4.5.7-3.3 3.2.8 4.6-4-2.1-4 2.1.8-4.6L5.5 10.2l4.5-.7z"/></svg>
					</span>
					<span class="flavor-like-label"><?php echo esc_html( $label_text ); ?></span>
					<?php
						echo $up_vote_inner_text;
						do_action( 'flavor_like_inside_like_button', $flavor_like_template );
					?>
				</button>
				<?php
					echo $display_counters ? sprintf( '<span class="count-box flavor_like_counter_up" data-flavor-like-counter-value="%s">%s</span>', esc_attr( $count_val ), esc_html( $count_val ) ) : '';
					do_action( 'flavor_like_after_up_vote_button', $flavor_like_template );
				?>
			</div>
		<?php do_action( 'flavor_like_inside_template', $flavor_like_template ); ?>
		</div>
	<?php
		do_action( 'flavor_like_after_template', $flavor_like_template );
		return ob_get_clean();
	}
}

/**
 * 收藏星模板
 */
if( ! function_exists( 'flavor_like_set_fave_star_template' ) ){
	function flavor_like_set_fave_star_template( array $flavor_like_template ){
		ob_start();
		do_action( 'flavor_like_before_template', $flavor_like_template );
		extract( $flavor_like_template );
		$label_text = ! empty( $button_text ) ? $button_text : '收藏';
		$count_val  = ( $formatted_total_likes !== '' && $formatted_total_likes !== null ) ? $formatted_total_likes : '0';
	?>
		<div class="flavorlike flavorlike-fave-star <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo flavor_like_setting_repo::getLikeAriaLabel(); ?>"
					data-flavor-like-id="<?php echo $ID; ?>"
					data-flavor-like-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
					data-flavor-like-type="<?php echo $type; ?>"
					data-flavor-like-template="<?php echo $style; ?>"
					data-flavor-like-display-likers="<?php echo $display_likers; ?>"
					data-flavor-like-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<span class="flavor-like-icon">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
					</span>
					<span class="flavor-like-label"><?php echo esc_html( $label_text ); ?></span>
					<?php
						echo $up_vote_inner_text;
						do_action( 'flavor_like_inside_like_button', $flavor_like_template );
					?>
				</button>
				<?php
					echo $display_counters ? sprintf( '<span class="count-box flavor_like_counter_up" data-flavor-like-counter-value="%s">%s</span>', esc_attr( $count_val ), esc_html( $count_val ) ) : '';
					do_action( 'flavor_like_after_up_vote_button', $flavor_like_template );
				?>
			</div>
		<?php do_action( 'flavor_like_inside_template', $flavor_like_template ); ?>
		</div>
	<?php
		do_action( 'flavor_like_after_template', $flavor_like_template );
		return ob_get_clean();
	}
}

/**
 * 图钉按钮模板
 */
if( ! function_exists( 'flavor_like_set_pin_template' ) ){
	function flavor_like_set_pin_template( array $flavor_like_template ){
		ob_start();
		do_action( 'flavor_like_before_template', $flavor_like_template );
		extract( $flavor_like_template );
		$label_text = ! empty( $button_text ) ? $button_text : '喜欢';
		$count_val  = ( $formatted_total_likes !== '' && $formatted_total_likes !== null ) ? $formatted_total_likes : '0';
	?>
		<div class="flavorlike flavorlike-pin <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo flavor_like_setting_repo::getLikeAriaLabel(); ?>"
					data-flavor-like-id="<?php echo $ID; ?>"
					data-flavor-like-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
					data-flavor-like-type="<?php echo $type; ?>"
					data-flavor-like-template="<?php echo $style; ?>"
					data-flavor-like-display-likers="<?php echo $display_likers; ?>"
					data-flavor-like-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<span class="flavor-like-icon">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
					</span>
					<span class="flavor-like-label"><?php echo esc_html( $label_text ); ?></span>
					<?php
						echo $up_vote_inner_text;
						do_action( 'flavor_like_inside_like_button', $flavor_like_template );
					?>
				</button>
				<?php
					echo $display_counters ? sprintf( '<span class="count-box flavor_like_counter_up" data-flavor-like-counter-value="%s">%s</span>', esc_attr( $count_val ), esc_html( $count_val ) ) : '';
					do_action( 'flavor_like_after_up_vote_button', $flavor_like_template );
				?>
			</div>
		<?php do_action( 'flavor_like_inside_template', $flavor_like_template ); ?>
		</div>
	<?php
		do_action( 'flavor_like_after_template', $flavor_like_template );
		return ob_get_clean();
	}
}
