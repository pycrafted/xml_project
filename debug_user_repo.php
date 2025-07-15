<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';

use WhatsApp\Models\User;
use WhatsApp\Utils\XMLManager;
use WhatsApp\Repositories\UserRepository;

echo "🔍 Debug UserRepository...\n";

try {
    echo "✓ Autoload OK\n";

    $xmlManager = new XMLManager('data/debug_test.xml');
    echo "✓ XMLManager créé\n";

    $userRepo = new UserRepository($xmlManager);
    echo "✓ UserRepository créé\n";

    $user = new User('test1', 'Test User', 'test@example.com');
    echo "✓ User créé\n";

    // Test sans settings d'abord
    $user->setSettings([]);
    echo "✓ Settings vides configurés\n";

    echo "Tentative de création...\n";
    $result = $userRepo->create($user);
    echo "✓ Create result: " . ($result ? "true" : "false") . "\n";

} catch (Throwable $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "❌ File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "❌ Trace: " . $e->getTraceAsString() . "\n";
} 