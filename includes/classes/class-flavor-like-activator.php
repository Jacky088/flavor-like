<?php
/**
 * Flavor Like Activator — installs meta + pulse storage.
 *
 * // @echo HEADER
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class flavor_like_activator {

	protected static $instance = null;

	public function activate() {
		$this->install_tables( false === get_option( 'flavor_like_dbVersion', false ) );
	}

	/**
	 * Create missing meta + pulse tables and bootstrap storage mode.
	 *
	 * @param bool $is_fresh_install Treat as brand-new site (pulse-only mode).
	 * @param bool $set_db_version   Update flavor_like_dbVersion when appropriate.
	 * @return bool
	 */
	public function install_tables( $is_fresh_install = false, $set_db_version = true ) {
		if ( ! Flavor_Like_Meta_Schema::install() ) {
			return false;
		}

		if ( ! Flavor_Like_Pulse_Schema::install() ) {
			return false;
		}

		Flavor_Like_Pulse_Schema::bootstrap_mode( $is_fresh_install );

		if ( $set_db_version && ( $is_fresh_install || false === get_option( 'flavor_like_dbVersion', false ) ) ) {
			update_option( 'flavor_like_dbVersion', FLAVOR_LIKE_DB_VERSION );
		}

		return true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
