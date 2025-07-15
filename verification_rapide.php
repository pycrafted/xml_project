<?php
/**
 * Script de vérification rapide - WhatsApp Clone
 * Détecte les erreurs courantes avant les tests
 */

echo "🔍 VÉRIFICATION RAPIDE DE L'APPLICATION\n";
echo "=====================================\n\n";

$errors = 0;
$warnings = 0;

// Test 1: Fichiers essentiels
echo "1. 📁 Vérification des fichiers essentiels...\n";
$requiredFiles = [
    'public/index.php',
    'public/dashboard.php', 
    'public/contacts.php',
    'public/groups.php',
    'public/chat.php',
    'public/profile.php',
    'public/ajax.php',
    'public/assets/css/style.css',
    'public/assets/js/app.js',
    'data/sample_data.xml',
    'schemas/whatsapp_data.xsd'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ $file - MANQUANT\n";
        $errors++;
    }
}

// Test 2: Serveur web
echo "\n2. 🌐 Test du serveur web...\n";
$serverUrl = 'http://localhost:8000';
$context = stream_context_create([
    'http' => [
        'timeout' => 3,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($serverUrl, false, $context);
if ($response !== false) {
    echo "   ✅ Serveur accessible sur $serverUrl\n";
    
    // Vérifier si c'est bien la page WhatsApp
    if (strpos($response, 'WhatsApp Web') !== false) {
        echo "   ✅ Page d'accueil WhatsApp détectée\n";
    } else {
        echo "   ⚠️ Page d'accueil non reconnue\n";
        $warnings++;
    }
} else {
    echo "   ❌ Serveur inaccessible - Relancez: php -S localhost:8000 -t public\n";
    $errors++;
}

// Test 3: Syntaxe PHP
echo "\n3. 🔧 Vérification de la syntaxe PHP...\n";
$phpFiles = ['public/index.php', 'public/dashboard.php', 'public/contacts.php', 'public/groups.php', 'public/chat.php', 'public/profile.php', 'public/ajax.php'];

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $output = [];
        $return = 0;
        exec("php -l $file 2>&1", $output, $return);
        
        if ($return === 0) {
            echo "   ✅ " . basename($file) . "\n";
        } else {
            echo "   ❌ " . basename($file) . " - ERREUR SYNTAXE\n";
            $errors++;
        }
    }
}

// Test 4: Permissions et données
echo "\n4. 📄 Vérification des données XML...\n";
$xmlFile = 'data/sample_data.xml';
if (file_exists($xmlFile)) {
    if (is_readable($xmlFile)) {
        echo "   ✅ Fichier XML lisible\n";
    } else {
        echo "   ❌ Fichier XML non lisible - Problème de permissions\n";
        $errors++;
    }
    
    if (is_writable($xmlFile)) {
        echo "   ✅ Fichier XML modifiable\n";
    } else {
        echo "   ⚠️ Fichier XML non modifiable - Données temporaires\n";
        $warnings++;
    }
} else {
    echo "   ❌ Fichier XML manquant\n";
    $errors++;
}

// Test 5: Structure XML
echo "\n5. 📋 Validation de la structure XML...\n";
if (file_exists($xmlFile)) {
    $xml = @simplexml_load_file($xmlFile);
    if ($xml !== false) {
        echo "   ✅ Structure XML valide\n";
        
        // Compter les éléments
        $users = $xml->users->user ?? [];
        $messages = $xml->messages->message ?? [];
        $contacts = $xml->contacts->contact ?? [];
        $groups = $xml->groups->group ?? [];
        
        echo "   📊 Statistiques actuelles:\n";
        echo "      • Utilisateurs: " . count($users) . "\n";
        echo "      • Messages: " . count($messages) . "\n";
        echo "      • Contacts: " . count($contacts) . "\n";
        echo "      • Groupes: " . count($groups) . "\n";
        
    } else {
        echo "   ❌ Structure XML invalide\n";
        $errors++;
    }
}

// Test 6: Autoload Composer
echo "\n6. 🎵 Vérification de l'autoload Composer...\n";
if (file_exists('vendor/autoload.php')) {
    echo "   ✅ Autoload Composer présent\n";
    
    // Test des classes principales
    require_once 'vendor/autoload.php';
    
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
            echo "   ✅ " . basename(str_replace('\\', '/', $class)) . "\n";
        } else {
            echo "   ❌ " . basename(str_replace('\\', '/', $class)) . " - NON TROUVÉE\n";
            $errors++;
        }
    }
    
} else {
    echo "   ❌ Autoload Composer manquant - Lancez: composer install\n";
    $errors++;
}

// RÉSULTATS
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RÉSULTATS DE LA VÉRIFICATION\n";
echo str_repeat("=", 50) . "\n";

if ($errors === 0 && $warnings === 0) {
    echo "🎉 TOUT EST PARFAIT !\n";
    echo "✅ Application prête pour les tests\n";
    echo "🌐 Ouvrez http://localhost:8000 dans votre navigateur\n";
    echo "📋 Suivez le guide GUIDE_TEST_APPLICATION.md\n";
} elseif ($errors === 0) {
    echo "⚠️ QUELQUES AVERTISSEMENTS\n";
    echo "✅ Application fonctionnelle malgré $warnings avertissement(s)\n";
    echo "🌐 Vous pouvez commencer les tests\n";
} else {
    echo "❌ ERREURS DÉTECTÉES\n";
    echo "🔧 Corrigez les $errors erreur(s) avant de continuer\n";
    echo "📧 Contactez l'aide si nécessaire\n";
}

echo "\n💡 PROCHAINES ÉTAPES:\n";
echo "1. Ouvrez votre navigateur\n";
echo "2. Allez sur http://localhost:8000\n";
echo "3. Suivez le guide étape par étape\n";
echo "4. Cochez chaque test dans la checklist\n";

echo "\n🎓 BONNE CHANCE POUR VOS TESTS !\n";
?> 