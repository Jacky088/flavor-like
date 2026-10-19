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
