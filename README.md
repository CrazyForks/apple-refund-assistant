## 退款管理助手


# Refund Swatter Lite

简体中文 | [English](./README.md)

基于 Laravel 的多租户支付退款预防服务。

## 概述

实时处理 Apple 的 CONSUMPTION_REQUEST 通知，并立即将消费信息发送回 Apple，帮助减少欺诈性退款。

### 主要特性

- **密钥自持** -私钥仅保存在你的数据库`apps`表中(会进行对称加密,密钥由你的应用生成)
- **零依赖**

| 依赖项 | 零依赖方案 |  进阶方案   |
|-----|--|-----|
|  数据库   | sqlite | MySQL/PostgreSQL/MariaDB/SQL Server    |
|  队列   | sync | database/redis  |
|  缓存   | file | redis  |
|   session | file |  redis   |


- **实时处理** - 立即处理收到的通知
- **自动处理** - 全自动化工作流程
- **12 个消费字段** - 计算所有必需的 Apple 字段

## 为什么是我们
> - 痛点真实存在：不少 iOS 团队遭遇过"隔夜大规模恶意退款"，轻则几百刀、重则上万刀，还可能被下架。
> - 关键机制：用户申请退款后，Apple 会向开发者发送最多 3 次 CONSUMPTION_REQUEST；只要实时回复包含消费信息（累计消费/累计退款/开发者偏好等），即可帮助 Apple 更"公平"地决策，从而显著降低恶意退款比例。退款周期最长可达 90 天，因此需要持续覆盖这一周期。
> - 现有方案不足：如 RevenueCat 等三方平台虽支持自动回复，但通常需要把 App Store Server API 密钥（AuthKey.p8）与 In-App Purchase Key 上传到其云端，把查询与操作权限交给第三方；对安全敏感团队（含企业）而言难以接受。
> - 可观测与可审计：自动答复 CONSUMPTION_REQUEST 的同时，展示各字段含义、任务与日志，方便排查与回溯。
> - 实际收益：在保证 AuthKey 与 IAP Key 安全性的同时，显著减少恶意退款订单（对消耗型尤其明显）。

## 快速开始

1. **克隆并配置**
```bash
## TODO 后续更新 Docker 方案
git clone https://github.com/seth-shi/apple-refund-assistant
cd apple-refund-assistant
# 使用你的凭据编辑 .env.project
cp .env.example .env
## 安装依赖
composer install
## 初始化数据表 && 填充默认数据
php artisan app:init
## 启动服务
php artisan serve
```


## TODO
[ ] NotificationRawLog select field without request body, payload

## 许可证

根据 Apache License 2.0 授权，详见 [LICENSE](./LICENSE)。

## 支持

如有问题或疑问，请在 GitHub 上提交 issue。

## 未来计划
- 有其它想法或对合作感兴趣？请在 GitHub 上提交 issue - 我们非常期待您的反馈！
