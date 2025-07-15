<?php

require_once 'vendor/autoload.php';

use WhatsApp\Models\User;
use WhatsApp\Utils\XMLManager;
use WhatsApp\Repositories\UserRepository;

try {
    echo "🔍 Debug SimpleXML...\n";
    
    // Créer un utilisateur
    if (file_exists('data/debug_simple.xml')) {
        unlink('data/debug_simple.xml');
    }
    
    $xmlManager = new XMLManager('data/debug_simple.xml');
    $userRepo = new UserRepository($xmlManager);
    
    $user = new User('debug1', 'Debug User', 'debug@test.com');
    $userRepo->create($user);
    echo "✓ Utilisateur créé\n";
    
    // Test SimpleXML direct
    echo "\n🔍 Test SimpleXML direct...\n";
    $simpleXML = simplexml_load_file('data/debug_simple.xml');
    
    echo "✓ SimpleXML chargé\n";
    echo "✓ Root element: " . $simpleXML->getName() . "\n";
    
    // Afficher structure
    echo "✓ Namespaces: " . json_encode($simpleXML->getNamespaces(true)) . "\n";
    
    // Test avec namespace
    $namespaces = $simpleXML->getNamespaces(true);
    $defaultNS = '';
    foreach ($namespaces as $prefix => $uri) {
        if ($prefix === '') {
            $defaultNS = $uri;
            break;
        }
    }
    
    echo "✓ Default namespace: $defaultNS\n";
    
    // Accès avec namespace
    $users = $simpleXML->children($defaultNS)->users ?? null;
    if ($users) {
        echo "✓ Users node trouvé\n";
        $userNodes = $users->children($defaultNS);
        echo "✓ Nombre de children users: " . count($userNodes) . "\n";
        
        foreach ($userNodes as $userNode) {
            echo "✓ User node: " . $userNode->getName() . "\n";
            echo "   ID: " . (string)$userNode['id'] . "\n";
            echo "   Name: " . (string)$userNode->name . "\n";
        }
    } else {
        echo "❌ Users node non trouvé\n";
    }
    
    // Nettoyage
    unlink('data/debug_simple.xml');

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "❌ Trace: " . $e->getTraceAsString() . "\n";
} 