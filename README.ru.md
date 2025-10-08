## apple-refund-assistant
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/seth-shi/apple-refund-assistant/laravel.yml)
![Codecov](https://img.shields.io/codecov/c/github/seth-shi/apple-refund-assistant)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/seth-shi/apple-refund-assistant?utm_source=oss&utm_medium=github&utm_campaign=seth-shi%2Fapple-refund-assistant&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

[English](./README.md) | [简体中文](./README.zh.md) | [Español](./README.es.md) | [हिन्दी](./README.hi.md) | [العربية](./README.ar.md) | [Português](./README.pt.md) | Русский | [日本語](./README.ja.md) | [Français](./README.fr.md)

Этот сервис построен на архитектуре Laravel / Filament multi-tenant,
эффективно помогая разработчикам предотвращать мошеннические возвраты, мгновенно обрабатывая уведомления CONSUMPTION_REQUEST от Apple и асинхронно возвращая данные о потреблении.

- **Поддержка Multi-tenant**
- **Поддержка Multi-языков** (中文 / English / Español / हिन्दी / العربية / Português / Русский / 日本語 / Français)
- **Поддержка Multi-валют**
- **Нулевые Зависимости File+SQLite** `или обновление до Redis+MySQL`
- **100% Покрытие Тестами**
- **Самоуправляемые Ключи Приложения** Приватные ключи хранятся только в вашей таблице базы данных `apps` (с симметричным шифрованием, ключи генерируются вашим приложением)
- **12 Поля Потребления** - [Вычислить все необходимые поля Apple](#стратегия-полей-потребления)
- **Пересылка Сообщений Уведомлений** Сервер Apple отправляет в текущий сервис, текущий сервис пересылает на ваш продакшн сервер


## Онлайн Демо

🌐 **URL Демо**: [https://apple-refund-assistant.shiguopeng.cn/](https://apple-refund-assistant.shiguopeng.cn/)

> ⚠️ **Примечание**: Система перезапускается каждые 30 минут.

 
## Скриншоты
![Главная страница](assets/0.png)
![Главная страница](assets/1.png)
![Главная страница](assets/2.png)
![Главная страница](assets/3.png)
![Главная страница](assets/4.png)
![Главная страница](assets/5.png)


## Быстрый Старт
### Использование Предварительно Собранного Образца
```bash
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```


### Локальная Сборка и Запуск
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

## Стратегия Полей Потребления
* Документация: [https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest](https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest)
* Код Стратегии: [ConsumptionService.php](./app/Services/ConsumptionService.php) 
* Поля таблицы `users` могут быть обновлены другими системами

| Поле                       | Описание                | Источник Данных                          | Правило Вычисления                                                                                           |
|--------------------------|-------------------|--------------------------------|------------------------------------------------------------------------------------------------|
| accountTenure            | Дни регистрации пользователя            | `users.register_at`            | Текущее время минус время регистрации                                                                                     |
| appAccountToken          | Токен аккаунта          | `users.app_account_token`      | [Нужно передавать при создании клиентом заказа](https://developer.apple.com/documentation/StoreKit/Transaction/appAccountToken) |
| consumptionStatus        | Статус потребления              | `transactions.expiration_date` | Сравнить с текущим временем, если истек срок вернуть потреблено                                                                              |
| customerConsented        | Согласие пользователя предоставить данные          | Нет                              | Жестко закодировано `true`                                                                                       |
| deliveryStatus           | Успешно ли доставлена функциональная внутриигровая покупка. | Нет                              | Жестко закодировано `0`(нормальная доставка)                                                                                    |
| lifetimeDollarsPurchased | Общая сумма внутриигровых покупок             | `users.purchased_dollars`      | Накопить это поле на основе событий транзакций Apple, вы также можете накапливать сами                                                                        |
| lifetimeDollarsRefunded  | Общая сумма возвратов             | `users.refunded_dollars`       | Накопить это поле на основе событий возвратов Apple, вы также можете накапливать сами                                                                        |
| platform                 | Платформа                | Нет                              | Жестко закодировано `1`(apple)                                                                                   |
| playTime                 | Значение времени использования приложения клиентом        | `users.play_seconds`           | Ваша система должна поддерживать обновление этого поля, иначе это `0`                                                                          |
| refundPreference         | Ожидаемый результат запроса возврата         | `transactions.expiration_date` | Сравнить с текущим временем, если истек срок надеяться отклонить возврат                                                                             |
| sampleContentProvided    | Предоставляется ли пробная версия            | `apps.sample_content_provided` | Настроить приложение при создании приложения                                                                                      |
| userStatus               | Статус пользователя              | Нет                              | Жестко закодировано `1`(нормальный пользователь)                                                                                   |

## Планы на Будущее
- Есть другие идеи или заинтересованы в сотрудничестве? Пожалуйста, отправьте issue на GitHub - мы ждем ваших отзывов!

## Благодарности
* [Rates By Exchange Rate API](https://www.exchangerate-api.com)
* [https://github.com/argus-sight/refund-swatter-lite](https://github.com/argus-sight/refund-swatter-lite)