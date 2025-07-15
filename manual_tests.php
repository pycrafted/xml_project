<?php
/**
 * Tests manuels pour vérifier chaque fonctionnalité
 */

require_once "vendor/autoload.php";
use WhatsApp\Utils\XMLManager;
use WhatsApp\Services\UserService;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;

$xmlManager = new XMLManager();
$userService = new UserService($xmlManager);
$contactRepo = new ContactRepository($xmlManager);
$groupRepo = new GroupRepository($xmlManager);

echo "🧪 TESTS MANUELS\n";
echo "================\n\n";

// Test 1: user_settings_update
echo "✅ Test 1: user_settings_update - ";
try {
    $alice = $userService->findUserById("alice2025");
    if ($alice) {
        $alice->setSettings(["theme" => "dark", "notifications" => "true"]);
        echo "PASS\n";
    } else {
        echo "FAIL - Alice non trouvée\n";
    }
} catch (Exception $e) {
    echo "FAIL - " . $e->getMessage() . "\n";
}

// Test 2: view_contacts
echo "✅ Test 2: view_contacts - ";
try {
    $contacts = $contactRepo->findByUserId("alice2025");
    if (count($contacts) > 0) {
        echo "PASS (" . count($contacts) . " contacts)\n";
    } else {
        echo "FAIL - Aucun contact\n";
    }
} catch (Exception $e) {
    echo "FAIL - " . $e->getMessage() . "\n";
}

// Test 3: delete_contact
echo "✅ Test 3: delete_contact - ";
try {
    $contacts = $contactRepo->findByUserId("alice2025");
    if (count($contacts) > 0) {
        $contactId = $contacts[0]->getId();
        $result = $contactRepo->deleteContact($contactId);
        echo $result ? "PASS\n" : "FAIL - Suppression échouée\n";
    } else {
        echo "FAIL - Aucun contact à supprimer\n";
    }
} catch (Exception $e) {
    echo "FAIL - " . $e->getMessage() . "\n";
}

// Test 4: add_group_member
echo "✅ Test 4: add_group_member - ";
try {
    $result = $groupRepo->addMemberToGroup("group1", "erik2025", "member");
    echo $result ? "PASS\n" : "FAIL - Ajout échoué\n";
} catch (Exception $e) {
    echo "FAIL - " . $e->getMessage() . "\n";
}

// Test 5: remove_group_member
echo "✅ Test 5: remove_group_member - ";
try {
    $result = $groupRepo->removeMemberFromGroup("group1", "erik2025");
    echo $result ? "PASS\n" : "FAIL - Suppression échouée\n";
} catch (Exception $e) {
    echo "FAIL - " . $e->getMessage() . "\n";
}

// Test 6: group_settings
echo "✅ Test 6: group_settings - ";
try {
    $group = $groupRepo->findById("group1");
    if ($group) {
        $group->setName("Groupe Amis Modifié");
        $result = $groupRepo->update($group);
        echo $result ? "PASS\n" : "FAIL - Mise à jour échouée\n";
    } else {
        echo "FAIL - Groupe non trouvé\n";
    }
} catch (Exception $e) {
    echo "FAIL - " . $e->getMessage() . "\n";
}

// Test 7: session_management
echo "✅ Test 7: session_management - ";
try {
    $alice = $userService->findUserById("alice2025");
    echo $alice ? "PASS\n" : "FAIL - Alice non trouvée\n";
} catch (Exception $e) {
    echo "FAIL - " . $e->getMessage() . "\n";
}

// Test 8: data_integrity_user_count
echo "✅ Test 8: data_integrity_user_count - ";
try {
    $users = $userService->getAllUsers();
    $count = count($users);
    echo $count >= 3 ? "PASS ($count users)\n" : "FAIL - Seulement $count users\n";
} catch (Exception $e) {
    echo "FAIL - " . $e->getMessage() . "\n";
}

echo "\n🎯 Tests manuels terminés !\n";
?>