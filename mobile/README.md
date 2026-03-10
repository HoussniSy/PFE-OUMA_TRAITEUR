# OumaTraiteur Mobile

Application mobile React Native pour la gestion des devis et factures, connectée à l'API Symfony.

## Prérequis

- Node.js 18+
- React Native CLI (`npm install -g react-native-cli`)
- Android Studio + SDK (pour Android)
- JDK 17

## Installation

```bash
cd mobile
npm install
```

## Configuration

Modifier l'URL de l'API dans `src/api/apiService.js` :

```javascript
// Émulateur Android
const API_BASE_URL = 'http://10.0.2.2:8001';

// Device physique (remplacer par votre IP LAN)
const API_BASE_URL = 'http://192.168.1.X:8001';
```

## Lancement

```bash
# Démarrer le Metro bundler
npx react-native start

# Dans un second terminal, lancer sur Android
npx react-native run-android
```

## Structure

```
src/
├── api/apiService.js       # Service API (axios + JWT)
├── context/AuthContext.js   # Gestion authentification
├── navigation/AppNavigator.js
├── screens/
│   ├── LoginScreen.js      # Connexion
│   ├── DashboardScreen.js  # Tableau de bord
│   ├── ClientsScreen.js    # Liste clients
│   ├── ClientDetailScreen.js
│   ├── ClientFormScreen.js # Création/modif client
│   ├── DocumentsScreen.js  # Devis & factures
│   ├── DocumentDetailScreen.js
│   ├── StockScreen.js      # Gestion stock
│   ├── ReportsScreen.js    # Rapports & graphiques
│   └── SettingsScreen.js   # Paramètres
└── utils/theme.js          # Design system
```

## Fonctionnalités

- ✅ Authentification JWT
- ✅ Dashboard avec stats temps réel
- ✅ Gestion clients (CRUD)
- ✅ Consultation devis & factures
- ✅ Gestion stock avec réapprovisionnement
- ✅ Rapports avec graphiques
- ✅ Pull-to-refresh sur tous les écrans
