<?php

/**
 * Test complet de la classe XMLManager
 */

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;

echo "🔍 Test XMLManager...\n\n";

try {
    // Test 1: Création et initialisation
    echo "✅ Test 1: Création XMLManager\n";
    $xmlManager = new XMLManager('data/test_manager.xml');
    echo "   ✓ XMLManager créé\n";
    
    // Vérifier qu'il a créé le fichier XML vide
    if (file_exists('data/test_manager.xml')) {
        echo "   ✓ Fichier XML de test créé automatiquement\n";
    }

    // Test 2: Chargement et validation
    echo "\n✅ Test 2: Chargement et validation\n";
    $xmlManager->load();
    echo "   ✓ XML chargé et validé\n";

    // Test 3: Ajout d'un utilisateur
    echo "\n✅ Test 3: Ajout d'un utilisateur\n";
    $userData = [
        'attributes' => ['id' => 'test_user1'],
        'name' => 'Test User',
        'email' => 'test@example.com',
        'status' => 'active',
        'settings' => []
    ];
    
    $xmlManager->addElement('//wa:users', 'user', $userData);
    echo "   ✓ Utilisateur ajouté avec succès\n";

    // Test 4: Recherche par ID
    echo "\n✅ Test 4: Recherche par ID\n";
    $userElement = $xmlManager->findElementById('user', 'test_user1');
    if ($userElement) {
        echo "   ✓ Utilisateur trouvé : " . $userElement->getElementsByTagName('name')->item(0)->textContent . "\n";
    } else {
        throw new Exception("Utilisateur non trouvé");
    }

    // Test 5: Ajout d'un message
    echo "\n✅ Test 5: Ajout d'un message\n";
    $messageData = [
        'attributes' => ['id' => 'test_msg1'],
        'content' => 'Hello World!',
        'type' => 'text',
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'sent',
        'from_user' => 'test_user1',
        'to_user' => 'test_user2'
    ];
    
    $xmlManager->addElement('//wa:messages', 'message', $messageData);
    echo "   ✓ Message ajouté avec succès\n";

    // Test 6: Lecture avec SimpleXML
    echo "\n✅ Test 6: Lecture SimpleXML\n";
    $simpleXML = $xmlManager->getSimpleXML();
    $userCount = count($simpleXML->users->user);
    $messageCount = count($simpleXML->messages->message);
    echo "   ✓ Utilisateurs: {$userCount}, Messages: {$messageCount}\n";

    // Test 7: Suppression
    echo "\n✅ Test 7: Suppression d'élément\n";
    if ($xmlManager->deleteElementById('user', 'test_user1')) {
        echo "   ✓ Utilisateur supprimé\n";
    } else {
        throw new Exception("Erreur de suppression");
    }

    // Vérifier la suppression
    $deletedUser = $xmlManager->findElementById('user', 'test_user1');
    if (!$deletedUser) {
        echo "   ✓ Suppression confirmée\n";
    } else {
        throw new Exception("Utilisateur toujours présent");
    }

    echo "\n🎯 XMLManager: TOUS LES TESTS OK!\n";

    // Nettoyage
    if (file_exists('data/test_manager.xml')) {
        unlink('data/test_manager.xml');
        echo "   ✓ Fichier de test nettoyé\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
} 