<?php

/**
 * CORRECTION DES UTILISATEURS DE DÉMONSTRATION
 * 
 * Créer correctement les utilisateurs de démonstration avec leurs emails
 * pour que l'authentification fonctionne
 */

echo "🔧 CORRECTION DES UTILISATEURS DE DÉMONSTRATION\n";
echo "================================================\n\n";

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;
use WhatsApp\Services\UserService;
use WhatsApp\Repositories\UserRepository;

try {
    $xmlManager = new XMLManager();
    $userService = new UserService($xmlManager);
    $userRepository = new UserRepository($xmlManager);
    
    echo "🔹 Création des utilisateurs de démonstration...\n";
    
    // Utilisateurs de démonstration avec leurs emails corrects
    $demoUsers = [
        [
            'id' => 'admin',
            'name' => 'Administrateur',
            'email' => 'admin@whatsapp.com',
            'password' => 'admin123'
        ],
        [
            'id' => 'demo',
            'name' => 'Utilisateur Demo',
            'email' => 'demo@whatsapp.com',
            'password' => 'demo123'
        ],
        [
            'id' => 'test',
            'name' => 'Test User',
            'email' => 'test@whatsapp.com',
            'password' => 'test123'
        ],
        [
            'id' => 'alice2025',
            'name' => 'Alice Martin',
            'email' => 'alice@test.com',
            'password' => 'password123'
        ],
        [
            'id' => 'bob2025',
            'name' => 'Bob Durand',
            'email' => 'bob@test.com',
            'password' => 'password123'
        ],
        [
            'id' => 'charlie2025',
            'name' => 'Charlie Dupont',
            'email' => 'charlie@test.com',
            'password' => 'password123'
        ],
        [
            'id' => 'diana2025',
            'name' => 'Diana Lemoine',
            'email' => 'diana@test.com',
            'password' => 'password123'
        ],
        [
            'id' => 'erik2025',
            'name' => 'Erik Rousseau',
            'email' => 'erik@test.com',
            'password' => 'password123'
        ]
    ];
    
    foreach ($demoUsers as $userData) {
        // Vérifier si l'utilisateur existe déjà
        $existingUsers = $userRepository->findByEmail($userData['email']);
        
        if (empty($existingUsers)) {
            // Créer l'utilisateur
            $userService->createUser($userData['id'], $userData['name'], $userData['email']);
            echo "✅ Utilisateur créé: {$userData['name']} ({$userData['email']})\n";
        } else {
            echo "⚠️  Utilisateur existe déjà: {$userData['name']} ({$userData['email']})\n";
        }
    }
    
    echo "\n🔹 Vérification des utilisateurs créés...\n";
    
    // Vérifier que tous les utilisateurs sont bien créés
    foreach ($demoUsers as $userData) {
        $users = $userRepository->findByEmail($userData['email']);
        if (!empty($users)) {
            $user = $users[0];
            echo "✅ Utilisateur vérifié: {$user->getName()} (ID: {$user->getId()}, Email: {$user->getEmail()})\n";
        } else {
            echo "❌ Utilisateur non trouvé: {$userData['email']}\n";
        }
    }
    
    echo "\n🔹 Test d'authentification...\n";
    
    // Test d'authentification pour chaque utilisateur
    foreach ($demoUsers as $userData) {
        $users = $userRepository->findByEmail($userData['email']);
        if (!empty($users)) {
            $user = $users[0];
            echo "✅ Authentification possible pour: {$userData['email']}\n";
        } else {
            echo "❌ Authentification impossible pour: {$userData['email']}\n";
        }
    }
    
    echo "\n🎉 CORRECTION TERMINÉE !\n";
    echo "========================\n\n";
    
    echo "🔑 COMPTES DE DÉMONSTRATION PRÊTS :\n";
    echo "  👨‍💼 admin@whatsapp.com / admin123\n";
    echo "  🎪 demo@whatsapp.com / demo123\n";
    echo "  🧪 test@whatsapp.com / test123\n";
    echo "  🔬 alice@test.com / password123\n";
    echo "  🔬 bob@test.com / password123\n";
    echo "  🔬 charlie@test.com / password123\n";
    echo "  🔬 diana@test.com / password123\n";
    echo "  🔬 erik@test.com / password123\n";
    
    echo "\n🌐 Testez maintenant sur : http://localhost:8000\n";
    echo "🚀 Utilisez un des comptes ci-dessus pour vous connecter !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la correction : " . $e->getMessage() . "\n";
    echo "📋 Détails de l'erreur :\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n🧪 TESTS RECOMMANDÉS :\n";
echo "  • php quick_test_modifications.php\n";
echo "  • php test_login_modifications.php\n";
echo "  • Testez manuellement sur http://localhost:8000\n"; 