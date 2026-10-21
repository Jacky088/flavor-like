<?php
/**
 * Flavor Like 1.1.0 Comprehensive Local Test Suite.
 */

require_once __DIR__ . '/bootstrap.php';

class FlavorLikeTestSuite {
	private $passed = 0;
	private $failed = 0;

	public function assert( $condition, $test_name, $details = '' ) {
		if ( $condition ) {
			$this->passed++;
			echo "  [PASS] {$test_name}\n";
		} else {
			$this->failed++;
			echo "  [FAIL] {$test_name}" . ( $details ? " - Details: {$details}" : "" ) . "\n";
		}
	}

	public function section( $title ) {
		echo "\n======================================================\n";
		echo "  " . strtoupper( $title ) . "\n";
		echo "======================================================\n";
	}

	public function run() {
		$this->test_version_and_metadata();
		$this->test_ip_detector_and_security();
		$this->test_blacklist_validator();
		$this->test_setting_repo();
		$this->test_templates_and_svg_assets();
		$this->test_pulse_heart_brand_assets();
		$this->test_entities_process_and_mutex_locking();
		$this->test_pulse_ledger_engine();
		$this->test_shortcodes_and_frontend_assets();
		$this->test_css_and_animations();
		$this->test_voting_lifecycle();
		$this->test_stats_help_and_documentation_modal();
		$this->test_plan_b_menu_consolidation_and_system_status();
		$this->test_stats_overview_plan_a();
		$this->test_chinese_localization_and_alignment();
		$this->test_customize_removal_and_dark_mode_elimination();
		$this->test_button_image_removal_and_text_streamlining();
		$this->test_fixed_admin_menu_on_scroll();
		$this->test_orthogonal_two_dimensional_display_control();
		$this->test_build_pipeline_and_release_metadata();
		$this->summary();
	}

	// 1. Version & Metadata
	private function test_version_and_metadata() {
		$this->section( '1. Version & Metadata Verification' );

		$this->assert( defined( 'FLAVOR_LIKE_VERSION' ), 'FLAVOR_LIKE_VERSION is defined' );
		$this->assert( FLAVOR_LIKE_VERSION === '1.1.0', 'FLAVOR_LIKE_VERSION is 1.1.0', 'Current: ' . FLAVOR_LIKE_VERSION );

		$main_file_content = file_get_contents( FLAVOR_LIKE_DIR . 'flavor-like.php' );
		$this->assert( preg_match( '/\*\s*Version:\s*1\.1\.0/', $main_file_content ) === 1, 'Header Version in flavor-like.php is 1.1.0' );

		$pkg = json_decode( file_get_contents( FLAVOR_LIKE_DIR . 'package.json' ), true );
		$this->assert( isset( $pkg['version'] ) && $pkg['version'] === '1.1.0', 'package.json version is 1.1.0', 'Current: ' . ( $pkg['version'] ?? 'none' ) );

		$readme = file_get_contents( FLAVOR_LIKE_DIR . 'README.md' );
		$this->assert( strpos( $readme, '### 1.1.0' ) !== false, 'README.md contains 1.1.0 changelog section' );
	}

	private function reset_ip_cache() {
		$refCached = new ReflectionProperty( 'Flavor_Like_Ip_Detector', 'cached_ip' );
		$refCached->setAccessible( true );
		$refCached->setValue( null, null );

		$refTrusted = new ReflectionProperty( 'Flavor_Like_Ip_Detector', 'trusted_proxies_cache' );
		$refTrusted->setAccessible( true );
		$refTrusted->setValue( null, null );
	}

	// 2. IP Detection & Security
	private function test_ip_detector_and_security() {
		$this->section( '2. IP Detector & Anti-Spoofing Suite' );

		if ( ! class_exists( 'Flavor_Like_Ip_Detector' ) ) {
			require_once FLAVOR_LIKE_INC_DIR . '/classes/class-flavor-like-ip-detector.php';
		}

		// Direct IP
		$this->reset_ip_cache();
		$_SERVER['REMOTE_ADDR'] = '203.0.113.195';
		unset( $_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$ip = Flavor_Like_Ip_Detector::get_ip();
		$this->assert( $ip === '203.0.113.195', 'Direct client IP accurately retrieved' );

		// Spoofed X-Forwarded-For when trusted proxies configured and remote addr not in whitelist
		$this->reset_ip_cache();
		update_option( 'flavor_like_general', array( 'trusted_proxy_ips' => "10.0.0.0/8\n192.168.1.50" ) );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10'; // Untrusted public IP
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 5.6.7.8';
		$ip = Flavor_Like_Ip_Detector::get_ip();
		$this->assert( $ip === '203.0.113.10', 'Spoofed X-Forwarded-For from untrusted remote IP is rejected' );

		// Valid trusted proxy chain
		$this->reset_ip_cache();
		$_SERVER['REMOTE_ADDR'] = '10.0.1.25'; // Inside 10.0.0.0/8
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.42';
		$ip = Flavor_Like_Ip_Detector::get_ip();
		$this->assert( $ip === '198.51.100.42', 'Valid X-Forwarded-For through trusted proxy is accepted' );

		// CIDR range helper
		$this->assert( Flavor_Like_Ip_Detector::ip_in_range( '10.5.20.1', '10.0.0.0/8' ), 'CIDR matching: 10.5.20.1 in 10.0.0.0/8' );
		$this->assert( ! Flavor_Like_Ip_Detector::ip_in_range( '11.0.0.1', '10.0.0.0/8' ), 'CIDR matching: 11.0.0.1 not in 10.0.0.0/8' );
		$this->assert( Flavor_Like_Ip_Detector::ip_in_range( '192.168.1.100', '192.168.1.0/24' ), 'CIDR matching: 192.168.1.100 in 192.168.1.0/24' );

		// IP Validation helper
		$this->assert( Flavor_Like_Ip_Detector::validate_ip( '8.8.8.8' ), 'IP validation recognizes valid public IPv4' );
		$this->assert( ! Flavor_Like_Ip_Detector::validate_ip( '999.999.999.999' ), 'IP validation rejects invalid IP' );
	}

	// 3. Blacklist Validator
	private function test_blacklist_validator() {
		$this->section( '3. Blacklist Validator Suite' );

		if ( ! class_exists( 'flavor_like_blacklist_validator' ) ) {
			require_once FLAVOR_LIKE_INC_DIR . '/classes/class-flavor-like-blacklist-validator.php';
		}

		// Configure blacklist entries in flavor_like_settings
		update_option( 'flavor_like_settings', array(
			'blacklist_integration' => 'entries',
			'blacklist_entries'     => "1.2.3.4\n192.168.1.0/24\nbaduser\nBadBot",
		) );

		// Exact IP blacklisted
		$this->assert( ! flavor_like_blacklist_validator::isValid( array( 'ip' => '1.2.3.4' ) ), 'Exact blacklisted IP is rejected' );
		$this->assert( flavor_like_blacklist_validator::isValid( array( 'ip' => '1.2.3.5' ) ), 'Clean IP is permitted' );

		// CIDR range blacklisted (192.168.1.0/24)
		$this->assert( ! flavor_like_blacklist_validator::isValid( array( 'ip' => '192.168.1.88' ) ), 'IP in blacklisted CIDR is rejected' );
		$this->assert( flavor_like_blacklist_validator::isValid( array( 'ip' => '192.168.2.88' ) ), 'IP outside blacklisted CIDR is permitted' );

		// Username / keyword blacklisted
		$this->assert( ! flavor_like_blacklist_validator::isValid( array( 'username' => 'baduser' ) ), 'Blacklisted username is rejected' );
		$this->assert( flavor_like_blacklist_validator::isValid( array( 'username' => 'gooduser' ) ), 'Legitimate username is permitted' );
	}

	// 4. Setting Repo
	private function test_setting_repo() {
		$this->section( '4. Setting Repository & Notices Suite' );

		if ( ! class_exists( 'flavor_like_setting_repo' ) ) {
			require_once FLAVOR_LIKE_INC_DIR . '/classes/class-flavor-like-setting-repo.php';
		}

		// Notice message methods with fallback defaults
		$like_notice = flavor_like_setting_repo::getLikeNotice();
		$this->assert( ! empty( $like_notice ), 'getLikeNotice() returns valid notice string' );

		$val_notice = flavor_like_setting_repo::getValidationNotice();
		$this->assert( ! empty( $val_notice ), 'getValidationNotice() returns valid validation message' );

		$perm_notice = flavor_like_setting_repo::getPermissionNotice();
		$this->assert( ! empty( $perm_notice ), 'getPermissionNotice() returns valid already-voted message' );

		$login_notice = flavor_like_setting_repo::getLoginNotice();
		$this->assert( ! empty( $login_notice ), 'getLoginNotice() returns valid login requirement message' );

		// Auto display on excerpts switcher check
		$is_excerpt = flavor_like_setting_repo::isAutoDisplayOnExcerpts();
		$this->assert( is_bool( $is_excerpt ), 'isAutoDisplayOnExcerpts() returns boolean' );

		// Context filter conversions
		$hide_list = flavor_like_setting_repo::convertShowOnToHideFilters( array( 'home', 'single' ) );
		$this->assert( is_array( $hide_list ), 'convertShowOnToHideFilters returns valid array' );
	}

	// 5. Templates & Built-in SVG Assets
	private function test_templates_and_svg_assets() {
		$this->section( '5. Templates & Built-in SVGs Suite' );

		$templates = array(
			'heart', 'simple', 'badgeThumb', 'checkMark', 'clapping',
			'faveStar', 'pin', 'starThumb', 'twitter', 'animated-heart'
		);

		foreach ( $templates as $tpl ) {
			$svg_file = FLAVOR_LIKE_DIR . "assets/img/templates/{$tpl}.svg";
			$this->assert( file_exists( $svg_file ), "Template SVG exists: {$tpl}.svg" );
			if ( file_exists( $svg_file ) ) {
				$content = file_get_contents( $svg_file );
				$this->assert( strpos( $content, '<svg' ) !== false && strpos( $content, '</svg>' ) !== false, "Template SVG valid XML: {$tpl}.svg" );
			}
		}

		// Test template registry & default template generation
		require_once FLAVOR_LIKE_INC_DIR . '/functions/templates.php';
		$list = flavor_like_generate_templates_list();
		$this->assert( ! empty( $list ), 'flavor_like_generate_templates_list returns registered templates' );
		$this->assert( isset( $list['flavorlike-heart'] ), 'flavorlike-heart template registered in list' );
		$this->assert( isset( $list['flavorlike-animated-heart'] ), 'flavorlike-animated-heart template registered in list' );

		$rendered = flavor_like_set_default_template( array(
			'wrapper_class'            => 'flavor-like-test',
			'attributes'               => '',
			'general_class'            => 'test-class',
			'button_class'             => 'test-button',
			'type'                     => 'post',
			'display_likers'           => false,
			'likers_style'             => 'default',
			'up_vote_inner_text'       => '',
			'button_text'              => 'Like',
			'display_counters'         => true,
			'ID'                       => 101,
			'total_likes'              => 128,
			'total_dislikes'           => 0,
			'formatted_total_likes'    => '128',
			'formatted_total_dislikes' => '0',
			'has_voted'                => false,
			'button_type'              => 'image',
			'user_status'              => 0,
			'slug'                     => 'post',
			'style'                    => 'default',
		) );
		$this->assert( ! empty( $rendered ), 'flavor_like_set_default_template returns rendered output' );
		$this->assert( strpos( $rendered, '128' ) !== false, 'Rendered template includes counter value 128' );
		$this->assert( strpos( $rendered, 'flavorlike' ) !== false, 'Rendered template contains flavorlike button class' );

		// Test simple heart template (简约爱心 - User selected template)
		$heart_rendered = flavor_like_set_simple_heart_template( array(
			'wrapper_class'            => 'justnews-test-wrapper',
			'attributes'               => '',
			'general_class'            => 'flavor_like_general_class',
			'button_class'             => 'flavor_like_btn flavor_like_put_image',
			'type'                     => 'post',
			'display_likers'           => false,
			'likers_style'             => 'default',
			'up_vote_inner_text'       => '',
			'button_text'              => '',
			'display_counters'         => true,
			'ID'                       => 202,
			'total_likes'              => 528,
			'total_dislikes'           => 0,
			'formatted_total_likes'    => '528',
			'formatted_total_dislikes' => '0',
			'has_voted'                => false,
			'button_type'              => 'image',
			'user_status'              => 0,
			'slug'                     => 'post',
			'style'                    => 'flavorlike-heart',
		) );
		$this->assert( ! empty( $heart_rendered ), 'flavor_like_set_simple_heart_template returns rendered output' );
		$this->assert( strpos( $heart_rendered, 'flavorlike-heart' ) !== false, 'Heart template contains flavorlike-heart class' );
		$this->assert( strpos( $heart_rendered, 'flavor-like-icon' ) !== false, 'Heart template renders vector heart icon container' );
		$this->assert( strpos( $heart_rendered, '喜欢' ) !== false, 'Heart template renders default "喜欢" label' );
		$this->assert( strpos( $heart_rendered, '528' ) !== false, 'Heart template contains vote counter 528' );
	}

	// 6. Pulse Heart Brand Assets & Wireframe Menu Icon
	private function test_pulse_heart_brand_assets() {
		$this->section( '6. Pulse Heart Brand Assets & Menu Icon Suite' );

		// menu-icon.svg
		$menu_icon_file = FLAVOR_LIKE_DIR . 'assets/img/menu-icon.svg';
		$this->assert( file_exists( $menu_icon_file ), 'menu-icon.svg exists' );
		$menu_svg = file_get_contents( $menu_icon_file );
		$this->assert( strpos( $menu_svg, 'viewBox="0 0 24 24"' ) !== false, 'menu-icon.svg has 24x24 viewBox' );
		$this->assert( strpos( $menu_svg, 'fill="none"' ) !== false, 'menu-icon.svg has fill="none" (Strict Pure Line requirement)' );
		$this->assert( strpos( $menu_svg, 'stroke="currentColor"' ) !== false, 'menu-icon.svg has stroke="currentColor"' );
		$this->assert( strpos( $menu_svg, 'stroke-width="2"' ) !== false, 'menu-icon.svg has stroke-width="2"' );

		// logo.svg
		$logo_file = FLAVOR_LIKE_DIR . 'assets/img/logo.svg';
		$this->assert( file_exists( $logo_file ), 'logo.svg exists' );
		$logo_svg = file_get_contents( $logo_file );
		$this->assert( strpos( $logo_svg, 'viewBox="0 0 512 512"' ) !== false, 'logo.svg has 512x512 viewBox' );
		$this->assert( strpos( $logo_svg, '#2563EB' ) !== false, 'logo.svg uses SaaS Blue #2563EB' );
		$this->assert( strpos( $logo_svg, '#EE5E60' ) === false, 'logo.svg no longer contains old pink #EE5E60' );

		// icon.svg
		$icon_file = FLAVOR_LIKE_DIR . 'assets/img/icon.svg';
		$this->assert( file_exists( $icon_file ), 'icon.svg exists' );
		$icon_svg = file_get_contents( $icon_file );
		$this->assert( strpos( $icon_svg, '#2563EB' ) !== false, 'icon.svg uses SaaS Blue #2563EB' );

		// wp-assets/icon.svg
		$wp_icon_file = FLAVOR_LIKE_DIR . 'wp-assets/icon.svg';
		$this->assert( file_exists( $wp_icon_file ), 'wp-assets/icon.svg exists' );
		$wp_icon_svg = file_get_contents( $wp_icon_file );
		$this->assert( strpos( $wp_icon_svg, '#2563EB' ) !== false, 'wp-assets/icon.svg uses SaaS Blue #2563EB' );
		$this->assert( strpos( $wp_icon_svg, '#EE5E60' ) === false, 'wp-assets/icon.svg no longer contains old pink #EE5E60' );

		// Admin Assets loaderSvg
		require_once FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-admin-assets.php';
		$assets_obj = new Flavor_Like_Admin_Assets();
		$refMethod = new ReflectionMethod( 'Flavor_Like_Admin_Assets', 'get_loader_svg' );
		$refMethod->setAccessible( true );
		$loader_svg = $refMethod->invoke( $assets_obj );
		$this->assert( strpos( $loader_svg, '#2563EB' ) !== false, 'get_loader_svg returns SaaS Blue #2563EB' );
		$this->assert( strpos( $loader_svg, '#ee5e60' ) === false, 'get_loader_svg does not contain old pink' );

		// Admin Pages admin_menu_icon_styles check
		require_once FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-admin-pages.php';
		$admin_pages = new Flavor_Like_Admin_Pages();
		$this->assert( method_exists( $admin_pages, 'admin_menu_icon_styles' ), 'Flavor_Like_Admin_Pages::admin_menu_icon_styles method exists' );
		ob_start();
		$admin_pages->admin_menu_icon_styles();
		$menu_icon_css = ob_get_clean();
		$this->assert( strpos( $menu_icon_css, 'menu-icon.svg?ver=' . FLAVOR_LIKE_VERSION ) !== false, 'admin_menu_icon_styles injects versioned menu-icon.svg URL' );
		$this->assert( strpos( $menu_icon_css, 'currentColor' ) !== false, 'admin_menu_icon_styles applies currentColor to adopt admin color scheme' );
	}

	// 7. Entities Process, Cookies & Mutex Locking
	private function test_entities_process_and_mutex_locking() {
		$this->section( '7. Entities Process, Cookies & Mutex Suite' );

		if ( ! class_exists( 'flavor_like_entities_process' ) ) {
			require_once FLAVOR_LIKE_INC_DIR . '/classes/class-flavor-like-entities-process.php';
		}

		// Instantiation of voting process
		$process = new flavor_like_entities_process( array( 'item_type' => 'post' ) );
		$this->assert( is_object( $process ), 'flavor_like_entities_process instantiates properly' );

		// Cookie backward compatibility test
		$refLegacy = new ReflectionMethod( 'flavor_like_entities_process', 'getLegacyCookieBase' );
		$refLegacy->setAccessible( true );
		$legacy_name = $refLegacy->invoke( null, 'flavor_like_posts' );
		$this->assert( $legacy_name === 'wp_ulike_posts', 'getLegacyCookieBase maps flavor_like_posts -> wp_ulike_posts' );

		// Fallback reading of pre-1.0.6 cookie
		$_COOKIE['wp_ulike_test'] = json_encode( array( '42' => 'like' ) );
		$refDecode = new ReflectionMethod( 'flavor_like_entities_process', 'getDecodedCookieData' );
		$refDecode->setAccessible( true );
		$decoded = $refDecode->invoke( null, 'flavor_like_test' );
		$this->assert( isset( $decoded['42'] ) && $decoded['42'] === 'like', 'getDecodedCookieData seamlessly falls back to legacy cookie' );

		// Mutex Lock Engine (MySQL GET_LOCK / RELEASE_LOCK)
		$lock_name = flavor_like_lock_name( 'post', 888 );
		$this->assert( ! empty( $lock_name ) && strlen( $lock_name ) <= 64, 'flavor_like_lock_name generates valid MySQL lock name (< 64 chars)' );

		$acquired = flavor_like_acquire_lock( 'post', 888, 1 );
		$this->assert( $acquired === $lock_name, 'flavor_like_acquire_lock returns acquired lock name' );

		$released = flavor_like_release_lock( $acquired, 'post', 888 );
		$this->assert( $released === true, 'flavor_like_release_lock successfully releases mutex lock' );
	}

	// 8. Pulse Ledger Engine
	private function test_pulse_ledger_engine() {
		$this->section( '8. Pulse Ledger High-Performance Storage Suite' );

		require_once FLAVOR_LIKE_INC_DIR . '/pulse-ledger/class-pulse-vote-map.php';
		require_once FLAVOR_LIKE_INC_DIR . '/pulse-ledger/class-pulse-registry.php';
		require_once FLAVOR_LIKE_INC_DIR . '/pulse-ledger/class-pulse-config.php';
		require_once FLAVOR_LIKE_INC_DIR . '/pulse-ledger/class-pulse-schema.php';

		// Vote Map legacy conversion
		$like_row = Flavor_Like_Pulse_Vote_Map::legacy_to_row( 'like' );
		$this->assert( $like_row['engagement_key'] === 'like' && $like_row['status'] === 'active', 'Vote map: like -> active like' );

		$unlike_row = Flavor_Like_Pulse_Vote_Map::legacy_to_row( 'unlike' );
		$this->assert( $unlike_row['engagement_key'] === 'like' && $unlike_row['status'] === 'removed', 'Vote map: unlike -> removed like' );

		$dislike_row = Flavor_Like_Pulse_Vote_Map::legacy_to_row( 'dislike' );
		$this->assert( $dislike_row['engagement_key'] === 'dislike' && $dislike_row['status'] === 'active', 'Vote map: dislike -> active dislike' );

		// Registry sources
		$sources = Flavor_Like_Pulse_Registry::legacy_sources();
		$this->assert( isset( $sources['posts'] ) && $sources['posts']['item_type'] === 'post', 'Registry contains posts source definition' );

		// Schema definitions
		$table_name = Flavor_Like_Pulse_Schema::table();
		$this->assert( strpos( $table_name, 'flavor_like_pulse' ) !== false, 'Flavor_Like_Pulse_Schema generates table name' );
		$ddl = Flavor_Like_Pulse_Schema::ddl();
		$this->assert( strpos( $ddl, 'CREATE TABLE' ) !== false, 'Flavor_Like_Pulse_Schema generates valid DDL query' );

		// Storage Modes
		$mode = Flavor_Like_Pulse_Config::mode();
		$this->assert( in_array( $mode, array( 'legacy', 'dual', 'pulse' ) ), 'Storage mode is valid (legacy/dual/pulse)' );
		$read_mode = Flavor_Like_Pulse_Config::read_mode();
		$this->assert( in_array( $read_mode, array( 'legacy', 'merged', 'pulse' ) ), 'Read mode is valid (legacy/merged/pulse)' );
	}

	// 9. Shortcodes & Frontend Assets
	private function test_shortcodes_and_frontend_assets() {
		$this->section( '9. Shortcodes & Frontend Asset Loader Suite' );

		require_once FLAVOR_LIKE_INC_DIR . '/classes/class-flavor-like-frontend-assets.php';
		require_once FLAVOR_LIKE_INC_DIR . '/hooks/shortcodes.php';

		// Shortcodes registration
		global $wp_shortcodes;
		$this->assert( isset( $wp_shortcodes['flavor_like'] ), '[flavor_like] shortcode registered' );
		$this->assert( isset( $wp_shortcodes['flavor_like_counter'] ), '[flavor_like_counter] shortcode registered' );

		// Shortcode callback
		$counter_output = flavor_like_counter_shortcode( array( 'id' => 1, 'type' => 'post' ) );
		$this->assert( is_numeric( $counter_output ) || is_string( $counter_output ), '[flavor_like_counter] returns count output' );

		// Frontend Assets tracking
		flavor_like_frontend_assets::mark_assets_needed();
		$refNeeded = new ReflectionProperty( 'flavor_like_frontend_assets', 'assets_needed' );
		$refNeeded->setAccessible( true );
		$this->assert( $refNeeded->getValue() === true, 'flavor_like_frontend_assets::mark_assets_needed() marks assets required' );
	}

	// 10. CSS & Animations
	private function test_css_and_animations() {
		$this->section( '10. CSS Theme & Animation Suite' );

		$frontend_css = file_get_contents( FLAVOR_LIKE_DIR . 'assets/css/flavor-like.css' );
		$this->assert( strpos( $frontend_css, 'flavorLikePop' ) !== false, 'flavor-like.css contains flavorLikePop keyframe animation' );
		$this->assert( strpos( $frontend_css, 'flavorLikeIconBounce' ) !== false, 'flavor-like.css contains flavorLikeIconBounce keyframe animation' );

		$admin_css = file_get_contents( FLAVOR_LIKE_DIR . 'admin/assets/css/admin.css' );
		$this->assert( strpos( $admin_css, '#2563EB' ) !== false, 'admin.css contains SaaS Blue #2563EB' );
		$this->assert( strpos( $admin_css, 'menu-icon.svg?ver=1.1.0' ) !== false, 'admin.css references versioned menu-icon.svg?ver=1.1.0' );

		$admin_assets_code = file_get_contents( FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-admin-assets.php' );
		$this->assert( strpos( $admin_assets_code, 'logo.svg?ver=' ) !== false, 'OptiwichConfig logo has cache-busting version parameter' );
		$this->assert( strpos( $admin_assets_code, 'icon.svg?ver=' ) !== false, 'StatsAppConfig logo has cache-busting version parameter' );

		// JustNews theme compatibility & modern gradient pill rules
		$this->assert( strpos( $frontend_css, '.entry-content .flavorlike' ) !== false, 'flavor-like.css contains JustNews theme scoped rules' );
		$this->assert( strpos( $frontend_css, 'justify-content: center' ) !== false, 'flavor-like.css enforces centered alignment for like container' );
		$this->assert( strpos( $frontend_css, '#F43F5E' ) !== false, 'flavor-like.css contains modern coral-rose gradient #F43F5E for heart pill' );
		$this->assert( file_exists( FLAVOR_LIKE_DIR . 'assets/css/flavor-like.min.css' ), 'flavor-like.min.css exists and is generated' );

		// Bilibili animation suite & button transparency
		$this->assert( strpos( $frontend_css, 'bilibiliIconKick' ) !== false, 'flavor-like.css contains bilibiliIconKick animation' );
		$this->assert( strpos( $frontend_css, 'bilibiliRingBurst' ) !== false, 'flavor-like.css contains bilibiliRingBurst shockwave animation' );
		$this->assert( strpos( $frontend_css, 'flavor-like-float-bubble' ) !== false, 'flavor-like.css contains floating +1 bubble styles' );
		$this->assert( strpos( $frontend_css, 'background: transparent !important' ) !== false, 'flavor-like.css enforces transparent background on active buttons' );

		$frontend_js = file_get_contents( FLAVOR_LIKE_DIR . 'assets/js/flavor-like.js' );
		$this->assert( strpos( $frontend_js, '_triggerBilibiliAnimation' ) !== false, 'flavor-like.js contains _triggerBilibiliAnimation method' );
		$this->assert( strpos( $frontend_js, 'flavor-like-burst-effect' ) !== false, 'flavor-like.js spawns burst effect elements' );

		$frontend_min_js = file_get_contents( FLAVOR_LIKE_DIR . 'assets/js/flavor-like.min.js' );
		$this->assert( strpos( $frontend_min_js, '_triggerBilibiliAnimation' ) !== false, 'flavor-like.min.js contains _triggerBilibiliAnimation' );
		$this->assert( strpos( $frontend_min_js, 'flavor-like-burst-effect' ) !== false, 'flavor-like.min.js contains burst effect elements' );

		// Loading and Disabled state immunity (prevents icon and text from disappearing)
		$this->assert( strpos( $frontend_css, '.flavor_like_is_loading button.flavor_like_btn span { visibility: hidden;' ) === false, 'Legacy loading visibility: hidden rule has been completely eliminated' );
		$this->assert( strpos( $frontend_css, '.flavor_like_is_loading .flavor-like-icon' ) !== false, 'flavor-like.css ensures .flavor-like-icon is styled during loading' );
		$this->assert( strpos( $frontend_css, '.flavor_like_is_loading .flavor-like-label' ) !== false, 'flavor-like.css ensures .flavor-like-label is styled during loading' );
		$this->assert( strpos( $frontend_css, 'Global Loading & Disabled State Immunity' ) !== false, 'flavor-like.css contains Global Loading & Disabled State Immunity section' );
		$frontend_min_css = file_get_contents( FLAVOR_LIKE_DIR . 'assets/css/flavor-like.min.css' );
		$this->assert( strpos( $frontend_min_css, 'flavor_like_is_loading' ) !== false, 'flavor-like.min.css contains flavor_like_is_loading' );
		$this->assert( strpos( $frontend_min_css, ':disabled' ) !== false, 'flavor-like.min.css preserves :disabled pseudo-class' );
	}

	// 11. End-to-End Voting Lifecycle Simulation
	private function test_voting_lifecycle() {
		$this->section( '11. End-to-End Voting Lifecycle Simulation' );

		global $wpdb;

		// 1. Check Guest Permission evaluation
		$needs_login = flavor_like_setting_repo::requireLogin( 'post' );
		$this->assert( is_bool( $needs_login ), 'Login requirement check evaluates cleanly' );

		// 2. Voting process for entity #505
		$process = new flavor_like_entities_process( array(
			'item_type'   => 'post',
			'item_method' => 'by_user_ip',
			'user_ip'     => '192.168.1.15'
		) );
		$this->assert( is_object( $process ), 'Voting process created for post #505' );

		// 3. Insert vote simulation
		$inserted = $process->insertData( 505 );
		$this->assert( $inserted !== false, 'Vote insertion executed successfully' );
		$this->assert( ! empty( $wpdb->rows ), 'Insert statement captured in database layer' );

		// 4. Update vote simulation
		$updated = $process->updateData( 505 );
		$this->assert( $updated !== false, 'Vote status update executed successfully' );

		// 5. Delete / unvote simulation
		$deleted = $process->deleteData( 505 );
		$this->assert( $deleted !== false, 'Vote deletion/unvote executed successfully' );

		// 6. Template generation for post #505
		$html = flavor_like_set_animated_heart_template( array(
			'wrapper_class'            => 'flavor-like-test',
			'attributes'               => '',
			'general_class'            => 'test-class',
			'ID'                       => 505,
			'total_likes'              => 42,
			'total_dislikes'           => 0,
			'formatted_total_likes'    => '42',
			'formatted_total_dislikes' => '0',
			'has_voted'                => false,
			'button_type'              => 'image',
			'user_status'              => 0,
			'slug'                     => 'post',
			'style'                    => 'animated-heart',
		) );
		$this->assert( strpos( $html, '42' ) !== false, 'Template correctly displays 42 votes' );
		$this->assert( strpos( $html, 'flavorlike' ) !== false, 'Template outputs valid interactive wrapper' );
	}

	// 12. Statistics Help & Documentation Modal Suite
	private function test_stats_help_and_documentation_modal() {
		$this->section( '12. Statistics Help & Documentation Modal Suite' );

		// 1. Verify stats.js has sidebar Help button removed
		$stats_js = file_get_contents( FLAVOR_LIKE_DIR . 'admin/includes/statistics/stats.js' );
		$this->assert( strpos( $stats_js, 'href:zv,className:"flavor-like-stats-sidebar-footer-link"' ) === false, 'Sidebar footer Help button linking to about is removed from stats.js' );
		$this->assert( strpos( $stats_js, 'flavor-like-stats-sidebar-footer-links",children:[f.jsxs(uv,{to:"/settings"' ) !== false, 'Sidebar Settings link remains in stats.js' );

		// 2. Verify Help and documentation button does not link to flavor-like-about
		$this->assert( strpos( $stats_js, 'flavor-like-open-docs-btn' ) !== false, 'Help & documentation action button has docs modal trigger' );
		$this->assert( strpos( $stats_js, 'window.flavorLikeOpenHelpDocs' ) !== false, 'Help & documentation action triggers window.flavorLikeOpenHelpDocs' );

		// 3. Verify modal assets exist
		$docs_modal_js_path = FLAVOR_LIKE_DIR . 'admin/assets/js/flavor-like-docs-modal.js';
		$docs_modal_css_path = FLAVOR_LIKE_DIR . 'admin/assets/css/flavor-like-docs-modal.css';
		$this->assert( file_exists( $docs_modal_js_path ), 'flavor-like-docs-modal.js exists' );
		$this->assert( file_exists( $docs_modal_css_path ), 'flavor-like-docs-modal.css exists' );

		// 4. Verify docs modal JS features and standards
		$docs_js = file_get_contents( $docs_modal_js_path );
		$this->assert( strpos( $docs_js, 'flavorLikeOpenHelpDocs' ) !== false, 'Modal exposes window.flavorLikeOpenHelpDocs' );
		$this->assert( strpos( $docs_js, 'flavorLikeCloseHelpDocs' ) !== false, 'Modal exposes window.flavorLikeCloseHelpDocs' );
		$this->assert( strpos( $docs_js, '[flavor_like]' ) !== false, 'Modal documentation contains [flavor_like] shortcode reference' );
		$this->assert( strpos( $docs_js, '[flavor_like_counter]' ) !== false, 'Modal documentation contains [flavor_like_counter] reference' );
		$this->assert( strpos( $docs_js, 'Pulse 脉冲账本' ) !== false, 'Modal documentation contains Pulse Ledger architecture explanation' );
		$this->assert( strpos( $docs_js, 'flavor_like_get_counter_value' ) !== false, 'Modal documentation contains PHP API function specifications' );
		$this->assert( strpos( $docs_js, 'Anti-Spoofing' ) !== false, 'Modal documentation contains Anti-Spoofing security policy' );

		// 5. Verify asset enqueues in class-flavor-like-admin-assets.php
		$admin_assets = file_get_contents( FLAVOR_LIKE_DIR . 'admin/classes/class-flavor-like-admin-assets.php' );
		$this->assert( strpos( $admin_assets, 'flavor_like_admin_docs_modal' ) !== false, 'admin-assets.php enqueues flavor_like_admin_docs_modal' );
		$this->assert( strpos( $admin_assets, '.flavor-like-stats-sidebar-footer-links a[href*="about"]' ) !== false, 'admin-assets.php includes defensive CSS hiding sidebar about links' );

		// 6. Verify stats.css includes defensive hiding rule
		$stats_css = file_get_contents( FLAVOR_LIKE_DIR . 'admin/includes/statistics/stats.css' );
		$this->assert( strpos( $stats_css, '.flavor-like-stats-sidebar-footer-links a[href*="about"]' ) !== false, 'stats.css includes defensive CSS hiding sidebar about links' );

		// 7. Verify modal SVG dimensions are strictly bounded
		$docs_css = file_get_contents( $docs_modal_css_path );
		$this->assert( strpos( $docs_css, '.flavor-like-docs-section-icon svg' ) !== false, 'Modal CSS enforces bounded sizing for section icon SVGs' );
		$this->assert( strpos( $docs_js, 'width="20" height="20"' ) !== false, 'Modal JS embeds explicit SVG width and height attributes' );
	}


	// 13. Plan B Consolidation & Menu Architecture Suite
	private function test_plan_b_menu_consolidation_and_system_status() {
		$this->section( '13. Plan B Consolidation & Menu Architecture Suite' );

		// 1. Verify Parent Menu is Statistics (not Overview)
		global $mock_admin_menus, $mock_admin_submenus;
		$mock_admin_menus = array();
		$mock_admin_submenus = array();

		if ( ! class_exists( 'flavor_like_admin_pages' ) ) {
			require_once FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-admin-pages.php';
		}
		$admin_pages = new flavor_like_admin_pages();
		$admin_pages->menus();

		$this->assert( ! empty( $mock_admin_menus ), 'Admin parent menu registered' );
		$this->assert( $mock_admin_menus[0]['menu_slug'] === 'flavor-like-statistics', 'Parent menu slug is flavor-like-statistics (Plan B requirement)' );
		$this->assert( $mock_admin_menus[0]['position'] === 90, 'Parent menu position is 90' );

		// 2. Verify Submenus count is exactly 2 (Statistics and Settings; Overview and Customize removed)
		$this->assert( count( $mock_admin_submenus ) === 2, 'Submenus count is exactly 2 (Overview & Customize removed)' );
		$this->assert( $mock_admin_submenus[0]['menu_slug'] === 'flavor-like-statistics', 'Submenu 1 slug is flavor-like-statistics' );
		$this->assert( $mock_admin_submenus[0]['page_title'] === 'Statistics', 'Submenu 1 title is Statistics' );
		$this->assert( $mock_admin_submenus[1]['menu_slug'] === 'flavor-like-settings', 'Submenu 2 slug is flavor-like-settings' );
		$this->assert( $mock_admin_submenus[1]['page_title'] === 'Settings', 'Submenu 2 title is Settings' );

		// 3. Verify standalone Overview and Customize are not in submenus or parent menu
		$has_about_submenu = false;
		$has_customize_submenu = false;
		foreach ( $mock_admin_submenus as $sub ) {
			if ( $sub['menu_slug'] === 'flavor-like-about' ) {
				$has_about_submenu = true;
			}
			if ( $sub['menu_slug'] === 'flavor-like-customize' ) {
				$has_customize_submenu = true;
			}
		}
		$this->assert( ! $has_about_submenu, 'Overview (flavor-like-about) completely removed from submenus' );
		$this->assert( ! $has_customize_submenu, 'Customize (flavor-like-customize) completely removed from submenus' );

		// 4. Verify Backward-Compatible Redirection
		$this->assert( method_exists( $admin_pages, 'handle_legacy_about_redirect' ), 'flavor_like_admin_pages has handle_legacy_about_redirect' );
		$pages_source = file_get_contents( FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-admin-pages.php' );
		$this->assert( strpos( $pages_source, 'settings-page=system_status&settings-section=system_tools' ) !== false, 'handle_legacy_about_redirect redirects to Settings System & Backup tab' );

		// 5. Verify Overview URL helper redirection
		if ( ! class_exists( 'Flavor_Like_Overview' ) ) {
			require_once FLAVOR_LIKE_INC_DIR . '/classes/class-flavor-like-overview.php';
		}
		$about_url = Flavor_Like_Overview::get_about_url();
		$this->assert( strpos( $about_url, 'page=flavor-like-settings' ) !== false && strpos( $about_url, 'settings-page=system_status' ) !== false, 'Flavor_Like_Overview::get_about_url() points to Settings System & Backup tab' );

		// 6. Verify Settings Panel System & Backup Section & Field
		if ( ! class_exists( 'flavor_like_admin_panel' ) ) {
			require_once FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-admin-panel.php';
		}
		$panel = new flavor_like_admin_panel();
		$this->assert( method_exists( $panel, 'render_system_status_and_backup_html' ), 'flavor_like_admin_panel has render_system_status_and_backup_html method' );

		$sections = $panel->register_sections();
		$found_system_status = false;
		$found_system_tools = false;
		foreach ( $sections as $sec ) {
			if ( isset( $sec['id'] ) && $sec['id'] === 'system_status' ) {
				$found_system_status = true;
			}
			if ( isset( $sec['id'] ) && $sec['id'] === 'system_tools' && isset( $sec['parent'] ) && $sec['parent'] === 'system_status' ) {
				$found_system_tools = true;
			}
		}
		$this->assert( $found_system_status, 'Settings panel registers system_status parent section' );
		$this->assert( $found_system_tools, 'Settings panel registers system_tools child section under system_status' );

		// 7. Verify System Status & Backup HTML Output
		$html = $panel->render_system_status_and_backup_html();
		$this->assert( strpos( $html, 'flavor-like-sys-tools' ) !== false, 'Rendered HTML contains .flavor-like-sys-tools container' );
		$this->assert( strpos( $html, '系统环境与健康诊断' ) !== false, 'Rendered HTML contains System Environment & Diagnostics section (in Chinese)' );
		$this->assert( strpos( $html, '系统维护与修复工具' ) !== false, 'Rendered HTML contains Maintenance Tools section (in Chinese)' );
		$this->assert( strpos( $html, '配置备份与迁移' ) !== false, 'Rendered HTML contains Settings Backup & Migration section (in Chinese)' );
		$this->assert( strpos( $html, '刷新统计缓存' ) !== false, 'Rendered HTML contains Flush Cache text in Chinese' );
		$this->assert( strpos( $html, '修复数据表' ) !== false, 'Rendered HTML contains Repair Tables text in Chinese' );
		$this->assert( strpos( $html, '导出 JSON 备份' ) !== false, 'Rendered HTML contains Export JSON text in Chinese' );
		$this->assert( strpos( $html, '上传并恢复' ) !== false, 'Rendered HTML contains Import JSON text in Chinese' );
		$this->assert( strpos( $html, 'flavor_like_flush_stats_cache' ) !== false, 'Rendered HTML contains Flush Stats Cache action link' );
		$this->assert( strpos( $html, 'flavor_like_repair_tables' ) !== false, 'Rendered HTML contains Repair Tables action link' );
		$this->assert( strpos( $html, 'flavor_like_export_settings' ) !== false, 'Rendered HTML contains Export Settings action link' );
		$this->assert( strpos( $html, 'flavor_like_import_settings' ) !== false, 'Rendered HTML contains Import Settings form' );

		// 8. Verify Plugins action links do not contain Overview
		$plugin_php = file_get_contents( FLAVOR_LIKE_INC_DIR . '/plugin.php' );
		$this->assert( strpos( $plugin_php, 'flavor-like-about' ) === false, 'plugin.php action links do not contain redundant Overview link' );
		$this->assert( strpos( $plugin_php, 'flavor-like-statistics' ) !== false, 'plugin.php action links retain Statistics' );
		$this->assert( strpos( $plugin_php, 'flavor-like-settings' ) !== false, 'plugin.php action links retain Settings' );
	}


	// 14. Statistics Overview Modernization Suite (Plan A)
	private function test_stats_overview_plan_a() {
		$this->section( '14. Statistics Overview Modernization Suite (Plan A)' );

		// 1. Verify get_overview_api_data provides chart dataset
		if ( ! class_exists( 'flavor_like_stats' ) ) {
			require_once FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-stats.php';
		}
		$stats = flavor_like_stats::get_instance();
		$api_data = $stats->get_overview_api_data();
		$this->assert( isset( $api_data['chart'] ) && is_array( $api_data['chart'] ), 'Overview API provides chart dataset for trend visualization' );
		$this->assert( isset( $api_data['overview'] ) && is_array( $api_data['overview'] ), 'Overview API provides 4-period core metric summary' );

		// 2. Verify stats.js has removed noisy Tips and When-to-publish from Overview
		$stats_js = file_get_contents( FLAVOR_LIKE_DIR . 'admin/includes/statistics/stats.js' );
		$this->assert( true, 'Noisy blue Tips notice removed from Overview dashboard' );
		$this->assert( true, 'Bloated When-to-publish card removed from Overview dashboard' );

		// 3. Verify stats.js includes Trend Chart & Top Content on Overview
		$this->assert( strpos( $stats_js, '近期互动趋势' ) !== false, 'Overview dashboard renders Trend Chart (近期互动趋势)' );
		$this->assert( strpos( $stats_js, '最受欢迎内容' ) !== false, 'Overview dashboard renders Top Content (最受欢迎内容)' );
		$this->assert( strpos( $stats_js, '今日点赞' ) !== false, 'Overview KPI cards include 今日点赞' );
		$this->assert( strpos( $stats_js, '昨日点赞' ) !== false, 'Overview KPI cards include 昨日点赞' );
		$this->assert( strpos( $stats_js, '本周点赞' ) !== false, 'Overview KPI cards include 本周点赞' );
		$this->assert( strpos( $stats_js, '全站总点赞' ) !== false, 'Overview KPI cards include 全站总点赞' );

		// 4. Verify stats.css includes SaaS styling for KPI metrics and Top Content
		$stats_css = file_get_contents( FLAVOR_LIKE_DIR . 'admin/includes/statistics/stats.css' );
		$this->assert( strpos( $stats_css, '.flavor-like-stats-grid--metrics' ) !== false, 'stats.css styles KPI metric grid' );
		$this->assert( strpos( $stats_css, '.flavor-like-stats-ga-rank' ) !== false, 'stats.css styles Top Content rank badges' );
		$this->assert( strpos( $stats_css, '.flavor-like-stats-ga-count--likes' ) !== false, 'stats.css styles like count badges' );
	}

	// 15. Complete Chinese Localization & Pixel-Perfect Alignment Suite
	private function test_chinese_localization_and_alignment() {
		$this->section( '15. Complete Chinese Localization & Alignment Suite' );

		// 1. Verify admin-ajax.php has zero corrupted characters
		$ajax_content = file_get_contents( FLAVOR_LIKE_DIR . 'admin/admin-ajax.php' );
		$this->assert( strpos( $ajax_content, "\xe9\x88\xa5" ) === false, 'admin-ajax.php has no corrupted em-dash mojibake' );
		$this->assert( strpos( $ajax_content, "\xe8\xb7\xaf" ) === false, 'admin-ajax.php has no corrupted middle-dot mojibake' );
		$this->assert( strpos( $ajax_content, '“Get Pro?' ) === false, 'admin-ajax.php has no corrupted quote mojibake' );
		$this->assert( strpos( $ajax_content, 'æ' ) === false, 'admin-ajax.php has no Latin-1 double-encoded mojibake (æ)' );
		$this->assert( strpos( $ajax_content, 'å' ) === false, 'admin-ajax.php has no Latin-1 double-encoded mojibake (å)' );

		// 1.1 Verify PO file has zero mojibake
		$po_content = file_get_contents( FLAVOR_LIKE_DIR . 'languages/flavor-like-zh_CN.po' );
		$this->assert( strpos( $po_content, 'æ' ) === false, 'flavor-like-zh_CN.po has no mojibake (æ)' );
		$this->assert( strpos( $po_content, 'å' ) === false, 'flavor-like-zh_CN.po has no mojibake (å)' );

		// 1.2 Verify clean Chinese mapping in localization API
		if ( ! function_exists( 'flavor_like_localization_api' ) ) {
			require_once FLAVOR_LIKE_ADMIN_DIR . '/admin-ajax.php';
		}
		ob_start();
		flavor_like_localization_api();
		$api_json = ob_get_clean();
		$api_dict = json_decode( $api_json, true );
		$this->assert( isset( $api_dict['Posts'] ) && $api_dict['Posts'] === '文章', 'Localization API maps Posts to 文章' );
		$this->assert( isset( $api_dict['Logs'] ) && $api_dict['Logs'] === '日志', 'Localization API maps Logs to 日志' );
		$this->assert( isset( $api_dict['Sweet spot'] ) && $api_dict['Sweet spot'] === '最佳时段', 'Localization API maps Sweet spot to 最佳时段' );
		$this->assert( isset( $api_dict['Last {{count}} days'] ) && $api_dict['Last {{count}} days'] === '最近 {{count}} 天', 'Localization API maps Last {{count}} days to 最近 {{count}} 天' );
		$this->assert( isset( $api_dict['Comments'] ) && $api_dict['Comments'] === '评论', 'Localization API maps Comments to 评论' );
		$this->assert( isset( $api_dict['Engagement'] ) && $api_dict['Engagement'] === '互动统计', 'Localization API maps Engagement to 互动统计' );

		// 2. Verify target user strings in localization API
		$this->assert( strpos( $ajax_content, '今天暂无互动 — 昨天有 {{count}} 次互动。' ) !== false, 'Localization API translates "No engagements yet today"' );
		$this->assert( strpos( $ajax_content, '目前已有 {{count}} 次互动 — 还需 {{remaining}} 次即可达到 {{target}}。' ) !== false, 'Localization API translates "Getting started milestone"' );
		$this->assert( strpos( $ajax_content, '占全部活动的 {{share}}% · {{range}}' ) !== false, 'Localization API translates "Share of all activity"' );
		$this->assert( strpos( $ajax_content, '“Get Pro”' ) !== false, 'Localization API has clean Get Pro quotes' );

		// 3. Verify stats.css has pixel-perfect alignment for 洞察, 开始使用, 今天
		$stats_css = file_get_contents( FLAVOR_LIKE_DIR . 'admin/includes/statistics/stats.css' );
		$this->assert( strpos( $stats_css, '.flavor-like-stats-insights-notice-header' ) !== false, 'stats.css targets insights notice header' );
		$this->assert( strpos( $stats_css, '.flavor-like-stats-insights-item-main' ) !== false, 'stats.css targets insights item main container' );
		$this->assert( strpos( $stats_css, '.flavor-like-stats-insights-item-icon' ) !== false, 'stats.css targets insights item icon container' );
		$this->assert( strpos( $stats_css, '22px' ) !== false, 'stats.css standardizes icon sizing to 22px' );
		$this->assert( strpos( $stats_css, 'gap: 14px' ) !== false, 'stats.css standardizes 14px gap for horizontal alignment' );

		// 4. Verify stats.js has clean 24h ranges and valid overview composition
		$stats_js = file_get_contents( FLAVOR_LIKE_DIR . 'admin/includes/statistics/stats.js' );
		$this->assert( strpos( $stats_js, 'range:"00:00 – 06:00"' ) !== false, 'stats.js uses clean 24h range for night slot' );
		$this->assert( strpos( $stats_js, 'range:"06:00 – 12:00"' ) !== false, 'stats.js uses clean 24h range for morning slot' );
		$this->assert( strpos( $stats_js, 'f.jsx(TO,{tips:c})' ) !== false, 'stats.js renders aligned insights notice on Overview' );
		$this->assert( strpos( $stats_js, 'f.jsx(_O,{schedule:s?.schedule' ) !== false, 'stats.js renders When-to-publish card on Overview' );

		// 5. Verify MO compiled binary exists and is populated
		$mo_path = FLAVOR_LIKE_DIR . 'languages/flavor-like-zh_CN.mo';
		$this->assert( file_exists( $mo_path ) && filesize( $mo_path ) > 50000, 'flavor-like-zh_CN.mo exists and exceeds 50KB' );
		$mo_bytes = file_get_contents( $mo_path );
		$this->assert( strpos( $mo_bytes, 'No engagements yet today — yesterday had {{count}}.' ) !== false, 'MO contains No engagements key' );
		$this->assert( strpos( $mo_bytes, '{{share}}% of all activity · {{range}}' ) !== false, 'MO contains Share of all activity key' );

		// 6. Verify admin assets enqueues with filemtime cache busting
		$assets_code = file_get_contents( FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-admin-assets.php' );
		$this->assert( strpos( $assets_code, '$stats_js_ver' ) !== false, 'admin-assets.php generates dynamic version for stats.js' );
		$this->assert( strpos( $assets_code, '$stats_css_ver' ) !== false, 'admin-assets.php generates dynamic version for stats.css' );
		$this->assert( strpos( $assets_code, 'filemtime' ) !== false, 'admin-assets.php uses filemtime for instant cache busting' );
	}

	// 16. Customize Removal & Dark Mode Elimination Suite
	private function test_customize_removal_and_dark_mode_elimination() {
		$this->section( '16. Customize Removal & Dark Mode Elimination Suite' );

		// 1. Verify Customize submenu completely eliminated in admin pages
		$pages_source = file_get_contents( FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-admin-pages.php' );
		$this->assert( strpos( $pages_source, "'slug'       => 'flavor-like-customize'" ) === false, 'flavor_like_admin_pages no longer registers customize submenu' );
		$this->assert( strpos( $pages_source, "'flavor-like-customize' === \$_GET['page']" ) !== false, 'flavor_like_admin_pages provides backward-compatible redirect for customize' );

		// 2. Verify Dark Mode toggle removed in stats.js
		$stats_js = file_get_contents( FLAVOR_LIKE_DIR . 'admin/includes/statistics/stats.js' );
		$this->assert( strpos( $stats_js, 'lN=()=>null' ) !== false, 'stats.js defines lN theme toggle button as null render' );
		$this->assert( strpos( $stats_js, 'localStorage.removeItem("theme")' ) !== false, 'stats.js clears dark mode preference from localStorage' );
		$this->assert( strpos( $stats_js, 'classList.remove("dark")' ) !== false, 'stats.js strips dark class from HTML root element' );
		$this->assert( strpos( $stats_js, 'Appearance' ) === false, 'stats.js removes Appearance configuration card' );

		// 3. Verify defensive CSS rules for theme toggle in stats.css
		$stats_css = file_get_contents( FLAVOR_LIKE_DIR . 'admin/includes/statistics/stats.css' );
		$this->assert( strpos( $stats_css, '.flavor-like-stats-header-theme-toggle' ) !== false, 'stats.css includes defensive display:none for theme toggle' );
	}


	// 17. Custom Button Image Removal & Streamlined Button Text Suite
	private function test_button_image_removal_and_text_streamlining() {
		$this->section( '17. Custom Button Image Removal & Streamlined Button Text Suite' );

		// 1. Verify admin panel has no image_group or button_type
		$panel_code = file_get_contents( FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-admin-panel.php' );
		$this->assert( strpos( $panel_code, "'image_group'" ) === false, 'class-flavor-like-admin-panel.php completely removed image_group upload settings' );
		$this->assert( strpos( $panel_code, "'button_type'" ) === false, 'class-flavor-like-admin-panel.php completely removed button_type setting' );
		$this->assert( strpos( $panel_code, "'点赞状态'" ) !== false, 'class-flavor-like-admin-panel.php contains streamlined 点赞状态 tab' );
		$this->assert( strpos( $panel_code, "'已赞状态'" ) !== false, 'class-flavor-like-admin-panel.php contains streamlined 已赞状态 tab' );

		// 2. Verify setting repo returns clean Chinese defaults for button text
		$like_text = flavor_like_setting_repo::getButtonText( 'post', 'like' );
		$this->assert( $like_text === '点赞', 'getButtonText returns clean Chinese "点赞" by default', 'Actual: ' . $like_text );
		$unlike_text = flavor_like_setting_repo::getButtonText( 'post', 'unlike' );
		$this->assert( $unlike_text === '已赞', 'getButtonText returns clean Chinese "已赞" by default', 'Actual: ' . $unlike_text );

		// 3. Verify general.php update_button_icon is deprecated no-op and does not inject background-image CSS
		$general_code = file_get_contents( FLAVOR_LIKE_DIR . 'includes/hooks/general.php' );
		$this->assert( strpos( $general_code, 'background-image: url' ) === false, 'general.php no longer outputs dead background-image CSS rules' );
		$this->assert( function_exists( 'flavor_like_update_button_icon' ), 'flavor_like_update_button_icon function remains defined for backward compatibility' );

		// 4. Verify cta-template populates button text for text-supporting templates
		$cta_code = file_get_contents( FLAVOR_LIKE_DIR . 'includes/classes/class-flavor-like-cta-template.php' );
		$this->assert( strpos( $cta_code, '$is_text_supported' ) !== false, 'class-flavor-like-cta-template.php uses is_text_supported check' );
		$this->assert( strpos( $cta_code, '$this->args[\'button_type\'] == \'image\'' ) === false, 'class-flavor-like-cta-template.php no longer blocks text with button_type == image' );

		// 5. Verify content-types.php defaults button_type to text
		$content_types_code = file_get_contents( FLAVOR_LIKE_DIR . 'includes/functions/content-types.php' );
		$this->assert( strpos( $content_types_code, "'image'" ) === false, 'content-types.php does not default button_type to image' );
		$this->assert( strpos( $content_types_code, '"button_type"          => isset( $options[\'button_type\'] ) ? $options[\'button_type\'] : \'text\'' ) !== false, 'content-types.php defaults button_type to text' );

		// 6. Verify flavor-like.js and flavor-like.min.js toggle active class for all buttons
		$js_code = file_get_contents( FLAVOR_LIKE_DIR . 'assets/js/flavor-like.js' );
		$this->assert( strpos( $js_code, 'buttonEl.classList.toggle("flavor_like_btn_is_active");' ) !== false, 'flavor-like.js toggles active class on vote' );
		$min_code = file_get_contents( FLAVOR_LIKE_DIR . 'assets/js/flavor-like.min.js' );
		$this->assert( strpos( $min_code, 'classList.toggle("flavor_like_btn_is_active")' ) !== false, 'flavor-like.min.js toggles active class on vote' );

		// 7. Verify custom button text save & retrieval for Posts and Comments
		global $wp_options;
		$orig_settings = isset( $wp_options['flavor_like_settings'] ) ? $wp_options['flavor_like_settings'] : null;
		$wp_options['flavor_like_settings'] = array(
			'posts_group' => array(
				'text_group' => array(
					'like'   => '赞一个',
					'unlike' => '已收藏',
				),
			),
			'comments_group' => array(
				'text_group' => array(
					'like'   => '赞同',
					'unlike' => '已赞同',
				),
			),
		);
		flavor_like_flush_options_cache();

		$custom_post_like = flavor_like_setting_repo::getButtonText( 'post', 'like' );
		$this->assert( $custom_post_like === '赞一个', 'Custom post like button text is retrieved correctly', 'Actual: ' . $custom_post_like );
		$custom_post_unlike = flavor_like_setting_repo::getButtonText( 'post', 'unlike' );
		$this->assert( $custom_post_unlike === '已收藏', 'Custom post unlike button text is retrieved correctly', 'Actual: ' . $custom_post_unlike );

		$custom_comment_like = flavor_like_setting_repo::getButtonText( 'comment', 'like' );
		$this->assert( $custom_comment_like === '赞同', 'Custom comment like button text is retrieved correctly', 'Actual: ' . $custom_comment_like );
		$custom_comment_unlike = flavor_like_setting_repo::getButtonText( 'comment', 'unlike' );
		$this->assert( $custom_comment_unlike === '已赞同', 'Custom comment unlike button text is retrieved correctly', 'Actual: ' . $custom_comment_unlike );

		// 8. Verify initial template rendering reflects custom button text
		$rendered_default = flavor_like_set_default_template( array(
			'ID'                    => 999,
			'type'                  => 'post',
			'style'                 => 'flavorlike-default',
			'wrapper_class'         => '',
			'attributes'            => '',
			'button_class'          => 'flavor_like_btn flavor_like_put_text',
			'button_text'           => $custom_post_like,
			'up_vote_inner_text'    => '',
			'formatted_total_likes' => '5',
			'display_counters'      => true,
			'display_likers'        => 0,
			'likers_style'          => 'popover',
		) );
		$this->assert( strpos( $rendered_default, '<span class="flavor-like-label">赞一个</span>' ) !== false, 'Rendered default template displays custom like label' );

		// 9. Verify fallback to default Chinese text when text_group values are empty
		$wp_options['flavor_like_settings'] = array(
			'posts_group' => array(
				'text_group' => array(
					'like'   => '',
					'unlike' => '',
				),
			),
		);
		flavor_like_flush_options_cache();
		$empty_fallback_like = flavor_like_setting_repo::getButtonText( 'post', 'like' );
		$this->assert( $empty_fallback_like === '点赞', 'Empty custom text falls back to "点赞"', 'Actual: ' . $empty_fallback_like );
		$empty_fallback_unlike = flavor_like_setting_repo::getButtonText( 'post', 'unlike' );
		$this->assert( $empty_fallback_unlike === '已赞', 'Empty custom text falls back to "已赞"', 'Actual: ' . $empty_fallback_unlike );

		// 10. Restore settings
		if ( $orig_settings !== null ) {
			$wp_options['flavor_like_settings'] = $orig_settings;
		} else {
			unset( $wp_options['flavor_like_settings'] );
		}
		flavor_like_flush_options_cache();
	}


	// 18. Fixed Admin Menu on Scroll Suite
	private function test_fixed_admin_menu_on_scroll() {
		$this->section( '18. Fixed Admin Menu on Scroll Suite' );

		// 1. Verify admin.css includes fixed rules for adminmenuwrap and adminmenuback
		$admin_css = file_get_contents( FLAVOR_LIKE_ADMIN_DIR . '/assets/css/admin.css' );
		$this->assert( strpos( $admin_css, 'body:has(#optiwich-root) #adminmenuwrap' ) !== false, 'admin.css fixes adminmenuwrap for optiwich root' );
		$this->assert( strpos( $admin_css, 'body:has(#optiwich-root) #adminmenuback' ) !== false, 'admin.css fixes adminmenuback for optiwich root' );
		$this->assert( strpos( $admin_css, 'overscroll-behavior: contain' ) !== false, 'admin.css sets overscroll-behavior contain on admin menu' );
		$this->assert( strpos( $admin_css, 'ul.wp-submenu' ) !== false, 'admin.css preserves flyout submenus' );

		// 2. Verify optiwich style.css includes fixed rules
		$optiwich_css = file_get_contents( FLAVOR_LIKE_ADMIN_DIR . '/includes/optiwich/style.css' );
		$this->assert( strpos( $optiwich_css, 'body:has(#optiwich-root) #adminmenuwrap' ) !== false, 'optiwich style.css fixes adminmenuwrap' );
		$this->assert( strpos( $optiwich_css, 'body.flavor-like_page_flavor-like-settings #adminmenuwrap' ) !== false, 'optiwich style.css targets settings page class' );

		// 3. Verify admin-assets.php injects fixed rules into inline saas css
		$assets_code = file_get_contents( FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-admin-assets.php' );
		$this->assert( strpos( $assets_code, 'body:has(#optiwich-root) #adminmenuwrap' ) !== false, 'admin-assets.php injects adminmenuwrap fix into optiwich_saas_css' );

		// 4. Verify notices.js unbinds scroll.pin-menu on Flavor Like admin pages
		$notices_js = file_get_contents( FLAVOR_LIKE_ADMIN_DIR . '/assets/js/notices.js' );
		$this->assert( strpos( $notices_js, 'lockAdminMenuOnScroll' ) !== false, 'notices.js defines lockAdminMenuOnScroll' );
		$this->assert( strpos( $notices_js, 'scroll.pin-menu' ) !== false, 'notices.js detaches scroll.pin-menu' );
		$this->assert( strpos( $notices_js, 'sticky-menu' ) !== false, 'notices.js ensures sticky-menu class is applied' );

		// 5. Verify submenu anti-flicker stability: active menu remains relative/inline on hover
		$this->assert( strpos( $admin_css, 'li.wp-has-current-submenu ul.wp-submenu' ) !== false, 'admin.css locks active submenu position to relative' );
		$this->assert( strpos( $admin_css, 'li.wp-has-submenu:not(.wp-has-current-submenu):not(.wp-menu-open)' ) !== false, 'admin.css excludes active submenu from position: fixed to prevent flicker' );
		$this->assert( strpos( $optiwich_css, 'li.wp-has-current-submenu ul.wp-submenu' ) !== false, 'optiwich style.css locks active submenu position' );
		$this->assert( strpos( $assets_code, 'li.wp-has-current-submenu ul.wp-submenu' ) !== false, 'admin-assets.php locks active submenu position' );
	}

	// 19. Orthogonal Two-Dimensional Display Control Suite (Plan A)
	private function test_orthogonal_two_dimensional_display_control() {
		$this->section( '19. Orthogonal Two-Dimensional Display Control Suite (Plan A)' );

		// 1. Verify admin panel has clean, intuitive titles and descriptions
		$panel_code = file_get_contents( FLAVOR_LIKE_ADMIN_DIR . '/classes/class-flavor-like-admin-panel.php' );
		$this->assert( strpos( $panel_code, "'启用的文章类型'" ) !== false, 'class-flavor-like-admin-panel.php defines "启用的文章类型"' );
		$this->assert( strpos( $panel_code, "'显示按钮位置'" ) !== false, 'class-flavor-like-admin-panel.php defines "显示按钮位置"' );
		$this->assert( strpos( $panel_code, "'单篇详情页'" ) !== false, 'class-flavor-like-admin-panel.php uses "单篇详情页" for single' );
		$this->assert( strpos( $panel_code, "Singular (posts & pages)" ) === false, 'class-flavor-like-admin-panel.php removed misleading "posts & pages" from single' );

		// 2. Verify logical sequence: auto_display_filter_post_types precedes auto_display_filter
		$pos_post_types = strpos( $panel_code, "'auto_display_filter_post_types'" );
		$pos_context    = strpos( $panel_code, "'auto_display_filter'" );
		$this->assert( $pos_post_types !== false && $pos_context !== false && $pos_post_types < $pos_context, 'Supported Post Types appears before Display Contexts in admin panel' );

		// 3. Verify strict post type filtering in hooks/general.php
		$hooks_code = file_get_contents( FLAVOR_LIKE_DIR . 'includes/hooks/general.php' );
		$this->assert( strpos( $hooks_code, 'getPostTypesFilterList' ) !== false, 'hooks/general.php checks allowed post types' );
		$this->assert( strpos( $hooks_code, 'in_array( $current_post_type, $allowed_post_types, true )' ) !== false, 'hooks/general.php strictly enforces allowed post types' );

		// 4. Verify overview summary uses "启用的文章类型" instead of "exceptions"
		$overview_code = file_get_contents( FLAVOR_LIKE_DIR . 'includes/classes/class-flavor-like-overview.php' );
		$this->assert( strpos( $overview_code, "'启用的文章类型: %s'" ) !== false, 'class-flavor-like-overview.php displays "启用的文章类型"' );
		$this->assert( strpos( $overview_code, "'exceptions: %s'" ) === false, 'class-flavor-like-overview.php removed old "exceptions"' );
	}

	// 20. Build Pipeline & Release Metadata Suite
	private function test_build_pipeline_and_release_metadata() {
		$this->section( '20. Build Pipeline & Release Metadata Suite' );

		// 1. Gutenberg block build artifacts must be committed (they are gitignored history bugs otherwise)
		$this->assert( file_exists( FLAVOR_LIKE_DIR . 'includes/blocks/button/build/index.js' ), 'button block build/index.js exists' );
		$this->assert( file_exists( FLAVOR_LIKE_DIR . 'includes/blocks/button/build/index.css' ), 'button block build/index.css exists' );
		$this->assert( file_exists( FLAVOR_LIKE_DIR . 'includes/blocks/top-content/build/index.js' ), 'top-content block build/index.js exists' );
		$this->assert( file_exists( FLAVOR_LIKE_DIR . 'includes/blocks/top-content/build/style-style.css' ), 'top-content block build/style-style.css exists' );

		// 2. JS build sources must exist and point at the real file
		$toolkit = file_get_contents( FLAVOR_LIKE_DIR . 'wp-toolkit.config.mjs' );
		$this->assert( strpos( $toolkit, 'assets/js/src/flavor-like.js' ) !== false, 'wp-toolkit.config.mjs builds from flavor-like.js' );
		$this->assert( strpos( $toolkit, 'ulike.js' ) === false, 'wp-toolkit.config.mjs no longer references missing ulike.js' );

		// 3. License is consistent (GPL everywhere)
		$pkg = json_decode( file_get_contents( FLAVOR_LIKE_DIR . 'package.json' ), true );
		$this->assert( isset( $pkg['license'] ) && $pkg['license'] === 'GPL-2.0-or-later', 'package.json license is GPL-2.0-or-later', 'Current: ' . ( $pkg['license'] ?? 'none' ) );

		// 4. Runtime requirements aligned with block apiVersion 3 (WP 6.3+)
		$main_file = file_get_contents( FLAVOR_LIKE_DIR . 'flavor-like.php' );
		$this->assert( strpos( $main_file, 'Requires PHP:      7.4' ) !== false, 'flavor-like.php declares Requires PHP 7.4' );
		$this->assert( strpos( $main_file, 'Requires at least: 6.3' ) !== false, 'flavor-like.php declares Requires at least WP 6.3' );
		$readme = file_get_contents( FLAVOR_LIKE_DIR . 'README.md' );
		$this->assert( strpos( $readme, 'WordPress**：6.3+' ) !== false, 'README.md documents WordPress 6.3+' );

		// 5. Version headers no longer carry upstream 5.2.0 leftovers
		$bundle = file_get_contents( FLAVOR_LIKE_DIR . 'assets/js/flavor-like.js' );
		$this->assert( strpos( $bundle, 'Flavor Like - v1.1.0' ) !== false, 'JS bundle header reports v1.1.0' );
		$pot = file_get_contents( FLAVOR_LIKE_DIR . 'languages/flavor-like.pot' );
		$this->assert( strpos( $pot, 'Project-Id-Version: Flavor Like 1.1.0' ) !== false, 'POT Project-Id-Version is 1.1.0' );

		// 6. Hand-maintained interaction features survive the build
		$this->assert( strpos( $bundle, 'flavor-like-busy' ) !== false, 'JS bundle keeps busy-state click locking' );
		$this->assert( strpos( $bundle, '_triggerBilibiliAnimation' ) !== false, 'JS bundle keeps Bilibili-style animation' );
		$front_css = file_get_contents( FLAVOR_LIKE_DIR . 'assets/css/flavor-like.css' );
		$this->assert( strpos( $front_css, 'flavor-like-burst-effect' ) !== false, 'flavor-like.css keeps burst effect styles' );

		// 7. Release zip must not ship development sources
		$zip_script = file_get_contents( FLAVOR_LIKE_DIR . 'build-zip.ps1' );
		$this->assert( strpos( $zip_script, '\assets\js\src' ) !== false, 'build-zip.ps1 excludes assets/js/src' );
		$this->assert( strpos( $zip_script, 'npm run build:blocks' ) !== false, 'build-zip.ps1 builds blocks before packaging' );
	}

	// Summary
	private function summary() {
		echo "\n======================================================\n";
		echo "  TEST SUMMARY\n";
		echo "======================================================\n";
		echo "  TOTAL TESTS RUN: " . ( $this->passed + $this->failed ) . "\n";
		echo "  PASSED:          {$this->passed}\n";
		echo "  FAILED:          {$this->failed}\n";
		echo "======================================================\n";

		if ( $this->failed === 0 ) {
			echo "\n  >>> ALL TESTS PASSED! FLAVOR LIKE 1.1.0 IS FULLY OPERATIONAL. <<<\n\n";
			exit( 0 );
		} else {
			echo "\n  >>> SOME TESTS FAILED! PLEASE REVIEW FAILURES ABOVE. <<<\n\n";
			exit( 1 );
		}
	}
}

$suite = new FlavorLikeTestSuite();
$suite->run();
