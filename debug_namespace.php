<?php

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;

try {
    echo "🔍 Debug namespace...\n";
    
    $xmlManager = new XMLManager('data/debug_ns.xml');
    echo "✓ XMLManager créé\n";
    
    // Test avec settings vides
    $userData1 = [
        'attributes' => ['id' => 'user1'],
        'name' => 'Test User',
        'email' => 'test@example.com',
        'status' => 'active',
        'settings' => []
    ];
    
    echo "Tentative ajout utilisateur sans settings...\n";
    $result1 = $xmlManager->addElement('//wa:users', 'user', $userData1);
    echo "✓ Résultat 1: " . ($result1 ? "OK" : "ERREUR") . "\n";
    
    // Test avec settings simples
    $userData2 = [
        'attributes' => ['id' => 'user2'],
        'name' => 'Test User 2',
        'email' => 'test2@example.com',
        'status' => 'active',
        'settings' => [
            'setting' => [
                ['attributes' => ['key' => 'theme', 'value' => 'dark']]
            ]
        ]
    ];
    
    echo "Tentative ajout utilisateur avec settings...\n";
    $result2 = $xmlManager->addElement('//wa:users', 'user', $userData2);
    echo "✓ Résultat 2: " . ($result2 ? "OK" : "ERREUR") . "\n";
    
    // Nettoyage
    if (file_exists('data/debug_ns.xml')) {
        unlink('data/debug_ns.xml');
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "❌ Trace: " . $e->getTraceAsString() . "\n";
} 