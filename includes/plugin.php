<?php
/**
 * FLAVOR LIKE BASE CLASS
 *
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class FlavorLikeInit {

  /**
    * Instance of this class.
    *
    * @since    3.1
    *
    * @var      object
    */
  protected static $instance = null;

  /**
  * Initialize the plugin
  *
  * @since     3.1
  */
  private function __construct() {
    // init plugin
    $this->plugin();

    // This hook is called once any activated plugins have been loaded.
    add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

    $prefix = is_network_admin() ? 'network_admin_' : '';
    add_filter( "{$prefix}plugin_action_links", array( $this, 'add_links' ), 10, 2 );
    add_filter( "{$prefix}plugin_row_meta", array( $this, 'add_row_meta' ), 10, 2 );
  }

  /**
   * Plugins loaded hook
   *
   * @return void
   */
  public function plugins_loaded(){
    flavor_like_maybe_backfill_first_activated_at();

    // Migrate pre-rename (WP ULike) tables/options/uploads to the new
    // flavor_like_* names before any read happens on this request.
    $this->maybe_migrate_legacy_storage();

    // Only run upgrade check when plugin version changes to avoid
    // unnecessary queries on every admin page load
    $this->maybe_upgrade_database();
  }

  /**
   * One-time migration from the pre-1.0.6 "wp_ulike_*" namespace.
   *
   * Renames legacy tables, copies wp_ulike_* options and moves the
   * uploads/wp-ulike custom CSS directory to their flavor_like_* /
   * flavor-like counterparts. Runs once per site and is a no-op on
   * fresh installs.
   *
   * @return void
   */
  private function maybe_migrate_legacy_storage(){
    if ( get_option( 'flavor_like_storage_migrated' ) ) {
      return;
    }

    global $wpdb;

    $did_migrate = false;

    // 1) Rename legacy tables (only when the new table does not exist yet).
    $legacy_tables = array(
      'ulike'            => 'flavor_like',
      'ulike_comments'   => 'flavor_like_comments',
      'ulike_activities' => 'flavor_like_activities',
      'ulike_forums'     => 'flavor_like_forums',
      'ulike_meta'       => 'flavor_like_meta',
      'ulike_pulse'      => 'flavor_like_pulse',
    );

    foreach ( $legacy_tables as $old_suffix => $new_suffix ) {
      $old_table = $wpdb->prefix . $old_suffix;
      $new_table = $wpdb->prefix . $new_suffix;

      $old_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_table ) ) === $old_table;
      if ( ! $old_exists ) {
        continue;
      }

      $new_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table ) ) === $new_table;
      if ( $new_exists ) {
        continue;
      }

      // RENAME TABLE is atomic and instant (metadata-only) in MySQL/MariaDB.
      $wpdb->query( "RENAME TABLE `{$old_table}` TO `{$new_table}`" );
      $did_migrate = true;
    }

    // 2) Copy wp_ulike_* options (settings, versions, customizer, pulse state...).
    $like_pattern = $wpdb->esc_like( 'wp_ulike_' ) . '%';
    $legacy_options = $wpdb->get_results(
      $wpdb->prepare( "SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE %s", $like_pattern )
    );

    if ( ! empty( $legacy_options ) ) {
      foreach ( $legacy_options as $legacy_option ) {
        $new_name = 'flavor_like_' . substr( $legacy_option->option_name, strlen( 'wp_ulike_' ) );
        if ( false === get_option( $new_name, false ) ) {
          $autoload = in_array( $legacy_option->autoload, array( 'on', 'off', 'auto' ), true ) ? $legacy_option->autoload : 'yes';
          add_option( $new_name, maybe_unserialize( $legacy_option->option_value ), '', $autoload );
        }
      }
      $did_migrate = true;
    }

    // 3) Move uploads/wp-ulike (custom CSS) to uploads/flavor-like.
    $uploads = wp_get_upload_dir();
    $legacy_custom_dir = trailingslashit( $uploads['basedir'] ) . 'wp-ulike';
    if ( is_dir( $legacy_custom_dir ) ) {
      $new_custom_dir = trailingslashit( $uploads['basedir'] ) . FLAVOR_LIKE_SLUG;
      if ( ! is_dir( $new_custom_dir ) ) {
        wp_mkdir_p( $new_custom_dir );
      }
      foreach ( (array) glob( $legacy_custom_dir . '/*' ) as $legacy_file ) {
        if ( is_file( $legacy_file ) ) {
          $target = $new_custom_dir . '/' . basename( $legacy_file );
          if ( ! file_exists( $target ) ) {
            copy( $legacy_file, $target );
          }
        }
      }
      $did_migrate = true;
    }

    if ( $did_migrate ) {
      // Invalidate cached counters stored in legacy meta? Not needed — meta
        // values live in renamed tables and stay intact.
      wp_cache_flush();
    }

    update_option( 'flavor_like_storage_migrated', 1 );
  }

  private function maybe_upgrade_database(){
    // Check if plugin version has changed
    $current_plugin_version = get_option( 'flavor_like_plugin_version', '0' );

    // Only proceed with database checks if plugin version changed
    if ( version_compare( $current_plugin_version, FLAVOR_LIKE_VERSION, '>=' ) ) {
      return;
    }

    $stored = get_option( 'flavor_like_dbVersion', false );

    // Fresh installs set flavor_like_dbVersion during activation.
    if ( false === $stored ) {
      // Update plugin version for fresh installs
      update_option( 'flavor_like_plugin_version', FLAVOR_LIKE_VERSION );
      return;
    }

    $target = FLAVOR_LIKE_DB_VERSION;

    if ( version_compare( $stored, '2.4', '<' ) ) {
      if ( false === Flavor_Like_Legacy_Upgrade::run() ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
          error_log( sprintf( 'Flavor Like: Legacy database upgrade failed. Current version: %s', $stored ) );
        }
        return;
      }

      $stored = '2.4';
      update_option( 'flavor_like_dbVersion', $stored );
    }

    if ( version_compare( $stored, $target, '<' ) ) {
      $activator = flavor_like_activator::get_instance();

      if ( false === $activator->install_tables( false, false ) ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
          error_log( sprintf( 'Flavor Like: Storage upgrade to %s failed. Current version: %s', $target, $stored ) );
        }
        return;
      }

      update_option( 'flavor_like_dbVersion', $target );
    }

    if ( ! Flavor_Like_Meta_Schema::table_exists() || ! Flavor_Like_Pulse_Schema::table_exists() ) {
      flavor_like_activator::get_instance()->install_tables( false, false );
    }

    // Update plugin version after successful upgrade
    update_option( 'flavor_like_plugin_version', FLAVOR_LIKE_VERSION );
  }

  /**
  * Init the plugin when WordPress Initialises.
  *
  * @return void
  */
  public function plugin(){
    // Define constant values
    $this->define_constants();

    // load trasnlations
    $this->load_plugin_textdomain();

    // Include Files
    $this->includes();

    // Loaded action
    do_action( 'flavor_like_loaded' );
  }

  /**
   * Define constants
   *
   * @return void
   */
  private function define_constants(){
    // a custom directory in uploads directory for storing custom files. Default uploads/{FLAVOR_LIKE_SLUG}
    $uploads = wp_get_upload_dir();
    define( 'FLAVOR_LIKE_CUSTOM_DIR' , $uploads['basedir'] . '/' . FLAVOR_LIKE_SLUG );
    define( 'FLAVOR_LIKE_CUSTOM_URL' , $uploads['baseurl'] . '/' . FLAVOR_LIKE_SLUG );
  }

  /**
   * Add admin links
   *
   * @param array $actions
   * @param string $plugin_file
   * @return array
   */
  public function add_links( $actions, $plugin_file ) {

    if (  $plugin_file === FLAVOR_LIKE_BASENAME ) {
      $settings = array('settings'  => '<a href="admin.php?page=flavor-like-settings">' . esc_html__('Settings', 'flavor-like') . '</a>');
      $stats    = array('stats'     => '<a href="admin.php?page=flavor-like-statistics">' . esc_html__('Statistics', 'flavor-like') . '</a>');
      $about    = array('overview'  => '<a href="admin.php?page=flavor-like-about">' . esc_html__( 'Overview', 'flavor-like' ) . '</a>');
      // Merge on actions array
      $actions  = array_merge( $about, $actions );
      $actions  = array_merge( $stats, $actions );
      $actions  = array_merge( $settings, $actions );
    }

    return $actions;
  }

  /**
   * Add documentation link under the plugin description on the Plugins screen.
   *
   * @param array  $links       Plugin row meta links.
   * @param string $plugin_file Plugin basename.
   * @return array
   */
  public function add_row_meta( $links, $plugin_file ) {
    if ( FLAVOR_LIKE_BASENAME !== $plugin_file ) {
      return $links;
    }

    return $links;
  }


  /**
   * Auto-load classes on demand to reduce memory consumption
   *
   * @param mixed $class
   * @return void
   */
  public function autoload( $class ) {
    $path  = null;
    $class = strtolower( $class );
    $file = 'class-' . str_replace( '_', '-', $class ) . '.php';

    // the possible pathes containing classes
    $possible_pathes = array(
        FLAVOR_LIKE_INC_DIR   . '/classes/',
        FLAVOR_LIKE_ADMIN_DIR . '/classes/'
    );

    foreach ( $possible_pathes as $path ) {
        if( is_readable( $path . $file ) ){
            include_once( $path . $file );
            return;
        }
    }
  }

  /**
   * Include Files
   *
   * @return void
  */
  private function includes() {
    // Auto-load classes on demand
    spl_autoload_register( array( $this, 'autoload' ) );

    // load common functionalities
    include_once( FLAVOR_LIKE_INC_DIR . '/index.php' );

    // Dashboard and Administrative Functionality
    if ( self::is_admin_backend() ) {
      // Load admin specific codes
      include( FLAVOR_LIKE_ADMIN_DIR . '/index.php' );

      // Load AJAX specific codes on demand
      if ( self::is_ajax() ){
        include( FLAVOR_LIKE_INC_DIR . '/hooks/frontend-ajax.php' );
        include( FLAVOR_LIKE_ADMIN_DIR . '/admin-ajax.php'  );
      }
    }

    // Load Frontend Functionality
    if( self::is_frontend() ){
      include( FLAVOR_LIKE_INC_DIR . '/public/index.php' );
    }
  }

  /**
   * Is ajax
   *
   * @return bool
   */
  public static function is_ajax() {
    return ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || defined( 'DOING_AJAX' );
  }

  /**
   * Is admin
   *
   * @return bool
   */
  public static function is_admin_backend() {
    return is_admin();
  }

  /**
   * Is cron
   *
   * @return bool
   */
  public static function is_cron() {
    return ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) || defined( 'DOING_CRON' );
  }

  /**
   * Is rest
   *
   * @return bool
   */
  public static function is_rest() {
    return defined( 'REST_REQUEST' ) && REST_REQUEST;
  }

  /**
   * Is frontend
   *
   * @return bool
   */
  public static function is_frontend() {
    return ( ! self::is_admin_backend() || ! self::is_ajax() ) && ! self::is_cron() && ! self::is_rest();
  }

  /**
   * Get Client IP address
   *
   * @return   String
  */
  public function get_ip() {
    _deprecated_function( 'get_ip', '4.2.7', 'flavor_like_get_user_ip' );
    // Get user IP
    return flavor_like_get_user_ip();
  }

  /**
   * Load the plugin text domain for translation.
   *
   * @return void
   */
  public function load_plugin_textdomain() {
    // Set filter for language directory
		$lang_dir = FLAVOR_LIKE_SLUG . '/languages';
    $lang_dir = apply_filters( 'flavor_like_languages_directory', $lang_dir );

    // Get locale from WordPress settings instead of hardcoding
    $locale = apply_filters( 'plugin_locale', get_locale(), FLAVOR_LIKE_SLUG );

    // Try to load specific locale file first
    $mo_file = FLAVOR_LIKE_DIR . 'languages/' . FLAVOR_LIKE_SLUG . '-' . $locale . '.mo';

    if ( file_exists( $mo_file ) ) {
      load_textdomain( FLAVOR_LIKE_SLUG, $mo_file );
    }

    // Load plugin textdomain (will use WordPress language packs if available)
    load_plugin_textdomain( FLAVOR_LIKE_SLUG, false, basename( FLAVOR_LIKE_DIR ) . '/languages' );
  }


  /**
  * Return an instance of this class.
  *
  * @since     3.1
  *
  * @return    object    A single instance of this class.
  */
  public static function get_instance() {
    // If the single instance hasn't been set, set it now.
    if ( null == self::$instance ) {
      self::$instance = new self;
    }

    return self::$instance;
  }

}

/**
 * Start Flavor Like service
 *
 * @return void
 */
function RUN_FLAVOR_LIKE(){
  FlavorLikeInit::get_instance();
}
RUN_FLAVOR_LIKE();