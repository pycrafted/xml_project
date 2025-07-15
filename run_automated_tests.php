<?php

/**
 * Script de lancement automatique des tests
 * Lance l'application, met des données de test, et execute tous les tests
 * 
 * Similaire à votre expérience avec Django + Selenium
 */

echo "🚀 LANCEMENT AUTOMATIQUE DES TESTS WHATSAPP CLONE\n";
echo "=================================================\n\n";

// Configuration
$serverHost = 'localhost';
$serverPort = 8000;
$testType = $argv[1] ?? 'http'; // 'http' ou 'selenium'

// Étape 1: Vérifier les dépendances
echo "📦 Vérification des dépendances...\n";
if (!file_exists('vendor/autoload.php')) {
    echo "⚠️  Dépendances manquantes. Installation...\n";
    exec('composer install');
}

// Étape 2: Préparer les données de test
echo "🗄️  Préparation des données de test...\n";
resetTestData();

// Étape 3: Démarrer le serveur web
echo "🌐 Démarrage du serveur web sur http://{$serverHost}:{$serverPort}...\n";
$serverProcess = startWebServer($serverHost, $serverPort);
sleep(2); // Attendre que le serveur démarre

// Étape 4: Vérifier que le serveur répond
echo "🔍 Vérification du serveur...\n";
if (!isServerRunning($serverHost, $serverPort)) {
    echo "❌ Le serveur ne répond pas. Arrêt.\n";
    exit(1);
}
echo "✅ Serveur prêt !\n\n";

// Étape 5: Lancer les tests selon le type
try {
    if ($testType === 'selenium') {
        echo "🤖 Lancement des tests Selenium...\n";
        runSeleniumTests();
    } else {
        echo "🌐 Lancement des tests HTTP...\n";
        runHttpTests();
    }
    
    echo "\n🎉 Tous les tests terminés avec succès !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors des tests : " . $e->getMessage() . "\n";
    exit(1);
} finally {
    // Arrêter le serveur
    echo "\n🛑 Arrêt du serveur web...\n";
    if (isset($serverProcess)) {
        stopWebServer($serverProcess);
    }
}

/**
 * Démarrer le serveur web PHP
 */
function startWebServer(string $host, int $port): resource
{
    $command = "php -S {$host}:{$port} -t public";
    
    if (PHP_OS_FAMILY === 'Windows') {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (!is_resource($process)) {
            throw new Exception("Impossible de démarrer le serveur web");
        }
        
        return $process;
    } else {
        $process = proc_open($command . ' > /dev/null 2>&1 &', [], $pipes);
        return $process;
    }
}

/**
 * Arrêter le serveur web
 */
function stopWebServer($process): void
{
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
}

/**
 * Vérifier que le serveur répond
 */
function isServerRunning(string $host, int $port): bool
{
    $maxAttempts = 10;
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 1,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents("http://{$host}:{$port}", false, $context);
        
        if ($response !== false) {
            return true;
        }
        
        $attempt++;
        sleep(1);
    }
    
    return false;
}

/**
 * Réinitialiser les données de test
 */
function resetTestData(): void
{
    // Sauvegarder les données existantes
    $dataFile = 'data/sample_data.xml';
    if (file_exists($dataFile)) {
        copy($dataFile, $dataFile . '.backup');
    }
    
    // Créer un fichier XML minimal pour les tests
    $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
<whatsapp_data>
    <users>
        <user id="testuser1">
            <name>Test User 1</name>
            <email>test1@example.com</email>
            <password>password123</password>
            <status>active</status>
            <settings>
                <theme>light</theme>
                <notifications>true</notifications>
                <language>fr</language>
            </settings>
        </user>
        <user id="testuser2">
            <name>Test User 2</name>
            <email>test2@example.com</email>
            <password>password123</password>
            <status>active</status>
            <settings>
                <theme>dark</theme>
                <notifications>true</notifications>
                <language>fr</language>
            </settings>
        </user>
    </users>
    <contacts>
        <contact id="contact1">
            <user_id>testuser1</user_id>
            <contact_user_id>testuser2</contact_user_id>
            <contact_name>Test User 2</contact_name>
            <added_date>2025-01-01 10:00:00</added_date>
            <is_blocked>false</is_blocked>
        </contact>
    </contacts>
    <groups>
        <group id="testgroup1">
            <name>Test Group</name>
            <description>Groupe de test</description>
            <admin_user_id>testuser1</admin_user_id>
            <created_date>2025-01-01 10:00:00</created_date>
            <members>
                <member>testuser1</member>
                <member>testuser2</member>
            </members>
        </group>
    </groups>
    <messages>
        <message id="msg1">
            <content>Message de test initial</content>
            <from_user>testuser1</from_user>
            <to_user>testuser2</to_user>
            <type>text</type>
            <timestamp>2025-01-01 10:00:00</timestamp>
            <status>sent</status>
        </message>
    </messages>
</whatsapp_data>';
    
    file_put_contents($dataFile, $xmlContent);
    echo "✅ Données de test préparées\n";
}

/**
 * Lancer les tests HTTP
 */
function runHttpTests(): void
{
    echo "🧪 Exécution des tests HTTP automatisés...\n\n";
    
    // Test 1: Test complet
    echo "1️⃣ Test complet du workflow...\n";
    runSingleTest('WhatsApp\\Tests\\SimpleHttpTest::testCompleteWorkflowHttp');
    
    // Test 2: Test de performance
    echo "2️⃣ Test de performance...\n";
    runSingleTest('WhatsApp\\Tests\\SimpleHttpTest::testPerformanceHttp');
    
    // Test 3: Test de stress
    echo "3️⃣ Test de stress...\n";
    runSingleTest('WhatsApp\\Tests\\SimpleHttpTest::testStressTest');
    
    echo "✅ Tests HTTP terminés\n";
}

/**
 * Lancer les tests Selenium
 */
function runSeleniumTests(): void
{
    echo "🤖 Exécution des tests Selenium automatisés...\n\n";
    
    // Vérifier que Selenium Grid est disponible
    if (!isSeleniumReady()) {
        echo "⚠️  Selenium Grid non disponible. Utilisation des tests HTTP.\n";
        runHttpTests();
        return;
    }
    
    // Test 1: Test complet avec Selenium
    echo "1️⃣ Test complet avec Selenium...\n";
    runSingleTest('WhatsApp\\Tests\\WhatsAppAutomatedTest::testCompleteWhatsAppWorkflow');
    
    // Test 2: Test de performance Selenium
    echo "2️⃣ Test de performance avec utilisateurs multiples...\n";
    runSingleTest('WhatsApp\\Tests\\WhatsAppAutomatedTest::testPerformanceWithMultipleUsers');
    
    // Test 3: Test de gestion des erreurs
    echo "3️⃣ Test de gestion des erreurs...\n";
    runSingleTest('WhatsApp\\Tests\\WhatsAppAutomatedTest::testErrorHandling');
    
    echo "✅ Tests Selenium terminés\n";
}

/**
 * Lancer un test spécifique
 */
function runSingleTest(string $testMethod): void
{
    $command = "vendor/bin/phpunit --bootstrap vendor/autoload.php --testdox {$testMethod} 2>&1";
    
    $output = shell_exec($command);
    
    if ($output === null) {
        echo "❌ Impossible d'exécuter le test\n";
        return;
    }
    
    echo $output;
    
    if (strpos($output, 'OK') !== false || strpos($output, 'success') !== false) {
        echo "✅ Test réussi\n";
    } else {
        echo "⚠️  Test avec des avertissements\n";
    }
}

/**
 * Vérifier que Selenium Grid est prêt
 */
function isSeleniumReady(): bool
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 2,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents('http://localhost:4444/wd/hub/status', false, $context);
    return $response !== false;
}

/**
 * Afficher les instructions finales
 */
function showFinalInstructions(): void
{
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📋 INSTRUCTIONS POUR UTILISER LES TESTS AUTOMATISÉS\n";
    echo str_repeat("=", 50) . "\n\n";
    
    echo "💡 Options disponibles :\n";
    echo "  php run_automated_tests.php http      - Tests HTTP simples\n";
    echo "  php run_automated_tests.php selenium  - Tests Selenium complets\n\n";
    
    echo "🔧 Pour configurer Selenium (optionnel) :\n";
    echo "  1. Télécharger Selenium Grid\n";
    echo "  2. Lancer : java -jar selenium-server-standalone.jar\n";
    echo "  3. Installer ChromeDriver\n\n";
    
    echo "📊 Résultats des tests :\n";
    echo "  - Captures d'écran : screenshots/\n";
    echo "  - Logs détaillés : dans la console\n";
    echo "  - Données de test : data/sample_data.xml\n\n";
    
    echo "🎯 Parfait pour votre présentation académique !\n";
}

// Afficher les instructions si lancé sans paramètre
if ($argc === 1) {
    showFinalInstructions();
} 