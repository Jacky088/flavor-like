<?php
/**
 * Blocks Registration
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/**
 * Register custom block category for Flavor Like blocks
 */
function flavor_like_block_category( $categories, $editor_context ) {
	$custom_category = array(
		'slug'  => 'flavor-like',
		'title' => esc_html__( 'Flavor Like', 'flavor-like' )
	);

	array_unshift( $categories, $custom_category );
	return $categories;
}
add_filter( 'block_categories_all', 'flavor_like_block_category', 10, 2 );

/**
 * Get all registered Flavor Like block names
 * Cached to avoid repeated file system access
 */
function flavor_like_get_block_names() {
	static $block_names = null;

	if ( $block_names !== null ) {
		return $block_names;
	}

	$block_names = array();
	$blocks_dir = FLAVOR_LIKE_INC_DIR . '/blocks';

	if ( ! is_dir( $blocks_dir ) ) {
		return $block_names;
	}

	$block_dirs = glob( $blocks_dir . '/*', GLOB_ONLYDIR );

	foreach ( $block_dirs as $block_dir ) {
		$block_json = $block_dir . '/block.json';
		if ( file_exists( $block_json ) ) {
			$block_data = json_decode( file_get_contents( $block_json ), true );
			if ( isset( $block_data['name'] ) ) {
				$block_names[] = $block_data['name'];
			}
		}
	}

	return $block_names;
}

/**
 * Register Flavor Like Gutenberg Blocks
 * Automatically registers all blocks found in the blocks directory
 */
function flavor_like_register_blocks() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	$blocks_dir = FLAVOR_LIKE_INC_DIR . '/blocks';
	if ( ! is_dir( $blocks_dir ) ) {
		return;
	}

	$block_dirs = glob( $blocks_dir . '/*', GLOB_ONLYDIR );

	foreach ( $block_dirs as $block_dir ) {
		$block_json = $block_dir . '/block.json';
		if ( ! file_exists( $block_json ) ) {
			continue;
		}

		$block_data = json_decode( file_get_contents( $block_json ), true );
		$block_name = isset( $block_data['name'] ) ? $block_data['name'] : '';

		$block_args = array();
		$render_file = $block_dir . '/render.php';

		if ( file_exists( $render_file ) ) {
		$block_args['render_callback'] = function( $attributes, $content, $block = null ) use ( $render_file, $block_name ) {
			return flavor_like_block_render_callback( $attributes, $content, $block, $render_file, $block_name );
		};
		}

		register_block_type( $block_dir, $block_args );
	}
}
add_action( 'init', 'flavor_like_register_blocks' );

/**
 * Enqueue Flavor Like frontend styles
 * Only enqueues if not already enqueued by class-flavor-like-frontend-assets
 */
function flavor_like_block_enqueue_frontend_styles() {
	// Check if already enqueued by main class
	if ( wp_style_is( FLAVOR_LIKE_SLUG, 'enqueued' ) || wp_style_is( FLAVOR_LIKE_SLUG, 'registered' ) ) {
		return;
	}

	// Use existing class method to load styles
	if ( class_exists( 'flavor_like_frontend_assets' ) ) {
		$assets = new flavor_like_frontend_assets();
		$assets->load_styles();
	}

	// Enqueue Pro version CSS if Pro exists
	if ( defined( 'FLAVOR_LIKE_PRO_VERSION' ) && defined( 'FLAVOR_LIKE_PRO_PUBLIC_URL' ) && defined( 'FLAVOR_LIKE_PRO_DOMAIN' ) ) {
		if ( ! wp_style_is( FLAVOR_LIKE_PRO_DOMAIN, 'enqueued' ) && ! wp_style_is( FLAVOR_LIKE_PRO_DOMAIN, 'registered' ) ) {
			wp_enqueue_style( FLAVOR_LIKE_PRO_DOMAIN, FLAVOR_LIKE_PRO_PUBLIC_URL . '/assets/css/flavor-like-pro.min.css', array( FLAVOR_LIKE_SLUG ), FLAVOR_LIKE_PRO_VERSION );
		}
	}
}

/**
 * Get initialization script for iframe
 */
function flavor_like_get_iframe_init_script() {
	return '
		(function() {
			if (typeof FlavorLike === "undefined") return;

			function initFlavorLike(elements) {
				if (!elements || !elements.length) return;
				Array.prototype.forEach.call(elements, function(el) {
					if (el && !el.hasAttribute("data-flavor-like-initialized")) {
						try {
							new FlavorLike(el);
							el.setAttribute("data-flavor-like-initialized", "true");
						} catch(e) {}
					}
				});
			}

			function setupObserver() {
				if (!document.body) {
					setTimeout(setupObserver, 50);
					return;
				}

				var observer = new MutationObserver(function(mutations) {
					var found = false;
					mutations.forEach(function(mutation) {
						if (mutation.addedNodes.length) {
							mutation.addedNodes.forEach(function(node) {
								if (node.nodeType === 1) {
									if (node.classList && node.classList.contains("flavorlike")) {
										found = true;
									} else if (node.querySelector && node.querySelector(".flavorlike")) {
										found = true;
									}
								}
							});
						}
					});
					if (found) {
						var elements = document.querySelectorAll(".flavorlike:not([data-flavor-like-initialized])");
						initFlavorLike(elements);
					}
				});

				observer.observe(document.body, { childList: true, subtree: true });

				var existing = document.querySelectorAll(".flavorlike:not([data-flavor-like-initialized])");
				initFlavorLike(existing);
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", setupObserver);
			} else {
				setupObserver();
			}
		})();
	';
}

/**
 * Enqueue Flavor Like frontend JavaScript
 * Only enqueues if not already enqueued by class-flavor-like-frontend-assets
 */
function flavor_like_block_enqueue_frontend_scripts() {
	// Handle Pro version first (Pro >= 1.5.3 includes free scripts, so use Pro instead)
	if ( defined( 'FLAVOR_LIKE_PRO_VERSION' ) && defined( 'FLAVOR_LIKE_PRO_PUBLIC_URL' ) && defined( 'FLAVOR_LIKE_PRO_DOMAIN' ) ) {
		if ( version_compare( FLAVOR_LIKE_PRO_VERSION, '1.5.3', '>=' ) ) {
			// Check if Pro script already enqueued
			if ( wp_script_is( FLAVOR_LIKE_PRO_DOMAIN, 'enqueued' ) || wp_script_is( FLAVOR_LIKE_PRO_DOMAIN, 'registered' ) ) {
				// Still add initialization script even if already enqueued
				wp_add_inline_script( FLAVOR_LIKE_PRO_DOMAIN, flavor_like_get_iframe_init_script(), 'after' );
				return;
			}

			// Use minified version (Pro uses DEV comments, but we'll use minified for consistency)
			wp_enqueue_script( FLAVOR_LIKE_PRO_DOMAIN, FLAVOR_LIKE_PRO_PUBLIC_URL . '/assets/js/flavor-like-pro.min.js', array(), FLAVOR_LIKE_PRO_VERSION, true );

			// Match Pro's exact localization logic
			if ( function_exists( 'flavor_like_get_option' ) && class_exists( 'Flavor_Like_Pro' ) ) {
				flavor_like_add_inline_script_data(
					FLAVOR_LIKE_PRO_DOMAIN,
					'FlavorLikeProCommonConfig',
					array(
						'AjaxUrl' => admin_url( 'admin-ajax.php' ),
						'Nonce'   => wp_create_nonce( FLAVOR_LIKE_PRO_DOMAIN ),
						'ViewTracking' => array(
							'enabledTypes' => false,
						),
					)
				);
			}

			// Add initialization script for Pro
			wp_add_inline_script( FLAVOR_LIKE_PRO_DOMAIN, flavor_like_get_iframe_init_script(), 'after' );
			return;
		}
	}

	// Check if already enqueued by main class
	if ( wp_script_is( 'flavor_like', 'enqueued' ) || wp_script_is( 'flavor_like', 'registered' ) ) {
		// Still add initialization script even if already enqueued
		wp_add_inline_script( 'flavor_like', flavor_like_get_iframe_init_script(), 'after' );
		return;
	}

	// Use existing class method to load scripts
	if ( class_exists( 'flavor_like_frontend_assets' ) ) {
		$assets = new flavor_like_frontend_assets();
		$assets->load_scripts();
	}

	// Add initialization script
	wp_add_inline_script( 'flavor_like', flavor_like_get_iframe_init_script(), 'after' );
}

/**
 * Enqueue block assets for editor iframe (WordPress 6.3+)
 * Only loads if not already loaded by class-flavor-like-frontend-assets
 */
function flavor_like_block_assets() {
	// Only load in editor iframe, not frontend (frontend handled by class-flavor-like-frontend-assets)
	$is_editor = is_admin() || FlavorLikeInit::is_rest();

	if ( $is_editor ) {
		flavor_like_block_enqueue_frontend_styles();
		flavor_like_block_enqueue_frontend_scripts();
	}
}
add_action( 'enqueue_block_assets', 'flavor_like_block_assets' );

/**
 * Enqueue block editor assets (Top List dynamic config only).
 * Scripts, styles, and translations are registered via block.json (textdomain + wp-i18n).
 */
function flavor_like_block_editor_assets() {
	$blocks_dir = FLAVOR_LIKE_INC_DIR . '/blocks';
	if ( ! is_dir( $blocks_dir ) ) {
		return;
	}

	$block_dirs = glob( $blocks_dir . '/*', GLOB_ONLYDIR );

	foreach ( $block_dirs as $block_dir ) {
		$block_json = $block_dir . '/block.json';
		if ( ! file_exists( $block_json ) ) {
			continue;
		}

		$asset_file = $block_dir . '/build/index.asset.php';
		$js_file    = $block_dir . '/build/index.js';

		if ( ! file_exists( $asset_file ) || ! file_exists( $js_file ) ) {
			continue;
		}

		$asset      = require $asset_file;
		$block_data = json_decode( file_get_contents( $block_json ), true );
		$block_name = isset( $block_data['name'] ) ? $block_data['name'] : '';
		$block_slug = sanitize_key( str_replace( array( 'flavor-like/', '/' ), array( '', '-' ), $block_name ) );
		$script_handle = 'flavor-like-block-' . $block_slug . '-editor';
		$block_url = FLAVOR_LIKE_INC_URL . '/blocks/' . basename( $block_dir );

		$block_script_handle = function_exists( 'generate_block_asset_handle' )
			? generate_block_asset_handle( $block_name, 'editorScript' )
			: $script_handle;

		$editor_style_handle = function_exists( 'generate_block_asset_handle' )
			? generate_block_asset_handle( $block_name, 'editorStyle' )
			: $script_handle;

		if ( ! wp_script_is( $block_script_handle, 'registered' ) ) {
			wp_enqueue_script(
				$script_handle,
				$block_url . '/build/index.js',
				isset( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
				! empty( $asset['version'] ) ? $asset['version'] : FLAVOR_LIKE_VERSION,
				true
			);
			$block_script_handle = $script_handle;
		}

		if ( 'flavor-like/top-content' === $block_name ) {
			if ( ! class_exists( 'Flavor_Like_Top_Content_Renderer' ) ) {
				require_once $block_dir . '/class-top-content-renderer.php';
			}

			flavor_like_add_inline_script_data(
				$block_script_handle,
				'flavorLikeTopContentBlock',
				Flavor_Like_Top_Content_Renderer::get_editor_config()
			);
		}

		$editor_css = $block_dir . '/build/index.css';
		if ( file_exists( $editor_css ) && ! wp_style_is( $editor_style_handle, 'registered' ) ) {
			wp_enqueue_style(
				$script_handle,
				$block_url . '/build/index.css',
				array(),
				! empty( $asset['version'] ) ? $asset['version'] : FLAVOR_LIKE_VERSION
			);
		}
	}
}
add_action( 'enqueue_block_editor_assets', 'flavor_like_block_editor_assets', 20 );

/**
 * Enqueue frontend assets when block is used (fallback if main class doesn't load)
 */
function flavor_like_block_frontend_assets() {
	if ( wp_script_is( 'flavor_like', 'enqueued' ) || ( defined( 'FLAVOR_LIKE_PRO_DOMAIN' ) && wp_script_is( FLAVOR_LIKE_PRO_DOMAIN, 'enqueued' ) ) ) {
		return;
	}

	// Top List is server-rendered with block styles only; skip global vote JS/CSS.
	if ( ! has_block( 'flavor-like/button' ) ) {
		return;
	}

	flavor_like_block_enqueue_frontend_styles();
	flavor_like_block_enqueue_frontend_scripts();
}
add_action( 'wp_enqueue_scripts', 'flavor_like_block_frontend_assets' );

/**
 * Block render callback
 */
function flavor_like_block_render_callback( $attributes, $content = '', $block = null, $render_file = '', $block_name = '' ) {
	$wrapper_class = '';

	if ( $block instanceof WP_Block ) {
		$block_name = $block->name;
		if ( ! empty( $block->parsed_block['attrs']['className'] ) ) {
			$wrapper_class = $block->parsed_block['attrs']['className'];
		}
	}

	if ( empty( $wrapper_class ) && isset( $attributes['className'] ) && ! empty( $attributes['className'] ) ) {
		$wrapper_class = $attributes['className'];
	}

	if ( empty( $render_file ) && ! empty( $block_name ) ) {
		$block_slug = str_replace( 'flavor-like/', '', $block_name );
		$render_file = FLAVOR_LIKE_INC_DIR . '/blocks/' . $block_slug . '/render.php';
	}

	if ( empty( $render_file ) || ! file_exists( $render_file ) ) {
		$render_file = FLAVOR_LIKE_INC_DIR . '/blocks/button/render.php';
	}

	if ( ! file_exists( $render_file ) ) {
		return '';
	}

	if ( 'flavor-like/top-content' === $block_name ) {
		$render_context = array(
			'attributes'   => is_array( $attributes ) ? $attributes : array(),
			'wrapperClass' => $wrapper_class,
			'block'        => $block,
		);
	} else {
		$render_context = array(
			'for'              => isset( $attributes['for'] ) ? $attributes['for'] : 'post',
			'itemId'           => isset( $attributes['itemId'] ) ? $attributes['itemId'] : '',
			'useCurrentPostId' => isset( $attributes['useCurrentPostId'] ) ? $attributes['useCurrentPostId'] : true,
			'template'         => isset( $attributes['template'] ) ? $attributes['template'] : '',
			'buttonType'       => isset( $attributes['buttonType'] ) ? $attributes['buttonType'] : '',
			'wrapperClass'     => $wrapper_class,
			'block'            => $block,
		);
	}

	ob_start();
	$context = $render_context;
	include $render_file;
	return ob_get_clean();
}

/**
 * Register REST API endpoint for templates
 */
function flavor_like_register_rest_routes() {
	register_rest_route( 'flavor-like/v1', '/templates', array(
		'methods'             => 'GET',
		'callback'            => 'flavor_like_get_templates_for_block',
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
	) );
}
add_action( 'rest_api_init', 'flavor_like_register_rest_routes' );

/**
 * Get templates list for block editor
 *
 * @return array
 */
function flavor_like_get_templates_for_block() {
	if ( ! function_exists( 'flavor_like_generate_templates_list' ) ) {
		return array();
	}

	$templates = flavor_like_generate_templates_list();
	$output = array();

	// Get default template from settings (for posts by default)
	$default_template_key = 'flavorlike-default';
	if ( function_exists( 'flavor_like_get_option' ) ) {
		$saved_template = flavor_like_get_option( 'posts_group|template', 'flavorlike-default' );
		if ( ! empty( $saved_template ) && isset( $templates[ $saved_template ] ) ) {
			$default_template_key = $saved_template;
		}
	}

	// Find the default template name
	$default_template_name = __( 'Use Settings Default', 'flavor-like' );
	if ( isset( $templates[ $default_template_key ] ) && isset( $templates[ $default_template_key ]['name'] ) ) {
		$default_template_name = sprintf( __( 'Use Settings Default (%s)', 'flavor-like' ), $templates[ $default_template_key ]['name'] );
	}

	if ( ! empty( $templates ) ) {
		foreach ( $templates as $key => $args ) {
			$output[] = array(
				'key'             => $key,
				'name'            => isset( $args['name'] ) ? $args['name'] : ucfirst( str_replace( array( 'flavorlike-', 'flavor-like-' ), '', $key ) ),
				'symbol'          => isset( $args['symbol'] ) ? $args['symbol'] : '',
				'is_text_support' => isset( $args['is_text_support'] ) ? $args['is_text_support'] : false,
				'is_locked'       => isset( $args['is_locked'] ) ? $args['is_locked'] : false
			);
		}
	}

	return array(
		'templates' => $output,
		'default_template_name' => $default_template_name,
		'default_template_key' => $default_template_key
	);
}
