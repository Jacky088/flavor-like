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

## 更新日志

### 1.0.2
- 新增 6 个按钮模板（星星拇指、对勾标记、鼓掌、徽章拇指、收藏星、图钉）
- 修复"显示按钮位置"和"始终在文章类型上显示"逻辑
- 后台保存提示汉化修复
- 前端弹窗字体调大
- 删除停用反馈弹窗和安装欢迎弹窗

### 1.0.1
- 修复前端自动显示不生效
- 修复统计页面滚动卡住
- 修复 ZIP 路径正斜杠问题
- 禁止 WP.org 更新检测
- 优化夜间模式显示

### 1.0.0
- 初始版本
- 基于 WP ULike 5.2.0 修改
- 全面中文汉化
- 移除 Pro 功能和推广
- 作者：木木

## 许可证

GPL-2.0+
