# Flavor Like

为 WordPress 添加一键点赞按钮的轻量级插件。内置统计仪表盘、10 种按钮模板和隐私工具。

## 功能特性

- 10 种点赞按钮模板（简约拇指、爱心、Twitter 心、动态心、星星、对勾、鼓掌、徽章、收藏星、图钉）
- 支持文章、评论、BuddyPress 活动、bbPress 话题
- 统计仪表盘，追踪互动数据
- 自动显示或短代码手动放置
- 点赞者头像列表（内联/弹出框）
- GDPR 合规（IP 匿名化）
- 全中文后台界面
- 夜间模式兼容
- 无外部依赖，原生 JavaScript
- 并发投票互斥保护（MySQL GET_LOCK，防重复计数）
- 前端资源按需加载（可选）

## 系统要求

- WordPress 6.0+
- PHP 7.2.5+

## 安装

1. 下载 `flavor-like.zip`
2. WordPress 后台 → 插件 → 安装新插件 → 上传插件
3. 启用即可

## 短代码

```
[flavor_like]
[flavor_like for="post" id="123"]
[flavor_like for="comment"]
[flavor_like_counter type="post" status="like"]
[flavor_like_likers_box type="post" counter="5"]
```

> 兼容旧短代码 `[wp_ulike]`、`[wp_ulike_counter]`、`[wp_ulike_likers_box]`

## 安全与性能设置（1.0.5 新增）

后台 → Flavor Like → 常规：

- **受信代理 IP（Trusted Proxy IPs）**：站点位于反向代理 / 负载均衡之后时，填写代理的 IP 或 IPv4 CIDR（每行一个，如 `10.0.0.0/8`）。配置后只有来自这些地址的请求才读取 `X-Forwarded-For` 等代理头，防止伪造 IP 绕过投票去重和黑名单。留空则保持旧行为（始终信任代理头）。使用 Cloudflare 的站点无需配置（已内置 Cloudflare IP 段校验）。
- **资源加载策略（Assets Load Strategy）**：
  - `Load Globally`（默认）：所有页面加载插件 CSS/JS，兼容所有场景。
  - `Load On Demand`：仅当页面实际渲染了点赞按钮（自动插入、短代码或区块）时才加载资源，减少无关节页面的开销。

开发者可用的过滤器：

- `wp_ulike_trusted_proxies` — 程序化设置受信代理列表
- `wp_ulike_lock_name` — 自定义投票互斥锁名称（MySQL GET_LOCK）

## 更新日志

### 1.0.5

**安全加固**

- 投票互斥锁由文件锁（flock + unlink，存在释放竞态）改为 MySQL `GET_LOCK`，消除并发场景下的重复计数风险，同时避免临时目录路径注入与多站点锁文件互相干扰
- 锁释放改为 `try/finally` 结构，异常路径下也能保证释放
- `wp_ulike_get_likers` AJAX 端点补上 nonce 校验（与投票端点一致），防止未认证请求枚举点赞用户
- 新增受信代理 IP 白名单：配置后仅当请求来自受信代理时才读取代理头，防止伪造 `X-Forwarded-For` 绕过 IP 去重与黑名单
- Cloudflare IP 列表拉取失败时仅缓存 5 分钟（原先空结果缓存 1 周，导致失败期间 CF 站点 IP 识别失效）

**性能优化**

- 新增资源加载策略：按需模式下仅含点赞按钮的页面输出 CSS/JS（默认仍为全局加载，保持兼容）
- 计数器更新维持增量 ±1 逻辑，避免热门内容每次投票全表扫描

### 1.0.4
- 修复统计页面显示空白问题（权限函数 bug）
- 修复硬编码语言环境，支持多语言站点
- 新增 6 个按钮模板（星星拇指、对勾标记、鼓掌、徽章拇指、收藏星、图钉）
- 修复前端自动显示不生效
- 修复统计页面滚动卡住
- 优化夜间模式显示
- 基于 WP ULike 5.2.0 修改

## 许可证

GPL-2.0+
