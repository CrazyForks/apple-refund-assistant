## 退款管理助手

简体中文 | [English](./README.md)

基于 Laravel 的多租户支付退款预防服务。

## 概述

实时处理 Apple 的 CONSUMPTION_REQUEST 通知，并立即将消费信息发送回 Apple，帮助减少欺诈性退款。

- **webhook** 接口 **100%** 测试覆盖率
    - [ ] 覆盖率完成
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

## 截图
![首页](assets/1.png)
![首页](assets/2.png)
![首页](assets/3.png)


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
