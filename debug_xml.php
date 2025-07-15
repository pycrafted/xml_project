<?php

echo "🔍 Debug validation XML...\n\n";

// Activer les erreurs libxml
libxml_use_internal_errors(true);

$dom = new DOMDocument();
$dom->formatOutput = true;
$dom->load('data/sample_data.xml');

echo "✅ Original XML chargé\n";

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

echo "✅ Utilisateur ajouté avec namespace\n";

// Test de validation
if ($dom->schemaValidate('schemas/whatsapp_data.xsd')) {
    echo "✅ XML modifié valide!\n";
    $dom->save('data/test_output.xml');
    echo "✅ Sauvegardé dans test_output.xml\n";
} else {
    echo "❌ Erreurs de validation:\n";
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
        echo "   - " . trim($error->message) . "\n";
    }
    libxml_clear_errors();
} 