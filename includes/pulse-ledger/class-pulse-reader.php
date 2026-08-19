<?php
/**
 * Pulse Ledger — per-user vote state reader.
 *
 * @package Flavor_Like
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Flavor_Like_Pulse_Reader' ) ) {

	final class Flavor_Like_Pulse_Reader {

		/**
		 * Resolve user's latest legacy action for an item.
		 *
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical or setting type.
		 * @return string|false like|dislike|unlike|undislike|false
		 */
		public static function user_action( $item_id, $user_id, $item_type ) {
			$item_type = Flavor_Like_Pulse_Registry::normalize_item_type( $item_type );
			$read_mode = Flavor_Like_Pulse_Config::read_mode();

			if ( Flavor_Like_Pulse_Config::READ_PULSE === $read_mode ) {
				return self::from_pulse( $item_id, $user_id, $item_type );
			}

			if ( Flavor_Like_Pulse_Config::READ_LEGACY === $read_mode ) {
				return self::from_legacy( $item_id, $user_id, $item_type );
			}

			return self::from_merged( $item_id, $user_id, $item_type );
		}

		/**
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical type.
		 * @return string|false
		 */
		private static function from_pulse( $item_id, $user_id, $item_type ) {
			global $wpdb;

			$table = esc_sql( Flavor_Like_Pulse_Schema::table() );
			$row   = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT engagement_key, status, date_time
					FROM `{$table}`
					WHERE item_id = %d AND item_type = %s AND user_id = %s AND engagement_kind = %s
					ORDER BY date_time DESC, id DESC
					LIMIT 1",
					absint( $item_id ),
					$item_type,
					(string) $user_id,
					Flavor_Like_Pulse_Registry::KIND_VOTE
				)
			);

			if ( ! $row ) {
				return false;
			}

			return Flavor_Like_Pulse_Vote_Map::row_to_legacy( $row->engagement_key, $row->status );
		}

		/**
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical type.
		 * @return string|false
		 */
		private static function from_legacy( $item_id, $user_id, $item_type ) {
			global $wpdb;

			$source = Flavor_Like_Pulse_Registry::legacy_source_for_type( $item_type );
			if ( ! $source || ! Flavor_Like_Pulse_Registry::table_exists( $source['table'] ) ) {
				return false;
			}

			$table  = esc_sql( $source['table'] );
			$column = esc_sql( $source['column'] );

			$status = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT status FROM `{$table}` WHERE `{$column}` = %d AND user_id = %s ORDER BY id DESC LIMIT 1",
					absint( $item_id ),
					(string) $user_id
				)
			);

			return $status ? (string) $status : false;
		}

		/**
		 * Newest row wins between legacy snapshot and pulse delta.
		 *
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical type.
		 * @return string|false
		 */
		private static function from_merged( $item_id, $user_id, $item_type ) {
			$legacy = self::legacy_latest_row( $item_id, $user_id, $item_type );
			$pulse  = self::pulse_latest_row( $item_id, $user_id, $item_type );

			if ( ! $legacy && ! $pulse ) {
				return false;
			}

			if ( $legacy && ! $pulse ) {
				return (string) $legacy->status;
			}

			if ( $pulse && ! $legacy ) {
				return Flavor_Like_Pulse_Vote_Map::row_to_legacy( $pulse->engagement_key, $pulse->status );
			}

			$legacy_time = strtotime( $legacy->date_time );
			$pulse_time  = strtotime( $pulse->date_time );

			if ( $pulse_time >= $legacy_time ) {
				return Flavor_Like_Pulse_Vote_Map::row_to_legacy( $pulse->engagement_key, $pulse->status );
			}

			return (string) $legacy->status;
		}

		/**
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical type.
		 * @return object|null
		 */
		private static function legacy_latest_row( $item_id, $user_id, $item_type ) {
			global $wpdb;

			$source = Flavor_Like_Pulse_Registry::legacy_source_for_type( $item_type );
			if ( ! $source || ! Flavor_Like_Pulse_Registry::table_exists( $source['table'] ) ) {
				return null;
			}

			$table  = esc_sql( $source['table'] );
			$column = esc_sql( $source['column'] );

			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT status, date_time FROM `{$table}` WHERE `{$column}` = %d AND user_id = %s ORDER BY id DESC LIMIT 1",
					absint( $item_id ),
					(string) $user_id
				)
			);
		}

		/**
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical type.
		 * @return object|null
		 */
		private static function pulse_latest_row( $item_id, $user_id, $item_type ) {
			global $wpdb;

			$table = esc_sql( Flavor_Like_Pulse_Schema::table() );
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT engagement_key, status, date_time FROM `{$table}`
					WHERE item_id = %d AND item_type = %s AND user_id = %s AND engagement_kind = %s
					ORDER BY date_time DESC, id DESC LIMIT 1",
					absint( $item_id ),
					$item_type,
					(string) $user_id,
					Flavor_Like_Pulse_Registry::KIND_VOTE
				)
			);
		}
	}
}
