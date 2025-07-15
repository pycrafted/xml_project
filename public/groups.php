<?php
/**
 * WhatsApp Web Clone - Gestion des Groupes
 * Interface complète pour gérer les groupes et leurs membres
 */

require_once '../vendor/autoload.php';

use WhatsApp\Services\UserService;
use WhatsApp\Repositories\GroupRepository;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Models\Group;

session_start();

// Fonction de logging simple
function debugLog($message) {
    file_put_contents('../logs/app.log', date('Y-m-d H:i:s') . " [DEBUG] " . $message . "\n", FILE_APPEND);
}

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Initialisation des services
$xmlManager = new WhatsApp\Utils\XMLManager();
$userService = new UserService($xmlManager);
$groupRepo = new GroupRepository($xmlManager);
$contactRepo = new ContactRepository($xmlManager);

// Variables
$currentUser = $userService->findUserById($_SESSION['user_id']);
$pageTitle = "Groupes - WhatsApp Web";
$error = '';
$success = '';
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$groupId = $_GET['id'] ?? $_POST['group_id'] ?? '';

// Gestion des actions
switch ($action) {
    case 'create':
        if ($_POST) {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name)) {
                $error = "Le nom du groupe est requis";
            } else {
                try {
                    // Créer le groupe avec le créateur comme admin directement
                    $groupId = $groupRepo->createGroup($name, $description, $_SESSION['user_id']);
                    $success = "Groupe '$name' créé avec succès";
                    $action = 'list';
                } catch (Exception $e) {
                    $error = "Erreur lors de la création du groupe : " . $e->getMessage();
                }
            }
        }
        break;
        
    case 'delete':
        if ($groupId) {
            try {
                $group = $groupRepo->getGroupById($groupId);
                if ($group) {
                    // Vérifier si l'utilisateur est admin du groupe
                    $isAdmin = $groupRepo->isUserAdminOfGroup($groupId, $_SESSION['user_id']);
                    if ($isAdmin) {
                        $groupRepo->deleteGroup($groupId);
                        $success = "Groupe supprimé avec succès";
                    } else {
                        $error = "Seuls les administrateurs peuvent supprimer le groupe";
                    }
                } else {
                    $error = "Groupe non trouvé";
                }
            } catch (Exception $e) {
                $error = "Erreur lors de la suppression : " . $e->getMessage();
            }
        }
        $action = 'list';
        break;
        
    case 'add_member_to_group':
    case 'add_member':
        
        // Log de débogage simplifié
        debugLog("Tentative d'ajout de membre au groupe $groupId par l'utilisateur " . $_SESSION['user_id']);
        
        if ($_POST && $groupId) {
            $contactId = $_POST['contact_id'] ?? '';
            $userId = $_POST['user_id'] ?? '';
            $role = $_POST['role'] ?? 'member';
            
            debugLog("Contact ID: " . $contactId);
            debugLog("User ID: " . $userId);
            debugLog("Role: " . $role);
            
            // Si on a un user_id directement, l'utiliser
            if ($userId) {
                debugLog("Utilisation de user_id directement");
                try {
                    $isAdmin = $groupRepo->isUserAdminOfGroup($groupId, $_SESSION['user_id']);
                    debugLog("IsAdmin check: " . ($isAdmin ? 'true' : 'false'));
                    if (!$isAdmin) {
                        $error = "Seuls les administrateurs peuvent ajouter des membres";
                        debugLog("Erreur: Non admin");
                    } else {
                        debugLog("Appel addMemberToGroup avec userId: $userId");
                        $result = $groupRepo->addMemberToGroup($groupId, $userId, $role);
                        debugLog("Résultat addMemberToGroup: " . ($result ? 'true' : 'false'));
                        if ($result) {
                            $success = "Membre ajouté au groupe avec succès";
                            debugLog("Succès: Membre ajouté");
                        } else {
                            $error = "Erreur lors de l'ajout du membre (peut-être déjà membre du groupe)";
                            debugLog("Erreur: Échec ajout membre");
                        }
                    }
                } catch (Exception $e) {
                    $error = "Erreur lors de l'ajout du membre : " . $e->getMessage();
                    debugLog("Exception: " . $e->getMessage());
                }
            }
            // Sinon, utiliser l'ancien système avec contact_id
            elseif ($contactId) {
                debugLog("Utilisation de contact_id");
                try {
                    $isAdmin = $groupRepo->isUserAdminOfGroup($groupId, $_SESSION['user_id']);
                    debugLog("IsAdmin check: " . ($isAdmin ? 'true' : 'false'));
                    if (!$isAdmin) {
                        $error = "Seuls les administrateurs peuvent ajouter des membres";
                        debugLog("Erreur: Non admin");
                    } else {
                        // Récupérer l'ID utilisateur du contact
                        debugLog("Récupération du contact: $contactId");
                        $contact = $contactRepo->getContactById($contactId);
                        if ($contact) {
                            $contactUserId = $contact->getContactUserId();
                            debugLog("Contact trouvé, userId: $contactUserId");
                            $result = $groupRepo->addMemberToGroup($groupId, $contactUserId, $role);
                            debugLog("Résultat addMemberToGroup: " . ($result ? 'true' : 'false'));
                            if ($result) {
                                $success = "Membre ajouté au groupe avec succès";
                                debugLog("Succès: Membre ajouté");
                            } else {
                                $error = "Erreur lors de l'ajout du membre (peut-être déjà membre du groupe)";
                                debugLog("Erreur: Échec ajout membre");
                            }
                        } else {
                            $error = "Contact non trouvé";
                            debugLog("Erreur: Contact non trouvé");
                        }
                    }
                } catch (Exception $e) {
                    $error = "Erreur lors de l'ajout du membre : " . $e->getMessage();
                    debugLog("Exception: " . $e->getMessage());
                }
            } else {
                $error = "Veuillez spécifier un utilisateur ou un contact";
            }
        }
        $action = 'manage';
        break;
        
    case 'remove_member_from_group':
    case 'remove_member':
        $memberId = $_GET['member_id'] ?? $_POST['member_id'] ?? $_POST['user_id'] ?? '';
        if ($groupId && $memberId) {
            try {
                $isAdmin = $groupRepo->isUserAdminOfGroup($groupId, $_SESSION['user_id']);
                if (!$isAdmin) {
                    $error = "Seuls les administrateurs peuvent retirer des membres";
                } else {
                    $groupRepo->removeMemberFromGroup($groupId, $memberId);
                    $success = "Membre retiré du groupe avec succès";
                }
            } catch (Exception $e) {
                $error = "Erreur lors de la suppression du membre : " . $e->getMessage();
            }
        }
        $action = 'manage';
        break;
        
    case 'update_group':
        if ($_POST && $groupId) {
            $name = trim($_POST['group_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name)) {
                $error = "Le nom du groupe est requis";
            } else {
                try {
                    $isAdmin = $groupRepo->isUserAdminOfGroup($groupId, $_SESSION['user_id']);
                    if (!$isAdmin) {
                        $error = "Seuls les administrateurs peuvent modifier le groupe";
                    } else {
                        $group = $groupRepo->findById($groupId);
                        if ($group) {
                            $group->setName($name);
                            $group->setDescription($description);
                            $groupRepo->update($group);
                            $success = "Groupe modifié avec succès";
                        } else {
                            $error = "Groupe non trouvé";
                        }
                    }
                } catch (Exception $e) {
                    $error = "Erreur lors de la modification : " . $e->getMessage();
                }
            }
        }
        $action = 'manage';
        break;
        
    case 'leave':
        if ($groupId) {
            try {
                $groupRepo->removeMemberFromGroup($groupId, $_SESSION['user_id']);
                $success = "Vous avez quitté le groupe";
                $action = 'list';
            } catch (Exception $e) {
                $error = "Erreur lors de la sortie du groupe : " . $e->getMessage();
            }
        }
        break;
        
    case 'manage':
        // Affichage de la gestion d'un groupe spécifique
        break;
        
    default:
        $action = 'list';
        break;
}

// Récupération des groupes pour l'affichage
$groups = [];
$contacts = [];
try {
    $groups = $groupRepo->getGroupsByUserId($_SESSION['user_id']);
    $contacts = $contactRepo->getContactsByUserId($_SESSION['user_id']);
} catch (Exception $e) {
    $error = "Erreur lors du chargement des groupes : " . $e->getMessage();
}

// Si on gère un groupe spécifique, récupérer ses détails
$currentGroup = null;
$groupMembers = [];
if ($action === 'manage' && $groupId) {
    try {
        $currentGroup = $groupRepo->getGroupById($groupId);
        $groupMembers = $groupRepo->getGroupMembers($groupId);
    } catch (Exception $e) {
        $error = "Erreur lors du chargement du groupe : " . $e->getMessage();
        $action = 'list';
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>💬 WhatsApp Web</h2>
                <p>Bienvenue <?= htmlspecialchars($currentUser ? $currentUser->getName() : 'Utilisateur') ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    📊 Dashboard
                </a>
                <a href="contacts.php" class="nav-item">
                    👥 Mes Contacts
                </a>
                <a href="groups.php" class="nav-item active">
                    👫 Mes Groupes
                </a>
                <a href="chat.php" class="nav-item">
                    💬 Messages
                </a>
                <a href="profile.php" class="nav-item">
                    ⚙️ Mon Profil
                </a>
                <a href="index.php?action=logout" class="nav-item" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                    🚪 Déconnexion
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <?php if ($action === 'manage' && $currentGroup): ?>
                    <h1>👫 Gestion du groupe : <?= htmlspecialchars($currentGroup->getName()) ?></h1>
                    <p>Gérez les membres et paramètres du groupe</p>
                    <a href="groups.php" class="btn btn-secondary btn-sm">← Retour aux groupes</a>
                <?php else: ?>
                    <h1>👫 Gestion des Groupes</h1>
                    <p>Créez et gérez vos groupes de discussion</p>
                <?php endif; ?>
            </div>

            <div class="content-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <strong>Erreur :</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <strong>Succès :</strong> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'create'): ?>
                    <!-- Formulaire de création de groupe -->
                    <div class="card">
                        <div class="card-header">
                            ➕ Créer un nouveau groupe
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="create">
                                
                                <div class="form-group">
                                    <label for="name">Nom du groupe :</label>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        name="name" 
                                        class="form-control" 
                                        placeholder="Ex: Équipe Projet, Famille..."
                                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="description">Description (optionnel) :</label>
                                    <textarea 
                                        id="description" 
                                        name="description" 
                                        class="form-control" 
                                        placeholder="Décrivez le but de ce groupe..."
                                        rows="3"
                                    ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        ➕ Créer le groupe
                                    </button>
                                    <a href="groups.php" class="btn btn-secondary">
                                        ❌ Annuler
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php elseif ($action === 'manage' && $currentGroup): ?>
                    <!-- Gestion d'un groupe spécifique -->
                    <div class="card">
                        <div class="card-header">
                            ℹ️ Informations du groupe
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <strong>Nom :</strong><br>
                                    <?= htmlspecialchars($currentGroup->getName()) ?>
                                </div>
                                <div>
                                    <strong>Membres :</strong><br>
                                    <?= count($groupMembers) ?> personne(s)
                                </div>
                                <div style="grid-column: 1 / -1;">
                                    <strong>Description :</strong><br>
                                    <?= htmlspecialchars($currentGroup->getDescription() ?: 'Aucune description') ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Membres du groupe -->
                    <div class="card">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>👥 Membres du groupe (<?= count($groupMembers) ?>)</span>
                            <?php if ($groupRepo->isUserAdminOfGroup($groupId, $_SESSION['user_id'])): ?>
                                <button onclick="openGroupModal()" class="btn btn-primary btn-sm">
                                    ➕ Ajouter un membre
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($groupMembers)): ?>
                                <p style="text-align: center; color: #667781;">Aucun membre dans ce groupe.</p>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($groupMembers as $userId => $role): ?>
                                        <?php 
                                        $memberUser = null;
                                        try {
                                            $memberUser = $userService->findUserById($userId);
                                        } catch (Exception $e) {
                                            // Utilisateur non trouvé
                                        }
                                        ?>
                                        <li class="list-group-item">
                                            <div>
                                                <strong>
                                                    <?= $memberUser ? htmlspecialchars($memberUser->getName()) : 'Utilisateur inconnu' ?>
                                                    <?= $userId === $_SESSION['user_id'] ? ' (Vous)' : '' ?>
                                                </strong>
                                                <br>
                                                <small style="color: #667781;">
                                                    <?= $memberUser ? htmlspecialchars($memberUser->getEmail()) : 'Email inconnu' ?>
                                                    • 
                                                    <span style="color: <?= $role === 'admin' ? '#00a884' : '#667781' ?>;">
                                                        <?= $role === 'admin' ? '👑 Administrateur' : '👤 Membre' ?>
                                                    </span>
                                                </small>
                                            </div>
                                            <div>
                                                <?php if ($groupRepo->isUserAdminOfGroup($groupId, $_SESSION['user_id']) && $userId !== $_SESSION['user_id']): ?>
                                                    <button 
                                                        onclick="if(confirm('Retirer ce membre du groupe ?')) { window.location.href='groups.php?action=remove_member&id=<?= $groupId ?>&member_id=<?= $userId ?>'; }" 
                                                        class="btn btn-danger btn-sm" 
                                                        title="Retirer du groupe">
                                                        🗑️
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($userId === $_SESSION['user_id'] && $role !== 'admin'): ?>
                                                    <button 
                                                        onclick="if(confirm('Quitter ce groupe ?')) { window.location.href='groups.php?action=leave&id=<?= $groupId ?>'; }" 
                                                        class="btn btn-secondary btn-sm" 
                                                        title="Quitter le groupe">
                                                        🚪
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Modal d'ajout de membre (affiché seulement si admin) -->
                    <?php if ($groupRepo->isUserAdminOfGroup($groupId, $_SESSION['user_id'])): ?>
                        <div id="group-modal" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3 class="modal-title">Ajouter un membre au groupe</h3>
                                    <button type="button" class="modal-close" onclick="closeGroupModal()">×</button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" id="add-member-form">
                                        <input type="hidden" name="action" value="add_member">
                                        <input type="hidden" name="group_id" value="<?= htmlspecialchars($groupId) ?>">
                                        
                                        <div class="form-group">
                                            <label>Sélectionner un contact :</label>
                                            <select name="contact_id" class="form-control" required>
                                                <option value="">-- Choisir un contact --</option>
                                            <?php foreach ($contacts as $contact): ?>
                                                <?php
                                                // Vérifier si ce contact est déjà membre
                                                $isAlreadyMember = false;
                                                foreach ($groupMembers as $userId => $role) {
                                                    if ($userId === $contact->getContactUserId()) {
                                                        $isAlreadyMember = true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <?php if (!$isAlreadyMember): ?>
                                                    <option value="<?= htmlspecialchars($contact->getId()) ?>">
                                                        <?= htmlspecialchars($contact->getName()) ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                        <div class="form-group">
                                            <label>Rôle :</label>
                                            <select name="role" class="form-control">
                                                <option value="member">👤 Membre</option>
                                                <option value="admin">👑 Administrateur</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" onclick="closeGroupModal()" class="btn btn-secondary">Annuler</button>
                                    <button type="submit" form="add-member-form" class="btn btn-primary">Ajouter</button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Liste des groupes -->
                    <div class="card">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>📋 Mes groupes (<?= count($groups) ?>)</span>
                            <a href="groups.php?action=create" class="btn btn-primary btn-sm">
                                ➕ Créer un groupe
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($groups)): ?>
                                <div style="text-align: center; padding: 40px 0; color: #667781;">
                                    <h3>👫 Aucun groupe</h3>
                                    <p>Vous ne participez à aucun groupe.</p>
                                    <a href="groups.php?action=create" class="btn btn-primary">
                                        ➕ Créer votre premier groupe
                                    </a>
                                </div>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($groups as $group): ?>
                                        <?php 
                                        $memberCount = 0;
                                        $isAdmin = false;
                                        try {
                                            $members = $groupRepo->getGroupMembers($group->getId());
                                            $memberCount = count($members);
                                            $isAdmin = $groupRepo->isUserAdminOfGroup($group->getId(), $_SESSION['user_id']);
                                        } catch (Exception $e) {
                                            // Erreur lors du chargement des membres
                                        }
                                        ?>
                                        <li class="list-group-item">
                                            <div>
                                                <strong><?= htmlspecialchars($group->getName()) ?></strong>
                                                <?= $isAdmin ? ' <span style="color: #00a884;">👑</span>' : '' ?>
                                                <br>
                                                <small style="color: #667781;">
                                                    <?= htmlspecialchars($group->getDescription() ?: 'Aucune description') ?>
                                                    <br>
                                                    👥 <?= $memberCount ?> membre(s)
                                                    <?= $isAdmin ? ' • Vous êtes administrateur' : ' • Vous êtes membre' ?>
                                                </small>
                                            </div>
                                            <div>
                                                <a href="chat.php?group_id=<?= htmlspecialchars($group->getId()) ?>" 
                                                   class="btn btn-primary btn-sm" 
                                                   title="Envoyer un message">
                                                    💬
                                                </a>
                                                <a href="groups.php?action=manage&id=<?= htmlspecialchars($group->getId()) ?>" 
                                                   class="btn btn-secondary btn-sm" 
                                                   title="Gérer le groupe">
                                                    ⚙️
                                                </a>
                                                <?php if ($isAdmin): ?>
                                                    <button 
                                                        onclick="confirmDelete('groupe', '<?= htmlspecialchars($group->getId()) ?>', '<?= htmlspecialchars($group->getName()) ?>')" 
                                                        class="btn btn-danger btn-sm" 
                                                        title="Supprimer le groupe">
                                                        🗑️
                                                    </button>
                                                <?php else: ?>
                                                    <button 
                                                        onclick="if(confirm('Quitter le groupe <?= htmlspecialchars($group->getName()) ?> ?')) { window.location.href='groups.php?action=leave&id=<?= htmlspecialchars($group->getId()) ?>'; }" 
                                                        class="btn btn-secondary btn-sm" 
                                                        title="Quitter le groupe">
                                                        🚪
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Statistiques des groupes -->
                    <div class="card">
                        <div class="card-header">
                            📊 Statistiques de mes groupes
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; text-align: center;">
                                <div>
                                    <strong style="color: #00a884; font-size: 24px;"><?= count($groups) ?></strong>
                                    <br><small>Total groupes</small>
                                </div>
                                <div>
                                    <strong style="color: #00a884; font-size: 24px;">
                                        <?php
                                        $adminGroups = 0;
                                        foreach ($groups as $group) {
                                            try {
                                                if ($groupRepo->isUserAdminOfGroup($group->getId(), $_SESSION['user_id'])) {
                                                    $adminGroups++;
                                                }
                                            } catch (Exception $e) {
                                                // Ignorer les erreurs
                                            }
                                        }
                                        echo $adminGroups;
                                        ?>
                                    </strong>
                                    <br><small>Groupes administrés</small>
                                </div>
                                <div>
                                    <strong style="color: #00a884; font-size: 24px;">
                                        <?php
                                        $totalMembers = 0;
                                        foreach ($groups as $group) {
                                            try {
                                                $members = $groupRepo->getGroupMembers($group->getId());
                                                $totalMembers += count($members);
                                            } catch (Exception $e) {
                                                // Ignorer les erreurs
                                            }
                                        }
                                        echo $totalMembers;
                                        ?>
                                    </strong>
                                    <br><small>Total membres</small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Fonction de suppression spécifique aux groupes
        function confirmDelete(itemType, itemId, itemName) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer le ${itemType} "${itemName}" ?\n\nCette action est irréversible et supprimera tous les messages du groupe.`)) {
                window.location.href = `groups.php?action=delete&id=${itemId}`;
            }
        }
    </script>
    <script>
    // Debug JavaScript pour tester le modal
    console.log('Script de debug modal chargé');
    
    // Tester si les fonctions existent
    function testModal() {
        console.log('Test du modal...');
        
        // Vérifier que les fonctions existent
        if (typeof openGroupModal === 'function') {
            console.log('✅ openGroupModal existe');
        } else {
            console.log('❌ openGroupModal n\'existe pas');
        }
        
        if (typeof closeGroupModal === 'function') {
            console.log('✅ closeGroupModal existe');
        } else {
            console.log('❌ closeGroupModal n\'existe pas');
        }
        
        // Vérifier que le modal existe
        const modal = document.getElementById('group-modal');
        if (modal) {
            console.log('✅ Modal DOM element trouvé');
        } else {
            console.log('❌ Modal DOM element non trouvé');
        }
    }
    
    // Fonction obsolète supprimée - utilisation de openGroupModal() améliorée
    
    // Intercepter la soumission du formulaire
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM chargé, configuration des listeners...');
        
        const form = document.querySelector('#group-modal form');
        if (form) {
            console.log('✅ Formulaire trouvé');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                console.log('📤 Soumission du formulaire détectée');
                
                // Valider le formulaire
                if (!validateForm(form)) {
                    return;
                }
                
                // Utiliser la nouvelle fonction AJAX
                submitGroupForm(form, 'add_member');
            });
        } else {
            console.log('❌ Formulaire non trouvé');
        }
        
        // Tester le modal
        testModal();
    });
    
    // Remplacer temporairement le onclick du bouton
    document.addEventListener('DOMContentLoaded', function() {
        const addButton = document.querySelector('button[onclick="openGroupModal()"]');
        if (addButton) {
            console.log('✅ Bouton "Ajouter un membre" trouvé');
            addButton.onclick = function() {
                console.log('🔘 Bouton "Ajouter un membre" cliqué');
                openGroupModal(); // Utiliser la fonction améliorée
            };
        } else {
            console.log('❌ Bouton "Ajouter un membre" non trouvé');
        }
        
        // Test du modal au chargement
        console.log('Test du modal au chargement...');
        const modal = document.getElementById('group-modal');
        if (modal) {
            console.log('Modal trouvé au chargement');
            console.log('État initial du modal:');
            console.log('- Display:', getComputedStyle(modal).display);
            console.log('- Opacity:', getComputedStyle(modal).opacity);
            console.log('- Z-index:', getComputedStyle(modal).zIndex);
            console.log('- Position:', getComputedStyle(modal).position);
        } else {
            console.log('❌ Modal non trouvé au chargement');
        }
    });
    
    // Code de débogage retiré - utilisation de la soumission AJAX normale
    </script>
</body>
</html> 