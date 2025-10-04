
## Assistente de Reembolso da Apple

[English](./README.md) | [简体中文](./README.zh.md) | [Español](./README.es.md) | [हिन्दी](./README.hi.md) | [العربية](./README.ar.md) | Português | [Русский](./README.ru.md) | [日本語](./README.ja.md) | [Français](./README.fr.md)

Um serviço de prevenção de reembolso de pagamentos multi-tenant baseado em Laravel.

![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/seth-shi/apple-refund-assistant/laravel.yml)
![Codecov](https://img.shields.io/codecov/c/github/seth-shi/apple-refund-assistant)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/seth-shi/apple-refund-assistant?utm_source=oss&utm_medium=github&utm_campaign=seth-shi%2Fapple-refund-assistant&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

## Visão Geral

Processa notificações CONSUMPTION_REQUEST da Apple em tempo real e envia imediatamente informações de consumo de volta para a Apple, ajudando a reduzir reembolsos fraudulentos.


- **Suporte Multi-moeda**
- **Suporte Multi-tenant**
- **Suporte Multi-idioma (中文 / English / Español / हिन्दी / العربية / Português / Русский / 日本語 / Français)**
- **Zero Dependências - Inicie o serviço local diretamente para implantação mais rápida**

| Dependência | Zero Dependências |  Avançado   |
|-----|--|-----|
|  Banco de Dados   | sqlite | MySQL |
|  Cache   | file | redis  |
|   Sessão | file |  redis   |
- API **Webhook** com **100%** de cobertura de testes
    - [x] Cobertura completa (Services\WebhookService)
- **Chaves Auto-gerenciadas** - Chaves privadas são armazenadas apenas na sua tabela de banco de dados `apps` (com criptografia simétrica, chaves geradas pela sua aplicação)
- **12 Campos de Consumo** - Calcula todos os campos Apple necessários
- Suporte para encaminhamento de mensagens do servidor
  - Servidor Apple envia para o serviço atual, que encaminha para o seu servidor de produção

 
## Capturas de Tela
![Página Inicial](assets/0.png)
![Página Inicial](assets/1.png)
![Página Inicial](assets/2.png)
![Página Inicial](assets/3.png)
![Página Inicial](assets/4.png)


## Início Rápido
### Usando Imagem Pré-construída
```bash
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```


### Construir e Executar Localmente
```bash
git clone https://github.com/seth-shi/apple-refund-assistant
cd apple-refund-assistant
## Construir imagem e implantar
./deploy.sh
```

### Se Você Precisar Montar Dados
```
touch database.sqlite
docker run -d \
  -p 8080:8080 \
  -v $(pwd)/database.sqlite:/var/www/html/database/database.sqlite \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```

## Estratégia de Campos de Consumo
* Documentação: [https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest](https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest)
* Código de Estratégia: [ConsumptionService.php](./app/Services/ConsumptionService.php) 
* Campos da tabela `users` podem ser atualizados por outros sistemas

| Campo                       | Descrição                | Fonte de Dados                          | Regra de Cálculo                                                                                           |
|--------------------------|-------------------|--------------------------------|------------------------------------------------------------------------------------------------|
| accountTenure            | Dias desde o registro do usuário            | `users.register_at`            | Tempo atual menos tempo de registro                                                                                     |
| appAccountToken          | Token da conta          | `users.app_account_token`      | [Deve ser passado quando o cliente cria o pedido](https://developer.apple.com/documentation/StoreKit/Transaction/appAccountToken) |
| consumptionStatus        | Status de consumo              | `transactions.expiration_date` | Comparar com o tempo atual, retornar consumido se expirado                                                                              |
| customerConsented        | Usuário consentiu em fornecer dados          | N/A                              | Valor fixo `true`                                                                                       |
| deliveryStatus           | Se uma compra funcional dentro do app foi entregue com sucesso | N/A                              | Valor fixo `0` (entrega normal)                                                                                    |
| lifetimeDollarsPurchased | Valor total de compras dentro do app             | `users.purchased_dollars`      | Acumulado com base em eventos de transação da Apple, ou você pode acumular manualmente                                                                        |
| lifetimeDollarsRefunded  | Valor total de reembolsos             | `users.refunded_dollars`       | Acumulado com base em eventos de reembolso da Apple, ou você pode acumular manualmente                                                                        |
| platform                 | Plataforma                | N/A                              | Valor fixo `1` (apple)                                                                                   |
| playTime                 | Valor de tempo de uso do app pelo cliente        | `users.play_seconds`           | Seu sistema precisa suportar a atualização deste campo, caso contrário é `0`                                                                          |
| refundPreference         | Resultado esperado para solicitação de reembolso         | `transactions.expiration_date` | Comparar com o tempo atual, preferir rejeitar reembolso se expirado                                                                             |
| sampleContentProvided    | Se o teste é fornecido            | `apps.sample_content_provided` | Configurar ao criar o app                                                                                      |
| userStatus               | Status do usuário              | N/A                              | Valor fixo `1` (usuário normal)                                                                                   |



## Licença

Licenciado sob Apache License 2.0, veja [LICENSE](./LICENSE) para detalhes.

## Suporte

Para perguntas ou preocupações, por favor envie uma issue no GitHub.

## Planos Futuros
- Tem outras ideias ou interessado em colaboração? Por favor envie uma issue no GitHub - aguardamos seu feedback!

## Agradecimentos
* [Rates By Exchange Rate API](https://www.exchangerate-api.com)

