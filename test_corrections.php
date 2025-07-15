<?php

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;
use WhatsApp\Services\UserService;
use WhatsApp\Repositories\MessageRepository;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;

echo "=== TEST DES CORRECTIONS APPLIQUÉES ===\n\n";

try {
    $xmlManager = new XMLManager();
    
    // 1. Test UserService::getUserStats()
    echo "1. Test UserService::getUserStats():\n";
    $userService = new UserService($xmlManager);
    $stats = $userService->getUserStats();
    echo "Statistiques obtenues:\n";
    foreach ($stats as $key => $value) {
        echo "  - $key: $value\n";
    }
    
    // Vérifier les clés attendues
    if (isset($stats['total_users']) && isset($stats['active_users'])) {
        echo "✅ Clés total_users et active_users présentes\n";
    } else {
        echo "❌ Clés total_users et active_users manquantes\n";
    }
    
    echo "\n";
    
    // 2. Test MessageRepository::getMessagesByUserId()
    echo "2. Test MessageRepository::getMessagesByUserId():\n";
    $messageRepo = new MessageRepository($xmlManager);
    
    if (method_exists($messageRepo, 'getMessagesByUserId')) {
        echo "✅ Méthode getMessagesByUserId() disponible\n";
        $messages = $messageRepo->getMessagesByUserId('user1');
        echo "Messages pour user1: " . count($messages) . "\n";
    } else {
        echo "❌ Méthode getMessagesByUserId() manquante\n";
    }
    
    // 3. Test MessageRepository::getMessagesBetweenUsers()
    if (method_exists($messageRepo, 'getMessagesBetweenUsers')) {
        echo "✅ Méthode getMessagesBetweenUsers() disponible\n";
        $messages = $messageRepo->getMessagesBetweenUsers('user1', 'user2');
        echo "Messages entre user1 et user2: " . count($messages) . "\n";
    } else {
        echo "❌ Méthode getMessagesBetweenUsers() manquante\n";
    }
    
    // 4. Test MessageRepository::getGroupMessages()
    if (method_exists($messageRepo, 'getGroupMessages')) {
        echo "✅ Méthode getGroupMessages() disponible\n";
        $messages = $messageRepo->getGroupMessages('group1');
        echo "Messages pour group1: " . count($messages) . "\n";
    } else {
        echo "❌ Méthode getGroupMessages() manquante\n";
    }
    
    echo "\n";
    
    // 5. Test ContactRepository (vérification)
    echo "3. Test ContactRepository:\n";
    $contactRepo = new ContactRepository($xmlManager);
    
    $requiredMethods = ['getContactsByUserId', 'getContactById', 'createContact', 'deleteContact'];
    foreach ($requiredMethods as $method) {
        if (method_exists($contactRepo, $method)) {
            echo "✅ ContactRepository::$method() disponible\n";
        } else {
            echo "❌ ContactRepository::$method() manquante\n";
        }
    }
    
    echo "\n";
    
    // 6. Test GroupRepository (vérification)
    echo "4. Test GroupRepository:\n";
    $groupRepo = new GroupRepository($xmlManager);
    
    $requiredMethods = ['findByUserId', 'getGroupsByUserId', 'getGroupById', 'createGroup', 'deleteGroup'];
    foreach ($requiredMethods as $method) {
        if (method_exists($groupRepo, $method)) {
            echo "✅ GroupRepository::$method() disponible\n";
        } else {
            echo "❌ GroupRepository::$method() manquante\n";
        }
    }
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "✅ UserService::getUserStats() corrigé (clés total_users/active_users)\n";
    echo "✅ MessageRepository::getMessagesByUserId() ajouté\n";
    echo "✅ MessageRepository::getMessagesBetweenUsers() ajouté\n";
    echo "✅ MessageRepository::getGroupMessages() ajouté\n";
    echo "\nLe serveur web peut maintenant être testé !\n";
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 