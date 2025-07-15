# WhatsApp Web Clone

Un clone web de WhatsApp développé en PHP avec une architecture MVC propre et une gestion des données en XML.

## 📋 Table des matières

- [À propos](#à-propos)
- [Technologies](#technologies)
- [Architecture](#architecture)
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Structure du projet](#structure-du-projet)
- [Tests](#tests)
- [Contribution](#contribution)

## 🎯 À propos

Ce projet est un clone web de WhatsApp développé dans le cadre du Master en Génie Logiciel à l'UCAD/DGI/ESP. Il permet aux utilisateurs de :

- ✅ S'authentifier de manière sécurisée
- ✅ Gérer leurs contacts
- ✅ Envoyer et recevoir des messages en temps réel
- ✅ Créer et gérer des groupes de discussion
- ✅ Personnaliser leur profil

## 🛠️ Technologies

- **Backend** : PHP 8.0+
- **Base de données** : XML avec validation XSD
- **Frontend** : HTML5, CSS3, JavaScript (Vanilla)
- **Architecture** : MVC avec Repository Pattern
- **Tests** : PHPUnit
- **Gestion des dépendances** : Composer

## 🏗️ Architecture

Le projet suit une architecture MVC stricte avec les patterns suivants :

```
src/
├── Models/          # Entités métier
├── Repositories/    # Couche d'accès aux données
├── Services/        # Logique métier
└── Utils/          # Utilitaires (XMLManager)
```

## 📦 Installation

### Prérequis

- PHP 8.0 ou supérieur
- Composer
- Serveur web (Apache/Nginx) ou PHP built-in server

### Étapes d'installation

1. **Cloner le repository**
   ```bash
   git clone https://github.com/votre-username/whatsapp-web-clone.git
   cd whatsapp-web-clone
   ```

2. **Installer les dépendances**
```bash
composer install
```

3. **Créer les utilisateurs par défaut**
   ```bash
   php create_default_user.php
   ```

4. **Démarrer l'application**
```bash
   php start_app.php
   ```

L'application sera accessible à l'adresse : `http://localhost:8080`

## 🚀 Utilisation

### Comptes de démonstration

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| admin@whatsapp.com | admin123 | Administrateur |
| demo@whatsapp.com | demo123 | Utilisateur démo |
| test@whatsapp.com | test123 | Utilisateur test |
| alice@test.com | password123 | Utilisateur |
| bob@test.com | password123 | Utilisateur |

### Fonctionnalités principales

1. **Connexion** : Utilisez l'un des comptes de démonstration
2. **Gestion des contacts** : Ajoutez des contacts via leur email
3. **Messagerie** : Envoyez des messages en temps réel
4. **Groupes** : Créez et gérez des groupes de discussion
5. **Profil** : Personnalisez votre profil et statut

## 📁 Structure du projet

```
whatsapp-web-clone/
├── app.php                 # Point d'entrée principal
├── start_app.php          # Script de démarrage du serveur
├── create_default_user.php # Création des utilisateurs par défaut
├── composer.json          # Dépendances PHP
├── phpunit.xml           # Configuration des tests
├── .gitignore            # Fichiers ignorés par Git
├── README.md             # Documentation
├── CONTRIBUTING.md       # Guide de contribution
│
├── data/                 # Données XML
│   ├── sample_data.xml   # Données d'exemple
│   └── *.xml            # Fichiers de données
│
├── public/              # Fichiers publics
│   ├── index.php        # Page d'accueil
│   ├── dashboard.php    # Tableau de bord
│   ├── chat.php         # Interface de chat
│   ├── contacts.php     # Gestion des contacts
│   ├── groups.php       # Gestion des groupes
│   ├── profile.php      # Profil utilisateur
│   ├── ajax.php         # API AJAX
│   └── assets/          # Ressources statiques
│       ├── css/         # Styles
│       └── js/          # Scripts
│
├── rapport/             # Documentation technique
│   ├── ARCHITECTURE.md  # Architecture du projet
│   └── DEBUGGING_GUIDE.md # Guide de débogage
│
├── schemas/             # Schémas XSD
│   └── whatsapp_data.xsd
│
├── src/                 # Code source
│   ├── Models/          # Modèles
│   ├── Repositories/    # Repositories
│   ├── Services/        # Services
│   └── Utils/           # Utilitaires
│
├── tests/               # Tests unitaires et d'intégration
│   ├── Unit/           # Tests unitaires
│   └── Integration/    # Tests d'intégration
│
├── logs/               # Logs de l'application
└── vendor/             # Dépendances (généré)
```

## 🧪 Tests

### Exécuter les tests

```bash
# Tous les tests
./vendor/bin/phpunit

# Tests unitaires uniquement
./vendor/bin/phpunit --testsuite Unit

# Tests d'intégration uniquement
./vendor/bin/phpunit --testsuite Integration

# Avec couverture de code
./vendor/bin/phpunit --coverage-html coverage/
```

### Structure des tests

- **Unit/** : Tests unitaires isolés
- **Integration/** : Tests d'intégration complets

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. Forkez le projet
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Commitez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Pushez vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

### Standards de code

- Suivre les standards PSR-12 pour PHP
- Documenter les méthodes complexes
- Écrire des tests pour les nouvelles fonctionnalités
- Maintenir une couverture de code > 80%

## 📝 Licence

Ce projet est développé dans un cadre académique pour le Master en Génie Logiciel de l'UCAD/DGI/ESP.

## 👥 Auteur

Développé dans le cadre du cours du Professeur Ibrahima FALL - Master Génie Logiciel 2024-2025

---

*Pour toute question ou support, veuillez ouvrir une issue sur le repository.* 