# 📋 DOCUMENTATION - SYSTÈME DE RAPPORT DE RÉSOLUTION

## ✅ IMPLÉMENTATION COMPLÈTE

Voici ce qui a été mis en place pour permettre aux agents de remplir un rapport obligatoire lors de la résolution d'un ticket.

---

## 🗂️ FICHIERS CRÉÉS/MODIFIÉS

### 1. **save-rapport.php** (NOUVEAU)
**Chemin:** `DashBoard/php/save-rapport.php`

**Responsabilités:**
- Endpoint POST pour sauvegarder/modifier un rapport de résolution
- Valide que l'agent est assigné au ticket (AGENT) ou est ADMIN/SUPERVISOR
- Valide que le ticket est en statut "Résolu"
- Rapport obligatoire (max 5000 caractères)
- Upload optionnel de fichier (max 5 MB)
- Gère les transactions BD

**Paramètres POST:**
```
- ticket_id (required): ID du ticket
- report_content (required): Contenu du rapport
- attachment (optional): Fichier à attacher
```

---

### 2. **get-rapport.php** (NOUVEAU)
**Chemin:** `DashBoard/php/get-rapport.php`

**Responsabilités:**
- Endpoint GET pour récupérer un rapport existant
- Retourne le contenu, l'auteur, les dates et pièces jointes
- Sécurité : Seuls ADMIN/SUPERVISOR et l'agent assigné peuvent voir

**Paramètres GET:**
```
- ticket_id (required): ID du ticket
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ticket_id": 28,
    "report_content": "Résolution effectuée...",
    "agent_id": 1,
    "agent_name": "Admin Test",
    "created_at": "2026-02-10 12:45:00",
    "updated_at": "2026-02-10 14:20:00",
    "attachments": [...]
  }
}
```

---

### 3. **admin-dashboard-new.php** (MODIFIÉ)
**Chemin:** `DashBoard/php/admin-dashboard-new.php`

**Modifications:**

#### a) Ajout de la Modal de Rapport
- Nouvelle modal HTML (`rapportModal`) avec:
  - Textarea pour le rapport (obligatoire, 5000 caractères max)
  - Input file pour pièce jointe optionnelle
  - Compteur de caractères en temps réel
  - Message si rapport existe déjà

#### b) Modification du bouton "Résoudre"
- Au lieu d'afficher une simple confirmation, ouvre la modal de rapport
- Flow: Remplir rapport → Sauvegarder rapport → Marquer ticket comme "Résolu"

#### c) Ajout du JavaScript
**Fonctions principales:**
- `showRapportModal(ticketId, ticketRef)` : Ouvre la modal et récupère le rapport existant
- Gestion du compteur de caractères
- Envoi du formulaire via `save-rapport.php`
- Puis appel à `quick-ticket-actions.php` pour résoudre le ticket

---

### 4. **get-ticket-details.php** (MODIFIÉ)
**Chemin:** `DashBoard/php/get-ticket-details.php`

**Modifications:**

#### a) Séparation des pièces jointes
- Pièces jointes du ticket (WHERE report_id IS NULL)
- Pièces jointes du rapport (WHERE report_id = rapport_id)

#### b) Récupération du rapport
- Ajoute une clé `rapport` dans la réponse JSON si rapport existe
- Inclut auteur, dates (création/modification), contenu et pièces jointes

#### c) Response JSON enrichie
```json
{
  "success": true,
  "data": {
    "id": 28,
    "reference": "TKT-2026-00025",
    ...
    "attachments_list": [...],
    "rapport": {
      "id": 1,
      "report_content": "...",
      "agent_name": "Admin Test",
      ...
    }
  }
}
```

---

### 5. **Base de Données**
**Table créée:** `rapports`

```sql
CREATE TABLE rapports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL UNIQUE,
    agent_id INT NULL,
    report_content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rapports_ticket
        FOREIGN KEY (ticket_id)
        REFERENCES tickets(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_rapports_agent
        FOREIGN KEY (agent_id)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Table modifiée:** `attachments`
- Ajout colonne `report_id` pour lier pièces jointes au rapport

---

## 🔄 FLOW COMPLET

### Scénario: Un agent résout un ticket

```
1. Agent clique sur "Résoudre" dans le menu Actions
   ↓
2. Modal "Rapport de Résolution" s'affiche
   ↓
3. Agent remplit obligatoirement le rapport (textarea)
   ↓
4. Agent peut ajouter une pièce jointe optionnelle
   ↓
5. Agent clique "Confirmer et résoudre"
   ↓
6. POST à save-rapport.php
   - Sauvegarde le rapport dans BD
   - Upload fichier si présent
   - Retour JSON succès/erreur
   ↓
7. Si succès: POST à quick-ticket-actions.php
   - Change status_id à "Résolu"
   ↓
8. Rechargement page avec notification de succès
```

---

## 📝 GESTION DES CAS

### Cas 1: Rapport n'existe pas
- Agent voit un formulaire vide
- Remplissage et création du rapport

### Cas 2: Rapport existe déjà
- Agent voit le rapport existant prérempli
- Message: "Rapport créé par [Agent] le [Date]"
- Agent peut le modifier
- Click confirmer → UPDATE au lieu d'INSERT

### Cas 3: Pièce jointe optionnelle
- Accepte: PDF, DOC, DOCX, XLS, XLSX, TXT, JPG, PNG, GIF
- Max 5 MB
- Stockée dans table `attachments` avec `report_id`

---

## 🔒 SÉCURITÉ

### Authentification
- Vérification de la session sur tous les endpoints
- Retour 401 si non authentifié

### Autorisation
**AGENT:**
- Peut remplir rapport UNIQUEMENT pour ses propres tickets
- Pas d'accès aux rapports des autres agents (sauf lecture par ADMIN)

**ADMIN/SUPERVISOR:**
- Peut remplir rapport pour tous les tickets
- Peut voir tous les rapports

### Validation
- Rapport obligatoire (non vide)
- Max 5000 caractères
- Fichier: Type validé, taille limitée
- Statut ticket: Doit être "Résolu"

---

## 📦 STRUCTURE BD

```
tickets
├── id
├── reference
├── status_id ← Doit être "Résolu" pour rapport
├── assigned_to ← Agent assigné
└── ...

rapports (NOUVELLE)
├── id
├── ticket_id (UNIQUE) ← Une seul rapport par ticket
├── agent_id ← Qui a rempli
├── report_content ← Le rapport
├── created_at
└── updated_at

attachments (MODIFIÉ)
├── id
├── ticket_id
├── report_id ← Clé étrangère NOUVELLE
├── file_name
└── file_path
```

---

## 🎯 FONCTIONNALITÉS

✅ Rapport obligatoire à la résolution  
✅ Rapport modifiable après création  
✅ Pièce jointe optionnelle au rapport  
✅ Historique (dates création/modification)  
✅ Affichage du rapport dans détails du ticket  
✅ Sécurité par rôle (AGENT, ADMIN, SUPERVISOR)  
✅ Gestion des fichiers sécurisée  
✅ UI/UX avec modal Bootstrap 5  
✅ Compteur de caractères en temps réel  
✅ Notifications SweetAlert2  

---

## 🧪 TESTS RECOMMANDÉS

1. **Test 1: Agent résout un ticket**
   - Cliquer "Résoudre"
   - Remplir rapport
   - Vérifier création dans BD
   - Vérifier affichage dans détails

2. **Test 2: Modification du rapport**
   - Cliquer "Résoudre" sur ticket résolu
   - Vérifier rapport prérempli
   - Modifier le contenu
   - Vérifier UPDATE en BD

3. **Test 3: Upload fichier**
   - Ajouter un fichier
   - Vérifier upload et BD
   - Vérifier téléchargement depuis détails

4. **Test 4: Sécurité - Agent non assigné**
   - Agent A ne peut pas remplir rapport ticket assigné Agent B
   - Vérifier erreur 403

5. **Test 5: Validation**
   - Rapport vide → Erreur
   - Fichier > 5 MB → Erreur
   - Extension non autorisée → Erreur

---

## 📞 SUPPORT

En cas de problème:
1. Vérifier les logs PHP (`php_errors.log`)
2. Vérifier les permissions dossier upload
3. Vérifier les contraintes BD (UNIQUE sur ticket_id)
4. Vérifier la session utilisateur

---

**Implémentation terminée le:** 10 février 2026  
**Statut:** ✅ Prêt pour test en production
