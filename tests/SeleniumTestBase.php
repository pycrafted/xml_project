<?php

namespace WhatsApp\Tests;

use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverElement;

/**
 * Classe de base pour les tests Selenium
 * Similaire à votre expérience avec Django + Selenium
 */
abstract class SeleniumTestBase extends TestCase
{
    protected RemoteWebDriver $driver;
    protected string $baseUrl = 'http://localhost:8000';
    protected int $waitTimeout = 10;
    
    protected function setUp(): void
    {
        // Configuration similaire à Selenium avec Django
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability('chromeOptions', [
            'args' => [
                '--headless',  // Mode sans interface graphique
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--window-size=1920,1080'
            ]
        ]);
        
        $this->driver = RemoteWebDriver::create(
            'http://localhost:4444/wd/hub',  // Selenium Grid
            $capabilities
        );
        
        $this->driver->manage()->window()->maximize();
    }
    
    protected function tearDown(): void
    {
        if ($this->driver) {
            $this->driver->quit();
        }
    }
    
    /**
     * Attendre qu'un élément soit présent
     */
    protected function waitForElement(WebDriverBy $by): WebDriverElement
    {
        $wait = new WebDriverWait($this->driver, $this->waitTimeout);
        return $wait->until(
            WebDriverExpectedCondition::presenceOfElementLocated($by)
        );
    }
    
    /**
     * Attendre qu'un élément soit cliquable
     */
    protected function waitForClickable(WebDriverBy $by): WebDriverElement
    {
        $wait = new WebDriverWait($this->driver, $this->waitTimeout);
        return $wait->until(
            WebDriverExpectedCondition::elementToBeClickable($by)
        );
    }
    
    /**
     * Remplir un formulaire automatiquement
     */
    protected function fillForm(array $data): void
    {
        foreach ($data as $selector => $value) {
            $element = $this->waitForElement(WebDriverBy::cssSelector($selector));
            $element->clear();
            $element->sendKeys($value);
        }
    }
    
    /**
     * Créer un utilisateur de test
     */
    protected function createTestUser(string $userId, string $name, string $email, string $password): void
    {
        $this->driver->get($this->baseUrl);
        
        // Aller sur la page d'inscription
        $this->waitForClickable(WebDriverBy::linkText('Inscription'))->click();
        
        // Remplir le formulaire d'inscription
        $this->fillForm([
            'input[name="user_id"]' => $userId,
            'input[name="name"]' => $name,
            'input[name="email"]' => $email,
            'input[name="password"]' => $password,
            'input[name="confirm_password"]' => $password
        ]);
        
        // Soumettre le formulaire
        $this->waitForClickable(WebDriverBy::cssSelector('button[type="submit"]'))->click();
        
        // Attendre la redirection
        $this->waitForElement(WebDriverBy::cssSelector('.success-message, .dashboard'));
    }
    
    /**
     * Se connecter avec un utilisateur
     */
    protected function login(string $email, string $password): void
    {
        $this->driver->get($this->baseUrl);
        
        $this->fillForm([
            'input[name="email"]' => $email,
            'input[name="password"]' => $password
        ]);
        
        $this->waitForClickable(WebDriverBy::cssSelector('button[type="submit"]'))->click();
        
        // Attendre d'être sur le dashboard
        $this->waitForElement(WebDriverBy::cssSelector('.dashboard'));
    }
    
    /**
     * Ajouter un contact
     */
    protected function addContact(string $contactId, string $contactName): void
    {
        // Aller sur la page contacts
        $this->waitForClickable(WebDriverBy::linkText('Contacts'))->click();
        
        // Cliquer sur "Ajouter un contact"
        $this->waitForClickable(WebDriverBy::cssSelector('.add-contact-btn'))->click();
        
        // Remplir le formulaire
        $this->fillForm([
            'input[name="contact_id"]' => $contactId,
            'input[name="contact_name"]' => $contactName
        ]);
        
        // Soumettre
        $this->waitForClickable(WebDriverBy::cssSelector('button[type="submit"]'))->click();
        
        // Attendre la confirmation
        $this->waitForElement(WebDriverBy::cssSelector('.success-message'));
    }
    
    /**
     * Envoyer un message
     */
    protected function sendMessage(string $recipientId, string $message): void
    {
        // Aller sur la page chat
        $this->waitForClickable(WebDriverBy::linkText('Chat'))->click();
        
        // Sélectionner le destinataire
        $this->waitForClickable(WebDriverBy::cssSelector(
            "[data-contact-id='{$recipientId}']"
        ))->click();
        
        // Taper le message
        $messageInput = $this->waitForElement(WebDriverBy::cssSelector('#message-input'));
        $messageInput->sendKeys($message);
        
        // Envoyer
        $this->waitForClickable(WebDriverBy::cssSelector('#send-btn'))->click();
        
        // Attendre que le message apparaisse
        $this->waitForElement(WebDriverBy::xpath(
            "//div[@class='message-sent' and contains(text(), '{$message}')]"
        ));
    }
    
    /**
     * Vérifier qu'un message a été reçu
     */
    protected function assertMessageReceived(string $message): void
    {
        $messageElement = $this->waitForElement(WebDriverBy::xpath(
            "//div[@class='message-received' and contains(text(), '{$message}')]"
        ));
        
        $this->assertTrue($messageElement->isDisplayed());
    }
    
    /**
     * Prendre une capture d'écran (pour debugging)
     */
    protected function takeScreenshot(string $filename): void
    {
        $screenshot = $this->driver->takeScreenshot();
        file_put_contents("screenshots/{$filename}.png", $screenshot);
    }
} 