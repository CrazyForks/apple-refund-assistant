
## 苹果退款管理助手

[English](./README.md) | 简体中文 | [Español](./README.es.md) | [हिन्दी](./README.hi.md) | [العربية](./README.ar.md) | [Português](./README.pt.md) | [Русский](./README.ru.md) | [日本語](./README.ja.md) | [Français](./README.fr.md)

基于 Laravel 的多租户支付退款预防服务。

![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/seth-shi/apple-refund-assistant/laravel.yml)
![Codecov](https://img.shields.io/codecov/c/github/seth-shi/apple-refund-assistant)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/seth-shi/apple-refund-assistant?utm_source=oss&utm_medium=github&utm_campaign=seth-shi%2Fapple-refund-assistant&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

## 在线演示

🌐 **演示地址**: [https://apple-refund-assistant.shiguopeng.cn/](https://apple-refund-assistant.shiguopeng.cn/)

> ⚠️ **注意**: 系统每30分钟会重置一次。

## 概述

实时处理 Apple 的 CONSUMPTION_REQUEST 通知，并立即将消费信息发送回 Apple，帮助减少欺诈性退款。


- **多币种支持**
- **多租户支持**
- **多语言支持(中文 / English / Español / हिन्दी / العربية / Português / Русский / 日本語 / Français)**
- **零依赖-本地服务直接启动快人一步**

| 依赖项 | 零依赖方案 |  进阶方案   |
|-----|--|-----|
|  数据库   | sqlite | MySQL |
|  缓存   | file | redis  |
|   session | file |  redis   |
- **webhook** 接口 **100%** 测试覆盖率
- **密钥自持** -私钥仅保存在你的数据库`apps`表中(会进行对称加密,密钥由你的应用生成)
- **12 个消费字段** - 计算所有必需的 Apple 字段
- 支持服务器消息转发
  - 苹果服务器发送到当前服务,当前服务转发到你的正式服务器

 
## 截图
![首页](assets/0.png)
![首页](assets/1.png)
![首页](assets/2.png)
![首页](assets/3.png)
![首页](assets/4.png)


## 快速开始
### 使用已经构建好的镜像
```bash
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```


### 本地构建运行
```bash
git clone https://github.com/seth-shi/apple-refund-assistant
cd apple-refund-assistant
## 构建镜像并部署
./deploy.sh
```

### 如果需要挂载数据
```
touch database.sqlite
docker run -d \
  -p 8080:8080 \
  -v $(pwd)/database.sqlite:/var/www/html/database/database.sqlite \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```

## 消费字段策略
* 文档地址: [https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest](https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest)
* 策略代码: [ConsumptionService.php](./app/Services/ConsumptionService.php) 
* `users` 表字段可由其他系统更新

| 字段                       | 描述                | 数据表来源                          | 计算规则                                                                                           |
|--------------------------|-------------------|--------------------------------|------------------------------------------------------------------------------------------------|
| accountTenure            | 用户注册天数            | `users.register_at`            | 当前时间减去注册时间                                                                                     |
| appAccountToken          | 账号 token          | `users.app_account_token`      | [需要客户端创建订单时传递](https://developer.apple.com/documentation/StoreKit/Transaction/appAccountToken) |
| consumptionStatus        | 消费状况              | `transactions.expiration_date` | 对比当前时间,如果已到期返回消费完                                                                              |
| customerConsented        | 用户同意提供数据          | 无                              | 写死`true`                                                                                       |
| deliveryStatus           | 是否成功交付了一个功能正常的内购。 | 无                              | 写死`0`(正常交付)                                                                                    |
| lifetimeDollarsPurchased | 内购总金额             | `users.purchased_dollars`      | 根据苹果交易事件累加这个字段,你也可以自行累加                                                                        |
| lifetimeDollarsRefunded  | 退款总金额             | `users.refunded_dollars`       | 根据苹果退款事件累加这个字段,你也可以自行累加                                                                        |
| platform                 | 平台                | 无                              | 写死`1`(apple)                                                                                   |
| playTime                 | 客户使用应用时间的值        | `users.play_seconds`           | 需要你的系统支持更新这个字段,否则是`0`                                                                          |
| refundPreference         | 退款请求的期望结果         | `transactions.expiration_date` | 对比当前时间,如果已到期希望拒绝退款                                                                             |
| sampleContentProvided    | 是否提供试用            | `apps.sample_content_provided` | 创建应用时配置应用                                                                                      |
| userStatus               | 用户状态              | 无                              | 写死是`1`(正常用户)                                                                                   |



## 许可证

根据 Apache License 2.0 授权，详见 [LICENSE](./LICENSE)。
## 支持

如有问题或疑问，请在 GitHub 上提交 issue。

## 未来计划
- 有其它想法或对合作感兴趣？请在 GitHub 上提交 issue - 我们非常期待您的反馈！

## 感谢
* [Rates By Exchange Rate API](https://www.exchangerate-api.com)

