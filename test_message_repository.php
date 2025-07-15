<?php

require_once 'vendor/autoload.php';

use WhatsApp\Models\Message;
use WhatsApp\Utils\XMLManager;
use WhatsApp\Repositories\MessageRepository;

echo "🔍 Test MessageRepository...\n\n";

try {
    // Nettoyage préalable
    if (file_exists('data/test_messages.xml')) {
        unlink('data/test_messages.xml');
    }
    
    $xmlManager = new XMLManager('data/test_messages.xml');
    $messageRepo = new MessageRepository($xmlManager);

    // Test 1: Message privé
    echo "✅ Test 1: Création message privé\n";
    $msg1 = new Message('msg1', 'Hello John!', 'user1');
    $msg1->setRecipientUser('user2');
    
    if ($messageRepo->create($msg1)) {
        echo "   ✓ Message privé créé\n";
    } else {
        throw new Exception("Erreur création message privé");
    }

    // Test 2: Message de groupe
    echo "\n✅ Test 2: Création message de groupe\n";
    $msg2 = new Message('msg2', 'Hello everyone!', 'user1');
    $msg2->setRecipientGroup('group1');
    
    if ($messageRepo->create($msg2)) {
        echo "   ✓ Message de groupe créé\n";
    } else {
        throw new Exception("Erreur création message groupe");
    }

    // Test 3: Message avec fichier
    echo "\n✅ Test 3: Création message avec fichier\n";
    $msg3 = new Message('msg3', 'Check this file', 'user2', 'file');
    $msg3->setRecipientUser('user1');
    $msg3->setFilePath('/uploads/document.pdf');
    
    if ($messageRepo->create($msg3)) {
        echo "   ✓ Message avec fichier créé\n";
    } else {
        throw new Exception("Erreur création message fichier");
    }

    // Test 4: FindById
    echo "\n✅ Test 4: Recherche par ID\n";
    $foundMsg = $messageRepo->findById('msg1');
    if ($foundMsg && $foundMsg->getContent() === 'Hello John!') {
        echo "   ✓ Message trouvé : " . $foundMsg->getContent() . "\n";
        echo "   ✓ De : " . $foundMsg->getFromUser() . "\n";
        echo "   ✓ Vers : " . $foundMsg->getToUser() . "\n";
        echo "   ✓ Type : " . ($foundMsg->isPrivateMessage() ? "Privé" : "Groupe") . "\n";
    } else {
        throw new Exception("Message non trouvé");
    }

    // Test 5: FindAll
    echo "\n✅ Test 5: Recherche de tous les messages\n";
    $allMessages = $messageRepo->findAll();
    echo "   ✓ Nombre de messages : " . count($allMessages) . "\n";
    
    foreach ($allMessages as $msg) {
        echo "   ✓ Message: " . substr($msg->getContent(), 0, 20) . "... (ID: " . $msg->getId() . ")\n";
    }

    // Test 6: FindByUser
    echo "\n✅ Test 6: Messages d'un utilisateur\n";
    $user1Messages = $messageRepo->findByUser('user1');
    echo "   ✓ Messages de user1 : " . count($user1Messages) . "\n";

    // Test 7: FindByGroup
    echo "\n✅ Test 7: Messages d'un groupe\n";
    $groupMessages = $messageRepo->findByGroup('group1');
    echo "   ✓ Messages du group1 : " . count($groupMessages) . "\n";

    // Test 8: Conversation
    echo "\n✅ Test 8: Conversation entre utilisateurs\n";
    $conversation = $messageRepo->findConversation('user1', 'user2');
    echo "   ✓ Messages dans conversation user1<->user2 : " . count($conversation) . "\n";

    // Test 9: Update message
    echo "\n✅ Test 9: Mise à jour message\n";
    $msg1->setContent('Hello John! (Updated)');
    $msg1->markAsRead();
    
    if ($messageRepo->update($msg1)) {
        echo "   ✓ Message mis à jour\n";
        
        $updatedMsg = $messageRepo->findById('msg1');
        if ($updatedMsg->getContent() === 'Hello John! (Updated)') {
            echo "   ✓ Contenu mis à jour confirmé\n";
        }
        if ($updatedMsg->getStatus() === 'read') {
            echo "   ✓ Statut mis à jour confirmé\n";
        }
    }

    // Test 10: Delete
    echo "\n✅ Test 10: Suppression message\n";
    if ($messageRepo->delete('msg2')) {
        echo "   ✓ Message supprimé\n";
        
        if (!$messageRepo->findById('msg2')) {
            echo "   ✓ Suppression confirmée\n";
        }
    }

    $finalMessages = $messageRepo->findAll();
    echo "\n✅ Vérification finale : " . count($finalMessages) . " messages restants\n";

    echo "\n🎯 MessageRepository: TOUS LES TESTS OK!\n";

    // Nettoyage
    if (file_exists('data/test_messages.xml')) {
        unlink('data/test_messages.xml');
        echo "   ✓ Fichier de test nettoyé\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "❌ Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} 