<?php
/**
 * Plugin Name:       Flavor Like
 * Plugin URI:        https://github.com/Jacky088/wp-ulike
 * Description:       为 WordPress 添加一键点赞按钮。内置统计仪表盘、热门内容排行和隐私工具，几分钟即可完成设置。
 * Version:           1.0.3
 * Author:            木木
 * Author URI:        https://github.com/Jacky088
 * Requires PHP:      7.2.5
 * Requires at least: 6.0
 * Text Domain:       wp-ulike
 * Domain Path:       /languages
 * Tested up to:      7.0
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * WP ULike 是自由软件：你可以根据自由软件基金会发布的
 * GNU 通用公共许可证（第 3 版或更高版本）重新分发和/或修改它。
 *
 * WP ULike 的发布是希望它能有用，
 * 但不作任何担保；甚至不作适销性或特定用途适用性的暗示担保。
 * 详情请参阅 GNU 通用公共许可证。
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Do not change these values
define( 'WP_ULIKE_PLUGIN_URI'   , 'https://github.com/Jacky088/wp-ulike' 	);
define( 'WP_ULIKE_VERSION'      , '1.0.3' 					 		    	);
define( 'WP_ULIKE_DB_VERSION'   , '2.5' 					 	 			);
define( 'WP_ULIKE_SLUG'         , 'wp-ulike' 					 			);
define( 'WP_ULIKE_NAME'         , 'Flavor Like'	    						);

define( 'WP_ULIKE_DIR'          , plugin_dir_path( __FILE__ ) 	 			);
define( 'WP_ULIKE_URL'          , plugins_url( '', __FILE__ ) 	 			);
define( 'WP_ULIKE_BASENAME'     , plugin_basename( __FILE__ ) 	 			);

define( 'WP_ULIKE_ADMIN_DIR'    , WP_ULIKE_DIR . 'admin' 		 			);
define( 'WP_ULIKE_ADMIN_URL'    , WP_ULIKE_URL . '/admin' 		 			);

define( 'WP_ULIKE_INC_DIR'      , WP_ULIKE_DIR . 'includes' 	 			);
define( 'WP_ULIKE_INC_URL'      , WP_ULIKE_URL . '/includes'     			);

define( 'WP_ULIKE_ASSETS_DIR'   , WP_ULIKE_DIR . 'assets' 					);
define( 'WP_ULIKE_ASSETS_URL'   , WP_ULIKE_URL . '/assets' 		 			);

/**
 * Initialize the plugin
 * ===========================================================================*/

/**
 * 禁止 WordPress.org 更新检测，防止被官方版本覆盖
 */
add_filter( 'site_transient_update_plugins', 'wp_ulike_disable_updates' );
add_filter( 'pre_set_site_transient_update_plugins', 'wp_ulike_disable_updates' );
function wp_ulike_disable_updates( $transient ) {
	if ( isset( $transient->response ) ) {
		$basename = plugin_basename( __FILE__ );
		if ( isset( $transient->response[ $basename ] ) ) {
			unset( $transient->response[ $basename ] );
		}
	}
	return $transient;
}

/**
 * 修改"查看详情"链接指向 GitHub 仓库
 */
add_filter( 'plugins_api', 'wp_ulike_custom_plugin_info', 20, 3 );
function wp_ulike_custom_plugin_info( $result, $action, $args ) {
	if ( 'plugin_information' !== $action ) {
		return $result;
	}
	if ( ! isset( $args->slug ) || 'flavor-like' !== $args->slug ) {
		return $result;
	}
	// 返回自定义信息，阻止从 WordPress.org 获取
	$plugin_info = new stdClass();
	$plugin_info->name          = 'Flavor Like';
	$plugin_info->slug          = 'flavor-like';
	$plugin_info->version       = WP_ULIKE_VERSION;
	$plugin_info->author        = '<a href="https://github.com/Jacky088">木木</a>';
	$plugin_info->homepage      = 'https://github.com/Jacky088/wp-ulike';
	$plugin_info->requires      = '6.0';
	$plugin_info->tested        = '7.0';
	$plugin_info->requires_php  = '7.2.5';
	$plugin_info->sections      = array(
		'description' => '为 WordPress 添加一键点赞按钮。内置统计仪表盘、热门内容排行和隐私工具，几分钟即可完成设置。',
		'changelog'   => '<h4>1.0.0</h4><p>初始版本 - 基于 WP ULike 修改，全面汉化，移除 Pro 功能。</p>',
	);
	return $plugin_info;
}

require WP_ULIKE_INC_DIR . '/action.php';
// Register hooks that are fired when the plugin is activated or deactivated.
register_activation_hook  ( __FILE__, array( 'wp_ulike_register_action_hook', 'activate'   ) );
register_deactivation_hook( __FILE__, array( 'wp_ulike_register_action_hook', 'deactivate' ) );

if ( ! version_compare( PHP_VERSION, '7.2.5', '>=' ) ) {
	add_action( 'admin_notices', 'wp_ulike_fail_php_version' );
} elseif ( ! version_compare( get_bloginfo( 'version' ), '6.0', '>=' ) ) {
	add_action( 'admin_notices', 'wp_ulike_fail_wp_version' );
} else {
	if( ! class_exists( 'WpUlikeInit' ) ) {
		require WP_ULIKE_INC_DIR . '/plugin.php';
	}
}

/**
 * WP ULike 最低 PHP 版本提示。
 *
 * @return void
 */
function wp_ulike_fail_php_version() {
	/* translators: %s: PHP version */
	$message = sprintf( esc_html__( 'WP ULike 需要 PHP %s+ 版本，插件当前未运行。', 'wp-ulike' ), '7.2.5' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

/**
 * WP ULike 最低 WordPress 版本提示。
 *
 * @return void
 */
function wp_ulike_fail_wp_version() {
	/* translators: %s: WordPress version */
	$message = sprintf( esc_html__( 'WP ULike 需要 WordPress %s+ 版本。由于您使用的是较早版本，插件当前未运行。', 'wp-ulike' ), '6.0' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

/*============================================================================*/