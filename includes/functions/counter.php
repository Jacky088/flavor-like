<?php
/**
 * Counter Functions
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

if( ! function_exists( 'flavor_like_update_meta_counter_value' ) ){
	/**
	 * Update meta counter value
	 *
	 * @param integer $ID
	 * @param string $value
	 * @param string $type
	 * @param string $status
	 * @param boolean $is_distinct
	 * @return int|bool
	 */
	function flavor_like_update_meta_counter_value( $ID, $value, $type, $status, $is_distinct = true, $prev_value = '' ){
		$distinct = !$is_distinct ? 'total' : 'distinct';
		$meta_key = sprintf( 'count_%s_%s', $distinct, $status );
		return flavor_like_update_meta_data( $ID, $type, $meta_key, $value, $prev_value );
	}
}

if( ! function_exists( 'flavor_like_meta_counter_value' ) ){
	/**
	 * Get meta counter value
	 *
	 * @param integer $ID
	 * @param string $type
	 * @param string $status
	 * @param boolean $is_distinct
	 * @return null|integer
	 */
	function flavor_like_meta_counter_value( $ID, $type, $status, $is_distinct = true ){
		$distinct = ! $is_distinct ? 'total' : 'distinct';
		$meta_key = sprintf( 'count_%s_%s', $distinct, $status );
		$meta_val = flavor_like_get_meta_data( $ID, $type, $meta_key, true );

		if( ( empty( $meta_val ) && ! is_numeric( $meta_val ) ) ){
			return NULL;
		}

		return (int) $meta_val;
	}
}

if( ! function_exists( 'flavor_like_get_counter_value_info' ) ){
	/**
	 * Get counter value data
	 *
	 * @param integer $ID
	 * @param string $type
	 * @param string $status
	 * @param boolean $is_distinct
	 * @param string|array $date_range
	 * @return WP_Error[]|integer
	 */
	function flavor_like_get_counter_value_info( $ID, $type, $status = 'like', $is_distinct = true, $date_range = NULL ){
		// Remove 'un' prefix from status
		$status = ltrim( $status, 'un');

		if( ( empty( $ID ) && !is_numeric($ID) ) || empty( $type ) ){
			return new WP_Error( 'broke', esc_html__( "Please enter some value for required variables.", 'flavor-like' ) );
		}

		$counter_value = flavor_like_meta_counter_value( $ID, $type, $status, $is_distinct );

		if( is_null( $counter_value ) || ! empty( $date_range ) ){
			global $wpdb;

			// period limit SQL
			$period_limit = flavor_like_get_period_limit_sql( $date_range );

			// get table info
			$table_info   = flavor_like_get_table_info( $type );
			if( empty( $table_info ) ){
				return new WP_Error( 'broke', esc_html__( "Table info is empty.", 'flavor-like' ) );
			}

			$table = isset( $table_info['table'] ) ? $table_info['table'] : '';
			$column = isset( $table_info['column'] ) ? $table_info['column'] : '';

			$status_condition = $status !== 'all' ? $wpdb->prepare( "`status` = %s", $status ) : "`status` NOT LIKE 'un%'";
			$count_type       = $is_distinct ? "DISTINCT `user_id`" : "*";
			$table_escaped = esc_sql( $wpdb->prefix . $table );
			$column_escaped = esc_sql( $column );

			if ( flavor_like_use_pulse_queries() ) {
				$counter_value = Flavor_Like_Pulse_Query::count_item_votes( $ID, $type, $status, $is_distinct, $date_range );
			} else {
				$counter_value  = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT({$count_type}) FROM `{$table_escaped}` WHERE {$status_condition} AND `{$column_escaped}` = %d {$period_limit}",
					$ID
				) );
				$counter_value  = empty( $counter_value ) ? 0 : (int) $counter_value;
			}

			if( empty( $date_range ) ){
				// Add counter to meta value
				flavor_like_update_meta_counter_value( $ID, $counter_value, $type, $status, $is_distinct );
			}
		}

		// By checking this option, users who have upgraded to version +4 and deleted their old logs can add the number of old likes to the new figures.
		$enable_meta_values = flavor_like_get_option( 'enable_meta_values', false );
		if( flavor_like_is_true( $enable_meta_values ) && in_array( $status, array( 'like', 'all' ) ) ){
			$counter_value += flavor_like_get_old_meta_value( $ID, $type );
		}

		// Create an action when counter value is ready.
		do_action('flavor_like_counter_value_generated');

		return apply_filters( 'flavor_like_counter_value' , $counter_value, $ID, $type, $status, $date_range );
	}
}

if( ! function_exists( 'flavor_like_get_counter_value' ) ){
	/**
	 * Get counter value
	 *
	 * @param integer $ID
	 * @param string $type
	 * @param string $status
	 * @param boolean $is_distinct
	 * @param string|array $date_range
	 * @return integer
	 */
	function flavor_like_get_counter_value( $ID, $type, $status = 'like', $is_distinct = true, $date_range = NULL ){
		$counter_info = flavor_like_get_counter_value_info( $ID, $type, $status, $is_distinct, $date_range );
		return ! is_wp_error( $counter_info ) ? absint( $counter_info ) : 0;
	}
}

if( ! function_exists( 'flavor_like_get_old_meta_value' ) ){
	/**
	 * Get the number of old meta values
	 *
	 * @param integer $ID
	 * @param string $type
	 * @return integer
	 */
	function flavor_like_get_old_meta_value( $ID, $type ){
		$meta_value = 0;

		switch ( $type ) {
			case 'post':
				$meta_value = get_post_meta( $ID, '_liked', true );
				break;
			case 'comment':
				$meta_value = get_comment_meta( $ID, '_commentliked', true );
				break;
			case 'activity':
				$meta_value = function_exists( 'bp_activity_get_meta' ) ? bp_activity_get_meta( $ID, '_activityliked' ) : '';
				break;
			case 'topic':
				$meta_value = get_post_meta( $ID, '_topicliked', true );
				break;
		}

		return empty( $meta_value ) ? 0 : (int) $meta_value;
	}
}