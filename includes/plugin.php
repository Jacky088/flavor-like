<?php
/**
 * WP ULIKE BASE CLASS
 *
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WpUlikeInit {

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
    wp_ulike_maybe_backfill_first_activated_at();

    // Schema checks/upgrades only need to run where they can be observed and
    // acted on (wp-admin, WP-Cron); skip them on plain, unauthenticated
    // frontend requests to avoid extra queries and DDL on every pageview.
    if ( self::is_admin_backend() || self::is_cron() ) {
      $this->maybe_upgrade_database();
    }
  }

  private function maybe_upgrade_database(){
    $stored = get_option( 'wp_ulike_dbVersion', false );

    // Fresh installs set wp_ulike_dbVersion during activation.
    if ( false === $stored ) {
      return;
    }

    $target = WP_ULIKE_DB_VERSION;

    if ( version_compare( $stored, '2.4', '<' ) ) {
      if ( false === WP_Ulike_Legacy_Upgrade::run() ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
          error_log( sprintf( 'WP ULike: Legacy database upgrade failed. Current version: %s', $stored ) );
        }
        return;
      }

      $stored = '2.4';
      update_option( 'wp_ulike_dbVersion', $stored );
    }

    if ( version_compare( $stored, $target, '<' ) ) {
      $activator = wp_ulike_activator::get_instance();

      if ( false === $activator->install_tables( false, false ) ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
          error_log( sprintf( 'WP ULike: Storage upgrade to %s failed. Current version: %s', $target, $stored ) );
        }
        return;
      }

      update_option( 'wp_ulike_dbVersion', $target );
    }

    if ( ! WP_Ulike_Meta_Schema::table_exists() || ! WP_Ulike_Pulse_Schema::table_exists() ) {
      wp_ulike_activator::get_instance()->install_tables( false, false );
    }
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
    do_action( 'wp_ulike_loaded' );
  }

  /**
   * Define constants
   *
   * @return void
   */
  private function define_constants(){
    // a custom directory in uploads directory for storing custom files. Default uploads/{WP_ULIKE_SLUG}
    $uploads = wp_get_upload_dir();
    define( 'WP_ULIKE_CUSTOM_DIR' , $uploads['basedir'] . '/' . WP_ULIKE_SLUG );
    define( 'WP_ULIKE_CUSTOM_URL' , $uploads['baseurl'] . '/' . WP_ULIKE_SLUG );
  }

  /**
   * Add admin links
   *
   * @param array $actions
   * @param string $plugin_file
   * @return array
   */
  public function add_links( $actions, $plugin_file ) {

    if (  $plugin_file === WP_ULIKE_BASENAME ) {
      $settings = array('settings'  => '<a href="admin.php?page=wp-ulike-settings">' . esc_html__('Settings', 'wp-ulike') . '</a>');
      $stats    = array('stats'     => '<a href="admin.php?page=wp-ulike-statistics">' . esc_html__('Statistics', 'wp-ulike') . '</a>');
      $about    = array('overview'  => '<a href="admin.php?page=wp-ulike-about">' . esc_html__( 'Overview', 'wp-ulike' ) . '</a>');
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
    if ( WP_ULIKE_BASENAME !== $plugin_file ) {
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
        WP_ULIKE_INC_DIR   . '/classes/',
        WP_ULIKE_ADMIN_DIR . '/classes/'
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
    include_once( WP_ULIKE_INC_DIR . '/index.php' );

    // Dashboard and Administrative Functionality
    if ( self::is_admin_backend() ) {
      // Load admin specific codes
      include( WP_ULIKE_ADMIN_DIR . '/index.php' );

      // Load AJAX specific codes on demand
      if ( self::is_ajax() ){
        include( WP_ULIKE_INC_DIR . '/hooks/frontend-ajax.php' );
        include( WP_ULIKE_ADMIN_DIR . '/admin-ajax.php'  );
      }
    }

    // Load Frontend Functionality
    if( self::is_frontend() ){
      include( WP_ULIKE_INC_DIR . '/public/index.php' );
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
    _deprecated_function( 'get_ip', '4.2.7', 'wp_ulike_get_user_ip' );
    // Get user IP
    return wp_ulike_get_user_ip();
  }

  /**
   * Load the plugin text domain for translation.
   *
   * @return void
   */
  public function load_plugin_textdomain() {
    // Set filter for language directory
		$lang_dir = WP_ULIKE_SLUG . '/languages';
    $lang_dir = apply_filters( 'wp_ulike_languages_directory', $lang_dir );

		$locale   = 'zh_CN';
    /**
     * Filter to adjust the wp ulike locale to use for translations.
     */
    $locale = apply_filters( 'plugin_locale', $locale, WP_ULIKE_SLUG );

    // Auto-generate .mo file from .po if it doesn't exist
    $mo_path = WP_ULIKE_DIR . 'languages/' . WP_ULIKE_SLUG . '-' . $locale . '.mo';
    $po_path = WP_ULIKE_DIR . 'languages/' . WP_ULIKE_SLUG . '-' . $locale . '.po';
    if ( ! file_exists( $mo_path ) && file_exists( $po_path ) ) {
      $this->generate_mo_from_po( $po_path, $mo_path );
    }

    load_textdomain( WP_ULIKE_SLUG, WP_ULIKE_DIR . 'languages/' . WP_ULIKE_SLUG . '-' . $locale . '.mo' );
    load_plugin_textdomain( WP_ULIKE_SLUG, false, basename( WP_ULIKE_DIR ) . '/languages' );
  }

  /**
   * Generate .mo file from .po file
   *
   * @param string $po_path
   * @param string $mo_path
   * @return bool
   */
  private function generate_mo_from_po( $po_path, $mo_path ) {
    $poContent = file_get_contents( $po_path );
    if ( ! $poContent ) return false;

    $entries = array();
    $currentMsgid = '';
    $currentMsgstr = '';
    $inMsgid = false;
    $inMsgstr = false;

    $lines = explode( "\n", $poContent );
    foreach ( $lines as $line ) {
      $line = trim( $line );
      if ( $line === '' || ( isset( $line[0] ) && $line[0] === '#' ) ) {
        if ( $inMsgstr && $currentMsgid !== '' ) {
          $entries[ $currentMsgid ] = $currentMsgstr;
        }
        $inMsgid = false;
        $inMsgstr = false;
        $currentMsgid = '';
        $currentMsgstr = '';
        continue;
      }
      if ( strpos( $line, 'msgid "' ) === 0 ) {
        if ( $inMsgstr && $currentMsgid !== '' ) {
          $entries[ $currentMsgid ] = $currentMsgstr;
        }
        $inMsgid = true;
        $inMsgstr = false;
        $currentMsgid = stripcslashes( substr( $line, 7, -1 ) );
      } elseif ( strpos( $line, 'msgstr "' ) === 0 ) {
        $inMsgid = false;
        $inMsgstr = true;
        $currentMsgstr = stripcslashes( substr( $line, 8, -1 ) );
      } elseif ( strpos( $line, 'msgid_plural "' ) === 0 ) {
        // skip
      } elseif ( preg_match( '/^msgstr\[0\] "(.*)"$/', $line, $m ) ) {
        $inMsgid = false;
        $inMsgstr = true;
        $currentMsgstr = stripcslashes( $m[1] );
      } elseif ( preg_match( '/^msgstr\[\d+\]/', $line ) ) {
        // skip
      } elseif ( isset( $line[0] ) && $line[0] === '"' && $line[ strlen( $line ) - 1 ] === '"' ) {
        $content = stripcslashes( substr( $line, 1, -1 ) );
        if ( $inMsgid ) $currentMsgid .= $content;
        elseif ( $inMsgstr ) $currentMsgstr .= $content;
      }
    }
    if ( $inMsgstr && $currentMsgid !== '' ) {
      $entries[ $currentMsgid ] = $currentMsgstr;
    }

    // Separate header and filter empty
    $header = isset( $entries[''] ) ? $entries[''] : '';
    unset( $entries[''] );
    $filtered = array();
    foreach ( $entries as $k => $v ) {
      if ( $v !== '' ) $filtered[ $k ] = $v;
    }
    ksort( $filtered );
    $allEntries = array_merge( array( '' => $header ), $filtered );

    $count = count( $allEntries );
    $header_size = 28;
    $orig_strings = array_keys( $allEntries );
    $trans_strings = array_values( $allEntries );

    $o_table_offset = $header_size;
    $t_table_offset = $o_table_offset + $count * 8;
    $data_offset = $t_table_offset + $count * 8;

    $current_offset = $data_offset;
    $o_offsets = array();
    foreach ( $orig_strings as $s ) {
      $o_offsets[] = array( strlen( $s ), $current_offset );
      $current_offset += strlen( $s ) + 1;
    }
    $t_offsets = array();
    foreach ( $trans_strings as $s ) {
      $t_offsets[] = array( strlen( $s ), $current_offset );
      $current_offset += strlen( $s ) + 1;
    }

    $mo = pack( 'V', 0x950412de ) . pack( 'V', 0 ) . pack( 'V', $count );
    $mo .= pack( 'V', $o_table_offset ) . pack( 'V', $t_table_offset );
    $mo .= pack( 'V', 0 ) . pack( 'V', 0 );

    foreach ( $o_offsets as $o ) { $mo .= pack( 'V', $o[0] ) . pack( 'V', $o[1] ); }
    foreach ( $t_offsets as $t ) { $mo .= pack( 'V', $t[0] ) . pack( 'V', $t[1] ); }
    foreach ( $orig_strings as $s ) { $mo .= $s . "\0"; }
    foreach ( $trans_strings as $s ) { $mo .= $s . "\0"; }

    return (bool) @file_put_contents( $mo_path, $mo );
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
 * Start WP ULike service
 *
 * @return void
 */
function RUN_WPULIKE(){
  WpUlikeInit::get_instance();
}
RUN_WPULIKE();