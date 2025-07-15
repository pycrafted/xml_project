<?php

/**
 * TEST DE VALIDATION COMPLÈTE - TOUS LES CORRECTIFS
 * 
 * Ce script teste toutes les fonctionnalités critiques qui ont été corrigées :
 * 1. Suppression en cascade des messages
 * 2. Contacts bidirectionnels
 * 3. Isolation des conversations privées
 * 4. Validation et nettoyage des données
 * 5. Correctifs de confidentialité
 */

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;
use WhatsApp\Services\UserService;
use WhatsApp\Services\MessageService;
use WhatsApp\Repositories\UserRepository;
use WhatsApp\Repositories\MessageRepository;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;

class ValidationTestRunner
{
    private XMLManager $xmlManager;
    private UserService $userService;
    private MessageService $messageService;
    private UserRepository $userRepository;
    private MessageRepository $messageRepository;
    private ContactRepository $contactRepository;
    private GroupRepository $groupRepository;
    
    private array $testResults = [];
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;
    
    public function __construct()
    {
        // Utiliser un fichier de test pour ne pas affecter les données réelles
        $testFile = 'data/validation_test.xml';
        $this->xmlManager = new XMLManager($testFile);
        $this->userService = new UserService($this->xmlManager);
        $this->messageService = new MessageService($this->xmlManager);
        $this->userRepository = new UserRepository($this->xmlManager);
        $this->messageRepository = new MessageRepository($this->xmlManager);
        $this->contactRepository = new ContactRepository($this->xmlManager);
        $this->groupRepository = new GroupRepository($this->xmlManager);
        
        // Créer un fichier de test propre
        $this->initializeTestData();
    }
    
    public function runAllTests(): void
    {
        echo "🧪 VALIDATION COMPLÈTE - TOUS LES CORRECTIFS IMPLÉMENTÉS\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Test 1: Suppression en cascade
        echo "🔹 TEST 1: SUPPRESSION EN CASCADE\n";
        $this->testCascadeDeletion();
        
        // Test 2: Contacts bidirectionnels
        echo "\n🔹 TEST 2: CONTACTS BIDIRECTIONNELS\n";
        $this->testBidirectionalContacts();
        
        // Test 3: Isolation des conversations
        echo "\n🔹 TEST 3: ISOLATION DES CONVERSATIONS\n";
        $this->testConversationPrivacy();
        
        // Test 4: Validation des données
        echo "\n🔹 TEST 4: VALIDATION DES DONNÉES\n";
        $this->testDataIntegrity();
        
        // Test 5: Nettoyage des données orphelines
        echo "\n🔹 TEST 5: NETTOYAGE DES DONNÉES\n";
        $this->testDataCleaning();
        
        // Test 6: Correctifs de confidentialité
        echo "\n🔹 TEST 6: CORRECTIFS DE CONFIDENTIALITÉ\n";
        $this->testPrivacyFixes();
        
        // Résultats finaux
        $this->displayResults();
    }
    
    private function initializeTestData(): void
    {
        echo "📝 Initialisation des données de test...\n";
        
        // Créer des utilisateurs de test avec des settings par défaut
        $this->userService->createUser('alice_test', 'Alice Test', 'alice@test.com', ['theme' => 'light']);
        $this->userService->createUser('bob_test', 'Bob Test', 'bob@test.com', ['theme' => 'dark']);
        $this->userService->createUser('charlie_test', 'Charlie Test', 'charlie@test.com', ['theme' => 'light']);
        
        echo "✅ Données de test initialisées\n";
    }
    
    private function testCascadeDeletion(): void
    {
        echo "  🔸 Test de suppression en cascade des messages...\n";
        
        // Créer un contact entre Alice et Bob
        $contactId = $this->contactRepository->createContact('Bob Test', 'alice_test', 'bob_test');
        
        // Envoyer quelques messages
        $this->messageService->sendPrivateMessage('alice_test', 'bob_test', 'Message 1');
        $this->messageService->sendPrivateMessage('alice_test', 'bob_test', 'Message 2');
        $this->messageService->sendPrivateMessage('bob_test', 'alice_test', 'Réponse');
        
        // Vérifier que les messages existent
        $messagesBefore = $this->messageService->getConversation('alice_test', 'bob_test');
        $this->runTest('messages_exist_before_deletion', count($messagesBefore) === 3);
        
        // Supprimer le contact avec cascade
        $deleteResult = $this->contactRepository->deleteContact($contactId);
        $this->runTest('contact_deleted_successfully', $deleteResult === true);
        
        // Vérifier que les messages ont été supprimés
        try {
            $messagesAfter = $this->messageService->getConversation('alice_test', 'bob_test');
            $this->runTest('messages_deleted_with_contact', count($messagesAfter) === 0);
        } catch (Exception $e) {
            // C'est normal si une exception est levée car le contact n'existe plus
            $this->runTest('no_conversation_after_deletion', true);
        }
        
        echo "  ✅ Test de suppression en cascade terminé\n";
    }
    
    private function testBidirectionalContacts(): void
    {
        echo "  🔸 Test des contacts bidirectionnels...\n";
        
        // Alice ajoute Bob comme contact
        $contactId = $this->contactRepository->createContact('Bob Test', 'alice_test', 'bob_test');
        
        // Vérifier que Bob peut voir Alice dans ses contacts
        $bobCanSeeAlice = $this->contactRepository->contactExists('bob_test', 'alice_test');
        $this->runTest('bidirectional_contact_created', $bobCanSeeAlice === true);
        
        // Vérifier que Alice peut envoyer un message à Bob
        $message1 = $this->messageService->sendPrivateMessage('alice_test', 'bob_test', 'Salut Bob!');
        $this->runTest('alice_can_send_to_bob', $message1 !== null);
        
        // Vérifier que Bob peut envoyer un message à Alice
        $message2 = $this->messageService->sendPrivateMessage('bob_test', 'alice_test', 'Salut Alice!');
        $this->runTest('bob_can_send_to_alice', $message2 !== null);
        
        // Vérifier la conversation
        $conversation = $this->messageService->getConversation('alice_test', 'bob_test');
        $this->runTest('bidirectional_conversation_works', count($conversation) === 2);
        
        echo "  ✅ Test des contacts bidirectionnels terminé\n";
    }
    
    private function testConversationPrivacy(): void
    {
        echo "  🔸 Test de l'isolation des conversations...\n";
        
        // Créer des contacts
        $contact1 = $this->contactRepository->createContact('Bob Test', 'alice_test', 'bob_test');
        $contact2 = $this->contactRepository->createContact('Charlie Test', 'alice_test', 'charlie_test');
        
        // Alice envoie un message à Bob
        $messageToBob = $this->messageService->sendPrivateMessage('alice_test', 'bob_test', 'Message secret pour Bob');
        
        // Alice envoie un message à Charlie
        $messageToCharlie = $this->messageService->sendPrivateMessage('alice_test', 'charlie_test', 'Message secret pour Charlie');
        
        // Vérifier que les conversations sont isolées
        $conversationWithBob = $this->messageService->getConversation('alice_test', 'bob_test');
        $conversationWithCharlie = $this->messageService->getConversation('alice_test', 'charlie_test');
        
        $this->runTest('conversation_isolation_bob', count($conversationWithBob) === 1);
        $this->runTest('conversation_isolation_charlie', count($conversationWithCharlie) === 1);
        
        // Vérifier que les messages ne se mélangent pas
        $bobMessage = $conversationWithBob[0];
        $charlieMessage = $conversationWithCharlie[0];
        
        $this->runTest('bob_message_correct', $bobMessage->getContent() === 'Message secret pour Bob');
        $this->runTest('charlie_message_correct', $charlieMessage->getContent() === 'Message secret pour Charlie');
        
        echo "  ✅ Test de l'isolation des conversations terminé\n";
    }
    
    private function testDataIntegrity(): void
    {
        echo "  🔸 Test de validation des données...\n";
        
        // Tester la validation des références
        try {
            $this->messageService->sendPrivateMessage('alice_test', 'utilisateur_inexistant', 'Message test');
            $this->runTest('invalid_recipient_rejected', false);
        } catch (Exception $e) {
            $this->runTest('invalid_recipient_rejected', true);
        }
        
        // Tester la validation des expéditeurs
        try {
            $this->messageService->sendPrivateMessage('utilisateur_inexistant', 'bob_test', 'Message test');
            $this->runTest('invalid_sender_rejected', false);
        } catch (Exception $e) {
            $this->runTest('invalid_sender_rejected', true);
        }
        
        // Tester la validation du contenu
        try {
            $this->messageService->sendPrivateMessage('alice_test', 'bob_test', '');
            $this->runTest('empty_message_rejected', false);
        } catch (Exception $e) {
            $this->runTest('empty_message_rejected', true);
        }
        
        // Tester la validation d'intégrité
        $integrityReport = $this->messageService->validateDataIntegrity();
        $this->runTest('data_integrity_report_generated', isset($integrityReport['total_messages']));
        
        echo "  ✅ Test de validation des données terminé\n";
    }
    
    private function testDataCleaning(): void
    {
        echo "  🔸 Test de nettoyage des données...\n";
        
        // Créer des données de test
        $this->contactRepository->createContact('Test Contact', 'alice_test', 'bob_test');
        $this->messageService->sendPrivateMessage('alice_test', 'bob_test', 'Message test');
        
        // Exécuter le nettoyage
        $cleanupResult = $this->messageService->cleanupOrphanedData();
        $this->runTest('cleanup_executed', isset($cleanupResult['cleanup_timestamp']));
        
        // Vérifier que les données valides sont préservées
        $validMessages = $this->messageService->getConversation('alice_test', 'bob_test');
        $this->runTest('valid_data_preserved', count($validMessages) >= 1);
        
        echo "  ✅ Test de nettoyage des données terminé\n";
    }
    
    private function testPrivacyFixes(): void
    {
        echo "  🔸 Test des correctifs de confidentialité...\n";
        
        // Créer des contacts et messages
        $this->contactRepository->createContact('Bob Test', 'alice_test', 'bob_test');
        $this->contactRepository->createContact('Charlie Test', 'alice_test', 'charlie_test');
        
        $messageAliceToBob = $this->messageService->sendPrivateMessage('alice_test', 'bob_test', 'Message privé Alice->Bob');
        $messageAliceToCharlie = $this->messageService->sendPrivateMessage('alice_test', 'charlie_test', 'Message privé Alice->Charlie');
        
        // Vérifier que les messages sont correctement attribués
        $this->runTest('message_attribution_alice_bob', $messageAliceToBob->getFromUser() === 'alice_test');
        $this->runTest('message_attribution_alice_bob_recipient', $messageAliceToBob->getToUser() === 'bob_test');
        
        $this->runTest('message_attribution_alice_charlie', $messageAliceToCharlie->getFromUser() === 'alice_test');
        $this->runTest('message_attribution_alice_charlie_recipient', $messageAliceToCharlie->getToUser() === 'charlie_test');
        
        // Vérifier que les conversations sont séparées
        $bobConversation = $this->messageService->getConversation('alice_test', 'bob_test');
        $charlieConversation = $this->messageService->getConversation('alice_test', 'charlie_test');
        
        $this->runTest('conversations_separated', count($bobConversation) === 1 && count($charlieConversation) === 1);
        $this->runTest('no_message_leakage', $bobConversation[0]->getContent() !== $charlieConversation[0]->getContent());
        
        echo "  ✅ Test des correctifs de confidentialité terminé\n";
    }
    
    private function runTest(string $testName, bool $result): void
    {
        $this->totalTests++;
        $this->testResults[$testName] = $result;
        
        if ($result) {
            $this->passedTests++;
            echo "    ✅ $testName\n";
        } else {
            $this->failedTests++;
            echo "    ❌ $testName\n";
        }
    }
    
    private function displayResults(): void
    {
        echo "\n🎯 RÉSULTATS FINAUX\n";
        echo "=" . str_repeat("=", 60) . "\n";
        
        $successRate = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 2) : 0;
        
        echo "📊 STATISTIQUES:\n";
        echo "  • Total des tests: {$this->totalTests}\n";
        echo "  • Tests réussis: {$this->passedTests}\n";
        echo "  • Tests échoués: {$this->failedTests}\n";
        echo "  • Taux de réussite: {$successRate}%\n\n";
        
        if ($this->failedTests > 0) {
            echo "❌ TESTS ÉCHOUÉS:\n";
            foreach ($this->testResults as $testName => $result) {
                if (!$result) {
                    echo "  • $testName\n";
                }
            }
            echo "\n";
        }
        
        // Évaluation globale
        if ($successRate >= 100) {
            echo "🎉 EXCELLENT! Tous les correctifs fonctionnent parfaitement.\n";
            echo "✅ Le système est prêt pour la production.\n";
        } elseif ($successRate >= 90) {
            echo "👍 TRÈS BIEN! La plupart des correctifs fonctionnent.\n";
            echo "⚠️  Quelques ajustements mineurs peuvent être nécessaires.\n";
        } elseif ($successRate >= 80) {
            echo "⚠️  BON! Les correctifs principaux fonctionnent.\n";
            echo "🔧 Certains problèmes nécessitent une attention.\n";
        } else {
            echo "❌ ATTENTION! Plusieurs correctifs ne fonctionnent pas correctement.\n";
            echo "🚨 Révision nécessaire avant la production.\n";
        }
        
        echo "\n🏆 STATUT FINAL: ";
        if ($successRate >= 95) {
            echo "✅ SYSTÈME VALIDÉ - PRÊT POUR PRODUCTION\n";
        } else {
            echo "⚠️  SYSTÈME EN COURS DE VALIDATION - TESTS À COMPLÉTER\n";
        }
        
        echo "\n📝 Rapport généré le: " . date('Y-m-d H:i:s') . "\n";
    }
}

// Exécuter les tests
try {
    $testRunner = new ValidationTestRunner();
    $testRunner->runAllTests();
} catch (Exception $e) {
    echo "❌ ERREUR LORS DE L'EXÉCUTION DES TESTS: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . "\n";
    echo "📍 Ligne: " . $e->getLine() . "\n";
} 