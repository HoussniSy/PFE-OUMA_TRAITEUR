# 🍽️ OUMA TRAITEUR — Plateforme de Gestion de Traiteur

> Application web & mobile complète pour la gestion d'une entreprise de traiteur : clients, devis, factures, stock, paiements, communications et bien plus.

---

## 📋 Table des matières

- [Aperçu](#-aperçu)
- [Fonctionnalités](#-fonctionnalités)
- [Architecture](#-architecture)
- [Technologies](#-technologies)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Utilisation](#-utilisation)
- [Structure du projet](#-structure-du-projet)
- [API](#-api)
- [Auteur](#-auteur)

---

## 🎯 Aperçu

**OUMA TRAITEUR** est une plateforme de gestion métier dédiée aux entreprises de traiteur. Elle propose une interface web moderne (Symfony + Twig + Bootstrap) et une application mobile (React Native / Expo), toutes deux connectées via une API REST (API Platform).

L'application est entièrement dockerisée avec **FrankenPHP** pour des performances optimales.

---

## ✨ Fonctionnalités

### 📊 Dashboard
- Tableau de bord personnalisable avec widgets drag & drop
- Visualisation des KPIs : revenus, clients, documents, stocks

### 👥 Gestion des Clients
- CRUD complet des clients
- Recherche et filtres avancés avec sauvegarde des filtres
- Export Excel / PDF

### 📄 Gestion des Documents
- Création de **devis** et **factures**
- Ajout d'items avec calcul automatique (TVA, remises)
- Conversion devis → facture
- Export PDF

### 💰 Paiements
- Suivi des paiements par document
- Historique et statut de paiement

### 📦 Gestion de Stock
- Suivi des articles en stock
- Alertes de stock bas

### 📧 Communication
- **Email** : Templates personnalisables avec historique d'envoi
- **SMS** : Envoi de messages SMS aux clients
- **WhatsApp** : Intégration messagerie WhatsApp

### 🔔 Notifications
- Système de notifications en temps réel
- Centre de notifications avec marquage lu/non lu

### 📈 Rapports & Exports
- Rapports généraux (chiffre d'affaires, activité)
- Export Excel et PDF

### 👤 Gestion Utilisateurs
- Inscription avec vérification email
- Réinitialisation de mot de passe
- Profils et paramètres utilisateur
- Journal d'audit des actions

### 🏢 Gestion Entreprise
- Configuration de l'entreprise (logo, coordonnées, infos légales)
- Catégories de services personnalisables

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────┐
│                   Docker Compose                     │
├──────────┬──────────┬────────────┬──────────────────┤
│  App     │  MySQL   │ phpMyAdmin │    MailDev        │
│ FrankenPHP│  8.0    │  :2002     │ SMTP :1025        │
│  :8001   │  :3306   │            │  Web :1080        │
├──────────┴──────────┴────────────┴──────────────────┤
│           Symfony 7.4 + API Platform                 │
│         ┌────────────┬────────────┐                  │
│         │  Twig Web  │  REST API  │                  │
│         └─────┬──────┴─────┬──────┘                  │
│               │            │                         │
│         Navigateur    App Mobile                     │
│           Web       React Native                     │
└─────────────────────────────────────────────────────┘
```

---

## 🛠️ Technologies

| Composant | Technologie |
|---|---|
| **Backend** | PHP 8.3 · Symfony 7.4 |
| **API** | API Platform 4 · JWT (LexikJWT) |
| **Frontend Web** | Twig · Bootstrap 5 · Webpack Encore · Stimulus |
| **Application Mobile** | React Native · Expo 52 · TypeScript |
| **Base de données** | MySQL 8.0 · Doctrine ORM |
| **Serveur** | FrankenPHP (Caddy) |
| **Conteneurisation** | Docker · Docker Compose |
| **Emails** | Symfony Mailer · MailDev (dev) |
| **Autres** | DomPDF · PhpSpreadsheet · Messenger |

---

## 📋 Prérequis

- **Docker** & **Docker Compose** (v2+)
- **Git**
- **Node.js** 18+ & **npm** (pour le mobile)

---

## 🚀 Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/<VOTRE_USERNAME>/PFE-OUMA_TRAITEUR.git
cd PFE-OUMA_TRAITEUR
```

### 2. Configurer l'environnement

```bash
# Copier le fichier d'environnement Docker
cp .env.docker.example .env.docker
# Adapter les valeurs si nécessaire (DATABASE_URL, APP_SECRET, etc.)
```

### 3. Lancer les conteneurs Docker

```bash
docker compose up -d --build
```

### 4. Installer les dépendances (dans le conteneur)

```bash
# Accéder au conteneur app
docker exec -it traiteur_app bash

# Installer les dépendances PHP
composer install

# Installer les dépendances JS et compiler les assets
npm install
npm run build
```

### 5. Créer la base de données et exécuter les migrations

```bash
# Toujours dans le conteneur
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction

# (Optionnel) Charger les fixtures
php bin/console doctrine:fixtures:load --no-interaction
```

### 6. Générer les clés JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

### 7. Accéder à l'application

| Service | URL |
|---|---|
| **Application Web** | [http://localhost:8001](http://localhost:8001) |
| **phpMyAdmin** | [http://localhost:2002](http://localhost:2002) |
| **MailDev** | [http://localhost:1080](http://localhost:1080) |

---

## 📱 Application Mobile

```bash
cd mobile

# Installer les dépendances
npm install

# Lancer l'application
npx expo start
```

> **Note** : Assurez-vous que l'API backend est accessible depuis votre appareil/émulateur.

---

## 📁 Structure du projet

```
PFE-OUMA_TRAITEUR/
├── app/                        # Application Symfony
│   ├── config/                 # Configuration Symfony
│   ├── migrations/             # Migrations Doctrine
│   ├── public/                 # Point d'entrée web
│   ├── src/
│   │   ├── ApiResource/        # Ressources API Platform
│   │   ├── Command/            # Commandes console
│   │   ├── Controller/         # Contrôleurs (Web + API)
│   │   ├── DataFixtures/       # Fixtures de données
│   │   ├── Entity/             # Entités Doctrine
│   │   ├── Form/               # Formulaires Symfony
│   │   ├── Repository/         # Repositories Doctrine
│   │   ├── Security/           # Authentification & autorisation
│   │   ├── Service/            # Services métier
│   │   └── Twig/               # Extensions Twig
│   ├── templates/              # Templates Twig
│   ├── assets/                 # Assets frontend (JS/CSS)
│   ├── composer.json
│   └── webpack.config.js
├── mobile/                     # Application mobile React Native
│   ├── src/                    # Code source mobile
│   ├── assets/                 # Assets mobile
│   ├── App.tsx
│   └── package.json
├── compose.yml                 # Docker Compose principal
├── dockerfile                  # Dockerfile (FrankenPHP)
├── Caddyfile                   # Configuration Caddy
├── .gitignore
└── README.md
```

---

## 🔌 API

L'API REST est construite avec **API Platform** et sécurisée par **JWT**.

### Authentification

```bash
# Obtenir un token JWT
curl -X POST http://localhost:8001/api/login \
  -H "Content-Type: application/json" \
  -d '{"username": "user@example.com", "password": "password"}'
```

### Documentation API

La documentation interactive (Swagger UI) est disponible à :
- [http://localhost:8001/api](http://localhost:8001/api)

---

## 👨‍💻 Auteur

Projet de fin d'études (PFE) — **OUMA TRAITEUR**

---

## 📄 Licence

Ce projet est sous licence propriétaire. Tous droits réservés.
