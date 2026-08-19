<?php
/**
 * Back-end AJAX Functionalities
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/*******************************************************
  Start AJAX From Here
*******************************************************/

/**
 * AJAX handler to store the state of dismissible notices.
 *
 * @return			Void
 */
function flavor_like_ajax_notice_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Permission denied.', 'flavor-like' ) );
	}

	if ( ! isset( $_POST['id'] ) || ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), '_notice_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Token Error.', 'flavor-like' ) );
	}

	$expiration = isset( $_POST['expiration'] ) ? absint( $_POST['expiration'] ) : YEAR_IN_SECONDS;

	flavor_like_set_transient( 'flavor-like-notice-' . sanitize_text_field( wp_unslash( $_POST['id'] ) ), 1, $expiration );
	wp_send_json_success( esc_html__( 'It\'s OK.', 'flavor-like' ) );
}
add_action( 'wp_ajax_flavor_like_dismissed_notice', 'flavor_like_ajax_notice_handler' );


/**
 * Dashboard api
 *
 * @return void
 */
function flavor_like_stats_api(){
	if( ! current_user_can( flavor_like_get_user_access_capability('stats') ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

    $stats = flavor_like_stats::get_instance()->get_all_data();
    return wp_send_json($stats);
}
add_action('wp_ajax_flavor_like_stats_api','flavor_like_stats_api');

/**
 * Save per-user stats dashboard preferences.
 *
 * @return void
 */
function flavor_like_stats_save_user_prefs() {
	$nonce_valid = flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG );

	if ( ! current_user_can( flavor_like_get_user_access_capability( 'stats' ) ) || ! $nonce_valid ) {
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	$raw  = isset( $_POST['prefs'] ) ? wp_unslash( $_POST['prefs'] ) : '';
	$data = json_decode( $raw, true );

	if ( ! is_array( $data ) ) {
		wp_send_json_error( esc_html__( 'Invalid preferences payload.', 'flavor-like' ) );
	}

	if ( ! class_exists( 'Flavor_Like_Stats_User_Prefs' ) ) {
		wp_send_json_error( esc_html__( 'Preferences storage is unavailable.', 'flavor-like' ) );
	}

	Flavor_Like_Stats_User_Prefs::save_prefs( $data );

	wp_send_json_success( Flavor_Like_Stats_User_Prefs::get_prefs() );
}
add_action( 'wp_ajax_flavor_like_stats_save_user_prefs', 'flavor_like_stats_save_user_prefs' );

/**
 * Overview dashboard API (free).
 *
 * @return void
 */
function flavor_like_overview_api() {
	if ( ! current_user_can( flavor_like_get_user_access_capability( 'stats' ) ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ) {
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	$stats = flavor_like_stats::get_instance()->get_overview_api_data();
	return wp_send_json( $stats );
}
add_action( 'wp_ajax_flavor_like_overview_api', 'flavor_like_overview_api' );

/**
 * Engagement data for a single content type (free).
 *
 * @return void
 */
function flavor_like_engagement_api() {
	if ( ! current_user_can( flavor_like_get_user_access_capability( 'stats' ) ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ) {
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
	$data = flavor_like_stats::get_instance()->get_engagement_api_data( $type );

	if ( null === $data ) {
		wp_send_json_error( esc_html__( 'Invalid content type.', 'flavor-like' ) );
	}

	return wp_send_json( $data );
}
add_action( 'wp_ajax_flavor_like_engagement_api', 'flavor_like_engagement_api' );

/**
 * Top content for a single type (free 鈥?no filters).
 *
 * @return void
 */
function flavor_like_tops_api() {
	if ( ! current_user_can( flavor_like_get_user_access_capability( 'stats' ) ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ) {
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	$type  = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
	$limit = isset( $_GET['limit'] ) ? absint( $_GET['limit'] ) : 8;
	$data  = flavor_like_stats::get_instance()->get_tops_api_data( $type, $limit );

	if ( null === $data ) {
		wp_send_json_error( esc_html__( 'Invalid content type.', 'flavor-like' ) );
	}

	return wp_send_json( $data );
}
add_action( 'wp_ajax_flavor_like_tops_api', 'flavor_like_tops_api' );

/**
 * Engagement history api
 *
 * @return void
 */
function flavor_like_history_api(){
	if( ! current_user_can( flavor_like_get_user_access_capability('stats') ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG )  ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	$type    = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'post';
	$page    = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
	$perPage = isset( $_GET['perPage'] ) ? absint( $_GET['perPage'] ) : 15;

	$settings = flavor_like_setting_type::get_instance( $type );
	$instance = new flavor_like_logs( $settings->getLogIdentifier(), $page, $perPage  );
	$output   = $instance->get_rows();

	wp_send_json( $output );
}
add_action('wp_ajax_flavor_like_history_api','flavor_like_history_api');

/**
 * Engagement history api
 *
 * @return void
 */
function flavor_like_delete_history_api(){
	if( ! current_user_can( 'manage_options' ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	$item_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$type    = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';

	if( empty( $item_id ) || empty( $type ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	$settings = flavor_like_setting_type::get_instance( $type );
	$instance = new flavor_like_logs( $settings->getLogIdentifier()  );

	if( ! $instance->delete_row( $item_id ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	wp_send_json_success();
}
add_action('wp_ajax_flavor_like_delete_history_api','flavor_like_delete_history_api');

/**
 * Localization api
 *
 * @return void
 */
function flavor_like_localization_api(){
	if( ! current_user_can( flavor_like_get_user_access_capability('stats') ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	global $current_user;

	wp_send_json( array(
		// Template variables (not shown in UI)
		'{{site_name}}'    => get_bloginfo( 'name' ),
		'{{language}}'     => substr( get_bloginfo( 'language' ), 0, 2 ),
		'{{display_name}}' => esc_attr( $current_user->display_name ),

		// Navigation & shell
		'Overview'             => esc_html__( 'Overview', 'flavor-like' ),
		'Reports'              => esc_html__( 'Reports', 'flavor-like' ),
		'Engagement'           => esc_html__( 'Engagement', 'flavor-like' ),
		'Intelligence'         => esc_html__( 'Intelligence', 'flavor-like' ),
		'Content intelligence' => esc_html__( 'Content intelligence', 'flavor-like' ),
		'Audience'             => esc_html__( 'Audience', 'flavor-like' ),
		'Countries'         => esc_html__( 'Countries', 'flavor-like' ),
		'Technology'           => esc_html__( 'Technology', 'flavor-like' ),
		'Devices'            => esc_html__( 'Devices', 'flavor-like' ),
		'Logs'                 => esc_html__( 'Logs', 'flavor-like' ),
		'Insights'             => esc_html__( 'Insights', 'flavor-like' ),
		'View'                 => esc_html__( 'View', 'flavor-like' ),
		'Filters'              => esc_html__( 'Filters', 'flavor-like' ),
		'Clear all'            => esc_html__( 'Clear all', 'flavor-like' ),
		'Apply'                => esc_html__( 'Apply', 'flavor-like' ),
		'Cancel'               => esc_html__( 'Cancel', 'flavor-like' ),
		'Clear'                => esc_html__( 'Clear', 'flavor-like' ),
		'Loading...'             => esc_html__( 'Loading...', 'flavor-like' ),

		// Page descriptions
		'Actionable engagement intelligence for your site' => esc_html__( 'Actionable engagement intelligence for your site', 'flavor-like' ),
		'Your engagement dashboard at a glance'            => esc_html__( 'Your engagement dashboard at a glance', 'flavor-like' ),
		'Publishing schedule, categories, and commerce insights' => esc_html__( 'Publishing schedule, categories, and commerce insights', 'flavor-like' ),
		'Publishing schedule and category performance' => esc_html__( 'Publishing schedule and category performance', 'flavor-like' ),
		'Connect product engagement with sales to spot opportunities and plan campaigns' => esc_html__( 'Connect product engagement with sales to spot opportunities and plan campaigns', 'flavor-like' ),
		'See where your audience engages from'           => esc_html__( 'See where your audience engages from', 'flavor-like' ),
		'Audience by location'                           => esc_html__( 'Audience by location', 'flavor-like' ),
		'Device, OS and browser breakdown by unique voters' => esc_html__( 'Device, OS and browser breakdown by unique voters', 'flavor-like' ),
		'Voters by device & browser'                     => esc_html__( 'Voters by device & browser', 'flavor-like' ),
		'Trends and totals for {{type}}'                 => esc_html__( 'Trends and totals for {{type}}', 'flavor-like' ),
		'Top {{type}} your audience engages with most'   => esc_html__( 'Top {{type}} your audience engages with most', 'flavor-like' ),
		'Browse and manage vote history'                 => esc_html__( 'Browse and manage vote history', 'flavor-like' ),
		'Browse and manage vote history for {{type}}'    => esc_html__( 'Browse and manage vote history for {{type}}', 'flavor-like' ),

		// KPI metrics
		'Engagement This Week'        => esc_html__( 'Engagement This Week', 'flavor-like' ),
		'Monthly Engagement Overview' => esc_html__( 'Monthly Engagement Overview', 'flavor-like' ),
		'Yearly Engagement Trends'    => esc_html__( 'Yearly Engagement Trends', 'flavor-like' ),
		'Overall Performance'         => esc_html__( 'Overall Performance', 'flavor-like' ),
		'All time'                    => esc_html__( 'All time', 'flavor-like' ),
		'This Week'                   => esc_html__( 'This Week', 'flavor-like' ),
		'This Month'                  => esc_html__( 'This Month', 'flavor-like' ),
		'This Year'                   => esc_html__( 'This Year', 'flavor-like' ),
		'Today'                       => esc_html__( 'Today', 'flavor-like' ),
		'Yesterday'                   => esc_html__( 'Yesterday', 'flavor-like' ),
		'vs last week'                => esc_html__( 'vs last week', 'flavor-like' ),
		'vs last month'               => esc_html__( 'vs last month', 'flavor-like' ),
		'vs last year'                => esc_html__( 'vs last year', 'flavor-like' ),
		'Total Interactions To Date'  => esc_html__( 'Total Interactions To Date', 'flavor-like' ),
		'Engagement Summary'          => esc_html__( 'Engagement Summary', 'flavor-like' ),
		'Totals at a glance'          => esc_html__( 'Totals at a glance', 'flavor-like' ),
		'{{total}} total 路 {{today}} today' => esc_html__( '{{total}} total 路 {{today}} today', 'flavor-like' ),

		// Engagement reports
		'Trends'              => esc_html__( 'Trends', 'flavor-like' ),
		'Top content'         => esc_html__( 'Top content', 'flavor-like' ),
		'positive'            => esc_html__( 'positive', 'flavor-like' ),
		'Vote history 路 {{type}}' => esc_html__( 'Vote history 路 {{type}}', 'flavor-like' ),
		'Only {{ratio}}% positive this week 鈥?check Top content for items getting dislikes.' => esc_html__( 'Only {{ratio}}% positive this week 鈥?check Top content for items getting dislikes.', 'flavor-like' ),
		'Use Content intelligence to find the best publish times for your audience.' => esc_html__( 'Use Content intelligence to find the best publish times for your audience.', 'flavor-like' ),
		'{{likes}} likes this week 鈥?use Top content to find what resonates.' => esc_html__( '{{likes}} likes this week 鈥?use Top content to find what resonates.', 'flavor-like' ),
		'{{likes}} likes and {{dislikes}} dislikes 鈥?use Top content to find what resonates.' => esc_html__( '{{likes}} likes and {{dislikes}} dislikes 鈥?use Top content to find what resonates.', 'flavor-like' ),

		// Content types (keys match capitalizeFirstLetter( type ) in Logs)
		'Posts'      => esc_html__( 'Posts', 'flavor-like' ),
		'Comments'   => esc_html__( 'Comments', 'flavor-like' ),
		'Activities' => esc_html__( 'Activities', 'flavor-like' ),
		'Topics'     => esc_html__( 'Topics', 'flavor-like' ),
		'Engagers'   => esc_html__( 'Engagers', 'flavor-like' ),
		'Top members' => esc_html__( 'Top members', 'flavor-like' ),
		'Most active engagers recently' => esc_html__( 'Most active engagers recently', 'flavor-like' ),
		'Most active members recently'  => esc_html__( 'Most active members recently', 'flavor-like' ),
		'Most active visitors recently' => esc_html__( 'Most active visitors recently', 'flavor-like' ),
		'{{count}} actions this week 鈥?reward top engagers to build loyalty.' => esc_html__( '{{count}} actions this week 鈥?reward top engagers to build loyalty.', 'flavor-like' ),
		'{{count}} actions this week 鈥?reward top members to build loyalty.' => esc_html__( '{{count}} actions this week 鈥?reward top members to build loyalty.', 'flavor-like' ),
		'{{count}} actions this week 鈥?see which visitors engage most often.' => esc_html__( '{{count}} actions this week 鈥?see which visitors engage most often.', 'flavor-like' ),

		// Filters & status
		'Like'           => esc_html__( 'Like', 'flavor-like' ),
		'Unlike'         => esc_html__( 'Unlike', 'flavor-like' ),
		'Dislike'        => esc_html__( 'Dislike', 'flavor-like' ),
		'Undislike'      => esc_html__( 'Undislike', 'flavor-like' ),
		'Status Filter'  => esc_html__( 'Status Filter', 'flavor-like' ),
		'Status'         => esc_html__( 'Status', 'flavor-like' ),
		'Date Range'     => esc_html__( 'Date Range', 'flavor-like' ),
		'Start date'     => esc_html__( 'Start date', 'flavor-like' ),
		'End date'       => esc_html__( 'End date', 'flavor-like' ),
		'Select...'        => esc_html__( 'Select...', 'flavor-like' ),
		'Content type'   => esc_html__( 'Content type', 'flavor-like' ),
		'Content Types'  => esc_html__( 'Content Types', 'flavor-like' ),
		'View By'        => esc_html__( 'View By', 'flavor-like' ),
		'OS'             => esc_html__( 'OS', 'flavor-like' ),
		'Browser'        => esc_html__( 'Browser', 'flavor-like' ),
		'Post type'      => esc_html__( 'Post type', 'flavor-like' ),
		'Taxonomy'       => esc_html__( 'Taxonomy', 'flavor-like' ),
		'Sort by'        => esc_html__( 'Sort by', 'flavor-like' ),
		'Highest first'  => esc_html__( 'Highest first', 'flavor-like' ),
		'Lowest first'   => esc_html__( 'Lowest first', 'flavor-like' ),
		'Search'         => esc_html__( 'Search', 'flavor-like' ),
		'{{count}} selected' => esc_html__( '{{count}} selected', 'flavor-like' ),

		// Date presets
		'Custom'                 => esc_html__( 'Custom', 'flavor-like' ),
		'This week'              => esc_html__( 'This week', 'flavor-like' ),
		'Last week'              => esc_html__( 'Last week', 'flavor-like' ),
		'Last {{count}} days'    => esc_html__( 'Last {{count}} days', 'flavor-like' ),
		'Last {{count}} months'  => esc_html__( 'Last {{count}} months', 'flavor-like' ),
		'This month'             => esc_html__( 'This month', 'flavor-like' ),
		'Last month'             => esc_html__( 'Last month', 'flavor-like' ),
		'Quarter to date'        => esc_html__( 'Quarter to date', 'flavor-like' ),
		'This year'              => esc_html__( 'This year', 'flavor-like' ),
		'Last calendar year'     => esc_html__( 'Last calendar year', 'flavor-like' ),

		// Tables & lists
		'Content'         => esc_html__( 'Content', 'flavor-like' ),
		'Performance'     => esc_html__( 'Performance', 'flavor-like' ),
		'Published'       => esc_html__( 'Published', 'flavor-like' ),
		'Views'           => esc_html__( 'Views', 'flavor-like' ),
		'Dislikes'        => esc_html__( 'Dislikes', 'flavor-like' ),
		'Date'            => esc_html__( 'Date', 'flavor-like' ),
		'User'            => esc_html__( 'User', 'flavor-like' ),
		'IP'              => esc_html__( 'IP', 'flavor-like' ),
		'Comment Author'  => esc_html__( 'Comment Author', 'flavor-like' ),
		'Comment Content' => esc_html__( 'Comment Content', 'flavor-like' ),
		'Activity Title'  => esc_html__( 'Activity Title', 'flavor-like' ),
		'Topic Title'     => esc_html__( 'Topic Title', 'flavor-like' ),
		'Post Title'      => esc_html__( 'Post Title', 'flavor-like' ),
		'Categories'      => esc_html__( 'Categories', 'flavor-like' ),
		'Category'        => esc_html__( 'Category', 'flavor-like' ),
		'Actions'         => esc_html__( 'Actions', 'flavor-like' ),
		'Name'            => esc_html__( 'Name', 'flavor-like' ),
		'Share'           => esc_html__( 'Share', 'flavor-like' ),
		'Growth'          => esc_html__( 'Growth', 'flavor-like' ),
		'Country'         => esc_html__( 'Country', 'flavor-like' ),
		'Device'          => esc_html__( 'Device', 'flavor-like' ),
		'Voters'          => esc_html__( 'Voters', 'flavor-like' ),
		'User(s)'         => esc_html__( 'User(s)', 'flavor-like' ),
		'Engaged Users'   => esc_html__( 'Engaged Users', 'flavor-like' ),
		'Unique users'    => esc_html__( 'Unique users', 'flavor-like' ),
		'Selected period' => esc_html__( 'Selected period', 'flavor-like' ),
		'Untitled'        => esc_html__( 'Untitled', 'flavor-like' ),
		'Unknown User'    => esc_html__( 'Unknown User', 'flavor-like' ),

		// Pagination & logs
		'Showing {{from}} to {{to}} of {{total}} results' => esc_html__( 'Showing {{from}} to {{to}} of {{total}} results', 'flavor-like' ),
		'per page'        => esc_html__( 'per page', 'flavor-like' ),
		'Total records'   => esc_html__( 'Total records', 'flavor-like' ),
		'Delete Selected' => esc_html__( 'Delete Selected', 'flavor-like' ),
		'Download CSV'    => esc_html__( 'Download CSV', 'flavor-like' ),
		'Failed to delete the log entry.' => esc_html__( 'Failed to delete the log entry.', 'flavor-like' ),
		'An error occurred while deleting the log entry.' => esc_html__( 'An error occurred while deleting the log entry.', 'flavor-like' ),
		'No logs found for this category' => esc_html__( 'No logs found for this category', 'flavor-like' ),

		// Empty & error states
		'No data for this period' => esc_html__( 'No data for this period', 'flavor-like' ),
		'Try changing your filters or search.' => esc_html__( 'Try changing your filters or search.', 'flavor-like' ),
		'Unable to load data. Please try again.' => esc_html__( 'Unable to load data. Please try again.', 'flavor-like' ),
		'Something went wrong'    => esc_html__( 'Something went wrong', 'flavor-like' ),
		'Unable to load data. Refresh the page or contact support.' => esc_html__( 'Unable to load data. Refresh the page or contact support.', 'flavor-like' ),
		'Page Not Found'          => esc_html__( 'Page Not Found', 'flavor-like' ),
		'This page does not exist or was moved.' => esc_html__( 'This page does not exist or was moved.', 'flavor-like' ),
		'No Data Available'       => esc_html__( 'No Data Available', 'flavor-like' ),
		'No data yet. Records will appear here once engagement starts.' => esc_html__( 'No data yet. Records will appear here once engagement starts.', 'flavor-like' ),
		'Go to Home'              => esc_html__( 'Go to Home', 'flavor-like' ),
		'Refresh Page'            => esc_html__( 'Refresh Page', 'flavor-like' ),

		// Geography
		'User Engagement by Country' => esc_html__( 'User Engagement by Country', 'flavor-like' ),
		'Activity by country'        => esc_html__( 'Activity by country', 'flavor-like' ),
		'Top countries'              => esc_html__( 'Top countries', 'flavor-like' ),
		'No country data yet'        => esc_html__( 'No country data yet', 'flavor-like' ),
		'{{country}} is your top market with {{share}}% of engaged voters.' => esc_html__( '{{country}} is your top market with {{share}}% of engaged voters.', 'flavor-like' ),

		// Intelligence & performance
		'Performance snapshot' => esc_html__( 'Performance snapshot', 'flavor-like' ),
		'Engagement rate'      => esc_html__( 'Engagement rate', 'flavor-like' ),
		'Positive sentiment'   => esc_html__( 'Positive sentiment', 'flavor-like' ),
		'Total likes'          => esc_html__( 'Total likes', 'flavor-like' ),
		'Engagement trend'     => esc_html__( 'Engagement trend', 'flavor-like' ),
		'Button impressions'   => esc_html__( 'Button impressions', 'flavor-like' ),
		'Total views'          => esc_html__( 'Total views', 'flavor-like' ),
		'Reach'                => esc_html__( 'Reach', 'flavor-like' ),
		'Daily activity'       => esc_html__( 'Daily activity', 'flavor-like' ),
		'Likes this month'     => esc_html__( 'Likes this month', 'flavor-like' ),
		'Interactions this month' => esc_html__( 'Interactions this month', 'flavor-like' ),
		'Likes per view'       => esc_html__( 'Likes per view', 'flavor-like' ),
		'Likes + dislikes per view' => esc_html__( 'Likes + dislikes per view', 'flavor-like' ),
		'Like vs dislike ratio' => esc_html__( 'Like vs dislike ratio', 'flavor-like' ),
		'Like-only template'   => esc_html__( 'Like-only template', 'flavor-like' ),
		'{{likes}} likes 路 {{dislikes}} dislikes' => esc_html__( '{{likes}} likes 路 {{dislikes}} dislikes', 'flavor-like' ),
		'All likes in this period.' => esc_html__( 'All likes in this period.', 'flavor-like' ),
		'Daily counts for this period.' => esc_html__( 'Daily counts for this period.', 'flavor-like' ),
		'Enable it in General settings to see engagement rate and button impressions.' => esc_html__( 'Enable it in General settings to see engagement rate and button impressions.', 'flavor-like' ),
		'Enable view tracking in General settings if you have not already to see engagement rate and button impressions.' => esc_html__( 'Enable view tracking in General settings if you have not already to see engagement rate and button impressions.', 'flavor-like' ),
		'Engagement as a share of page views.' => esc_html__( 'Engagement as a share of page views.', 'flavor-like' ),
		'Impressions will appear as visitors view pages with your like button.' => esc_html__( 'Impressions will appear as visitors view pages with your like button.', 'flavor-like' ),
		'How page views convert to engagement.' => esc_html__( 'How page views convert to engagement.', 'flavor-like' ),
		'No button impressions in this period yet.' => esc_html__( 'No button impressions in this period yet.', 'flavor-like' ),
		'Likes as a share of all reactions.' => esc_html__( 'Likes as a share of all reactions.', 'flavor-like' ),
		'{{likes}} of {{total}} reactions' => esc_html__( '{{likes}} of {{total}} reactions', 'flavor-like' ),
		'Change compared to the previous period.' => esc_html__( 'Change compared to the previous period.', 'flavor-like' ),
		'{{rate}}% engagement rate' => esc_html__( '{{rate}}% engagement rate', 'flavor-like' ),
		'How often your like button was shown. Compare with engagements.' => esc_html__( 'How often your like button was shown. Compare with engagements.', 'flavor-like' ),
		'Reaction and voter metrics.' => esc_html__( 'Reaction and voter metrics.', 'flavor-like' ),
		'View tracking is off.' => esc_html__( 'View tracking is off.', 'flavor-like' ),

		// Content intelligence report
		'When to publish'     => esc_html__( 'When to publish', 'flavor-like' ),
		'When your audience is most likely to react.' => esc_html__( 'When your audience is most likely to react.', 'flavor-like' ),
		'Sweet spot'          => esc_html__( 'Sweet spot', 'flavor-like' ),
		'Peak'                => esc_html__( 'Peak', 'flavor-like' ),
		'Hourly pattern'      => esc_html__( 'Hourly pattern', 'flavor-like' ),
		'Full report'         => esc_html__( 'Full report', 'flavor-like' ),
		'{{day}} 路 {{time}}'  => esc_html__( '{{day}} 路 {{time}}', 'flavor-like' ),
		'{{share}}% of weekly activity' => esc_html__( '{{share}}% of weekly activity', 'flavor-like' ),
		'{{share}}% of all activity 路 {{range}}' => esc_html__( '{{share}}% of all activity 路 {{range}}', 'flavor-like' ),
		'{{count}} engagements in this slot' => esc_html__( '{{count}} engagements in this slot', 'flavor-like' ),
		'Activity heatmap'    => esc_html__( 'Activity heatmap', 'flavor-like' ),
		'Engagement by day and hour' => esc_html__( 'Engagement by day and hour', 'flavor-like' ),
		'When your audience engages' => esc_html__( 'When your audience engages', 'flavor-like' ),
		'Time windows'        => esc_html__( 'Time windows', 'flavor-like' ),
		'Best day(s)'         => esc_html__( 'Best day(s)', 'flavor-like' ),
		'Share of weekly activity' => esc_html__( 'Share of weekly activity', 'flavor-like' ),
		'Best hour'           => esc_html__( 'Best hour', 'flavor-like' ),
		'Best hours to post'  => esc_html__( 'Best hours to post', 'flavor-like' ),
		'Category performance' => esc_html__( 'Category performance', 'flavor-like' ),
		'Engagements'         => esc_html__( 'Engagements', 'flavor-like' ),
		'Hour'                => esc_html__( 'Hour', 'flavor-like' ),
		'Top categories'      => esc_html__( 'Top categories', 'flavor-like' ),
		'Shop spotlight'      => esc_html__( 'Shop spotlight', 'flavor-like' ),
		'Less'                => esc_html__( 'Less', 'flavor-like' ),
		'More'                => esc_html__( 'More', 'flavor-like' ),
		'Publish on {{day}} around {{time}} for maximum engagement.' => esc_html__( 'Publish on {{day}} around {{time}} for maximum engagement.', 'flavor-like' ),
		'Your audience is most active in the {{window}} window ({{range}}).' => esc_html__( 'Your audience is most active in the {{window}} window ({{range}}).', 'flavor-like' ),
		'{{share}}% of engagers use mobile 鈥?optimize for small screens.' => esc_html__( '{{share}}% of engagers use mobile 鈥?optimize for small screens.', 'flavor-like' ),
		'{{share}}% engage from desktop 鈥?long-form content may perform better.' => esc_html__( '{{share}}% engage from desktop 鈥?long-form content may perform better.', 'flavor-like' ),

		// WooCommerce intelligence report
		'WooCommerce' => esc_html__( 'WooCommerce', 'flavor-like' ),
		'WooCommerce report unavailable' => esc_html__( 'WooCommerce report unavailable', 'flavor-like' ),
		'Enable likes on products or reviews, or wait for store orders, to unlock commerce intelligence.' => esc_html__( 'Enable likes on products or reviews, or wait for store orders, to unlock commerce intelligence.', 'flavor-like' ),
		'Store snapshot' => esc_html__( 'Store snapshot', 'flavor-like' ),
		'Engagement correlated with sales' => esc_html__( 'Engagement correlated with sales', 'flavor-like' ),
		'Unique view: see whether shopper reactions align with orders and revenue.' => esc_html__( 'Unique view: see whether shopper reactions align with orders and revenue.', 'flavor-like' ),
		'Product likes' => esc_html__( 'Product likes', 'flavor-like' ),
		'Likes and dislikes on WooCommerce products.' => esc_html__( 'Likes and dislikes on WooCommerce products.', 'flavor-like' ),
		'Product engagement' => esc_html__( 'Product engagement', 'flavor-like' ),
		'Reactions on verified customer product reviews.' => esc_html__( 'Reactions on verified customer product reviews.', 'flavor-like' ),
		'Review engagement' => esc_html__( 'Review engagement', 'flavor-like' ),
		'Completed and processing orders.' => esc_html__( 'Completed and processing orders.', 'flavor-like' ),
		'Orders' => esc_html__( 'Orders', 'flavor-like' ),
		'Net product revenue in this period.' => esc_html__( 'Net product revenue in this period.', 'flavor-like' ),
		'Revenue' => esc_html__( 'Revenue', 'flavor-like' ),
		'Product units sold in this period.' => esc_html__( 'Product units sold in this period.', 'flavor-like' ),
		'Units sold' => esc_html__( 'Units sold', 'flavor-like' ),
		'Average revenue per order in this period.' => esc_html__( 'Average revenue per order in this period.', 'flavor-like' ),
		'Average order value' => esc_html__( 'Average order value', 'flavor-like' ),
		'Average product and review reactions per order 鈥?a pre-purchase interest signal.' => esc_html__( 'Average product and review reactions per order 鈥?a pre-purchase interest signal.', 'flavor-like' ),
		'Engagement per order' => esc_html__( 'Engagement per order', 'flavor-like' ),
		'Revenue generated per like or dislike 鈥?helps compare campaign ROI.' => esc_html__( 'Revenue generated per like or dislike 鈥?helps compare campaign ROI.', 'flavor-like' ),
		'Revenue per engagement' => esc_html__( 'Revenue per engagement', 'flavor-like' ),
		'Engagement vs orders trend' => esc_html__( 'Engagement vs orders trend', 'flavor-like' ),
		'Daily product reactions compared with store orders' => esc_html__( 'Daily product reactions compared with store orders', 'flavor-like' ),
		'Look for days when engagement rises before orders 鈥?a sign your social proof is working.' => esc_html__( 'Look for days when engagement rises before orders 鈥?a sign your social proof is working.', 'flavor-like' ),
		'Top products' => esc_html__( 'Top products', 'flavor-like' ),
		'Engagement and sales side by side' => esc_html__( 'Engagement and sales side by side', 'flavor-like' ),
		'No product engagement yet' => esc_html__( 'No product engagement yet', 'flavor-like' ),
		'Product' => esc_html__( 'Product', 'flavor-like' ),
		'Match score' => esc_html__( 'Match score', 'flavor-like' ),
		'Opportunities' => esc_html__( 'Opportunities', 'flavor-like' ),
		'Where engagement and sales diverge' => esc_html__( 'Where engagement and sales diverge', 'flavor-like' ),
		'High interest, lower sales 鈥?optimize merchandising, pricing, or checkout.' => esc_html__( 'High interest, lower sales 鈥?optimize merchandising, pricing, or checkout.', 'flavor-like' ),
		'Strong sellers with few likes 鈥?surface social proof with like buttons or badges.' => esc_html__( 'Strong sellers with few likes 鈥?surface social proof with like buttons or badges.', 'flavor-like' ),
		'Engagement and revenue by product category' => esc_html__( 'Engagement and revenue by product category', 'flavor-like' ),
		'Engagement vs sales intelligence' => esc_html__( 'Engagement vs sales intelligence', 'flavor-like' ),
		'See how product likes and review reactions relate to orders and revenue 鈥?available in Pro.' => esc_html__( 'See how product likes and review reactions relate to orders and revenue 鈥?available in Pro.', 'flavor-like' ),

		// Growth tips (overview)
		'Actionable recommendations based on your data' => esc_html__( 'Actionable recommendations based on your data', 'flavor-like' ),
		'Best time to publish' => esc_html__( 'Best time to publish', 'flavor-like' ),
		'{{day}} around {{time}} gets the most engagement 鈥?schedule content then.' => esc_html__( '{{day}} around {{time}} gets the most engagement 鈥?schedule content then.', 'flavor-like' ),
		'{{category}} drives {{share}}% of engagement 鈥?create more on this topic.' => esc_html__( '{{category}} drives {{share}}% of engagement 鈥?create more on this topic.', 'flavor-like' ),
		'Low conversion' => esc_html__( 'Low conversion', 'flavor-like' ),
		'Only {{rate}}% of viewers engage 鈥?improve button placement and CTAs.' => esc_html__( 'Only {{rate}}% of viewers engage 鈥?improve button placement and CTAs.', 'flavor-like' ),
		'Momentum' => esc_html__( 'Momentum', 'flavor-like' ),
		'Sentiment drop' => esc_html__( 'Sentiment drop', 'flavor-like' ),
		'Positive reactions fell to {{ratio}}% 鈥?review content getting dislikes.' => esc_html__( 'Positive reactions fell to {{ratio}}% 鈥?review content getting dislikes.', 'flavor-like' ),

		// Top content insights
		'{{title}} leads with {{count}} likes in this period.' => esc_html__( '{{title}} leads with {{count}} likes in this period.', 'flavor-like' ),
		'{{title}} converts best at {{rate}}% 鈥?replicate this format.' => esc_html__( '{{title}} converts best at {{rate}}% 鈥?replicate this format.', 'flavor-like' ),
		'{{name}} is your most active user 鈥?consider a loyalty perk.' => esc_html__( '{{name}} is your most active user 鈥?consider a loyalty perk.', 'flavor-like' ),

		// Header & metrics
		'Refresh data' => esc_html__( 'Refresh data', 'flavor-like' ),
		'Total engagements' => esc_html__( 'Total engagements', 'flavor-like' ),
		'Distinct people who engaged in this period.' => esc_html__( 'Distinct people who engaged in this period.', 'flavor-like' ),
		'Current period' => esc_html__( 'Current period', 'flavor-like' ),
		'Previous period' => esc_html__( 'Previous period', 'flavor-like' ),
		'Likes'          => esc_html__( 'Likes', 'flavor-like' ),
		'Interactions'   => esc_html__( 'Interactions', 'flavor-like' ),
		'Impressions'    => esc_html__( 'Impressions', 'flavor-like' ),

		// Notifications
		'Dismiss'             => esc_html__( 'Dismiss', 'flavor-like' ),
		'New Notifications'   => esc_html__( 'New Notifications', 'flavor-like' ),
		'No new notifications' => esc_html__( 'No new notifications', 'flavor-like' ),
		'{{current}} of {{total}}' => esc_html__( '{{current}} of {{total}}', 'flavor-like' ),

		// License
		'License Not Found!' => esc_html__( 'License Not Found!', 'flavor-like' ),
		'Your license is invalid or missing. Enter a valid key or purchase Pro to continue.' => esc_html__( 'Your license is invalid or missing. Enter a valid key or purchase Pro to continue.', 'flavor-like' ),
		'Get License'        => esc_html__( 'Get License', 'flavor-like' ),

		// Pro preview, sidebar & free shell
		'Upgrade to Pro' => esc_html__( 'Upgrade to Pro', 'flavor-like' ),
		'Learn more'     => esc_html__( 'Learn more', 'flavor-like' ),
		'Pro'            => esc_html__( 'Pro', 'flavor-like' ),
		'Settings'       => esc_html__( 'Settings', 'flavor-like' ),
		'Help'           => esc_html__( 'Help', 'flavor-like' ),
		'Get Pro'        => esc_html__( 'Get Pro', 'flavor-like' ),
		'Get Pro reports' => esc_html__( 'Get Pro reports', 'flavor-like' ),
		'Unlock live audience maps, top fans, and publishing insights with Pro.' => esc_html__( 'Unlock live audience maps, top fans, and publishing insights with Pro.', 'flavor-like' ),
		"See who's engaging most" => esc_html__( "See who's engaging most", 'flavor-like' ),
		'{{count}} engagements so far 鈥?unlock audience maps, top fans, and publishing insights.' => esc_html__( '{{count}} engagements so far 鈥?unlock audience maps, top fans, and publishing insights.', 'flavor-like' ),
		'Minimize Pro promo' => esc_html__( 'Minimize Pro promo', 'flavor-like' ),
		'Find your best posting times and top-performing topics' => esc_html__( 'Find your best posting times and top-performing topics', 'flavor-like' ),
		'Learn which devices and browsers your voters use' => esc_html__( 'Learn which devices and browsers your voters use', 'flavor-like' ),
		'Sidebar Pro card' => esc_html__( 'Sidebar Pro card', 'flavor-like' ),
		'Minimized to 鈥淕et Pro鈥?in the sidebar.' => esc_html__( 'Minimized to 鈥淕et Pro鈥?in the sidebar.', 'flavor-like' ),
		'Full card visible in the sidebar.' => esc_html__( 'Full card visible in the sidebar.', 'flavor-like' ),
		'Show full card' => esc_html__( 'Show full card', 'flavor-like' ),
		'Milestone'      => esc_html__( 'Milestone', 'flavor-like' ),
		'You have passed {{count}} total engagements 鈥?{{remaining}} to go until {{next}}.' => esc_html__( 'You have passed {{count}} total engagements 鈥?{{remaining}} to go until {{next}}.', 'flavor-like' ),
		'Your community has reached {{count}} total engagements!' => esc_html__( 'Your community has reached {{count}} total engagements!', 'flavor-like' ),
		'Total'          => esc_html__( 'Total', 'flavor-like' ),
		'Engagement is up {{percent}}% compared to last week.' => esc_html__( 'Engagement is up {{percent}}% compared to last week.', 'flavor-like' ),
		'Engagement is down {{percent}}% compared to last week.' => esc_html__( 'Engagement is down {{percent}}% compared to last week.', 'flavor-like' ),
		'Getting started' => esc_html__( 'Getting started', 'flavor-like' ),
		'{{count}} engagements so far 鈥?{{remaining}} more to reach {{target}}.' => esc_html__( '{{count}} engagements so far 鈥?{{remaining}} more to reach {{target}}.', 'flavor-like' ),
		'{{count}} engagements today 鈥?keep it going.' => esc_html__( '{{count}} engagements today 鈥?keep it going.', 'flavor-like' ),
		'No engagements yet today 鈥?yesterday had {{count}}.' => esc_html__( 'No engagements yet today 鈥?yesterday had {{count}}.', 'flavor-like' ),
		'Engagement is up {{percent}}% compared to yesterday.' => esc_html__( 'Engagement is up {{percent}}% compared to yesterday.', 'flavor-like' ),
		'Engagement is down {{percent}}% compared to yesterday.' => esc_html__( 'Engagement is down {{percent}}% compared to yesterday.', 'flavor-like' ),
		'Around {{time}} gets the most engagement 鈥?schedule content then.' => esc_html__( 'Around {{time}} gets the most engagement 鈥?schedule content then.', 'flavor-like' ),
		'A quick look at where your audience engages from.' => esc_html__( 'A quick look at where your audience engages from.', 'flavor-like' ),
		'See full map in Pro' => esc_html__( 'See full map in Pro', 'flavor-like' ),
		'Explore'        => esc_html__( 'Explore', 'flavor-like' ),
		'Preferences for your stats dashboard experience.' => esc_html__( 'Preferences for your stats dashboard experience.', 'flavor-like' ),
		'Announcements'  => esc_html__( 'Announcements', 'flavor-like' ),
		'Show announcement modals' => esc_html__( 'Show announcement modals', 'flavor-like' ),
		'Display popup announcements when you open the stats dashboard. Multiple announcements are shown one at a time.' => esc_html__( 'Display popup announcements when you open the stats dashboard. Multiple announcements are shown one at a time.', 'flavor-like' ),
		'Dismissed announcements' => esc_html__( 'Dismissed announcements', 'flavor-like' ),
		'No dismissed announcements.' => esc_html__( 'No dismissed announcements.', 'flavor-like' ),
		'{{count}} dismissed announcement(s) stored for your account.' => esc_html__( '{{count}} dismissed announcement(s) stored for your account.', 'flavor-like' ),
		'Clear dismissed' => esc_html__( 'Clear dismissed', 'flavor-like' ),
		'Dismissals were cleared. Popup announcements will appear again the next time you open the stats dashboard.' => esc_html__( 'Dismissals were cleared. Popup announcements will appear again the next time you open the stats dashboard.', 'flavor-like' ),
		'Plugin configuration' => esc_html__( 'Plugin configuration', 'flavor-like' ),
		'Open plugin settings' => esc_html__( 'Open plugin settings', 'flavor-like' ),
		'Help & documentation' => esc_html__( 'Help & documentation', 'flavor-like' ),
		'Appearance'     => esc_html__( 'Appearance', 'flavor-like' ),
		'Theme preference is stored locally in this browser, not in your WordPress user account.' => esc_html__( 'Theme preference is stored locally in this browser, not in your WordPress user account.', 'flavor-like' ),
		'Publishing schedule, category insights, and commerce analytics 鈥?available in Pro.' => esc_html__( 'Publishing schedule, category insights, and commerce analytics 鈥?available in Pro.', 'flavor-like' ),
		'Publishing schedule and category insights 鈥?available in Pro.' => esc_html__( 'Publishing schedule and category insights 鈥?available in Pro.', 'flavor-like' ),
		'See where your audience engages from with country breakdowns and trends.' => esc_html__( 'See where your audience engages from with country breakdowns and trends.', 'flavor-like' ),
		'Device, OS, and browser breakdowns for every vote.' => esc_html__( 'Device, OS, and browser breakdowns for every vote.', 'flavor-like' ),
		'Discover your most active members and reward loyal engagers.' => esc_html__( 'Discover your most active members and reward loyal engagers.', 'flavor-like' ),
		'See exactly who engaged with each piece of content.' => esc_html__( 'See exactly who engaged with each piece of content.', 'flavor-like' ),
		'Users who engaged with this content' => esc_html__( 'Users who engaged with this content', 'flavor-like' ),
		'Member'         => esc_html__( 'Member', 'flavor-like' ),
		'Last active'    => esc_html__( 'Last active', 'flavor-like' ),
		'Reactions'      => esc_html__( 'Reactions', 'flavor-like' ),
		'Back to engagement' => esc_html__( 'Back to engagement', 'flavor-like' ),
		'No registered engagers yet' => esc_html__( 'No registered engagers yet', 'flavor-like' ),
		'No registered engagers for {{title}}' => esc_html__( 'No registered engagers for {{title}}', 'flavor-like' ),
		'Guest votes are counted in totals but only registered members appear here.' => esc_html__( 'Guest votes are counted in totals but only registered members appear here.', 'flavor-like' ),
		'Like / Dislike Buttons' => esc_html__( 'Like / Dislike Buttons', 'flavor-like' ),
		'{{count}} engaged users' => esc_html__( '{{count}} engaged users', 'flavor-like' ),
		'{{count}} users engaged with {{title}}' => esc_html__( '{{count}} users engaged with {{title}}', 'flavor-like' ),
		'{{count}} engager(s)' => esc_html__( '{{count}} engager(s)', 'flavor-like' ),
		'No engagers'    => esc_html__( 'No engagers', 'flavor-like' ),
		'No engagers yet' => esc_html__( 'No engagers yet', 'flavor-like' ),
		'{{count}} total' => esc_html__( '{{count}} total', 'flavor-like' ),
		'Totals and charts for {{type}}' => esc_html__( 'Totals and charts for {{type}}', 'flavor-like' ),
		'Trends and top content for {{type}}' => esc_html__( 'Trends and top content for {{type}}', 'flavor-like' ),
		'Morning'        => esc_html__( 'Morning', 'flavor-like' ),
		'Afternoon'      => esc_html__( 'Afternoon', 'flavor-like' ),
		'Evening'        => esc_html__( 'Evening', 'flavor-like' ),
		'Night'          => esc_html__( 'Night', 'flavor-like' ),

		// Engagement modes & engagers UI
		'Close'          => esc_html__( 'Close', 'flavor-like' ),
		'See who'        => esc_html__( 'See who', 'flavor-like' ),
		'Ratings'        => esc_html__( 'Ratings', 'flavor-like' ),
		'Average rating' => esc_html__( 'Average rating', 'flavor-like' ),
		'Star Rating'    => esc_html__( 'Star Rating', 'flavor-like' ),
		'Star rating template' => esc_html__( 'Star rating template', 'flavor-like' ),
		'Star ratings in this period.' => esc_html__( 'Star ratings in this period.', 'flavor-like' ),
		'Emoji Reactions' => esc_html__( 'Emoji Reactions', 'flavor-like' ),
		'Emoji template'  => esc_html__( 'Emoji template', 'flavor-like' ),
		'Emoji reactions in this period.' => esc_html__( 'Emoji reactions in this period.', 'flavor-like' ),
		'Reactions are up {{percent}}% compared to last week.' => esc_html__( 'Reactions are up {{percent}}% compared to last week.', 'flavor-like' ),
		'Ratings are up {{percent}}% compared to last week.' => esc_html__( 'Ratings are up {{percent}}% compared to last week.', 'flavor-like' ),
		'{{count}} emoji reactions this week 鈥?use Top content to find what resonates.' => esc_html__( '{{count}} emoji reactions this week 鈥?use Top content to find what resonates.', 'flavor-like' ),
		'{{count}} star ratings this week 鈥?use Top content to find what resonates.' => esc_html__( '{{count}} star ratings this week 鈥?use Top content to find what resonates.', 'flavor-like' ),
		'{{name}} is your top rater 鈥?consider a loyalty perk.' => esc_html__( '{{name}} is your top rater 鈥?consider a loyalty perk.', 'flavor-like' ),
		'{{title}} averages 鈽?{{avg}} 鈥?replicate this format.' => esc_html__( '{{title}} averages 鈽?{{avg}} 鈥?replicate this format.', 'flavor-like' ),
		'{{title}} leads with {{count}} reactions in this period.' => esc_html__( '{{title}} leads with {{count}} reactions in this period.', 'flavor-like' ),
		'鈽?{{avg}} site average' => esc_html__( '鈽?{{avg}} site average', 'flavor-like' ),
		'Device Insights' => esc_html__( 'Device Insights', 'flavor-like' ),
		'Intelligence Report' => esc_html__( 'Intelligence Report', 'flavor-like' ),
		'WooCommerce Report' => esc_html__( 'WooCommerce Report', 'flavor-like' ),
		'Upgrade like storage' => esc_html__( 'Upgrade like storage', 'flavor-like' ),
		'Unable to load data' => esc_html__( 'Unable to load data', 'flavor-like' ),
		'Please refresh the page or try again later.' => esc_html__( 'Please refresh the page or try again later.', 'flavor-like' ),

		// Time ago
		'timeAgo'       => esc_html__( '{{count}} {{interval}} ago', 'flavor-like' ),
		'year'          => esc_html__( 'year', 'flavor-like' ),
		'year_plural'   => esc_html__( 'years', 'flavor-like' ),
		'month'         => esc_html__( 'month', 'flavor-like' ),
		'month_plural'  => esc_html__( 'months', 'flavor-like' ),
		'week'          => esc_html__( 'week', 'flavor-like' ),
		'week_plural'   => esc_html__( 'weeks', 'flavor-like' ),
		'day'           => esc_html__( 'day', 'flavor-like' ),
		'day_plural'    => esc_html__( 'days', 'flavor-like' ),
		'hour'          => esc_html__( 'hour', 'flavor-like' ),
		'hour_plural'   => esc_html__( 'hours', 'flavor-like' ),
		'minute'        => esc_html__( 'minute', 'flavor-like' ),
		'minute_plural' => esc_html__( 'minutes', 'flavor-like' ),
		'second'        => esc_html__( 'second', 'flavor-like' ),
		'second_plural' => esc_html__( 'seconds', 'flavor-like' ),
		'just now'      => esc_html__( 'just now', 'flavor-like' ),
	) );
}
add_action('wp_ajax_flavor_like_localization','flavor_like_localization_api');

/**
 * Settings schema api
 *
 * @return void
 */
function flavor_like_schema_api(){
	if( ! current_user_can( 'manage_options' ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	// Get settings API instance
	if ( class_exists( 'flavor_like_settings_api' ) ) {
		$settings_api = new flavor_like_settings_api();
		$schema = $settings_api->get_schema();
		wp_send_json_success( $schema );
	} else {
		wp_send_json_error( esc_html__( 'Error: Settings API not available.', 'flavor-like' ) );
	}
}
add_action('wp_ajax_flavor_like_schema_api','flavor_like_schema_api');

/**
 * Settings values api
 *
 * @return void
 */
function flavor_like_settings_api(){
	if( ! current_user_can( 'manage_options' ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	// Get settings API instance
	if ( class_exists( 'flavor_like_settings_api' ) ) {
		$settings_api = new flavor_like_settings_api();
		$values = $settings_api->get_settings( null );
		wp_send_json_success( $values );
	} else {
		wp_send_json_error( esc_html__( 'Error: Settings API not available.', 'flavor-like' ) );
	}
}
add_action('wp_ajax_flavor_like_settings_api','flavor_like_settings_api');

/**
 * Save settings api
 *
 * @return void
 */
function flavor_like_save_settings_api(){
	if( ! current_user_can( 'manage_options' ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	$max_body = defined( 'MB_IN_BYTES' ) ? 2 * MB_IN_BYTES : 2097152;
	$json     = flavor_like_read_php_input_capped( $max_body );
	if ( is_wp_error( $json ) ) {
		wp_send_json_error( $json->get_error_message() );
	}
	$values = json_decode( $json, true );

	if ( ! is_array( $values ) ) {
		wp_send_json_error( esc_html__( 'Invalid request data. Expected an object with setting values.', 'flavor-like' ) );
	}

	// Get settings API instance
	if ( class_exists( 'flavor_like_settings_api' ) ) {
		$settings_api = new flavor_like_settings_api();
		$result = $settings_api->save_settings( $values );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		} else {
			wp_send_json_success( $result );
		}
	} else {
		wp_send_json_error( esc_html__( 'Error: Settings API not available.', 'flavor-like' ) );
	}
}
add_action('wp_ajax_flavor_like_save_settings_api','flavor_like_save_settings_api');


/**
 * Customizer schema api
 *
 * @return void
 */
function flavor_like_customizer_schema_api(){
	if( ! current_user_can( 'manage_options' ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	// Get customizer API instance
	if ( class_exists( 'flavor_like_customizer_api' ) ) {
		$customizer_api = new flavor_like_customizer_api();
		$schema = $customizer_api->get_schema();
		wp_send_json_success( $schema );
	} else {
		wp_send_json_error( esc_html__( 'Error: Customizer API not available.', 'flavor-like' ) );
	}
}
add_action('wp_ajax_flavor_like_customizer_schema_api','flavor_like_customizer_schema_api');

/**
 * Customizer values api
 *
 * @return void
 */
function flavor_like_customizer_values_api(){
	if( ! current_user_can( 'manage_options' ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	// Get customizer API instance
	if ( class_exists( 'flavor_like_customizer_api' ) ) {
		$customizer_api = new flavor_like_customizer_api();
		$values = $customizer_api->get_values( null );
		wp_send_json_success( $values );
	} else {
		wp_send_json_error( esc_html__( 'Error: Customizer API not available.', 'flavor-like' ) );
	}
}
add_action('wp_ajax_flavor_like_customizer_values_api','flavor_like_customizer_values_api');

/**
 * Save customizer api
 *
 * @return void
 */
function flavor_like_save_customizer_api(){
	if( ! current_user_can( 'manage_options' ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	$max_body = defined( 'MB_IN_BYTES' ) ? 2 * MB_IN_BYTES : 2097152;
	$json     = flavor_like_read_php_input_capped( $max_body );
	if ( is_wp_error( $json ) ) {
		wp_send_json_error( $json->get_error_message() );
	}
	$values = json_decode( $json, true );

	if ( ! is_array( $values ) ) {
		wp_send_json_error( esc_html__( 'Invalid request data. Expected an object with customizer values.', 'flavor-like' ) );
	}

	// Get customizer API instance
	if ( class_exists( 'flavor_like_customizer_api' ) ) {
		$customizer_api = new flavor_like_customizer_api();
		$customizer_api->save_values( $values );
	} else {
		wp_send_json_error( esc_html__( 'Error: Customizer API not available.', 'flavor-like' ) );
	}
}
add_action('wp_ajax_flavor_like_save_customizer_api','flavor_like_save_customizer_api');

/**
 * Customizer preview api
 *
 * @return void
 */
function flavor_like_customizer_preview_api(){
	if( ! current_user_can( 'manage_options' ) || ! flavor_like_is_valid_nonce( FLAVOR_LIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'flavor-like' ) );
	}

	// Get customizer API instance
	if ( class_exists( 'flavor_like_customizer_api' ) ) {
		$customizer_api = new flavor_like_customizer_api();
		$customizer_api->get_preview( null );
	} else {
		wp_send_json_error( esc_html__( 'Error: Customizer API not available.', 'flavor-like' ) );
	}
}
add_action('wp_ajax_flavor_like_customizer_preview_api','flavor_like_customizer_preview_api');

