<?php

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;
use WhatsApp\Repositories\MessageRepository;

echo "=== DEBUG MessageRepository::getMessagesByUserId() MANQUANT ===\n\n";

try {
    $xmlManager = new XMLManager();
    $messageRepo = new MessageRepository($xmlManager);
    
    echo "1. Analyse des méthodes disponibles:\n";
    $methods = get_class_methods($messageRepo);
    echo "Méthodes: " . implode(', ', $methods) . "\n\n";
    
    // Test de la méthode manquante
    echo "2. Test getMessagesByUserId():\n";
    if (method_exists($messageRepo, 'getMessagesByUserId')) {
        echo "✅ getMessagesByUserId() existe\n";
    } else {
        echo "❌ getMessagesByUserId() manquant\n";
        echo "Méthodes similaires disponibles:\n";
        
        if (method_exists($messageRepo, 'findByUser')) {
            echo "✅ findByUser() existe - peut être utilisé comme alternative\n";
        }
        
        if (method_exists($messageRepo, 'findByGroup')) {
            echo "✅ findByGroup() existe\n";
        }
        
        if (method_exists($messageRepo, 'findConversation')) {
            echo "✅ findConversation() existe\n";
        }
    }
    
    echo "\n3. Test avec un utilisateur fictif:\n";
    try {
        if (method_exists($messageRepo, 'findByUser')) {
            $messages = $messageRepo->findByUser('user1');
            echo "✅ findByUser('user1') fonctionne - " . count($messages) . " messages trouvés\n";
        }
    } catch (Exception $e) {
        echo "❌ Erreur findByUser: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== SOLUTION PROPOSÉE ===\n";
    echo "Ajouter la méthode getMessagesByUserId() comme alias de findByUser()\n";
    echo "Également ajouter getMessagesBetweenUsers() et getGroupMessages() si nécessaires\n";
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 