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
        foreach ($members as $member) {
            if ($member['user_id'] === $_SESSION['user_id']) {
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

// Gestion de l'envoi de messages
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $content = trim($_POST['content'] ?? '');
    $type = $_POST['type'] ?? 'text';
    
    if (empty($content)) {
        $error = "Le message ne peut pas être vide";
    } elseif (!$activeConversation) {
        $error = "Aucune conversation sélectionnée";
    } else {
        try {
            if ($conversationType === 'contact') {
                // Message privé
                $messageService->sendPrivateMessage(
                    $_SESSION['user_id'],
                    $activeConversation->getContactUserId(),
                    $content,
                    $type
                );
            } else {
                // Message de groupe
                $messageService->sendGroupMessage(
                    $_SESSION['user_id'],
                    $activeConversation->getId(),
                    $content,
                    $type
                );
            }
            $success = "Message envoyé";
        } catch (Exception $e) {
            $error = "Erreur lors de l'envoi : " . $e->getMessage();
        }
    }
}

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
                                            📎 <em>Fichier : <?= htmlspecialchars($message->getContent()) ?></em>
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
                        
                        <form method="POST" id="chat-form" style="display: flex; gap: 10px; width: 100%;">
                            <input type="hidden" name="action" value="send_message">
                            <input type="hidden" name="type" value="text">
                            <input type="hidden" id="conversation-id" value="<?= $conversationType ?>_<?= htmlspecialchars($activeConversation->getId()) ?>">
                            
                            <input 
                                type="text" 
                                name="content" 
                                id="message-input" 
                                placeholder="Tapez votre message..."
                                required
                                style="flex: 1;"
                            >
                            
                            <button type="submit" style="background: #00a884; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer;">
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

        // Soumission du formulaire de chat
        document.getElementById('chat-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageInput = document.getElementById('message-input');
            const content = messageInput.value.trim();
            
            if (!content) {
                showAlert('Veuillez saisir un message', 'error');
                return;
            }
            
            // Créer un nouveau FormData pour préserver le contenu
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('content', content);
            formData.append('type', 'text');
            
            // Soumettre via POST classique
            const form = this;
            
            // Créer un input hidden temporaire pour le contenu
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'content';
            hiddenInput.value = content;
            form.appendChild(hiddenInput);
            
            // Vider le champ d'interface immédiatement pour l'UX
            messageInput.value = '';
            
            // Ajouter le message optimiste à l'interface
            addMessageToChat({
                content: content,
                timestamp: new Date().toLocaleTimeString()
            }, 'sent');
            
            const chatMessages = document.getElementById('chat-messages');
            scrollToBottom(chatMessages);
            
            // Soumettre le formulaire
            form.submit();
        });

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