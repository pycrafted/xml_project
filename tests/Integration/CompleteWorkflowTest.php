<?php

namespace WhatsApp\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WhatsApp\Utils\XMLManager;
use WhatsApp\Services\UserService;
use WhatsApp\Services\MessageService;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;
use WhatsApp\Models\Contact;
use WhatsApp\Models\Group;

/**
 * Test d'intégration complet - Workflow utilisateur
 * 
 * Simule un scénario réel d'utilisation de l'application
 * 
 * @group integration
 */
class CompleteWorkflowTest extends TestCase
{
    private XMLManager $xmlManager;
    private UserService $userService;
    private MessageService $messageService;
    private ContactRepository $contactRepo;
    private GroupRepository $groupRepo;
    private string $testFile;

    protected function setUp(): void
    {
        $this->testFile = 'data/test_integration.xml';
        $this->xmlManager = new XMLManager($this->testFile);
        $this->userService = new UserService($this->xmlManager);
        $this->messageService = new MessageService($this->xmlManager);
        $this->contactRepo = new ContactRepository($this->xmlManager);
        $this->groupRepo = new GroupRepository($this->xmlManager);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    /**
     * @test
     * Scénario complet : Alice et Bob utilisent l'application
     */
    public function complete_whatsapp_workflow(): void
    {
        // ÉTAPE 1: Création des utilisateurs
        $alice = $this->userService->createUser(
            'alice',
            'Alice Dupont',
            'alice@example.com',
            ['theme' => 'dark', 'notifications' => 'enabled']
        );
        
        $bob = $this->userService->createUser(
            'bob',
            'Bob Martin',
            'bob@example.com',
            ['theme' => 'light']
        );

        $charlie = $this->userService->createUser(
            'charlie',
            'Charlie Wilson',
            'charlie@example.com'
        );

        $this->assertCount(3, $this->userService->searchUsers([]));

        // ÉTAPE 2: Alice ajoute Bob et Charlie comme contacts
        $contact1 = new Contact('contact_bob', 'Bob Contact', 'bob');
        $contact2 = new Contact('contact_charlie', 'Charlie Contact', 'charlie');
        
        $this->contactRepo->create($contact1);
        $this->contactRepo->create($contact2);

        // Vérifier les contacts
        $aliceContacts = $this->contactRepo->findAll();
        $this->assertCount(2, $aliceContacts);

        // ÉTAPE 3: Alice crée un groupe avec Bob et Charlie
        $group = new Group('project_group', 'Projet Universitaire', 'Groupe pour le projet UCAD');
        $group->addMember('alice', 'admin');
        $group->addMember('bob', 'member');
        $group->addMember('charlie', 'member');
        
        $this->groupRepo->create($group);

        // Vérifier le groupe
        $savedGroup = $this->groupRepo->findById('project_group');
        $this->assertNotNull($savedGroup);
        $this->assertCount(3, $savedGroup->getMembers());
        $this->assertTrue($savedGroup->isAdmin('alice'));
        $this->assertTrue($savedGroup->isMember('bob'));

        // ÉTAPE 4: Échange de messages privés
        $privateMessage1 = $this->messageService->sendPrivateMessage(
            'alice',
            'bob',
            'Salut Bob! Tu as vu le projet du prof Fall?'
        );

        $privateMessage2 = $this->messageService->sendPrivateMessage(
            'bob',
            'alice',
            'Oui! On devrait s\'organiser. Tu veux qu\'on crée un groupe?'
        );

        // Vérifier la conversation
        $conversation = $this->messageService->getConversation('alice', 'bob');
        $this->assertCount(2, $conversation);

        // ÉTAPE 5: Messages de groupe
        $groupMessage1 = $this->messageService->sendGroupMessage(
            'alice',
            'project_group',
            'Hello tout le monde! Bienvenue dans notre groupe projet 🎯'
        );

        $groupMessage2 = $this->messageService->sendGroupMessage(
            'bob',
            'project_group',
            'Merci Alice! Prêt à travailler dur 💪'
        );

        $groupMessage3 = $this->messageService->sendGroupMessage(
            'charlie',
            'project_group',
            'Super! On va avoir une excellente note 🏆'
        );

        // Vérifier les messages du groupe
        $groupMessages = $this->messageService->getGroupMessages('project_group', 'alice');
        $this->assertCount(3, $groupMessages);

        // ÉTAPE 6: Marquer des messages comme lus
        $this->messageService->markAsRead($privateMessage1->getId(), 'bob');
        $this->messageService->markAsRead($groupMessage1->getId(), 'bob');

        // ÉTAPE 7: Statistiques finales
        $userStats = $this->userService->getUserStats();
        $messageStats = $this->messageService->getMessageStats();

        $this->assertEquals(3, $userStats['total']);
        $this->assertEquals(3, $userStats['active']);
        
        $this->assertEquals(5, $messageStats['total']); // 2 privés + 3 groupe
        $this->assertEquals(2, $messageStats['private']);
        $this->assertEquals(3, $messageStats['group']);
        $this->assertEquals(2, $messageStats['read']);

        // ÉTAPE 8: Validation XML finale
        $this->assertTrue($this->xmlManager->validate());

        // ÉTAPE 9: Vérifier la persistance - recharger et vérifier
        $newXmlManager = new XMLManager($this->testFile);
        $newUserService = new UserService($newXmlManager);
        $reloadedUsers = $newUserService->searchUsers([]);
        
        $this->assertCount(3, $reloadedUsers);
        
        // Vérifier qu'Alice existe toujours avec ses settings
        $reloadedAlice = null;
        foreach ($reloadedUsers as $user) {
            if ($user->getId() === 'alice') {
                $reloadedAlice = $user;
                break;
            }
        }
        
        $this->assertNotNull($reloadedAlice);
        $this->assertEquals('Alice Dupont', $reloadedAlice->getName());
        $this->assertEquals(['theme' => 'dark', 'notifications' => 'enabled'], $reloadedAlice->getSettings());

        echo "\n✅ WORKFLOW COMPLET TESTÉ AVEC SUCCÈS !\n";
        echo "   👤 3 utilisateurs créés\n";
        echo "   📞 2 contacts ajoutés\n";
        echo "   👥 1 groupe avec 3 membres\n";
        echo "   💬 5 messages échangés (2 privés + 3 groupe)\n";
        echo "   📊 Statistiques correctes\n";
        echo "   🔄 Persistance XML validée\n";
    }

    /**
     * @test
     * Test des contraintes métier
     */
    public function it_enforces_business_rules(): void
    {
        // Créer un utilisateur
        $alice = $this->userService->createUser('alice', 'Alice', 'alice@test.com');

        // Test 1: Ne peut pas envoyer de message à un utilisateur inexistant
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Destinataire non trouvé');
        
        $this->messageService->sendPrivateMessage('alice', 'inexistant', 'Hello');
    }

    /**
     * @test
     * Test de la gestion d'erreurs
     */
    public function it_handles_errors_gracefully(): void
    {
        // Test 1: Création avec email invalide
        try {
            $this->userService->createUser('test', 'Test', 'invalid-email');
            $this->fail('Should have thrown exception');
        } catch (\Exception $e) {
            $this->assertStringContains('Format d\'email invalide', $e->getMessage());
        }

        // Test 2: Message vide
        $this->userService->createUser('sender', 'Sender', 'sender@test.com');
        $this->userService->createUser('receiver', 'Receiver', 'receiver@test.com');
        
        try {
            $this->messageService->sendPrivateMessage('sender', 'receiver', '');
            $this->fail('Should have thrown exception');
        } catch (\Exception $e) {
            $this->assertStringContains('contenu du message ne peut pas être vide', $e->getMessage());
        }
    }
} 