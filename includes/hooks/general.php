<?php
/**
 * General Hooks
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/*******************************************************
  Post Type Auto Display
*******************************************************/

if( ! function_exists( 'flavor_like_put_posts' ) ){
	/**
	 * Auto insert flavor_like function in the posts/pages content
	 *
	 * Uses standard WordPress conditional tags to scope the auto-insert to
	 * the main loop on the frontend, outside of feeds and embeds.
	 *
	 * @param string $content
	 * @since 1.0
	 * @return string
	 */
	function flavor_like_put_posts( $content ) {
		// Auto-display is off
		if ( ! flavor_like_setting_repo::isAutoDisplayOn('post') ) {
			return apply_filters( 'flavor_like_the_content', $content, $content );
		}

		// Skip on admin, cron, REST contexts
		if ( FlavorLikeInit::is_admin_backend() || FlavorLikeInit::is_cron() || FlavorLikeInit::is_rest() ) {
			return apply_filters( 'flavor_like_the_content', $content, $content );
		}

		// Excerpts / list snippets are opt-in (off by default) to avoid like spam on archives.
		if ( 'the_excerpt' === current_filter() && ! flavor_like_setting_repo::isAutoDisplayOnExcerpts() ) {
			return apply_filters( 'flavor_like_the_content', $content, $content );
		}

		// Standard WordPress context exclusions: feeds and embeds render in
		// non-HTML / stripped contexts where the button would not work.
		if ( is_feed() || is_embed() ) {
			return apply_filters( 'flavor_like_the_content', $content, $content );
		}

		// Stack variable
		$output = $content;

		// 判断是否应该显示
		$should_display = false;

		// 获取"显示按钮位置"：DB 存的是 hide-list（用户没勾选的位置）
		$hide_list = flavor_like_get_option( 'posts_group|auto_display_filter', null );
		if ( null === $hide_list ) {
			// 未保存过设置，使用默认值：除了 single 以外都隐藏
			$hide_list = flavor_like_setting_repo::getAutoDisplayFilterDefault();
		}
		if ( ! is_array( $hide_list ) ) {
			$hide_list = array();
		}

		// 把 hide-list 转为 show-on 列表
		$all_contexts = array( 'home', 'single', 'archive', 'category', 'search', 'tag', 'author' );
		$show_on = array_values( array_diff( $all_contexts, $hide_list ) );

		// 检查当前页面上下文是否在 show-on 列表中
		$context_map = array(
			'home'     => is_front_page() || is_home(),
			'single'   => is_singular(),
			'archive'  => is_archive(),
			'category' => is_category(),
			'search'   => is_search(),
			'tag'      => is_tag(),
			'author'   => is_author(),
		);

		foreach ( $show_on as $context ) {
			if ( isset( $context_map[ $context ] ) && $context_map[ $context ] ) {
				$should_display = true;
				break;
			}
		}

		// 如果是 singular 页面，还要检查"始终在文章类型上显示"
		if ( ! $should_display && is_singular() ) {
			$allowed_post_types = flavor_like_setting_repo::getPostTypesFilterList();
			if ( ! empty( $allowed_post_types ) && in_array( get_post_type(), $allowed_post_types, true ) ) {
				$should_display = true;
			}
		}

		if ( $should_display ) {
			// Get button
			$button = flavor_like('put');
			switch ( flavor_like_get_option( 'posts_group|auto_display_position', 'bottom' ) ) {
				case 'top':
					$output = $button . $content;
					break;

				case 'top_bottom':
					$output = $button . $content . $button;
					break;

				default:
					$output = $content . $button;
					break;
			}
		}

		return apply_filters( 'flavor_like_the_content', $output, $content );
	}
	add_filter( 'the_content', 'flavor_like_put_posts', 15 );
	add_filter( 'the_excerpt', 'flavor_like_put_posts', 15 );
}

/*******************************************************
  Comments Auto Display
*******************************************************/

if( ! function_exists( 'flavor_like_put_comments' ) ){
	/**
	 * Auto insert flavor_like_comments in the comments content
	 *
	 * @param string $content
	 * @param object $com
	 * @return string
	 */
	function flavor_like_put_comments( $content, $comment = null ) {
		// Stack variable
		$output = $content;

		/**
		 * Don't append like dislike when links are being checked
		 */
		if( isset($_REQUEST['comment']) ){
			return $content;
		}

		/**
		 * Don't implement on admin section
		 */
		if( FlavorLikeInit::is_admin_backend() && ! FlavorLikeInit::is_ajax() ){
			return $content;
		}

		if ( flavor_like_setting_repo::isAutoDisplayOn('comment') && FlavorLikeInit::is_frontend() && isset( $comment->comment_ID ) ) {
			//auto display position
			$position = flavor_like_get_option( 'comments_group|auto_display_position', 'bottom' );
			//add flavor_like function
			$button   = flavor_like_comments( 'put', array(
				'id' => $comment->comment_ID
			) );
			// Check position
			switch ($position) {
				case 'top':
					$output = $button . $content;
					break;

				case 'top_bottom':
					$output = $button . $content . $button;
					break;

				default:
					$output = $content . $button;
					break;
			}
		}

		return apply_filters( 'flavor_like_comment_text', $output, $content, $comment );
	}
	add_filter( 'comment_text', 'flavor_like_put_comments', 15, 2 );
}

/*******************************************************
  Other
*******************************************************/

if( ! function_exists( 'flavor_like_register_widget' ) ){
	/**
	 * Register Flavor Like Widgets
	 *
	 * @author Alimir
	 * @since 1.2
	 * @return Void
	 */
	function flavor_like_register_widget() {
		register_widget( 'flavor_like_widget' );
	}
	add_action( 'widgets_init', 'flavor_like_register_widget' );
}

if( ! function_exists( 'flavor_like_generate_microdata' ) ){
	/**
	 * Generate rich snippet hooks
	 *
	 * @param array $args
	 * @return string
	 */
	function flavor_like_generate_microdata( $args ){
		// Bulk output
		$output = '';

		// Check flavor_like type
		switch ( $args['type'] ) {
			case 'likeThis':
				$output = apply_filters( 'flavor_like_posts_microdata', null );
				break;

			case 'likeThisComment':
				$output = apply_filters( 'flavor_like_comments_microdata', null );
				break;

			case 'likeThisActivity':
				$output = apply_filters( 'flavor_like_activities_microdata', null );
				break;

			case 'likeThisTopic':
				$output = apply_filters( 'flavor_like_topics_microdata', null );
				break;
		}

		echo $output;
	}
	add_action( 'flavor_like_inside_template', 'flavor_like_generate_microdata' );
}

if( ! function_exists( 'flavor_like_display_inline_likers_template' ) ){
	/**
	 * Display inline likers box without AJAX request
	 *
	 * @param array $args
	 * @since 3.5.1
	 * @return void
	 */
	function flavor_like_display_inline_likers_template( $args ){
		// Return if likers is hidden
		if( empty( $args['display_likers'] ) ){
			return;
		}
		// Get settings for current type
		$get_settings = flavor_like_get_post_settings_by_type( $args['type'] );
		// If method not exist, then return error message
		if( flavor_like_setting_repo::restrictLikersBox( $args['type'] ) || empty( $get_settings ) || empty( $args['ID'] ) ) {
			return;
		}
		// Extract settings array - assign explicitly per WordPress coding standards
		$table = isset( $get_settings['table'] ) ? $get_settings['table'] : '';
		$column = isset( $get_settings['column'] ) ? $get_settings['column'] : '';
		$setting = isset( $get_settings['setting'] ) ? $get_settings['setting'] : '';

		if( $args['disable_pophover'] || $args['likers_style'] == 'default' ){
			echo sprintf(
			'<div class="flavor_like_likers_wrapper wp_%s_likers_%s">%s</div>',
			esc_attr($args['type']), esc_attr( $args['ID'] ), flavor_like_get_likers_template( $table, $column, $args['ID'], $setting, array( 'style' => 'default' ) ) );
		}

		do_action( 'flavor_like_inline_display_likers_box', $args, $get_settings );
	}
	add_action( 'flavor_like_inside_template', 'flavor_like_display_inline_likers_template' );
}

if( ! function_exists( 'flavor_like_update_button_icon' ) ){
	/**
	 * Update button icons
	 *
	 * @param array $args
	 * @return void
	 */
	function flavor_like_update_button_icon( $args ){
		$button_type  = flavor_like_get_option( $args['setting'] . '|button_type' );
		$image_group  = flavor_like_get_option( $args['setting'] . '|image_group' );
		$return_style = null;

		// Check value
		if( $button_type !== 'image' || empty( $image_group ) || ! in_array( $args['style'], array( 'flavorlike-default', 'flavor-like-pro-default', 'flavorlike-heart' ) ) ){
			return;
		}

		if( isset( $image_group['like'] ) && ! empty( $image_group['like'] ) ) {
			$return_style .= '.flavor_like_btn.flavor_like_put_image:after { background-image: url('.esc_url($image_group['like']).') !important; }';
		}
		if( isset( $image_group['unlike'] ) && ! empty( $image_group['unlike'] ) ) {
			$return_style .= '.flavor_like_btn.flavor_like_put_image.flavor_like_btn_is_active:after { background-image: url('.esc_url($image_group['unlike']).') !important; filter:none; }';
		}
		if( isset( $image_group['dislike'] ) && ! empty( $image_group['dislike'] ) ) {
			$return_style .= '.flavorlike_down_vote .flavor_like_btn.flavor_like_put_image:after { background-image: url('.esc_url($image_group['dislike']).') !important; }';
		}
		if( isset( $image_group['undislike'] ) && ! empty( $image_group['undislike'] ) ) {
			$return_style .= '.flavorlike_down_vote .flavor_like_btn.flavor_like_put_image.flavor_like_btn_is_active:after { background-image: url('.esc_url($image_group['undislike']).') !important; filter:none; }';
		}

		echo !empty( $return_style ) ? sprintf( '<style>%s</style>', wp_strip_all_tags( $return_style ) ) : '';
	}
	add_action( 'flavor_like_inside_template', 'flavor_like_update_button_icon', 1 );
}


if( ! function_exists( 'flavor_like_load_deprecated_classes' ) ){
	/**
	 * Load deprecated classes for backward compatibility
	 *
	 * @return void
	 */
	function flavor_like_load_deprecated_classes(){
		require_once( FLAVOR_LIKE_ADMIN_DIR . '/includes/deprecated.class.php');
	}
	add_action( 'plugins_loaded', 'flavor_like_load_deprecated_classes', 999 );
}


if( ! function_exists( 'flavor_like_run_php_snippets' ) ){
	/**
	 * Run php snippets
	 *
	 * @return void
	 */
	function flavor_like_run_php_snippets(){
		if( flavor_like_setting_repo::isCodeSnippetsDisabled() ){
			return;
		}

		$php_snippets = flavor_like_setting_repo::getPhpSnippets();

		if( empty( $php_snippets ) ){
			return;
		}

		if ( class_exists( '\\ParseError' ) ) {
			try {
				eval( $php_snippets ); // phpcs:ignore
			} catch( \ParseError $e ) { // phpcs:ignore
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Flavor Like PHP Snippet Error: ' . $e->getMessage() );
				}
			}
		} else {
			eval( $php_snippets ); // phpcs:ignore
		}
	}
	add_action( 'flavor_like_loaded', 'flavor_like_run_php_snippets' );
}

if( ! function_exists( 'flavor_like_run_javascript_snippets' ) ){
	/**
	 * Run js snippets
	 *
	 * @return void
	 */
	function flavor_like_run_javascript_snippets(){
		if( flavor_like_setting_repo::isCodeSnippetsDisabled() ){
			return;
		}

		$js_snippets = flavor_like_setting_repo::getJsSnippets();

		if( empty( $js_snippets ) ){
			return;
		}

		$js_snippets = trim( $js_snippets, "\n" );

		printf( "<script type='text/javascript' id='%s'>\n%s\n</script>\n", 'flavor_like_js_snippets', $js_snippets );

	}
	add_action( 'wp_footer', 'flavor_like_run_javascript_snippets', 100 );
}

if( ! function_exists( 'flavor_like_delete_post_votes' ) ){
	/**
	 * Fires after the activity item has been deleted.
	 *
	 * @param array $args
	 * @return void
	 */
	function flavor_like_delete_post_votes( $ID ) {
		global $wpdb, $post_type;
		$type = in_array( $post_type, array('forum','topic','reply') ) ? 'topic' : 'post';

		// delete post votes
		flavor_like_delete_vote_data( $ID, $type );

		// don't check comments for bbpress
		if( $type == 'topic' ){
			return;
		}

		// delete comments if exist
		if ( flavor_like_use_pulse_queries() ) {
			$comment_ids = get_comments(
				array(
					'post_id' => $ID,
					'fields'  => 'ids',
					'number'  => 0,
				)
			);
			if ( ! empty( $comment_ids ) ) {
				foreach ( $comment_ids as $comment_id ) {
					flavor_like_delete_vote_data( $comment_id, 'comment' );
				}
			}
			return;
		}

		$comments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
				c.comment_ID
				FROM
					$wpdb->comments c
					INNER JOIN {$wpdb->prefix}flavor_like_comments uc ON c.comment_ID = uc.comment_id
				WHERE
					c.comment_post_ID = %d
					GROUP BY c.comment_ID",
				$ID
			)
		);

		if( ! empty( $comments ) ){
			foreach ($comments as $comment_ID) {
				flavor_like_delete_vote_data( $comment_ID, 'comment' );
			}
		}
	}
	add_action( 'before_delete_post', 'flavor_like_delete_post_votes', 1, 10 );
}

if( ! function_exists( 'flavor_like_delete_comment_votes' ) ){
	/**
	 * Fires after the comment item has been deleted.
	 *
	 * @param integer $ID
	 * @return void
	 */
	function flavor_like_delete_comment_votes( $ID ) {
		flavor_like_delete_vote_data( $ID, 'comment' );
	}
	add_action( 'deleted_comment', 'flavor_like_delete_comment_votes', 1, 10 );
}


if( ! function_exists( 'flavor_like_delete_activity_votes' ) ){
	/**
	 * Fires after the activity item has been deleted.
	 *
	 * @param array $args
	 * @return void
	 */
	function flavor_like_delete_activity_votes( $args ){
		if( ! empty( $args['id'] ) ){
			flavor_like_delete_vote_data( $args['id'], 'activity' );
		}
	}
	add_action( 'bp_activity_delete', 'flavor_like_delete_activity_votes', 1, 10 );
}

