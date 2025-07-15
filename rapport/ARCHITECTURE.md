# Architecture Technique - WhatsApp Web Clone

## Vue d'ensemble

Ce document décrit l'architecture technique du projet WhatsApp Web Clone, développé en PHP avec une approche orientée objet et des patterns de conception modernes.

## Architecture générale

### Pattern MVC (Model-View-Controller)

Le projet suit une architecture MVC stricte :

```
┌─────────────────────────────────────────────────────────┐
│                     Frontend (View)                      │
│                  HTML/CSS/JavaScript                     │
└─────────────────────┬───────────────────────────────────┘
                      │ HTTP Requests
                      ▼
┌─────────────────────────────────────────────────────────┐
│                  Controller Layer                        │
│              public/*.php (endpoints)                    │
└─────────────────────┬───────────────────────────────────┘
                      │ Business Logic
                      ▼
┌─────────────────────────────────────────────────────────┐
│                   Service Layer                          │
│                 src/Services/*.php                       │
└─────────────────────┬───────────────────────────────────┘
                      │ Data Operations
                      ▼
┌─────────────────────────────────────────────────────────┐
│                 Repository Layer                         │
│              src/Repositories/*.php                      │
└─────────────────────┬───────────────────────────────────┘
                      │ XML Operations
                      ▼
┌─────────────────────────────────────────────────────────┐
│                   Data Layer (XML)                       │
│              data/*.xml + XMLManager                     │
└─────────────────────────────────────────────────────────┘
```

## Composants principaux

### 1. Models (src/Models/)

Les modèles représentent les entités métier :

- **User** : Gestion des utilisateurs et leurs paramètres
- **Contact** : Relations entre utilisateurs
- **Message** : Messages privés et de groupe
- **Group** : Groupes de discussion et membres

### 2. Repositories (src/Repositories/)

Couche d'abstraction pour l'accès aux données :

- **UserRepository** : CRUD des utilisateurs
- **ContactRepository** : Gestion des contacts
- **MessageRepository** : Stockage et récupération des messages
- **GroupRepository** : Gestion des groupes

### 3. Services (src/Services/)

Logique métier et orchestration :

- **UserService** : Authentification et gestion des profils
- **MessageService** : Envoi et réception de messages

### 4. Utils (src/Utils/)

Composants transversaux :

- **XMLManager** : Gestion centralisée des opérations XML et validation XSD

## Patterns de conception utilisés

### 1. Repository Pattern

```php
interface RepositoryInterface {
    public function findById(string $id);
    public function findAll();
    public function create($entity);
    public function update($entity);
    public function delete(string $id);
}
```

### 2. Dependency Injection

```php
class UserService {
    private XMLManager $xmlManager;
    
    public function __construct(XMLManager $xmlManager) {
        $this->xmlManager = $xmlManager;
    }
}
```

### 3. Data Transfer Objects (DTO)

Les modèles servent de DTO pour transférer les données entre les couches.

## Gestion des données XML

### Structure XML

```xml
<whatsapp_data xmlns="http://whatsapp.clone/data">
    <users>
        <user id="unique_id">
            <name>Nom</name>
            <email>email@example.com</email>
            <password>hash</password>
            <status>active</status>
            <settings>
                <setting key="theme" value="dark"/>
            </settings>
        </user>
    </users>
    <!-- Autres sections : contacts, groups, messages -->
</whatsapp_data>
```

### Validation XSD

Toutes les opérations XML sont validées contre le schéma `schemas/whatsapp_data.xsd`.

## Sécurité

### 1. Authentification

- Sessions PHP pour la persistance
- Mots de passe stockés en clair (à améliorer en production)

### 2. Validation des entrées

- Échappement HTML systématique
- Validation des emails
- Vérification des permissions

### 3. Protection CSRF

- À implémenter : tokens CSRF pour les formulaires

## Performance

### 1. Optimisations actuelles

- Chargement XML unique par requête
- Cache des objets SimpleXML en mémoire

### 2. Améliorations futures

- Mise en cache des requêtes fréquentes
- Pagination des messages
- Indexation des données XML

## Tests

### Structure des tests

```
tests/
├── Unit/              # Tests unitaires isolés
│   ├── UserServiceTest.php
│   └── XMLManagerTest.php
└── Integration/       # Tests d'intégration
    └── CompleteWorkflowTest.php
```

### Couverture

- Objectif : > 80% de couverture
- Tests automatisés avec PHPUnit

## Déploiement

### Prérequis

- PHP 8.0+
- Extensions : SimpleXML, DOM, LibXML
- Serveur web compatible PHP

### Configuration

1. Variables d'environnement (à implémenter)
2. Permissions sur le dossier `data/`
3. Configuration du serveur web

## Évolutions futures

### Court terme

1. Implémentation de WebSockets pour le temps réel
2. Système de notifications
3. Amélioration de l'interface utilisateur

### Moyen terme

1. API RESTful complète
2. Support des médias (images, vidéos)
3. Chiffrement des messages

### Long terme

1. Migration vers une base de données relationnelle
2. Architecture microservices
3. Application mobile native

## Conventions de code

- **PSR-12** : Standard de codage PHP
- **PHPDoc** : Documentation des classes et méthodes
- **Naming** : CamelCase pour les classes, camelCase pour les méthodes

## Maintenance

### Logs

Les logs sont stockés dans `logs/app.log` avec rotation quotidienne.

### Monitoring

- Vérification de la validité XML
- Surveillance des performances
- Alertes sur les erreurs critiques

---

*Document maintenu par l'équipe de développement - Dernière mise à jour : Décembre 2024* 