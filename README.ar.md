
## مساعد استرداد أموال Apple

[English](./README.md) | [简体中文](./README.zh.md) | [Español](./README.es.md) | [हिन्दी](./README.hi.md) | العربية | [Português](./README.pt.md) | [Русский](./README.ru.md) | [日本語](./README.ja.md) | [Français](./README.fr.md)

خدمة منع استرداد المدفوعات متعددة المستأجرين بناءً على Laravel.

![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/seth-shi/apple-refund-assistant/laravel.yml)
![Codecov](https://img.shields.io/codecov/c/github/seth-shi/apple-refund-assistant)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/seth-shi/apple-refund-assistant?utm_source=oss&utm_medium=github&utm_campaign=seth-shi%2Fapple-refund-assistant&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

## نظرة عامة

معالجة إشعارات CONSUMPTION_REQUEST من Apple في الوقت الفعلي وإرسال معلومات الاستهلاك فوراً إلى Apple، مما يساعد في تقليل عمليات الاسترداد الاحتيالية.


- **دعم متعدد العملات**
- **دعم متعدد المستأجرين**
- **دعم متعدد اللغات (中文 / English / Español / हिन्दी / العربية / Português / Русский / 日本語 / Français)**
- **بدون تبعيات - ابدأ الخدمة المحلية مباشرة لنشر أسرع**

| التبعية | بدون تبعيات |  متقدم   |
|-----|--|-----|
|  قاعدة البيانات   | sqlite | MySQL |
|  ذاكرة التخزين المؤقت   | file | redis  |
|   الجلسة | file |  redis   |
- واجهة برمجة التطبيقات **Webhook** مع تغطية اختبار **100%**
- **مفاتيح ذاتية الإدارة** - يتم تخزين المفاتيح الخاصة فقط في جدول قاعدة البيانات `apps` (مع التشفير المتماثل، المفاتيح المولدة بواسطة تطبيقك)
- **12 حقل استهلاك** - احسب جميع حقول Apple المطلوبة
- دعم إعادة توجيه رسائل الخادم
  - يرسل خادم Apple إلى الخدمة الحالية، والتي تعيد التوجيه إلى خادم الإنتاج الخاص بك

 
## لقطات الشاشة
![الصفحة الرئيسية](assets/0.png)
![الصفحة الرئيسية](assets/1.png)
![الصفحة الرئيسية](assets/2.png)
![الصفحة الرئيسية](assets/3.png)
![الصفحة الرئيسية](assets/4.png)


## البدء السريع
### استخدام صورة مسبقة البناء
```bash
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```


### البناء والتشغيل محلياً
```bash
git clone https://github.com/seth-shi/apple-refund-assistant
cd apple-refund-assistant
## بناء الصورة والنشر
./deploy.sh
```

### إذا كنت بحاجة إلى تثبيت البيانات
```
touch database.sqlite
docker run -d \
  -p 8080:8080 \
  -v $(pwd)/database.sqlite:/var/www/html/database/database.sqlite \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```

## استراتيجية حقول الاستهلاك
* التوثيق: [https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest](https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest)
* كود الاستراتيجية: [ConsumptionService.php](./app/Services/ConsumptionService.php) 
* يمكن تحديث حقول جدول `users` بواسطة أنظمة أخرى

| الحقل                       | الوصف                | مصدر البيانات                          | قاعدة الحساب                                                                                           |
|--------------------------|-------------------|--------------------------------|------------------------------------------------------------------------------------------------|
| accountTenure            | عدد الأيام منذ تسجيل المستخدم            | `users.register_at`            | الوقت الحالي ناقص وقت التسجيل                                                                                     |
| appAccountToken          | رمز الحساب          | `users.app_account_token`      | [يجب تمريره عندما ينشئ العميل الطلب](https://developer.apple.com/documentation/StoreKit/Transaction/appAccountToken) |
| consumptionStatus        | حالة الاستهلاك              | `transactions.expiration_date` | مقارنة بالوقت الحالي، إرجاع مستهلك إذا انتهت الصلاحية                                                                              |
| customerConsented        | وافق المستخدم على تقديم البيانات          | N/A                              | قيمة ثابتة `true`                                                                                       |
| deliveryStatus           | ما إذا تم تسليم عملية شراء داخل التطبيق بنجاح | N/A                              | قيمة ثابتة `0` (تسليم عادي)                                                                                    |
| lifetimeDollarsPurchased | إجمالي مبلغ الشراء داخل التطبيق             | `users.purchased_dollars`      | متراكم بناءً على أحداث معاملات Apple، أو يمكنك التراكم يدوياً                                                                        |
| lifetimeDollarsRefunded  | إجمالي مبلغ الاسترداد             | `users.refunded_dollars`       | متراكم بناءً على أحداث استرداد Apple، أو يمكنك التراكم يدوياً                                                                        |
| platform                 | المنصة                | N/A                              | قيمة ثابتة `1` (apple)                                                                                   |
| playTime                 | قيمة وقت استخدام التطبيق للعميل        | `users.play_seconds`           | يحتاج نظامك إلى دعم تحديث هذا الحقل، وإلا فهو `0`                                                                          |
| refundPreference         | النتيجة المتوقعة لطلب الاسترداد         | `transactions.expiration_date` | مقارنة بالوقت الحالي، يفضل رفض الاسترداد إذا انتهت الصلاحية                                                                             |
| sampleContentProvided    | ما إذا تم توفير تجربة            | `apps.sample_content_provided` | التكوين عند إنشاء التطبيق                                                                                      |
| userStatus               | حالة المستخدم              | N/A                              | قيمة ثابتة `1` (مستخدم عادي)                                                                                   |



## الترخيص

مرخص بموجب Apache License 2.0، راجع [LICENSE](./LICENSE) للتفاصيل.

## الدعم

للأسئلة أو المخاوف، يرجى تقديم مشكلة على GitHub.

## الخطط المستقبلية
- هل لديك أفكار أخرى أو مهتم بالتعاون؟ يرجى تقديم مشكلة على GitHub - نتطلع إلى ملاحظاتك!

## شكر وتقدير
* [Rates By Exchange Rate API](https://www.exchangerate-api.com)

