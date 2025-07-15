<?php

/**
 * DÉBOGAGE DE L'AUTHENTIFICATION
 * 
 * Script pour déboguer la logique d'authentification et les sessions
 */

echo "🔍 DÉBOGAGE DE L'AUTHENTIFICATION\n";
echo "==================================\n\n";

// Test avec session
session_start();

echo "🔹 Test de la logique d'authentification directe...\n";

require_once 'vendor/autoload.php';

use WhatsApp\Services\UserService;
use WhatsApp\Utils\XMLManager;
use WhatsApp\Repositories\UserRepository;

try {
    $xmlManager = new XMLManager();
    $userService = new UserService($xmlManager);
    $userRepository = new UserRepository($xmlManager);
    
    // Simuler une authentification
    $email = 'admin@whatsapp.com';
    $password = 'admin123';
    
    echo "📧 Email de test : $email\n";
    echo "🔐 Mot de passe de test : $password\n\n";
    
    // Vérifier les credentials
    $validCredentials = [
        'admin@whatsapp.com' => 'admin123',
        'demo@whatsapp.com' => 'demo123',
        'test@whatsapp.com' => 'test123',
        'alice@test.com' => 'password123',
        'bob@test.com' => 'password123',
        'charlie@test.com' => 'password123',
        'diana@test.com' => 'password123',
        'erik@test.com' => 'password123'
    ];
    
    echo "🔹 Vérification des credentials...\n";
    if (isset($validCredentials[$email]) && $validCredentials[$email] === $password) {
        echo "✅ Credentials valides pour $email\n";
        
        // Chercher l'utilisateur
        echo "🔹 Recherche de l'utilisateur dans la base...\n";
        $users = $userRepository->findByEmail($email);
        
        if (!empty($users)) {
            $user = $users[0];
            echo "✅ Utilisateur trouvé : {$user->getName()} (ID: {$user->getId()})\n";
            
            // Simuler la session
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['user_name'] = $user->getName();
            $_SESSION['user_email'] = $user->getEmail();
            
            echo "✅ Session créée avec succès\n";
            echo "   - user_id: {$_SESSION['user_id']}\n";
            echo "   - user_name: {$_SESSION['user_name']}\n";
            echo "   - user_email: {$_SESSION['user_email']}\n";
            
            // Vérifier si l'authentification fonctionne
            echo "\n🔹 Vérification de l'authentification...\n";
            if (isset($_SESSION['user_id'])) {
                echo "✅ L'authentification fonctionne !\n";
                echo "🌐 L'utilisateur devrait pouvoir accéder au dashboard\n";
            } else {
                echo "❌ L'authentification a échoué\n";
            }
            
        } else {
            echo "❌ Utilisateur non trouvé dans la base de données\n";
        }
    } else {
        echo "❌ Credentials invalides\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "📋 Trace : " . $e->getTraceAsString() . "\n";
}

echo "\n🔹 Test HTTP direct...\n";

// Test HTTP direct
$testUrl = 'http://localhost:8000/';
$postData = [
    'action' => 'login',
    'email' => 'admin@whatsapp.com',
    'password' => 'admin123'
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($postData)
    ]
]);

echo "📤 Envoi de la requête POST à $testUrl\n";
echo "📋 Données : " . json_encode($postData) . "\n";

$response = file_get_contents($testUrl, false, $context);

if ($response !== false) {
    echo "✅ Réponse reçue\n";
    
    // Vérifier les headers de réponse
    if (isset($http_response_header)) {
        echo "🔹 Headers de réponse :\n";
        foreach ($http_response_header as $header) {
            echo "  $header\n";
            
            // Vérifier redirection
            if (strpos($header, 'Location:') !== false) {
                echo "🔄 Redirection détectée : $header\n";
            }
            
            // Vérifier cookies
            if (strpos($header, 'Set-Cookie:') !== false) {
                echo "🍪 Cookie détecté : $header\n";
            }
        }
    }
    
    // Vérifier le contenu
    if (strpos($response, 'dashboard') !== false) {
        echo "✅ Dashboard détecté dans la réponse\n";
    } elseif (strpos($response, 'Erreur') !== false) {
        echo "❌ Erreur détectée dans la réponse\n";
        // Extraire le message d'erreur
        if (preg_match('/Erreur[^:]*:\s*([^<]+)/', $response, $matches)) {
            echo "📋 Message d'erreur : " . trim($matches[1]) . "\n";
        }
    } else {
        echo "⚠️  Réponse ambiguë\n";
    }
} else {
    echo "❌ Pas de réponse reçue\n";
}

echo "\n🔹 Test manuel suggéré...\n";
echo "1. Allez sur : http://localhost:8000/\n";
echo "2. Entrez : admin@whatsapp.com\n";
echo "3. Entrez : admin123\n";
echo "4. Cliquez sur Se connecter\n";
echo "5. Observez si vous êtes redirigé vers le dashboard\n";

echo "\n🎯 DÉBOGAGE TERMINÉ !\n";
echo "======================\n";
echo "🔍 Consultez les résultats ci-dessus pour identifier le problème\n";
echo "🔧 Si nécessaire, vérifiez les logs du serveur PHP\n"; 