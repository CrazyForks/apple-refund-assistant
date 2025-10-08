## apple-refund-assistant
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/seth-shi/apple-refund-assistant/laravel.yml)
![Codecov](https://img.shields.io/codecov/c/github/seth-shi/apple-refund-assistant)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/seth-shi/apple-refund-assistant?utm_source=oss&utm_medium=github&utm_campaign=seth-shi%2Fapple-refund-assistant&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

[English](./README.md) | [简体中文](./README.zh.md) | [Español](./README.es.md) | [हिन्दी](./README.hi.md) | [العربية](./README.ar.md) | [Português](./README.pt.md) | [Русский](./README.ru.md) | [日本語](./README.ja.md) | Français

Ce service est construit sur l'architecture multi-tenant Laravel / Filament,
aidant efficacement les développeurs à prévenir les remboursements frauduleux en traitant instantanément les notifications CONSUMPTION_REQUEST d'Apple et en retournant les données de consommation de manière asynchrone.

- **Support Multi-tenant**
- **Support Multi-langues** (中文 / English / Español / हिन्दी / العربية / Português / Русский / 日本語 / Français)
- **Support Multi-devises**
- **Zéro Dépendance File+SQLite** `ou mise à niveau vers Redis+MySQL`
- **100% Couverture de Tests**
- **Clés d'Application Auto-gérées** Les clés privées ne sont stockées que dans votre table de base de données `apps` (avec chiffrement symétrique, clés générées par votre application)
- **12 Champs de Consommation** - [Calculer tous les champs Apple requis](#stratégie-des-champs-de-consommation)
- **Transfert de Messages de Notification** Le serveur Apple envoie au service actuel, le service actuel transfère vers votre serveur de production


## Démo en Ligne

🌐 **URL de Démo**: [https://apple-refund-assistant.shiguopeng.cn/](https://apple-refund-assistant.shiguopeng.cn/)

> ⚠️ **Note**: Le système redémarre toutes les 30 minutes.

 
## Captures d'Écran
![Page d'Accueil](assets/0.png)
![Page d'Accueil](assets/1.png)
![Page d'Accueil](assets/2.png)
![Page d'Accueil](assets/3.png)
![Page d'Accueil](assets/4.png)
![Page d'Accueil](assets/5.png)


## Démarrage Rapide
### Utilisation d'Image Pré-construite
```bash
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  ghcr.io/seth-shi/apple-refund-assistant:latest
```


### Construction et Exécution Locale
```bash
git clone https://github.com/seth-shi/apple-refund-assistant
cd apple-refund-assistant
## Construire l'image et déployer
./deploy.sh
```

### Si vous devez monter des données
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
* Documentation: [https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest](https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest)
* Code de Stratégie: [ConsumptionService.php](./app/Services/ConsumptionService.php) 
* Les champs de la table `users` peuvent être mis à jour par d'autres systèmes

| Champ                       | Description                | Source de Données                          | Règle de Calcul                                                                                           |
|--------------------------|-------------------|--------------------------------|------------------------------------------------------------------------------------------------|
| accountTenure            | Jours d'inscription utilisateur            | `users.register_at`            | Temps actuel moins temps d'inscription                                                                                     |
| appAccountToken          | Token de compte          | `users.app_account_token`      | [Doit être passé quand le client crée une commande](https://developer.apple.com/documentation/StoreKit/Transaction/appAccountToken) |
| consumptionStatus        | Statut de consommation              | `transactions.expiration_date` | Comparer avec le temps actuel, si expiré retourner consommé                                                                              |
| customerConsented        | Consentement utilisateur à fournir des données          | Aucun                              | Codé en dur `true`                                                                                       |
| deliveryStatus           | Si un achat in-app fonctionnel a été livré avec succès. | Aucun                              | Codé en dur `0`(livraison normale)                                                                                    |
| lifetimeDollarsPurchased | Montant total des achats in-app             | `users.purchased_dollars`      | Accumuler ce champ basé sur les événements de transaction Apple, vous pouvez aussi l'accumuler vous-même                                                                        |
| lifetimeDollarsRefunded  | Montant total des remboursements             | `users.refunded_dollars`       | Accumuler ce champ basé sur les événements de remboursement Apple, vous pouvez aussi l'accumuler vous-même                                                                        |
| platform                 | Plateforme                | Aucun                              | Codé en dur `1`(apple)                                                                                   |
| playTime                 | Valeur du temps d'utilisation de l'app par le client        | `users.play_seconds`           | Votre système doit supporter la mise à jour de ce champ, sinon c'est `0`                                                                          |
| refundPreference         | Résultat attendu de la demande de remboursement         | `transactions.expiration_date` | Comparer avec le temps actuel, si expiré espérer rejeter le remboursement                                                                             |
| sampleContentProvided    | Si un essai est fourni            | `apps.sample_content_provided` | Configurer l'app lors de la création de l'app                                                                                      |
| userStatus               | Statut utilisateur              | Aucun                              | Codé en dur `1`(utilisateur normal)                                                                                   |

## Plans Futurs
- Avez-vous d'autres idées ou êtes-vous intéressé par la collaboration ? Veuillez soumettre un issue sur GitHub - nous attendons vos commentaires !

## Remerciements
* [Rates By Exchange Rate API](https://www.exchangerate-api.com)
* [https://github.com/argus-sight/refund-swatter-lite](https://github.com/argus-sight/refund-swatter-lite)