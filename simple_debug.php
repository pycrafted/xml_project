<?php

/**
 * DÉBOGAGE SIMPLIFIÉ DES TESTS ÉCHOUÉS
 */

echo "🔍 DÉBOGAGE SIMPLIFIÉ\n";
echo "====================\n\n";

// Fonction pour requête HTTP
function makeHttpRequest($method, $url, $data = []) {
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    return file_get_contents('http://localhost:8000' . $url, false, $context);
}

// TEST 1: user_settings_update
echo "🔹 TEST 1: user_settings_update\n";

// Se connecter d'abord
echo "  Connexion...\n";
$loginResponse = makeHttpRequest('POST', '/', [
    'action' => 'login',
    'email' => 'alice@test.com',
    'password' => 'password123'
]);

echo "  Longueur réponse login: " . strlen($loginResponse) . "\n";
echo "  Contient 'dashboard': " . (strpos($loginResponse, 'dashboard') !== false ? "OUI" : "NON") . "\n";

// Maintenant tester update_settings
echo "  Test update_settings...\n";
$settingsResponse = makeHttpRequest('POST', '/profile.php', [
    'action' => 'update_settings',
    'theme' => 'dark',
    'notifications' => 'true'
]);

echo "  Longueur réponse settings: " . strlen($settingsResponse) . "\n";
echo "  Contient 'success': " . (strpos($settingsResponse, 'success') !== false ? "OUI" : "NON") . "\n";
echo "  Contient 'sauvegardés': " . (strpos($settingsResponse, 'sauvegardés') !== false ? "OUI" : "NON") . "\n";
echo "  Contient 'updated': " . (strpos($settingsResponse, 'updated') !== false ? "OUI" : "NON") . "\n";

// Afficher le début de la réponse
echo "  Début de la réponse:\n";
echo "  " . substr($settingsResponse, 0, 200) . "...\n\n";

// TEST 2: delete_contact
echo "🔹 TEST 2: delete_contact\n";

// Test delete_contact
echo "  Test delete_contact...\n";
$deleteResponse = makeHttpRequest('POST', '/contacts.php', [
    'action' => 'delete_contact',
    'contact_id' => 'bob2025'
]);

echo "  Longueur réponse delete: " . strlen($deleteResponse) . "\n";
echo "  Contient 'success': " . (strpos($deleteResponse, 'success') !== false ? "OUI" : "NON") . "\n";
echo "  Contient 'supprimé': " . (strpos($deleteResponse, 'supprimé') !== false ? "OUI" : "NON") . "\n";
echo "  Contient 'deleted': " . (strpos($deleteResponse, 'deleted') !== false ? "OUI" : "NON") . "\n";

// Afficher le début de la réponse
echo "  Début de la réponse:\n";
echo "  " . substr($deleteResponse, 0, 200) . "...\n\n";

echo "🎯 DÉBOGAGE TERMINÉ !\n"; 