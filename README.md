# Flavor Like

[![WordPress](https://img.shields.io/badge/WordPress-6.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0+-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.1.0-orange.svg)]()

WordPress 轻量级高颜值点赞互动插件。原生 JavaScript 驱动，零外部依赖，集成现代化胶囊按钮、Bilibili 风格动效、并发互斥锁与统计仪表盘。

---

## ✨ 特性一览

- **10 种现代胶囊模板**：简约拇指、简约爱心、Twitter 爱心、动态爱心、星星拇指、对勾认可、鼓掌致敬、荣誉徽章、收藏星、图钉。
- **灵动微交互动画**：内置 Bilibili 风格弹性回弹、粒子光环爆彩（Burst Effect）及浮动 `+1` 徽章气泡。
- **全主题深度兼容**：深度兼容 JustNews、B2、Core 等主流主题容器，彻底杜绝样式穿透、文字重叠及按钮闪烁。
- **高并发互斥保护**：采用 MySQL `GET_LOCK` 分布式悲观锁，杜绝高并发重复计数与缓存击穿。
- **资源智能防缓存**：动态注入文件修改时间戳（Cache-Busting），插件更新后前端资源秒级生效。
- **轻量高性能**：纯原生 ES6+ JavaScript，支持全站全局加载与页面按需加载（On Demand）。
- **隐私与安全**：支持 GDPR 匿名化、真实 IP 识别（含 Cloudflare 与反向代理 CIDR 白名单）及防刷黑名单。
- **SaaS 级设计系统**：全新 SaaS Blue（`#2563EB`）管理后台、自适应响应式布局与极简纯线条图标。

---

## 📋 系统要求

- WordPress 6.0 或更高版本
- PHP 7.4 或更高版本
- MySQL 5.7+ / MariaDB 10.3+

---

## 🚀 快速安装

1. 下载最新发行包 `flavor-like.zip`。
2. 进入 WordPress 后台 → **插件** → **安装插件** → **上传插件**。
3. 选择 zip 文件上传并点击**启用**即可。

---

## 💻 常用短代码

```text
[flavor_like]                                   # 默认文章点赞按钮
[flavor_like for="post" id="123"]               # 指定文章 ID
[flavor_like for="comment" id="456"]            # 评论点赞
[flavor_like_counter type="post" status="like"] # 仅显示点赞数
[flavor_like_likers_box type="post" counter="6"]# 显示点赞者头像列表
```

---

## ⚙️ 核心配置说明

前往后台 **Flavor Like → 设置**：

- **资源加载策略**：
  - `Load Globally`（默认）：全站加载，适合所有复杂页面。
  - `Load On Demand`：仅在实际渲染点赞按钮的页面输出 CSS/JS，优化加载速度。
- **受信代理 IP**：
  - 站点部署在 CDN / 反向代理 / 负载均衡后方时填写（每行一个 IP 或 CIDR，如 `10.0.0.0/8`）。仅信任名单内代理转发的 `X-Forwarded-For` 头，防止伪造 IP 绕过频控。Cloudflare 节点自动免配置信任。

---

## 📝 更新日志

### 1.1.0 (2026-10)
- **视觉体系重构**：全后台升级 SaaS Blue（`#2563EB`）高质感配色、对称 24px 网格布局与高清 Pulse Heart 品牌资产。
- **图标纯线条工艺**：菜单与管理图标严格遵循单色纯线条（Wireframe Stroke）工艺，完美适配深浅色模式。
- **Bilibili 风格爆彩动效**：点赞触发图标多级震颤、彩色火花环散射与向上漂浮渐隐的 `+1` 胶囊。
- **前端抗干扰与防消失加固**：
  - 点赞请求期采用无感 busy 状态锁，解除原生 `:disabled` 依赖，杜绝主题透明度压制。
  - 强固胶囊内 flex 布局与图标物理外边距，彻底解决第三方主题下的文字挤压重叠。
  - 前端资源注册引入 `filemtime` 自动化防缓存版本控制。
- **工程化构建完善**：新增 Terser / CSS 自动化压缩流水线及全量回归自动化测试套件（125 项测试 100% 通过）。

### 1.0.6
- 框架正式更名为 Flavor Like，旧版数据与 Cookie 自动无缝迁移。
- 引入 MySQL `GET_LOCK` 替换旧版文件锁，彻底解决高并发死锁。
- 新增反向代理与受信 CIDR 防穿透校验。
- 新增前端按需加载（On Demand）策略。

### 1.0.4
- 修复统计仪表盘空白及滚动问题。
- 新增 6 款现代按钮模板，完善夜间模式显示。

---

## 📄 开源许可证

本项目基于 [GPL-2.0+](LICENSE) 协议开源。
