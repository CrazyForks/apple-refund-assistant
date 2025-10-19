## apple-refund-assistant
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/seth-shi/apple-refund-assistant/laravel.yml)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=seth-shi_apple-refund-assistant&metric=coverage)](https://sonarcloud.io/summary/new_code?id=seth-shi_apple-refund-assistant)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=seth-shi_apple-refund-assistant&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=seth-shi_apple-refund-assistant)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/seth-shi/apple-refund-assistant?utm_source=oss&utm_medium=github&utm_campaign=seth-shi%2Fapple-refund-assistant&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

[English](./README.md) | [简体中文](./README.zh.md) | [Español](./README.es.md) | हिन्दी | [العربية](./README.ar.md) | [Português](./README.pt.md) | [Русский](./README.ru.md) | [日本語](./README.ja.md) | [Français](./README.fr.md)

यह सेवा Laravel / Filament मल्टी-टेनेंट आर्किटेक्चर पर बनाई गई है,
Apple के CONSUMPTION_REQUEST नोटिफिकेशन को तुरंत प्रोसेस करके और खपत डेटा को एसिंक्रोनस रूप से वापस करके डेवलपर्स को धोखाधड़ी रिफंड को रोकने में प्रभावी रूप से मदद करती है।

- **मल्टी-टेनेंट सपोर्ट**
- **मल्टी-लैंग्वेज सपोर्ट** (中文 / English / Español / हिन्दी / العربية / Português / Русский / 日本語 / Français)
- **मल्टी-करेंसी सपोर्ट**
- **ज़ीरो डिपेंडेंसी File+SQLite** `या Redis+MySQL में अपग्रेड करें`
- **100% टेस्ट कवरेज**
- **सेल्फ-मैनेज्ड ऐप कीज़** प्राइवेट कीज़ केवल आपके डेटाबेस `apps` टेबल में स्टोर होती हैं (सिमेट्रिक एन्क्रिप्शन के साथ, आपके एप्लिकेशन द्वारा जेनरेट की गई कीज़)
- **12 कंजम्पशन फील्ड्स** - [सभी आवश्यक Apple फील्ड्स की गणना करें](#कंजम्पशन-फील्ड-स्ट्रैटेजी)
- **नोटिफिकेशन मैसेज फॉरवर्डिंग** Apple सर्वर वर्तमान सेवा को भेजता है, वर्तमान सेवा आपके प्रोडक्शन सर्वर को फॉरवर्ड करती है


## ऑनलाइन डेमो

🌐 **डेमो URL**: [https://apple-refund-assistant.shiguopeng.cn/](https://apple-refund-assistant.shiguopeng.cn/)

> ⚠️ **नोट**: सिस्टम हर 30 मिनट में रीसेट होता है।

 
## स्क्रीनशॉट्स
![होमपेज](assets/0.png)
![होमपेज](assets/1.png)
![होमपेज](assets/2.png)
![होमपेज](assets/3.png)
![होमपेज](assets/4.png)
![होमपेज](assets/5.png)


## क्विक स्टार्ट
### पहले से बिल्ट इमेज का उपयोग करना
```bash
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```


### लोकल बिल्ड और रन
```bash
git clone https://github.com/seth-shi/apple-refund-assistant
cd apple-refund-assistant
## इमेज बिल्ड करें और डिप्लॉय करें
./deploy.sh
```

### अगर आपको डेटा माउंट करना है
```
touch database.sqlite
docker run -d \
  -p 8080:8080 \
  -v $(pwd)/database.sqlite:/var/www/html/database/database.sqlite \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```

## कंजम्पशन फील्ड स्ट्रैटेजी
* डॉक्यूमेंटेशन: [https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest](https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest)
* स्ट्रैटेजी कोड: [ConsumptionService.php](./app/Services/ConsumptionService.php) 
* `users` टेबल फील्ड्स को अन्य सिस्टम द्वारा अपडेट किया जा सकता है

| फील्ड                       | विवरण                | डेटा सोर्स                          | गणना नियम                                                                                           |
|--------------------------|-------------------|--------------------------------|------------------------------------------------------------------------------------------------|
| accountTenure            | यूजर रजिस्ट्रेशन दिन            | `users.register_at`            | वर्तमान समय घटा रजिस्ट्रेशन समय                                                                                     |
| appAccountToken          | अकाउंट टोकन          | `users.app_account_token`      | [क्लाइंट द्वारा ऑर्डर बनाते समय पास करना आवश्यक](https://developer.apple.com/documentation/StoreKit/Transaction/appAccountToken) |
| consumptionStatus        | कंजम्पशन स्टेटस              | `transactions.expiration_date` | वर्तमान समय के साथ तुलना करें, यदि एक्सपायर हो गया है तो कंज्यूम्ड रिटर्न करें                                                                              |
| customerConsented        | यूजर डेटा प्रदान करने की सहमति          | कोई नहीं                              | हार्डकोडेड `true`                                                                                       |
| deliveryStatus           | क्या सफलतापूर्वक एक फंक्शनल इन-ऐप खरीदारी डिलीवर की गई। | कोई नहीं                              | हार्डकोडेड `0`(सामान्य डिलीवरी)                                                                                    |
| lifetimeDollarsPurchased | कुल इन-ऐप खरीदारी राशि             | `users.purchased_dollars`      | Apple ट्रांजैक्शन इवेंट्स के आधार पर इस फील्ड को जमा करें, आप इसे स्वयं भी जमा कर सकते हैं                                                                        |
| lifetimeDollarsRefunded  | कुल रिफंड राशि             | `users.refunded_dollars`       | Apple रिफंड इवेंट्स के आधार पर इस फील्ड को जमा करें, आप इसे स्वयं भी जमा कर सकते हैं                                                                        |
| platform                 | प्लेटफॉर्म                | कोई नहीं                              | हार्डकोडेड `1`(apple)                                                                                   |
| playTime                 | कस्टमर ऐप उपयोग समय मूल्य        | `users.play_seconds`           | आपके सिस्टम को इस फील्ड को अपडेट करने का सपोर्ट करना होगा, अन्यथा यह `0` है                                                                          |
| refundPreference         | रिफंड अनुरोध का अपेक्षित परिणाम         | `transactions.expiration_date` | वर्तमान समय के साथ तुलना करें, यदि एक्सपायर हो गया है तो रिफंड को रिजेक्ट करने की आशा करें                                                                             |
| sampleContentProvided    | क्या ट्रायल प्रदान किया गया है            | `apps.sample_content_provided` | ऐप बनाते समय ऐप को कॉन्फ़िगर करें                                                                                      |
| userStatus               | यूजर स्टेटस              | कोई नहीं                              | हार्डकोडेड `1`(सामान्य यूजर)                                                                                   |

## भविष्य की योजनाएं
- क्या आपके पास अन्य विचार हैं या सहयोग में रुचि है? कृपया GitHub पर एक issue सबमिट करें - हम आपके फीडबैक की प्रतीक्षा कर रहे हैं!

## आभार
* [Rates By Exchange Rate API](https://www.exchangerate-api.com)
* [https://github.com/argus-sight/refund-swatter-lite](https://github.com/argus-sight/refund-swatter-lite)