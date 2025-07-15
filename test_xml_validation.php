<?php

/**
 * Script de test pour la validation XML avec XSD
 */

require_once 'vendor/autoload.php';

echo "🔍 Test de validation XML/XSD...\n\n";

try {
    // Test 1: Validation XSD
    echo "✅ Test 1: Validation du schéma XSD\n";
    
    $dom = new DOMDocument();
    $dom->load('data/sample_data.xml');
    
    if ($dom->schemaValidate('schemas/whatsapp_data.xsd')) {
        echo "   ✓ XML valide selon le XSD\n";
    } else {
        echo "   ❌ XML invalide selon le XSD\n";
        throw new Exception("Validation XSD échouée");
    }

    // Test 2: Lecture des données avec SimpleXML
    echo "\n✅ Test 2: Lecture avec SimpleXML\n";
    $xml = simplexml_load_file('data/sample_data.xml');
    
    if ($xml) {
        echo "   ✓ Fichier XML chargé avec succès\n";
        echo "   ✓ Nombre d'utilisateurs: " . count($xml->users->user) . "\n";
        echo "   ✓ Nombre de messages: " . count($xml->messages->message) . "\n";
        
        // Affichage d'un utilisateur
        $user = $xml->users->user[0];
        echo "   ✓ Premier utilisateur: " . $user->name . " (ID: " . $user['id'] . ")\n";
    } else {
        throw new Exception("Impossible de charger le XML");
    }

    // Test 3: Modification et sauvegarde
    echo "\n✅ Test 3: Modification XML\n";
    $dom = new DOMDocument();
    $dom->formatOutput = true;
    $dom->load('data/sample_data.xml');
    
    // Créer un nouvel utilisateur AVEC namespace
    $usersNode = $dom->getElementsByTagName('users')->item(0);
    $newUser = $dom->createElementNS('http://whatsapp.clone/data', 'user');
    $newUser->setAttribute('id', 'user3');
    
    $name = $dom->createElementNS('http://whatsapp.clone/data', 'name', 'Test User');
    $email = $dom->createElementNS('http://whatsapp.clone/data', 'email', 'test@example.com');
    $status = $dom->createElementNS('http://whatsapp.clone/data', 'status', 'active');
    $settings = $dom->createElementNS('http://whatsapp.clone/data', 'settings');
    
    $newUser->appendChild($name);
    $newUser->appendChild($email);
    $newUser->appendChild($status);
    $newUser->appendChild($settings);
    
    $usersNode->appendChild($newUser);
    
    // Validation après modification
    if ($dom->schemaValidate('schemas/whatsapp_data.xsd')) {
        echo "   ✓ XML modifié reste valide\n";
        
        // Sauvegarde test
        if ($dom->save('data/test_output.xml')) {
            echo "   ✓ Sauvegarde réussie\n";
        } else {
            throw new Exception("Erreur de sauvegarde");
        }
    } else {
        throw new Exception("XML modifié invalide");
    }

    echo "\n🎯 Validation XML/XSD: OK\n";

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
} 