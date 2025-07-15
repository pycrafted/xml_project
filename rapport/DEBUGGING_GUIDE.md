# Guide de Débogage - WhatsApp Web Clone

## Erreurs communes et solutions

### 1. Erreur "Cannot access offset of type string on string"

**Erreur typique :**
```
Fatal error: Uncaught TypeError: Cannot access offset of type string on string
```

**Cause :**
Tentative d'accès à un index de tableau sur une variable qui est une chaîne de caractères.

**Exemple problématique :**
```php
$members = $groupRepo->getGroupMembers($groupId);
foreach ($members as $member) {
    if ($member['user_id'] === $_SESSION['user_id']) { // ERREUR
        // ...
    }
}
```

**Solution :**
Comprendre le format des données retournées par `getGroupMembers()` :
```php
$members = $groupRepo->getGroupMembers($groupId);
foreach ($members as $userId => $role) {
    if ($userId === $_SESSION['user_id']) { // CORRECT
        // ...
    }
}
```

**Explication :**
- `getGroupMembers()` retourne : `['user_id' => 'role']`
- La clé est l'ID utilisateur, la valeur est le rôle

### 2. Erreurs de format de données XML

**Prévention :**
Toujours vérifier le format des données avant utilisation :

```php
// Vérifier le type
if (is_array($data)) {
    foreach ($data as $key => $value) {
        // Traitement sécurisé
    }
} else {
    // Gérer le cas d'erreur
}
```

### 3. Erreurs de sessions

**Vérification :**
```php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
```

### 4. Erreurs de validation XML

**Vérification :**
```php
try {
    $result = $xmlManager->validate();
    if (!$result) {
        throw new Exception("XML invalide");
    }
} catch (Exception $e) {
    // Gérer l'erreur
}
```

## Outils de débogage

### 1. Vérification des types

```php
// Vérifier le type d'une variable
echo gettype($variable);

// Vérifier si c'est un tableau
if (is_array($variable)) {
    print_r($variable);
}

// Vérifier la structure
var_dump($variable);
```

### 2. Logs de débogage

```php
// Ajouter des logs temporaires
error_log("Debug: " . print_r($variable, true));

// Utiliser le système de logs
file_put_contents('logs/debug.log', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
```

### 3. Tests unitaires

Créer des tests pour vérifier le comportement :

```php
public function testGetGroupMembersFormat()
{
    $members = $this->groupRepo->getGroupMembers('test_group');
    
    // Vérifier que c'est un tableau
    $this->assertIsArray($members);
    
    // Vérifier le format
    foreach ($members as $userId => $role) {
        $this->assertIsString($userId);
        $this->assertIsString($role);
    }
}
```

## Checklist de débogage

### Avant de corriger un bug :

1. **Reproduire l'erreur** de manière consistante
2. **Identifier la ligne exacte** où l'erreur se produit
3. **Vérifier les types de données** à cette ligne
4. **Tracer le flux de données** jusqu'à la source
5. **Vérifier la documentation** des méthodes utilisées

### Après la correction :

1. **Tester la correction** avec plusieurs cas
2. **Vérifier les effets de bord** sur d'autres fonctionnalités
3. **Ajouter des tests** pour éviter la régression
4. **Documenter** la solution si nécessaire

## Prévention des erreurs

### 1. Validation des entrées

```php
public function getGroupMembers(string $groupId): array
{
    if (empty($groupId)) {
        throw new InvalidArgumentException("Group ID cannot be empty");
    }
    
    // ... suite de la méthode
}
```

### 2. Documentation des formats

```php
/**
 * Récupère les membres d'un groupe
 * 
 * @param string $groupId ID du groupe
 * @return array Format: ['user_id' => 'role'] où role = 'admin'|'member'
 */
public function getGroupMembers(string $groupId): array
```

### 3. Typage strict

```php
declare(strict_types=1);
```

## Ressources utiles

- [PHP Error Handling](https://www.php.net/manual/en/language.errors.php)
- [PHP Debugging](https://www.php.net/manual/en/function.debug-backtrace.php)
- [PHPUnit Testing](https://phpunit.de/documentation.html)

---

*Guide mis à jour en fonction des erreurs rencontrées - Décembre 2024* 