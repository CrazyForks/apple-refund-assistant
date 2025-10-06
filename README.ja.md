
## Apple返金アシスタント

[English](./README.md) | [简体中文](./README.zh.md) | [Español](./README.es.md) | [हिन्दी](./README.hi.md) | [العربية](./README.ar.md) | [Português](./README.pt.md) | [Русский](./README.ru.md) | 日本語 | [Français](./README.fr.md)

Laravelベースのマルチテナント決済返金防止サービス。

![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/seth-shi/apple-refund-assistant/laravel.yml)
![Codecov](https://img.shields.io/codecov/c/github/seth-shi/apple-refund-assistant)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/seth-shi/apple-refund-assistant?utm_source=oss&utm_medium=github&utm_campaign=seth-shi%2Fapple-refund-assistant&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

## 概要

AppleのCONSUMPTION_REQUEST通知をリアルタイムで処理し、直ちに消費情報をAppleに送信し、不正な返金を減らすのに役立ちます。


- **マルチ通貨対応**
- **マルチテナント対応**
- **多言語対応（中文 / English / Español / हिन्दी / العربية / Português / Русский / 日本語 / Français）**
- **ゼロ依存関係 - ローカルサービスを直接起動してより速いデプロイを実現**

| 依存関係 | ゼロ依存関係 |  高度   |
|-----|--|-----|
|  データベース   | sqlite | MySQL |
|  キャッシュ   | file | redis  |
|   セッション | file |  redis   |
- **Webhook** API **100%** テストカバレッジ
- **自己管理キー** - 秘密鍵はデータベース`apps`テーブルにのみ保存されます（対称暗号化、アプリケーションで生成されたキー）
- **12個の消費フィールド** - 必要なすべてのAppleフィールドを計算
- サーバーメッセージ転送サポート
  - Appleサーバーが現在のサービスに送信し、本番サーバーに転送します

 
## スクリーンショット
![ホームページ](assets/0.png)
![ホームページ](assets/1.png)
![ホームページ](assets/2.png)
![ホームページ](assets/3.png)
![ホームページ](assets/4.png)


## クイックスタート
### ビルド済みイメージの使用
```bash
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```


### ローカルでビルドして実行
```bash
git clone https://github.com/seth-shi/apple-refund-assistant
cd apple-refund-assistant
## イメージをビルドしてデプロイ
./deploy.sh
```

### データをマウントする必要がある場合
```
touch database.sqlite
docker run -d \
  -p 8080:8080 \
  -v $(pwd)/database.sqlite:/var/www/html/database/database.sqlite \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```

## 消費フィールド戦略
* ドキュメント: [https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest](https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest)
* 戦略コード: [ConsumptionService.php](./app/Services/ConsumptionService.php) 
* `users`テーブルのフィールドは他のシステムで更新可能

| フィールド                       | 説明                | データソース                          | 計算ルール                                                                                           |
|--------------------------|-------------------|--------------------------------|------------------------------------------------------------------------------------------------|
| accountTenure            | ユーザー登録からの日数            | `users.register_at`            | 現在時刻マイナス登録時刻                                                                                     |
| appAccountToken          | アカウントトークン          | `users.app_account_token`      | [クライアントが注文を作成する際に渡す必要があります](https://developer.apple.com/documentation/StoreKit/Transaction/appAccountToken) |
| consumptionStatus        | 消費ステータス              | `transactions.expiration_date` | 現在時刻と比較し、期限切れの場合は消費済みを返す                                                                              |
| customerConsented        | ユーザーがデータ提供に同意          | N/A                              | ハードコード `true`                                                                                       |
| deliveryStatus           | 機能的なアプリ内購入が正常に配信されたか | N/A                              | ハードコード `0`（通常配信）                                                                                    |
| lifetimeDollarsPurchased | アプリ内購入合計額             | `users.purchased_dollars`      | Appleトランザクションイベントに基づいて累積、または手動で累積可能                                                                        |
| lifetimeDollarsRefunded  | 返金合計額             | `users.refunded_dollars`       | Apple返金イベントに基づいて累積、または手動で累積可能                                                                        |
| platform                 | プラットフォーム                | N/A                              | ハードコード `1`（apple）                                                                                   |
| playTime                 | 顧客のアプリ使用時間値        | `users.play_seconds`           | システムがこのフィールドの更新をサポートする必要があり、そうでなければ`0`                                                                          |
| refundPreference         | 返金リクエストの期待される結果         | `transactions.expiration_date` | 現在時刻と比較し、期限切れの場合は返金拒否を推奨                                                                             |
| sampleContentProvided    | トライアルが提供されているか            | `apps.sample_content_provided` | アプリ作成時に設定                                                                                      |
| userStatus               | ユーザーステータス              | N/A                              | ハードコード `1`（通常ユーザー）                                                                                   |



## ライセンス

Apache License 2.0でライセンスされています。詳細は[LICENSE](./LICENSE)を参照してください。

## サポート

質問や懸念事項については、GitHubでissueを提出してください。

## 今後の予定
- 他のアイデアや協力に興味がありますか？GitHubでissueを提出してください - フィードバックをお待ちしています！

## 謝辞
* [Rates By Exchange Rate API](https://www.exchangerate-api.com)

