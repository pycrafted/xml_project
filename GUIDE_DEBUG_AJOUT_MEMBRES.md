# Guide de débogage - Ajout de membres aux groupes

## Problème identifié
L'utilisateur ne peut pas ajouter de membres au groupe "test" malgré le fait que le processus semble fonctionner dans les tests.

## Vérifications à effectuer

### 1. Vérifier que le serveur est démarré
```bash
php -S localhost:8000 -t public
```
Ouvrir le navigateur sur `http://localhost:8000`

### 2. Vérifier la connexion admin
- Email: `admin@whatsapp.com`
- Mot de passe: `admin123`

### 3. Étapes de test détaillées

#### 3.1 Accès au groupe
1. Aller dans "Groupes" dans le menu
2. Cliquer sur "Gérer" pour le groupe "test"
3. L'URL devrait être: `http://localhost:8000/groups.php?action=manage&id=group_1752586696_687659c82235f`

#### 3.2 Vérifier les contacts disponibles
- S'assurer que des contacts existent dans la liste déroulante
- Vérifier qu'ils ne sont pas déjà membres du groupe

#### 3.3 Processus d'ajout
1. Cliquer sur "Ajouter un membre"
2. Sélectionner un contact dans la liste
3. Choisir le rôle (membre ou admin)
4. Cliquer sur "Ajouter"

## Solutions possibles

### Solution 1: Vérifier les logs
Consulter le fichier `logs/app.log` pour voir les erreurs:
```bash
tail -f logs/app.log
```

### Solution 2: Activer le mode debug
Ajouter dans `public/groups.php` au début:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Solution 3: Vérifier les permissions
- S'assurer que l'utilisateur "admin" est bien administrateur du groupe
- Vérifier dans les données XML que le groupe existe

### Solution 4: Test manuel avec les données
Ouvrir `data/sample_data.xml` et vérifier:
- Le groupe "test" existe-t-il ? (chercher `<name>test</name>`)
- L'utilisateur "admin" est-il admin du groupe ?
- Y a-t-il des contacts disponibles ?

## Diagnostic step-by-step

### Étape 1: Vérifier le groupe dans les données
```xml
<group id="group_1752586696_687659c82235f">
    <name>test</name>
    <members>
        <member user_id="admin" role="admin"/>
        <member user_id="demo" role="member"/>
    </members>
</group>
```

### Étape 2: Vérifier les contacts admin
Chercher dans `<contacts>` les contacts avec `user_id="admin"`:
```xml
<contact id="contact_1752580286_687640bedb47e">
    <name>Bob Durand</name>
    <user_id>admin</user_id>
    <contact_user_id>bob2025</contact_user_id>
</contact>
```

### Étape 3: Vérifier le formulaire HTML
Le formulaire devrait contenir:
- `<input type="hidden" name="action" value="add_member">`
- `<input type="hidden" name="group_id" value="...">`
- `<select name="contact_id">`

## Actions correctives

Si le problème persiste, essayer ces corrections :

### Correction 1: Vérifier la méthode addMemberToGroup
Dans `src/Repositories/GroupRepository.php`, vérifier que la méthode `addMemberToGroup` fonctionne correctement.

### Correction 2: Vérifier les validations
Dans `public/groups.php`, vérifier que:
- L'utilisateur est bien admin du groupe
- Le contact sélectionné n'est pas déjà membre
- Les données POST sont bien reçues

### Correction 3: Logs de débogage
Ajouter des logs dans `public/groups.php` dans la section `add_member`:
```php
logInfo("Tentative d'ajout de membre", [
    'group_id' => $groupId,
    'contact_id' => $contactId,
    'user_id' => $_SESSION['user_id']
]);
```

## Test final
Après chaque correction, tester le processus complet :
1. Démarrer le serveur
2. Se connecter comme admin
3. Aller dans Groupes → Gérer test
4. Ajouter un membre
5. Vérifier que le membre apparaît dans la liste
6. Vérifier dans les logs qu'il n'y a pas d'erreur

## Contacts utiles pour les tests
- Bob Durand (bob2025)
- Charlie Dupont (charlie2025)  
- Diana Lemoine (diana2025)
- Erik Rousseau (erik2025)

Ces utilisateurs existent dans le système et peuvent être ajoutés au groupe "test". 