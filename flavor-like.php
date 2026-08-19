<?php
/**
 * Plugin Name:       Flavor Like
 * Plugin URI:        https://github.com/Jacky088/flavor-like
 * Description:       为 WordPress 添加一键点赞按钮。内置统计仪表盘、热门内容排行和隐私工具，几分钟即可完成设置。
 * Version:           1.0.6
 * Author:            木木
 * Author URI:        https://github.com/Jacky088
 * Requires PHP:      7.2.5
 * Requires at least: 6.0
 * Text Domain:       flavor-like
 * Domain Path:       /languages
 * Tested up to:      7.0
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Flavor Like 是自由软件：你可以根据自由软件基金会发布的
 * GNU 通用公共许可证（第 3 版或更高版本）重新分发和/或修改它。
 *
 * Flavor Like 的发布是希望它能有用，
 * 但不作任何担保；甚至不作适销性或特定用途适用性的暗示担保。
 * 详情请参阅 GNU 通用公共许可证。
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Do not change these values
define( 'FLAVOR_LIKE_PLUGIN_URI'   , 'https://github.com/Jacky088/flavor-like' 	);
define( 'FLAVOR_LIKE_VERSION'      , '1.0.6' 					 		    	);
define( 'FLAVOR_LIKE_DB_VERSION'   , '2.5' 					 	 			);
define( 'FLAVOR_LIKE_SLUG'         , 'flavor-like' 					 			);
define( 'FLAVOR_LIKE_NAME'         , 'Flavor Like'	    						);

define( 'FLAVOR_LIKE_DIR'          , plugin_dir_path( __FILE__ ) 	 			);
define( 'FLAVOR_LIKE_URL'          , plugins_url( '', __FILE__ ) 	 			);
define( 'FLAVOR_LIKE_BASENAME'     , plugin_basename( __FILE__ ) 	 			);

define( 'FLAVOR_LIKE_ADMIN_DIR'    , FLAVOR_LIKE_DIR . 'admin' 		 			);
define( 'FLAVOR_LIKE_ADMIN_URL'    , FLAVOR_LIKE_URL . '/admin' 		 			);

define( 'FLAVOR_LIKE_INC_DIR'      , FLAVOR_LIKE_DIR . 'includes' 	 			);
define( 'FLAVOR_LIKE_INC_URL'      , FLAVOR_LIKE_URL . '/includes'     			);

define( 'FLAVOR_LIKE_ASSETS_DIR'   , FLAVOR_LIKE_DIR . 'assets' 					);
define( 'FLAVOR_LIKE_ASSETS_URL'   , FLAVOR_LIKE_URL . '/assets' 		 			);

/**
 * Initialize the plugin
 * ===========================================================================*/

/**
 * 禁止 WordPress.org 更新检测，防止被官方版本覆盖
 */
add_filter( 'site_transient_update_plugins', 'flavor_like_disable_updates' );
add_filter( 'pre_set_site_transient_update_plugins', 'flavor_like_disable_updates' );
function flavor_like_disable_updates( $transient ) {
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
add_filter( 'plugins_api', 'flavor_like_custom_plugin_info', 20, 3 );
function flavor_like_custom_plugin_info( $result, $action, $args ) {
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
	$plugin_info->version       = FLAVOR_LIKE_VERSION;
	$plugin_info->author        = '<a href="https://github.com/Jacky088">木木</a>';
	$plugin_info->homepage      = 'https://github.com/Jacky088/flavor-like';
	$plugin_info->requires      = '6.0';
	$plugin_info->tested        = '7.0';
	$plugin_info->requires_php  = '7.2.5';
	$plugin_info->sections      = array(
		'description' => '为 WordPress 添加一键点赞按钮。内置统计仪表盘、热门内容排行和隐私工具，几分钟即可完成设置。',
		'changelog'   => '<h4>1.0.0</h4><p>初始版本 - 基于 Flavor Like 修改，全面汉化，移除 Pro 功能。</p>',
	);
	return $plugin_info;
}

require FLAVOR_LIKE_INC_DIR . '/action.php';
// Register hooks that are fired when the plugin is activated or deactivated.
register_activation_hook  ( __FILE__, array( 'flavor_like_register_action_hook', 'activate'   ) );
register_deactivation_hook( __FILE__, array( 'flavor_like_register_action_hook', 'deactivate' ) );

if ( ! version_compare( PHP_VERSION, '7.2.5', '>=' ) ) {
	add_action( 'admin_notices', 'flavor_like_fail_php_version' );
} elseif ( ! version_compare( get_bloginfo( 'version' ), '6.0', '>=' ) ) {
	add_action( 'admin_notices', 'flavor_like_fail_wp_version' );
} else {
	if( ! class_exists( 'FlavorLikeInit' ) ) {
		require FLAVOR_LIKE_INC_DIR . '/plugin.php';
	}
}

/**
 * Flavor Like 最低 PHP 版本提示。
 *
 * @return void
 */
function flavor_like_fail_php_version() {
	/* translators: %s: PHP version */
	$message = sprintf( esc_html__( 'Flavor Like 需要 PHP %s+ 版本，插件当前未运行。', 'flavor-like' ), '7.2.5' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

/**
 * Flavor Like 最低 WordPress 版本提示。
 *
 * @return void
 */
function flavor_like_fail_wp_version() {
	/* translators: %s: WordPress version */
	$message = sprintf( esc_html__( 'Flavor Like 需要 WordPress %s+ 版本。由于您使用的是较早版本，插件当前未运行。', 'flavor-like' ), '6.0' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

/*============================================================================*/