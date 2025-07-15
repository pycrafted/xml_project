<?php

namespace WhatsApp\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests HTTP simples (sans Selenium)
 * Plus facile à configurer et utiliser immédiatement
 */
class SimpleHttpTest extends TestCase
{
    private string $baseUrl = 'http://localhost:8000';
    private array $cookies = [];
    
    /**
     * Test automatisé complet avec requêtes HTTP
     */
    public function testCompleteWorkflowHttp(): void
    {
        echo "\n🚀 Test automatisé HTTP (sans Selenium)\n";
        
        // Étape 1: Créer des utilisateurs de test
        echo "📝 Création des utilisateurs...\n";
        $this->createUser('alice123', 'Alice', 'alice@test.com', 'password123');
        $this->createUser('bob456', 'Bob', 'bob@test.com', 'password123');
        $this->createUser('charlie789', 'Charlie', 'charlie@test.com', 'password123');
        
        // Étape 2: Alice se connecte
        echo "👤 Alice se connecte...\n";
        $this->loginUser('alice@test.com', 'password123');
        
        // Étape 3: Alice ajoute des contacts
        echo "📱 Alice ajoute des contacts...\n";
        $this->addContact('bob456', 'Bob Martin');
        $this->addContact('charlie789', 'Charlie Durand');
        
        // Étape 4: Alice envoie un message à Bob
        echo "💬 Alice envoie un message à Bob...\n";
        $this->sendMessage('bob456', 'Salut Bob ! Comment vas-tu ?');
        
        // Étape 5: Vérifier que le message est dans la base
        echo "✅ Vérification du message...\n";
        $this->assertMessageExists('Salut Bob ! Comment vas-tu ?');
        
        // Étape 6: Bob se connecte et répond
        echo "🔄 Bob se connecte et répond...\n";
        $this->loginUser('bob@test.com', 'password123');
        $this->addContact('alice123', 'Alice Dupont');
        $this->sendMessage('alice123', 'Salut Alice ! Ça va bien !');
        
        // Étape 7: Créer un groupe
        echo "👥 Création d'un groupe...\n";
        $this->createGroup('groupe_test', 'Groupe Test', ['alice123', 'bob456', 'charlie789']);
        
        // Étape 8: Envoyer un message de groupe
        echo "📢 Message de groupe...\n";
        $this->sendGroupMessage('groupe_test', 'Salut tout le monde !');
        
        echo "🎉 Test HTTP terminé avec succès !\n";
    }
    
    /**
     * Test de performance avec plusieurs utilisateurs
     */
    public function testPerformanceHttp(): void
    {
        echo "\n⚡ Test de performance HTTP\n";
        
        $startTime = microtime(true);
        
        // Créer 20 utilisateurs rapidement
        for ($i = 1; $i <= 20; $i++) {
            $this->createUser("user{$i}", "User {$i}", "user{$i}@test.com", "password123");
        }
        
        // Envoyer 100 messages
        for ($i = 1; $i <= 100; $i++) {
            $fromUser = "user" . (($i % 20) + 1);
            $toUser = "user" . ((($i + 1) % 20) + 1);
            
            $this->loginUser("{$fromUser}@test.com", "password123");
            $this->sendMessage($toUser, "Message automatique #{$i}");
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        echo "✅ Performance : 20 utilisateurs, 100 messages en {$duration}s\n";
    }
    
    /**
     * Créer un utilisateur via HTTP
     */
    private function createUser(string $userId, string $name, string $email, string $password): void
    {
        $data = [
            'action' => 'register',
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'confirm_password' => $password
        ];
        
        $response = $this->makeHttpRequest('POST', '/', $data);
        $this->assertStringContainsString('success', $response);
    }
    
    /**
     * Se connecter via HTTP
     */
    private function loginUser(string $email, string $password): void
    {
        $data = [
            'action' => 'login',
            'email' => $email,
            'password' => $password
        ];
        
        $response = $this->makeHttpRequest('POST', '/', $data);
        $this->assertStringContainsString('dashboard', $response);
    }
    
    /**
     * Ajouter un contact via HTTP
     */
    private function addContact(string $contactId, string $contactName): void
    {
        $data = [
            'action' => 'add_contact',
            'contact_id' => $contactId,
            'contact_name' => $contactName
        ];
        
        $response = $this->makeHttpRequest('POST', '/contacts.php', $data);
        $this->assertStringContainsString('success', $response);
    }
    
    /**
     * Envoyer un message via HTTP
     */
    private function sendMessage(string $recipientId, string $message): void
    {
        $data = [
            'action' => 'send_message',
            'recipient_id' => $recipientId,
            'message' => $message,
            'type' => 'text'
        ];
        
        $response = $this->makeHttpRequest('POST', '/ajax.php', $data);
        $this->assertStringContainsString('success', $response);
    }
    
    /**
     * Créer un groupe via HTTP
     */
    private function createGroup(string $groupId, string $groupName, array $members): void
    {
        $data = [
            'action' => 'create_group',
            'group_id' => $groupId,
            'group_name' => $groupName,
            'members' => implode(',', $members)
        ];
        
        $response = $this->makeHttpRequest('POST', '/groups.php', $data);
        $this->assertStringContainsString('success', $response);
    }
    
    /**
     * Envoyer un message de groupe via HTTP
     */
    private function sendGroupMessage(string $groupId, string $message): void
    {
        $data = [
            'action' => 'send_group_message',
            'group_id' => $groupId,
            'message' => $message
        ];
        
        $response = $this->makeHttpRequest('POST', '/ajax.php', $data);
        $this->assertStringContainsString('success', $response);
    }
    
    /**
     * Vérifier qu'un message existe
     */
    private function assertMessageExists(string $message): void
    {
        // Vérifier directement dans le fichier XML
        $xmlFile = __DIR__ . '/../data/sample_data.xml';
        $xmlContent = file_get_contents($xmlFile);
        $this->assertStringContainsString($message, $xmlContent);
    }
    
    /**
     * Faire une requête HTTP
     */
    private function makeHttpRequest(string $method, string $url, array $data = []): string
    {
        $fullUrl = $this->baseUrl . $url;
        
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Cookie: ' . $this->formatCookies()
                ],
                'content' => http_build_query($data),
                'timeout' => 30
            ]
        ]);
        
        $response = file_get_contents($fullUrl, false, $context);
        
        // Extraire les cookies de la réponse
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (strpos($header, 'Set-Cookie:') === 0) {
                    $cookie = substr($header, 12);
                    $cookieParts = explode(';', $cookie);
                    $cookieData = explode('=', $cookieParts[0], 2);
                    $this->cookies[$cookieData[0]] = $cookieData[1] ?? '';
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Formater les cookies pour les requêtes
     */
    private function formatCookies(): string
    {
        $cookieString = '';
        foreach ($this->cookies as $name => $value) {
            $cookieString .= "{$name}={$value}; ";
        }
        return rtrim($cookieString, '; ');
    }
    
    /**
     * Test de stress : beaucoup d'utilisateurs et de messages
     */
    public function testStressTest(): void
    {
        echo "\n🏋️ Test de stress\n";
        
        $startTime = microtime(true);
        $userCount = 50;
        $messageCount = 500;
        
        // Créer beaucoup d'utilisateurs
        for ($i = 1; $i <= $userCount; $i++) {
            $this->createUser("stress_user{$i}", "Stress User {$i}", "stress{$i}@test.com", "password123");
        }
        
        // Envoyer beaucoup de messages
        for ($i = 1; $i <= $messageCount; $i++) {
            $fromUser = "stress_user" . (($i % $userCount) + 1);
            $toUser = "stress_user" . ((($i + 1) % $userCount) + 1);
            
            $this->loginUser("{$fromUser}@test.com", "password123");
            $this->sendMessage($toUser, "Message de stress #{$i}");
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        echo "✅ Stress test : {$userCount} utilisateurs, {$messageCount} messages en {$duration}s\n";
        echo "📊 Performance : " . round($messageCount / $duration, 2) . " messages/seconde\n";
    }
} 