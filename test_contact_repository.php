<?php

require_once 'vendor/autoload.php';

use WhatsApp\Models\Contact;
use WhatsApp\Utils\XMLManager;
use WhatsApp\Repositories\ContactRepository;

echo "🔍 Test ContactRepository...\n\n";

try {
    // Nettoyage préalable
    if (file_exists('data/test_contacts.xml')) {
        unlink('data/test_contacts.xml');
    }
    
    $xmlManager = new XMLManager('data/test_contacts.xml');
    $contactRepo = new ContactRepository($xmlManager);

    // Test 1: Création contact
    echo "✅ Test 1: Création contact\n";
    $contact1 = new Contact('contact1', 'John Doe Contact', 'user1');
    
    if ($contactRepo->create($contact1)) {
        echo "   ✓ Contact créé avec succès\n";
    } else {
        throw new Exception("Erreur création contact");
    }

    // Test 2: Recherche par ID
    echo "\n✅ Test 2: Recherche par ID\n";
    $foundContact = $contactRepo->findById('contact1');
    if ($foundContact && $foundContact->getName() === 'John Doe Contact') {
        echo "   ✓ Contact trouvé : " . $foundContact->getName() . "\n";
        echo "   ✓ User ID : " . $foundContact->getUserId() . "\n";
    } else {
        throw new Exception("Contact non trouvé");
    }

    // Test 3: Création de plusieurs contacts
    echo "\n✅ Test 3: Création de plusieurs contacts\n";
    $contact2 = new Contact('contact2', 'Jane Smith Contact', 'user2');
    $contact3 = new Contact('contact3', 'Bob Wilson Contact', 'user3');
    $contact4 = new Contact('contact4', 'John Alternative', 'user1'); // Même user_id, nom différent
    
    $contactRepo->create($contact2);
    $contactRepo->create($contact3);
    $contactRepo->create($contact4);
    echo "   ✓ Plusieurs contacts créés\n";

    // Test 4: FindAll
    echo "\n✅ Test 4: Recherche de tous les contacts\n";
    $allContacts = $contactRepo->findAll();
    echo "   ✓ Nombre de contacts : " . count($allContacts) . "\n";
    
    foreach ($allContacts as $contact) {
        echo "   ✓ Contact: " . $contact->getName() . " -> " . $contact->getUserId() . " (ID: " . $contact->getId() . ")\n";
    }

    // Test 5: FindByName
    echo "\n✅ Test 5: Recherche par nom\n";
    $johnContacts = $contactRepo->findByName('John');
    echo "   ✓ Contacts avec 'John' : " . count($johnContacts) . "\n";
    foreach ($johnContacts as $contact) {
        echo "   ✓ Trouvé: " . $contact->getName() . "\n";
    }

    // Test 6: FindByUserId
    echo "\n✅ Test 6: Recherche par User ID\n";
    $user1Contacts = $contactRepo->findByUserId('user1');
    echo "   ✓ Contacts pour user1 : " . count($user1Contacts) . "\n";
    foreach ($user1Contacts as $contact) {
        echo "   ✓ Contact: " . $contact->getName() . "\n";
    }

    // Test 7: Update
    echo "\n✅ Test 7: Mise à jour contact\n";
    $contact1->setName('John Doe Contact (Updated)');
    
    if ($contactRepo->update($contact1)) {
        echo "   ✓ Contact mis à jour\n";
        
        // Vérifier la mise à jour
        $updatedContact = $contactRepo->findById('contact1');
        if ($updatedContact->getName() === 'John Doe Contact (Updated)') {
            echo "   ✓ Nom mis à jour confirmé\n";
        }
    } else {
        throw new Exception("Erreur mise à jour");
    }

    // Test 8: Exists
    echo "\n✅ Test 8: Vérification existence\n";
    if ($contactRepo->exists('contact1')) {
        echo "   ✓ Contact1 existe\n";
    }
    if (!$contactRepo->exists('contact999')) {
        echo "   ✓ Contact999 n'existe pas\n";
    }

    // Test 9: Delete
    echo "\n✅ Test 9: Suppression\n";
    if ($contactRepo->delete('contact2')) {
        echo "   ✓ Contact2 supprimé\n";
        
        if (!$contactRepo->exists('contact2')) {
            echo "   ✓ Suppression confirmée\n";
        }
    } else {
        throw new Exception("Erreur suppression");
    }

    // Vérification finale
    $finalContacts = $contactRepo->findAll();
    echo "\n✅ Vérification finale : " . count($finalContacts) . " contacts restants\n";

    echo "\n🎯 ContactRepository: TOUS LES TESTS OK!\n";

    // Nettoyage
    if (file_exists('data/test_contacts.xml')) {
        unlink('data/test_contacts.xml');
        echo "   ✓ Fichier de test nettoyé\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "❌ Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} 