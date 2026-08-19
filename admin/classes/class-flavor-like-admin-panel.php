<?php
/**
 * Wp Flavor Like Admin Panel
 * // @echo HEADER
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'flavor_like_admin_panel' ) ) {
    class flavor_like_admin_panel{

        protected $option_domain = 'flavor_like_settings';
        protected $sections_cache = null;

		/**
		 * __construct
		 */
		function __construct() {
            // No framework dependencies - just initialize
        }

        /**
         * Register setting sections
         * Returns array structure for API consumption
         *
         * @return array Sections structure
         */
        public function register_sections(){
            // Return cached sections if available
            if ( $this->sections_cache !== null ) {
                return $this->sections_cache;
            }

            do_action( 'flavor_like_panel_sections_started' );

            $sections = array();

            /**
             * Configuration Section
             */
            $sections[] = array(
                'id'    => 'configuration',
                'title' => esc_html__( 'Configuration','flavor-like'),
                'icon'  => 'cog-6-tooth',
            );

            // General
            $sections[] = array(
                'id'     => 'general',
                'parent' => 'configuration',
                'title'  => esc_html__( 'General','flavor-like'),
                'icon'   => 'adjustments-horizontal',
                'fields' => apply_filters( 'flavor_like_panel_general', array_merge( array(
                    array(
                        'id'      => 'enable_toast_notice',
                        'type'    => 'switcher',
                        'title'   => esc_html__('In-app Notifications', 'flavor-like'),
                        'default' => true,
                        'desc'    => '用户点赞/取消赞后显示简短确认消息。'
                    ),
                    array(
                        'id'          => 'filter_toast_types',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Disable Notifications On','flavor-like' ),
                        'desc'        => '选择禁用通知的位置。',
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
                            'post'     => esc_html__('Posts', 'flavor-like'),
                            'comment'  => esc_html__('Comments', 'flavor-like'),
                            'activity' => esc_html__('Activities', 'flavor-like'),
                            'topic'    => esc_html__('Topics', 'flavor-like')
                        ),
                        'dependency'=> array( 'enable_toast_notice', '==', 'true' ),
                    ),
                    array(
                        'id'      => 'enable_kilobyte_format',
                        'type'    => 'switcher',
                        'title'   => esc_html__('Compact Counter (1k, 1.2k)', 'flavor-like'),
                        'default' => false,
                        'desc'    => '以简短格式显示大量赞数。示例：1250 → 1.2k（适用于 1,000+）。'
                    ),
                    array(
                        'id'            => 'filter_counter_value',
                        'type'          => 'tabbed',
                        'desc'          => '在计数器值前后添加文本。',
                        'title'         => esc_html__( 'Counter Prefix & Suffix', 'flavor-like'),
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__('Prefix','flavor-like'),
                                'fields'    => apply_filters( 'flavor_like_filter_counter_options', array(
                                    array(
                                        'id'      => 'like_prefix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Like Prefix','flavor-like'),
                                        'desc'    => '显示在计数前的文本（例如 "+" 显示为 "+125"）。',
                                        'default' => '+'
                                    ),
                                    array(
                                        'id'      => 'unlike_prefix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Unlike Prefix','flavor-like'),
                                        'desc'    => '显示在计数前的文本（例如 "+" 显示为 "+125"）。',
                                        'default' => '+'
                                    ),
                                ), 'prefix' )
                            ),
                            array(
                                'title'     => esc_html__('Suffix','flavor-like'),
                                'fields'    => apply_filters( 'flavor_like_filter_counter_options', array(
                                    array(
                                        'id'      => 'like_postfix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Like Suffix','flavor-like'),
                                        'desc'    => '显示在计数后的文本（例如 " likes" 显示为 "125 likes"）。'
                                    ),
                                    array(
                                        'id'      => 'unlike_postfix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Unlike Suffix','flavor-like'),
                                        'desc'    => '显示在计数后的文本（例如 " likes" 显示为 "125 likes"）。'
                                    ),
                                ), 'postfix' )
                            ),
                        )
                    ),
                    array(
                        'id'    => 'enable_anonymise_ip',
                        'type'  => 'switcher',
                        'title' => esc_html__('Anonymize IP Addresses', 'flavor-like'),
                        'desc'  => '屏蔽部分 IP 地址，使投票无法追溯到个人用户。'
                    ),
                    array(
                        'id'         => 'disable_ip_logging',
                        'type'       => 'switcher',
                        'title'      => esc_html__('Do Not Store IPs', 'flavor-like'),
                        'desc'       => '停止在数据库中保存用户 IP 地址。',
                        'dependency' => array( 'enable_anonymise_ip', '==', 'true' ),
                    ),
                    array(
                        'id'          => 'trusted_proxy_ips',
                        'type'        => 'textarea',
                        'title'       => esc_html__('Trusted Proxy IPs', 'flavor-like'),
                        'desc'        => '每行一个反向代理 / 负载均衡的 IP 或 IPv4 CIDR（如 10.0.0.0/8）。配置后，仅当请求确实来自这些地址时才读取 X-Forwarded-For 等代理头，防止伪造 IP 绕过投票去重与黑名单。留空则维持旧行为（始终信任代理头）。',
                        'placeholder' => "10.0.0.0/8\n172.16.0.1",
                        'sanitize'    => 'sanitize_textarea_field',
                    ),
                    array(
                        'id'    => 'cache_exist',
                        'type'  => 'switcher',
                        'title' => esc_html__('Site Uses Caching', 'flavor-like'),
                        'desc'  => '如果您的站点使用缓存插件或服务，请启用此选项。'
                    ),
                    array(
                        'id'          => 'disable_plugin_files',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Disable Assets On','flavor-like'),
                        'desc'        => '阻止在选定页面类型上加载插件 CSS/JS。',
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
                            'home'        => esc_html__('Home', 'flavor-like'),
                            'single'      => esc_html__('Singular', 'flavor-like'),
                            'archive'     => esc_html__('Archives', 'flavor-like'),
                            'category'    => esc_html__('Categories', 'flavor-like'),
                            'search'      => esc_html__('Search Results', 'flavor-like'),
                            'tag'         => esc_html__('Tags', 'flavor-like'),
                            'author'      => esc_html__('Author Page', 'flavor-like'),
                            'buddypress'  => esc_html__('BuddyPress Pages', 'flavor-like'),
                            'bbpress'     => esc_html__('bbPress Pages', 'flavor-like'),
                            'woocommerce' => esc_html__('WooCommerce Pages', 'flavor-like')
                        )
                    ),
                    array(
                        'id'      => 'assets_load_strategy',
                        'type'    => 'select',
                        'title'   => esc_html__( 'Assets Load Strategy','flavor-like'),
                        'desc'    => '资源加载策略：全局加载（默认，兼容所有场景）或按需加载（仅当页面包含点赞按钮时才输出 CSS/JS，可减少无关节页面的资源开销）。',
                        'options' => array(
                            'global'    => esc_html__('Load Globally (default)', 'flavor-like'),
                            'on_demand' => esc_html__('Load On Demand', 'flavor-like'),
                        ),
                        'default' => 'global',
                    ),
                    array(
                        'id'    => 'disable_admin_notice',
                        'type'  => 'switcher',
                        'title' => esc_html__('Hide Plugin Admin Notices', 'flavor-like'),
                        'desc'  => '对所有用户完全隐藏插件管理通知。'
                    ),
                    array(
                        'id'          => 'enable_admin_posts_columns',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Show Admin Columns','flavor-like' ),
                        'desc'        => '在管理列表中添加赞数列。',
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => 'post_types'
                    ),
                    array(
                        'id'          => 'blacklist_integration',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Blacklist','flavor-like'),
                        'options'     => array(
                            'default'  => '使用 Flavor Like 黑名单',
                            'comments' => '使用 WordPress 评论禁止关键词'
                        ),
                        'default'     => 'default',
                        'desc'        => '选择用于投票的黑名单。<a href="'.admin_url('options-discussion.php').'">评论禁止关键词</a>设置在 WordPress 讨论设置页面中可用。'
                    ),
                    array(
                        'id'         => 'blacklist_entries',
                        'type'       => 'textarea',
                        'title'      => esc_html__( 'Blacklist Entries', 'flavor-like'),
                        'desc'       => '每行输入一个 IP 地址。来自匹配 IP 的投票将被拒绝。',
                        'dependency' => array( 'blacklist_integration', 'any', 'default' )
                    )
                ), $this->get_legacy_integration_fields() ) )
            );

            // Get all content options
            $get_content_options = apply_filters( 'flavor_like_panel_content_options', $this->get_content_options() );
            $get_content_fields  = array();

            // Generate posts fields
            $get_content_fields['posts']    = $get_content_options;

            if( flavor_like_is_wpml_active() ){
                $get_content_fields['posts']['enable_wpml_synchronization'] = array(
                    'id'    => 'enable_wpml_synchronization',
                    'type'  => 'switcher',
                    'title' => esc_html__('Enable WPML Synchronization', 'flavor-like'),
                    'desc'  => '使用 WPML 插件时同步翻译文章类型的赞。'
                );
            }


            // Generate comment fields
            $get_content_fields['comments'] = $get_content_options;
            unset( $get_content_fields['comments']['auto_display_on_excerpts'] );
            unset( $get_content_fields['comments']['auto_display_filter'] );
            unset( $get_content_fields['comments']['auto_display_filter_post_types'] );
            $get_content_fields['comments'] = flavor_like_array_insert_after(
                $get_content_fields['comments'],
                'auto_display_position',
                array(
                    'enable_admin_columns' => array(
                        'id'         => 'enable_admin_columns',
                        'type'       => 'switcher',
                        'title'      => esc_html__('Show Admin Columns', 'flavor-like'),
                        'desc'       => '在管理列表中添加赞数列。'
                    ),
                )
            );

            // Generate buddypress fields (only if plugin is active)
            $buddypress_options = array();
            if ( function_exists('is_buddypress') ) {
                $get_content_fields['buddypress'] = $get_content_options;
                unset( $get_content_fields['buddypress']['auto_display_on_excerpts'] );
                unset( $get_content_fields['buddypress']['auto_display_filter'] );
                unset( $get_content_fields['buddypress']['auto_display_filter_post_types'] );
                $get_content_fields['buddypress']['auto_display_position']['options'] = array(
                    'content' => esc_html__('Activity Content', 'flavor-like'),
                    'meta'    => esc_html__('Activity Meta', 'flavor-like')
                );
                $get_content_fields['buddypress']['auto_display_position']['default'] = 'content';
                $get_content_fields['buddypress']['enable_comments'] = array(
                    'id'         => 'enable_comments',
                    'type'       => 'switcher',
                    'title'      => esc_html__('Enable Activity Comment Likes', 'flavor-like'),
                    'desc'       => esc_html__('Allow liking BuddyPress comments in the activity stream.', 'flavor-like')
                );
                $get_content_fields['buddypress']['enable_add_bp_activity'] = array(
                    'id'         => 'enable_add_bp_activity',
                    'type'       => 'switcher',
                    'title'      => esc_html__('Add Activity Entries for Likes', 'flavor-like'),
                    'desc'       => esc_html__('Create a BuddyPress activity item when someone likes content.', 'flavor-like'),
                );
                $get_content_fields['buddypress']['posts_notification_template'] = array(
                    'id'       => 'posts_notification_template',
                    'type'     => 'code_editor',
                    'settings' => array(
                        'theme' => 'shadowfox',
                        'mode'  => 'htmlmixed',
                    ),
                    'default'  => '<strong>%POST_LIKER%</strong> liked <a href="%POST_PERMALINK%" title="%POST_TITLE%">%POST_TITLE%</a>. (So far, This post has <span class="badge">%POST_COUNT%</span> likes)',
                    'title'    => esc_html__('Post Activity Text', 'flavor-like'),
                    'desc'     => esc_html__('Allowed Variables:', 'flavor-like') . ' <code>%POST_LIKER%</code> , <code>%POST_PERMALINK%</code> , <code>%POST_COUNT%</code> , <code>%POST_TITLE%</code>',
                    'dependency'=> array( 'enable_add_bp_activity', '==', 'true' ),
                );
                $get_content_fields['buddypress']['comments_notification_template'] = array(
                    'id'       => 'comments_notification_template',
                    'type'     => 'code_editor',
                    'settings' => array(
                        'theme' => 'shadowfox',
                        'mode'  => 'htmlmixed',
                    ),
                    'default'  => '<strong>%COMMENT_LIKER%</strong> liked <strong>%COMMENT_AUTHOR%</strong> comment. (So far, %COMMENT_AUTHOR% has <span class="badge">%COMMENT_COUNT%</span> likes for this comment)',
                    'title'    => esc_html__('Comment Activity Text', 'flavor-like'),
                    'desc'     => esc_html__('Allowed Variables:', 'flavor-like') . ' <code>%COMMENT_LIKER%</code> , <code>%COMMENT_AUTHOR%</code> , <code>%COMMENT_COUNT%</code>, <code>%COMMENT_PERMALINK%</code>',
                    'dependency'=> array( 'enable_add_bp_activity', '==', 'true' ),
                );
                $get_content_fields['buddypress']['enable_add_notification'] = array(
                    'id'         => 'enable_add_notification',
                    'type'       => 'switcher',
                    'title'      => esc_html__('Enable User Notifications', 'flavor-like'),
                    'desc'       => esc_html__('Send a notification when your content receives a like.', 'flavor-like'),
                );
                $get_content_fields['buddypress']['filter_user_notification_types'] = array(
                    'id'          => 'filter_user_notification_types',
                    'type'        => 'select',
                    'title'       => esc_html__( 'Disable Notifications On','flavor-like' ),
                    'desc'        => '选择禁用通知的位置。',
                    'chosen'      => true,
                    'multiple'    => true,
                    'options'     => array(
                        'post'     => esc_html__('Posts', 'flavor-like'),
                        'comment'  => esc_html__('Comments', 'flavor-like'),
                        'activity' => esc_html__('Activities', 'flavor-like'),
                        'topic'    => esc_html__('Topics', 'flavor-like')
                    ),
                    'dependency'=> array( 'enable_add_notification', '==', 'true' ),
                );
                $buddypress_options = array_values( apply_filters( 'flavor_like_panel_buddypress_type_options', $get_content_fields['buddypress'] ) );
            }

            // Generate bbPress fields (only if plugin is active)
            $bbPress_options = array();
            if ( function_exists('is_bbpress') ) {
                $get_content_fields['bbpress'] = $get_content_options;
                unset( $get_content_fields['bbpress']['auto_display_on_excerpts'] );
                unset( $get_content_fields['bbpress']['auto_display_filter'] );
                unset( $get_content_fields['bbpress']['auto_display_filter_post_types'] );
                $bbPress_options = array_values( apply_filters( 'flavor_like_panel_bbpress_type_options', $get_content_fields['bbpress'] ) );
            }

            // Content Groups
            $content_types_fields = array(
                // Posts
                array(
                    'id'         => 'posts_group',
                    'type'       => 'fieldset',
                    'title'      => esc_html__( 'Posts', 'flavor-like' ),
                    'fields'     => array_values( apply_filters( 'flavor_like_panel_post_type_options', $get_content_fields['posts'] ) ),
                    'sanitize'   => 'flavor_like_sanitize_multiple_select',
                    'display_as' => 'section' // Mark as section menu
                ),
                // Comments
                array(
                    'id'         => 'comments_group',
                    'type'       => 'fieldset',
                    'title'      => esc_html__( 'Comments', 'flavor-like' ),
                    'fields'     => array_values( apply_filters( 'flavor_like_panel_comment_type_options', $get_content_fields['comments'] ) ),
                    'display_as' => 'section' // Mark as section menu
                ),
            );

            // Only add BuddyPress if plugin is active
            if ( function_exists('is_buddypress') ) {
                $content_types_fields[] = array(
                    'id'         => 'buddypress_group',
                    'type'       => 'fieldset',
                    'title'      => esc_html__('BuddyPress'),
                    'fields'     => $buddypress_options,
                    'display_as' => 'section' // Mark as section menu
                );
            }

            // Only add bbPress if plugin is active
            if ( function_exists('is_bbpress') ) {
                $content_types_fields[] = array(
                    'id'         => 'bbpress_group',
                    'type'       => 'fieldset',
                    'title'      => esc_html__('bbPress'),
                    'fields'     => $bbPress_options,
                    'display_as' => 'section' // Mark as section menu
                );
            }

            $sections[] = array(
                'id'     => 'content-types',
                'parent' => 'configuration',
                'title'  => esc_html__( 'Content Types','flavor-like'),
                'icon'   => 'squares-2x2',
                'fields' => $content_types_fields
            );

            /**
             * Translations Section
             */
            $sections[] = array(
                'id'    => 'translations',
                'title' => esc_html__( 'Translations','flavor-like'),
                'icon'  => 'language',
            );

            $sections[] = array(
                'id'     => 'strings',
                'title'  => esc_html__( 'Strings','flavor-like'),
                'parent' => 'translations',
                'icon'   => 'document-text',
                'fields' => apply_filters( 'flavor_like_panel_translations', array(
                    array(
                        'id'      => 'validate_notice',
                        'type'    => 'text',
                        'default' => esc_html__( 'Your vote cannot be submitted at this time.','flavor-like'),
                        'title'   => esc_html__( 'Validation Notice Message', 'flavor-like'),
                        'desc'    => esc_html__( 'Message shown when a vote cannot be processed due to validation errors.', 'flavor-like')
                    ),
                    array(
                        'id'      => 'already_registered_notice',
                        'type'    => 'text',
                        'default' => esc_html__( 'You have already registered a vote.','flavor-like'),
                        'title'   => esc_html__( 'Already Voted Message', 'flavor-like'),
                        'desc'    => esc_html__( 'Message shown when a user tries to vote again after already voting.', 'flavor-like')
                    ),
                    array(
                        'id'      => 'login_required_notice',
                        'type'    => 'text',
                        'default' => esc_html__( 'You Should Login To Submit Your Like','flavor-like'),
                        'title'   => esc_html__( 'Login Required Message', 'flavor-like'),
                        'desc'    => esc_html__( 'Message shown to visitors who need to log in before they can vote.', 'flavor-like')
                    ),
                    array(
                        'id'      => 'like_notice',
                        'type'    => 'text',
                        'default' => esc_html__('Thanks! You Liked This.','flavor-like'),
                        'title'   => esc_html__( 'Liked Notice Message', 'flavor-like'),
                        'desc'    => esc_html__( 'Confirmation message shown after a user successfully likes content.', 'flavor-like')
                    ),
                    array(
                        'id'      => 'unlike_notice',
                        'type'    => 'text',
                        'default' => esc_html__('Sorry! You unliked this.','flavor-like'),
                        'title'   => esc_html__( 'Unliked Notice Message', 'flavor-like'),
                        'desc'    => esc_html__( 'Confirmation message shown after a user removes their like.', 'flavor-like')
                    ),
                    array(
                        'id'      => 'ajax_error_notice',
                        'type'    => 'text',
                        'default' => esc_html__( 'Could not save your vote. Please refresh the page and try again.', 'flavor-like' ),
                        'title'   => esc_html__( 'Connection Error Message', 'flavor-like' ),
                        'desc'    => esc_html__( 'Message shown when a vote cannot be saved due to a network or server error.', 'flavor-like' ),
                    ),
                    array(
                        'id'      => 'like_button_aria_label',
                        'type'    => 'text',
                        'default' => esc_html__( 'Like Button','flavor-like'),
                        'title'   => esc_html__( 'Like Button Aria Label', 'flavor-like'),
                        'desc'    => esc_html__( 'Accessibility label for screen readers. Helps visually impaired users understand what the button does.', 'flavor-like')
                    )
                ) )
            );

            /**
             * Customization Section
             */
            $sections[] = array(
                'id'    => 'customization',
                'title' => esc_html__( 'Developer Tools','flavor-like'),
                'icon'  => 'code-bracket',
            );

            $sections[] = array(
                'id'     => 'scripts',
                'parent' => 'customization',
                'title'  => esc_html__( 'Scripts','flavor-like'),
                'icon'   => 'document-text',
                'fields' => apply_filters( 'flavor_like_panel_customization', array(
                    array(
                        'id'    => 'custom_css',
                        'type'  => 'code_editor',
                        'settings' => array(
                            'theme'  => 'mbo',
                            'mode'   => 'css',
                        ),
                        'title' => esc_html__('Custom CSS','flavor-like'),
                        'desc'  => '添加自定义 CSS 来设置点赞按钮和相关元素的样式。此 CSS 将在所有页面上加载。',
                    ),
                    array(
                        'id'           => 'custom_spinner',
                        'type'         => 'upload',
                        'title'        => esc_html__('Custom Spinner','flavor-like'),
                        'desc'         => '上传在处理投票时显示的自定义加载动画图片。',
                        'library'      => 'image',
                        'placeholder'  => 'http://'
                    ),
                    array(
                        'id'    => 'enable_inline_custom_css',
                        'type'  => 'switcher',
                        'title' => esc_html__('Enable Inline Custom CSS', 'flavor-like'),
                        'desc'  => '在页面上内联输出样式，而不是加载 custom.css 文件。'
                    )
                ) )
            );

            do_action( 'flavor_like_panel_sections_ended' );

            $sections = $this->sort_configuration_child_sections( $sections );

            // Cache sections
            $this->sections_cache = $sections;

            return $sections;
        }

        /**
         * Generate general content options
         *
         * @return void
         */
        public function get_content_options(){
            return array(
                'template' => array(
                    'id'      => 'template',
                    'type'    => 'image_select',
                    'title'   => esc_html__( 'Select a Template','flavor-like'),
                    'desc'    => esc_html__( 'Pick a style for your like button.', 'flavor-like' ),
                    'options' => $this->get_templates_option_array(),
                    'default' => 'flavorlike-default',
                ),
                'enable_auto_display' => array(
                    'id'      => 'enable_auto_display',
                    'type'    => 'switcher',
                    'default' => true,
                    'title'   => esc_html__('Automatic Display', 'flavor-like'),
                    'desc'    => '自动在内容上显示点赞按钮，无需手动添加短代码。',
                ),
                'auto_display_position' => array(
                    'id'      => 'auto_display_position',
                    'type'    => 'radio',
                    'title'   => esc_html__( 'Button Position','flavor-like' ),
                    'desc'    => '选择点赞按钮相对于内容的显示位置。',
                    'default' => 'bottom',
                    'options' => flavor_like_get_post_auto_display_position_labels(),
                    'dependency' => array( 'enable_auto_display', '==', 'true' ),
                ),
                // UI: "Show Buttons On". DB: hide-list (same key). Converted in admin-hooks.
                'auto_display_filter' => array(
                    'id'          => 'auto_display_filter',
                    'type'        => 'select',
                    'title'       => esc_html__( 'Show Buttons On', 'flavor-like' ),
                    'desc'        => '勾选的位置将显示点赞按钮。默认为单页（单篇文章和页面）。',
                    'chosen'      => true,
                    'multiple'    => true,
                    'default'     => array( 'single' ),
                    'options'     => array(
                        'single'   => esc_html__( 'Singular (posts & pages)', 'flavor-like' ),
                        'home'     => esc_html__( 'Home', 'flavor-like' ),
                        'archive'  => esc_html__( 'Archives', 'flavor-like' ),
                        'category' => esc_html__( 'Categories', 'flavor-like' ),
                        'search'   => esc_html__( 'Search Results', 'flavor-like' ),
                        'tag'      => esc_html__( 'Tags', 'flavor-like' ),
                        'author'   => esc_html__( 'Author Page', 'flavor-like' ),
                    ),
                    'dependency' => array( 'enable_auto_display', '==', 'true' ),
                ),
                'auto_display_on_excerpts' => array(
                    'id'         => 'auto_display_on_excerpts',
                    'type'       => 'switcher',
                    'default'    => false,
                    'title'      => esc_html__( 'Also Show on Excerpts / List Views', 'flavor-like' ),
                    'desc'       => '默认关闭，以避免归档列表中重复显示点赞按钮。仅在需要在摘要中显示按钮时启用。',
                    'dependency' => array( 'enable_auto_display', '==', 'true' ),
                ),
                'auto_display_filter_post_types' => array(
                    'id'          => 'auto_display_filter_post_types',
                    'type'        => 'select',
                    'title'       => esc_html__( 'Always Show On Post Types', 'flavor-like' ),
                    'placeholder' => esc_html__( 'Select post types','flavor-like' ),
                    'desc'        => '即使上面的单页未勾选，也在这些文章类型上显示按钮。',
                    'chosen'      => true,
                    'multiple'    => true,
                    'default'     => array( 'post' ),
                    'options'     => 'post_types',
                    'dependency'  => array( 'enable_auto_display', '==', 'true' ),
                ),
                'button_type' => array(
                    'id'         => 'button_type',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Button Type', 'flavor-like'),
                    'desc'       => '选择在按钮上显示图片图标还是文本标签。',
                    'default'    => 'image',
                    'options'    => array(
                        'image' => esc_html__('Image', 'flavor-like'),
                        'text'  => esc_html__('Text', 'flavor-like')
                    ),
                    'dependency' => array( 'template', 'any', 'flavorlike-default,flavor-like-pro-default,flavorlike-heart' ),
                ),
                'text_group' => array(
                    'id'            => 'text_group',
                    'type'          => 'tabbed',
                    'desc'          => '设置自定义按钮文本。允许使用 HTML。',
                    'title'         => esc_html__( 'Button Text', 'flavor-like'),
                    'tabs'          => array(
                        array(
                            'title'     => esc_html__('Like','flavor-like'),
                            'fields'    => array(
                                array(
                                    'id'      => 'like',
                                    'type'    => 'text',
                                    'title'   => esc_html__('Button Label','flavor-like'),
                                    'desc'    => esc_html__('Text displayed on the like button (e.g., "Like", "👍", "Love").', 'flavor-like'),
                                    'default' => esc_html__('Like', 'flavor-like')
                                ),
                            )
                        ),
                        array(
                            'title'     => esc_html__('Unlike','flavor-like'),
                            'fields'    => array(
                                array(
                                    'id'      => 'unlike',
                                    'type'    => 'text',
                                    'title'   => esc_html__('Button Label','flavor-like'),
                                    'desc'    => esc_html__('Text displayed on the button after liking (e.g., "Liked", "❤️", "Unlike").', 'flavor-like'),
                                    'default' => esc_html__('Liked', 'flavor-like')
                                ),
                            )
                        ),
                    ),
                    'dependency' => array( 'button_type|template', 'any|any', 'text|flavorlike-default,flavor-like-pro-default,flavorlike-heart' ),
                ),
                'image_group' => array(
                    'id'            => 'image_group',
                    'type'          => 'tabbed',
                    'title'         => esc_html__( 'Button Image', 'flavor-like'),
                    'desc'          => '上传点赞和取消赞按钮状态的自定义图片。',
                    'tabs'          => array(
                        array(
                            'title'     => esc_html__('Like','flavor-like'),
                            'fields'    => array(
                                array(
                                    'id'           => 'like',
                                    'type'         => 'upload',
                                    'title'        => esc_html__('Button Image','flavor-like'),
                                    'desc'         => '上传按钮状态的图片图标。',
                                    'library'      => 'image',
                                    'placeholder'  => 'http://'
                                ),
                            )
                        ),
                        array(
                            'title'     => esc_html__('Unlike','flavor-like'),
                            'fields'    => array(
                                array(
                                    'id'           => 'unlike',
                                    'type'         => 'upload',
                                    'title'        => esc_html__('Button Image','flavor-like'),
                                    'desc'         => '上传按钮状态的图片图标。',
                                    'library'      => 'image',
                                    'placeholder'  => 'http://'
                                ),
                            )
                        ),
                    ),
                    'dependency' => array( 'button_type|template', 'any|any', 'image|flavorlike-default,flavor-like-pro-default,flavorlike-heart' ),
                ),
                'counter_display_condition' => array(
                    'id'         => 'counter_display_condition',
                    'type'       => 'button_set',
                    'desc'       => '控制投票计数器何时显示在按钮旁边。',
                    'title'      => esc_html__( 'Counter Display', 'flavor-like'),
                    'default'    => 'visible',
                    'options'    => array(
                        'visible'         => esc_html__('Always Visible', 'flavor-like'),
                        'hidden'          => esc_html__('Hidden', 'flavor-like'),
                        'logged_in_users' => esc_html__('Restrict to Logged-in Users', 'flavor-like')
                    )
                ),
                'hide_zero_counter' => array(
                    'id'         => 'hide_zero_counter',
                    'type'       => 'switcher',
                    'title'      => esc_html__('Hide Zero Counter', 'flavor-like'),
                    'desc'       => '当没有投票时隐藏投票计数器。',
                    'dependency' => array( 'counter_display_condition', '!=', 'hidden' )
                ),
                'enable_only_logged_in_users' => array(
                    'id'      => 'enable_only_logged_in_users',
                    'type'    => 'switcher',
                    'default' => false,
                    'title'   => esc_html__('Restrict to Logged-in Users', 'flavor-like'),
                    'desc'    => '建议启用此选项以防止通过更改 IP 地址生成虚假投票。',
                ),
                'logged_out_display_type' => array(
                    'id'         => 'logged_out_display_type',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Logged-out Button Display', 'flavor-like'),
                    'desc'       => '选择未登录用户如何看到投票按钮——显示为标准按钮或提示登录的模板消息。',
                    'options'    => array(
                        'alert'  => esc_html__('Template', 'flavor-like'),
                        'button' => esc_html__('Button', 'flavor-like')
                    ),
                    'default'    => 'button',
                    'dependency' => array( 'enable_only_logged_in_users', '==', 'true' ),
                ),
                'login_template' => array(
                    'id'       => 'login_template',
                    'type'     => 'code_editor',
                    'desc'     => esc_html__('Allowed Variables:', 'flavor-like') . ' <code>%CURRENT_PAGE_URL%</code>',
                    'settings' => array(
                        'theme' => 'shadowfox',
                        'mode'  => 'htmlmixed',
                    ),
                    'default'  => sprintf( '<p class="alert alert-info fade in" role="alert">%s<a href="%s">%s</a></p>',
                        esc_html__('You need to login in order to like this post: ','flavor-like'),
                        wp_login_url(),
                        esc_html__('click here','flavor-like')
                    ),
                    'title'    => esc_html__('Custom HTML Template', 'flavor-like'),
                    'dependency'=> array( 'logged_out_display_type|enable_only_logged_in_users', '==|==', 'alert|true' ),
                ),
                'logging_method' => array(
                    'id'          => 'logging_method',
                    'type'        => 'select',
                    'desc'        => '选择投票跟踪方式。可允许无限投票，或使用 Cookie、用户名/IP 来限制重复投票。',
                    'title'       => esc_html__( 'Logging Method','flavor-like'),
                    'options'     => flavor_like_get_logging_method_labels(),
                    'default'     => 'by_username',
                    'help'        => sprintf( '<p>%s</p><p>%s</p><p>%s</p><p>%s</p>', esc_html__( '"No Limit": There will be no restrictions and users can submit their points each time they refresh the page. In this option, it will not be possible to resubmit reverse points (un-like/un-dislike).', 'flavor-like' ), esc_html__( '"Cookie": By saving users\' cookies, it is possible to submit points only once per user and in case of re-clicking, the appropriate message will be displayed.', 'flavor-like' ), esc_html__( 'Username/IP: By saving the username/IP of users, It supports the reverse feature  (un-like and un-dislike) and users can change their reactions and are only allowed to have a specific point type.', 'flavor-like' ), esc_html__( 'Username/IP + Cookie: Same as username/IP description, However, if the user IP or username changes and the cookie is set, it does not allow the user to like /dislike.', 'flavor-like' )  )
                ),
                'cookie_expires' => array(
                    'id'         => 'cookie_expires',
                    'type'       => 'number',
                    'title'      => esc_html__( 'Cookie Expiration', 'flavor-like'),
                    'desc'       => '指定 Cookie 过期时间（秒）。默认：31536000（1 年）。',
                    'default'    => 31536000,
                    'dependency' => array( 'logging_method', 'any', 'by_cookie,by_user_ip_cookie' ),
                ),
                'vote_limit_number' => array(
                    'id'         => 'vote_limit_number',
                    'type'       => 'spinner',
                    'title'      => esc_html__( 'Maximum Votes Allowed', 'flavor-like'),
                    'desc'       => '设置每个用户可以对一个项目提交的最大投票次数。',
                    'default'    => 10,
                    'min'        => 1,
                    'max'        => 1000,
                    'dependency' => array( 'logging_method', '==', 'do_not_log' ),
                ),
                'enable_likers_box' => array(
                    'id'    => 'enable_likers_box',
                    'type'  => 'switcher',
                    'desc'  => '显示投票用户列表，让您查看谁参与了每个项目的互动。',
                    'title' => esc_html__('Display Likers Box', 'flavor-like'),
                ),
                'likers_order' => array(
                    'id'         => 'likers_order',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Likers List Order', 'flavor-like'),
                    'desc'       => '按最新优先（降序）或最早优先（升序）排序。',
                    'default'    => 'desc',
                    'options'    => array(
                        'asc'  => esc_html__('Ascending', 'flavor-like'),
                        'desc' => esc_html__('Descending', 'flavor-like')
                    ),
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'hide_likers_for_anonymous_users' => array(
                    'id'    => 'hide_likers_for_anonymous_users',
                    'type'  => 'switcher',
                    'default' => false,
                    'title' => esc_html__('Hide For Anonymous Users', 'flavor-like'),
                    'desc'  => '对未登录的访客隐藏点赞者框。',
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'likers_style' => array(
                    'id'         => 'likers_style',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Likers Box Layout', 'flavor-like'),
                    'desc'       => '内联：在按钮下方直接显示头像。弹出框：悬停在计数器上时在弹窗中显示头像。',
                    'default'    => 'popover',
                    'options'    => array(
                        'default' => esc_html__('Inline', 'flavor-like'),
                        'popover' => esc_html__('Popover', 'flavor-like')
                    ),
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'likers_template' => array(
                    'id'       => 'likers_template',
                    'type'     => 'code_editor',
                    'settings' => array(
                        'theme' => 'shadowfox',
                        'mode'  => 'htmlmixed',
                    ),
                    'default'  => '<div class="flavor-like-likers-list">%START_WHILE%<span class="flavor-like-liker"><a href="#" title="%USER_NAME%">%USER_AVATAR%</a></span>%END_WHILE%</div>',
                    'title'    => esc_html__('Custom HTML Template', 'flavor-like'),
                    'desc'     => esc_html__('Allowed Variables:', 'flavor-like') . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>',
                    'dependency'=> array( 'enable_likers_box|likers_style', '==|any', 'true|default,popover'  ),
                ),
                // 'disable_likers_pophover' => array(
                //     'id'         => 'disable_likers_pophover',
                //     'type'       => 'switcher',
                //     'title'      => esc_html__('Disable Pophover', 'flavor-like'),
                //     'dependency' => array( 'enable_likers_box', '==', 'true' ),
                //     'desc'       => esc_html__('Active this option to show liked users avatars in the bottom of button like.', 'flavor-like')
                // ),
                'likers_gravatar_size' => array(
                    'id'         => 'likers_gravatar_size',
                    'type'       => 'number',
                    'title'      => esc_html__( 'Avatar Size', 'flavor-like'),
                    'desc'       => '设置点赞者框中显示的用户头像大小。',
                    'default'    => 32,
                    'unit'       => 'px',
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'likers_count' => array(
                    'id'         => 'likers_count',
                    'type'       => 'number',
                    'title'      => esc_html__( 'Likers to Show', 'flavor-like'),
                    'desc'       => '点赞者框中显示的用户数量',
                    'default'    => 10,
                    'unit'       => 'users',
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                )
            );
        }

        /**
         * Get templates option array
         * Returns structured data with symbol and is_locked flag for React app
         *
         * @return array
         */
        public function get_templates_option_array(){
            $options = flavor_like_generate_templates_list();
            $output  = array();

            if( !empty( $options ) ){
                foreach ($options as $key => $args) {
                    $output[$key] = array(
                        'symbol'    => isset( $args['symbol'] ) ? $args['symbol'] : '',
                        'is_locked' => ! empty( $args['is_locked'] ),
                    );
                }
            }

            return $output;
        }

        /**
         * Put Configuration sub-sections in onboarding-friendly order.
         *
         * @param array $sections
         * @return array
         */
        protected function sort_configuration_child_sections( $sections ) {
            $child_order = array(
                'content-types',
                'general',
                'profiles',
                'login-signup',
                'social-logins',
                'share-buttons',
            );

            $configuration_children = array();
            $other_sections         = array();

            foreach ( $sections as $section ) {
                if ( isset( $section['parent'], $section['id'] ) && 'configuration' === $section['parent'] ) {
                    $configuration_children[ $section['id'] ] = $section;
                    continue;
                }

                $other_sections[] = $section;
            }

            $sorted_children = array();
            foreach ( $child_order as $child_id ) {
                if ( isset( $configuration_children[ $child_id ] ) ) {
                    $sorted_children[] = $configuration_children[ $child_id ];
                    unset( $configuration_children[ $child_id ] );
                }
            }

            foreach ( $configuration_children as $child_section ) {
                $sorted_children[] = $child_section;
            }

            $result = array();
            foreach ( $other_sections as $section ) {
                $result[] = $section;

                if ( isset( $section['id'] ) && 'configuration' === $section['id'] && ! isset( $section['parent'] ) ) {
                    $result = array_merge( $result, $sorted_children );
                }
            }

            return $result;
        }

        /**
         * Legacy integration options — shown only when already enabled so existing sites keep control.
         *
         * @return array
         */
        protected function get_legacy_integration_fields() {
            $fields = array();

            $legacy_options = array(
                'enable_meta_values' => array(
                    'id'    => 'enable_meta_values',
                    'type'  => 'switcher',
                    'title' => esc_html__('Enable Old Meta Values', 'flavor-like'),
                    'desc'  => sprintf( '%s<br><strong>* %s</strong>', esc_html__('By activating this option, users who have upgraded to version +4 and deleted their old logs can add the number of old likes to the new figures.', 'flavor-like'), esc_html__('Attention: If you have been using Flavor Like +v4 from the beginning Or you haven\'t deleted any logs yet, do not enable this option.', 'flavor-like') )
                ),
                'enable_deprecated_options' => array(
                    'id'    => 'enable_deprecated_options',
                    'type'  => 'switcher',
                    'title' => esc_html__('Enable Deprecated Options', 'flavor-like'),
                    'desc'  => sprintf( '%s<br><strong>* %s</strong>', esc_html__('By activating this option, users who have upgraded to version +4.1 and lost their old options can restore and enable previous settings.', 'flavor-like'), esc_html__('Attention: If you have been using Flavor Like +v4.1 from the beginning, do not enable this option.', 'flavor-like') )
                ),
            );

            foreach ( $legacy_options as $option_key => $field ) {
                if ( flavor_like_is_true( flavor_like_get_option( $option_key, false ) ) ) {
                    $fields[] = $field;
                }
            }

            if ( ! empty( $fields ) ) {
                array_unshift( $fields, array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Integrations', 'flavor-like' ),
                ) );
            }

            /**
             * Filter legacy integration fields appended to General.
             * Previously used for the standalone Integrations section.
             *
             * @param array $fields
             */
            return apply_filters( 'flavor_like_panel_integrations', $fields );
        }


    }
}