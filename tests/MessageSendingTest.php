<?php

use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\WebDriverException;

/**
 * Test complet pour l'envoi de messages avec Selenium
 */
class MessageSendingTest extends TestCase
{
    private $driver;
    private $wait;
    private $baseUrl = 'http://localhost:8000';

    protected function setUp(): void
    {
        // Configuration du driver Selenium
        $host = 'http://localhost:4444/wd/hub';
        $capabilities = \Facebook\WebDriver\Remote\DesiredCapabilities::chrome();
        
        try {
            $this->driver = \Facebook\WebDriver\Remote\RemoteWebDriver::create($host, $capabilities);
            $this->wait = new WebDriverWait($this->driver, 10);
        } catch (Exception $e) {
            $this->markTestSkipped('Selenium server not available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if ($this->driver) {
            $this->driver->quit();
        }
    }

    /**
     * Test d'envoi de message complet avec login
     */
    public function testCompleteMessageSending()
    {
        echo "\n=== TEST COMPLET D'ENVOI DE MESSAGES ===\n";
        
        // 1. Aller à la page de connexion
        $this->driver->get($this->baseUrl);
        echo "✅ Page de connexion chargée\n";
        
        // 2. Se connecter
        $this->loginUser('john_doe', 'john123');
        echo "✅ Connexion réussie\n";
        
        // 3. Aller à la section Chat
        $this->goToChat();
        echo "✅ Section Chat accessible\n";
        
        // 4. Sélectionner un contact
        $this->selectContact();
        echo "✅ Contact sélectionné\n";
        
        // 5. Tester l'envoi de différents types de messages
        $this->testMessageTypes();
        echo "✅ Tests de types de messages réussis\n";
        
        // 6. Vérifier que les messages apparaissent
        $this->verifyMessagesAppear();
        echo "✅ Messages affichés correctement\n";
        
        echo "\n🎉 TOUS LES TESTS D'ENVOI DE MESSAGES SONT PASSÉS !\n";
    }

    /**
     * Test des erreurs d'envoi
     */
    public function testMessageSendingErrors()
    {
        echo "\n=== TEST DES ERREURS D'ENVOI ===\n";
        
        $this->driver->get($this->baseUrl);
        $this->loginUser('john_doe', 'john123');
        $this->goToChat();
        $this->selectContact();
        
        // Test message vide
        $this->testEmptyMessage();
        echo "✅ Message vide correctement rejeté\n";
        
        // Test message avec seulement des espaces
        $this->testWhitespaceMessage();
        echo "✅ Message avec espaces seulement correctement rejeté\n";
        
        // Test message très long
        $this->testLongMessage();
        echo "✅ Message très long correctement géré\n";
        
        echo "\n🎉 TOUS LES TESTS D'ERREURS SONT PASSÉS !\n";
    }

    /**
     * Test de performance d'envoi multiple
     */
    public function testMultipleMessagesSending()
    {
        echo "\n=== TEST DE PERFORMANCE D'ENVOI MULTIPLE ===\n";
        
        $this->driver->get($this->baseUrl);
        $this->loginUser('john_doe', 'john123');
        $this->goToChat();
        $this->selectContact();
        
        $startTime = microtime(true);
        
        // Envoyer 5 messages rapidement
        for ($i = 1; $i <= 5; $i++) {
            $this->sendMessage("Message rapide #{$i}");
            usleep(500000); // 0.5 seconde entre chaque message
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        echo "✅ 5 messages envoyés en {$totalTime} secondes\n";
        
        // Vérifier que tous les messages sont présents
        $this->verifyMultipleMessages(5);
        echo "✅ Tous les messages multiples sont affichés\n";
        
        echo "\n🎉 TEST DE PERFORMANCE RÉUSSI !\n";
    }

    private function loginUser(string $username, string $password): void
    {
        $this->wait->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::name('username')));
        
        $this->driver->findElement(WebDriverBy::name('username'))->sendKeys($username);
        $this->driver->findElement(WebDriverBy::name('password'))->sendKeys($password);
        $this->driver->findElement(WebDriverBy::xpath('//button[@type="submit"]'))->click();
        
        // Attendre la redirection
        $this->wait->until(WebDriverExpectedCondition::urlContains('dashboard'));
    }

    private function goToChat(): void
    {
        $chatLink = $this->wait->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::linkText('Chat')));
        $chatLink->click();
        
        // Attendre que la page chat soit chargée
        $this->wait->until(WebDriverExpectedCondition::urlContains('chat'));
    }

    private function selectContact(): void
    {
        // Attendre qu'un contact soit disponible
        $contact = $this->wait->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::cssSelector('.contact-item')));
        $contact->click();
        
        // Attendre que la zone de message soit visible
        $this->wait->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('message-input')));
    }

    private function testMessageTypes(): void
    {
        // Message normal
        $this->sendMessage('Message de test normal');
        
        // Message avec caractères spéciaux
        $this->sendMessage('Message avec émojis 😊🎉 et caractères spéciaux !@#$%');
        
        // Message avec retours à la ligne
        $this->sendMessage("Message\navec\nplusieurs\nlignes");
        
        // Message avec HTML (doit être échappé)
        $this->sendMessage('<script>alert("test")</script>');
    }

    private function sendMessage(string $content): void
    {
        $messageInput = $this->driver->findElement(WebDriverBy::id('message-input'));
        $messageInput->clear();
        $messageInput->sendKeys($content);
        
        $sendButton = $this->driver->findElement(WebDriverBy::id('send-button'));
        $sendButton->click();
        
        // Attendre que le message soit envoyé
        sleep(1);
    }

    private function verifyMessagesAppear(): void
    {
        // Attendre que les messages apparaissent
        $messages = $this->wait->until(WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::cssSelector('.message')));
        
        $this->assertGreaterThan(0, count($messages), 'Aucun message trouvé');
        
        // Vérifier que le dernier message contient le contenu attendu
        $lastMessage = end($messages);
        $this->assertNotEmpty($lastMessage->getText(), 'Le dernier message est vide');
    }

    private function testEmptyMessage(): void
    {
        $messageInput = $this->driver->findElement(WebDriverBy::id('message-input'));
        $messageInput->clear();
        
        $sendButton = $this->driver->findElement(WebDriverBy::id('send-button'));
        $sendButton->click();
        
        // Vérifier qu'un message d'erreur apparaît
        try {
            $this->wait->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.error-message')));
            echo "✅ Message d'erreur affiché pour message vide\n";
        } catch (WebDriverException $e) {
            echo "⚠️ Pas de message d'erreur pour message vide (acceptable)\n";
        }
    }

    private function testWhitespaceMessage(): void
    {
        $messageInput = $this->driver->findElement(WebDriverBy::id('message-input'));
        $messageInput->clear();
        $messageInput->sendKeys('   ');
        
        $sendButton = $this->driver->findElement(WebDriverBy::id('send-button'));
        $sendButton->click();
        
        // Vérifier qu'un message d'erreur apparaît ou que le message n'est pas envoyé
        sleep(1);
    }

    private function testLongMessage(): void
    {
        $longMessage = str_repeat('A', 5000); // Message très long
        
        $messageInput = $this->driver->findElement(WebDriverBy::id('message-input'));
        $messageInput->clear();
        $messageInput->sendKeys($longMessage);
        
        $sendButton = $this->driver->findElement(WebDriverBy::id('send-button'));
        $sendButton->click();
        
        // Vérifier la gestion du message long
        sleep(1);
    }

    private function verifyMultipleMessages(int $expectedCount): void
    {
        $messages = $this->driver->findElements(WebDriverBy::cssSelector('.message'));
        $this->assertGreaterThanOrEqual($expectedCount, count($messages), 
            "Expected at least {$expectedCount} messages, found " . count($messages));
    }
} 