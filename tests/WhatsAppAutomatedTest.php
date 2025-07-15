<?php

namespace WhatsApp\Tests;

use WhatsApp\Tests\SeleniumTestBase;

/**
 * Test automatisé complet de l'application WhatsApp
 * Similaire à vos tests Django + Selenium
 */
class WhatsAppAutomatedTest extends SeleniumTestBase
{
    /**
     * Test complet : inscription, connexion, messages, groupes
     * Exactement comme avec Django + Selenium !
     */
    public function testCompleteWhatsAppWorkflow(): void
    {
        echo "\n🚀 Début du test automatisé WhatsApp\n";
        
        // Étape 1: Créer des utilisateurs de test
        echo "📝 Création des utilisateurs de test...\n";
        $this->createTestUser('alice123', 'Alice Dupont', 'alice@test.com', 'password123');
        $this->createTestUser('bob456', 'Bob Martin', 'bob@test.com', 'password123');
        $this->createTestUser('charlie789', 'Charlie Durand', 'charlie@test.com', 'password123');
        
        // Étape 2: Alice se connecte et ajoute des contacts
        echo "👤 Alice se connecte et ajoute des contacts...\n";
        $this->login('alice@test.com', 'password123');
        $this->addContact('bob456', 'Bob Martin');
        $this->addContact('charlie789', 'Charlie Durand');
        
        // Étape 3: Alice envoie un message à Bob
        echo "💬 Alice envoie un message à Bob...\n";
        $this->sendMessage('bob456', 'Salut Bob ! Comment ça va ?');
        $this->takeScreenshot('alice_sends_message_to_bob');
        
        // Étape 4: Bob se connecte et répond
        echo "🔄 Bob se connecte et répond...\n";
        $this->login('bob@test.com', 'password123');
        $this->addContact('alice123', 'Alice Dupont'); // Bob ajoute Alice
        $this->assertMessageReceived('Salut Bob ! Comment ça va ?');
        $this->sendMessage('alice123', 'Salut Alice ! Ça va bien et toi ?');
        $this->takeScreenshot('bob_replies_to_alice');
        
        // Étape 5: Créer un groupe
        echo "👥 Création d'un groupe...\n";
        $this->createGroup('groupe_test', 'Groupe Test', ['alice123', 'bob456', 'charlie789']);
        
        // Étape 6: Envoyer des messages de groupe
        echo "📢 Messages de groupe...\n";
        $this->sendGroupMessage('groupe_test', 'Salut tout le monde !');
        
        // Étape 7: Charlie se connecte et participe
        echo "🆕 Charlie rejoint la conversation...\n";
        $this->login('charlie@test.com', 'password123');
        $this->sendGroupMessage('groupe_test', 'Salut ! Je suis Charlie !');
        $this->takeScreenshot('group_conversation');
        
        // Étape 8: Vérifications finales
        echo "✅ Vérifications finales...\n";
        $this->verifyMessagesExist([
            'Salut Bob ! Comment ça va ?',
            'Salut Alice ! Ça va bien et toi ?',
            'Salut tout le monde !',
            'Salut ! Je suis Charlie !'
        ]);
        
        echo "🎉 Test automatisé terminé avec succès !\n";
    }
    
    /**
     * Test de performance : créer plusieurs utilisateurs et messages
     */
    public function testPerformanceWithMultipleUsers(): void
    {
        echo "\n⚡ Test de performance avec utilisateurs multiples\n";
        
        // Créer 10 utilisateurs automatiquement
        for ($i = 1; $i <= 10; $i++) {
            $this->createTestUser(
                "user{$i}",
                "Utilisateur {$i}",
                "user{$i}@test.com",
                "password123"
            );
        }
        
        // Chaque utilisateur envoie 5 messages
        for ($i = 1; $i <= 10; $i++) {
            $this->login("user{$i}@test.com", "password123");
            
            for ($j = 1; $j <= 5; $j++) {
                $recipientId = $i < 10 ? "user" . ($i + 1) : "user1";
                $this->sendMessage($recipientId, "Message {$j} de l'utilisateur {$i}");
            }
        }
        
        echo "✅ Test de performance terminé : 10 utilisateurs, 50 messages\n";
    }
    
    /**
     * Test de robustesse : gestion des erreurs
     */
    public function testErrorHandling(): void
    {
        echo "\n🛡️ Test de gestion des erreurs\n";
        
        // Tenter de se connecter avec de mauvaises données
        $this->driver->get($this->baseUrl);
        $this->fillForm([
            'input[name="email"]' => 'inexistant@test.com',
            'input[name="password"]' => 'mauvais_password'
        ]);
        
        $this->waitForClickable(WebDriverBy::cssSelector('button[type="submit"]'))->click();
        
        // Vérifier que le message d'erreur apparaît
        $this->waitForElement(WebDriverBy::cssSelector('.error-message'));
        
        // Tenter d'envoyer un message vide
        $this->createTestUser('test_user', 'Test User', 'test@test.com', 'password123');
        $this->login('test@test.com', 'password123');
        $this->sendMessage('inexistant_user', ''); // Message vide
        
        // Vérifier la gestion d'erreur
        $this->waitForElement(WebDriverBy::cssSelector('.error-message'));
        
        echo "✅ Test de gestion des erreurs terminé\n";
    }
    
    /**
     * Créer un groupe avec des membres
     */
    protected function createGroup(string $groupId, string $groupName, array $memberIds): void
    {
        $this->waitForClickable(WebDriverBy::linkText('Groupes'))->click();
        $this->waitForClickable(WebDriverBy::cssSelector('.create-group-btn'))->click();
        
        $this->fillForm([
            'input[name="group_id"]' => $groupId,
            'input[name="group_name"]' => $groupName
        ]);
        
        // Ajouter les membres
        foreach ($memberIds as $memberId) {
            $this->waitForClickable(WebDriverBy::cssSelector(
                "[data-user-id='{$memberId}'] .add-member-btn"
            ))->click();
        }
        
        $this->waitForClickable(WebDriverBy::cssSelector('button[type="submit"]'))->click();
        $this->waitForElement(WebDriverBy::cssSelector('.success-message'));
    }
    
    /**
     * Envoyer un message de groupe
     */
    protected function sendGroupMessage(string $groupId, string $message): void
    {
        $this->waitForClickable(WebDriverBy::linkText('Chat'))->click();
        $this->waitForClickable(WebDriverBy::cssSelector(
            "[data-group-id='{$groupId}']"
        ))->click();
        
        $messageInput = $this->waitForElement(WebDriverBy::cssSelector('#message-input'));
        $messageInput->sendKeys($message);
        
        $this->waitForClickable(WebDriverBy::cssSelector('#send-btn'))->click();
        
        $this->waitForElement(WebDriverBy::xpath(
            "//div[@class='message-sent' and contains(text(), '{$message}')]"
        ));
    }
    
    /**
     * Vérifier que tous les messages existent
     */
    protected function verifyMessagesExist(array $messages): void
    {
        foreach ($messages as $message) {
            $this->assertMessageReceived($message);
        }
    }
    
    /**
     * Test de régression : s'assurer que les fonctionnalités existantes marchent
     */
    public function testRegression(): void
    {
        echo "\n🔄 Test de régression\n";
        
        // Tester toutes les pages principales
        $pages = [
            '/' => 'Page d\'accueil',
            '/dashboard.php' => 'Dashboard',
            '/contacts.php' => 'Contacts',
            '/groups.php' => 'Groupes',
            '/chat.php' => 'Chat',
            '/profile.php' => 'Profil'
        ];
        
        foreach ($pages as $url => $description) {
            $this->driver->get($this->baseUrl . $url);
            
            // Vérifier qu'il n'y a pas d'erreur PHP
            $pageSource = $this->driver->getPageSource();
            $this->assertStringNotContainsString('Fatal error', $pageSource);
            $this->assertStringNotContainsString('Parse error', $pageSource);
            
            echo "✅ {$description} fonctionne\n";
        }
        
        echo "✅ Test de régression terminé\n";
    }
} 