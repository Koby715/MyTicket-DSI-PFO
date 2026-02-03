# Guide d'Intégration : API Outlook pour Tickets

Ce document explique comment le système d'envoi d'email via Outlook a été intégré dans votre application de Tickets.

## 1. Architecture

Le système fonctionne désormais en deux parties :
*   **PHP (Client)** : Le fichier `traitement-ticket.php` prépare les données du ticket et les envoie à l'API locale.
*   **Python (Serveur)** : Le script `main.py` reçoit la demande et utilise votre Outlook local pour envoyer le mail.

## 2. Prérequis pour que les mails partent

Pour que les emails soient envoyés lors de la création d'un ticket, **deux conditions doivent être réunies sur le serveur/poste** :

1.  **Outlook doit être ouvert** (ou au moins configuré) sur la session Windows active.
2.  **L'API Python doit être lancée**.

## 3. Comment lancer le système

### Étape 1 : Lancer l'API Python
Ouvrez un terminal (CMD ou PowerShell) dans le dossier où se trouve `main.py` et lancez :

```powershell
python main.py
```

Vous devriez voir un message : `Uvicorn running on http://127.0.0.1:8000`.
**Ne fermez pas cette fenêtre**, sinon l'API s'arrête.

### Étape 2 : Tester
Créez un ticket depuis l'interface web habituelle via `ajout-ticket.php`.
Si tout fonctionne :
*   Le ticket est créé en base de données.
*   Le terminal Python affiche une ligne `POST /send-mail 200 OK`.
*   Le mail apparaît dans la **Boîte d'envoi** de votre Outlook.

## 4. En cas de problème

Si les mails ne partent pas :

*   **Vérifiez le terminal Python** : Y a-t-il une erreur rouge ? (Ex: Outlook fermé).
*   **Vérifiez les logs PHP** : Le fichier `traitement-ticket.php` écrit dans le log d'erreur PHP (`php_error_log`) si l'API répond une erreur.
*   **Pièces jointes** : Assurez-vous que les fichiers uploadés sont bien accessibles par le script.

## 5. Retour en arrière (Rollback)

Si vous souhaitez revenir à l'ancien système d'envoi SMTP :
1.  Ouvrez `traitement-ticket.php`.
2.  Cherchez la fin du fichier (vers la ligne 315).
3.  Commentez la nouvelle ligne et décommentez l'ancienne :

```php
// $emailSent = sendTicketEmailViaLocalAPI(...); // NOUVEAU (Désactivé)
$emailSent = sendTicketEmail(...); // ANCIEN (Réactivé)
```
