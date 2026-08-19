<?php
/**
 * Privacy (personal data export / erase) for Flavor Like log tables.
 *
 * @package WP_Flavor Like
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log tables that store a WordPress user id in the user_id column.
 *
 * @return array<string, string> table_suffix => human label
 */
function flavor_like_privacy_log_tables() {
	$labels = array(
		'flavor_like'            => __( 'Posts', 'flavor-like' ),
		'flavor_like_comments'   => __( 'Comments', 'flavor-like' ),
		'flavor_like_activities' => __( 'Activities', 'flavor-like' ),
		'flavor_like_forums'     => __( 'Topics', 'flavor-like' ),
	);

	global $wpdb;
	$tables = array();

	foreach ( Flavor_Like_Pulse_Registry::legacy_sources() as $source ) {
		$suffix            = str_replace( $wpdb->prefix, '', $source['table'] );
		$tables[ $suffix ] = isset( $labels[ $suffix ] ) ? $labels[ $suffix ] : $suffix;
	}

	return $tables;
}

/**
 * @param string $email_address User email.
 * @param int    $page          Page number (1-based).
 * @return array{data: array<int, array>, done: bool}
 */
function flavor_like_privacy_exporter( $email_address, $page = 1 ) {
	$email_address = trim( $email_address );
	$page          = max( 1, (int) $page );

	$user = get_user_by( 'email', $email_address );
	if ( ! $user instanceof WP_User ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$uid    = (string) (int) $user->ID;
	$per_page = 100;
	$data     = array();
	$labels   = flavor_like_privacy_log_tables();

	if ( flavor_like_use_pulse_queries() ) {
		$rows = Flavor_Like_Pulse_Log_Bridge::get_privacy_rows( $uid, $page, $per_page );
	} else {
		global $wpdb;

		$offset       = ( $page - 1 ) * $per_page;
		$union_parts  = array();
		$prepare_args = array();

		foreach ( Flavor_Like_Pulse_Registry::legacy_sources() as $source ) {
			$suffix        = str_replace( $wpdb->prefix, '', $source['table'] );
			$table         = esc_sql( $source['table'] );
			$geo_cols      = Flavor_Like_Pulse_Log_Bridge::legacy_personal_columns_sql( $source['table'] );
			$union_parts[] = "(SELECT '{$suffix}' AS src, id, date_time, status, ip, {$geo_cols} FROM `{$table}` WHERE user_id = %s)";
			$prepare_args[] = $uid;
		}

		if ( empty( $union_parts ) ) {
			$rows = array();
		} else {
			$sql            = 'SELECT * FROM ( ' . implode( ' UNION ALL ', $union_parts ) . ' ) AS combined ORDER BY date_time DESC, src ASC, id DESC LIMIT %d OFFSET %d';
			$prepare_args[] = $per_page;
			$prepare_args[] = $offset;
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table names from plugin registry.
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $prepare_args ), ARRAY_A );
		}
	}

	if ( ! empty( $rows ) ) {
		$anonymise_ip = flavor_like_setting_repo::isAnonymiseIpOn();
		foreach ( $rows as $row ) {
			$src   = isset( $row['src'] ) ? $row['src'] : '';
			$label = isset( $labels[ $src ] ) ? $labels[ $src ] : __( 'Logs', 'flavor-like' );
			$ip    = isset( $row['ip'] ) ? $row['ip'] : '';
			if ( $anonymise_ip && '' !== $ip ) {
				$ip = wp_privacy_anonymize_data( 'ip_address', $ip );
			}

			$pairs = array(
				__( 'Date', 'flavor-like' )   => isset( $row['date_time'] ) ? $row['date_time'] : '',
				__( 'Status', 'flavor-like' ) => isset( $row['status'] ) ? $row['status'] : '',
				__( 'IP', 'flavor-like' )     => $ip,
			);

			foreach ( array(
				'fingerprint'  => __( 'Fingerprint', 'flavor-like' ),
				'country_code' => __( 'Country', 'flavor-like' ),
				'device'       => __( 'Device', 'flavor-like' ),
				'os'           => __( 'OS', 'flavor-like' ),
				'browser'      => __( 'Browser', 'flavor-like' ),
			) as $key => $name ) {
				$value = isset( $row[ $key ] ) ? $row[ $key ] : '';
				if ( null !== $value && '' !== $value ) {
					$pairs[ $name ] = $value;
				}
			}

			$data[] = array(
				'group_id'    => 'flavor-like',
				'group_label' => __( 'Flavor Like', 'flavor-like' ),
				'item_id'     => $src . '-' . (int) $row['id'],
				'data'        => array(
					array(
						'name'  => $label,
						'value' => implode( ', ', array_map(
							static function ( $k, $v ) {
								return $k . ': ' . $v;
							},
							array_keys( $pairs ),
							array_values( $pairs )
						) ),
					),
				),
			);
		}
	}

	$has_more = is_array( $rows ) && count( $rows ) === $per_page;

	return array(
		'data' => $data,
		'done' => ! $has_more,
	);
}

/**
 * @param string $email_address User email.
 * @param int    $page          Page (unused; single pass).
 * @return array{items_removed: bool, items_retained: bool, messages: string[], done: bool}
 */
function flavor_like_privacy_eraser( $email_address, $page = 1 ) {
	$email_address = trim( $email_address );
	$user          = get_user_by( 'email', $email_address );

	if ( ! $user instanceof WP_User ) {
		return array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	$uid    = (string) (int) $user->ID;
	$total  = 0;

	if ( flavor_like_use_pulse_queries() ) {
		$total = Flavor_Like_Pulse_Log_Bridge::erase_user_logs( $uid );
	} else {
		global $wpdb;

		foreach ( array_keys( flavor_like_privacy_log_tables() ) as $suffix ) {
			$table = $wpdb->prefix . $suffix;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from fixed list.
			$result = $wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE user_id = %s", $uid ) );
			if ( false !== $result ) {
				$total += (int) $result;
			}
		}
	}

	$messages = array();
	if ( $total > 0 ) {
		$messages[] = sprintf(
			/* translators: %d: number of rows removed */
			__( 'Removed %d Flavor Like log row(s) for this user.', 'flavor-like' ),
			$total
		);
	}

	return array(
		'items_removed'  => $total > 0,
		'items_retained' => false,
		'messages'       => $messages,
		'done'           => true,
	);
}

/**
 * @param array<string, array> $exporters Registered exporters.
 * @return array<string, array>
 */
function flavor_like_privacy_register_exporters( $exporters ) {
	$exporters['flavor-like'] = array(
		'exporter_friendly_name' => __( 'Flavor Like', 'flavor-like' ),
		'callback'               => 'flavor_like_privacy_exporter',
	);
	return $exporters;
}

/**
 * @param array<string, array> $erasers Registered erasers.
 * @return array<string, array>
 */
function flavor_like_privacy_register_erasers( $erasers ) {
	$erasers['flavor-like'] = array(
		'eraser_friendly_name' => __( 'Flavor Like vote logs', 'flavor-like' ),
		'callback'             => 'flavor_like_privacy_eraser',
	);
	return $erasers;
}

add_filter( 'wp_privacy_personal_data_exporters', 'flavor_like_privacy_register_exporters' );
add_filter( 'wp_privacy_personal_data_erasers', 'flavor_like_privacy_register_erasers' );
