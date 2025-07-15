<?php

/**
 * Application de Démonstration WhatsApp Clone
 * 
 * Interface CLI pour présenter toutes les fonctionnalités
 * du projet universitaire.
 * 
 * @author WhatsApp Clone Team - UCAD/DGI/ESP
 * @professor Ibrahima FALL
 */

require_once 'vendor/autoload.php';

use WhatsApp\Utils\XMLManager;
use WhatsApp\Services\UserService;
use WhatsApp\Services\MessageService;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;
use WhatsApp\Models\Contact;
use WhatsApp\Models\Group;

class WhatsAppDemoApp
{
    private XMLManager $xmlManager;
    private UserService $userService;
    private MessageService $messageService;
    private ContactRepository $contactRepo;
    private GroupRepository $groupRepo;

    public function __construct()
    {
        echo "🎯 WhatsApp Clone - Projet Master Génie Logiciel\n";
        echo "📋 Professeur: Ibrahima FALL - UCAD/DGI/ESP\n";
        echo "🏗️ Architecture: Clean Code + SOLID + XML\n";
        echo str_repeat("=", 60) . "\n\n";

        $this->xmlManager = new XMLManager('data/whatsapp_data.xml');
        $this->userService = new UserService($this->xmlManager);
        $this->messageService = new MessageService($this->xmlManager);
        $this->contactRepo = new ContactRepository($this->xmlManager);
        $this->groupRepo = new GroupRepository($this->xmlManager);
        
        $this->initializeDemo();
    }

    public function run(): void
    {
        while (true) {
            $this->showMainMenu();
            $choice = $this->getInput("Votre choix");

            try {
                switch ($choice) {
                    case '1':
                        $this->demoUsers();
                        break;
                    case '2':
                        $this->demoContacts();
                        break;
                    case '3':
                        $this->demoGroups();
                        break;
                    case '4':
                        $this->demoMessages();
                        break;
                    case '5':
                        $this->showStatistics();
                        break;
                    case '6':
                        $this->showArchitecture();
                        break;
                    case '7':
                        $this->showXMLStructure();
                        break;
                    case '0':
                        echo "\n👋 Merci d'avoir testé notre application !\n";
                        echo "🎓 Projet réalisé selon les spécifications académiques\n";
                        return;
                    default:
                        echo "❌ Choix invalide\n";
                }
            } catch (Exception $e) {
                echo "❌ Erreur: " . $e->getMessage() . "\n";
            }

            $this->waitForEnter();
        }
    }

    private function showMainMenu(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📱 WHATSAPP CLONE - MENU PRINCIPAL\n";
        echo str_repeat("=", 60) . "\n";
        echo "1. 👤 Gestion Utilisateurs\n";
        echo "2. 📞 Gestion Contacts\n";
        echo "3. 👥 Gestion Groupes\n";
        echo "4. 💬 Gestion Messages\n";
        echo "5. 📊 Statistiques\n";
        echo "6. 🏗️ Architecture du Projet\n";
        echo "7. 📄 Structure XML\n";
        echo "0. 🚪 Quitter\n";
        echo str_repeat("-", 60) . "\n";
    }

    private function demoUsers(): void
    {
        echo "\n👤 DÉMONSTRATION - GESTION UTILISATEURS\n";
        echo str_repeat("-", 50) . "\n";

        // Créer des utilisateurs
        echo "📝 Création d'utilisateurs...\n";
        
        try {
            $user1 = $this->userService->createUser(
                'demo_user1',
                'Alice Dupont',
                'alice@example.com',
                ['theme' => 'dark', 'notifications' => 'enabled']
            );
            echo "✅ Utilisateur créé: {$user1->getName()}\n";

            $user2 = $this->userService->createUser(
                'demo_user2',
                'Bob Martin',
                'bob@example.com',
                ['theme' => 'light', 'language' => 'fr']
            );
            echo "✅ Utilisateur créé: {$user2->getName()}\n";

        } catch (Exception $e) {
            echo "ℹ️ Utilisateurs déjà existants (normal)\n";
        }

        // Rechercher des utilisateurs
        echo "\n🔍 Recherche d'utilisateurs...\n";
        $users = $this->userService->searchUsers(['name' => 'Alice']);
        foreach ($users as $user) {
            echo "✓ Trouvé: {$user->getName()} ({$user->getEmail()})\n";
        }

        // Statistiques
        echo "\n📊 Statistiques utilisateurs:\n";
        $stats = $this->userService->getUserStats();
        echo "   Total: {$stats['total']}\n";
        echo "   Actifs: {$stats['active']}\n";
        echo "   Inactifs: {$stats['inactive']}\n";
    }

    private function demoContacts(): void
    {
        echo "\n📞 DÉMONSTRATION - GESTION CONTACTS\n";
        echo str_repeat("-", 50) . "\n";

        // Créer des contacts (éviter doublons)
        echo "📝 Ajout de contacts...\n";
        
        if (!$this->contactRepo->exists('contact_demo1')) {
            $contact1 = new Contact('contact_demo1', 'Alice Contact', 'demo_user1');
            $this->contactRepo->create($contact1);
        }
        
        if (!$this->contactRepo->exists('contact_demo2')) {
            $contact2 = new Contact('contact_demo2', 'Bob Contact', 'demo_user2');
            $this->contactRepo->create($contact2);
        }
        
        echo "✅ Contacts vérifiés/ajoutés\n";

        // Lister les contacts
        echo "\n📋 Liste des contacts:\n";
        $contacts = $this->contactRepo->findAll();
        foreach ($contacts as $contact) {
            echo "✓ {$contact->getName()} -> {$contact->getUserId()}\n";
        }

        // Recherche par nom
        echo "\n🔍 Recherche 'Alice':\n";
        $aliceContacts = $this->contactRepo->findByName('Alice');
        foreach ($aliceContacts as $contact) {
            echo "✓ Trouvé: {$contact->getName()}\n";
        }
    }

    private function demoGroups(): void
    {
        echo "\n👥 DÉMONSTRATION - GESTION GROUPES\n";
        echo str_repeat("-", 50) . "\n";

        // Créer un groupe (éviter doublons)
        echo "📝 Création de groupe...\n";
        
        if (!$this->groupRepo->exists('demo_group1')) {
            $group = new Group('demo_group1', 'Équipe Projet', 'Groupe pour le projet WhatsApp');
            $group->addMember('demo_user1', 'admin');
            $group->addMember('demo_user2', 'member');
            
            $this->groupRepo->create($group);
            echo "✅ Groupe créé: {$group->getName()}\n";
        } else {
            echo "ℹ️ Groupe déjà existant\n";
        }

        // Afficher les membres
        echo "\n👥 Membres du groupe:\n";
        $savedGroup = $this->groupRepo->findById('demo_group1');
        foreach ($savedGroup->getMembers() as $userId => $role) {
            echo "✓ {$userId}: {$role}\n";
        }

        // Groupes d'un utilisateur
        echo "\n🔍 Groupes de demo_user1:\n";
        $userGroups = $this->groupRepo->findByMember('demo_user1');
        foreach ($userGroups as $g) {
            echo "✓ {$g->getName()} (rôle: " . ($g->isAdmin('demo_user1') ? 'admin' : 'member') . ")\n";
        }
    }

    private function demoMessages(): void
    {
        echo "\n💬 DÉMONSTRATION - GESTION MESSAGES\n";
        echo str_repeat("-", 50) . "\n";

        // Message privé
        echo "📝 Envoi de message privé...\n";
        $privateMsg = $this->messageService->sendPrivateMessage(
            'demo_user1',
            'demo_user2',
            'Salut Bob! Comment ça va ?'
        );
        echo "✅ Message privé envoyé: {$privateMsg->getContent()}\n";

        // Message de groupe
        echo "\n📝 Envoi de message de groupe...\n";
        $groupMsg = $this->messageService->sendGroupMessage(
            'demo_user1',
            'demo_group1',
            'Hello tout le monde dans le groupe!'
        );
        echo "✅ Message de groupe envoyé: {$groupMsg->getContent()}\n";

        // Conversation
        echo "\n💬 Conversation Alice ↔ Bob:\n";
        $conversation = $this->messageService->getConversation('demo_user1', 'demo_user2');
        foreach ($conversation as $msg) {
            $from = $msg->getFromUser();
            $content = substr($msg->getContent(), 0, 50) . "...";
            echo "✓ {$from}: {$content}\n";
        }

        // Messages du groupe
        echo "\n👥 Messages du groupe:\n";
        $groupMessages = $this->messageService->getGroupMessages('demo_group1', 'demo_user1');
        foreach ($groupMessages as $msg) {
            $from = $msg->getFromUser();
            $content = substr($msg->getContent(), 0, 50) . "...";
            echo "✓ {$from}: {$content}\n";
        }
    }

    private function showStatistics(): void
    {
        echo "\n📊 STATISTIQUES GLOBALES\n";
        echo str_repeat("-", 50) . "\n";

        $userStats = $this->userService->getUserStats();
        $messageStats = $this->messageService->getMessageStats();
        
        echo "👤 UTILISATEURS:\n";
        echo "   Total: {$userStats['total']}\n";
        echo "   Actifs: {$userStats['active']}\n";
        
        echo "\n💬 MESSAGES:\n";
        echo "   Total: {$messageStats['total']}\n";
        echo "   Privés: {$messageStats['private']}\n";
        echo "   Groupes: {$messageStats['group']}\n";
        echo "   Avec fichiers: {$messageStats['with_files']}\n";
        echo "   Lus: {$messageStats['read']}\n";
        
        echo "\n📞 CONTACTS: " . count($this->contactRepo->findAll()) . "\n";
        echo "👥 GROUPES: " . count($this->groupRepo->findAll()) . "\n";
    }

    private function showArchitecture(): void
    {
        echo "\n🏗️ ARCHITECTURE DU PROJET\n";
        echo str_repeat("-", 50) . "\n";
        echo "📁 STRUCTURE EN COUCHES:\n";
        echo "   ├── Models (User, Message, Contact, Group)\n";
        echo "   ├── Repositories (Accès données XML)\n";
        echo "   ├── Services (Logique métier)\n";
        echo "   ├── Utils (XMLManager + validation XSD)\n";
        echo "   └── Tests (PHPUnit + couverture)\n\n";
        
        echo "🎯 PRINCIPES APPLIQUÉS:\n";
        echo "   ✓ Architecture Clean Code\n";
        echo "   ✓ Principes SOLID\n";
        echo "   ✓ Séparation des responsabilités\n";
        echo "   ✓ Validation XSD automatique\n";
        echo "   ✓ Gestion d'erreurs robuste\n";
        echo "   ✓ Tests unitaires complets\n\n";
        
        echo "📋 CONFORMITÉ CAHIER DES CHARGES:\n";
        echo "   ✓ PHP uniquement (8.0+)\n";
        echo "   ✓ Stockage XML exclusif\n";
        echo "   ✓ SimpleXML + DOM\n";
        echo "   ✓ Schema XSD\n";
        echo "   ✓ Documentation PHPDoc\n";
    }

    private function showXMLStructure(): void
    {
        echo "\n📄 STRUCTURE XML ACTUELLE\n";
        echo str_repeat("-", 50) . "\n";
        
        if (file_exists('data/whatsapp_data.xml')) {
            $content = file_get_contents('data/whatsapp_data.xml');
            $dom = new DOMDocument();
            $dom->loadXML($content);
            $dom->formatOutput = true;
            
            echo $dom->saveXML();
        } else {
            echo "❌ Fichier XML non trouvé\n";
        }
    }

    private function initializeDemo(): void
    {
        echo "🔧 Initialisation des données de démonstration...\n";
        
        try {
            // Créer quelques données de base si elles n'existent pas
            if (!$this->userService->searchUsers(['email' => 'demo@example.com'])) {
                $this->userService->createUser(
                    'demo_base',
                    'Utilisateur Demo',
                    'demo@example.com'
                );
            }
        } catch (Exception $e) {
            // Les données existent déjà
        }
        
        echo "✅ Initialisation terminée\n";
    }

    private function getInput(string $prompt): string
    {
        echo "{$prompt}: ";
        return trim(fgets(STDIN));
    }

    private function waitForEnter(): void
    {
        echo "\n⏎ Appuyez sur Entrée pour continuer...";
        fgets(STDIN);
    }
}

// Lancement de l'application
try {
    $app = new WhatsAppDemoApp();
    $app->run();
} catch (Exception $e) {
    echo "❌ Erreur fatale: " . $e->getMessage() . "\n";
    exit(1);
} 