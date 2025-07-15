<?php
/**
 * WhatsApp Web Clone - API AJAX
 * Gestionnaire des requêtes AJAX pour fonctionnalités temps réel
 */

require_once '../vendor/autoload.php';

use WhatsApp\Services\UserService;
use WhatsApp\Services\MessageService;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;
use WhatsApp\Repositories\MessageRepository;

session_start();

// Configuration des headers pour AJAX
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

logInfo("AJAX Request", [
    'action' => $_GET['action'] ?? $_POST['action'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'],
    'user_id' => $_SESSION['user_id'] ?? 'anonymous'
]);

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
            logInfo("Send message request", [
                'user_id' => $_SESSION['user_id'],
                'content_length' => strlen($_POST['content'] ?? ''),
                'recipient_id' => $_POST['recipient_id'] ?? '',
                'type' => $_POST['type'] ?? 'text'
            ]);
            
            $content = trim($_POST['content'] ?? '');
            $recipientId = $_POST['recipient_id'] ?? '';
            $type = $_POST['type'] ?? 'text';
            
            if (empty($content)) {
                logWarning("Message vide", ['user_id' => $_SESSION['user_id']]);
                throw new Exception('Le message ne peut pas être vide');
            }
            
            if (empty($recipientId)) {
                logWarning("Destinataire non spécifié", ['user_id' => $_SESSION['user_id']]);
                throw new Exception('Destinataire non spécifié');
            }
            
            logDebug("Tentative d'envoi de message", [
                'from' => $_SESSION['user_id'],
                'to' => $recipientId,
                'content' => substr($content, 0, 50) . '...'
            ]);
            
            $message = $messageService->sendPrivateMessage($_SESSION['user_id'], $recipientId, $content, $type);
            
            logSuccess("Message envoyé", [
                'message_id' => $message->getId(),
                'from' => $_SESSION['user_id'],
                'to' => $recipientId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => [
                    'id' => $message->getId(),
                    'content' => $content,
                    'type' => $type,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'from_user_id' => $_SESSION['user_id']
                ]
            ]);
            break;
            
        case 'send_group_message':
            $content = trim($_POST['content'] ?? '');
            $groupId = $_POST['group_id'] ?? '';
            $type = $_POST['type'] ?? 'text';
            
            if (empty($content)) {
                throw new Exception('Le message ne peut pas être vide');
            }
            
            if (empty($groupId)) {
                throw new Exception('Groupe non spécifié');
            }
            
            $message = $messageService->sendGroupMessage($_SESSION['user_id'], $groupId, $content, $type);
            
            echo json_encode([
                'success' => true,
                'message' => [
                    'id' => $message->getId(),
                    'content' => $content,
                    'type' => $type,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'from_user_id' => $_SESSION['user_id'],
                    'to_group_id' => $groupId
                ]
            ]);
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
                $formattedMessages[] = [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'type' => $message->getType(),
                    'timestamp' => $message->getTimestamp(),
                    'from_user_id' => $message->getFromUserId(),
                    'is_sent' => ($message->getFromUserId() === $_SESSION['user_id'])
                ];
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
            
            $file = $_FILES['file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
            $maxSize = 10 * 1024 * 1024; // 10MB
            
            if ($file['size'] > $maxSize) {
                throw new Exception('Fichier trop volumineux (max 10MB)');
            }
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Type de fichier non autorisé');
            }
            
            // Simuler l'upload (ne pas vraiment sauvegarder le fichier)
            $fileName = 'file_' . time() . '_' . $file['name'];
            
            echo json_encode([
                'success' => true,
                'file' => [
                    'name' => $fileName,
                    'size' => $file['size'],
                    'type' => $file['type'],
                    'url' => '/uploads/' . $fileName // URL fictive
                ]
            ]);
            break;
            
        default:
            throw new Exception('Action non reconnue: ' . $action);
    }
    
} catch (Exception $e) {
    logError("AJAX Exception", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'action' => $_GET['action'] ?? $_POST['action'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? 'anonymous'
    ]);
    
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