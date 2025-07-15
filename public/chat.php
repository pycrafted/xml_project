<?php
/**
 * WhatsApp Web Clone - Interface de Chat
 * Interface principale de messagerie avec conversations en temps réel
 */

require_once '../vendor/autoload.php';

use WhatsApp\Services\UserService;
use WhatsApp\Services\MessageService;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;
use WhatsApp\Repositories\MessageRepository;

session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Initialisation des services
$xmlManager = new WhatsApp\Utils\XMLManager();
$userService = new UserService($xmlManager);
$messageService = new MessageService($xmlManager);
$contactRepo = new ContactRepository($xmlManager);
$groupRepo = new GroupRepository($xmlManager);
$messageRepo = new MessageRepository($xmlManager);

// Variables
$currentUser = $userService->findUserById($_SESSION['user_id']);
$pageTitle = "Chat - WhatsApp Web";
$error = '';
$success = '';

// Paramètres de conversation
$contactId = $_GET['contact_id'] ?? '';
$groupId = $_GET['group_id'] ?? '';
$activeConversation = null;
$conversationType = '';
$conversationName = '';

// Déterminer le type de conversation active
if ($contactId) {
    try {
        $contact = $contactRepo->getContactById($contactId);
        if ($contact && $contact->getUserId() === $_SESSION['user_id']) {
            $activeConversation = $contact;
            $conversationType = 'contact';
            $conversationName = $contact->getName();
        }
    } catch (Exception $e) {
        $error = "Contact non trouvé";
    }
} elseif ($groupId) {
    try {
        $group = $groupRepo->getGroupById($groupId);
        $members = $groupRepo->getGroupMembers($groupId);
        $isMember = false;
        foreach ($members as $userId => $role) {
            if ($userId === $_SESSION['user_id']) {
                $isMember = true;
                break;
            }
        }
        if ($group && $isMember) {
            $activeConversation = $group;
            $conversationType = 'group';
            $conversationName = $group->getName();
        }
    } catch (Exception $e) {
        $error = "Groupe non trouvé";
    }
}

// Traitement POST supprimé - Les messages sont maintenant envoyés uniquement via AJAX

// Récupération des conversations et messages
$contacts = [];
$groups = [];
$messages = [];

try {
    $contacts = $contactRepo->getContactsByUserId($_SESSION['user_id']);
    $groups = $groupRepo->getGroupsByUserId($_SESSION['user_id']);
    
    if ($activeConversation) {
        if ($conversationType === 'contact') {
            $messages = $messageService->getConversation($_SESSION['user_id'], $activeConversation->getContactUserId());
        } else {
            $messages = $messageRepo->getGroupMessages($activeConversation->getId());
        }
    }
} catch (Exception $e) {
    $error = "Erreur lors du chargement des données : " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Styles spécifiques au chat */
        .chat-layout {
            display: flex;
            height: 100vh;
        }
        
        .conversation-sidebar {
            width: 350px;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
        }
        
        .conversation-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .conversation-item:hover {
            background: #e9ecef;
        }
        
        .conversation-item.active {
            background: #00a884;
            color: white;
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-placeholder {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667781;
            text-align: center;
            background: #e5ddd5;
        }
        
        .message-form {
            padding: 15px 20px;
            background: #f0f2f5;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container chat-layout">
        <!-- Sidebar des conversations -->
        <div class="conversation-sidebar">
            <div class="sidebar-header">
                <h3>💬 Conversations</h3>
                <p><?= htmlspecialchars($currentUser ? $currentUser->getName() : 'Utilisateur') ?></p>
            </div>
            
            <!-- Navigation rapide -->
            <nav class="sidebar-nav" style="padding: 10px 0;">
                <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
                <a href="contacts.php" class="nav-item">👥 Contacts</a>
                <a href="groups.php" class="nav-item">👫 Groupes</a>
                <a href="profile.php" class="nav-item">⚙️ Profil</a>
                <a href="index.php?action=logout" class="nav-item" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">🚪 Déconnexion</a>
            </nav>
            
            <!-- Liste des conversations -->
            <div class="conversation-list">
                <div style="padding: 15px 20px; background: #e9ecef;">
                    <strong>📱 Contacts</strong>
                </div>
                
                <?php if (empty($contacts)): ?>
                    <div style="padding: 20px; text-align: center; color: #667781;">
                        <p>Aucun contact</p>
                        <a href="contacts.php?action=create" class="btn btn-primary btn-sm">Ajouter</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): ?>
                        <?php 
                        $isActive = ($contactId === $contact->getId());
                        $contactUser = null;
                        try {
                            $contactUser = $userService->findUserById($contact->getContactUserId());
                        } catch (Exception $e) {
                            // Utilisateur non trouvé
                        }
                        ?>
                        <div class="conversation-item <?= $isActive ? 'active' : '' ?>" 
                             onclick="window.location.href='chat.php?contact_id=<?= htmlspecialchars($contact->getId()) ?>'">
                            <strong><?= htmlspecialchars($contact->getName()) ?></strong>
                            <br>
                            <small style="<?= $isActive ? '' : 'color: #667781;' ?>">
                                <?= $contactUser ? htmlspecialchars($contactUser->getStatus() ?: 'En ligne') : 'Hors ligne' ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div style="padding: 15px 20px; background: #e9ecef; margin-top: 10px;">
                    <strong>👫 Groupes</strong>
                </div>
                
                <?php if (empty($groups)): ?>
                    <div style="padding: 20px; text-align: center; color: #667781;">
                        <p>Aucun groupe</p>
                        <a href="groups.php?action=create" class="btn btn-primary btn-sm">Créer</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($groups as $group): ?>
                        <?php 
                        $isActive = ($groupId === $group->getId());
                        $memberCount = 0;
                        try {
                            $members = $groupRepo->getGroupMembers($group->getId());
                            $memberCount = count($members);
                        } catch (Exception $e) {
                            // Erreur
                        }
                        ?>
                        <div class="conversation-item <?= $isActive ? 'active' : '' ?>" 
                             onclick="window.location.href='chat.php?group_id=<?= htmlspecialchars($group->getId()) ?>'">
                            <strong><?= htmlspecialchars($group->getName()) ?></strong>
                            <br>
                            <small style="<?= $isActive ? '' : 'color: #667781;' ?>">
                                👥 <?= $memberCount ?> membre(s)
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Zone de chat principale -->
        <div class="chat-main">
            <?php if (!$activeConversation): ?>
                <!-- Placeholder quand aucune conversation n'est sélectionnée -->
                <div class="chat-placeholder">
                    <div>
                        <h2>💬 WhatsApp Web</h2>
                        <p>Sélectionnez une conversation pour commencer à discuter</p>
                        <br>
                        <a href="contacts.php?action=create" class="btn btn-primary">Ajouter un contact</a>
                        <a href="groups.php?action=create" class="btn btn-secondary">Créer un groupe</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Interface de chat active -->
                <div class="chat-container">
                    <!-- En-tête de conversation -->
                    <div class="chat-header">
                        <div>
                            <strong><?= htmlspecialchars($conversationName) ?></strong>
                            <br>
                            <small style="color: #667781;">
                                <?php if ($conversationType === 'contact'): ?>
                                    <?php 
                                    try {
                                        $contactUser = $userService->findUserById($activeConversation->getContactUserId());
                                        echo htmlspecialchars($contactUser ? $contactUser->getStatus() ?: 'En ligne' : 'Hors ligne');
                                    } catch (Exception $e) {
                                        echo 'Statut inconnu';
                                    }
                                    ?>
                                <?php else: ?>
                                    <?php 
                                    try {
                                        $members = $groupRepo->getGroupMembers($activeConversation->getId());
                                        echo count($members) . ' membre(s)';
                                    } catch (Exception $e) {
                                        echo 'Groupe';
                                    }
                                    ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div>
                            <?php if ($conversationType === 'group'): ?>
                                <a href="groups.php?action=manage&id=<?= htmlspecialchars($activeConversation->getId()) ?>" 
                                   class="btn btn-secondary btn-sm">
                                    ⚙️ Gérer
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Zone des messages -->
                    <div class="chat-messages" id="chat-messages">
                        <?php if (empty($messages)): ?>
                            <div style="text-align: center; padding: 40px 0; color: #667781;">
                                <p>Aucun message dans cette conversation.</p>
                                <p>Envoyez le premier message ! 👇</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <?php 
                                $isSent = ($message->getFromUserId() === $_SESSION['user_id']);
                                $senderName = '';
                                if (!$isSent) {
                                    try {
                                        $sender = $userService->findUserById($message->getFromUserId());
                                        $senderName = $sender ? $sender->getName() : 'Utilisateur inconnu';
                                    } catch (Exception $e) {
                                        $senderName = 'Utilisateur inconnu';
                                    }
                                }
                                ?>
                                <div class="message <?= $isSent ? 'sent' : 'received' ?>">
                                    <?php if (!$isSent && $conversationType === 'group'): ?>
                                        <div style="font-size: 12px; color: #00a884; margin-bottom: 5px;">
                                            <?= htmlspecialchars($senderName) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="message-content">
                                        <?php if ($message->getType() === 'file'): ?>
                                            <?php if ($message->getFilePath()): ?>
                                                <div class="file-message">
                                                    <?php if ($message->isImage()): ?>
                                                        <!-- Prévisualisation image -->
                                                        <div style="margin-bottom: 8px;">
                                                            <img src="<?= htmlspecialchars($message->getFilePath()) ?>" 
                                                                 alt="<?= htmlspecialchars($message->getFileName() ?: 'Image') ?>"
                                                                 style="max-width: 250px; max-height: 200px; border-radius: 8px; cursor: pointer;"
                                                                 onclick="window.open('<?= htmlspecialchars($message->getFilePath()) ?>', '_blank')">
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Informations du fichier -->
                                                    <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                                                        <span style="font-size: 20px;">
                                                            <?php
                                                            if ($message->isImage()) {
                                                                echo '🖼️';
                                                            } elseif (strpos($message->getFileName() ?: '', '.pdf') !== false) {
                                                                echo '📄';
                                                            } elseif (strpos($message->getFileName() ?: '', '.doc') !== false) {
                                                                echo '📝';
                                                            } else {
                                                                echo '📎';
                                                            }
                                                            ?>
                                                        </span>
                                                        <div style="flex: 1;">
                                                            <div style="font-weight: bold; font-size: 13px;">
                                                                <?= htmlspecialchars($message->getFileName() ?: 'Fichier') ?>
                                                            </div>
                                                            <div style="font-size: 11px; color: #8696a0;">
                                                                <?= htmlspecialchars($message->getFormattedFileSize()) ?>
                                                            </div>
                                                        </div>
                                                        <a href="download.php?file=<?= urlencode($message->getFilePath()) ?>&message=<?= urlencode($message->getId()) ?>" 
                                                           style="color: #00a884; text-decoration: none; font-size: 12px; padding: 5px 10px; border: 1px solid #00a884; border-radius: 5px;">
                                                            📥 Télécharger
                                                        </a>
                                                    </div>
                                                    
                                                    <!-- Texte accompagnant le fichier -->
                                                    <?php if ($message->getContent() && $message->getContent() !== $message->getFileName()): ?>
                                                        <div style="margin-top: 8px; font-size: 14px;">
                                                            <?= nl2br(htmlspecialchars($message->getContent())) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                📎 <em>Fichier : <?= htmlspecialchars($message->getContent()) ?></em>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?= nl2br(htmlspecialchars($message->getContent())) ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="message-time">
                                        <?= $message->getTimestamp() ? date('H:i', strtotime($message->getTimestamp())) : 'Maintenant' ?>
                                        <?php if ($isSent): ?>
                                            <span style="color: #00a884;">
                                                <?php
                                                switch ($message->getStatus()) {
                                                    case 'sent': echo '✓'; break;
                                                    case 'delivered': echo '✓✓'; break;
                                                    case 'read': echo '✓✓'; break;
                                                    default: echo '⏳';
                                                }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Formulaire d'envoi -->
                    <div class="chat-input">
                        <?php if ($error): ?>
                            <div class="alert alert-error" style="margin: 0 0 10px 0; padding: 10px;">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form id="chat-form" style="display: flex; gap: 10px; width: 100%; align-items: flex-end;">
                            <input type="hidden" id="conversation-id" value="<?= $conversationType ?>_<?= htmlspecialchars($activeConversation->getId()) ?>">
                            
                            <?php if ($conversationType === 'contact'): ?>
                                <input type="hidden" id="recipient-id" value="<?= htmlspecialchars($activeConversation->getContactUserId()) ?>">
                            <?php endif; ?>
                            
                            <!-- Bouton de sélection de fichier -->
                            <div style="position: relative;">
                                <input 
                                    type="file" 
                                    id="file-input" 
                                    name="file"
                                    style="display: none;"
                                    accept="image/jpeg,image/jpg,image/png,image/gif,image/bmp,image/webp,image/svg+xml,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain,text/rtf,application/zip,application/x-rar-compressed,audio/mpeg,audio/mp3,video/mp4,video/avi,video/quicktime,video/x-ms-wmv"
                                >
                                <button type="button" id="file-button" style="background: #667781; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; display: flex; align-items: center; justify-content: center;" title="Joindre un fichier">
                                    📎
                                </button>
                            </div>
                            
                            <!-- Zone de prévisualisation du fichier -->
                            <div id="file-preview" style="display: none; background: #f0f2f5; padding: 10px; border-radius: 10px; margin-right: 10px; max-width: 200px;">
                                <div id="file-info" style="font-size: 12px; color: #667781;"></div>
                                <div style="margin-top: 5px;">
                                    <button type="button" id="remove-file" style="background: #dc3545; color: white; border: none; border-radius: 5px; padding: 2px 8px; font-size: 11px; cursor: pointer;">
                                        ✕ Supprimer
                                    </button>
                                </div>
                            </div>
                            
                            <input 
                                type="text" 
                                id="message-input" 
                                placeholder="Tapez votre message..."
                                style="flex: 1;"
                            >
                            
                            <button type="submit" id="send-button" style="background: #00a884; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer;">
                                ➤
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Auto-scroll vers le bas lors du chargement
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                scrollToBottom(chatMessages);
            }
            
            // Focus sur l'input de message
            const messageInput = document.getElementById('message-input');
            if (messageInput) {
                messageInput.focus();
            }
        });

        // Event listener supprimé - géré dans app.js pour éviter le double envoi

        // Initialiser la conversation active
        if (typeof resetCurrentConversation === 'function') {
            resetCurrentConversation();
        }
        
        // Charger les messages initiaux
            const conversationId = document.getElementById('conversation-id');
        if (conversationId && conversationId.value && typeof loadMessages === 'function') {
            loadMessages(conversationId.value);
            }

        // Gérer la touche Entrée pour envoyer
        document.getElementById('message-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('chat-form').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html> 
</html> 