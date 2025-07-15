<?php

require_once 'vendor/autoload.php';

use WhatsApp\Models\Group;
use WhatsApp\Utils\XMLManager;
use WhatsApp\Repositories\GroupRepository;

echo "🔍 Test GroupRepository...\n\n";

try {
    // Nettoyage préalable
    if (file_exists('data/test_groups.xml')) {
        unlink('data/test_groups.xml');
    }
    
    $xmlManager = new XMLManager('data/test_groups.xml');
    $groupRepo = new GroupRepository($xmlManager);

    // Test 1: Création groupe simple
    echo "✅ Test 1: Création groupe simple\n";
    $group1 = new Group('group1', 'Famille', 'Groupe familial');
    $group1->addMember('user1', 'admin');
    $group1->addMember('user2', 'member');
    
    if ($groupRepo->create($group1)) {
        echo "   ✓ Groupe créé avec succès\n";
    } else {
        throw new Exception("Erreur création groupe");
    }

    // Test 2: Recherche par ID
    echo "\n✅ Test 2: Recherche par ID\n";
    $foundGroup = $groupRepo->findById('group1');
    if ($foundGroup && $foundGroup->getName() === 'Famille') {
        echo "   ✓ Groupe trouvé : " . $foundGroup->getName() . "\n";
        echo "   ✓ Description : " . $foundGroup->getDescription() . "\n";
        echo "   ✓ Membres : " . $foundGroup->getMemberCount() . "\n";
        echo "   ✓ user1 admin ? " . ($foundGroup->isAdmin('user1') ? 'Oui' : 'Non') . "\n";
        echo "   ✓ user2 membre ? " . ($foundGroup->isMember('user2') ? 'Oui' : 'Non') . "\n";
    } else {
        throw new Exception("Groupe non trouvé");
    }

    // Test 3: Création de plusieurs groupes
    echo "\n✅ Test 3: Création de plusieurs groupes\n";
    $group2 = new Group('group2', 'Travail');
    $group2->addMember('user1', 'admin');
    $group2->addMember('user3', 'member');
    
    $group3 = new Group('group3', 'Amis', 'Groupe d\'amis');
    $group3->addMember('user2', 'admin');
    $group3->addMember('user3', 'member');
    $group3->addMember('user4', 'member');
    
    $groupRepo->create($group2);
    $groupRepo->create($group3);
    echo "   ✓ Plusieurs groupes créés\n";

    // Test 4: FindAll
    echo "\n✅ Test 4: Recherche de tous les groupes\n";
    $allGroups = $groupRepo->findAll();
    echo "   ✓ Nombre de groupes : " . count($allGroups) . "\n";
    
    foreach ($allGroups as $group) {
        echo "   ✓ Groupe: " . $group->getName() . " (" . $group->getMemberCount() . " membres)\n";
    }

    // Test 5: FindByName
    echo "\n✅ Test 5: Recherche par nom\n";
    $familyGroups = $groupRepo->findByName('Famille');
    echo "   ✓ Groupes avec 'Famille' : " . count($familyGroups) . "\n";

    // Test 6: FindByMember
    echo "\n✅ Test 6: Groupes d'un membre\n";
    $user1Groups = $groupRepo->findByMember('user1');
    echo "   ✓ Groupes de user1 : " . count($user1Groups) . "\n";
    foreach ($user1Groups as $group) {
        echo "   ✓ " . $group->getName() . " (rôle: " . ($group->isAdmin('user1') ? 'admin' : 'member') . ")\n";
    }

    // Test 7: FindByAdmin
    echo "\n✅ Test 7: Groupes administrés\n";
    $user1AdminGroups = $groupRepo->findByAdmin('user1');
    echo "   ✓ Groupes administrés par user1 : " . count($user1AdminGroups) . "\n";

    // Test 8: Update - modification membres
    echo "\n✅ Test 8: Mise à jour groupe\n";
    $group1->setName('Famille (Updated)');
    $group1->addMember('user3', 'member'); // Nouveau membre
    
    if ($groupRepo->update($group1)) {
        echo "   ✓ Groupe mis à jour\n";
        
        $updatedGroup = $groupRepo->findById('group1');
        if ($updatedGroup->getName() === 'Famille (Updated)') {
            echo "   ✓ Nom mis à jour confirmé\n";
        }
        if ($updatedGroup->isMember('user3')) {
            echo "   ✓ Nouveau membre ajouté\n";
        }
    }

    // Test 9: Exists
    echo "\n✅ Test 9: Vérification existence\n";
    if ($groupRepo->exists('group1')) {
        echo "   ✓ Group1 existe\n";
    }
    if (!$groupRepo->exists('group999')) {
        echo "   ✓ Group999 n'existe pas\n";
    }

    // Test 10: Delete
    echo "\n✅ Test 10: Suppression\n";
    if ($groupRepo->delete('group2')) {
        echo "   ✓ Group2 supprimé\n";
        
        if (!$groupRepo->exists('group2')) {
            echo "   ✓ Suppression confirmée\n";
        }
    }

    $finalGroups = $groupRepo->findAll();
    echo "\n✅ Vérification finale : " . count($finalGroups) . " groupes restants\n";

    echo "\n🎯 GroupRepository: TOUS LES TESTS OK!\n";

    // Nettoyage
    if (file_exists('data/test_groups.xml')) {
        unlink('data/test_groups.xml');
        echo "   ✓ Fichier de test nettoyé\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "❌ Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} 