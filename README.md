# Système de Gestion de Tickets

Un système de gestion de tickets complet développé en PHP/MySQL pour le support technique.

## 🚀 Fonctionnalités

- Création de tickets par les utilisateurs.
- Tableau de bord Administrateur pour la gestion des tickets.
- Gestion des catégories, priorités et statuts.
- Notifications par Email (Intégration Gmail/Outlook Exchange).
- Export des tickets en format Excel.
- Gestion du SLA (Délai de réponse/résolution).
- Fermeture automatique des tickets inactifs.

## 🛠️ Installation

### 1. Prérequis
- Serveur PHP (XAMPP, WAMP, ou Laragon)
- MySQL / MariaDB

### 2. Clonage du projet
```bash
git clone https://github.com/votre-utilisateur/votre-projet.git
cd votre-projet
```

### 3. Configuration de la Base de Données
1. Créez une nouvelle base de données dans phpMyAdmin (ex: `tickets_bd`).
2. Importez le fichier SQL situé dans le dossier `/database/` (ex: `database/schema.sql`).

### 4. Configuration de l'Environnement
1. Allez dans le dossier `DashBoard/php/`.
2. Renommez le fichier `.env.example` en `.env`.
3. Éditez le fichier `.env` avec vos propres informations :
   - Identifiants de base de données (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
   - Configuration SMTP pour les emails.

## 📂 Structure du projet
- `/database` : Scripts SQL pour l'initialisation de la base de données.
- `/DashBoard` : Interface d'administration et logique backend.
- `/assets` : Fichiers CSS, JS et images.

## 🔒 Sécurité
Les fichiers contenant des informations sensibles (`.env`, configurations locales) sont exclus via le fichier `.gitignore`. Assurez-vous de ne jamais pousser ces fichiers sur un dépôt public.

## 📄 Licence
Ce projet est sous licence MIT.
