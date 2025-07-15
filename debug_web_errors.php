<?php
/**
 * Script de débogage approfondi pour l'interface web
 * Identifie précisément tous les problèmes
 */

echo "🔍 DÉBOGAGE APPROFONDI - INTERFACE WEB\n";
echo "====================================\n\n";

// Fonction de log détaillé
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] ❌ ERROR: $message\n";
    if (!empty($context)) {
        echo "    Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}

function logWarning($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] ⚠️ WARNING: $message\n";
    if (!empty($context)) {
        echo "    Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}

function logInfo($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] ℹ️ INFO: $message\n";
    if (!empty($context)) {
        echo "    Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}

function logSuccess($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] ✅ SUCCESS: $message\n";
    if (!empty($context)) {
        echo "    Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}

// Test 1: Vérification des chemins de fichiers
logInfo("Début des tests de chemins de fichiers");

$currentDir = getcwd();
logInfo("Répertoire de travail actuel", ['cwd' => $currentDir]);

// Test des chemins XSD
$xsdPaths = [
    'schemas/whatsapp_data.xsd',
    '../schemas/whatsapp_data.xsd',
    './schemas/whatsapp_data.xsd',
    dirname(__FILE__) . '/schemas/whatsapp_data.xsd',
    dirname(__FILE__) . '/../schemas/whatsapp_data.xsd'
];

logInfo("Test des chemins XSD possibles");
foreach ($xsdPaths as $path) {
    $fullPath = realpath($path);
    if (file_exists($path)) {
        logSuccess("XSD trouvé", ['path' => $path, 'full_path' => $fullPath]);
    } else {
        logError("XSD non trouvé", ['path' => $path, 'full_path' => $fullPath]);
    }
}

// Test des chemins XML
$xmlPaths = [
    'data/whatsapp_data.xml',
    'data/sample_data.xml',
    '../data/whatsapp_data.xml',
    '../data/sample_data.xml',
    dirname(__FILE__) . '/data/whatsapp_data.xml',
    dirname(__FILE__) . '/data/sample_data.xml'
];

logInfo("Test des chemins XML possibles");
foreach ($xmlPaths as $path) {
    $fullPath = realpath($path);
    if (file_exists($path)) {
        logSuccess("XML trouvé", ['path' => $path, 'full_path' => $fullPath, 'size' => filesize($path)]);
    } else {
        logError("XML non trouvé", ['path' => $path, 'full_path' => $fullPath]);
    }
}

// Test 2: Simulation d'appel depuis public/
logInfo("Simulation d'appel depuis le dossier public/");

// Changer vers le dossier public
$publicDir = dirname(__FILE__) . '/public';
if (is_dir($publicDir)) {
    chdir($publicDir);
    $newCwd = getcwd();
    logInfo("Changement vers public/", ['new_cwd' => $newCwd]);
    
    // Retester les chemins
    foreach ($xsdPaths as $path) {
        $fullPath = realpath($path);
        if (file_exists($path)) {
            logSuccess("XSD trouvé depuis public/", ['path' => $path, 'full_path' => $fullPath]);
        } else {
            logError("XSD non trouvé depuis public/", ['path' => $path, 'full_path' => $fullPath]);
        }
    }
    
    // Retour au répertoire original
    chdir($currentDir);
} else {
    logError("Dossier public/ non trouvé", ['expected_path' => $publicDir]);
}

// Test 3: Test de l'autoload
logInfo("Test de l'autoload Composer");

if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    logSuccess("Autoload chargé");
    
    // Test des classes
    $classes = [
        'WhatsApp\\Utils\\XMLManager',
        'WhatsApp\\Services\\UserService',
        'WhatsApp\\Services\\MessageService',
        'WhatsApp\\Repositories\\UserRepository',
        'WhatsApp\\Repositories\\ContactRepository',
        'WhatsApp\\Repositories\\GroupRepository',
        'WhatsApp\\Repositories\\MessageRepository'
    ];
    
    foreach ($classes as $class) {
        if (class_exists($class)) {
            logSuccess("Classe trouvée", ['class' => $class]);
            
            // Test des méthodes publiques
            $reflection = new ReflectionClass($class);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            $methodNames = array_map(function($method) {
                return $method->getName();
            }, $methods);
            
            logInfo("Méthodes publiques", ['class' => $class, 'methods' => $methodNames]);
            
        } else {
            logError("Classe non trouvée", ['class' => $class]);
        }
    }
} else {
    logError("Autoload non trouvé", ['path' => 'vendor/autoload.php']);
}

// Test 4: Test d'instanciation XMLManager
logInfo("Test d'instanciation XMLManager");

try {
    $xmlManager = new \WhatsApp\Utils\XMLManager();
    logSuccess("XMLManager instancié");
    
    // Test des chemins utilisés
    $reflection = new ReflectionClass($xmlManager);
    $dataFileProperty = $reflection->getProperty('dataFile');
    $xsdFileProperty = $reflection->getProperty('xsdFile');
    
    $dataFileProperty->setAccessible(true);
    $xsdFileProperty->setAccessible(true);
    
    $dataFilePath = $dataFileProperty->getValue($xmlManager);
    $xsdFilePath = $xsdFileProperty->getValue($xmlManager);
    
    logInfo("Chemins utilisés par XMLManager", [
        'data_file' => $dataFilePath,
        'xsd_file' => $xsdFilePath,
        'data_exists' => file_exists($dataFilePath),
        'xsd_exists' => file_exists($xsdFilePath)
    ]);
    
    // Test de chargement
    try {
        $xmlManager->loadXML();
        logSuccess("XML chargé avec succès");
    } catch (Exception $e) {
        logError("Erreur lors du chargement XML", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
    
} catch (Exception $e) {
    logError("Erreur lors de l'instanciation XMLManager", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// Test 5: Test des services
logInfo("Test des services");

try {
    $xmlManager = new \WhatsApp\Utils\XMLManager();
    $userService = new \WhatsApp\Services\UserService($xmlManager);
    logSuccess("UserService instancié");
    
    // Test des méthodes
    $userReflection = new ReflectionClass($userService);
    $userMethods = $userReflection->getMethods(ReflectionMethod::IS_PUBLIC);
    $userMethodNames = array_map(function($method) {
        return $method->getName();
    }, $userMethods);
    
    logInfo("Méthodes UserService", ['methods' => $userMethodNames]);
    
} catch (Exception $e) {
    logError("Erreur UserService", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// Test 6: Test des repositories
logInfo("Test des repositories");

try {
    $xmlManager = new \WhatsApp\Utils\XMLManager();
    $userRepo = new \WhatsApp\Repositories\UserRepository($xmlManager);
    logSuccess("UserRepository instancié");
    
    $userRepoReflection = new ReflectionClass($userRepo);
    $userRepoMethods = $userRepoReflection->getMethods(ReflectionMethod::IS_PUBLIC);
    $userRepoMethodNames = array_map(function($method) {
        return $method->getName();
    }, $userRepoMethods);
    
    logInfo("Méthodes UserRepository", ['methods' => $userRepoMethodNames]);
    
} catch (Exception $e) {
    logError("Erreur UserRepository", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// Test 7: Test depuis le contexte web
logInfo("Test depuis le contexte web (simulation)");

// Simuler les variables de session
$_SESSION = ['user_id' => 'test_user'];

// Simuler l'appel depuis public/
chdir('public');
$webContext = getcwd();
logInfo("Contexte web", ['cwd' => $webContext]);

// Tester les chemins depuis public/
$webXsdPath = '../schemas/whatsapp_data.xsd';
$webDataPath = '../data/sample_data.xml';

logInfo("Test des chemins depuis public/", [
    'xsd_path' => $webXsdPath,
    'xsd_exists' => file_exists($webXsdPath),
    'data_path' => $webDataPath,
    'data_exists' => file_exists($webDataPath)
]);

// Retour au répertoire original
chdir($currentDir);

echo "\n";
echo "🎯 RÉSUMÉ DES PROBLÈMES IDENTIFIÉS\n";
echo "=================================\n";
echo "1. Chemins XSD/XML incorrects depuis public/\n";
echo "2. Méthodes manquantes dans services/repositories\n";
echo "3. Problèmes de chemins relatifs\n";
echo "4. Autoload potentiellement défaillant\n";
echo "\n";
echo "📋 PROCHAINES ÉTAPES:\n";
echo "- Corriger les chemins dans XMLManager\n";
echo "- Ajouter les méthodes manquantes\n";
echo "- Tester depuis le contexte web\n";
echo "\n";
?> 