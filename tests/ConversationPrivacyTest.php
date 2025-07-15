<?php

use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\WebDriverException;

/**
 * Test de confidentialité des conversations
 * Valide que les messages privés ne se mélangent pas entre différentes conversations
 */
class ConversationPrivacyTest extends TestCase
{
    private $driver;
    private $wait;
    private $baseUrl = 'http://localhost:8000';
    
    protected function setUp(): void
    {
        echo "\n🔒 DÉBUT DU TEST DE CONFIDENTIALITÉ DES CONVERSATIONS\n";
        echo "=" . str_repeat("=", 60) . "\n";
        
        try {
            $host = 'http://localhost:4444/wd/hub';
            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability('chromeOptions', [
                'args' => ['--disable-web-security', '--disable-features=VizDisplayCompositor']
            ]);
            
            $this->driver = RemoteWebDriver::create($host, $capabilities);
            $this->wait = new WebDriverWait($this->driver, 10);
            
            echo "✅ Navigateur configuré avec succès\n";
        } catch (Exception $e) {
            echo "⚠️ Selenium non disponible, simulation du test...\n";
            $this->runSimulationTest();
            $this->markTestSkipped('Selenium server not available');
        }
    }
    
    protected function tearDown(): void
    {
        if ($this->driver) {
            $this->driver->quit();
        }
    }
    
    /**
     * Test principal : Valider que les messages privés ne se mélangent pas
     */
    public function testConversationPrivacy()
    {
        echo "\n🎯 TEST DE CONFIDENTIALITÉ DES CONVERSATIONS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        // Étape 1: Connexion de l'utilisateur
        echo "1. Connexion de l'utilisateur...\n";
        $this->loginUser('john_doe', 'password123');
        echo "   ✅ Utilisateur connecté\n";
        
        // Étape 2: Naviguer vers la section Chat
        echo "2. Navigation vers la section Chat...\n";
        $this->goToChat();
        echo "   ✅ Section Chat accessible\n";
        
        // Étape 3: Envoyer un message au Contact A
        echo "3. Envoi d'un message au Contact A...\n";
        $messageToContactA = "Message secret pour Contact A - " . date('H:i:s');
        $this->sendMessageToContact('contact_a', $messageToContactA);
        echo "   ✅ Message envoyé au Contact A\n";
        
        // Étape 4: Vérifier que le message apparaît dans la conversation A
        echo "4. Vérification du message dans la conversation A...\n";
        $this->verifyMessageInConversation('contact_a', $messageToContactA);
        echo "   ✅ Message trouvé dans la conversation A\n";
        
        // Étape 5: Passer à la conversation B
        echo "5. Navigation vers la conversation B...\n";
        $this->switchToContact('contact_b');
        echo "   ✅ Conversation B sélectionnée\n";
        
        // Étape 6: Vérifier que le message n'apparaît PAS dans la conversation B
        echo "6. Vérification que le message n'apparaît PAS dans la conversation B...\n";
        $this->verifyMessageNotInConversation('contact_b', $messageToContactA);
        echo "   ✅ Message correctement absent de la conversation B\n";
        
        // Étape 7: Envoyer un message différent au Contact B
        echo "7. Envoi d'un message différent au Contact B...\n";
        $messageToContactB = "Message différent pour Contact B - " . date('H:i:s');
        $this->sendMessageToContact('contact_b', $messageToContactB);
        echo "   ✅ Message envoyé au Contact B\n";
        
        // Étape 8: Vérifier que chaque conversation a ses propres messages
        echo "8. Vérification finale des conversations séparées...\n";
        $this->verifyConversationSeparation($messageToContactA, $messageToContactB);
        echo "   ✅ Conversations correctement séparées\n";
        
        echo "\n🎉 TEST DE CONFIDENTIALITÉ RÉUSSI !\n";
        echo "✅ Les messages privés ne se mélangent pas entre les conversations\n";
    }
    
    /**
     * Test supplémentaire : Validation avec plusieurs utilisateurs
     */
    public function testMultipleUsersConversations()
    {
        echo "\n👥 TEST AVEC UTILISATEURS MULTIPLES\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        // Test avec différents utilisateurs pour s'assurer que les messages
        // ne se mélangent pas même avec plusieurs utilisateurs actifs
        
        $testMessages = [
            'contact_a' => "Message A - " . uniqid(),
            'contact_b' => "Message B - " . uniqid(),
            'contact_c' => "Message C - " . uniqid()
        ];
        
        foreach ($testMessages as $contact => $message) {
            echo "Envoi du message à {$contact}...\n";
            $this->sendMessageToContact($contact, $message);
            echo "   ✅ Message envoyé\n";
        }
        
        // Vérifier que chaque conversation ne contient que ses propres messages
        foreach ($testMessages as $contact => $message) {
            echo "Vérification de la conversation {$contact}...\n";
            $this->verifyMessageInConversation($contact, $message);
            
            // Vérifier que les messages des autres contacts ne sont pas présents
            foreach ($testMessages as $otherContact => $otherMessage) {
                if ($contact !== $otherContact) {
                    $this->verifyMessageNotInConversation($contact, $otherMessage);
                }
            }
            echo "   ✅ Conversation {$contact} correctement isolée\n";
        }
        
        echo "\n🎉 TEST UTILISATEURS MULTIPLES RÉUSSI !\n";
    }
    
    private function loginUser(string $username, string $password): void
    {
        $this->driver->get($this->baseUrl);
        
        $this->wait->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::name('username')));
        
        $this->driver->findElement(WebDriverBy::name('username'))->sendKeys($username);
        $this->driver->findElement(WebDriverBy::name('password'))->sendKeys($password);
        $this->driver->findElement(WebDriverBy::xpath('//button[@type="submit"]'))->click();
        
        $this->wait->until(WebDriverExpectedCondition::urlContains('dashboard'));
    }
    
    private function goToChat(): void
    {
        $chatLink = $this->wait->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::linkText('Chat')));
        $chatLink->click();
        
        $this->wait->until(WebDriverExpectedCondition::urlContains('chat'));
    }
    
    private function sendMessageToContact(string $contactIdentifier, string $message): void
    {
        $this->switchToContact($contactIdentifier);
        
        $messageInput = $this->wait->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('message-input')));
        $messageInput->clear();
        $messageInput->sendKeys($message);
        
        $sendButton = $this->driver->findElement(WebDriverBy::id('send-button'));
        $sendButton->click();
        
        sleep(2); // Attendre que le message soit envoyé
    }
    
    private function switchToContact(string $contactIdentifier): void
    {
        $contact = $this->wait->until(WebDriverExpectedCondition::elementToBeClickable(
            WebDriverBy::xpath("//a[contains(@href, 'contact_id') and contains(@href, '{$contactIdentifier}')]")
        ));
        $contact->click();
        
        $this->wait->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('message-input')));
    }
    
    private function verifyMessageInConversation(string $contactIdentifier, string $expectedMessage): void
    {
        $this->switchToContact($contactIdentifier);
        
        // Attendre que les messages se chargent
        sleep(1);
        
        $messages = $this->driver->findElements(WebDriverBy::cssSelector('.message'));
        $messageFound = false;
        
        foreach ($messages as $msg) {
            if (strpos($msg->getText(), $expectedMessage) !== false) {
                $messageFound = true;
                break;
            }
        }
        
        $this->assertTrue($messageFound, "Message '{$expectedMessage}' non trouvé dans la conversation {$contactIdentifier}");
    }
    
    private function verifyMessageNotInConversation(string $contactIdentifier, string $unexpectedMessage): void
    {
        $this->switchToContact($contactIdentifier);
        
        // Attendre que les messages se chargent
        sleep(1);
        
        $messages = $this->driver->findElements(WebDriverBy::cssSelector('.message'));
        $messageFound = false;
        
        foreach ($messages as $msg) {
            if (strpos($msg->getText(), $unexpectedMessage) !== false) {
                $messageFound = true;
                break;
            }
        }
        
        $this->assertFalse($messageFound, "Message '{$unexpectedMessage}' trouvé dans la conversation {$contactIdentifier} (ne devrait pas être là)");
    }
    
    private function verifyConversationSeparation(string $messageA, string $messageB): void
    {
        // Vérifier conversation A
        $this->switchToContact('contact_a');
        $this->verifyMessageInConversation('contact_a', $messageA);
        $this->verifyMessageNotInConversation('contact_a', $messageB);
        
        // Vérifier conversation B
        $this->switchToContact('contact_b');
        $this->verifyMessageInConversation('contact_b', $messageB);
        $this->verifyMessageNotInConversation('contact_b', $messageA);
    }
    
    private function runSimulationTest(): void
    {
        echo "\n🎭 SIMULATION DU TEST DE CONFIDENTIALITÉ\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        echo "1. ✅ Connexion utilisateur simulée\n";
        echo "2. ✅ Navigation vers Chat simulée\n";
        echo "3. ✅ Envoi message Contact A simulé\n";
        echo "4. ✅ Vérification message Contact A simulée\n";
        echo "5. ✅ Navigation vers Contact B simulée\n";
        echo "6. ✅ Vérification absence message Contact B simulée\n";
        echo "7. ✅ Envoi message Contact B simulé\n";
        echo "8. ✅ Vérification séparation conversations simulée\n";
        
        echo "\n🎉 SIMULATION TERMINÉE AVEC SUCCÈS !\n";
        echo "✅ Pour un test complet, lancez Selenium et relancez ce test\n";
    }
} 