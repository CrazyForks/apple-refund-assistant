
## Assistant de Remboursement Apple

[English](./README.md) | [简体中文](./README.zh.md) | [Español](./README.es.md) | [हिन्दी](./README.hi.md) | [العربية](./README.ar.md) | [Português](./README.pt.md) | [Русский](./README.ru.md) | [日本語](./README.ja.md) | Français

Un service de prévention des remboursements de paiement multi-tenant basé sur Laravel.

![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/seth-shi/apple-refund-assistant/laravel.yml)
![Codecov](https://img.shields.io/codecov/c/github/seth-shi/apple-refund-assistant)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/seth-shi/apple-refund-assistant?utm_source=oss&utm_medium=github&utm_campaign=seth-shi%2Fapple-refund-assistant&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

## Démonstration en Direct

🌐 **Site de Démonstration**: [https://apple-refund-assistant.shiguopeng.cn/](https://apple-refund-assistant.shiguopeng.cn/)

> ⚠️ **Remarque**: Le système sera réinitialisé toutes les 30 minutes.

## Aperçu

Traitez les notifications CONSUMPTION_REQUEST d'Apple en temps réel et envoyez immédiatement les informations de consommation à Apple, aidant à réduire les remboursements frauduleux.


- **Support Multi-devises**
- **Support Multi-tenant**
- **Support Multi-langues (中文 / English / Español / हिन्दी / العربية / Português / Русский / 日本語 / Français)**
- **Zéro Dépendance - Démarrez le service local directement pour un déploiement plus rapide**

| Dépendance | Zéro Dépendance |  Avancé   |
|-----|--|-----|
|  Base de données   | sqlite | MySQL |
|  Cache   | file | redis  |
|   Session | file |  redis   |
- API **Webhook** avec **100%** de couverture de tests
- **Clés Auto-gérées** - Les clés privées sont uniquement stockées dans votre table de base de données `apps` (avec chiffrement symétrique, clés générées par votre application)
- **12 Champs de Consommation** - Calcule tous les champs Apple requis
- Support du transfert de messages du serveur
  - Le serveur Apple envoie au service actuel, qui transfère à votre serveur de production

 
## Captures d'écran
![Page d'accueil](assets/0.png)
![Page d'accueil](assets/1.png)
![Page d'accueil](assets/2.png)
![Page d'accueil](assets/3.png)
![Page d'accueil](assets/4.png)


## Démarrage Rapide
### Utilisation d'une Image Pré-construite
```bash
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```


### Construire et Exécuter Localement
```bash
git clone https://github.com/seth-shi/apple-refund-assistant
cd apple-refund-assistant
## Construire l'image et déployer
./deploy.sh
```

### Si Vous Devez Monter des Données
```
touch database.sqlite
docker run -d \
  -p 8080:8080 \
  -v $(pwd)/database.sqlite:/var/www/html/database/database.sqlite \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```

## Stratégie des Champs de Consommation
* Documentation : [https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest](https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest)
* Code de Stratégie : [ConsumptionService.php](./app/Services/ConsumptionService.php) 
* Les champs de la table `users` peuvent être mis à jour par d'autres systèmes

| Champ                       | Description                | Source de Données                          | Règle de Calcul                                                                                           |
|--------------------------|-------------------|--------------------------------|------------------------------------------------------------------------------------------------|
| accountTenure            | Jours depuis l'inscription de l'utilisateur            | `users.register_at`            | Temps actuel moins temps d'inscription                                                                                     |
| appAccountToken          | Token de compte          | `users.app_account_token`      | [Doit être transmis lorsque le client crée la commande](https://developer.apple.com/documentation/StoreKit/Transaction/appAccountToken) |
| consumptionStatus        | Statut de consommation              | `transactions.expiration_date` | Comparer avec le temps actuel, retourner consommé si expiré                                                                              |
| customerConsented        | L'utilisateur a consenti à fournir des données          | N/A                              | Codé en dur `true`                                                                                       |
| deliveryStatus           | Si un achat in-app fonctionnel a été livré avec succès | N/A                              | Codé en dur `0` (livraison normale)                                                                                    |
| lifetimeDollarsPurchased | Montant total des achats in-app             | `users.purchased_dollars`      | Accumulé en fonction des événements de transaction Apple, ou vous pouvez accumuler manuellement                                                                        |
| lifetimeDollarsRefunded  | Montant total des remboursements             | `users.refunded_dollars`       | Accumulé en fonction des événements de remboursement Apple, ou vous pouvez accumuler manuellement                                                                        |
| platform                 | Plateforme                | N/A                              | Codé en dur `1` (apple)                                                                                   |
| playTime                 | Valeur du temps d'utilisation de l'app par le client        | `users.play_seconds`           | Votre système doit prendre en charge la mise à jour de ce champ, sinon c'est `0`                                                                          |
| refundPreference         | Résultat attendu pour la demande de remboursement         | `transactions.expiration_date` | Comparer avec le temps actuel, préférer rejeter le remboursement si expiré                                                                             |
| sampleContentProvided    | Si un essai est fourni            | `apps.sample_content_provided` | Configurer lors de la création de l'app                                                                                      |
| userStatus               | Statut de l'utilisateur              | N/A                              | Codé en dur `1` (utilisateur normal)                                                                                   |



## Licence

Sous licence Apache License 2.0, voir [LICENSE](./LICENSE) pour les détails.

## Support

Pour toute question ou préoccupation, veuillez soumettre un problème sur GitHub.

## Plans Futurs
- Vous avez d'autres idées ou êtes intéressé par une collaboration ? Veuillez soumettre un problème sur GitHub - nous attendons vos retours !

## Remerciements
* [Rates By Exchange Rate API](https://www.exchangerate-api.com)

