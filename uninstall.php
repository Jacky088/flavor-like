<?php
/**
 * Uninstall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Uninstall class
 *
 * @class flavor_like_uninstall
 * @since 1.0.0
 */
class flavor_like_uninstall {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {

		if ( is_multisite() ) {
			$this->uninstall_sites();
		} else {
			$this->uninstall_site();
		}
	}

	/**
	 * Process uninstall on each sites (multisite)
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function uninstall_sites() {

		global $wpdb;

		// Save current blog ID.
		$current  = $wpdb->blogid;
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

		// Create tables for each blog ID.
		foreach ( $blog_ids as $blog_id ) {

			switch_to_blog( $blog_id );
			$this->uninstall_site();

		}

		// Go back to current blog.
		switch_to_blog( $current );

	}

	/**
	 * Process uninstall on current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function uninstall_site() {
		/*
		* Only remove ALL data if FLAVOR_LIKE_REMOVE_ALL_DATA constant is set to true in user's
		* wp-config.php. This is to prevent data loss when deleting the plugin from the backend
		* and to ensure only the site owner can perform this action.
		*/
		if ( defined( 'FLAVOR_LIKE_REMOVE_ALL_DATA' ) && true === FLAVOR_LIKE_REMOVE_ALL_DATA ) {
			$this->clear_scheduled_tasks();
			$this->drop_tables();
			$this->delete_transients();
			$this->delete_options();
			$this->delete_user_meta();
			$this->delete_counter_meta();
			$this->delete_files();
			$this->delete_lock_files();
		}
	}

	/**
	 * Unschedule WP-Cron jobs registered by the plugin.
	 *
	 * @since 5.2.0
	 * @access public
	 * @return void
	 */
	public function clear_scheduled_tasks() {
		wp_clear_scheduled_hook( 'flavor_like_pulse_sync_batch' );
	}

	/**
	 * Drop plugin custom tables from current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function drop_tables() {

		global $wpdb;

		$wpdb->query(
			"DROP TABLE IF EXISTS
			{$wpdb->prefix}flavor_like,
			{$wpdb->prefix}flavor_like_comments,
			{$wpdb->prefix}flavor_like_activities,
			{$wpdb->prefix}flavor_like_forums,
			{$wpdb->prefix}flavor_like_meta,
			{$wpdb->prefix}flavor_like_pulse"
		);

	}

	/**
	 * Delete plugin transients from current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function delete_transients() {

		global $wpdb;

		// Delete all plugin metadata.
		$options_table = $wpdb->options;
		$wpdb->query( $wpdb->prepare( "DELETE from `{$options_table}` WHERE option_name LIKE %s", '_transient_flavor-like%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE from `{$options_table}` WHERE option_name LIKE %s", '_transient_timeout_flavor-like%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE from `{$options_table}` WHERE option_name LIKE %s", '_transient_flavor_like%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE from `{$options_table}` WHERE option_name LIKE %s", '_transient_timeout_flavor_like%' ) );
	}

	/**
	 * Delete plugin options from current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function delete_options() {

		global $wpdb;

		delete_option( 'widget_flavor_like' );

		// Remove flavor_like_* options (free, pulse, legacy, unknown), but keep Pro's
		// license options intact -- consistent with delete_user_meta() below, so
		// uninstalling Free doesn't silently de-license a co-installed Pro.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$wpdb->options}` WHERE option_name LIKE %s AND option_name NOT LIKE %s",
				$wpdb->esc_like( 'flavor_like_' ) . '%',
				$wpdb->esc_like( 'flavor_like_pro_' ) . '%'
			)
		);
	}

	/**
	 * Delete plugin files
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function delete_files() {

		global $wp_filesystem;

		// Get filesystem.
		if ( empty( $wp_filesystem ) ) {

			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
			}

			WP_Filesystem();

		}

		$wp_content = $wp_filesystem->wp_content_dir();

		$wp_filesystem->delete( $wp_content . '/uploads/flavor-like', true );
	}

	/**
	 * Delete activation pointer and other plugin user meta.
	 *
	 * @since 5.0.6
	 * @access public
	 * @return void
	 */
	public function delete_user_meta() {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$wpdb->usermeta}` WHERE meta_key LIKE %s AND meta_key NOT LIKE %s",
				$wpdb->esc_like( 'flavor_like_' ) . '%',
				$wpdb->esc_like( 'flavor_like_pro_' ) . '%'
			)
		);
	}

	/**
	 * Delete legacy counter meta stored on WordPress posts and comments.
	 *
	 * BuddyPress activity counters live in BP meta tables and are not removed here.
	 *
	 * @since 5.2.0
	 * @access public
	 * @return void
	 */
	public function delete_counter_meta() {

		global $wpdb;

		$post_meta_keys = array( '_liked', '_topicliked' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$wpdb->postmeta}` WHERE meta_key IN ( %s, %s )",
				$post_meta_keys[0],
				$post_meta_keys[1]
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->commentmeta,
			array( 'meta_key' => '_commentliked' ),
			array( '%s' )
		);
	}

	/**
	 * Delete stale vote lock files from the system temp directory.
	 *
	 * Vote mutexes now use MySQL GET_LOCK (no files are created). This only
	 * cleans up flavor-like-{type}-{id}.lock leftovers from plugin versions
	 * that used file locks.
	 *
	 * On single-site installs the temp dir is site-specific enough to glob safely.
	 * On multisite, the same temp dir may be shared — skip to avoid touching other sites.
	 *
	 * @since 5.0.5
	 * @access public
	 * @return void
	 */
	public function delete_lock_files() {

		if ( is_multisite() ) {
			return;
		}

		$pattern = trailingslashit( get_temp_dir() ) . 'flavor-like-*.lock';
		$files   = glob( $pattern );

		if ( ! is_array( $files ) ) {
			return;
		}

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}
}

new flavor_like_uninstall();
