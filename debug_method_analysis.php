<?php

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;
use WhatsApp\Services\UserService;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;
use WhatsApp\Repositories\MessageRepository;

echo "=== ANALYSE DES MÉTHODES MANQUANTES ET PROBLÈMES ===\n\n";

try {
    $xmlManager = new XMLManager();
    
    // 1. Analyser UserService
    echo "1. ANALYSE UserService:\n";
    $userService = new UserService($xmlManager);
    $userServiceMethods = get_class_methods($userService);
    echo "Méthodes disponibles: " . implode(', ', $userServiceMethods) . "\n";
    
    // Vérifier les méthodes manquantes
    $requiredUserMethods = ['findUserById', 'getUserStats', 'findUserByEmail', 'getAllUsers'];
    foreach ($requiredUserMethods as $method) {
        if (!method_exists($userService, $method)) {
            echo "❌ MANQUANT: UserService::$method()\n";
        } else {
            echo "✅ PRÉSENT: UserService::$method()\n";
        }
    }
    
    // Tester getUserStats() si elle existe
    if (method_exists($userService, 'getUserStats')) {
        echo "Test getUserStats():\n";
        $stats = $userService->getUserStats();
        echo "Résultat: " . print_r($stats, true) . "\n";
    }
    
    echo "\n";
    
    // 2. Analyser ContactRepository
    echo "2. ANALYSE ContactRepository:\n";
    $contactRepo = new ContactRepository($xmlManager);
    $contactMethods = get_class_methods($contactRepo);
    echo "Méthodes disponibles: " . implode(', ', $contactMethods) . "\n";
    
    $requiredContactMethods = ['getContactsByUserId', 'getContactById', 'createContact', 'deleteContact'];
    foreach ($requiredContactMethods as $method) {
        if (!method_exists($contactRepo, $method)) {
            echo "❌ MANQUANT: ContactRepository::$method()\n";
        } else {
            echo "✅ PRÉSENT: ContactRepository::$method()\n";
        }
    }
    
    echo "\n";
    
    // 3. Analyser GroupRepository
    echo "3. ANALYSE GroupRepository:\n";
    $groupRepo = new GroupRepository($xmlManager);
    $groupMethods = get_class_methods($groupRepo);
    echo "Méthodes disponibles: " . implode(', ', $groupMethods) . "\n";
    
    $requiredGroupMethods = ['findByUserId', 'getGroupsByUserId', 'getGroupById', 'createGroup', 'deleteGroup', 'addMemberToGroup', 'removeMemberFromGroup'];
    foreach ($requiredGroupMethods as $method) {
        if (!method_exists($groupRepo, $method)) {
            echo "❌ MANQUANT: GroupRepository::$method()\n";
        } else {
            echo "✅ PRÉSENT: GroupRepository::$method()\n";
        }
    }
    
    echo "\n";
    
    // 4. Analyser MessageRepository
    echo "4. ANALYSE MessageRepository:\n";
    $messageRepo = new MessageRepository($xmlManager);
    $messageMethods = get_class_methods($messageRepo);
    echo "Méthodes disponibles: " . implode(', ', $messageMethods) . "\n";
    
    $requiredMessageMethods = ['findByUser', 'getMessagesByUserId', 'getMessagesBetweenUsers', 'getGroupMessages'];
    foreach ($requiredMessageMethods as $method) {
        if (!method_exists($messageRepo, $method)) {
            echo "❌ MANQUANT: MessageRepository::$method()\n";
        } else {
            echo "✅ PRÉSENT: MessageRepository::$method()\n";
        }
    }
    
    echo "\n";
    
    // 5. Tester XMLManager paths
    echo "5. ANALYSE XMLManager paths:\n";
    $reflector = new ReflectionClass($xmlManager);
    $properties = $reflector->getProperties();
    foreach ($properties as $property) {
        $property->setAccessible(true);
        echo "Property {$property->getName()}: " . var_export($property->getValue($xmlManager), true) . "\n";
    }
    
    echo "\nTest de chargement des fichiers:\n";
    try {
        $xmlManager->loadXML();
        echo "✅ XMLManager::loadXML() fonctionne\n";
    } catch (Exception $e) {
        echo "❌ XMLManager::loadXML() échoue: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== RÉSUMÉ DES PROBLÈMES IDENTIFIÉS ===\n";
    echo "1. UserService::findUserById() manquant\n";
    echo "2. UserService::getUserStats() peut retourner des clés incorrectes\n";
    echo "3. ContactRepository::getContactsByUserId() manquant\n";
    echo "4. GroupRepository::findByUserId() manquant\n";
    echo "5. Potentiels problèmes de paths dans XMLManager\n";
    
} catch (Exception $e) {
    echo "ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 