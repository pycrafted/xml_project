<?php

/**
 * SCRIPT DE DÉMARRAGE AUTOMATIQUE
 * 
 * Lance le serveur et crée automatiquement les utilisateurs par défaut
 */

echo "🚀 DÉMARRAGE AUTOMATIQUE DE L'APPLICATION\n";
echo "==========================================\n\n";

// 1. Vérifier que le serveur n'est pas déjà en cours d'exécution
echo "🔍 Vérification du serveur...\n";
$serverUrl = 'http://localhost:8000';
$context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
$response = @file_get_contents($serverUrl, false, $context);

if ($response !== false) {
    echo "✅ Serveur déjà en cours d'exécution\n";
} else {
    echo "🔄 Démarrage du serveur...\n";
    echo "📝 Commande: php -S localhost:8000 -t public\n";
    echo "⚠️  Le serveur démarrera en arrière-plan\n";
    echo "🌐 Application disponible sur: http://localhost:8000\n\n";
    
    // Démarrer le serveur en arrière-plan (Windows)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        pclose(popen("start /B php -S localhost:8000 -t public", "r"));
    } else {
        // Linux/Mac
        shell_exec('php -S localhost:8000 -t public > /dev/null 2>&1 &');
    }
    
    // Attendre que le serveur démarre
    echo "⏳ Attente du démarrage du serveur...\n";
    sleep(3);
    
    // Vérifier que le serveur a démarré
    $response = @file_get_contents($serverUrl, false, $context);
    if ($response !== false) {
        echo "✅ Serveur démarré avec succès\n";
    } else {
        echo "❌ Erreur lors du démarrage du serveur\n";
        echo "🔧 Lancez manuellement: php -S localhost:8000 -t public\n";
        exit(1);
    }
}

// 2. Créer automatiquement les utilisateurs par défaut
echo "\n👤 Création des utilisateurs par défaut...\n";

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;
use WhatsApp\Services\UserService;

try {
    $xmlManager = new XMLManager();
    $userService = new UserService($xmlManager);
    
    $defaultUsers = [
        ['admin', 'Administrateur', 'admin@whatsapp.com', 'admin123'],
        ['demo', 'Utilisateur Demo', 'demo@whatsapp.com', 'demo123'],
        ['test', 'Test User', 'test@whatsapp.com', 'test123'],
        ['alice2025', 'Alice Martin', 'alice@test.com', 'password123'],
        ['bob2025', 'Bob Durand', 'bob@test.com', 'password123'],
        ['charlie2025', 'Charlie Dupont', 'charlie@test.com', 'password123'],
        ['diana2025', 'Diana Lemoine', 'diana@test.com', 'password123'],
        ['erik2025', 'Erik Rousseau', 'erik@test.com', 'password123']
    ];
    
    foreach ($defaultUsers as [$id, $name, $email, $password]) {
        try {
            $userService->createUser($id, $name, $email);
            echo "✅ $name ($email)\n";
        } catch (Exception $e) {
            echo "⚠️  $name existe déjà\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la création des utilisateurs: " . $e->getMessage() . "\n";
}

// 3. Afficher les informations de connexion
echo "\n🎯 APPLICATION PRÊTE À UTILISER !\n";
echo "=================================\n";
echo "🌐 URL: http://localhost:8000\n\n";

echo "🔑 COMPTES DISPONIBLES :\n";
echo "------------------------\n";
echo "👨‍💼 admin@whatsapp.com → admin123\n";
echo "🎪 demo@whatsapp.com → demo123\n";
echo "🧪 test@whatsapp.com → test123\n";
echo "🔬 alice@test.com → password123\n";
echo "🔬 bob@test.com → password123\n";
echo "🔬 charlie@test.com → password123\n";
echo "🔬 diana@test.com → password123\n";
echo "🔬 erik@test.com → password123\n";

echo "\n💡 UTILISATION :\n";
echo "----------------\n";
echo "1. Ouvrez votre navigateur\n";
echo "2. Allez sur: http://localhost:8000\n";
echo "3. Connectez-vous avec un des comptes ci-dessus\n";
echo "4. Profitez de votre WhatsApp Clone !\n";

echo "\n🧪 TESTS AUTOMATISÉS :\n";
echo "----------------------\n";
echo "• php run_comprehensive_tests.php\n";
echo "• php test_messaging_complete.php\n";
echo "• php demo_simple.php\n";

echo "\n🎉 PRÊT À PRÉSENTER VOTRE PROJET !\n";
echo "===================================\n";
echo "📅 Échéance : 16 juillet 2025\n";
echo "🎓 Projet pour Prof. Ibrahima FALL\n";
echo "🏫 UCAD/DGI/ESP - Master\n";
echo "💻 WhatsApp Clone en PHP avec XML\n"; 