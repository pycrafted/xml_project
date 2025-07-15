# Guide de Contribution - WhatsApp Web Clone

## 🎯 Philosophie du projet

Ce projet suit les principes du **Clean Code** et les **bonnes pratiques** de développement professionnel. Toute contribution doit respecter ces standards.

## 📋 Avant de contribuer

### 1. Préparer votre environnement

```bash
# Cloner le repository
git clone [repository-url]
cd whatsapp-web-clone

# Installer les dépendances
composer install

# Créer une branche pour votre fonctionnalité
git checkout -b feature/nom-de-la-fonctionnalite
```

### 2. Comprendre l'architecture

Lisez attentivement :
- `README.md` - Vue d'ensemble du projet
- `rapport/ARCHITECTURE.md` - Architecture technique détaillée
- Le code source dans `src/` pour comprendre les patterns utilisés

## 🧾 Standards de code

### 1. PHP Standards (PSR-12)

Nous suivons strictement les standards PSR-12 :

```php
<?php

namespace WhatsApp\Models;

class User
{
    private string $id;
    private string $name;
    
    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
    
    public function getId(): string
    {
        return $this->id;
    }
}
```

### 2. Documentation (PHPDoc)

Toute méthode publique doit être documentée :

```php
/**
 * Trouve un utilisateur par son email
 * 
 * @param string $email L'email de l'utilisateur
 * @return User|null L'utilisateur trouvé ou null
 * @throws \InvalidArgumentException Si l'email est invalide
 */
public function findByEmail(string $email): ?User
{
    // Implementation
}
```

### 3. Nommage

- **Classes** : PascalCase (`UserRepository`)
- **Méthodes** : camelCase (`findByEmail`)
- **Variables** : camelCase (`$userName`)
- **Constantes** : UPPER_SNAKE_CASE (`MAX_RETRIES`)

## 🏗️ Architecture et patterns

### 1. Repository Pattern

Toute interaction avec les données doit passer par un repository :

```php
// ✅ Bon
$userRepository = new UserRepository($xmlManager);
$user = $userRepository->findById($id);

// ❌ Mauvais
$xml = simplexml_load_file('data/users.xml');
$user = $xml->xpath("//user[@id='$id']")[0];
```

### 2. Dependency Injection

Injectez les dépendances, ne les créez pas :

```php
// ✅ Bon
class UserService
{
    public function __construct(private UserRepository $repository)
    {
    }
}

// ❌ Mauvais
class UserService
{
    private $repository;
    
    public function __construct()
    {
        $this->repository = new UserRepository();
    }
}
```

### 3. Single Responsibility Principle

Chaque classe doit avoir une seule responsabilité :

```php
// ✅ Bon : Séparation des responsabilités
class UserValidator { /* validation logic */ }
class UserRepository { /* data access */ }
class UserService { /* business logic */ }

// ❌ Mauvais : Trop de responsabilités
class User {
    public function validate() { }
    public function save() { }
    public function sendEmail() { }
}
```

## 🧪 Tests

### 1. Écrire des tests

Pour chaque nouvelle fonctionnalité :

```php
class UserServiceTest extends TestCase
{
    public function testCreateUserWithValidData(): void
    {
        // Arrange
        $xmlManager = new XMLManager();
        $service = new UserService($xmlManager);
        
        // Act
        $user = $service->createUser('John', 'john@example.com');
        
        // Assert
        $this->assertNotNull($user);
        $this->assertEquals('John', $user->getName());
    }
}
```

### 2. Exécuter les tests

```bash
# Tous les tests
./vendor/bin/phpunit

# Un test spécifique
./vendor/bin/phpunit tests/Unit/UserServiceTest.php

# Avec couverture
./vendor/bin/phpunit --coverage-html coverage/
```

## 🔍 Revue de code

### Checklist avant de soumettre

- [ ] Le code suit les standards PSR-12
- [ ] Les méthodes publiques sont documentées
- [ ] Les tests passent (`./vendor/bin/phpunit`)
- [ ] Pas de code commenté ou de fichiers temporaires
- [ ] Les nouvelles fonctionnalités ont des tests
- [ ] Le code est lisible et auto-documenté
- [ ] Pas de duplication de code (DRY)
- [ ] Les erreurs sont gérées correctement

### Ce qu'on ne veut PAS voir

```php
// ❌ Fichiers de debug à la racine
debug_test.php
fix_something.php
test_quick.php

// ❌ Code commenté
// $user = new User();
// $user->save();

// ❌ Variables mal nommées
$u = $repo->find($i);
$x = $u->n;

// ❌ Magic numbers/strings
if ($count > 42) { }

// ❌ Logs de debug
var_dump($data);
echo "DEBUG: " . $value;
print_r($array);
```

## 📝 Process de contribution

### 1. Créer une issue

Avant de coder, créez une issue décrivant :
- Le problème ou la fonctionnalité
- La solution proposée
- L'impact sur l'existant

### 2. Développer

- Créez une branche depuis `main`
- Commitez régulièrement avec des messages clairs
- Gardez vos commits atomiques

### 3. Messages de commit

Format : `type(scope): description`

```bash
feat(auth): add remember me functionality
fix(messages): correct timestamp timezone issue
docs(readme): update installation instructions
test(user): add edge cases for email validation
refactor(repository): extract common query logic
```

Types :
- `feat` : Nouvelle fonctionnalité
- `fix` : Correction de bug
- `docs` : Documentation
- `test` : Ajout de tests
- `refactor` : Refactoring
- `style` : Formatage
- `chore` : Maintenance

### 4. Pull Request

- Titre clair et descriptif
- Description détaillée des changements
- Référence à l'issue correspondante
- Screenshots si changements UI
- Tests verts

## 🚨 Points d'attention

### 1. Sécurité

- Échapper toutes les entrées utilisateur
- Valider les données côté serveur
- Ne jamais stocker de mots de passe en clair
- Utiliser des requêtes préparées (si SQL)

### 2. Performance

- Éviter les boucles imbriquées sur de grandes données
- Utiliser la pagination pour les listes
- Mettre en cache les données statiques
- Optimiser les requêtes XPath

### 3. Maintenabilité

- Code simple plutôt que clever
- Noms explicites plutôt que courts
- Composition plutôt qu'héritage
- Interfaces plutôt que classes concrètes

## 📚 Ressources

- [PSR-12 Standard](https://www.php-fig.org/psr/psr-12/)
- [Clean Code by Robert C. Martin](https://www.oreilly.com/library/view/clean-code-a/9780136083238/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [PHP The Right Way](https://phptherightway.com/)

## 🤝 Code de conduite

- Soyez respectueux et professionnel
- Acceptez les critiques constructives
- Aidez les nouveaux contributeurs
- Maintenez la qualité du code

---

*En contribuant à ce projet, vous acceptez de suivre ces directives et de maintenir les standards de qualité établis.* 