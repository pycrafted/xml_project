<?php
/**
 * WhatsApp Web Clone - API AJAX
 * Gestionnaire des requêtes AJAX pour fonctionnalités temps réel
 */

require_once '../vendor/autoload.php';

use WhatsApp\Services\UserService;
use WhatsApp\Services\MessageService;
use WhatsApp\Services\FileUploadService;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;
use WhatsApp\Repositories\MessageRepository;

session_start();

// Configuration des headers pour AJAX
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Fonction de logging simple
function debugLog($message) {
    file_put_contents('../logs/app.log', date('Y-m-d H:i:s') . " [DEBUG] " . $message . "\n", FILE_APPEND);
}

debugLog("AJAX Request: " . json_encode([
    'action' => $_GET['action'] ?? $_POST['action'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'],
    'user_id' => $_SESSION['user_id'] ?? 'anonymous'
]));

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    logWarning("Non authentifié", ['IP' => $_SERVER['REMOTE_ADDR']]);
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Initialisation des services
$xmlManager = new WhatsApp\Utils\XMLManager();
$userService = new UserService($xmlManager);
$messageService = new MessageService($xmlManager);
$fileUploadService = new FileUploadService();
$contactRepo = new ContactRepository($xmlManager);
$groupRepo = new GroupRepository($xmlManager);
$messageRepo = new MessageRepository($xmlManager);

// Récupération de l'action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        
        // ===========================================
        // GESTION DES MESSAGES
        // ===========================================
        
        case 'send_message':
            debugLog("Send message request: " . json_encode([
                'user_id' => $_SESSION['user_id'],
                'content_length' => strlen($_POST['content'] ?? ''),
                'recipient_id' => $_POST['recipient_id'] ?? '',
                'type' => $_POST['type'] ?? 'text',
                'has_file' => isset($_FILES['file']) ? 'yes' : 'no'
            ]));
            
            $content = trim($_POST['content'] ?? '');
            $recipientId = $_POST['recipient_id'] ?? '';
            $type = $_POST['type'] ?? 'text';
            $fileInfo = null;
            
            // Gérer l'upload de fichier si présent
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                try {
                    $fileInfo = $fileUploadService->uploadFile($_FILES['file'], $_SESSION['user_id']);
                    $type = 'file';
                    
                    // Si pas de contenu texte, utiliser le nom du fichier
                    if (empty($content)) {
                        $content = $fileInfo['file_name'];
                    }
                    
                    debugLog("File uploaded for message: " . $fileInfo['file_path']);
                } catch (Exception $e) {
                    debugLog("ERROR: File upload failed - " . $e->getMessage());
                    throw new Exception('Erreur d\'upload : ' . $e->getMessage());
                }
            }
            
            // Vérifier que le message n'est pas vide (texte ou fichier)
            if (empty($content) && !$fileInfo) {
                debugLog("WARNING: Message vide - user_id: " . $_SESSION['user_id']);
                throw new Exception('Le message ne peut pas être vide');
            }
            
            if (empty($recipientId)) {
                debugLog("WARNING: Destinataire non spécifié - user_id: " . $_SESSION['user_id']);
                throw new Exception('Destinataire non spécifié');
            }
            
            debugLog("Tentative d'envoi de message - from: " . $_SESSION['user_id'] . " to: " . $recipientId . " type: " . $type);
            
            $message = $messageService->sendPrivateMessage($_SESSION['user_id'], $recipientId, $content, $type);
            
            // Ajouter les informations du fichier si présent
            if ($fileInfo) {
                $message->setFilePath($fileInfo['file_path']);
                $message->setFileName($fileInfo['file_name']);
                $message->setFileSize($fileInfo['file_size']);
                
                // Mettre à jour le message dans la base
                $messageRepo->update($message);
            }
            
            debugLog("SUCCESS: Message envoyé - message_id: " . $message->getId() . " from: " . $_SESSION['user_id'] . " to: " . $recipientId);
            
            $response = [
                'success' => true,
                'message' => [
                    'id' => $message->getId(),
                    'content' => $content,
                    'type' => $type,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'from_user_id' => $_SESSION['user_id']
                ]
            ];
            
            // Ajouter les informations du fichier à la réponse
            if ($fileInfo) {
                $response['message']['file'] = [
                    'path' => $fileInfo['file_path'],
                    'name' => $fileInfo['file_name'],
                    'size' => $fileInfo['file_size'],
                    'formatted_size' => $message->getFormattedFileSize(),
                    'is_image' => $message->isImage()
                ];
            }
            
            echo json_encode($response);
            break;
            
        case 'send_group_message':
            debugLog("Send group message request: " . json_encode([
                'user_id' => $_SESSION['user_id'],
                'content_length' => strlen($_POST['content'] ?? ''),
                'group_id' => $_POST['group_id'] ?? '',
                'type' => $_POST['type'] ?? 'text',
                'has_file' => isset($_FILES['file']) ? 'yes' : 'no'
            ]));
            
            $content = trim($_POST['content'] ?? '');
            $groupId = $_POST['group_id'] ?? '';
            $type = $_POST['type'] ?? 'text';
            $fileInfo = null;
            
            // Gérer l'upload de fichier si présent
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                try {
                    $fileInfo = $fileUploadService->uploadFile($_FILES['file'], $_SESSION['user_id']);
                    $type = 'file';
                    
                    // Si pas de contenu texte, utiliser le nom du fichier
                    if (empty($content)) {
                        $content = $fileInfo['file_name'];
                    }
                    
                    debugLog("File uploaded for group message: " . $fileInfo['file_path']);
                } catch (Exception $e) {
                    debugLog("ERROR: File upload failed - " . $e->getMessage());
                    throw new Exception('Erreur d\'upload : ' . $e->getMessage());
                }
            }
            
            // Vérifier que le message n'est pas vide (texte ou fichier)
            if (empty($content) && !$fileInfo) {
                throw new Exception('Le message ne peut pas être vide');
            }
            
            if (empty($groupId)) {
                throw new Exception('Groupe non spécifié');
            }
            
            $message = $messageService->sendGroupMessage($_SESSION['user_id'], $groupId, $content, $type);
            
            // Ajouter les informations du fichier si présent
            if ($fileInfo) {
                $message->setFilePath($fileInfo['file_path']);
                $message->setFileName($fileInfo['file_name']);
                $message->setFileSize($fileInfo['file_size']);
                
                // Mettre à jour le message dans la base
                $messageRepo->update($message);
            }
            
            // Récupérer le nom de l'expéditeur pour les messages de groupe
            $senderName = '';
            try {
                $sender = $userService->findUserById($_SESSION['user_id']);
                $senderName = $sender ? $sender->getName() : 'Utilisateur inconnu';
            } catch (Exception $e) {
                $senderName = 'Utilisateur inconnu';
            }
            
            debugLog("SUCCESS: Group message sent - message_id: " . $message->getId() . " group_id: " . $groupId);
            
            $response = [
                'success' => true,
                'message' => [
                    'id' => $message->getId(),
                    'content' => $content,
                    'type' => $type,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'from_user_id' => $_SESSION['user_id'],
                    'to_group_id' => $groupId,
                    'sender_name' => $senderName,
                    'is_sent' => true
                ]
            ];
            
            // Ajouter les informations du fichier à la réponse
            if ($fileInfo) {
                $response['message']['file'] = [
                    'path' => $fileInfo['file_path'],
                    'name' => $fileInfo['file_name'],
                    'size' => $fileInfo['file_size'],
                    'formatted_size' => $message->getFormattedFileSize(),
                    'is_image' => $message->isImage()
                ];
            }
            
            echo json_encode($response);
            break;
            
        case 'get_messages':
            $conversationId = $_GET['conversation_id'] ?? '';
            $lastMessageId = $_GET['last_message_id'] ?? '';
            
            if (empty($conversationId)) {
                throw new Exception('ID de conversation requis');
            }
            
            // Parser l'ID de conversation (format: type_id)
            $type = null;
            $id = null;
            
            if (strpos($conversationId, 'contact_') === 0) {
                $type = 'contact';
                $id = substr($conversationId, 8); // Enlever "contact_"
            } elseif (strpos($conversationId, 'group_') === 0) {
                $type = 'group';
                $id = substr($conversationId, 6); // Enlever "group_"
            } else {
                throw new Exception('Format de conversation invalide');
            }
            $messages = [];
            
            if ($type === 'contact') {
                $contact = $contactRepo->getContactById($id);
                if ($contact && $contact->getUserId() === $_SESSION['user_id']) {
                    $messages = $messageService->getConversation($_SESSION['user_id'], $contact->getContactUserId());
                }
            } elseif ($type === 'group') {
                $group = $groupRepo->getGroupById($id);
                $members = $groupRepo->getGroupMembers($id);
                $isMember = false;
                foreach ($members as $userId => $role) {
                    if ($userId === $_SESSION['user_id']) {
                        $isMember = true;
                        break;
                    }
                }
                if ($group && $isMember) {
                    $messages = $messageRepo->getGroupMessages($id);
                }
            }
            
            // Formater les messages pour AJAX
            $formattedMessages = [];
            foreach ($messages as $message) {
                $messageData = [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'type' => $message->getType(),
                    'timestamp' => $message->getTimestamp(),
                    'from_user_id' => $message->getFromUserId(),
                    'is_sent' => ($message->getFromUserId() === $_SESSION['user_id'])
                ];
                
                // Ajouter le nom de l'expéditeur pour les messages de groupe
                if ($type === 'group' && !$messageData['is_sent']) {
                    try {
                        $sender = $userService->findUserById($message->getFromUserId());
                        $messageData['sender_name'] = $sender ? $sender->getName() : 'Utilisateur inconnu';
                    } catch (Exception $e) {
                        $messageData['sender_name'] = 'Utilisateur inconnu';
                    }
                }
                
                // Ajouter les informations du fichier si c'est un message avec fichier
                if ($message->getType() === 'file' && $message->getFilePath()) {
                    $messageData['file'] = [
                        'path' => $message->getFilePath(),
                        'name' => $message->getFileName() ?: basename($message->getFilePath()),
                        'size' => $message->getFileSize() ?: 0,
                        'formatted_size' => $message->getFormattedFileSize(),
                        'is_image' => $message->isImage(),
                        'extension' => $message->getFileExtension()
                    ];
                }
                
                $formattedMessages[] = $messageData;
            }
            
            echo json_encode([
                'success' => true,
                'messages' => $formattedMessages
            ]);
            break;
            
        // ===========================================
        // STATISTIQUES EN TEMPS RÉEL
        // ===========================================
        
        case 'get_stats':
            $contactCount = count($contactRepo->getContactsByUserId($_SESSION['user_id']));
            $groupCount = count($groupRepo->getGroupsByUserId($_SESSION['user_id']));
            
            $userMessages = $messageRepo->getMessagesByUserId($_SESSION['user_id']);
            $messagesSent = 0;
            $messagesReceived = 0;
            
            foreach ($userMessages as $message) {
                if ($message->getFromUserId() === $_SESSION['user_id']) {
                    $messagesSent++;
                } else {
                    $messagesReceived++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'contacts' => $contactCount,
                    'groups' => $groupCount,
                    'messages_sent' => $messagesSent,
                    'messages_received' => $messagesReceived,
                    'total_messages' => count($userMessages)
                ]
            ]);
            break;
            
        case 'get_global_stats':
            $globalStats = $userService->getUserStats();
            echo json_encode([
                'success' => true,
                'stats' => $globalStats
            ]);
            break;
            
        // ===========================================
        // GESTION DES UTILISATEURS
        // ===========================================
        
        case 'search_users':
            $query = trim($_GET['q'] ?? '');
            if (strlen($query) < 2) {
                throw new Exception('Requête trop courte (minimum 2 caractères)');
            }
            
            $allUsers = $userService->getAllUsers();
            $results = [];
            
            foreach ($allUsers as $user) {
                if ($user->getId() !== $_SESSION['user_id']) {
                    $name = strtolower($user->getName());
                    $email = strtolower($user->getEmail());
                    $searchQuery = strtolower($query);
                    
                    if (strpos($name, $searchQuery) !== false || strpos($email, $searchQuery) !== false) {
                        $results[] = [
                            'id' => $user->getId(),
                            'name' => $user->getName(),
                            'email' => $user->getEmail(),
                            'status' => $user->getStatus()
                        ];
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'users' => array_slice($results, 0, 10) // Limiter à 10 résultats
            ]);
            break;
            
        case 'get_user_status':
            $userId = $_GET['user_id'] ?? '';
            if (empty($userId)) {
                throw new Exception('ID utilisateur requis');
            }
            
            $user = $userService->findUserById($userId);
            if (!$user) {
                throw new Exception('Utilisateur non trouvé');
            }
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'status' => $user->getStatus() ?: 'En ligne',
                    'last_activity' => date('Y-m-d H:i:s') // Simulé
                ]
            ]);
            break;
            
        // ===========================================
        // GESTION DES GROUPES
        // ===========================================
        
        case 'add_member':
        case 'add_member_to_group':
            $groupId = $_POST['group_id'] ?? '';
            $contactId = $_POST['contact_id'] ?? '';
            $userId = $_POST['user_id'] ?? '';
            $role = $_POST['role'] ?? 'member';
            
            if (!$groupId) {
                throw new Exception('ID du groupe requis');
            }
            
            // Vérifier si l'utilisateur est admin du groupe
            $isAdmin = $groupRepo->isUserAdminOfGroup($groupId, $_SESSION['user_id']);
            if (!$isAdmin) {
                throw new Exception('Seuls les administrateurs peuvent ajouter des membres');
            }
            
            // Si on a un user_id directement, l'utiliser
            if ($userId) {
                $result = $groupRepo->addMemberToGroup($groupId, $userId, $role);
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Membre ajouté au groupe avec succès'
                    ]);
                } else {
                    throw new Exception('Erreur lors de l\'ajout du membre (peut-être déjà membre du groupe)');
                }
            }
            // Sinon, utiliser l'ancien système avec contact_id
            elseif ($contactId) {
                $contact = $contactRepo->getContactById($contactId);
                if (!$contact) {
                    throw new Exception('Contact non trouvé');
                }
                
                $result = $groupRepo->addMemberToGroup($groupId, $contact->getContactUserId(), $role);
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Membre ajouté au groupe avec succès'
                    ]);
                } else {
                    throw new Exception('Erreur lors de l\'ajout du membre (peut-être déjà membre du groupe)');
                }
            } else {
                throw new Exception('ID du contact ou de l\'utilisateur requis');
            }
            break;
            
        case 'remove_member':
        case 'remove_member_from_group':
            $groupId = $_POST['group_id'] ?? '';
            $memberId = $_POST['member_id'] ?? '';
            
            if (!$groupId || !$memberId) {
                throw new Exception('ID du groupe et du membre requis');
            }
            
            // Vérifier si l'utilisateur est admin du groupe
            $isAdmin = $groupRepo->isUserAdminOfGroup($groupId, $_SESSION['user_id']);
            if (!$isAdmin) {
                throw new Exception('Seuls les administrateurs peuvent retirer des membres');
            }
            
            $result = $groupRepo->removeMemberFromGroup($groupId, $memberId);
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Membre retiré du groupe avec succès'
                ]);
            } else {
                throw new Exception('Erreur lors de la suppression du membre');
            }
            break;
            
        case 'get_group_members':
            $groupId = $_GET['group_id'] ?? '';
            
            if (!$groupId) {
                throw new Exception('ID du groupe requis');
            }
            
            $group = $groupRepo->getGroupById($groupId);
            if (!$group) {
                throw new Exception('Groupe non trouvé');
            }
            
            $members = [];
            foreach ($group->getMembers() as $userId => $role) {
                $user = $userService->findUserById($userId);
                if ($user) {
                    $members[] = [
                        'id' => $userId,
                        'name' => $user->getName(),
                        'email' => $user->getEmail(),
                        'role' => $role
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'members' => $members
            ]);
            break;

        // ===========================================
        // GESTION DES NOTIFICATIONS
        // ===========================================
        
        case 'get_notifications':
            // Simuler des notifications (à implémenter selon les besoins)
            $notifications = [
                [
                    'id' => 1,
                    'type' => 'message',
                    'title' => 'Nouveau message',
                    'content' => 'Vous avez reçu un nouveau message',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'read' => false
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications
            ]);
            break;
            
        case 'mark_notification_read':
            $notificationId = $_POST['notification_id'] ?? '';
            // Marquer comme lu (à implémenter)
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification marquée comme lue'
            ]);
            break;
            
        // ===========================================
        // VALIDATION ET TESTS
        // ===========================================
        
        case 'ping':
            echo json_encode([
                'success' => true,
                'message' => 'pong',
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $_SESSION['user_id']
            ]);
            break;
            
        case 'validate_session':
            $currentUser = $userService->findUserById($_SESSION['user_id']);
            if (!$currentUser) {
                throw new Exception('Session invalide');
            }
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $currentUser->getId(),
                    'name' => $currentUser->getName(),
                    'email' => $currentUser->getEmail()
                ]
            ]);
            break;
            
        // ===========================================
        // GESTION DES FICHIERS
        // ===========================================
        
        case 'upload_file':
            if (!isset($_FILES['file'])) {
                throw new Exception('Aucun fichier sélectionné');
            }
            
            debugLog("File upload request: " . json_encode([
                'user_id' => $_SESSION['user_id'],
                'file_name' => $_FILES['file']['name'],
                'file_size' => $_FILES['file']['size'],
                'file_type' => $_FILES['file']['type']
            ]));
            
            try {
                $fileInfo = $fileUploadService->uploadFile($_FILES['file'], $_SESSION['user_id']);
                
                debugLog("SUCCESS: File uploaded - path: " . $fileInfo['file_path']);
                
                echo json_encode([
                    'success' => true,
                    'file' => [
                        'path' => $fileInfo['file_path'],
                        'name' => $fileInfo['file_name'],
                        'size' => $fileInfo['file_size'],
                        'type' => $fileInfo['mime_type'],
                        'url' => $fileInfo['file_path']
                    ]
                ]);
            } catch (Exception $e) {
                debugLog("ERROR: File upload failed - " . $e->getMessage());
                throw new Exception('Erreur d\'upload : ' . $e->getMessage());
            }
            break;
            
        default:
            throw new Exception('Action non reconnue: ' . $action);
    }
    
} catch (Exception $e) {
    debugLog("AJAX Exception: " . json_encode([
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'action' => $_GET['action'] ?? $_POST['action'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? 'anonymous'
    ]));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?> 