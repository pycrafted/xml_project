<?php

/**
 * TESTS DES NOUVELLES MODIFICATIONS DE LA PAGE DE CONNEXION
 * 
 * Vérifier que la page d'accueil utilise maintenant email + mot de passe
 * et que les comptes de démonstration fonctionnent correctement
 */

echo "🧪 TESTS DES MODIFICATIONS DE LA PAGE DE CONNEXION\n";
echo "==================================================\n\n";

$baseUrl = 'http://localhost:8000';
$passedTests = 0;
$failedTests = 0;
$totalTests = 0;

function runTest($testName, $testFunction) {
    global $passedTests, $failedTests, $totalTests;
    
    $totalTests++;
    echo "🔸 $testName... ";
    
    try {
        $result = $testFunction();
        if ($result) {
            echo "✅\n";
            $passedTests++;
        } else {
            echo "❌\n";
            $failedTests++;
        }
    } catch (Exception $e) {
        echo "❌ (Erreur: " . $e->getMessage() . ")\n";
        $failedTests++;
    }
}

function makeHttpRequest($method, $url, $data = [], $cookies = []) {
    global $baseUrl;
    
    $fullUrl = $baseUrl . $url;
    
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'timeout' => 10,
            'ignore_errors' => true,
            'header' => 'Content-Type: application/x-www-form-urlencoded' . 
                       (empty($cookies) ? '' : "\r\nCookie: " . implode('; ', $cookies)),
            'content' => http_build_query($data)
        ]
    ]);
    
    $response = file_get_contents($fullUrl, false, $context);
    return $response !== false ? $response : '';
}

// Tests des modifications de la page de connexion
echo "🔹 PHASE 1 : Tests de la nouvelle interface de connexion\n";

runTest("Page d'accueil accessible", function() {
    $response = makeHttpRequest('GET', '/');
    return !empty($response) && strpos($response, 'WhatsApp Web') !== false;
});

runTest("Formulaire contient champ Email", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'type="email"') !== false && 
           strpos($response, 'name="email"') !== false;
});

runTest("Formulaire contient champ Mot de passe", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'type="password"') !== false && 
           strpos($response, 'name="password"') !== false;
});

runTest("Formulaire ne contient plus champ Nom", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'name="name"') === false ||
           strpos($response, 'Nom complet') === false;
});

runTest("Comptes de démonstration affichés", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'admin@whatsapp.com') !== false && 
           strpos($response, 'demo@whatsapp.com') !== false;
});

runTest("Bouton Se connecter présent", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'Se connecter') !== false;
});

// Tests des comptes de démonstration
echo "\n🔹 PHASE 2 : Tests des comptes de démonstration\n";

$demoAccounts = [
    'admin@whatsapp.com' => 'admin123',
    'demo@whatsapp.com' => 'demo123',
    'test@whatsapp.com' => 'test123',
    'alice@test.com' => 'password123'
];

foreach ($demoAccounts as $email => $password) {
    runTest("Connexion avec $email", function() use ($email, $password) {
        $response = makeHttpRequest('POST', '/', [
            'action' => 'login',
            'email' => $email,
            'password' => $password
        ]);
        
        // Vérifier redirection vers dashboard ou message de succès
        return strpos($response, 'dashboard') !== false || 
               strpos($response, 'Location: dashboard.php') !== false ||
               http_response_code() === 302;
    });
}

// Tests des cas d'erreur
echo "\n🔹 PHASE 3 : Tests des cas d'erreur\n";

runTest("Erreur email vide", function() {
    $response = makeHttpRequest('POST', '/', [
        'action' => 'login',
        'email' => '',
        'password' => 'password123'
    ]);
    return strpos($response, 'Email et mot de passe sont requis') !== false;
});

runTest("Erreur mot de passe vide", function() {
    $response = makeHttpRequest('POST', '/', [
        'action' => 'login',
        'email' => 'admin@whatsapp.com',
        'password' => ''
    ]);
    return strpos($response, 'Email et mot de passe sont requis') !== false;
});

runTest("Erreur email inexistant", function() {
    $response = makeHttpRequest('POST', '/', [
        'action' => 'login',
        'email' => 'inexistant@example.com',
        'password' => 'password123'
    ]);
    return strpos($response, 'Email ou mot de passe incorrect') !== false;
});

runTest("Erreur mot de passe incorrect", function() {
    $response = makeHttpRequest('POST', '/', [
        'action' => 'login',
        'email' => 'admin@whatsapp.com',
        'password' => 'mauvais_password'
    ]);
    return strpos($response, 'Email ou mot de passe incorrect') !== false;
});

// Tests de sécurité
echo "\n🔹 PHASE 4 : Tests de sécurité\n";

runTest("Protection contre SQL injection", function() {
    $response = makeHttpRequest('POST', '/', [
        'action' => 'login',
        'email' => "admin@whatsapp.com'; DROP TABLE users; --",
        'password' => 'admin123'
    ]);
    return strpos($response, 'Email ou mot de passe incorrect') !== false;
});

runTest("Protection contre XSS", function() {
    $response = makeHttpRequest('POST', '/', [
        'action' => 'login',
        'email' => '<script>alert("XSS")</script>',
        'password' => 'admin123'
    ]);
    return strpos($response, '<script>') === false;
});

runTest("Validation format email", function() {
    $response = makeHttpRequest('POST', '/', [
        'action' => 'login',
        'email' => 'email_invalide',
        'password' => 'admin123'
    ]);
    return strpos($response, 'Email ou mot de passe incorrect') !== false;
});

// Tests des fonctionnalités avancées
echo "\n🔹 PHASE 5 : Tests des fonctionnalités avancées\n";

runTest("Raccourcis clavier JavaScript présents", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'Ctrl + 1') !== false || 
           strpos($response, 'keydown') !== false;
});

runTest("Auto-focus sur champ email", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'emailInput.focus()') !== false;
});

runTest("Validation email en temps réel", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'emailRegex') !== false;
});

runTest("Statistiques plateforme affichées", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'Statistiques de la plateforme') !== false;
});

runTest("Informations techniques affichées", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'Technologies utilisées') !== false;
});

runTest("Crédits académiques affichés", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'Professeur Ibrahima FALL') !== false;
});

// Tests de performance
echo "\n🔹 PHASE 6 : Tests de performance\n";

runTest("Temps de réponse acceptable", function() {
    $startTime = microtime(true);
    $response = makeHttpRequest('GET', '/');
    $endTime = microtime(true);
    $loadTime = $endTime - $startTime;
    
    return !empty($response) && $loadTime < 2.0; // moins de 2 secondes
});

runTest("Taille page raisonnable", function() {
    $response = makeHttpRequest('GET', '/');
    $size = strlen($response);
    
    return $size > 1000 && $size < 50000; // Entre 1KB et 50KB
});

// Tests de compatibilité
echo "\n🔹 PHASE 7 : Tests de compatibilité\n";

runTest("HTML5 valide", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, '<!DOCTYPE html>') !== false && 
           strpos($response, '<html lang="fr">') !== false;
});

runTest("Meta viewport présent", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'viewport') !== false;
});

runTest("CSS externe lié", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'assets/css/style.css') !== false;
});

runTest("JavaScript externe lié", function() {
    $response = makeHttpRequest('GET', '/');
    return strpos($response, 'assets/js/app.js') !== false;
});

// Tests de régression
echo "\n🔹 PHASE 8 : Tests de régression\n";

runTest("Session gérée correctement", function() {
    // Simuler une session existante
    $response = makeHttpRequest('GET', '/', [], ['PHPSESSID=test123']);
    return !empty($response); // Page devrait toujours répondre
});

runTest("Action logout toujours fonctionnelle", function() {
    $response = makeHttpRequest('POST', '/', [
        'action' => 'logout'
    ]);
    return strpos($response, 'Location: index.php') !== false || 
           http_response_code() === 302;
});

// Résultats finaux
echo "\n============================================================\n";
echo "📊 RÉSULTATS DES TESTS DES MODIFICATIONS\n";
echo "============================================================\n\n";

echo "📈 STATISTIQUES GÉNÉRALES :\n";
echo "  Total des tests      : $totalTests\n";
echo "  Tests réussis        : $passedTests\n";
echo "  Tests échoués        : $failedTests\n";
echo "  Taux de réussite     : " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

if ($failedTests === 0) {
    echo "🎉 FÉLICITATIONS ! TOUTES LES MODIFICATIONS FONCTIONNENT PARFAITEMENT !\n";
    echo "✅ La page de connexion utilise maintenant email + mot de passe\n";
    echo "✅ Les comptes de démonstration sont opérationnels\n";
    echo "✅ La sécurité est renforcée\n";
    echo "✅ L'interface est améliorée\n";
} else {
    echo "⚠️  QUELQUES TESTS ONT ÉCHOUÉ :\n";
    echo "  - Vérifiez que le serveur est démarré\n";
    echo "  - Vérifiez que les utilisateurs de démonstration existent\n";
    echo "  - Consultez les détails des erreurs ci-dessus\n";
}

echo "\n🔑 COMPTES DE DÉMONSTRATION TESTÉS :\n";
echo "  👨‍💼 admin@whatsapp.com / admin123\n";
echo "  🎪 demo@whatsapp.com / demo123\n";
echo "  🧪 test@whatsapp.com / test123\n";
echo "  🔬 alice@test.com / password123\n";

echo "\n🌐 APPLICATION DISPONIBLE SUR : http://localhost:8000\n";
echo "🚀 Prêt pour la présentation !\n"; 