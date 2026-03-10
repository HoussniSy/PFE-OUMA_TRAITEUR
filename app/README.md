# 🍽️ Ouma Traiteur

> Application de gestion complète pour entreprise de traiteur — Devis, Factures, Clients, Stocks, Communications et Rapports.

![Symfony](https://img.shields.io/badge/Symfony-7.4-purple?logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue?logo=php)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-blueviolet?logo=bootstrap)
![License](https://img.shields.io/badge/Licence-Propriétaire-red)

---

## ✨ Fonctionnalités

### 📊 Tableau de bord
- Vue d'ensemble avec KPIs (clients, devis, factures, CA)
- Graphique du chiffre d'affaires sur 6 mois
- Widgets personnalisables et actions rapides

### 📄 Gestion des documents
- Création de **devis** et **factures** avec items détaillés
- Conversion devis → facture en un clic
- Génération PDF (DomPDF)
- Export Excel (PhpSpreadsheet)
- Suivi des statuts et paiements

### 👥 Gestion des clients
- Fiche client complète avec historique
- Recherche globale
- Export clients (CSV/Excel)

### 📦 Gestion des stocks
- Suivi des articles en stock
- Alertes de stock bas

### 💬 Communications
- **SMS** — Envoi de SMS aux clients
- **WhatsApp** — Messages et envoi de documents
- **Emails** — Templates d'emails personnalisables avec historique

### 📈 Rapports & Statistiques
- Rapports financiers détaillés
- Export des rapports

### 🔔 Notifications
- Système de notifications intégré

### 👤 Gestion utilisateurs
- Rôles : Admin, Comptable, Utilisateur
- Profils avec avatar
- Paramètres utilisateur personnalisables
- Journal d'audit

### 🌙 Thème
- Mode clair / sombre avec toggle
- Design moderne avec glassmorphism et gradient

---

## 🛠️ Stack technique

| Composant | Technologie |
|-----------|-------------|
| **Backend** | Symfony 7.4, PHP 8.2+ |
| **Base de données** | Doctrine ORM, Migrations |
| **API** | API Platform 4, JWT (LexikJWT) |
| **Frontend** | Twig, Bootstrap 5.3, Webpack Encore |
| **PDF** | DomPDF |
| **Excel** | PhpSpreadsheet |
| **Auth** | Symfony Security, Reset Password, Email Verification |
| **Conteneurisation** | Docker, Caddy |

---

## 🚀 Installation

### Prérequis
- PHP 8.2+
- Composer
- Node.js & npm
- MySQL / PostgreSQL

### Étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/votre-username/ouma-traiteur.git
cd ouma-traiteur

# 2. Installer les dépendances PHP
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Éditer .env.local avec vos paramètres de base de données

# 4. Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Installer les dépendances front
npm install
npm run build

# 6. Lancer le serveur
symfony server:start
```

### 🐳 Docker

```bash
docker compose up -d
```

---

## 📁 Structure du projet

```
app/
├── src/
│   ├── Controller/     # 23 contrôleurs
│   ├── Entity/         # 16 entités Doctrine
│   ├── Repository/     # Repositories
│   ├── Service/        # Services métier
│   └── ...
├── templates/          # Templates Twig (23 modules)
├── assets/             # CSS, JS (Webpack Encore)
├── config/             # Configuration Symfony
├── migrations/         # Migrations Doctrine
└── public/             # Point d'entrée web
```

---

## 📱 Application mobile

Une application mobile **React Native** (TypeScript) est également disponible dans le dossier `mobile/`, se connectant via l'API REST.

---

## 📝 Licence

Projet propriétaire — Tous droits réservés © 2026 Ouma Traiteur

---

<p align="center">
  Fait avec ❤️ à Paris, France FR
</p>
