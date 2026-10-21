/**
 * Flavor Like - Help & Documentation Modal System
 * Provides technical documentation popup for shortcodes, templates, analytics, and security.
 * Version: 1.1.0
 */

(function () {
  'use strict';

  var modalBackdrop = null;
  var currentTab = 'overview';

  // SVG Icons
  var ICONS = {
    book: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>',
    code: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
    palette: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>',
    chart: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    shield: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    help: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    search: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    close: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    copy: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>',
    check: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>',
    tip: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
    warn: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'
  };

  /**
   * Build the complete documentation HTML structure conforming to documentation standards.
   */
  function buildModalHtml() {
    return [
      '<div class="flavor-like-docs-backdrop" id="flavor-like-docs-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="flavor-like-docs-title">',
      '  <div class="flavor-like-docs-modal">',
      '    <div class="flavor-like-docs-header">',
      '      <div class="flavor-like-docs-brand">',
      '        <div class="flavor-like-docs-icon-badge">' + ICONS.book + '</div>',
      '        <div class="flavor-like-docs-title-group">',
      '          <div class="flavor-like-docs-title-row">',
      '            <h2 class="flavor-like-docs-title" id="flavor-like-docs-title">Flavor Like 帮助与技术规范文档</h2>',
      '            <span class="flavor-like-docs-version-badge">v1.1.0 官方规范</span>',
      '          </div>',
      '          <p class="flavor-like-docs-subtitle">高性能 WordPress 点赞与量化互动分析引擎使用手册</p>',
      '        </div>',
      '      </div>',
      '      <div class="flavor-like-docs-header-actions">',
      '        <div class="flavor-like-docs-search-wrap">',
      '          <span class="flavor-like-docs-search-icon">' + ICONS.search + '</span>',
      '          <input type="text" class="flavor-like-docs-search-input" id="flavor-like-docs-search-input" placeholder="实时搜索短代码、参数、常见问题..." aria-label="搜索文档" />',
      '        </div>',
      '        <button type="button" class="flavor-like-docs-close-btn" id="flavor-like-docs-close-btn" title="关闭 (Esc)" aria-label="关闭">' + ICONS.close + '</button>',
      '      </div>',
      '    </div>',
      '    <div class="flavor-like-docs-tabs-bar" role="tablist">',
      '      <button type="button" class="flavor-like-docs-tab-btn flavor-like-docs-tab-active" data-tab="overview" role="tab">' + ICONS.book + ' 核心概览</button>',
      '      <button type="button" class="flavor-like-docs-tab-btn" data-tab="shortcodes" role="tab">' + ICONS.code + ' 短代码与 API</button>',
      '      <button type="button" class="flavor-like-docs-tab-btn" data-tab="templates" role="tab">' + ICONS.palette + ' 模板与外观定制</button>',
      '      <button type="button" class="flavor-like-docs-tab-btn" data-tab="analytics" role="tab">' + ICONS.chart + ' 统计与指标解析</button>',
      '      <button type="button" class="flavor-like-docs-tab-btn" data-tab="security" role="tab">' + ICONS.shield + ' 安全与防刷风控</button>',
      '      <button type="button" class="flavor-like-docs-tab-btn" data-tab="faq" role="tab">' + ICONS.help + ' 常见问题排查</button>',
      '    </div>',
      '    <div class="flavor-like-docs-body">',

      // Tab 1: Overview
      '      <div class="flavor-like-docs-panel flavor-like-docs-panel-active" data-panel="overview">',
      '        <h3 class="flavor-like-docs-section-title"><span class="flavor-like-docs-section-icon">' + ICONS.book + '</span> 插件架构与工作原理</h3>',
      '        <p class="flavor-like-docs-p">Flavor Like 是为现代 WordPress 站点量身打造的高性能内容点赞、投票与用户互动统计插件。它不仅提供富有动感的前端交互动效，还通过创新的底层存储设计保障高并发环境下的极致吞吐量与数据强一致性。</p>',
      '        <div class="flavor-like-docs-callout tip">' + ICONS.tip + '<div><strong>零锁并发与缓存友好：</strong>前端请求全异步化，支持静态 HTML 页面缓存插件（WP Super Cache、LiteSpeed、WP Rocket 等）下的计数动态回填，并在后端采用零锁机制防止数据库行锁拥堵。</div></div>',
      '        <h4 class="flavor-like-docs-section-subtitle">存储引擎模式 (Storage Modes)</h4>',
      '        <p class="flavor-like-docs-p">插件支持三种可配置的底层数据存储模型，站点管理员可根据自身访客规模灵活切换：</p>',
      '        <ul class="flavor-like-docs-list">',
      '          <li><strong>传统模式 (Legacy)：</strong>直接在主数据表中进行汇总计算，完全向下兼容老版本数据结构，适合小型站点与个人博客。</li>',
      '          <li><strong>平滑双写验证 (Dual-Write)：</strong>点赞同时写入传统主表与 Pulse 脉冲账本，由内核模式感知查询器自动进行交叉比对，用于无缝过渡与迁移验证。</li>',
      '          <li><strong>Pulse 脉冲账本 (Pulse Ledger)：</strong>采用高性能追加型日志流水账本（Append-Only）设计，天然避免行级死锁，读写分离极大减轻 MySQL 负载，适合高并发、大流量企业站点。</li>',
      '        </ul>',
      '        <h4 class="flavor-like-docs-section-subtitle">点赞交互生命周期</h4>',
      '        <p class="flavor-like-docs-p">一次完整的点赞交互经历如下六阶段流转：</p>',
      '        <ol class="flavor-like-docs-list">',
      '          <li><strong>触发与前端即时动效：</strong>访客点击按钮，触发 CSS3 / SVG 微交互动效，按钮进入防重复保护状态。</li>',
      '          <li><strong>安全令牌与穿透检测：</strong>客户端异步附带 WordPress Nonce 校验签名发起 REST / AJAX 请求。</li>',
      '          <li><strong>网络风控与黑名单过滤：</strong>服务端提取真实客户端 IP，依次进行代理合法性校验、IP 黑名单匹配与用户封禁检测。</li>',
      '          <li><strong>频控规则判定：</strong>依据设置的判定级别（无限制 / Cookie / 用户名IP / 混合双重校验）确认用户是否有权投票。</li>',
      '          <li><strong>原子存储与计数器更新：</strong>获取短暂原子互斥锁，向账本写入流水并原子自增实体点赞计数。</li>',
      '          <li><strong>状态回传与广播：</strong>将最新点赞计数值与激活态返回前端并实时呈现。</li>',
      '        </ol>',
      '      </div>',

      // Tab 2: Shortcodes & API
      '      <div class="flavor-like-docs-panel" data-panel="shortcodes">',
      '        <h3 class="flavor-like-docs-section-title"><span class="flavor-like-docs-section-icon">' + ICONS.code + '</span> 短代码与 API 调用规范</h3>',
      '        <p class="flavor-like-docs-p">Flavor Like 提供了功能完备的短代码（Shortcode）与 PHP 模板函数，方便在文章内容、页面排版、Gutenberg 块或自定义模板中快速嵌入点赞组件。</p>',
      '        <h4 class="flavor-like-docs-section-subtitle">1. 基础点赞短代码：[flavor_like]</h4>',
      '        <p class="flavor-like-docs-p">渲染完整的交互式点赞按钮与计数值容器。</p>',
      '        <div class="flavor-like-docs-table-wrap">',
      '          <table class="flavor-like-docs-table">',
      '            <thead>',
      '              <tr><th>参数名称</th><th>数据类型</th><th>默认值</th><th>必须/可选</th><th>描述与可选值</th></tr>',
      '            </thead>',
      '            <tbody>',
      '              <tr><td><code>for</code></td><td><span class="flavor-like-docs-badge-type">string</span></td><td><code>posts</code></td><td><span class="flavor-like-docs-badge-opt">可选</span></td><td>交互对象实体类型：<code>posts</code> (文章/页面/自定义类型)、<code>comments</code> (评论)、<code>activities</code> (动态)、<code>topics</code> (论坛话题)</td></tr>',
      '              <tr><td><code>id</code></td><td><span class="flavor-like-docs-badge-type">integer</span></td><td>当前实体 ID</td><td><span class="flavor-like-docs-badge-opt">可选</span></td><td>目标实体的唯一数字 ID。如果在 WordPress Loop 循环内使用，可省略由系统自动提取。</td></tr>',
      '              <tr><td><code>slug</code></td><td><span class="flavor-like-docs-badge-type">string</span></td><td>全局模板设置</td><td><span class="flavor-like-docs-badge-opt">可选</span></td><td>覆盖当前全局模板，显式指定单次调用的外观样式，如 <code>flavorlike-animated-heart</code> 等。</td></tr>',
      '              <tr><td><code>show_count</code></td><td><span class="flavor-like-docs-badge-type">integer</span></td><td><code>1</code></td><td><span class="flavor-like-docs-badge-opt">可选</span></td><td>是否显示点赞计数数字。<code>1</code> (显示), <code>0</code> (仅显示点赞按钮，隐藏数字)。</td></tr>',
      '            </tbody>',
      '          </table>',
      '        </div>',
      '        <p class="flavor-like-docs-p"><strong>短代码调用示例：</strong></p>',
      '        <div class="flavor-like-docs-code-box">',
      '          <div class="flavor-like-docs-code-header"><span class="flavor-like-docs-code-label">Shortcode Examples</span><button type="button" class="flavor-like-docs-copy-btn" data-copy="[flavor_like]">' + ICONS.copy + ' 复制短代码</button></div>',
      '          <pre class="flavor-like-docs-code-pre"><code>; 当前文章默认点赞按钮\n[flavor_like]\n\n; 指定文章 #42 并采用动效爱心模板\n[flavor_like id="42" slug="flavorlike-animated-heart"]\n\n; 为评论 #108 插入点赞按钮并隐藏计数值\n[flavor_like for="comments" id="108" show_count="0"]</code></pre>',
      '        </div>',
      '        <h4 class="flavor-like-docs-section-subtitle">2. 独立计数器短代码：[flavor_like_counter]</h4>',
      '        <p class="flavor-like-docs-p">仅输出纯文本格式的点赞总数，非常适合插入在文章标题旁、卡片角标或侧边栏排版中：</p>',
      '        <div class="flavor-like-docs-code-box">',
      '          <div class="flavor-like-docs-code-header"><span class="flavor-like-docs-code-label">Counter Shortcode</span><button type="button" class="flavor-like-docs-copy-btn" data-copy="[flavor_like_counter]">' + ICONS.copy + ' 复制计数短代码</button></div>',
      '          <pre class="flavor-like-docs-code-pre"><code>; 输出当前文章点赞数\n[flavor_like_counter]\n\n; 输出文章 #108 的点赞数\n[flavor_like_counter id="108" for="posts"]</code></pre>',
      '        </div>',
      '        <h4 class="flavor-like-docs-section-subtitle">3. PHP 模板函数规范</h4>',
      '        <p class="flavor-like-docs-p">若您在主题模板文件（如 <code>single.php</code>, <code>content.php</code>）中定制输出，推荐使用官方封装的 PHP 接口：</p>',
      '        <div class="flavor-like-docs-code-box">',
      '          <div class="flavor-like-docs-code-header"><span class="flavor-like-docs-code-label">PHP API</span><button type="button" class="flavor-like-docs-copy-btn" data-copy="&lt;?php if ( function_exists( &#39;flavor_like&#39; ) ) { flavor_like(); } ?&gt;">' + ICONS.copy + ' 复制代码</button></div>',
      '          <pre class="flavor-like-docs-code-pre"><code>&lt;?php\n// 1. 渲染点赞交互按钮\nif ( function_exists( &#39;flavor_like&#39; ) ) {\n    flavor_like( &#39;posts&#39;, get_the_ID(), &#39;flavorlike-heart&#39;, 1 );\n}\n\n// 2. 获取指定实体点赞数值 (int)\nif ( function_exists( &#39;flavor_like_get_counter_value&#39; ) ) {\n    $likes = flavor_like_get_counter_value( get_the_ID(), &#39;posts&#39; );\n    echo &#39;累计点赞：&#39; . esc_html( $likes );\n}\n\n// 3. 校验当前访客是否已点赞 (bool)\nif ( function_exists( &#39;flavor_like_is_user_liked&#39; ) ) {\n    $has_liked = flavor_like_is_user_liked( get_the_ID(), &#39;posts&#39; );\n}\n?&gt;</code></pre>',
      '        </div>',
      '      </div>',

      // Tab 3: Templates & Styling
      '      <div class="flavor-like-docs-panel" data-panel="templates">',
      '        <h3 class="flavor-like-docs-section-title"><span class="flavor-like-docs-section-icon">' + ICONS.palette + '</span> 模板与外观定制规范</h3>',
      '        <p class="flavor-like-docs-p">Flavor Like 1.1.0 内置了 10 款纯矢量 SVG 图标与动效模板，无任何外置位图依赖，保证在 Retina 高分屏下拥有纤毫毕现的清晰度，且对页面初次加载零性能拖慢。</p>',
      '        <h4 class="flavor-like-docs-section-subtitle">10 款内置高质矢量模板</h4>',
      '        <div class="flavor-like-docs-table-wrap">',
      '          <table class="flavor-like-docs-table">',
      '            <thead>',
      '              <tr><th>模板标识 (Slug)</th><th>设计风格</th><th>推荐应用场景</th></tr>',
      '            </thead>',
      '            <tbody>',
      '              <tr><td><code>flavorlike-heart</code></td><td>经典矢量爱心</td><td>个人博客、文学随笔、资讯站点</td></tr>',
      '              <tr><td><code>flavorlike-animated-heart</code></td><td>动效红心 (Bilibili/Twitter 弹跳波纹)</td><td>高互动社区、娱乐媒体、年轻人垂直站</td></tr>',
      '              <tr><td><code>flavorlike-badgeThumb</code></td><td>现代微光拇指徽章</td><td>科技博客、SaaS 产品资讯、技术文档</td></tr>',
      '              <tr><td><code>flavorlike-simple</code></td><td>极简轮廓线条</td><td>极简主义主题、极客博客、无感嵌入</td></tr>',
      '              <tr><td><code>flavorlike-clapping</code></td><td>Medium 风格欢呼击掌</td><td>深度干货长文、专栏作品、行业洞察</td></tr>',
      '              <tr><td><code>flavorlike-twitter</code></td><td>社交平台推特心形粒子散发</td><td>社交资讯流、新闻快讯、高频更新内容</td></tr>',
      '              <tr><td><code>flavorlike-faveStar</code></td><td>五角星标收藏</td><td>精选案例、作品集、资源推荐板块</td></tr>',
      '              <tr><td><code>flavorlike-starThumb</code></td><td>星辉大拇指</td><td>电商评论、用户问答社区</td></tr>',
      '              <tr><td><code>flavorlike-checkMark</code></td><td>已阅勾选认证徽章</td><td>教程操作指南、知识库任务清单</td></tr>',
      '              <tr><td><code>flavorlike-pin</code></td><td>图钉收藏便签</td><td>公告速报、置顶推荐</td></tr>',
      '            </tbody>',
      '          </table>',
      '        </div>',
      '        <h4 class="flavor-like-docs-section-subtitle">CSS 类结构与样式覆盖指南</h4>',
      '        <p class="flavor-like-docs-p">为了方便开发者在子主题或自定义 CSS 中调整外观，插件规范定义了如下语义化类名：</p>',
      '        <ul class="flavor-like-docs-list">',
      '          <li><span class="flavor-like-docs-code-inline">.flavor-like-button</span>：主交互按钮容器。</li>',
      '          <li><span class="flavor-like-docs-code-inline">.flavor-like-icon</span>：矢量图标包裹器，包含动效基础样式。</li>',
      '          <li><span class="flavor-like-docs-code-inline">.flavor-like-counter</span>：数字呈现区域。</li>',
      '          <li><span class="flavor-like-docs-code-inline">.flavor_like_is_liked</span>：用户处于已点赞状态时附带的类名。</li>',
      '          <li><span class="flavor-like-docs-code-inline">.flavor_like_is_loading</span>：异步请求中的加载状态类名。</li>',
      '        </ul>',
      '        <div class="flavor-like-docs-callout tip">' + ICONS.tip + '<div><strong>流行主题无缝兼容：</strong>插件已内置针对 JustNews、Astra 等主流流行主题的排版补偿规则，默认居中展示并严格控制布局偏移（CLS 值为 0）。</div></div>',
      '      </div>',

      // Tab 4: Analytics
      '      <div class="flavor-like-docs-panel" data-panel="analytics">',
      '        <h3 class="flavor-like-docs-section-title"><span class="flavor-like-docs-section-icon">' + ICONS.chart + '</span> 统计数据与关键指标解析</h3>',
      '        <p class="flavor-like-docs-p">统计仪表盘（Metrics Dashboard）帮助内容运营者敏锐捕捉用户喜好与互动趋势，通过量化数据驱动选题与排版优化。</p>',
      '        <h4 class="flavor-like-docs-section-subtitle">核心指标定义</h4>',
      '        <ul class="flavor-like-docs-list">',
      '          <li><strong>全站总点赞量 (Total Votes)：</strong>记录系统部署以来或统计周期内的累计点赞总频次。</li>',
      '          <li><strong>今日点赞 (Today Interactions)：</strong>过去 24 小时（动态滑动窗口）内产生的新增互动增量。</li>',
      '          <li><strong>独立互动用户 (Engaged Users)：</strong>参与过点赞的去重独立访客数量（结合 IP 与登录用户识别）。</li>',
      '          <li><strong>互动转化率 (Engagement Rate)：</strong>点赞总量占页面总浏览量（PV）的比率，直接体现文章的认同感与含金量。</li>',
      '          <li><strong>热门榜单 (Top Content Leaderboard)：</strong>自动按点赞总数排序的热门文章列表，直观呈现站内最受读者青睐的爆款。</li>',
      '        </ul>',
      '        <h4 class="flavor-like-docs-section-subtitle">24 小时聚合缓存与穿透刷新</h4>',
      '        <p class="flavor-like-docs-p">为避免管理员在频繁刷新后台统计时对数据库产生聚合查询压力，统计数据由 WordPress Transient 缓存系统进行自动化托管：</p>',
      '        <ul class="flavor-like-docs-list">',
      '          <li>后台常规访问时直接读取聚合缓存，响应时间小于 15ms。</li>',
      '          <li>若需要立即拉取毫秒级最新点赞数据，可点击统计看板右上角的<strong>“刷新”</strong>按钮，系统将立即穿透缓存重新计算并同步更新。</li>',
      '        </ul>',
      '      </div>',

      // Tab 5: Security & Anti-Abuse
      '      <div class="flavor-like-docs-panel" data-panel="security">',
      '        <h3 class="flavor-like-docs-section-title"><span class="flavor-like-docs-section-icon">' + ICONS.shield + '</span> 安全防护、防刷与风控策略</h3>',
      '        <p class="flavor-like-docs-p">针对网络爬虫、作弊脚本与恶意刷赞行为，Flavor Like 构建了多层次防御体系，兼顾用户体验与数据真实性。</p>',
      '        <h4 class="flavor-like-docs-section-subtitle">四级风控判定机制</h4>',
      '        <div class="flavor-like-docs-table-wrap">',
      '          <table class="flavor-like-docs-table">',
      '            <thead>',
      '              <tr><th>限制级别</th><th>防刷强度</th><th>机制说明</th><th>推荐适用场景</th></tr>',
      '            </thead>',
      '            <tbody>',
      '              <tr><td><code>No Limit</code> (无限制)</td><td>无</td><td>允许访客无限制重复点击，每次刷新均可计分。不支持反向取消（Unlike）。</td><td>开放式民意打分、问卷测试活动</td></tr>',
      '              <tr><td><code>Cookie</code> (Cookie 标识)</td><td>基础</td><td>在访客本地写入高强度签名 Cookie。同一客户端仅可点赞一次。</td><td>普通博客、轻量化展示站点</td></tr>',
      '              <tr><td><code>Username/IP</code> (用户名与 IP)</td><td>高</td><td>将点赞行为与登录用户名及真实外网 IP 联合绑定，支持取消点赞与重新投票。</td><td>标准社区、知识分享、内容自媒体</td></tr>',
      '              <tr><td><code>Username/IP + Cookie</code> (严格双重校验)</td><td>极高</td><td>双重强校验。只要本地 Cookie 或网络 IP 任意一项存在历史记录，即判定已点赞。</td><td>商业评选、严谨投票活动、防止网络刷票</td></tr>',
      '            </tbody>',
      '          </table>',
      '        </div>',
      '        <h4 class="flavor-like-docs-section-subtitle">真实 IP 穿透识别引擎 (Anti-Spoofing)</h4>',
      '        <p class="flavor-like-docs-p">针对刷票脚本常见的伪造 HTTP 请求标头行为（如伪造 <code>X-Forwarded-For</code>、<code>Client-IP</code>），插件内核严格遵循安全反向代理白名单机制：</p>',
      '        <ul class="flavor-like-docs-list">',
      '          <li>默认直接信任外网 <code>REMOTE_ADDR</code>。</li>',
      '          <li>仅当站点配置了受信代理（Trusted Proxies，如 Cloudflare 或阿里云 CDN）时，才逐级穿透验证代理链中的真实客户端 IP，彻底粉碎肉鸡伪造 IP 刷赞。</li>',
      '        </ul>',
      '        <h4 class="flavor-like-docs-section-subtitle">黑名单与 CIDR 子网封锁规范</h4>',
      '        <p class="flavor-like-docs-p">可在设置页面中配置针对特定恶意来源的精准封禁：</p>',
      '        <ul class="flavor-like-docs-list">',
      '          <li><strong>单一 IPv4 / IPv6 封锁：</strong>直接填写 IP，如 <code>203.0.113.195</code>。</li>',
      '          <li><strong>CIDR 网络段批量封锁：</strong>如 <code>192.168.1.0/24</code>（封禁整段 256 个 IP）、<code>10.0.0.0/8</code>。</li>',
      '          <li><strong>用户名封锁：</strong>填入恶意账号用户名，命中时直接拒绝点赞并反馈警告。</li>',
      '        </ul>',
      '      </div>',

      // Tab 6: FAQ & Troubleshooting
      '      <div class="flavor-like-docs-panel" data-panel="faq">',
      '        <h3 class="flavor-like-docs-section-title"><span class="flavor-like-docs-section-icon">' + ICONS.help + '</span> 常见问题排查与技术支持</h3>',
      '        <div class="flavor-like-docs-faq-card" open>',
      '          <summary>Q1: 开启了页面静态化缓存插件（WP Super Cache、LiteSpeed、WP Rocket）后，点赞数不更新怎么办？</summary>',
      '          <div class="flavor-like-docs-faq-body">',
      '            <p><strong>解答：</strong>Flavor Like 1.1.0 具备全栈静态缓存免疫机制。页面加载完成后，前端 JavaScript 会自动向后端发起轻量级异步请求，动态回填最新点赞计数值并根据访客本地状态点亮按钮。请确保未在缓存插件中错误禁用了 REST API 或 admin-ajax.php 异步接口。</p>',
      '          </div>',
      '        </div>',
      '        <div class="flavor-like-docs-faq-card">',
      '          <summary>Q2: 点击点赞按钮无反应，或浏览器控制台提示 403 Forbidden / 401 Unauthorized 报错？</summary>',
      '          <div class="flavor-like-docs-faq-body">',
      '            <p><strong>解答：</strong>通常是由于安全防火墙插件（如 Wordfence、Sucuri、iThemes Security）或 CDN 云防火墙（Cloudflare WAF）拦截了异步 Nonce 请求。请在安全插件中将 <code>flavor_like_ajax_process</code> 动作添加至白名单规则中。</p>',
      '          </div>',
      '        </div>',
      '        <div class="flavor-like-docs-faq-card">',
      '          <summary>Q3: 如何重置某篇文章的点赞数或批量清除测试阶段的历史数据？</summary>',
      '          <div class="flavor-like-docs-faq-body">',
      '            <p><strong>解答：</strong>有以下两种方式：</p>',
      '            <ul class="flavor-like-docs-list">',
      '              <li><strong>单篇修改：</strong>在 WordPress 后台编辑该文章，展开底部的“自定义栏目”（Custom Fields），找到 <code>flavor_like_counter</code> 键名直接修改其数值并保存。</li>',
      '              <li><strong>批量清空/归档：</strong>进入“概览”页面中的“维护工具”面板，可按文章、评论等分类一键重置或清空测试日志。</li>',
      '            </ul>',
      '          </div>',
      '        </div>',
      '        <div class="flavor-like-docs-faq-card">',
      '          <summary>Q4: 从旧版本或其他点赞插件迁移至 Flavor Like 会丢失历史点赞数据吗？</summary>',
      '          <div class="flavor-like-docs-faq-body">',
      '            <p><strong>解答：</strong>完全不会。Flavor Like 深度重构后保留了与传统 <code>wp_ulike</code> 表的完整兼容层。系统启动时会自动检测历史数据并无缝承接；若开启 Pulse 账本双写模式，历史数据和后续新流水均会自动进行映射与保护。</p>',
      '          </div>',
      '        </div>',
      '        <div class="flavor-like-docs-faq-card">',
      '          <summary>Q5: 如何将 Flavor Like 点赞按钮居中或修改按钮间距？</summary>',
      '          <div class="flavor-like-docs-faq-body">',
      '            <p><strong>解答：</strong>可在“外观”->“自定义”->“额外 CSS”中添加简单样式规则，例如：</p>',
      '            <div class="flavor-like-docs-code-box">',
      '              <div class="flavor-like-docs-code-header"><span class="flavor-like-docs-code-label">Custom CSS</span><button type="button" class="flavor-like-docs-copy-btn" data-copy=".flavor-like-button { margin: 24px auto !important; display: flex !important; justify-content: center !important; }">' + ICONS.copy + ' 复制样式</button></div>',
      '              <pre class="flavor-like-docs-code-pre"><code>.flavor-like-button {\n  margin: 24px auto !important;\n  display: flex !important;\n  justify-content: center !important;\n}</code></pre>',
      '            </div>',
      '          </div>',
      '        </div>',
      '      </div>',

      '    </div>', // End body
      '    <div class="flavor-like-docs-footer">',
      '      <div class="flavor-like-docs-footer-hint">',
      '        <span>需要进一步技术支持？请访问</span>',
      '        <a href="https://github.com/Jacky088/flavor-like" target="_blank" rel="noopener noreferrer">Flavor Like GitHub 仓库</a>',
      '      </div>',
      '      <button type="button" class="flavor-like-docs-close-action-btn" id="flavor-like-docs-close-bottom-btn">关闭文档</button>',
      '    </div>',
      '  </div>',
      '</div>'
    ].join('\n');
  }

  /**
   * Initialize modal and event listeners.
   */
  function ensureModalMounted() {
    if (modalBackdrop) {
      return modalBackdrop;
    }

    var existing = document.getElementById('flavor-like-docs-modal-backdrop');
    if (existing) {
      modalBackdrop = existing;
      return modalBackdrop;
    }

    var wrapper = document.createElement('div');
    wrapper.innerHTML = buildModalHtml();
    modalBackdrop = wrapper.firstElementChild;
    document.body.appendChild(modalBackdrop);

    // Bind Close buttons
    var closeTop = document.getElementById('flavor-like-docs-close-btn');
    var closeBottom = document.getElementById('flavor-like-docs-close-bottom-btn');
    if (closeTop) closeTop.addEventListener('click', closeHelpDocs);
    if (closeBottom) closeBottom.addEventListener('click', closeHelpDocs);

    // Backdrop click close
    modalBackdrop.addEventListener('click', function (e) {
      if (e.target === modalBackdrop) {
        closeHelpDocs();
      }
    });

    // Escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modalBackdrop && modalBackdrop.classList.contains('flavor-like-docs-visible')) {
        closeHelpDocs();
      }
    });

    // Tab switching
    var tabBtns = modalBackdrop.querySelectorAll('.flavor-like-docs-tab-btn');
    tabBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var targetTab = btn.getAttribute('data-tab');
        switchTab(targetTab);
      });
    });

    // Copy snippet buttons
    modalBackdrop.addEventListener('click', function (e) {
      var copyBtn = e.target.closest('.flavor-like-docs-copy-btn');
      if (!copyBtn) return;
      var textToCopy = copyBtn.getAttribute('data-copy');
      if (!textToCopy) {
        var codeEl = copyBtn.closest('.flavor-like-docs-code-box').querySelector('code');
        if (codeEl) textToCopy = codeEl.textContent;
      }
      if (textToCopy && navigator.clipboard) {
        navigator.clipboard.writeText(textToCopy).then(function () {
          var originalHtml = copyBtn.innerHTML;
          copyBtn.innerHTML = ICONS.check + ' 已复制!';
          copyBtn.classList.add('copied');
          setTimeout(function () {
            copyBtn.innerHTML = originalHtml;
            copyBtn.classList.remove('copied');
          }, 1600);
        });
      }
    });

    // Real-time search filter
    var searchInput = document.getElementById('flavor-like-docs-search-input');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        var query = searchInput.value.trim().toLowerCase();
        handleSearch(query);
      });
    }

    return modalBackdrop;
  }

  /**
   * Switch active tab.
   */
  function switchTab(tabName) {
    if (!modalBackdrop) return;
    currentTab = tabName;

    var tabBtns = modalBackdrop.querySelectorAll('.flavor-like-docs-tab-btn');
    tabBtns.forEach(function (btn) {
      if (btn.getAttribute('data-tab') === tabName) {
        btn.classList.add('flavor-like-docs-tab-active');
      } else {
        btn.classList.remove('flavor-like-docs-tab-active');
      }
    });

    var panels = modalBackdrop.querySelectorAll('.flavor-like-docs-panel');
    panels.forEach(function (panel) {
      if (panel.getAttribute('data-panel') === tabName) {
        panel.classList.add('flavor-like-docs-panel-active');
      } else {
        panel.classList.remove('flavor-like-docs-panel-active');
      }
    });
  }

  /**
   * Handle real-time documentation search filtering.
   */
  function handleSearch(query) {
    if (!modalBackdrop) return;
    var body = modalBackdrop.querySelector('.flavor-like-docs-body');
    if (!query) {
      switchTab(currentTab || 'overview');
      return;
    }

    var panels = modalBackdrop.querySelectorAll('.flavor-like-docs-panel');
    var firstMatchTab = null;

    panels.forEach(function (panel) {
      var panelText = panel.textContent.toLowerCase();
      var panelTab = panel.getAttribute('data-panel');
      if (panelText.indexOf(query) !== -1) {
        if (!firstMatchTab) {
          firstMatchTab = panelTab;
        }
      }
    });

    if (firstMatchTab) {
      switchTab(firstMatchTab);
    }
  }

  /**
   * Public function to open Help & Documentation modal.
   */
  function openHelpDocs(tabName) {
    var modal = ensureModalMounted();
    if (tabName) {
      switchTab(tabName);
    } else {
      switchTab(currentTab || 'overview');
    }

    // Show modal with animation
    requestAnimationFrame(function () {
      modal.classList.add('flavor-like-docs-visible');
    });

    document.body.style.overflow = 'hidden';
  }

  /**
   * Public function to close Help & Documentation modal.
   */
  function closeHelpDocs() {
    if (!modalBackdrop) return;
    modalBackdrop.classList.remove('flavor-like-docs-visible');
    document.body.style.overflow = '';
  }

  // Expose to window
  window.flavorLikeOpenHelpDocs = openHelpDocs;
  window.flavorLikeCloseHelpDocs = closeHelpDocs;

  // Intercept any click on Help & documentation button (both English and Chinese)
  document.addEventListener('click', function (e) {
    var target = e.target;
    var btn = target.closest && target.closest(
      '.flavor-like-open-docs-btn, .flavor-like-stats-notify-action--secondary, [data-action="flavor-like-docs"], a[href*="flavor-like-about"]'
    );

    if (btn) {
      var text = (btn.textContent || '').trim();
      var isHelpDocsBtn = (
        btn.classList.contains('flavor-like-open-docs-btn') ||
        text.indexOf('Help & documentation') !== -1 ||
        text.indexOf('帮助与文档') !== -1 ||
        text.indexOf('帮助文档') !== -1
      );

      if (isHelpDocsBtn) {
        e.preventDefault();
        e.stopPropagation();
        openHelpDocs();
      }
    }
  }, true);

})();
