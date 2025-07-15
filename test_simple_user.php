<?php

require_once 'vendor/autoload.php';

use WhatsApp\Models\User;
use WhatsApp\Utils\XMLManager;
use WhatsApp\Repositories\UserRepository;

// Nettoyage
if (file_exists('data/simple_test.xml')) {
    unlink('data/simple_test.xml');
}

try {
    echo "🔍 Test UserRepository Simple...\n";
    
    $xmlManager = new XMLManager('data/simple_test.xml');
    $userRepo = new UserRepository($xmlManager);
    echo "✓ Repositories créés\n";

    // Test 1: Utilisateur simple
    $user = new User('simple1', 'Simple User', 'simple@test.com');
    echo "✓ User créé\n";

    $result = $userRepo->create($user);
    echo "✓ Create: " . ($result ? "OK" : "ERREUR") . "\n";

    // Test 2: FindAll
    $allUsers = $userRepo->findAll();
    echo "✓ FindAll: " . count($allUsers) . " users\n";

    // Test 3: FindByEmail
    $emailUsers = $userRepo->findByEmail('simple@test.com');
    echo "✓ FindByEmail: " . count($emailUsers) . " users trouvés\n";
    
    if (count($emailUsers) > 0) {
        echo "✓ Premier user trouvé: " . $emailUsers[0]->getName() . "\n";
    }

    echo "\n🎯 Test simple: OK!\n";

    // Nettoyage
    unlink('data/simple_test.xml');

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "❌ Trace: " . $e->getTraceAsString() . "\n";
} 