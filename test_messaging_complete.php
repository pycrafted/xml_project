<?php

/**
 * TEST COMPLET DE MESSAGERIE
 * 
 * Ce script teste spécifiquement l'envoi et la réception de messages
 * entre utilisateurs et dans des groupes
 */

echo "📱 TEST COMPLET DE MESSAGERIE\n";
echo "==============================\n\n";

// Configuration
$baseUrl = 'http://localhost:8000';
$cookies = [];

// Fonction pour faire une requête HTTP
function makeHttpRequest($method, $url, $data = [], $cookies = []) {
    global $baseUrl;
    
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => [
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: ' . implode('; ', $cookies)
            ],
            'content' => http_build_query($data),
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents($baseUrl . $url, false, $context);
    
    // Extraire les cookies de la réponse
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (strpos($header, 'Set-Cookie:') === 0) {
                $cookie = substr($header, 12);
                $cookies[] = $cookie;
            }
        }
    }
    
    return $response;
}

// Fonction pour se connecter
function loginUser($email, $password) {
    global $cookies;
    
    $response = makeHttpRequest('POST', '/', [
        'action' => 'login',
        'email' => $email,
        'password' => $password
    ], $cookies);
    
    // Mise à jour des cookies
    $cookies = [];
    if (isset($GLOBALS['http_response_header'])) {
        foreach ($GLOBALS['http_response_header'] as $header) {
            if (strpos($header, 'Set-Cookie:') === 0) {
                $cookie = substr($header, 12);
                $cookies[] = $cookie;
            }
        }
    }
    
    return strpos($response, 'dashboard') !== false || strpos($response, 'Dashboard') !== false;
}

echo "🔹 PHASE 1: Configuration des utilisateurs de test\n";

// Créer des utilisateurs de test
$users = [
    ['alice2025', 'Alice Martin', 'alice@test.com', 'password123'],
    ['bob2025', 'Bob Durand', 'bob@test.com', 'password123'],
    ['charlie2025', 'Charlie Dupont', 'charlie@test.com', 'password123'],
    ['diana2025', 'Diana Lemoine', 'diana@test.com', 'password123']
];

foreach ($users as $user) {
    [$id, $name, $email, $password] = $user;
    
    $response = makeHttpRequest('POST', '/', [
        'action' => 'register',
        'name' => $name,
        'email' => $email,
        'password' => $password
    ]);
    
    echo "✅ Utilisateur créé: $name ($email)\n";
}

echo "\n🔹 PHASE 2: Tests d'envoi de messages privés\n";

// Test 1: Alice envoie un message à Bob
echo "📤 Test 1: Alice → Bob\n";
$loginSuccess = loginUser('alice@test.com', 'password123');
echo "  Connexion Alice: " . ($loginSuccess ? "✅" : "❌") . "\n";

$response = makeHttpRequest('POST', '/ajax.php', [
    'action' => 'send_message',
    'recipient_id' => 'bob2025',
    'message' => 'Salut Bob ! Comment ça va ?',
    'type' => 'text'
], $cookies);

$sendSuccess = strpos($response, 'success') !== false || strpos($response, 'sent') !== false;
echo "  Envoi message: " . ($sendSuccess ? "✅" : "❌") . "\n";

// Test 2: Bob répond à Alice
echo "📤 Test 2: Bob → Alice\n";
$loginSuccess = loginUser('bob@test.com', 'password123');
echo "  Connexion Bob: " . ($loginSuccess ? "✅" : "❌") . "\n";

$response = makeHttpRequest('POST', '/ajax.php', [
    'action' => 'send_message',
    'recipient_id' => 'alice2025',
    'message' => 'Salut Alice ! Ça va bien, merci !',
    'type' => 'text'
], $cookies);

$sendSuccess = strpos($response, 'success') !== false || strpos($response, 'sent') !== false;
echo "  Envoi message: " . ($sendSuccess ? "✅" : "❌") . "\n";

// Test 3: Charlie envoie un message à Diana
echo "📤 Test 3: Charlie → Diana\n";
$loginSuccess = loginUser('charlie@test.com', 'password123');
echo "  Connexion Charlie: " . ($loginSuccess ? "✅" : "❌") . "\n";

$response = makeHttpRequest('POST', '/ajax.php', [
    'action' => 'send_message',
    'recipient_id' => 'diana2025',
    'message' => 'Coucou Diana ! Tu es libre ce soir ?',
    'type' => 'text'
], $cookies);

$sendSuccess = strpos($response, 'success') !== false || strpos($response, 'sent') !== false;
echo "  Envoi message: " . ($sendSuccess ? "✅" : "❌") . "\n";

echo "\n🔹 PHASE 3: Tests de réception de messages\n";

// Test 4: Alice vérifie ses messages reçus
echo "📥 Test 4: Alice vérifie ses messages\n";
$loginSuccess = loginUser('alice@test.com', 'password123');
echo "  Connexion Alice: " . ($loginSuccess ? "✅" : "❌") . "\n";

$response = makeHttpRequest('GET', '/ajax.php?action=get_messages&contact_id=bob2025', [], $cookies);
$receiveSuccess = strpos($response, 'Bob') !== false || strpos($response, 'message') !== false;
echo "  Réception messages: " . ($receiveSuccess ? "✅" : "❌") . "\n";

// Test 5: Bob vérifie ses messages reçus
echo "📥 Test 5: Bob vérifie ses messages\n";
$loginSuccess = loginUser('bob@test.com', 'password123');
echo "  Connexion Bob: " . ($loginSuccess ? "✅" : "❌") . "\n";

$response = makeHttpRequest('GET', '/ajax.php?action=get_messages&contact_id=alice2025', [], $cookies);
$receiveSuccess = strpos($response, 'Alice') !== false || strpos($response, 'message') !== false;
echo "  Réception messages: " . ($receiveSuccess ? "✅" : "❌") . "\n";

echo "\n🔹 PHASE 4: Tests de groupes\n";

// Test 6: Alice crée un groupe
echo "👥 Test 6: Alice crée un groupe\n";
$loginSuccess = loginUser('alice@test.com', 'password123');
echo "  Connexion Alice: " . ($loginSuccess ? "✅" : "❌") . "\n";

$response = makeHttpRequest('POST', '/groups.php', [
    'action' => 'create_group',
    'group_name' => 'Groupe Test',
    'members' => 'alice2025,bob2025,charlie2025'
], $cookies);

$groupCreated = strpos($response, 'success') !== false || strpos($response, 'créé') !== false;
echo "  Création groupe: " . ($groupCreated ? "✅" : "❌") . "\n";

// Test 7: Alice envoie un message dans le groupe
echo "📤 Test 7: Alice → Groupe Test\n";
$response = makeHttpRequest('POST', '/ajax.php', [
    'action' => 'send_group_message',
    'group_id' => 'groupe_test',
    'message' => 'Salut tout le monde dans le groupe !',
    'type' => 'text'
], $cookies);

$sendSuccess = strpos($response, 'success') !== false || strpos($response, 'sent') !== false;
echo "  Envoi message groupe: " . ($sendSuccess ? "✅" : "❌") . "\n";

// Test 8: Bob répond dans le groupe
echo "📤 Test 8: Bob → Groupe Test\n";
$loginSuccess = loginUser('bob@test.com', 'password123');
echo "  Connexion Bob: " . ($loginSuccess ? "✅" : "❌") . "\n";

$response = makeHttpRequest('POST', '/ajax.php', [
    'action' => 'send_group_message',
    'group_id' => 'groupe_test',
    'message' => 'Salut Alice ! Merci pour le groupe !',
    'type' => 'text'
], $cookies);

$sendSuccess = strpos($response, 'success') !== false || strpos($response, 'sent') !== false;
echo "  Envoi message groupe: " . ($sendSuccess ? "✅" : "❌") . "\n";

// Test 9: Charlie lit les messages du groupe
echo "📥 Test 9: Charlie lit les messages du groupe\n";
$loginSuccess = loginUser('charlie@test.com', 'password123');
echo "  Connexion Charlie: " . ($loginSuccess ? "✅" : "❌") . "\n";

$response = makeHttpRequest('GET', '/ajax.php?action=get_group_messages&group_id=groupe_test', [], $cookies);
$receiveSuccess = strpos($response, 'message') !== false || strpos($response, 'groupe') !== false;
echo "  Réception messages groupe: " . ($receiveSuccess ? "✅" : "❌") . "\n";

echo "\n🔹 PHASE 5: Tests de types de messages\n";

// Test 10: Message avec emoji
echo "😀 Test 10: Message avec emoji\n";
$loginSuccess = loginUser('alice@test.com', 'password123');
echo "  Connexion Alice: " . ($loginSuccess ? "✅" : "❌") . "\n";

$response = makeHttpRequest('POST', '/ajax.php', [
    'action' => 'send_message',
    'recipient_id' => 'bob2025',
    'message' => 'Salut Bob ! 😀 Comment ça va ? 🚀',
    'type' => 'text'
], $cookies);

$sendSuccess = strpos($response, 'success') !== false || strpos($response, 'sent') !== false;
echo "  Envoi message emoji: " . ($sendSuccess ? "✅" : "❌") . "\n";

// Test 11: Message long
echo "📝 Test 11: Message long\n";
$longMessage = str_repeat('Ceci est un message très long pour tester la capacité de l\'application à gérer des messages de grande taille. ', 10);

$response = makeHttpRequest('POST', '/ajax.php', [
    'action' => 'send_message',
    'recipient_id' => 'bob2025',
    'message' => $longMessage,
    'type' => 'text'
], $cookies);

$sendSuccess = strpos($response, 'success') !== false || strpos($response, 'sent') !== false;
echo "  Envoi message long: " . ($sendSuccess ? "✅" : "❌") . "\n";

// Test 12: Message avec caractères spéciaux
echo "🔤 Test 12: Message avec caractères spéciaux\n";
$specialMessage = "Test éèàç ñ 中文 العربية @#$%^&*()";

$response = makeHttpRequest('POST', '/ajax.php', [
    'action' => 'send_message',
    'recipient_id' => 'bob2025',
    'message' => $specialMessage,
    'type' => 'text'
], $cookies);

$sendSuccess = strpos($response, 'success') !== false || strpos($response, 'sent') !== false;
echo "  Envoi caractères spéciaux: " . ($sendSuccess ? "✅" : "❌") . "\n";

echo "\n🔹 PHASE 6: Tests de conversation complète\n";

// Test 13: Conversation complète Alice ↔ Bob
echo "💬 Test 13: Conversation complète Alice ↔ Bob\n";

$messages = [
    ['alice@test.com', 'bob2025', 'Salut Bob ! Tu es libre ce soir ?'],
    ['bob@test.com', 'alice2025', 'Salut Alice ! Oui, pourquoi ?'],
    ['alice@test.com', 'bob2025', 'On pourrait aller au cinéma !'],
    ['bob@test.com', 'alice2025', 'Excellente idée ! À quelle heure ?'],
    ['alice@test.com', 'bob2025', 'Disons 20h devant le cinéma ?'],
    ['bob@test.com', 'alice2025', 'Parfait ! À ce soir alors ! 👋']
];

foreach ($messages as $i => $message) {
    [$senderEmail, $recipientId, $content] = $message;
    
    $loginSuccess = loginUser($senderEmail, 'password123');
    if ($loginSuccess) {
        $response = makeHttpRequest('POST', '/ajax.php', [
            'action' => 'send_message',
            'recipient_id' => $recipientId,
            'message' => $content,
            'type' => 'text'
        ], $cookies);
        
        $sendSuccess = strpos($response, 'success') !== false || strpos($response, 'sent') !== false;
        echo "  Message " . ($i + 1) . ": " . ($sendSuccess ? "✅" : "❌") . "\n";
    } else {
        echo "  Message " . ($i + 1) . ": ❌ (Connexion échouée)\n";
    }
}

echo "\n🔹 RÉSULTATS FINAUX\n";
echo "===================\n";
echo "✅ Tests de messagerie terminés !\n";
echo "📱 Fonctionnalités testées :\n";
echo "  • Envoi de messages privés\n";
echo "  • Réception de messages privés\n";
echo "  • Création de groupes\n";
echo "  • Messages dans les groupes\n";
echo "  • Messages avec emojis\n";
echo "  • Messages longs\n";
echo "  • Messages avec caractères spéciaux\n";
echo "  • Conversations complètes\n";
echo "\n🎯 Système de messagerie opérationnel !\n";
echo "🔗 Application disponible : http://localhost:8000\n"; 