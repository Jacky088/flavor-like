<?php
/**
 * PHP Test Bootstrap & WordPress Environment Mock for Flavor Like.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

define( 'COOKIE_DOMAIN', '' );
define( 'COOKIEPATH', '/' );
define( 'COOKIEHASH', 'flavor_like_test_hash' );
define( 'OBJECT', 'OBJECT' );
define( 'OBJECT_K', 'OBJECT_K' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'ARRAY_N', 'ARRAY_N' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'WEEK_IN_SECONDS', 604800 );
define( 'MONTH_IN_SECONDS', 2592000 );
define( 'YEAR_IN_SECONDS', 31536000 );

// Global in-memory storage for test environment
global $wp_actions, $wp_filters, $wp_options, $wp_meta, $wp_transients, $wpdb, $current_user_id, $wp_scripts, $wp_styles, $wp_shortcodes;
$wp_actions    = array();
$wp_filters    = array();
$wp_options    = array();
$wp_meta       = array( 'post' => array(), 'comment' => array(), 'user' => array() );
$wp_transients = array();
$wp_scripts    = array();
$wp_styles     = array();
$wp_shortcodes = array();
$current_user_id = 0;

// WordPress Mock Functions
function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	global $wp_actions;
	$wp_actions[ $tag ][] = array( 'cb' => $callback, 'pri' => $priority, 'args' => $accepted_args );
	return true;
}

function do_action( $tag, ...$args ) {
	global $wp_actions;
	if ( isset( $wp_actions[ $tag ] ) ) {
		foreach ( $wp_actions[ $tag ] as $action ) {
			call_user_func_array( $action['cb'], array_slice( $args, 0, $action['args'] ) );
		}
	}
}

function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	global $wp_filters;
	$wp_filters[ $tag ][] = array( 'cb' => $callback, 'pri' => $priority, 'args' => $accepted_args );
	return true;
}

function apply_filters( $tag, $value, ...$args ) {
	global $wp_filters;
	if ( isset( $wp_filters[ $tag ] ) ) {
		foreach ( $wp_filters[ $tag ] as $filter ) {
			$params = array_merge( array( $value ), array_slice( $args, 0, $filter['args'] - 1 ) );
			$value  = call_user_func_array( $filter['cb'], $params );
		}
	}
	return $value;
}

function remove_filter( $tag, $callback, $priority = 10 ) {
	global $wp_filters;
	if ( isset( $wp_filters[ $tag ] ) ) {
		foreach ( $wp_filters[ $tag ] as $i => $item ) {
			if ( $item['cb'] === $callback && $item['pri'] === $priority ) {
				unset( $wp_filters[ $tag ][ $i ] );
			}
		}
	}
	return true;
}

function has_action( $tag, $function_to_check = false ) {
	global $wp_actions;
	if ( ! isset( $wp_actions[ $tag ] ) ) return false;
	if ( false === $function_to_check ) return ! empty( $wp_actions[ $tag ] );
	foreach ( $wp_actions[ $tag ] as $action ) {
		if ( $action['cb'] === $function_to_check ) return true;
	}
	return false;
}

function has_filter( $tag, $function_to_check = false ) {
	global $wp_filters;
	if ( ! isset( $wp_filters[ $tag ] ) ) return false;
	if ( false === $function_to_check ) return ! empty( $wp_filters[ $tag ] );
	foreach ( $wp_filters[ $tag ] as $filter ) {
		if ( $filter['cb'] === $function_to_check ) return true;
	}
	return false;
}

function get_option( $option, $default = false ) {
	global $wp_options;
	return array_key_exists( $option, $wp_options ) ? $wp_options[ $option ] : $default;
}

function update_option( $option, $value, $autoload = null ) {
	global $wp_options;
	$wp_options[ $option ] = $value;
	return true;
}

function delete_option( $option ) {
	global $wp_options;
	unset( $wp_options[ $option ] );
	return true;
}

function add_option( $option, $value, $deprecated = '', $autoload = 'yes' ) {
	global $wp_options;
	if ( ! array_key_exists( $option, $wp_options ) ) {
		$wp_options[ $option ] = $value;
		return true;
	}
	return false;
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	global $wp_meta;
	if ( ! isset( $wp_meta['post'][ $post_id ] ) ) return $single ? '' : array();
	if ( empty( $key ) ) return $wp_meta['post'][ $post_id ];
	if ( ! isset( $wp_meta['post'][ $post_id ][ $key ] ) ) return $single ? '' : array();
	return $single ? $wp_meta['post'][ $post_id ][ $key ] : array( $wp_meta['post'][ $post_id ][ $key ] );
}

function update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) {
	global $wp_meta;
	$wp_meta['post'][ $post_id ][ $meta_key ] = $meta_value;
	return true;
}

function delete_post_meta( $post_id, $meta_key, $meta_value = '' ) {
	global $wp_meta;
	unset( $wp_meta['post'][ $post_id ][ $meta_key ] );
	return true;
}

function get_comment_meta( $comment_id, $key = '', $single = false ) {
	global $wp_meta;
	if ( ! isset( $wp_meta['comment'][ $comment_id ] ) ) return $single ? '' : array();
	if ( empty( $key ) ) return $wp_meta['comment'][ $comment_id ];
	if ( ! isset( $wp_meta['comment'][ $comment_id ][ $key ] ) ) return $single ? '' : array();
	return $single ? $wp_meta['comment'][ $comment_id ][ $key ] : array( $wp_meta['comment'][ $comment_id ][ $key ] );
}

function update_comment_meta( $comment_id, $meta_key, $meta_value, $prev_value = '' ) {
	global $wp_meta;
	$wp_meta['comment'][ $comment_id ][ $meta_key ] = $meta_value;
	return true;
}

function get_transient( $transient ) {
	global $wp_transients;
	if ( isset( $wp_transients[ $transient ] ) && $wp_transients[ $transient ]['expire'] > time() ) {
		return $wp_transients[ $transient ]['value'];
	}
	return false;
}

function set_transient( $transient, $value, $expiration = 0 ) {
	global $wp_transients;
	$wp_transients[ $transient ] = array(
		'value'  => $value,
		'expire' => $expiration > 0 ? time() + $expiration : time() + 86400,
	);
	return true;
}

function delete_transient( $transient ) {
	global $wp_transients;
	unset( $wp_transients[ $transient ] );
	return true;
}

function is_user_logged_in() {
	global $current_user_id;
	return $current_user_id > 0;
}

function get_current_user_id() {
	global $current_user_id;
	return $current_user_id;
}

function wp_get_current_user() {
	global $current_user_id;
	$user = new stdClass();
	$user->ID = $current_user_id;
	$user->user_login = $current_user_id > 0 ? 'testuser' : '';
	return $user;
}

// i18n & Sanitization
function __( $text, $domain = 'default' ) { return $text; }
function _e( $text, $domain = 'default' ) { echo $text; }
function _n( $single, $plural, $number, $domain = 'default' ) { return $number === 1 ? $single : $plural; }
function _x( $text, $context, $domain = 'default' ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string)$text, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text, $domain = 'default' ) { return esc_html( $text ); }
function esc_attr( $text ) { return htmlspecialchars( (string)$text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr__( $text, $domain = 'default' ) { return esc_attr( $text ); }
function esc_url( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ) ?: ''; }
function esc_sql( $data ) {
	return is_array( $data ) ? array_map( 'esc_sql', $data ) : addslashes( (string)$data );
}
function wp_kses_post( $string ) { return $string; }
function wp_kses( $string, $allowed_html = array() ) { return $string; }
function wp_unslash( $value ) {
	return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string)$value );
}

global $wp_object_cache;
$wp_object_cache = array();

function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;
	$found = isset( $wp_object_cache[ $group ][ $key ] );
	return $found ? $wp_object_cache[ $group ][ $key ] : false;
}

function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	$wp_object_cache[ $group ][ $key ] = $data;
	return true;
}

function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;
	unset( $wp_object_cache[ $group ][ $key ] );
	return true;
}

function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	return wp_cache_set( $key, $data, $group, $expire );
}

function wp_cache_add_global_groups( $groups ) {
	return true;
}

function maybe_serialize( $data ) {
	return is_array( $data ) || is_object( $data ) ? serialize( $data ) : $data;
}

function maybe_unserialize( $data ) {
	return is_string( $data ) && ( 'a:' === substr( $data, 0, 2 ) || 'O:' === substr( $data, 0, 2 ) ) ? @unserialize( $data ) : $data;
}

class WP_Error {
	public $errors = array();
	public $error_data = array();
	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( ! empty( $code ) ) {
			$this->errors[ $code ][] = $message;
			if ( ! empty( $data ) ) {
				$this->error_data[ $code ] = $data;
			}
		}
	}
}

function is_wp_error( $thing ) {
	return ( $thing instanceof WP_Error );
}

function current_time( $type, $gmt = 0 ) {
	return 'mysql' === $type ? ( $gmt ? gmdate( 'Y-m-d H:i:s' ) : date( 'Y-m-d H:i:s' ) ) : ( $gmt ? time() : time() );
}

function sanitize_text_field( $str ) { return trim( strip_tags( (string)$str ) ); }
function sanitize_key( $key ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string)$key ) ); }
function sanitize_textarea_field( $str ) { return trim( strip_tags( (string)$str ) ); }
function absint( $maybeint ) { return abs( intval( $maybeint ) ); }
function wp_parse_args( $args, $defaults = array() ) {
	if ( is_object( $args ) ) $args = get_object_vars( $args );
	return array_merge( $defaults, (array)$args );
}

function wp_create_nonce( $action = -1 ) {
	return substr( md5( $action . 'salt' ), 0, 10 );
}

function wp_verify_nonce( $nonce, $action = -1 ) {
	return $nonce === substr( md5( $action . 'salt' ), 0, 10 );
}

function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) {
	return true;
}

function wp_send_json_success( $data = null, $status_code = null ) {
	echo json_encode( array( 'success' => true, 'data' => $data ) );
}

function wp_send_json_error( $data = null, $status_code = null ) {
	echo json_encode( array( 'success' => false, 'data' => $data ) );
}

function wp_die( $message = '', $title = '', $args = array() ) {
	throw new Exception( "wp_die: " . $message );
}

function add_shortcode( $tag, $callback ) {
	global $wp_shortcodes;
	$wp_shortcodes[ $tag ] = $callback;
}

function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	$atts = (array)$atts;
	$out = array();
	foreach ( $pairs as $name => $default ) {
		if ( array_key_exists( $name, $atts ) ) {
			$out[ $name ] = $atts[ $name ];
		} else {
			$out[ $name ] = $default;
		}
	}
	return $out;
}

function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
	global $wp_scripts;
	$wp_scripts[ $handle ] = array( 'src' => $src, 'deps' => $deps, 'ver' => $ver );
}

function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	global $wp_styles;
	$wp_styles[ $handle ] = array( 'src' => $src, 'deps' => $deps, 'ver' => $ver );
}

function wp_register_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
	return wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
}

function wp_register_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	return wp_enqueue_style( $handle, $src, $deps, $ver, $media );
}

function wp_localize_script( $handle, $object_name, $l10n ) {
	global $wp_scripts;
	if ( isset( $wp_scripts[ $handle ] ) ) {
		$wp_scripts[ $handle ]['l10n'][ $object_name ] = $l10n;
	}
	return true;
}

function is_admin() {
	return false;
}

function is_network_admin() {
	return false;
}

function is_multisite() {
	return false;
}

function wp_doing_ajax() {
	return false;
}

function wp_doing_cron() {
	return false;
}

function did_action( $tag ) {
	global $wp_actions;
	return isset( $wp_actions[ $tag ] ) ? count( $wp_actions[ $tag ] ) : 0;
}

function is_ssl() {
	return false;
}

function get_bloginfo( $show = '' ) {
	if ( 'version' === $show ) return '6.6';
	return 'Flavor Like Test Site';
}

function plugin_dir_path( $file ) {
	return trailingslashit( dirname( $file ) );
}

function plugins_url( $path = '', $file = '' ) {
	return 'https://example.com/wp-content/plugins/flavor-like' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
}

function plugin_basename( $file ) {
	return 'flavor-like/flavor-like.php';
}

function trailingslashit( $string ) {
	return rtrim( $string, '/\\' ) . '/';
}

function untrailingslashit( $string ) {
	return rtrim( $string, '/\\' );
}

function wp_get_upload_dir() {
	$dir = sys_get_temp_dir() . '/flavor_like_uploads';
	if ( ! is_dir( $dir ) ) {
		@mkdir( $dir, 0777, true );
	}
	return array(
		'path'    => $dir,
		'url'     => 'https://example.com/wp-content/uploads',
		'subdir'  => '',
		'basedir' => $dir,
		'baseurl' => 'https://example.com/wp-content/uploads',
		'error'   => false,
	);
}

function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
	return true;
}

function determine_locale() {
	return 'zh_CN';
}

function get_locale() {
	return 'zh_CN';
}

function is_textdomain_loaded( $domain ) {
	return true;
}

function load_textdomain( $domain, $mofile ) {
	return true;
}

function register_block_type( $name, $args = array() ) {
	return true;
}

function wp_add_inline_script( $handle, $data, $position = 'after' ) {
	return true;
}

function wp_script_add_data( $handle, $key, $value ) {
	return true;
}

function wp_style_add_data( $handle, $key, $value ) {
	return true;
}

function register_activation_hook( $file, $callback ) {}
function register_deactivation_hook( $file, $callback ) {}

function wp_upload_dir() {
	return wp_get_upload_dir();
}

// Mock wpdb
class MockWPDB {
	public $prefix = 'wp_';
	public $insert_id = 1;
	public $flavor_like = 'wp_flavor_like';
	public $flavor_like_comments = 'wp_flavor_like_comments';
	public $flavor_like_activities = 'wp_flavor_like_activities';
	public $flavor_like_forums = 'wp_flavor_like_forums';
	public $flavor_like_votes = 'wp_flavor_like_votes';
	public $flavor_like_vote_counts = 'wp_flavor_like_vote_counts';
	public $flavor_like_events = 'wp_flavor_like_events';
	public $posts = 'wp_posts';
	public $comments = 'wp_comments';
	public $users = 'wp_users';
	public $last_query = '';
	public $last_error = '';
	public $rows = array();
	public $queries = array();

	public function has_cap( $cap ) {
		return true;
	}

	public function get_charset_collate() {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
	}

	public function prepare( $query, ...$args ) {
		if ( isset( $args[0] ) && is_array( $args[0] ) ) $args = $args[0];
		foreach ( $args as $arg ) {
			if ( is_numeric( $arg ) ) {
				$query = preg_replace( '/%[df]/', $arg, $query, 1 );
			} else {
				$query = preg_replace( '/%s/', "'" . addslashes( (string)$arg ) . "'", $query, 1 );
			}
		}
		return $query;
	}

	public function query( $query ) {
		$this->last_query = $query;
		$this->queries[]  = $query;
		return 1;
	}

	public function get_var( $query = null, $x = 0, $y = 0 ) {
		$this->last_query = $query;
		$this->queries[]  = $query;
		// Handle MySQL GET_LOCK / RELEASE_LOCK simulation
		if ( strpos( $query, 'SHOW TABLES LIKE' ) !== false ) {
			if ( preg_match( "/'([^']+)'/", $query, $m ) ) {
				return $m[1];
			}
			return 'wp_flavor_like';
		}
		if ( strpos( $query, 'GET_LOCK' ) !== false ) {
			return '1';
		}
		if ( strpos( $query, 'RELEASE_LOCK' ) !== false ) {
			return '1';
		}
		if ( strpos( $query, 'COUNT(' ) !== false ) {
			return '42';
		}
		return '1';
	}

	public function get_row( $query = null, $output = OBJECT, $y = 0 ) {
		$this->last_query = $query;
		$this->queries[]  = $query;
		$row = new stdClass();
		$row->id = 1;
		$row->post_id = 123;
		$row->status = 'like';
		$row->user_id = 1;
		$row->date_time = date('Y-m-d H:i:s');
		return $row;
	}

	public function get_results( $query = null, $output = OBJECT ) {
		$this->last_query = $query;
		$this->queries[]  = $query;
		return array();
	}

	public function get_col( $query = null, $x = 0 ) {
		$this->last_query = $query;
		$this->queries[]  = $query;
		return array();
	}

	public function insert( $table, $data, $format = null ) {
		$this->last_query = "INSERT INTO $table";
		$this->queries[]  = $this->last_query;
		$this->rows[]     = array( 'table' => $table, 'data' => $data );
		return 1;
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		$this->last_query = "UPDATE $table";
		$this->queries[]  = $this->last_query;
		return 1;
	}

	public function delete( $table, $where, $where_format = null ) {
		$this->last_query = "DELETE FROM $table";
		$this->queries[]  = $this->last_query;
		return 1;
	}
}

$wpdb = new MockWPDB();

// Load Flavor Like constants and main definitions
require_once dirname( __DIR__ ) . '/flavor-like.php';

echo "WordPress Mock Environment Initialized Successfully.\n";
