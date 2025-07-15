<?php
    // Map des credentials pour les tests
    $userCredentials = [
        'admin@whatsapp.com' => 'admin123',
        'demo@whatsapp.com' => 'demo123',
        'test@whatsapp.com' => 'test123',
        'alice@test.com' => 'password123',
        'bob@test.com' => 'password123',
        'charlie@test.com' => 'password123',
        'diana@test.com' => 'password123',
        'erik@test.com' => 'password123'
    ];
    

/**
 * LANCEMENT DES TESTS EXHAUSTIFS
 * 
 * Ce script teste 100% des fonctionnalités de l'application
 * pour garantir que tout fonctionne parfaitement
 */

echo "🧪 LANCEMENT DES TESTS EXHAUSTIFS\n";
echo "==================================\n\n";

// Vérifier que le serveur est en cours d'exécution
echo "🔍 Vérification du serveur...\n";
$serverUrl = 'http://localhost:8000';
$context = stream_context_create([
    'http' => [
        'timeout' => 2,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($serverUrl, false, $context);
if ($response === false) {
    echo "❌ ERREUR : Serveur web non disponible\n";
    echo "🚀 Lancez d'abord : php -S localhost:8000 -t public\n";
    exit(1);
}

echo "✅ Serveur disponible\n\n";

// Inclure et lancer les tests
require_once 'tests/ComprehensiveTest.php';

echo "🚀 Lancement des tests complets...\n";
echo "⏱️  Ceci peut prendre 2-3 minutes...\n\n";

// Lancer tous les tests
$tester = new ComprehensiveTest();
$tester->runAllTests();

echo "\n🎯 Tests terminés !\n";
echo "📊 Consultez les résultats ci-dessus\n";
echo "🔗 Application disponible : http://localhost:8000\n"; 