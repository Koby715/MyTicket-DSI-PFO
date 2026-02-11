# 📧 Templates d'Emails Professionnels - Documentation

## Vue d'ensemble

Les templates d'emails du système de tickets ont été entièrement refactorisés avec une approche Bootstrap professionnelle et réactive. Chaque email inclut maintenant:

✅ **Logo de marque** en haut de chaque email
✅ **Design responsive** compatible avec tous les clients email
✅ **Styles CSS inline** pour une meilleure compatibilité
✅ **Variantes de templates** pour chaque type d'événement
✅ **Variables dynamiques** personnalisables

---

## 📁 Fichiers modifiés

### 1. **lib/notification-helper.php** 
Nouvelle fonction utilitaire : `renderEmailTemplate()`

```php
renderEmailTemplate($templateBody, $variables = [], $appUrl = '')
```

**Paramètres:**
- `$templateBody` : Contenu du template depuis la BD
- `$variables` : Tableau clé/valeur pour remplacer les {placeholders}
- `$appUrl` : URL de base de l'application (pour les liens et le logo)

**Retour:** HTML complet prêt à envoyer avec wrapper Bootstrap

---

### 2. **manage-notifications.php**
Les templates par défaut ont été mis à jour avec 4 modèles professionnels:

#### **Modèle 1: ticket_created** 🎫
**Événement:** Création d'un nouveau ticket
**Variables disponibles:**
- `{customer_name}` - Nom du client
- `{reference}` - Numéro de référence du ticket
- `{subject}` - Objet du ticket
- `{link}` - Lien de suivi
- `{created_date}` - Date de création
- `{sla_time}` - Délai SLA en jours

#### **Modèle 2: status_changed** 📢
**Événement:** Changement de statut du ticket
**Variables disponibles:**
- `{customer_name}` - Nom du client
- `{reference}` - Numéro de référence
- `{subject}` - Objet du ticket
- `{status}` - Nouveau statut
- `{link}` - Lien de suivi
- `{updated_date}` - Date de mise à jour
- `{last_message}` - Dernier message

#### **Modèle 3: agent_assigned** 👤
**Événement:** Assignation d'un agent au ticket
**Variables disponibles:**
- `{customer_name}` - Nom du client
- `{reference}` - Numéro de référence
- `{subject}` - Objet du ticket
- `{agent_name}` - Nom de l'agent
- `{agent_department}` - Département de l'agent
- `{priority}` - Priorité du ticket
- `{assigned_date}` - Date d'assignation

#### **Modèle 4: new_message** 💬
**Événement:** Nouveau message sur un ticket
**Variables disponibles:**
- `{customer_name}` - Nom du client
- `{reference}` - Numéro de référence
- `{message_author}` - Auteur du message
- `{message_date}` - Date du message
- `{message_preview}` - Aperçu du message
- `{link}` - Lien de suivi

---

### 3. **traitement-ticket.php**
Intégration des nouvelles fonctions:

#### **sendTicketEmailViaLocalAPI()**
Utilise maintenant le template 'ticket_created' depuis la BD et applique le wrapper Bootstrap.

#### **sendTicketEmail()**
Fonction legacy utilisant SMTP avec le même rendu Bootstrap.

---

## 🎨 Éléments de design inclus

### Couleurs:
- **Bleu primaire:** `#0066cc` (CTA, en-têtes)
- **Bleu foncé:** `#004499` (hover, accents)
- **Vert succès:** `#28a745` (statuts positifs)
- **Jaune avertissement:** `#ffc107` (en cours)
- **Gris neutre:** `#f9f9f9` (fonds alternatifs)

### Composants:
- **Email-wrapper:** Container responsive max 600px
- **Email-header:** En-tête avec logo et titre
- **Alert boxes:** Notifications avec bordure gauche colorée
- **Info-box:** Boîtes d'information personnalisées
- **Buttons:** CTA avec hover effects
- **Tables:** Pour lister les informations structurées
- **Divider:** Séparateurs visuels

---

## 🚀 Utilisation en production

### Pour envoyer un email utilisant les templates:

```php
require_once 'lib/notification-helper.php';

// Variables pour le template
$variables = [
    'customer_name' => 'Jean Dupont',
    'reference' => 'TK-2026-0001',
    'subject' => 'Mon problème',
    'link' => 'http://example.com/ticket',
    'created_date' => date('d/m/Y à H:i'),
    'sla_time' => 3
];

// Récupérer le template depuis la BD
$stmt = $pdo->prepare("SELECT body FROM notification_templates WHERE event_name = ? LIMIT 1");
$stmt->execute(['ticket_created']);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

// Rendu avec wrapper Bootstrap
$htmlEmail = renderEmailTemplate($template['body'], $variables, $appUrl);

// Envoyer via SMTP ou API
$sender->sendHTML($email, $name, $subject, $htmlEmail);
```

---

## 📋 Logo utilisé

**Chemin:** `/assets/img/logo/favicon-pfo.png`

Le logo est automatiquement inclus en haut de chaque email via l'URL:
```
{APP_URL}/assets/img/logo/favicon-pfo.png
```

---

## ✏️ Modifier les templates

### Via l'interface admin:
1. Accédez à **Paramètres > Notifications**
2. Cliquez sur **Modifier** pour chaque template
3. Éditez le sujet et le corps du message
4. Les variables `{variable}` restent disponibles
5. Prévisualisez votre template avec l'aperçu en direct

### Variables de base disponibles:
- `{link}` - Lien d'accès au ticket
- `{reference}` - Numéro de ticket
- `{subject}` - Objet du ticket
- `{customer_name}` - Nom du client
- `{status}` - Statut actuel
- `{priority}` - Priorité

Les variables seront **automatiquement remplacées** lors de l'envoi.

---

## 🧪 Test des templates

Un fichier de test est disponible:
```
DashBoard/php/test-email-templates.php
```

Accédez-le via votre navigateur pour voir un aperçu de tous les templates avec données de test.

---

## ⚙️ Configuration

### Couleur du wrapper HTML
Pour personnaliser les couleurs (bleu, vert, etc.), modifiez dans `lib/notification-helper.php`:

```php
// Ligne ~95
background: linear-gradient(135deg, #0066cc 0%, #004499 100%);
```

### Logo
Pour changer le logo, remplacez le fichier:
```
/assets/img/logo/favicon-pfo.png
```

### Responsive design
Les emails sont optimisés pour:
- 📱 Mobile (320px)
- 📱 Tablet (768px)
- 💻 Desktop (1200px)

---

## 🔒 Sécurité

- Tous les contenus dynamiques sont échappés avec `htmlspecialchars()`
- Les variables sont remplacées de manière sûre
- Les URLs sont encodées correctement
- Compatible avec les règles anti-spam des serveurs de mail

---

## 📞 Support

Pour toute question ou modification:
1. Consultez le fichier `manage-notifications.php`
2. Vérifiez les templates en base de données via phpmyadmin
3. Utilisez le fichier test pour déboguer le rendu

