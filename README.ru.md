
## Apple Refund Assistant

[English](./README.md) | [简体中文](./README.zh.md) | [Español](./README.es.md) | [हिन्दी](./README.hi.md) | [العربية](./README.ar.md) | [Português](./README.pt.md) | Русский | [日本語](./README.ja.md) | [Français](./README.fr.md)

Мультитенантный сервис предотвращения возвратов платежей на основе Laravel.

![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/seth-shi/apple-refund-assistant/laravel.yml)
![Codecov](https://img.shields.io/codecov/c/github/seth-shi/apple-refund-assistant)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/seth-shi/apple-refund-assistant?utm_source=oss&utm_medium=github&utm_campaign=seth-shi%2Fapple-refund-assistant&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

## Обзор

Обрабатывайте уведомления CONSUMPTION_REQUEST от Apple в реальном времени и немедленно отправляйте информацию о потреблении обратно в Apple, помогая сократить мошеннические возвраты.


- **Поддержка нескольких валют**
- **Поддержка нескольких арендаторов**
- **Многоязычная поддержка (中文 / English / Español / हिन्दी / العربية / Português / Русский / 日本語 / Français)**
- **Без зависимостей - Запускайте локальный сервис напрямую для более быстрого развертывания**

| Зависимость | Без зависимостей |  Расширенный   |
|-----|--|-----|
|  База данных   | sqlite | MySQL |
|  Очередь   | sync | redis  |
|  Кэш   | file | redis  |
|   Сессия | file |  redis   |
- API **Webhook** со **100%** покрытием тестами
    - [x] Покрытие завершено (Services\WebhookService)
- **Самоуправляемые ключи** - Приватные ключи хранятся только в вашей таблице базы данных `apps` (с симметричным шифрованием, ключи сгенерированы вашим приложением)
- **12 полей потребления** - Рассчитывает все необходимые поля Apple
- Поддержка пересылки сообщений сервера
  - Сервер Apple отправляет текущему сервису, который пересылает на ваш продакшн-сервер

 
## Скриншоты
![Главная страница](assets/0.png)
![Главная страница](assets/1.png)
![Главная страница](assets/2.png)
![Главная страница](assets/3.png)
![Главная страница](assets/4.png)


## Быстрый старт
### Использование готового образа
```bash
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```


### Сборка и запуск локально
```bash
git clone https://github.com/seth-shi/apple-refund-assistant
cd apple-refund-assistant
## Собрать образ и развернуть
./deploy.sh
```

### Если вам нужно смонтировать данные
```
touch database.sqlite
docker run -d \
  -p 8080:8080 \
  -v $(pwd)/database.sqlite:/var/www/html/database/database.sqlite \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```

## Стратегия полей потребления
* Документация: [https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest](https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest)
* Код стратегии: [ConsumptionService.php](./app/Services/ConsumptionService.php) 
* Поля таблицы `users` могут обновляться другими системами

| Поле                       | Описание                | Источник данных                          | Правило расчета                                                                                           |
|--------------------------|-------------------|--------------------------------|------------------------------------------------------------------------------------------------|
| accountTenure            | Дней с регистрации пользователя            | `users.register_at`            | Текущее время минус время регистрации                                                                                     |
| appAccountToken          | Токен учетной записи          | `users.app_account_token`      | [Должен передаваться при создании заказа клиентом](https://developer.apple.com/documentation/StoreKit/Transaction/appAccountToken) |
| consumptionStatus        | Статус потребления              | `transactions.expiration_date` | Сравнить с текущим временем, вернуть потреблено, если истек                                                                              |
| customerConsented        | Пользователь дал согласие на предоставление данных          | N/A                              | Жестко закодировано `true`                                                                                       |
| deliveryStatus           | Была ли функциональная покупка в приложении успешно доставлена | N/A                              | Жестко закодировано `0` (нормальная доставка)                                                                                    |
| lifetimeDollarsPurchased | Общая сумма покупок в приложении             | `users.purchased_dollars`      | Накапливается на основе событий транзакций Apple, или вы можете накапливать вручную                                                                        |
| lifetimeDollarsRefunded  | Общая сумма возвратов             | `users.refunded_dollars`       | Накапливается на основе событий возврата Apple, или вы можете накапливать вручную                                                                        |
| platform                 | Платформа                | N/A                              | Жестко закодировано `1` (apple)                                                                                   |
| playTime                 | Значение времени использования приложения клиентом        | `users.play_seconds`           | Ваша система должна поддерживать обновление этого поля, иначе это `0`                                                                          |
| refundPreference         | Ожидаемый результат для запроса возврата         | `transactions.expiration_date` | Сравнить с текущим временем, предпочесть отклонить возврат, если истек                                                                             |
| sampleContentProvided    | Предоставляется ли пробная версия            | `apps.sample_content_provided` | Настроить при создании приложения                                                                                      |
| userStatus               | Статус пользователя              | N/A                              | Жестко закодировано `1` (обычный пользователь)                                                                                   |



## Лицензия

Лицензировано по Apache License 2.0, см. [LICENSE](./LICENSE) для подробностей.

## Поддержка

По вопросам или проблемам, пожалуйста, создайте issue на GitHub.

## Планы на будущее
- Есть другие идеи или заинтересованы в сотрудничестве? Пожалуйста, создайте issue на GitHub - мы ждем ваших отзывов!

## Благодарности
* [Rates By Exchange Rate API](https://www.exchangerate-api.com)

