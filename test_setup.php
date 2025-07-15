<?php

/**
 * Script de test pour vérifier la configuration de base
 */

require_once 'vendor/autoload.php';

echo "🔍 Test de la configuration de base...\n\n";

try {
    // Test 1: Autoloading
    echo "✅ Test 1: Autoloading Composer\n";
    $user = new \WhatsApp\Models\User('user1', 'John Doe', 'john@example.com');
    echo "   ✓ Classe User créée avec succès\n";
    echo "   ✓ ID: " . $user->getId() . "\n";
    echo "   ✓ Nom: " . $user->getName() . "\n\n";

    // Test 2: Extension XML activée
    echo "✅ Test 2: Extensions XML PHP\n";
    if (extension_loaded('simplexml')) {
        echo "   ✓ SimpleXML disponible\n";
    } else {
        echo "   ❌ SimpleXML manquant\n";
    }
    
    if (extension_loaded('dom')) {
        echo "   ✓ DOM disponible\n";
    } else {
        echo "   ❌ DOM manquant\n";
    }

    if (extension_loaded('libxml')) {
        echo "   ✓ LibXML disponible\n";
    } else {
        echo "   ❌ LibXML manquant\n";
    }

    echo "\n🎯 Configuration de base: OK\n";

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
} 