<?php
/**
 * WhatsApp Web Clone - Endpoint de téléchargement sécurisé
 * Permet de télécharger les fichiers avec vérification des permissions
 */

require_once '../vendor/autoload.php';

use WhatsApp\Services\UserService;
use WhatsApp\Services\MessageService;
use WhatsApp\Services\FileUploadService;
use WhatsApp\Repositories\MessageRepository;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;

session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Accès non autorisé');
}

// Récupération des paramètres
$filePath = $_GET['file'] ?? '';
$messageId = $_GET['message'] ?? '';

if (empty($filePath) || empty($messageId)) {
    http_response_code(400);
    die('Paramètres manquants');
}

// Initialisation des services
$xmlManager = new WhatsApp\Utils\XMLManager();
$userService = new UserService($xmlManager);
$messageService = new MessageService($xmlManager);
$fileUploadService = new FileUploadService();
$messageRepo = new MessageRepository($xmlManager);
$contactRepo = new ContactRepository($xmlManager);
$groupRepo = new GroupRepository($xmlManager);

try {
    // Vérifier que le message existe
    $message = $messageRepo->findById($messageId);
    if (!$message) {
        http_response_code(404);
        die('Message non trouvé');
    }

    // Vérifier que le fichier correspond au message
    if ($message->getFilePath() !== $filePath) {
        http_response_code(403);
        die('Fichier non autorisé');
    }

    // Vérifier les permissions d'accès
    $userId = $_SESSION['user_id'];
    $hasAccess = false;

    // Si c'est un message privé
    if ($message->getToUser()) {
        // L'utilisateur peut télécharger s'il est expéditeur ou destinataire
        if ($message->getFromUser() === $userId || $message->getToUser() === $userId) {
            // Vérifier aussi qu'il y a un contact entre les deux utilisateurs
            if ($contactRepo->contactExists($message->getFromUser(), $message->getToUser()) ||
                $contactRepo->contactExists($message->getToUser(), $message->getFromUser())) {
                $hasAccess = true;
            }
        }
    }
    // Si c'est un message de groupe
    elseif ($message->getToGroup()) {
        // Vérifier que l'utilisateur est membre du groupe
        $group = $groupRepo->getGroupById($message->getToGroup());
        if ($group) {
            $members = $groupRepo->getGroupMembers($message->getToGroup());
            if (isset($members[$userId])) {
                $hasAccess = true;
            }
        }
    }

    if (!$hasAccess) {
        http_response_code(403);
        die('Accès non autorisé à ce fichier');
    }

    // Vérifier que le fichier existe
    if (!$fileUploadService->fileExists($filePath)) {
        http_response_code(404);
        die('Fichier non trouvé');
    }

    // Chemin complet du fichier
    $fullPath = 'public/' . $filePath;

    // Obtenir les informations du fichier
    $fileName = $message->getFileName() ?: basename($filePath);
    $fileSize = $message->getFileSize() ?: filesize($fullPath);
    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

    // Définir les headers appropriés
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: private, max-age=0');
    header('Pragma: no-cache');

    // Journaliser le téléchargement
    file_put_contents('../logs/app.log', 
        date('Y-m-d H:i:s') . " [DOWNLOAD] User: {$userId}, File: {$fileName}, Message: {$messageId}\n", 
        FILE_APPEND
    );

    // Envoyer le fichier
    readfile($fullPath);

} catch (Exception $e) {
    // Journaliser l'erreur
    file_put_contents('../logs/app.log', 
        date('Y-m-d H:i:s') . " [ERROR] Download failed: " . $e->getMessage() . "\n", 
        FILE_APPEND
    );
    
    http_response_code(500);
    die('Erreur lors du téléchargement');
}

exit; 