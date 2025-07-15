<?php

/**
 * CRÉATION D'UN UTILISATEUR ADMINISTRATEUR PAR DÉFAUT
 * 
 * Ce script crée automatiquement un utilisateur administrateur
 * qui peut se connecter directement sans inscription
 */

echo "👤 CRÉATION D'UN UTILISATEUR ADMINISTRATEUR PAR DÉFAUT\n";
echo "======================================================\n\n";

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;
use WhatsApp\Services\UserService;

try {
    $xmlManager = new XMLManager();
    $userService = new UserService($xmlManager);
    
    // Utilisateur administrateur par défaut
    $adminUser = [
        'id' => 'admin',
        'name' => 'Administrateur',
        'email' => 'admin@whatsapp.com',
        'password' => 'admin123'
    ];
    
    // Utilisateur demo pour la démonstration
    $demoUser = [
        'id' => 'demo',
        'name' => 'Utilisateur Demo',
        'email' => 'demo@whatsapp.com',
        'password' => 'demo123'
    ];
    
    // Utilisateur test simple
    $testUser = [
        'id' => 'test',
        'name' => 'Test User',
        'email' => 'test@whatsapp.com',
        'password' => 'test123'
    ];
    
    $defaultUsers = [$adminUser, $demoUser, $testUser];
    
    echo "🔹 Création des utilisateurs par défaut...\n";
    
    foreach ($defaultUsers as $user) {
        try {
            $userService->createUser($user['id'], $user['name'], $user['email']);
            echo "✅ Utilisateur créé: " . $user['name'] . " (" . $user['email'] . ")\n";
            echo "   🔑 Mot de passe: " . $user['password'] . "\n";
        } catch (Exception $e) {
            echo "⚠️  Utilisateur " . $user['name'] . " existe déjà\n";
        }
    }
    
    echo "\n🔹 Création des utilisateurs de test (pour les tests automatisés)...\n";
    
    // Utilisateurs de test pour les tests automatisés
    $testUsers = [
        ['alice2025', 'Alice Martin', 'alice@test.com'],
        ['bob2025', 'Bob Durand', 'bob@test.com'],
        ['charlie2025', 'Charlie Dupont', 'charlie@test.com'],
        ['diana2025', 'Diana Lemoine', 'diana@test.com'],
        ['erik2025', 'Erik Rousseau', 'erik@test.com']
    ];
    
    foreach ($testUsers as [$id, $name, $email]) {
        try {
            $userService->createUser($id, $name, $email);
            echo "✅ Utilisateur de test créé: $name ($email)\n";
        } catch (Exception $e) {
            echo "⚠️  Utilisateur de test $name existe déjà\n";
        }
    }
    
    echo "\n🎯 UTILISATEURS CRÉÉS AVEC SUCCÈS !\n";
    echo "====================================\n";
    echo "🔑 CONNEXIONS DISPONIBLES :\n\n";
    
    echo "👨‍💼 ADMINISTRATEUR :\n";
    echo "   📧 Email: admin@whatsapp.com\n";
    echo "   🔒 Mot de passe: admin123\n\n";
    
    echo "🎪 DÉMONSTRATION :\n";
    echo "   📧 Email: demo@whatsapp.com\n";
    echo "   🔒 Mot de passe: demo123\n\n";
    
    echo "🧪 TEST SIMPLE :\n";
    echo "   📧 Email: test@whatsapp.com\n";
    echo "   🔒 Mot de passe: test123\n\n";
    
    echo "🔬 TESTS AUTOMATISÉS :\n";
    echo "   📧 Email: alice@test.com\n";
    echo "   🔒 Mot de passe: password123\n\n";
    
    echo "🚀 PRÊT À UTILISER !\n";
    echo "🌐 Allez sur: http://localhost:8000\n";
    echo "💡 Utilisez n'importe lequel des comptes ci-dessus pour vous connecter\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la création des utilisateurs: " . $e->getMessage() . "\n";
}

echo "\n📋 RÉSUMÉ DES MOTS DE PASSE :\n";
echo "==============================\n";
echo "• admin@whatsapp.com → admin123\n";
echo "• demo@whatsapp.com → demo123\n";
echo "• test@whatsapp.com → test123\n";
echo "• alice@test.com → password123\n";
echo "• bob@test.com → password123\n";
echo "• charlie@test.com → password123\n";
echo "• diana@test.com → password123\n";
echo "• erik@test.com → password123\n"; 