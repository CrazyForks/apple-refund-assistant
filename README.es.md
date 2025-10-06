
## Asistente de Reembolsos de Apple

[English](./README.md) | [简体中文](./README.zh.md) | Español | [हिन्दी](./README.hi.md) | [العربية](./README.ar.md) | [Português](./README.pt.md) | [Русский](./README.ru.md) | [日本語](./README.ja.md) | [Français](./README.fr.md)

Un servicio de prevención de reembolsos de pagos multi-tenant basado en Laravel.

![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/seth-shi/apple-refund-assistant/laravel.yml)
![Codecov](https://img.shields.io/codecov/c/github/seth-shi/apple-refund-assistant)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/seth-shi/apple-refund-assistant?utm_source=oss&utm_medium=github&utm_campaign=seth-shi%2Fapple-refund-assistant&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

## Demostración en Vivo

🌐 **Sitio de Demostración**: [https://apple-refund-assistant.shiguopeng.cn/](https://apple-refund-assistant.shiguopeng.cn/)

> ⚠️ **Nota**: El sistema se reiniciará cada 30 minutos.

## Descripción General

Procesa las notificaciones CONSUMPTION_REQUEST de Apple en tiempo real y envía inmediatamente la información de consumo a Apple, ayudando a reducir los reembolsos fraudulentos.


- **Soporte Multi-moneda**
- **Soporte Multi-tenant**
- **Soporte Multi-idioma (中文 / English / Español / हिन्दी / العربية / Português / Русский / 日本語 / Français)**
- **Sin Dependencias - Inicia el servicio local directamente para un despliegue más rápido**

| Dependencia | Sin Dependencias |  Avanzado   |
|-----|--|-----|
|  Base de Datos   | sqlite | MySQL |
|  Caché   | file | redis  |
|   Sesión | file |  redis   |
- API **Webhook** con **100%** de cobertura de pruebas
- **Claves Auto-gestionadas** - Las claves privadas solo se almacenan en tu tabla de base de datos `apps` (con cifrado simétrico, claves generadas por tu aplicación)
- **12 Campos de Consumo** - Calcula todos los campos requeridos por Apple
- Soporte para reenvío de mensajes del servidor
  - El servidor de Apple envía al servicio actual, que reenvía a tu servidor de producción

 
## Capturas de Pantalla
![Página Principal](assets/0.png)
![Página Principal](assets/1.png)
![Página Principal](assets/2.png)
![Página Principal](assets/3.png)
![Página Principal](assets/4.png)


## Inicio Rápido
### Usando Imagen Pre-construida
```bash
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```


### Construir y Ejecutar Localmente
```bash
git clone https://github.com/seth-shi/apple-refund-assistant
cd apple-refund-assistant
## Construir imagen y desplegar
./deploy.sh
```

### Si Necesitas Montar Datos
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
| accountTenure            | Días desde el registro del usuario            | `users.register_at`            | Tiempo actual menos tiempo de registro                                                                                     |
| appAccountToken          | Token de cuenta          | `users.app_account_token`      | [Debe pasarse cuando el cliente crea el pedido](https://developer.apple.com/documentation/StoreKit/Transaction/appAccountToken) |
| consumptionStatus        | Estado de consumo              | `transactions.expiration_date` | Comparar con el tiempo actual, devolver consumido si expiró                                                                              |
| customerConsented        | Usuario consintió proporcionar datos          | N/A                              | Valor fijo `true`                                                                                       |
| deliveryStatus           | Si una compra funcional dentro de la app se entregó exitosamente | N/A                              | Valor fijo `0` (entrega normal)                                                                                    |
| lifetimeDollarsPurchased | Monto total de compras dentro de la app             | `users.purchased_dollars`      | Acumulado basado en eventos de transacción de Apple, o puedes acumular manualmente                                                                        |
| lifetimeDollarsRefunded  | Monto total de reembolsos             | `users.refunded_dollars`       | Acumulado basado en eventos de reembolso de Apple, o puedes acumular manualmente                                                                        |
| platform                 | Plataforma                | N/A                              | Valor fijo `1` (apple)                                                                                   |
| playTime                 | Valor de tiempo de uso de la app del cliente        | `users.play_seconds`           | Tu sistema necesita soportar la actualización de este campo, de lo contrario es `0`                                                                          |
| refundPreference         | Resultado esperado para la solicitud de reembolso         | `transactions.expiration_date` | Comparar con el tiempo actual, preferir rechazar el reembolso si expiró                                                                             |
| sampleContentProvided    | Si se proporciona prueba            | `apps.sample_content_provided` | Configurar al crear la app                                                                                      |
| userStatus               | Estado del usuario              | N/A                              | Valor fijo `1` (usuario normal)                                                                                   |



## Licencia

Licenciado bajo Apache License 2.0, ver [LICENSE](./LICENSE) para detalles.

## Soporte

Para preguntas o inquietudes, por favor envía un issue en GitHub.

## Planes Futuros
- ¿Tienes otras ideas o estás interesado en colaborar? Por favor envía un issue en GitHub - ¡esperamos tus comentarios!

## Agradecimientos
* [Rates By Exchange Rate API](https://www.exchangerate-api.com)

