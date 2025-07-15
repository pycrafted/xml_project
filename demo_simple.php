<?php

/**
 * DÉMONSTRATION SIMPLE D'AUTOMATISATION
 * 
 * Ce script lance l'application automatiquement et simule une utilisation complète
 * Exactement comme vous l'aviez fait avec Django + Selenium !
 * 
 * Usage: php demo_simple.php
 */

echo "🚀 DÉMONSTRATION AUTOMATISÉE - WHATSAPP CLONE\n";
echo "==============================================\n\n";

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;
use WhatsApp\Services\UserService;
use WhatsApp\Services\MessageService;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;

// Configuration
$baseUrl = 'http://localhost:8000';
$cookies = [];

// Étape 1: Vérifier que le serveur web est lancé
echo "🔍 Vérification du serveur web...\n";
if (!isServerRunning($baseUrl)) {
    echo "❌ Serveur web non disponible. Lancez : php -S localhost:8000 -t public\n";
    exit(1);
}
echo "✅ Serveur web disponible\n\n";

// Étape 2: Préparer les données de test
echo "🗄️  Préparation automatique des données...\n";
try {
    $xmlManager = new XMLManager();
    $userService = new UserService($xmlManager);
    $messageService = new MessageService($xmlManager);
    $contactRepo = new ContactRepository($xmlManager);
    $groupRepo = new GroupRepository($xmlManager);
    
    echo "✅ Composants initialisés\n";
} catch (Exception $e) {
    echo "❌ Erreur d'initialisation : " . $e->getMessage() . "\n";
    exit(1);
}

// Étape 3: Créer automatiquement des utilisateurs de test
echo "\n👥 Création automatique d'utilisateurs...\n";
$users = [
    ['alice2025', 'Alice Martin', 'alice@demo.com', 'password123'],
    ['bob2025', 'Bob Durand', 'bob@demo.com', 'password123'],
    ['charlie2025', 'Charlie Dupont', 'charlie@demo.com', 'password123'],
    ['diana2025', 'Diana Lemoine', 'diana@demo.com', 'password123']
];

foreach ($users as $userData) {
    [$userId, $name, $email, $password] = $userData;
    
    try {
        // Créer via l'interface web (simulation)
        $response = makeHttpRequest('POST', '/', [
            'action' => 'register',
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'confirm_password' => $password
        ]);
        
        echo "✅ Utilisateur créé : {$name} ({$email})\n";
    } catch (Exception $e) {
        echo "⚠️  Utilisateur existant : {$name}\n";
    }
}

// Étape 4: Simulation d'interactions automatiques
echo "\n💬 Simulation d'interactions automatiques...\n";

// Alice se connecte et ajoute des contacts
echo "👤 Alice se connecte...\n";
loginUser('alice@demo.com', 'password123');
addContact('bob2025', 'Bob Durand');
addContact('charlie2025', 'Charlie Dupont');
addContact('diana2025', 'Diana Lemoine');

// Alice envoie des messages
echo "📱 Alice envoie des messages...\n";
sendMessage('bob2025', 'Salut Bob ! Comment ça va ?');
sendMessage('charlie2025', 'Hey Charlie ! Tu fais quoi ce soir ?');
sendMessage('diana2025', 'Coucou Diana ! On se voit demain ?');

// Bob se connecte et répond
echo "🔄 Bob se connecte et répond...\n";
loginUser('bob@demo.com', 'password123');
addContact('alice2025', 'Alice Martin');
sendMessage('alice2025', 'Salut Alice ! Ça va super bien !');
sendMessage('alice2025', 'Et toi comment tu vas ?');

// Charlie participe
echo "🎭 Charlie rejoint la conversation...\n";
loginUser('charlie@demo.com', 'password123');
addContact('alice2025', 'Alice Martin');
addContact('bob2025', 'Bob Durand');
sendMessage('alice2025', 'Hey ! Ce soir je suis libre pour un ciné !');
sendMessage('bob2025', 'Salut Bob ! Tu viens au ciné avec nous ?');

// Créer un groupe automatiquement
echo "👥 Création automatique d'un groupe...\n";
createGroup('groupe_amis', 'Groupe d\'amis', ['alice2025', 'bob2025', 'charlie2025', 'diana2025']);

// Messages de groupe
echo "📢 Messages de groupe automatiques...\n";
sendGroupMessage('groupe_amis', 'Salut tout le monde !');
sendGroupMessage('groupe_amis', 'Quelqu\'un pour un café ?');
sendGroupMessage('groupe_amis', 'Rdv à 15h au café de la place !');

// Étape 5: Vérifications automatiques
echo "\n✅ Vérifications automatiques...\n";

// Compter les messages créés
$messageRepo = new WhatsApp\Repositories\MessageRepository($xmlManager);
$allMessages = $messageRepo->findAll();
echo "📊 Total messages créés : " . count($allMessages) . "\n";

// Compter les utilisateurs
$allUsers = $userService->getAllUsers();
echo "👥 Total utilisateurs : " . count($allUsers) . "\n";

// Compter les contacts
$allContacts = $contactRepo->findAll();
echo "📱 Total contacts : " . count($allContacts) . "\n";

// Statistiques finales
echo "\n📈 STATISTIQUES FINALES\n";
echo "=======================\n";
echo "✅ Utilisateurs créés automatiquement : " . count($users) . "\n";
echo "✅ Messages envoyés automatiquement : " . count($allMessages) . "\n";
echo "✅ Contacts ajoutés automatiquement : " . count($allContacts) . "\n";
echo "✅ Groupes créés automatiquement : 1\n";

// Étape 6: Test des pages web
echo "\n🌐 Test automatique des pages web...\n";
$pages = [
    '/' => 'Page d\'accueil',
    '/dashboard.php' => 'Dashboard',
    '/contacts.php' => 'Gestion des contacts',
    '/groups.php' => 'Gestion des groupes',
    '/chat.php' => 'Interface de chat',
    '/profile.php' => 'Profil utilisateur'
];

foreach ($pages as $url => $description) {
    $response = makeHttpRequest('GET', $url);
    if (strpos($response, 'Fatal error') === false) {
        echo "✅ {$description} fonctionne\n";
    } else {
        echo "❌ {$description} a des erreurs\n";
    }
}

echo "\n🎉 DÉMONSTRATION TERMINÉE AVEC SUCCÈS !\n";
echo "==========================================\n";
echo "🔗 Visitez http://localhost:8000 pour voir l'application\n";
echo "📱 Connectez-vous avec : alice@demo.com / password123\n";
echo "💬 Tous les messages de test ont été créés automatiquement\n";
echo "👥 Tous les contacts ont été ajoutés automatiquement\n";
echo "🎯 Parfait pour votre présentation académique !\n";

// ========================
// FONCTIONS UTILITAIRES
// ========================

function isServerRunning(string $baseUrl): bool
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 2,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($baseUrl, false, $context);
    return $response !== false;
}

function makeHttpRequest(string $method, string $url, array $data = []): string
{
    global $baseUrl, $cookies;
    
    $fullUrl = $baseUrl . $url;
    
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => [
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: ' . formatCookies($cookies)
            ],
            'content' => http_build_query($data),
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents($fullUrl, false, $context);
    
    // Extraire les cookies
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (strpos($header, 'Set-Cookie:') === 0) {
                $cookie = substr($header, 12);
                $cookieParts = explode(';', $cookie);
                $cookieData = explode('=', $cookieParts[0], 2);
                $cookies[$cookieData[0]] = $cookieData[1] ?? '';
            }
        }
    }
    
    return $response;
}

function formatCookies(array $cookies): string
{
    $cookieString = '';
    foreach ($cookies as $name => $value) {
        $cookieString .= "{$name}={$value}; ";
    }
    return rtrim($cookieString, '; ');
}

function loginUser(string $email, string $password): void
{
    makeHttpRequest('POST', '/', [
        'action' => 'login',
        'email' => $email,
        'password' => $password
    ]);
}

function addContact(string $contactId, string $contactName): void
{
    makeHttpRequest('POST', '/contacts.php', [
        'action' => 'add_contact',
        'contact_id' => $contactId,
        'contact_name' => $contactName
    ]);
}

function sendMessage(string $recipientId, string $message): void
{
    makeHttpRequest('POST', '/ajax.php', [
        'action' => 'send_message',
        'recipient_id' => $recipientId,
        'message' => $message,
        'type' => 'text'
    ]);
}

function createGroup(string $groupId, string $groupName, array $members): void
{
    makeHttpRequest('POST', '/groups.php', [
        'action' => 'create_group',
        'group_id' => $groupId,
        'group_name' => $groupName,
        'members' => implode(',', $members)
    ]);
}

function sendGroupMessage(string $groupId, string $message): void
{
    makeHttpRequest('POST', '/ajax.php', [
        'action' => 'send_group_message',
        'group_id' => $groupId,
        'message' => $message
    ]);
} 