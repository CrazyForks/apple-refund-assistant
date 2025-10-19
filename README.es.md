## apple-refund-assistant
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/seth-shi/apple-refund-assistant/laravel.yml)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=seth-shi_apple-refund-assistant&metric=coverage)](https://sonarcloud.io/summary/new_code?id=seth-shi_apple-refund-assistant)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=seth-shi_apple-refund-assistant&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=seth-shi_apple-refund-assistant)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/seth-shi/apple-refund-assistant?utm_source=oss&utm_medium=github&utm_campaign=seth-shi%2Fapple-refund-assistant&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

[English](./README.md) | [简体中文](./README.zh.md) | Español | [हिन्दी](./README.hi.md) | [العربية](./README.ar.md) | [Português](./README.pt.md) | [Русский](./README.ru.md) | [日本語](./README.ja.md) | [Français](./README.fr.md)

Este servicio está construido sobre la arquitectura multi-tenant de Laravel / Filament,
ayudando efectivamente a los desarrolladores a prevenir reembolsos fraudulentos procesando instantáneamente las notificaciones CONSUMPTION_REQUEST de Apple y devolviendo datos de consumo de forma asíncrona.

- **Soporte Multi-tenant**
- **Soporte Multi-idioma** (中文 / English / Español / हिन्दी / العربية / Português / Русский / 日本語 / Français)
- **Soporte Multi-moneda**
- **Cero Dependencias File+SQLite** `o actualizar a Redis+MySQL`
- **100% Cobertura de Pruebas**
- **Claves de Aplicación Auto-gestionadas** Las claves privadas solo se almacenan en tu tabla de base de datos `apps` (con cifrado simétrico, claves generadas por tu aplicación)
- **12 Campos de Consumo** - [Calcular todos los campos requeridos de Apple](#estrategia-de-campos-de-consumo)
- **Reenvío de Mensajes de Notificación** El servidor de Apple envía al servicio actual, el servicio actual reenvía a tu servidor de producción


## Demo en Línea

🌐 **URL de Demo**: [https://apple-refund-assistant.shiguopeng.cn/](https://apple-refund-assistant.shiguopeng.cn/)

> ⚠️ **Nota**: El sistema se reinicia cada 30 minutos.

 
## Capturas de Pantalla
![Página Principal](assets/0.png)
![Página Principal](assets/1.png)
![Página Principal](assets/2.png)
![Página Principal](assets/3.png)
![Página Principal](assets/4.png)
![Página Principal](assets/5.png)


## Inicio Rápido
### Usando Imagen Pre-construida
```bash
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```


### Construcción y Ejecución Local
```bash
git clone https://github.com/seth-shi/apple-refund-assistant
cd apple-refund-assistant
## Construir imagen y desplegar
./deploy.sh
```

### Si necesitas montar datos
```
touch database.sqlite
docker run -d \
  -p 8080:8080 \
  -v $(pwd)/database.sqlite:/var/www/html/database/database.sqlite \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```

## Estrategia de Campos de Consumo
* Documentación: [https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest](https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest)
* Código de Estrategia: [ConsumptionService.php](./app/Services/ConsumptionService.php) 
* Los campos de la tabla `users` pueden ser actualizados por otros sistemas

| Campo                       | Descripción                | Fuente de Datos                          | Regla de Cálculo                                                                                           |
|--------------------------|-------------------|--------------------------------|------------------------------------------------------------------------------------------------|
| accountTenure            | Días de registro del usuario            | `users.register_at`            | Tiempo actual menos tiempo de registro                                                                                     |
| appAccountToken          | Token de cuenta          | `users.app_account_token`      | [Necesita ser pasado cuando el cliente crea orden](https://developer.apple.com/documentation/StoreKit/Transaction/appAccountToken) |
| consumptionStatus        | Estado de consumo              | `transactions.expiration_date` | Comparar con tiempo actual, si expirado retornar consumido                                                                              |
| customerConsented        | Consentimiento del usuario para proporcionar datos          | Ninguno                              | Codificado `true`                                                                                       |
| deliveryStatus           | Si se entregó exitosamente una compra in-app funcional. | Ninguno                              | Codificado `0`(entrega normal)                                                                                    |
| lifetimeDollarsPurchased | Cantidad total de compras in-app             | `users.purchased_dollars`      | Acumular este campo basado en eventos de transacción de Apple, también puedes acumularlo tú mismo                                                                        |
| lifetimeDollarsRefunded  | Cantidad total de reembolsos             | `users.refunded_dollars`       | Acumular este campo basado en eventos de reembolso de Apple, también puedes acumularlo tú mismo                                                                        |
| platform                 | Plataforma                | Ninguno                              | Codificado `1`(apple)                                                                                   |
| playTime                 | Valor de tiempo de uso de la app del cliente        | `users.play_seconds`           | Tu sistema necesita soportar actualizar este campo, de lo contrario es `0`                                                                          |
| refundPreference         | Resultado esperado de solicitud de reembolso         | `transactions.expiration_date` | Comparar con tiempo actual, si expirado esperar rechazar reembolso                                                                             |
| sampleContentProvided    | Si se proporciona prueba            | `apps.sample_content_provided` | Configurar app al crear app                                                                                      |
| userStatus               | Estado del usuario              | Ninguno                              | Codificado `1`(usuario normal)                                                                                   |

## Planes Futuros
- ¿Tienes otras ideas o estás interesado en colaboración? ¡Por favor envía un issue en GitHub - esperamos tu feedback!

## Agradecimientos
* [Rates By Exchange Rate API](https://www.exchangerate-api.com)
* [https://github.com/argus-sight/refund-swatter-lite](https://github.com/argus-sight/refund-swatter-lite)