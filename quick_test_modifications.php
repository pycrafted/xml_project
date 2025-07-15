<?php

/**
 * TEST RAPIDE DES NOUVELLES MODIFICATIONS
 * 
 * Test rapide pour vérifier que les modifications fonctionnent
 */

echo "🚀 TEST RAPIDE DES MODIFICATIONS\n";
echo "================================\n\n";

$baseUrl = "http://localhost:8000";

function testLogin($email, $password) {
    global $baseUrl;
    
    $data = [
        "action" => "login",
        "email" => $email,
        "password" => $password
    ];
    
    $context = stream_context_create([
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/x-www-form-urlencoded",
            "content" => http_build_query($data)
        ]
    ]);
    
    $response = file_get_contents($baseUrl . "/", false, $context);
    
    if (strpos($response, "dashboard") !== false || strpos($response, "Location: dashboard.php") !== false) {
        echo "✅ Connexion réussie pour $email\n";
        return true;
    } else {
        echo "❌ Échec de connexion pour $email\n";
        return false;
    }
}

// Tester les comptes de démonstration
echo "🔸 Test des comptes de démonstration...\n";
testLogin("admin@whatsapp.com", "admin123");
testLogin("demo@whatsapp.com", "demo123");
testLogin("test@whatsapp.com", "test123");
testLogin("alice@test.com", "password123");

echo "\n🔸 Test de la page d'accueil...\n";
$response = file_get_contents($baseUrl . "/");
if (strpos($response, "type=\"password\"") !== false) {
    echo "✅ Page d'accueil contient le champ mot de passe\n";
} else {
    echo "❌ Page d'accueil ne contient pas le champ mot de passe\n";
}

if (strpos($response, "admin@whatsapp.com") !== false) {
    echo "✅ Comptes de démonstration affichés\n";
} else {
    echo "❌ Comptes de démonstration non affichés\n";
}

echo "\n🎉 Test rapide terminé !\n";
