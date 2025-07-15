<?php

/**
 * SCRIPT DE DEBUG SIMPLE
 * Pour identifier le problème avec le test de validation
 */

echo "🐛 SCRIPT DE DEBUG SIMPLE\n";
echo "========================\n\n";

// Étape 1: Vérifier l'autoload
echo "1. Test autoload...\n";
try {
    require_once 'vendor/autoload.php';
    echo "   ✅ Autoload OK\n";
} catch (Exception $e) {
    echo "   ❌ Erreur autoload: " . $e->getMessage() . "\n";
    exit(1);
}

// Étape 2: Tester l'instanciation des classes
echo "2. Test instanciation des classes...\n";
try {
    use WhatsApp\Utils\XMLManager;
    use WhatsApp\Services\UserService;
    
    echo "   🔸 Test XMLManager...\n";
    $xmlManager = new XMLManager('data/debug_test.xml');
    echo "   ✅ XMLManager OK\n";
    
    echo "   🔸 Test UserService...\n";
    $userService = new UserService($xmlManager);
    echo "   ✅ UserService OK\n";
    
} catch (Exception $e) {
    echo "   ❌ Erreur instanciation: " . $e->getMessage() . "\n";
    echo "   📍 Fichier: " . $e->getFile() . "\n";
    echo "   📍 Ligne: " . $e->getLine() . "\n";
    exit(1);
}

// Étape 3: Tester la création d'un utilisateur
echo "3. Test création utilisateur...\n";
try {
    $user = $userService->createUser('test_user', 'Test User', 'test@example.com', ['theme' => 'light']);
    echo "   ✅ Utilisateur créé: " . $user->getName() . "\n";
} catch (Exception $e) {
    echo "   ❌ Erreur création utilisateur: " . $e->getMessage() . "\n";
    echo "   📍 Fichier: " . $e->getFile() . "\n";
    echo "   📍 Ligne: " . $e->getLine() . "\n";
    exit(1);
}

// Étape 4: Tester les autres services
echo "4. Test autres services...\n";
try {
    use WhatsApp\Services\MessageService;
    use WhatsApp\Repositories\ContactRepository;
    
    $messageService = new MessageService($xmlManager);
    $contactRepository = new ContactRepository($xmlManager);
    
    echo "   ✅ MessageService OK\n";
    echo "   ✅ ContactRepository OK\n";
    
} catch (Exception $e) {
    echo "   ❌ Erreur autres services: " . $e->getMessage() . "\n";
    echo "   📍 Fichier: " . $e->getFile() . "\n";
    echo "   📍 Ligne: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n🎉 TOUS LES TESTS DE BASE RÉUSSIS !\n";
echo "Le problème n'est pas dans l'initialisation des classes.\n";
echo "Le problème pourrait être dans la logique des tests eux-mêmes.\n"; 