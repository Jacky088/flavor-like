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
 * Top content for a single type (free —no filters).
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

	// Authoritative Chinese translation dictionary
	static $zh_dict = null;
	if ( null === $zh_dict ) {
				$zh_dict = array(
			'A quick look at where your audience engages from.' => '快速了解受众的互动来源。',
			'Actionable engagement intelligence for your site' => '为您提供可操作的站点互动分析',
			'Actionable recommendations based on your data' => '基于您的数据的可操作建议',
			'Actions' => '操作',
			'Activities' => '活动',
			'Activity Title' => '活动标题',
			'Activity by country' => '按国家划分的活动',
			'Activity heatmap' => '活动热力图',
			'Afternoon' => '下午',
			'All likes in this period.' => '此时间段内的所有点赞。',
			'All time' => '历史累计',
			'An error occurred while deleting the log entry.' => '删除日志条目时发生错误。',
			'Announcements' => '公告',
			'Appearance' => '外观',
			'Apply' => '应用',
			'Around {{time}} gets the most engagement — schedule content then.' => '{{time}} 左右互动最多 — 建议在此时间段发布内容。',
			'Audience' => '受众',
			'Audience by location' => '按地区划分的受众',
			'Average order value' => '平均订单价值',
			'Average product and review reactions per order — a pre-purchase interest signal.' => '每笔订单的平均商品与评价互动 — 购买前兴趣信号。',
			'Average rating' => '平均评分',
			'Average revenue per order in this period.' => '此时间段内每笔订单的平均收入。',
			'Back to engagement' => '返回互动统计',
			'Best day(s)' => '最佳日期',
			'Best hour' => '最佳时间',
			'Best hours to post' => '最佳发布时间',
			'Best time to publish' => '最佳发布时间',
			'Browse and manage vote history' => '浏览和管理点赞历史',
			'Browse and manage vote history for {{type}}' => '浏览和管理{{type}}的点赞历史',
			'Browser' => '浏览器',
			'Button impressions' => '按钮展示量',
			'Cancel' => '取消',
			'Categories' => '分类',
			'Category' => '分类',
			'Category performance' => '分类表现',
			'Change compared to the previous period.' => '与上一时间段的变化。',
			'Clear' => '清除',
			'Clear all' => '清除全部',
			'Clear dismissed' => '清除已关闭记录',
			'Close' => '关闭',
			'Comment Author' => '评论作者',
			'Comment Content' => '评论内容',
			'Comments' => '评论',
			'Compared to last week' => '较上周',
			'Completed and processing orders.' => '已完成和处理中的订单。',
			'Connect product engagement with sales to spot opportunities and plan campaigns' => '将商品互动与销售关联，发现机会并规划活动',
			'Content' => '内容',
			'Content Types' => '内容类型',
			'Content intelligence' => '内容分析',
			'Content type' => '内容类型',
			'Countries' => '国家/地区',
			'Country' => '国家',
			'Current period' => '当前时间段',
			'Custom' => '自定义',
			'Daily activity' => '每日活动',
			'Daily counts for this period.' => '此时间段内的每日计数。',
			'Daily product reactions compared with store orders' => '每日商品互动与商店订单的对比',
			'Date' => '日期',
			'Date Range' => '日期范围',
			'Delete Selected' => '删除选中',
			'Device' => '设备',
			'Device Insights' => '设备洞察',
			'Device, OS and browser breakdown by unique voters' => '按独立点赞用户划分的设备、系统和浏览器分布',
			'Device, OS, and browser breakdowns for every vote.' => '每次点赞的设备、操作系统和浏览器分布。',
			'Devices' => '设备',
			'Discover your most active members and reward loyal engagers.' => '发现最活跃的用户并奖励忠实互动者。',
			'Dislike' => '踩',
			'Dislikes' => '踩',
			'Dismiss' => '关闭',
			'Dismissals were cleared. Popup announcements will appear again the next time you open the stats dashboard.' => '已清除关闭记录。下次打开统计仪表盘时，弹窗公告将再次出现。',
			'Dismissed announcements' => '已关闭的公告',
			'Display popup announcements when you open the stats dashboard. Multiple announcements are shown one at a time.' => '打开统计仪表盘时显示弹窗公告。多个公告将逐一显示。',
			'Distinct people who engaged in this period.' => '此时间段内参与互动的独立用户数。',
			'Download CSV' => '下载 CSV',
			'Emoji Reactions' => '表情互动',
			'Emoji reactions in this period.' => '此时间段内的表情互动。',
			'Emoji template' => '表情模板',
			'Enable it in General settings to see engagement rate and button impressions.' => '在常规设置中启用以查看互动率和按钮展示量。',
			'Enable likes on products or reviews, or wait for store orders, to unlock commerce intelligence.' => '启用商品或评价的点赞功能，或等待商店订单，以解锁电商分析。',
			'Enable view tracking in General settings if you have not already to see engagement rate and button impressions.' => '如果尚未启用，请在常规设置中启用浏览量跟踪以查看互动率和展示量。',
			'End date' => '结束日期',
			'Engaged Users' => '参与用户',
			'Engagement' => '互动统计',
			'Engagement Summary' => '互动摘要',
			'Engagement This Week' => '本周互动',
			'Engagement and revenue by product category' => '按商品分类划分的互动和收入',
			'Engagement and sales side by side' => '互动与销售对比',
			'Engagement as a share of page views.' => '互动占页面浏览量的比例。',
			'Engagement by day and hour' => '按天和小时划分的互动',
			'Engagement correlated with sales' => '互动与销售的关联',
			'Engagement is down {{percent}}% compared to last week.' => '互动量比上周下降了 {{percent}}%。',
			'Engagement is down {{percent}}% compared to yesterday.' => '互动量比昨天下降了 {{percent}}%。',
			'Engagement is up {{percent}}% compared to last week.' => '互动量比上周增长了 {{percent}}%。',
			'Engagement is up {{percent}}% compared to yesterday.' => '互动量比昨天增长了 {{percent}}%。',
			'Engagement per order' => '每笔订单的互动数',
			'Engagement rate' => '互动率',
			'Engagement rate is the share of button impressions that led to a like or reaction.' => '互动率是指导致点赞或反应的按钮展示比例。',
			'Engagement trend' => '互动趋势',
			'Engagement trends' => '互动趋势',
			'Engagement vs orders trend' => '互动与订单趋势',
			'Engagement vs sales intelligence' => '互动与销售分析',
			'Engagements' => '互动数',
			'Engagers' => '互动者',
			'Error' => '错误',
			'Error: You do not have permission to do that.' => '错误：您没有权限执行此操作。',
			'Evening' => '晚上',
			'Explore' => '探索',
			'Failed to delete the log entry.' => '删除日志条目失败。',
			'Feedback template' => '反馈模板',
			'Filters' => '筛选',
			'Find your best posting times and top-performing topics' => '发现最佳发布时间与高热度话题',
			'Full card visible in the sidebar.' => '侧边栏可见完整卡片。',
			'Full report' => '完整报告',
			'Get License' => '获取许可证',
			'Get Pro' => '获取专业版',
			'Get Pro reports' => '获取专业版报告',
			'Getting started' => '开始使用',
			'Getting started milestone' => '新手里程碑',
			'Go to Home' => '返回首页',
			'Growth' => '增长',
			'Guest votes are counted in totals but only registered members appear here.' => '访客点赞会计入总数，但仅注册会员显示在此处。',
			'Help' => '帮助',
			'Help & documentation' => '帮助与文档',
			'High interest, lower sales — optimize merchandising, pricing, or checkout.' => '高关注、低销量 — 建议优化商品展示、定价或结账流程。',
			'Highest first' => '最高优先',
			'Hour' => '小时',
			'Hourly pattern' => '每小时分布',
			'How often your like button was shown. Compare with engagements.' => '点赞按钮展示频率，与互动数据进行对比。',
			'How page views convert to engagement.' => '页面浏览量如何转化为互动。',
			'IP' => 'IP',
			'IP Address' => 'IP 地址',
			'Impressions' => '展示量',
			'Impressions will appear as visitors view pages with your like button.' => '当访客浏览包含点赞按钮的页面时，展示量将在此呈现。',
			'Insights' => '洞察',
			'Intelligence' => '智能分析',
			'Intelligence Report' => '智能分析报告',
			'Interactions' => '互动',
			'Interactions this month' => '本月互动数',
			'Item' => '项目',
			'Items' => '项目',
			'Last 30 days' => '最近 30 天',
			'Last active' => '最近活跃',
			'Last calendar year' => '去年',
			'Last month' => '上个月',
			'Last week' => '上周',
			'Last {{count}} days' => '最近 {{count}} 天',
			'Last {{count}} months' => '最近 {{count}} 个月',
			'Learn more' => '了解更多',
			'Learn which devices and browsers your voters use' => '了解您的点赞用户使用的设备和浏览器',
			'Less' => '更少',
			'License Not Found!' => '未找到许可证！',
			'Like' => '点赞',
			'Like / Dislike Buttons' => '点赞 / 踩按钮',
			'Like vs dislike ratio' => '好评率',
			'Like-only template' => '仅点赞模板',
			'Likes' => '点赞',
			'Likes + dislikes per view' => '每次浏览的点赞和踩数',
			'Likes and dislikes on WooCommerce products.' => 'WooCommerce 商品的点赞和踩。',
			'Likes as a share of all reactions.' => '点赞占所有互动的比例。',
			'Likes per view' => '每次浏览的点赞数',
			'Likes this month' => '本月点赞数',
			'Loading...' => '加载中...',
			'Logs' => '日志',
			'Look for days when engagement rises before orders — a sign your social proof is working.' => '观察订单增长前互动上升的日期 — 表明社交认同正在发挥作用。',
			'Low conversion' => '低转化',
			'Lowest first' => '最低优先',
			'Match score' => '匹配度得分',
			'Member' => '成员',
			'Milestone' => '里程碑',
			'Minimize Pro promo' => '最小化专业版推广',
			'Minimized to “Get Pro” in the sidebar.' => '已收起至侧边栏“获取专业版”。',
			'Momentum' => '势头',
			'Monthly Engagement Overview' => '月度互动概览',
			'More' => '更多',
			'Morning' => '上午',
			'Most active engagers recently' => '近期最活跃的互动者',
			'Most active members recently' => '近期最活跃的成员',
			'Most active visitors recently' => '近期最活跃的访客',
			'Name' => '名称',
			'Net product revenue in this period.' => '此时间段内的商品净收入。',
			'New Notifications' => '新通知',
			'Night' => '夜晚',
			'No Data Available' => '暂无数据',
			'No button impressions in this period yet.' => '此时间段内暂无按钮展示量。',
			'No country data yet' => '暂无国家数据',
			'No data for this period' => '该时间段暂无数据',
			'No data yet. Records will appear here once engagement starts.' => '暂无数据。互动开始后，记录将显示在此处。',
			'No dismissed announcements.' => '没有已关闭的公告。',
			'No engagements yet today — yesterday had {{count}}.' => '今天暂无互动 — 昨天有 {{count}} 次互动。',
			'No engagers' => '暂无互动者',
			'No engagers yet' => '暂无互动者',
			'No logs found for this category' => '此分类下未找到日志',
			'No new notifications' => '暂无新通知',
			'No product engagement yet' => '暂无商品互动',
			'No registered engagers for {{title}}' => '{{title}} 暂无注册互动者',
			'No registered engagers yet' => '暂无注册互动者',
			'OS' => '操作系统',
			'Only {{rate}}% of viewers engage — improve button placement and CTAs.' => '仅 {{rate}}% 的访客参与互动 — 建议优化点赞按钮位置与引导文案。',
			'Only {{ratio}}% positive this week — check Top content for items getting dislikes.' => '本周好评率仅 {{ratio}}% — 请查看被踩的热门内容。',
			'Open plugin settings' => '打开插件设置',
			'Opportunities' => '机会',
			'Orders' => '订单',
			'Overall Performance' => '整体表现',
			'Overview' => '概览',
			'Page Not Found' => '页面未找到',
			'Peak' => '峰值',
			'Peak window' => '最佳时段',
			'Performance' => '性能表现',
			'Performance snapshot' => '表现概览',
			'Please refresh the page or try again later.' => '请刷新页面或稍后重试。',
			'Plugin configuration' => '插件配置',
			'Positive reactions fell to {{ratio}}% — review content getting dislikes.' => '正面好评率降至 {{ratio}}% — 请查看被踩的内容。',
			'Positive sentiment' => '正面情感',
			'Post Title' => '文章标题',
			'Post type' => '文章类型',
			'Posts' => '文章',
			'Preferences for your stats dashboard experience.' => '统计仪表盘体验的偏好设置。',
			'Previous period' => '上一时间段',
			'Pro' => '专业版',
			'Product' => '商品',
			'Product engagement' => '商品互动',
			'Product likes' => '商品点赞数',
			'Product units sold in this period.' => '此时间段内售出的商品数量。',
			'Publish on {{day}} around {{time}} for maximum engagement.' => '在 {{day}} {{time}} 左右发布以获得最大互动量。',
			'Published' => '已发布',
			'Publishing schedule and category insights — available in Pro.' => '发布排期建议及分类洞察 — 专业版可用。',
			'Publishing schedule and category performance' => '发布计划和分类表现',
			'Publishing schedule, categories, and commerce insights' => '发布计划、分类和电商分析',
			'Publishing schedule, category insights, and commerce analytics — available in Pro.' => '发布排期建议、分类洞察及电商分析 — 专业版可用。',
			'Quarter to date' => '本季度至今',
			'Ratings' => '评分',
			'Ratings are up {{percent}}% compared to last week.' => '评分比上周增长了 {{percent}}%。',
			'Reach' => '覆盖范围',
			'Reaction and voter metrics.' => '反应和点赞者指标。',
			'Reactions' => '互动总数',
			'Reactions are up {{percent}}% compared to last week.' => '互动比上周增长了 {{percent}}%。',
			'Reactions on verified customer product reviews.' => '已验证客户商品评价的反应。',
			'Refresh Page' => '刷新页面',
			'Refresh data' => '刷新数据',
			'Reports' => '报表',
			'Revenue' => '收入',
			'Revenue generated per like or dislike — helps compare campaign ROI.' => '每个点赞或踩产生的收入 — 有助于评估营销活动投资回报。',
			'Revenue per engagement' => '每次互动的收入',
			'Review engagement' => '评价互动',
			'Search' => '搜索',
			'See exactly who engaged with each piece of content.' => '精确查看每条内容的互动者。',
			'See full map in Pro' => '在专业版中查看完整地图',
			'See how product likes and review reactions relate to orders and revenue — available in Pro.' => '查看商品点赞和评价互动与订单及收入的关联 — 专业版可用。',
			'See where your audience engages from' => '查看受众的互动来源',
			'See where your audience engages from with country breakdowns and trends.' => '通过国家分布和趋势查看受众的互动来源。',
			'See who' => '查看互动详情',
			'Select...' => '请选择...',
			'Selected period' => '选定时间段',
			'Sentiment drop' => '好评率下降',
			'Settings' => '设置',
			'Share' => '分享',
			'Share of weekly activity' => '占每周互动的比例',
			'Shop spotlight' => '店铺聚焦',
			'Show announcement modals' => '显示公告弹窗',
			'Show full card' => '显示完整卡片',
			'Showing {{from}} to {{to}} of {{total}} results' => '显示第 {{from}} 到 {{to}} 条，共 {{total}} 条结果',
			'Sidebar Pro card' => '侧边栏专业版卡片',
			'Something went wrong' => '出现错误',
			'Sort by' => '排序方式',
			'Star Rating' => '星级评分',
			'Star rating template' => '星级评分模板',
			'Star ratings in this period.' => '此时间段内的星级评分。',
			'Start date' => '开始日期',
			'Statistics' => '统计',
			'Status' => '状态',
			'Status Filter' => '状态筛选',
			'Store snapshot' => '店铺概览',
			'Strong sellers with few likes — surface social proof with like buttons or badges.' => '畅销但点赞较少 — 建议通过点赞按钮或徽章突出社交认同。',
			'Sweet spot' => '最佳时段',
			'Taxonomy' => '分类法',
			'Technology' => '技术统计',
			'Theme preference is stored locally in this browser, not in your WordPress user account.' => '主题偏好仅存储在此浏览器本地，不在您的 WordPress 用户账户中。',
			'This Month' => '本月',
			'This Week' => '本周',
			'This Week\'s Likes' => '本周点赞',
			'This Year' => '今年',
			'This month' => '本月',
			'This page does not exist or was moved.' => '此页面不存在或已被移动。',
			'This week' => '本周',
			'This year' => '今年',
			'Time windows' => '时间窗口',
			'Today' => '今日',
			'Today\'s Likes' => '今日点赞',
			'Top categories' => '热门分类',
			'Top content' => '热门内容',
			'Top countries' => '热门国家/地区',
			'Top members' => '顶级成员',
			'Top products' => '热门商品',
			'Top {{type}} your audience engages with most' => '受众互动最多的热门{{type}}',
			'Topic Title' => '话题标题',
			'Topics' => '话题',
			'Total' => '总计',
			'Total Interactions To Date' => '全站历史累计互动',
			'Total Likes' => '全站总点赞',
			'Total engagements' => '总互动数',
			'Total likes' => '总点赞数',
			'Total records' => '总记录数',
			'Total views' => '总浏览量',
			'Totals and charts for {{type}}' => '{{type}}的总计与图表',
			'Totals at a glance' => '总计概览',
			'Trends' => '趋势',
			'Trends and top content for {{type}}' => '{{type}}的趋势和热门内容',
			'Trends and totals for {{type}}' => '{{type}}的趋势和总计',
			'Try changing your filters or search.' => '请尝试更改筛选条件或搜索内容。',
			'Unable to load data' => '无法加载数据',
			'Unable to load data. Please try again.' => '无法加载数据。请重试。',
			'Unable to load data. Refresh the page or contact support.' => '无法加载数据。请刷新页面或联系支持。',
			'Undislike' => '取消踩',
			'Unique users' => '独立用户',
			'Unique view: see whether shopper reactions align with orders and revenue.' => '独特视角：查看购物者反应是否与订单和收入一致。',
			'Units sold' => '售出数量',
			'Unknown User' => '未知用户',
			'Unlike' => '取消赞',
			'Unlock live audience maps, top fans, and publishing insights with Pro.' => '使用专业版解锁实时受众地图、顶级粉丝和发布洞察。',
			'Untitled' => '无标题',
			'Upgrade like storage' => '升级点赞存储',
			'Upgrade to Pro' => '升级到专业版',
			'Use Content intelligence to find the best publish times for your audience.' => '使用内容分析功能为您的受众找到最佳发布时间。',
			'User' => '用户',
			'User Engagement by Country' => '按国家划分的用户互动',
			'User(s)' => '用户',
			'Users who engaged with this content' => '与此内容互动的用户',
			'View' => '查看',
			'View By' => '查看方式',
			'View tracking is off.' => '浏览量跟踪已关闭。',
			'Views' => '浏览量',
			'Vote history · {{type}}' => '投票历史 · {{type}}',
			'Voters' => '投票者',
			'Voters by device & browser' => '按设备和浏览器划分的投票者',
			'When to publish' => '何时发布',
			'When your audience engages' => '受众互动时间',
			'When your audience is most likely to react.' => '您的受众最有可能做出反应的时间。',
			'Where engagement and sales diverge' => '互动与销售出现差异的地方',
			'WooCommerce' => 'WooCommerce',
			'WooCommerce Report' => 'WooCommerce 报告',
			'WooCommerce report unavailable' => 'WooCommerce 报告不可用',
			'Yearly Engagement Trends' => '年度互动趋势',
			'Yesterday' => '昨天',
			'Yesterday\'s Likes' => '昨日点赞',
			'You have passed {{count}} total engagements — {{remaining}} to go until {{next}}.' => '总互动量已突破 {{count}} 次 — 距离达到 {{next}} 还需 {{remaining}} 次。',
			'Your audience is most active in the {{window}} window ({{range}}).' => '您的受众在 {{window}} 时段（{{range}}）最活跃。',
			'Your community has reached {{count}} total engagements!' => '您的社区已达到 {{count}} 次总互动！',
			'Your engagement dashboard at a glance' => '互动仪表盘概览',
			'Your license is invalid or missing. Enter a valid key or purchase Pro to continue.' => '您的许可证无效或缺失。请输入有效密钥或购买专业版以继续。',
			'day' => '天',
			'day_plural' => '天',
			'hour' => '小时',
			'hour_plural' => '小时',
			'just now' => '刚刚',
			'minute' => '分钟',
			'minute_plural' => '分钟',
			'month' => '个月',
			'month_plural' => '个月',
			'per page' => '每页',
			'positive' => '正面好评',
			's engaging most' => '互动最活跃的用户',
			'second' => '秒',
			'second_plural' => '秒',
			'timeAgo' => '发布于',
			'vs last month' => '与上月相比',
			'vs last week' => '与上周相比',
			'vs last year' => '与去年相比',
			'week' => '周',
			'week_plural' => '周',
			'year' => '年',
			'year_plural' => '年',
			'{{category}} drives {{share}}% of engagement — create more on this topic.' => '{{category}} 贡献了 {{share}}% 的互动 — 建议增加该主题内容。',
			'{{country}} is your top market with {{share}}% of engaged voters.' => '{{country}} 是您的主要市场，占参与投票者的 {{share}}%。',
			'{{count}} actions this week — reward top engagers to build loyalty.' => '本周累计 {{count}} 次互动 — 建议奖励活跃用户以提升忠诚度。',
			'{{count}} actions this week — reward top members to build loyalty.' => '本周累计 {{count}} 次互动 — 建议奖励活跃成员以提升忠诚度。',
			'{{count}} actions this week — see which visitors engage most often.' => '本周累计 {{count}} 次互动 — 查看哪些访客互动最频繁。',
			'{{count}} dismissed announcement(s) stored for your account.' => '已为您的账户存储了 {{count}} 条已关闭的公告。',
			'{{count}} emoji reactions this week — use Top content to find what resonates.' => '本周累计 {{count}} 次表情互动 — 查看热门内容了解受众喜好。',
			'{{count}} engaged users' => '{{count}} 位参与用户',
			'{{count}} engagements in this slot' => '此时间段内有 {{count}} 次互动',
			'{{count}} engagements so far — unlock audience maps, top fans, and publishing insights.' => '目前已有 {{count}} 次互动 — 解锁受众地图、顶级粉丝和发布洞察。',
			'{{count}} engagements so far — {{remaining}} more to reach {{target}}.' => '目前已有 {{count}} 次互动 — 还需 {{remaining}} 次即可达到 {{target}}。',
			'{{count}} engagements today — keep it going.' => '今天已有 {{count}} 次互动 — 继续保持。',
			'{{count}} engager(s)' => '{{count}} 位互动者',
			'{{count}} selected' => '已选择 {{count}} 项',
			'{{count}} star ratings this week — use Top content to find what resonates.' => '本周累计 {{count}} 次星级评分 — 查看热门内容了解受众喜好。',
			'{{count}} total' => '共 {{count}} 项',
			'{{count}} users engaged with {{title}}' => '{{count}} 位用户与《{{title}}》互动',
			'{{current}} of {{total}}' => '{{current}} / {{total}}',
			'{{day}} around {{time}} gets the most engagement — schedule content then.' => '{{day}} {{time}} 左右互动最多 — 建议在此时间段发布内容。',
			'{{day}} · {{time}}' => '{{day}} · {{time}}',
			'{{likes}} likes and {{dislikes}} dislikes — use Top content to find what resonates.' => '本周获得 {{likes}} 个点赞与 {{dislikes}} 个踩 — 请查看热门内容了解读者偏好。',
			'{{likes}} likes this week — use Top content to find what resonates.' => '本周获得 {{likes}} 次点赞 — 请查看热门内容了解读者喜好。',
			'{{likes}} likes · {{dislikes}} dislikes' => '{{likes}} 赞 · {{dislikes}} 踩',
			'{{likes}} of {{total}} reactions' => '{{total}} 个互动中有 {{likes}} 个点赞',
			'{{name}} is your most active user — consider a loyalty perk.' => '{{name}} 是最活跃的用户 — 建议提供忠诚度奖励。',
			'{{name}} is your top rater — consider a loyalty perk.' => '{{name}} 是评分最多的用户 — 建议提供忠诚度奖励。',
			'{{rate}}% engagement rate' => '{{rate}}% 互动率',
			'{{share}}% engage from desktop — long-form content may perform better.' => '{{share}}% 的互动来自电脑端 — 深度长文可能有更好表现。',
			'{{share}}% of all activity · {{range}}' => '占全部活动的 {{share}}% · {{range}}',
			'{{share}}% of engagers use mobile — optimize for small screens.' => '{{share}}% 的互动来自移动端 — 建议针对小屏幕进行优化。',
			'{{share}}% of weekly activity' => '占每周互动的 {{share}}%',
			'{{title}} averages — {{avg}} — replicate this format.' => '《{{title}}》平均分为 {{avg}} — 建议复制此内容结构。',
			'{{title}} converts best at {{rate}}% — replicate this format.' => '《{{title}}》互动率最高（{{rate}}%）— 建议复用此内容形式。',
			'{{title}} leads with {{count}} likes in this period.' => '《{{title}}》在此时间段内以 {{count}} 个点赞领先。',
			'{{title}} leads with {{count}} reactions in this period.' => '《{{title}}》在此时间段内以 {{count}} 个互动领先。',
			'— {{avg}} site average' => '— 全站平均 {{avg}}',
		);
	}

	$locale = function_exists( 'get_locale' ) ? get_locale() : 'zh_CN';
	$is_zh  = ( 0 === strpos( $locale, 'zh' ) );

	$raw_strings = array(
		// Template variables (not shown in UI)
		'{{site_name}}'    => get_bloginfo( 'name' ),
		'{{language}}'     => substr( get_bloginfo( 'language' ), 0, 2 ),
		'{{display_name}}' => ( $current_user && isset( $current_user->display_name ) ) ? esc_attr( $current_user->display_name ) : '',
		'Overview' => esc_html__( 'Overview', 'flavor-like' ),
		'Reports' => esc_html__( 'Reports', 'flavor-like' ),
		'Engagement' => esc_html__( 'Engagement', 'flavor-like' ),
		'Intelligence' => esc_html__( 'Intelligence', 'flavor-like' ),
		'Content intelligence' => esc_html__( 'Content intelligence', 'flavor-like' ),
		'Audience' => esc_html__( 'Audience', 'flavor-like' ),
		'Countries' => esc_html__( 'Countries', 'flavor-like' ),
		'Technology' => esc_html__( 'Technology', 'flavor-like' ),
		'Devices' => esc_html__( 'Devices', 'flavor-like' ),
		'Logs' => esc_html__( 'Logs', 'flavor-like' ),
		'Insights' => esc_html__( 'Insights', 'flavor-like' ),
		'View' => esc_html__( 'View', 'flavor-like' ),
		'Filters' => esc_html__( 'Filters', 'flavor-like' ),
		'Clear all' => esc_html__( 'Clear all', 'flavor-like' ),
		'Apply' => esc_html__( 'Apply', 'flavor-like' ),
		'Cancel' => esc_html__( 'Cancel', 'flavor-like' ),
		'Clear' => esc_html__( 'Clear', 'flavor-like' ),
		'Loading...' => esc_html__( 'Loading...', 'flavor-like' ),
		'Actionable engagement intelligence for your site' => esc_html__( 'Actionable engagement intelligence for your site', 'flavor-like' ),
		'Your engagement dashboard at a glance' => esc_html__( 'Your engagement dashboard at a glance', 'flavor-like' ),
		'Publishing schedule, categories, and commerce insights' => esc_html__( 'Publishing schedule, categories, and commerce insights', 'flavor-like' ),
		'Publishing schedule and category performance' => esc_html__( 'Publishing schedule and category performance', 'flavor-like' ),
		'Connect product engagement with sales to spot opportunities and plan campaigns' => esc_html__( 'Connect product engagement with sales to spot opportunities and plan campaigns', 'flavor-like' ),
		'See where your audience engages from' => esc_html__( 'See where your audience engages from', 'flavor-like' ),
		'Audience by location' => esc_html__( 'Audience by location', 'flavor-like' ),
		'Device, OS and browser breakdown by unique voters' => esc_html__( 'Device, OS and browser breakdown by unique voters', 'flavor-like' ),
		'Voters by device & browser' => esc_html__( 'Voters by device & browser', 'flavor-like' ),
		'Trends and totals for {{type}}' => esc_html__( 'Trends and totals for {{type}}', 'flavor-like' ),
		'Top {{type}} your audience engages with most' => esc_html__( 'Top {{type}} your audience engages with most', 'flavor-like' ),
		'Browse and manage vote history' => esc_html__( 'Browse and manage vote history', 'flavor-like' ),
		'Browse and manage vote history for {{type}}' => esc_html__( 'Browse and manage vote history for {{type}}', 'flavor-like' ),
		'Engagement This Week' => esc_html__( 'Engagement This Week', 'flavor-like' ),
		'Monthly Engagement Overview' => esc_html__( 'Monthly Engagement Overview', 'flavor-like' ),
		'Yearly Engagement Trends' => esc_html__( 'Yearly Engagement Trends', 'flavor-like' ),
		'Overall Performance' => esc_html__( 'Overall Performance', 'flavor-like' ),
		'All time' => esc_html__( 'All time', 'flavor-like' ),
		'This Week' => esc_html__( 'This Week', 'flavor-like' ),
		'This Month' => esc_html__( 'This Month', 'flavor-like' ),
		'This Year' => esc_html__( 'This Year', 'flavor-like' ),
		'Today' => esc_html__( 'Today', 'flavor-like' ),
		'Yesterday' => esc_html__( 'Yesterday', 'flavor-like' ),
		'vs last week' => esc_html__( 'vs last week', 'flavor-like' ),
		'vs last month' => esc_html__( 'vs last month', 'flavor-like' ),
		'vs last year' => esc_html__( 'vs last year', 'flavor-like' ),
		'Total Interactions To Date' => esc_html__( 'Total Interactions To Date', 'flavor-like' ),
		'Engagement Summary' => esc_html__( 'Engagement Summary', 'flavor-like' ),
		'Totals at a glance' => esc_html__( 'Totals at a glance', 'flavor-like' ),
		'Trends' => esc_html__( 'Trends', 'flavor-like' ),
		'Top content' => esc_html__( 'Top content', 'flavor-like' ),
		'positive' => esc_html__( 'positive', 'flavor-like' ),
		'Vote history · {{type}}' => esc_html__( 'Vote history · {{type}}', 'flavor-like' ),
		'Only {{ratio}}% positive this week —check Top content for items getting dislikes.' => esc_html__( 'Only {{ratio}}% positive this week —check Top content for items getting dislikes.', 'flavor-like' ),
		'Use Content intelligence to find the best publish times for your audience.' => esc_html__( 'Use Content intelligence to find the best publish times for your audience.', 'flavor-like' ),
		'{{likes}} likes this week —use Top content to find what resonates.' => esc_html__( '{{likes}} likes this week —use Top content to find what resonates.', 'flavor-like' ),
		'{{likes}} likes and {{dislikes}} dislikes —use Top content to find what resonates.' => esc_html__( '{{likes}} likes and {{dislikes}} dislikes —use Top content to find what resonates.', 'flavor-like' ),
		'Posts' => esc_html__( 'Posts', 'flavor-like' ),
		'Comments' => esc_html__( 'Comments', 'flavor-like' ),
		'Activities' => esc_html__( 'Activities', 'flavor-like' ),
		'Topics' => esc_html__( 'Topics', 'flavor-like' ),
		'Engagers' => esc_html__( 'Engagers', 'flavor-like' ),
		'Top members' => esc_html__( 'Top members', 'flavor-like' ),
		'Most active engagers recently' => esc_html__( 'Most active engagers recently', 'flavor-like' ),
		'Most active members recently' => esc_html__( 'Most active members recently', 'flavor-like' ),
		'Most active visitors recently' => esc_html__( 'Most active visitors recently', 'flavor-like' ),
		'{{count}} actions this week —reward top engagers to build loyalty.' => esc_html__( '{{count}} actions this week —reward top engagers to build loyalty.', 'flavor-like' ),
		'{{count}} actions this week —reward top members to build loyalty.' => esc_html__( '{{count}} actions this week —reward top members to build loyalty.', 'flavor-like' ),
		'{{count}} actions this week —see which visitors engage most often.' => esc_html__( '{{count}} actions this week —see which visitors engage most often.', 'flavor-like' ),
		'Like' => esc_html__( 'Like', 'flavor-like' ),
		'Unlike' => esc_html__( 'Unlike', 'flavor-like' ),
		'Dislike' => esc_html__( 'Dislike', 'flavor-like' ),
		'Undislike' => esc_html__( 'Undislike', 'flavor-like' ),
		'Status Filter' => esc_html__( 'Status Filter', 'flavor-like' ),
		'Status' => esc_html__( 'Status', 'flavor-like' ),
		'Date Range' => esc_html__( 'Date Range', 'flavor-like' ),
		'Start date' => esc_html__( 'Start date', 'flavor-like' ),
		'End date' => esc_html__( 'End date', 'flavor-like' ),
		'Select...' => esc_html__( 'Select...', 'flavor-like' ),
		'Content type' => esc_html__( 'Content type', 'flavor-like' ),
		'Content Types' => esc_html__( 'Content Types', 'flavor-like' ),
		'View By' => esc_html__( 'View By', 'flavor-like' ),
		'OS' => esc_html__( 'OS', 'flavor-like' ),
		'Browser' => esc_html__( 'Browser', 'flavor-like' ),
		'Post type' => esc_html__( 'Post type', 'flavor-like' ),
		'Taxonomy' => esc_html__( 'Taxonomy', 'flavor-like' ),
		'Sort by' => esc_html__( 'Sort by', 'flavor-like' ),
		'Highest first' => esc_html__( 'Highest first', 'flavor-like' ),
		'Lowest first' => esc_html__( 'Lowest first', 'flavor-like' ),
		'Search' => esc_html__( 'Search', 'flavor-like' ),
		'{{count}} selected' => esc_html__( '{{count}} selected', 'flavor-like' ),
		'Custom' => esc_html__( 'Custom', 'flavor-like' ),
		'This week' => esc_html__( 'This week', 'flavor-like' ),
		'Last week' => esc_html__( 'Last week', 'flavor-like' ),
		'Last {{count}} days' => esc_html__( 'Last {{count}} days', 'flavor-like' ),
		'Last {{count}} months' => esc_html__( 'Last {{count}} months', 'flavor-like' ),
		'This month' => esc_html__( 'This month', 'flavor-like' ),
		'Last month' => esc_html__( 'Last month', 'flavor-like' ),
		'Quarter to date' => esc_html__( 'Quarter to date', 'flavor-like' ),
		'This year' => esc_html__( 'This year', 'flavor-like' ),
		'Last calendar year' => esc_html__( 'Last calendar year', 'flavor-like' ),
		'Content' => esc_html__( 'Content', 'flavor-like' ),
		'Performance' => esc_html__( 'Performance', 'flavor-like' ),
		'Published' => esc_html__( 'Published', 'flavor-like' ),
		'Views' => esc_html__( 'Views', 'flavor-like' ),
		'Dislikes' => esc_html__( 'Dislikes', 'flavor-like' ),
		'Date' => esc_html__( 'Date', 'flavor-like' ),
		'User' => esc_html__( 'User', 'flavor-like' ),
		'IP' => esc_html__( 'IP', 'flavor-like' ),
		'Comment Author' => esc_html__( 'Comment Author', 'flavor-like' ),
		'Comment Content' => esc_html__( 'Comment Content', 'flavor-like' ),
		'Activity Title' => esc_html__( 'Activity Title', 'flavor-like' ),
		'Topic Title' => esc_html__( 'Topic Title', 'flavor-like' ),
		'Post Title' => esc_html__( 'Post Title', 'flavor-like' ),
		'Categories' => esc_html__( 'Categories', 'flavor-like' ),
		'Category' => esc_html__( 'Category', 'flavor-like' ),
		'Actions' => esc_html__( 'Actions', 'flavor-like' ),
		'Name' => esc_html__( 'Name', 'flavor-like' ),
		'Share' => esc_html__( 'Share', 'flavor-like' ),
		'Growth' => esc_html__( 'Growth', 'flavor-like' ),
		'Country' => esc_html__( 'Country', 'flavor-like' ),
		'Device' => esc_html__( 'Device', 'flavor-like' ),
		'Voters' => esc_html__( 'Voters', 'flavor-like' ),
		'User(s)' => esc_html__( 'User(s)', 'flavor-like' ),
		'Engaged Users' => esc_html__( 'Engaged Users', 'flavor-like' ),
		'Unique users' => esc_html__( 'Unique users', 'flavor-like' ),
		'Selected period' => esc_html__( 'Selected period', 'flavor-like' ),
		'Untitled' => esc_html__( 'Untitled', 'flavor-like' ),
		'Unknown User' => esc_html__( 'Unknown User', 'flavor-like' ),
		'Showing {{from}} to {{to}} of {{total}} results' => esc_html__( 'Showing {{from}} to {{to}} of {{total}} results', 'flavor-like' ),
		'per page' => esc_html__( 'per page', 'flavor-like' ),
		'Total records' => esc_html__( 'Total records', 'flavor-like' ),
		'Delete Selected' => esc_html__( 'Delete Selected', 'flavor-like' ),
		'Download CSV' => esc_html__( 'Download CSV', 'flavor-like' ),
		'Failed to delete the log entry.' => esc_html__( 'Failed to delete the log entry.', 'flavor-like' ),
		'An error occurred while deleting the log entry.' => esc_html__( 'An error occurred while deleting the log entry.', 'flavor-like' ),
		'No logs found for this category' => esc_html__( 'No logs found for this category', 'flavor-like' ),
		'No data for this period' => esc_html__( 'No data for this period', 'flavor-like' ),
		'Try changing your filters or search.' => esc_html__( 'Try changing your filters or search.', 'flavor-like' ),
		'Unable to load data. Please try again.' => esc_html__( 'Unable to load data. Please try again.', 'flavor-like' ),
		'Something went wrong' => esc_html__( 'Something went wrong', 'flavor-like' ),
		'Unable to load data. Refresh the page or contact support.' => esc_html__( 'Unable to load data. Refresh the page or contact support.', 'flavor-like' ),
		'Page Not Found' => esc_html__( 'Page Not Found', 'flavor-like' ),
		'This page does not exist or was moved.' => esc_html__( 'This page does not exist or was moved.', 'flavor-like' ),
		'No Data Available' => esc_html__( 'No Data Available', 'flavor-like' ),
		'No data yet. Records will appear here once engagement starts.' => esc_html__( 'No data yet. Records will appear here once engagement starts.', 'flavor-like' ),
		'Go to Home' => esc_html__( 'Go to Home', 'flavor-like' ),
		'Refresh Page' => esc_html__( 'Refresh Page', 'flavor-like' ),
		'User Engagement by Country' => esc_html__( 'User Engagement by Country', 'flavor-like' ),
		'Activity by country' => esc_html__( 'Activity by country', 'flavor-like' ),
		'Top countries' => esc_html__( 'Top countries', 'flavor-like' ),
		'No country data yet' => esc_html__( 'No country data yet', 'flavor-like' ),
		'{{country}} is your top market with {{share}}% of engaged voters.' => esc_html__( '{{country}} is your top market with {{share}}% of engaged voters.', 'flavor-like' ),
		'Performance snapshot' => esc_html__( 'Performance snapshot', 'flavor-like' ),
		'Engagement rate' => esc_html__( 'Engagement rate', 'flavor-like' ),
		'Positive sentiment' => esc_html__( 'Positive sentiment', 'flavor-like' ),
		'Total likes' => esc_html__( 'Total likes', 'flavor-like' ),
		'Engagement trend' => esc_html__( 'Engagement trend', 'flavor-like' ),
		'Button impressions' => esc_html__( 'Button impressions', 'flavor-like' ),
		'Total views' => esc_html__( 'Total views', 'flavor-like' ),
		'Reach' => esc_html__( 'Reach', 'flavor-like' ),
		'Daily activity' => esc_html__( 'Daily activity', 'flavor-like' ),
		'Likes this month' => esc_html__( 'Likes this month', 'flavor-like' ),
		'Interactions this month' => esc_html__( 'Interactions this month', 'flavor-like' ),
		'Likes per view' => esc_html__( 'Likes per view', 'flavor-like' ),
		'Likes + dislikes per view' => esc_html__( 'Likes + dislikes per view', 'flavor-like' ),
		'Like vs dislike ratio' => esc_html__( 'Like vs dislike ratio', 'flavor-like' ),
		'Like-only template' => esc_html__( 'Like-only template', 'flavor-like' ),
		'{{likes}} likes · {{dislikes}} dislikes' => esc_html__( '{{likes}} likes · {{dislikes}} dislikes', 'flavor-like' ),
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
		'When to publish' => esc_html__( 'When to publish', 'flavor-like' ),
		'When your audience is most likely to react.' => esc_html__( 'When your audience is most likely to react.', 'flavor-like' ),
		'Sweet spot' => esc_html__( 'Sweet spot', 'flavor-like' ),
		'Peak' => esc_html__( 'Peak', 'flavor-like' ),
		'Hourly pattern' => esc_html__( 'Hourly pattern', 'flavor-like' ),
		'Full report' => esc_html__( 'Full report', 'flavor-like' ),
		'{{day}} · {{time}}' => esc_html__( '{{day}} · {{time}}', 'flavor-like' ),
		'{{share}}% of weekly activity' => esc_html__( '{{share}}% of weekly activity', 'flavor-like' ),
		'{{share}}% of all activity · {{range}}' => esc_html__( '{{share}}% of all activity · {{range}}', 'flavor-like' ),
		'{{count}} engagements in this slot' => esc_html__( '{{count}} engagements in this slot', 'flavor-like' ),
		'Activity heatmap' => esc_html__( 'Activity heatmap', 'flavor-like' ),
		'Engagement by day and hour' => esc_html__( 'Engagement by day and hour', 'flavor-like' ),
		'When your audience engages' => esc_html__( 'When your audience engages', 'flavor-like' ),
		'Time windows' => esc_html__( 'Time windows', 'flavor-like' ),
		'Best day(s)' => esc_html__( 'Best day(s)', 'flavor-like' ),
		'Share of weekly activity' => esc_html__( 'Share of weekly activity', 'flavor-like' ),
		'Best hour' => esc_html__( 'Best hour', 'flavor-like' ),
		'Best hours to post' => esc_html__( 'Best hours to post', 'flavor-like' ),
		'Category performance' => esc_html__( 'Category performance', 'flavor-like' ),
		'Engagements' => esc_html__( 'Engagements', 'flavor-like' ),
		'Hour' => esc_html__( 'Hour', 'flavor-like' ),
		'Top categories' => esc_html__( 'Top categories', 'flavor-like' ),
		'Shop spotlight' => esc_html__( 'Shop spotlight', 'flavor-like' ),
		'Less' => esc_html__( 'Less', 'flavor-like' ),
		'More' => esc_html__( 'More', 'flavor-like' ),
		'Publish on {{day}} around {{time}} for maximum engagement.' => esc_html__( 'Publish on {{day}} around {{time}} for maximum engagement.', 'flavor-like' ),
		'Your audience is most active in the {{window}} window ({{range}}).' => esc_html__( 'Your audience is most active in the {{window}} window ({{range}}).', 'flavor-like' ),
		'{{share}}% of engagers use mobile —optimize for small screens.' => esc_html__( '{{share}}% of engagers use mobile —optimize for small screens.', 'flavor-like' ),
		'{{share}}% engage from desktop —long-form content may perform better.' => esc_html__( '{{share}}% engage from desktop —long-form content may perform better.', 'flavor-like' ),
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
		'Average product and review reactions per order —a pre-purchase interest signal.' => esc_html__( 'Average product and review reactions per order —a pre-purchase interest signal.', 'flavor-like' ),
		'Engagement per order' => esc_html__( 'Engagement per order', 'flavor-like' ),
		'Revenue generated per like or dislike —helps compare campaign ROI.' => esc_html__( 'Revenue generated per like or dislike —helps compare campaign ROI.', 'flavor-like' ),
		'Revenue per engagement' => esc_html__( 'Revenue per engagement', 'flavor-like' ),
		'Engagement vs orders trend' => esc_html__( 'Engagement vs orders trend', 'flavor-like' ),
		'Daily product reactions compared with store orders' => esc_html__( 'Daily product reactions compared with store orders', 'flavor-like' ),
		'Look for days when engagement rises before orders —a sign your social proof is working.' => esc_html__( 'Look for days when engagement rises before orders —a sign your social proof is working.', 'flavor-like' ),
		'Top products' => esc_html__( 'Top products', 'flavor-like' ),
		'Engagement and sales side by side' => esc_html__( 'Engagement and sales side by side', 'flavor-like' ),
		'No product engagement yet' => esc_html__( 'No product engagement yet', 'flavor-like' ),
		'Product' => esc_html__( 'Product', 'flavor-like' ),
		'Match score' => esc_html__( 'Match score', 'flavor-like' ),
		'Opportunities' => esc_html__( 'Opportunities', 'flavor-like' ),
		'Where engagement and sales diverge' => esc_html__( 'Where engagement and sales diverge', 'flavor-like' ),
		'High interest, lower sales —optimize merchandising, pricing, or checkout.' => esc_html__( 'High interest, lower sales —optimize merchandising, pricing, or checkout.', 'flavor-like' ),
		'Strong sellers with few likes —surface social proof with like buttons or badges.' => esc_html__( 'Strong sellers with few likes —surface social proof with like buttons or badges.', 'flavor-like' ),
		'Engagement and revenue by product category' => esc_html__( 'Engagement and revenue by product category', 'flavor-like' ),
		'Engagement vs sales intelligence' => esc_html__( 'Engagement vs sales intelligence', 'flavor-like' ),
		'See how product likes and review reactions relate to orders and revenue —available in Pro.' => esc_html__( 'See how product likes and review reactions relate to orders and revenue —available in Pro.', 'flavor-like' ),
		'Actionable recommendations based on your data' => esc_html__( 'Actionable recommendations based on your data', 'flavor-like' ),
		'Best time to publish' => esc_html__( 'Best time to publish', 'flavor-like' ),
		'{{day}} around {{time}} gets the most engagement —schedule content then.' => esc_html__( '{{day}} around {{time}} gets the most engagement —schedule content then.', 'flavor-like' ),
		'{{category}} drives {{share}}% of engagement —create more on this topic.' => esc_html__( '{{category}} drives {{share}}% of engagement —create more on this topic.', 'flavor-like' ),
		'Low conversion' => esc_html__( 'Low conversion', 'flavor-like' ),
		'Only {{rate}}% of viewers engage —improve button placement and CTAs.' => esc_html__( 'Only {{rate}}% of viewers engage —improve button placement and CTAs.', 'flavor-like' ),
		'Momentum' => esc_html__( 'Momentum', 'flavor-like' ),
		'Sentiment drop' => esc_html__( 'Sentiment drop', 'flavor-like' ),
		'Positive reactions fell to {{ratio}}% —review content getting dislikes.' => esc_html__( 'Positive reactions fell to {{ratio}}% —review content getting dislikes.', 'flavor-like' ),
		'{{title}} leads with {{count}} likes in this period.' => esc_html__( '{{title}} leads with {{count}} likes in this period.', 'flavor-like' ),
		'{{title}} converts best at {{rate}}% —replicate this format.' => esc_html__( '{{title}} converts best at {{rate}}% —replicate this format.', 'flavor-like' ),
		'{{name}} is your most active user —consider a loyalty perk.' => esc_html__( '{{name}} is your most active user —consider a loyalty perk.', 'flavor-like' ),
		'Refresh data' => esc_html__( 'Refresh data', 'flavor-like' ),
		'Total engagements' => esc_html__( 'Total engagements', 'flavor-like' ),
		'Distinct people who engaged in this period.' => esc_html__( 'Distinct people who engaged in this period.', 'flavor-like' ),
		'Current period' => esc_html__( 'Current period', 'flavor-like' ),
		'Previous period' => esc_html__( 'Previous period', 'flavor-like' ),
		'Likes' => esc_html__( 'Likes', 'flavor-like' ),
		'Interactions' => esc_html__( 'Interactions', 'flavor-like' ),
		'Impressions' => esc_html__( 'Impressions', 'flavor-like' ),
		'Dismiss' => esc_html__( 'Dismiss', 'flavor-like' ),
		'New Notifications' => esc_html__( 'New Notifications', 'flavor-like' ),
		'No new notifications' => esc_html__( 'No new notifications', 'flavor-like' ),
		'{{current}} of {{total}}' => esc_html__( '{{current}} of {{total}}', 'flavor-like' ),
		'License Not Found!' => esc_html__( 'License Not Found!', 'flavor-like' ),
		'Your license is invalid or missing. Enter a valid key or purchase Pro to continue.' => esc_html__( 'Your license is invalid or missing. Enter a valid key or purchase Pro to continue.', 'flavor-like' ),
		'Get License' => esc_html__( 'Get License', 'flavor-like' ),
		'Upgrade to Pro' => esc_html__( 'Upgrade to Pro', 'flavor-like' ),
		'Learn more' => esc_html__( 'Learn more', 'flavor-like' ),
		'Pro' => esc_html__( 'Pro', 'flavor-like' ),
		'Settings' => esc_html__( 'Settings', 'flavor-like' ),
		'Help' => esc_html__( 'Help', 'flavor-like' ),
		'Get Pro' => esc_html__( 'Get Pro', 'flavor-like' ),
		'Get Pro reports' => esc_html__( 'Get Pro reports', 'flavor-like' ),
		'Unlock live audience maps, top fans, and publishing insights with Pro.' => esc_html__( 'Unlock live audience maps, top fans, and publishing insights with Pro.', 'flavor-like' ),
		'{{count}} engagements so far —unlock audience maps, top fans, and publishing insights.' => esc_html__( '{{count}} engagements so far —unlock audience maps, top fans, and publishing insights.', 'flavor-like' ),
		'Minimize Pro promo' => esc_html__( 'Minimize Pro promo', 'flavor-like' ),
		'Find your best posting times and top-performing topics' => esc_html__( 'Find your best posting times and top-performing topics', 'flavor-like' ),
		'Learn which devices and browsers your voters use' => esc_html__( 'Learn which devices and browsers your voters use', 'flavor-like' ),
		'Sidebar Pro card' => esc_html__( 'Sidebar Pro card', 'flavor-like' ),
		'Minimized to —淕et Pro—in the sidebar.' => esc_html__( 'Minimized to —淕et Pro—in the sidebar.', 'flavor-like' ),
		'Full card visible in the sidebar.' => esc_html__( 'Full card visible in the sidebar.', 'flavor-like' ),
		'Show full card' => esc_html__( 'Show full card', 'flavor-like' ),
		'Milestone' => esc_html__( 'Milestone', 'flavor-like' ),
		'You have passed {{count}} total engagements —{{remaining}} to go until {{next}}.' => esc_html__( 'You have passed {{count}} total engagements —{{remaining}} to go until {{next}}.', 'flavor-like' ),
		'Your community has reached {{count}} total engagements!' => esc_html__( 'Your community has reached {{count}} total engagements!', 'flavor-like' ),
		'Total' => esc_html__( 'Total', 'flavor-like' ),
		'Engagement is up {{percent}}% compared to last week.' => esc_html__( 'Engagement is up {{percent}}% compared to last week.', 'flavor-like' ),
		'Engagement is down {{percent}}% compared to last week.' => esc_html__( 'Engagement is down {{percent}}% compared to last week.', 'flavor-like' ),
		'Getting started' => esc_html__( 'Getting started', 'flavor-like' ),
		'{{count}} engagements so far —{{remaining}} more to reach {{target}}.' => esc_html__( '{{count}} engagements so far —{{remaining}} more to reach {{target}}.', 'flavor-like' ),
		'{{count}} engagements today —keep it going.' => esc_html__( '{{count}} engagements today —keep it going.', 'flavor-like' ),
		'No engagements yet today —yesterday had {{count}}.' => esc_html__( 'No engagements yet today —yesterday had {{count}}.', 'flavor-like' ),
		'Engagement is up {{percent}}% compared to yesterday.' => esc_html__( 'Engagement is up {{percent}}% compared to yesterday.', 'flavor-like' ),
		'Engagement is down {{percent}}% compared to yesterday.' => esc_html__( 'Engagement is down {{percent}}% compared to yesterday.', 'flavor-like' ),
		'Around {{time}} gets the most engagement —schedule content then.' => esc_html__( 'Around {{time}} gets the most engagement —schedule content then.', 'flavor-like' ),
		'A quick look at where your audience engages from.' => esc_html__( 'A quick look at where your audience engages from.', 'flavor-like' ),
		'See full map in Pro' => esc_html__( 'See full map in Pro', 'flavor-like' ),
		'Explore' => esc_html__( 'Explore', 'flavor-like' ),
		'Preferences for your stats dashboard experience.' => esc_html__( 'Preferences for your stats dashboard experience.', 'flavor-like' ),
		'Announcements' => esc_html__( 'Announcements', 'flavor-like' ),
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
		'Appearance' => esc_html__( 'Appearance', 'flavor-like' ),
		'Theme preference is stored locally in this browser, not in your WordPress user account.' => esc_html__( 'Theme preference is stored locally in this browser, not in your WordPress user account.', 'flavor-like' ),
		'Publishing schedule, category insights, and commerce analytics —available in Pro.' => esc_html__( 'Publishing schedule, category insights, and commerce analytics —available in Pro.', 'flavor-like' ),
		'Publishing schedule and category insights —available in Pro.' => esc_html__( 'Publishing schedule and category insights —available in Pro.', 'flavor-like' ),
		'See where your audience engages from with country breakdowns and trends.' => esc_html__( 'See where your audience engages from with country breakdowns and trends.', 'flavor-like' ),
		'Device, OS, and browser breakdowns for every vote.' => esc_html__( 'Device, OS, and browser breakdowns for every vote.', 'flavor-like' ),
		'Discover your most active members and reward loyal engagers.' => esc_html__( 'Discover your most active members and reward loyal engagers.', 'flavor-like' ),
		'See exactly who engaged with each piece of content.' => esc_html__( 'See exactly who engaged with each piece of content.', 'flavor-like' ),
		'Users who engaged with this content' => esc_html__( 'Users who engaged with this content', 'flavor-like' ),
		'Member' => esc_html__( 'Member', 'flavor-like' ),
		'Last active' => esc_html__( 'Last active', 'flavor-like' ),
		'Reactions' => esc_html__( 'Reactions', 'flavor-like' ),
		'Back to engagement' => esc_html__( 'Back to engagement', 'flavor-like' ),
		'No registered engagers yet' => esc_html__( 'No registered engagers yet', 'flavor-like' ),
		'No registered engagers for {{title}}' => esc_html__( 'No registered engagers for {{title}}', 'flavor-like' ),
		'Guest votes are counted in totals but only registered members appear here.' => esc_html__( 'Guest votes are counted in totals but only registered members appear here.', 'flavor-like' ),
		'Like / Dislike Buttons' => esc_html__( 'Like / Dislike Buttons', 'flavor-like' ),
		'{{count}} engaged users' => esc_html__( '{{count}} engaged users', 'flavor-like' ),
		'{{count}} users engaged with {{title}}' => esc_html__( '{{count}} users engaged with {{title}}', 'flavor-like' ),
		'{{count}} engager(s)' => esc_html__( '{{count}} engager(s)', 'flavor-like' ),
		'No engagers' => esc_html__( 'No engagers', 'flavor-like' ),
		'No engagers yet' => esc_html__( 'No engagers yet', 'flavor-like' ),
		'{{count}} total' => esc_html__( '{{count}} total', 'flavor-like' ),
		'Totals and charts for {{type}}' => esc_html__( 'Totals and charts for {{type}}', 'flavor-like' ),
		'Trends and top content for {{type}}' => esc_html__( 'Trends and top content for {{type}}', 'flavor-like' ),
		'Morning' => esc_html__( 'Morning', 'flavor-like' ),
		'Afternoon' => esc_html__( 'Afternoon', 'flavor-like' ),
		'Evening' => esc_html__( 'Evening', 'flavor-like' ),
		'Night' => esc_html__( 'Night', 'flavor-like' ),
		'Close' => esc_html__( 'Close', 'flavor-like' ),
		'See who' => esc_html__( 'See who', 'flavor-like' ),
		'Ratings' => esc_html__( 'Ratings', 'flavor-like' ),
		'Average rating' => esc_html__( 'Average rating', 'flavor-like' ),
		'Star Rating' => esc_html__( 'Star Rating', 'flavor-like' ),
		'Star rating template' => esc_html__( 'Star rating template', 'flavor-like' ),
		'Star ratings in this period.' => esc_html__( 'Star ratings in this period.', 'flavor-like' ),
		'Emoji Reactions' => esc_html__( 'Emoji Reactions', 'flavor-like' ),
		'Emoji template' => esc_html__( 'Emoji template', 'flavor-like' ),
		'Emoji reactions in this period.' => esc_html__( 'Emoji reactions in this period.', 'flavor-like' ),
		'Reactions are up {{percent}}% compared to last week.' => esc_html__( 'Reactions are up {{percent}}% compared to last week.', 'flavor-like' ),
		'Ratings are up {{percent}}% compared to last week.' => esc_html__( 'Ratings are up {{percent}}% compared to last week.', 'flavor-like' ),
		'{{count}} emoji reactions this week —use Top content to find what resonates.' => esc_html__( '{{count}} emoji reactions this week —use Top content to find what resonates.', 'flavor-like' ),
		'{{count}} star ratings this week —use Top content to find what resonates.' => esc_html__( '{{count}} star ratings this week —use Top content to find what resonates.', 'flavor-like' ),
		'{{name}} is your top rater —consider a loyalty perk.' => esc_html__( '{{name}} is your top rater —consider a loyalty perk.', 'flavor-like' ),
		'{{title}} averages 鈽?{{avg}} —replicate this format.' => esc_html__( '{{title}} averages 鈽?{{avg}} —replicate this format.', 'flavor-like' ),
		'{{title}} leads with {{count}} reactions in this period.' => esc_html__( '{{title}} leads with {{count}} reactions in this period.', 'flavor-like' ),
		'鈽?{{avg}} site average' => esc_html__( '鈽?{{avg}} site average', 'flavor-like' ),
		'Device Insights' => esc_html__( 'Device Insights', 'flavor-like' ),
		'Intelligence Report' => esc_html__( 'Intelligence Report', 'flavor-like' ),
		'WooCommerce Report' => esc_html__( 'WooCommerce Report', 'flavor-like' ),
		'Upgrade like storage' => esc_html__( 'Upgrade like storage', 'flavor-like' ),
		'Unable to load data' => esc_html__( 'Unable to load data', 'flavor-like' ),
		'Please refresh the page or try again later.' => esc_html__( 'Please refresh the page or try again later.', 'flavor-like' ),
		'timeAgo' => esc_html__( 'timeAgo', 'flavor-like' ),
		'year' => esc_html__( 'year', 'flavor-like' ),
		'year_plural' => esc_html__( 'year_plural', 'flavor-like' ),
		'month' => esc_html__( 'month', 'flavor-like' ),
		'month_plural' => esc_html__( 'month_plural', 'flavor-like' ),
		'week' => esc_html__( 'week', 'flavor-like' ),
		'week_plural' => esc_html__( 'week_plural', 'flavor-like' ),
		'day' => esc_html__( 'day', 'flavor-like' ),
		'day_plural' => esc_html__( 'day_plural', 'flavor-like' ),
		'hour' => esc_html__( 'hour', 'flavor-like' ),
		'hour_plural' => esc_html__( 'hour_plural', 'flavor-like' ),
		'minute' => esc_html__( 'minute', 'flavor-like' ),
		'minute_plural' => esc_html__( 'minute_plural', 'flavor-like' ),
		'second' => esc_html__( 'second', 'flavor-like' ),
		'second_plural' => esc_html__( 'second_plural', 'flavor-like' ),
		'just now' => esc_html__( 'just now', 'flavor-like' ),
		'共 {{total}} 次互动 · 今日 {{today}} 次' => esc_html__( '共 {{total}} 次互动 · 今日 {{today}} 次', 'flavor-like' ),
		'Vote history · {{type}}' => esc_html__( 'Vote history · {{type}}', 'flavor-like' ),
		'{{likes}} likes · {{dislikes}} dislikes' => esc_html__( '{{likes}} likes · {{dislikes}} dislikes', 'flavor-like' ),
		'{{day}} · {{time}}' => esc_html__( '{{day}} · {{time}}', 'flavor-like' ),
		'{{share}}% of all activity · {{range}}' => esc_html__( '{{share}}% of all activity · {{range}}', 'flavor-like' ),
		'No engagements yet today — yesterday had {{count}}.' => esc_html__( 'No engagements yet today — yesterday had {{count}}.', 'flavor-like' ),
		'{{count}} engagements so far — {{remaining}} more to reach {{target}}.' => esc_html__( '{{count}} engagements so far — {{remaining}} more to reach {{target}}.', 'flavor-like' ),
		'{{count}} engagements today — keep it going.' => esc_html__( '{{count}} engagements today — keep it going.', 'flavor-like' ),
		'Around {{time}} gets the most engagement — schedule content then.' => esc_html__( 'Around {{time}} gets the most engagement — schedule content then.', 'flavor-like' ),
		'{{day}} around {{time}} gets the most engagement — schedule content then.' => esc_html__( '{{day}} around {{time}} gets the most engagement — schedule content then.', 'flavor-like' ),
		'{{category}} drives {{share}}% of engagement — create more on this topic.' => esc_html__( '{{category}} drives {{share}}% of engagement — create more on this topic.', 'flavor-like' ),
		'Only {{rate}}% of viewers engage — improve button placement and CTAs.' => esc_html__( 'Only {{rate}}% of viewers engage — improve button placement and CTAs.', 'flavor-like' ),
		'Positive reactions fell to {{ratio}}% — review content getting dislikes.' => esc_html__( 'Positive reactions fell to {{ratio}}% — review content getting dislikes.', 'flavor-like' ),
		'{{title}} converts best at {{rate}}% — replicate this format.' => esc_html__( '{{title}} converts best at {{rate}}% — replicate this format.', 'flavor-like' ),
		'{{name}} is your most active user — consider a loyalty perk.' => esc_html__( '{{name}} is your most active user — consider a loyalty perk.', 'flavor-like' ),
		's engaging most' => esc_html__( 's engaging most', 'flavor-like' ),
		'{{count}} engagements so far — unlock audience maps, top fans, and publishing insights.' => esc_html__( '{{count}} engagements so far — unlock audience maps, top fans, and publishing insights.', 'flavor-like' ),
		'Minimized to “Get Pro” in the sidebar.' => esc_html__( 'Minimized to “Get Pro” in the sidebar.', 'flavor-like' ),
		'You have passed {{count}} total engagements — {{remaining}} to go until {{next}}.' => esc_html__( 'You have passed {{count}} total engagements — {{remaining}} to go until {{next}}.', 'flavor-like' ),
		'Only {{ratio}}% positive this week — check Top content for items getting dislikes.' => esc_html__( 'Only {{ratio}}% positive this week — check Top content for items getting dislikes.', 'flavor-like' ),
		'{{likes}} likes this week — use Top content to find what resonates.' => esc_html__( '{{likes}} likes this week — use Top content to find what resonates.', 'flavor-like' ),
		'{{likes}} likes and {{dislikes}} dislikes — use Top content to find what resonates.' => esc_html__( '{{likes}} likes and {{dislikes}} dislikes — use Top content to find what resonates.', 'flavor-like' ),
		'{{count}} actions this week — reward top engagers to build loyalty.' => esc_html__( '{{count}} actions this week — reward top engagers to build loyalty.', 'flavor-like' ),
		'{{count}} actions this week — reward top members to build loyalty.' => esc_html__( '{{count}} actions this week — reward top members to build loyalty.', 'flavor-like' ),
		'{{count}} actions this week — see which visitors engage most often.' => esc_html__( '{{count}} actions this week — see which visitors engage most often.', 'flavor-like' ),
		'{{share}}% of engagers use mobile — optimize for small screens.' => esc_html__( '{{share}}% of engagers use mobile — optimize for small screens.', 'flavor-like' ),
		'{{share}}% engage from desktop — long-form content may perform better.' => esc_html__( '{{share}}% engage from desktop — long-form content may perform better.', 'flavor-like' ),
		'Average product and review reactions per order — a pre-purchase interest signal.' => esc_html__( 'Average product and review reactions per order — a pre-purchase interest signal.', 'flavor-like' ),
		'Revenue generated per like or dislike — helps compare campaign ROI.' => esc_html__( 'Revenue generated per like or dislike — helps compare campaign ROI.', 'flavor-like' ),
		'Look for days when engagement rises before orders — a sign your social proof is working.' => esc_html__( 'Look for days when engagement rises before orders — a sign your social proof is working.', 'flavor-like' ),
		'High interest, lower sales — optimize merchandising, pricing, or checkout.' => esc_html__( 'High interest, lower sales — optimize merchandising, pricing, or checkout.', 'flavor-like' ),
		'Strong sellers with few likes — surface social proof with like buttons or badges.' => esc_html__( 'Strong sellers with few likes — surface social proof with like buttons or badges.', 'flavor-like' ),
		'See how product likes and review reactions relate to orders and revenue — available in Pro.' => esc_html__( 'See how product likes and review reactions relate to orders and revenue — available in Pro.', 'flavor-like' ),
		'Publishing schedule, category insights, and commerce analytics — available in Pro.' => esc_html__( 'Publishing schedule, category insights, and commerce analytics — available in Pro.', 'flavor-like' ),
		'Publishing schedule and category insights — available in Pro.' => esc_html__( 'Publishing schedule and category insights — available in Pro.', 'flavor-like' ),
		'{{count}} emoji reactions this week — use Top content to find what resonates.' => esc_html__( '{{count}} emoji reactions this week — use Top content to find what resonates.', 'flavor-like' ),
		'{{count}} star ratings this week — use Top content to find what resonates.' => esc_html__( '{{count}} star ratings this week — use Top content to find what resonates.', 'flavor-like' ),
		'{{name}} is your top rater — consider a loyalty perk.' => esc_html__( '{{name}} is your top rater — consider a loyalty perk.', 'flavor-like' ),
		'{{title}} averages — {{avg}} — replicate this format.' => esc_html__( '{{title}} averages — {{avg}} — replicate this format.', 'flavor-like' ),
		'— {{avg}} site average' => esc_html__( '— {{avg}} site average', 'flavor-like' ),
		'timeAgo' => esc_html__( 'timeAgo', 'flavor-like' ),
		'year_plural' => esc_html__( 'year_plural', 'flavor-like' ),
		'month_plural' => esc_html__( 'month_plural', 'flavor-like' ),
		'week_plural' => esc_html__( 'week_plural', 'flavor-like' ),
		'day_plural' => esc_html__( 'day_plural', 'flavor-like' ),
		'hour_plural' => esc_html__( 'hour_plural', 'flavor-like' ),
		'minute_plural' => esc_html__( 'minute_plural', 'flavor-like' ),
		'second_plural' => esc_html__( 'second_plural', 'flavor-like' ),
	);

	$final_strings = array();
	foreach ( $raw_strings as $key => $val ) {
		// Template variables
		if ( '{{site_name}}' === $key || '{{language}}' === $key || '{{display_name}}' === $key ) {
			$final_strings[ $key ] = $val;
			continue;
		}
		// If untranslated or in Chinese locale, prioritize Chinese fallback
		if ( ( $val === $key || empty( $val ) || $is_zh ) && isset( $zh_dict[ $key ] ) ) {
			$final_strings[ $key ] = $zh_dict[ $key ];
		} else {
			$final_strings[ $key ] = $val;
		}
	}

	// Also ensure all Chinese keys map directly to themselves for direct lookup
	foreach ( $zh_dict as $en_k => $zh_v ) {
		if ( ! isset( $final_strings[ $en_k ] ) ) {
			$final_strings[ $en_k ] = $zh_v;
		}
		$final_strings[ $zh_v ] = $zh_v;
	}

	wp_send_json( $final_strings );
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

