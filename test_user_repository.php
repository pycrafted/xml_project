<?php

/**
 * Test complet de UserRepository
 */

require_once 'vendor/autoload.php';

use WhatsApp\Models\User;
use WhatsApp\Utils\XMLManager;
use WhatsApp\Repositories\UserRepository;

echo "🔍 Test UserRepository...\n\n";

try {
    // Nettoyage préalable
    if (file_exists('data/test_users.xml')) {
        unlink('data/test_users.xml');
    }
    
    // Initialisation
    $xmlManager = new XMLManager('data/test_users.xml');
    $userRepo = new UserRepository($xmlManager);

    // Test 1: Création d'utilisateur
    echo "✅ Test 1: Création d'utilisateur\n";
    $user1 = new User('user1', 'John Doe', 'john@example.com');
    $user1->setSettings(['theme' => 'dark', 'notifications' => 'enabled']);
    
    if ($userRepo->create($user1)) {
        echo "   ✓ Utilisateur créé avec succès\n";
    } else {
        throw new Exception("Erreur création utilisateur");
    }

    // Test 2: Recherche par ID
    echo "\n✅ Test 2: Recherche par ID\n";
    $foundUser = $userRepo->findById('user1');
    if ($foundUser && $foundUser->getName() === 'John Doe') {
        echo "   ✓ Utilisateur trouvé : " . $foundUser->getName() . "\n";
        echo "   ✓ Email : " . $foundUser->getEmail() . "\n";
        echo "   ✓ Settings : " . json_encode($foundUser->getSettings()) . "\n";
    } else {
        throw new Exception("Utilisateur non trouvé");
    }

    // Test 3: Création de plusieurs utilisateurs
    echo "\n✅ Test 3: Création de plusieurs utilisateurs\n";
    $user2 = new User('user2', 'Jane Smith', 'jane@example.com');
    $user3 = new User('user3', 'Bob Wilson', 'bob@example.com');
    
    $userRepo->create($user2);
    $userRepo->create($user3);
    echo "   ✓ Plusieurs utilisateurs créés\n";

    // Test 4: FindAll
    echo "\n✅ Test 4: Recherche de tous les utilisateurs\n";
    $allUsers = $userRepo->findAll();
    echo "   ✓ Nombre d'utilisateurs : " . count($allUsers) . "\n";
    
    foreach ($allUsers as $user) {
        echo "   ✓ User: " . $user->getName() . " (ID: " . $user->getId() . ")\n";
    }

    // Test 5: FindByEmail
    echo "\n✅ Test 5: Recherche par email\n";
    $usersByEmail = $userRepo->findByEmail('jane@example.com');
    echo "   ✓ Nombre trouvés: " . count($usersByEmail) . "\n";
    if (count($usersByEmail) >= 1 && $usersByEmail[0]->getName() === 'Jane Smith') {
        echo "   ✓ Utilisateur trouvé par email\n";
    } else {
        echo "   ❌ Debug: ";
        foreach ($usersByEmail as $u) echo $u->getName() . " ";
        echo "\n";
        throw new Exception("Recherche par email échouée");
    }

    // Test 6: Update
    echo "\n✅ Test 6: Mise à jour utilisateur\n";
    $user1->setName('John Doe Updated');
    $user1->setSettings(['theme' => 'light', 'language' => 'fr']);
    
    if ($userRepo->update($user1)) {
        echo "   ✓ Utilisateur mis à jour\n";
        
        // Vérifier la mise à jour
        $updatedUser = $userRepo->findById('user1');
        if ($updatedUser->getName() === 'John Doe Updated') {
            echo "   ✓ Nom mis à jour confirmé\n";
        }
        if ($updatedUser->getSettings()['theme'] === 'light') {
            echo "   ✓ Settings mis à jour confirmés\n";
        }
    } else {
        throw new Exception("Erreur mise à jour");
    }

    // Test 7: Exists
    echo "\n✅ Test 7: Vérification existence\n";
    if ($userRepo->exists('user1')) {
        echo "   ✓ User1 existe\n";
    }
    if (!$userRepo->exists('user999')) {
        echo "   ✓ User999 n'existe pas\n";
    }

    // Test 8: Delete
    echo "\n✅ Test 8: Suppression\n";
    if ($userRepo->delete('user2')) {
        echo "   ✓ User2 supprimé\n";
        
        if (!$userRepo->exists('user2')) {
            echo "   ✓ Suppression confirmée\n";
        }
    } else {
        throw new Exception("Erreur suppression");
    }

    // Vérification finale
    $finalUsers = $userRepo->findAll();
    echo "\n✅ Vérification finale : " . count($finalUsers) . " utilisateurs restants\n";

    echo "\n🎯 UserRepository: TOUS LES TESTS OK!\n";

    // Nettoyage
    if (file_exists('data/test_users.xml')) {
        unlink('data/test_users.xml');
        echo "   ✓ Fichier de test nettoyé\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "❌ Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} 