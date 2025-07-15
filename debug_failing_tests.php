<?php

/**
 * DÉBOGAGE DES TESTS ÉCHOUÉS
 * 
 * Ce script analyse en détail les 2 tests qui échouent
 */

echo "🔍 DÉBOGAGE DES TESTS ÉCHOUÉS\n";
echo "==============================\n\n";

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;
use WhatsApp\Services\UserService;
use WhatsApp\Repositories\ContactRepository;

// Fonction pour faire une requête HTTP comme les tests
function makeHttpRequest($method, $url, $data = []) {
    $baseUrl = 'http://localhost:8000';
    
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    return file_get_contents($baseUrl . $url, false, $context);
}

// Fonction pour se connecter
function loginUser($email, $password) {
    $response = makeHttpRequest('POST', '/', [
        'action' => 'login',
        'email' => $email,
        'password' => $password
    ]);
    
    return strpos($response, 'dashboard') !== false || strpos($response, 'Dashboard') !== false;
}

echo "🔹 DÉBOGAGE 1: Test user_settings_update\n";

// Test 1: Essayer de se connecter comme Alice
echo "  🔸 Test de connexion Alice...\n";
$loginSuccess = loginUser('alice@test.com', 'password123');
echo "  Résultat connexion: " . ($loginSuccess ? "✅ RÉUSSI" : "❌ ÉCHOUÉ") . "\n";

// Test 2: Essayer d'envoyer la requête update_settings
echo "  🔸 Test de la requête update_settings...\n";
$response = makeHttpRequest('POST', '/profile.php', [
    'action' => 'update_settings',
    'theme' => 'dark',
    'notifications' => 'true'
]);

echo "  Longueur de la réponse: " . strlen($response) . " caractères\n";
echo "  Recherche 'success': " . (strpos($response, 'success') !== false ? "✅ TROUVÉ" : "❌ NON TROUVÉ") . "\n";
echo "  Recherche 'sauvegardés': " . (strpos($response, 'sauvegardés') !== false ? "✅ TROUVÉ" : "❌ NON TROUVÉ") . "\n";
echo "  Recherche 'updated': " . (strpos($response, 'updated') !== false ? "✅ TROUVÉ" : "❌ NON TROUVÉ") . "\n";

// Afficher un extrait de la réponse
echo "  🔸 Extrait de la réponse (premiers 500 caractères):\n";
echo "  " . substr($response, 0, 500) . "...\n\n";

echo "🔹 DÉBOGAGE 2: Test delete_contact\n";

// Test 1: Vérifier si le contact bob2025 existe
echo "  🔸 Vérification de l'existence du contact bob2025...\n";
try {
    $xmlManager = new XMLManager();
    $contactRepo = new ContactRepository($xmlManager);
    
    $contacts = $contactRepo->getContactsByUserId('alice2025');
    $bob2025Found = false;
    
    foreach ($contacts as $contact) {
        if ($contact->getContactUserId() === 'bob2025') {
            $bob2025Found = true;
            echo "  Contact bob2025 trouvé: ID=" . $contact->getId() . ", Nom=" . $contact->getName() . "\n";
            break;
        }
    }
    
    if (!$bob2025Found) {
        echo "  ❌ Contact bob2025 NON TROUVÉ\n";
        echo "  📋 Contacts existants pour alice2025:\n";
        foreach ($contacts as $contact) {
            echo "    - ID: " . $contact->getId() . ", Nom: " . $contact->getName() . ", ContactUserId: " . $contact->getContactUserId() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "  ❌ Erreur lors de la vérification: " . $e->getMessage() . "\n";
}

// Test 2: Essayer d'envoyer la requête delete_contact
echo "  🔸 Test de la requête delete_contact...\n";
$response = makeHttpRequest('POST', '/contacts.php', [
    'action' => 'delete_contact',
    'contact_id' => 'bob2025'
]);

echo "  Longueur de la réponse: " . strlen($response) . " caractères\n";
echo "  Recherche 'success': " . (strpos($response, 'success') !== false ? "✅ TROUVÉ" : "❌ NON TROUVÉ") . "\n";
echo "  Recherche 'supprimé': " . (strpos($response, 'supprimé') !== false ? "✅ TROUVÉ" : "❌ NON TROUVÉ") . "\n";
echo "  Recherche 'deleted': " . (strpos($response, 'deleted') !== false ? "✅ TROUVÉ" : "❌ NON TROUVÉ") . "\n";

// Afficher un extrait de la réponse
echo "  🔸 Extrait de la réponse (premiers 500 caractères):\n";
echo "  " . substr($response, 0, 500) . "...\n\n";

// Test 3: Créer un contact avec l'ID exact attendu par le test
echo "  🔸 Création d'un contact avec l'ID exact pour le test...\n";
try {
    $xmlManager = new XMLManager();
    $contactRepo = new ContactRepository($xmlManager);
    
    // Créer un contact avec l'ID 'bob2025' qui correspond exactement au test
    $contactId = $contactRepo->createContact("Bob Durand", "alice2025", "bob2025");
    echo "  ✅ Contact créé avec ID: " . $contactId . "\n";
    
} catch (Exception $e) {
    echo "  ⚠️  Erreur ou contact existe déjà: " . $e->getMessage() . "\n";
}

echo "🔹 DÉBOGAGE 3: Vérification des méthodes\n";

// Vérifier UserService::updateUser
echo "  🔸 Test UserService::updateUser...\n";
try {
    $xmlManager = new XMLManager();
    $userService = new UserService($xmlManager);
    
    $user = $userService->findUserById('alice2025');
    if ($user) {
        echo "  ✅ Utilisateur alice2025 trouvé: " . $user->getName() . "\n";
        
        $result = $userService->updateUser('alice2025', [
            'settings' => [
                'theme' => 'dark',
                'notifications' => 'true'
            ]
        ]);
        
        echo "  Résultat updateUser: " . ($result ? "✅ RÉUSSI" : "❌ ÉCHOUÉ") . "\n";
    } else {
        echo "  ❌ Utilisateur alice2025 NON TROUVÉ\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Erreur UserService: " . $e->getMessage() . "\n";
}

// Vérifier ContactRepository::deleteContact
echo "  🔸 Test ContactRepository::deleteContact...\n";
try {
    $xmlManager = new XMLManager();
    $contactRepo = new ContactRepository($xmlManager);
    
    // Tester avec un contact existant
    $contacts = $contactRepo->getContactsByUserId('alice2025');
    if (count($contacts) > 0) {
        $testContact = $contacts[0];
        echo "  Contact de test: ID=" . $testContact->getId() . ", Nom=" . $testContact->getName() . "\n";
        
        $result = $contactRepo->deleteContact($testContact->getId());
        echo "  Résultat deleteContact: " . ($result ? "✅ RÉUSSI" : "❌ ÉCHOUÉ") . "\n";
    } else {
        echo "  ❌ Aucun contact trouvé pour tester\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Erreur ContactRepository: " . $e->getMessage() . "\n";
}

echo "\n🎯 DÉBOGAGE TERMINÉ !\n";
echo "======================\n";
echo "📊 Résultats du débogage ci-dessus\n";
echo "🔧 Corrections suggérées basées sur les résultats\n"; 