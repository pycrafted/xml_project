<?php

namespace WhatsApp\Services;

use Exception;

/**
 * FileUploadService - Service pour gérer l'upload sécurisé des fichiers
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class FileUploadService
{
    private const UPLOAD_DIR = 'public/uploads/';
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/rtf',
        'application/zip',
        'application/x-rar-compressed',
        'audio/mpeg',
        'audio/mp3',
        'video/mp4',
        'video/avi',
        'video/quicktime',
        'video/x-ms-wmv'
    ];
    
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'rtf', 'zip', 'rar', 'mp3', 'mp4', 'avi', 'mov', 'wmv'
    ];

    /**
     * Traite l'upload d'un fichier
     * 
     * @param array $file Données du fichier $_FILES
     * @param string $userId ID de l'utilisateur
     * @return array Informations du fichier uploadé
     * @throws Exception Si l'upload échoue
     */
    public function uploadFile(array $file, string $userId): array
    {
        // Vérifications de base
        $this->validateFileUpload($file);
        
        // Validation de sécurité
        $this->validateFileContent($file);
        
        // Générer un nom de fichier unique et sécurisé
        $fileName = $this->generateSecureFileName($file['name'], $userId);
        
        // Chemin complet du fichier
        $uploadPath = $this->getUploadPath($fileName);
        
        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Erreur lors de la sauvegarde du fichier');
        }
        
        // Vérifier que le fichier a été créé
        if (!file_exists($uploadPath)) {
            throw new Exception('Le fichier n\'a pas été sauvegardé correctement');
        }
        
        // Retourner les informations du fichier
        return [
            'file_path' => 'uploads/' . $fileName,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'upload_path' => $uploadPath
        ];
    }

    /**
     * Valide l'upload d'un fichier
     * 
     * @param array $file
     * @throws Exception
     */
    private function validateFileUpload(array $file): void
    {
        // Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }
        
        // Vérifier la taille
        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new Exception('Fichier trop volumineux (max 10MB)');
        }
        
        // Vérifier que le fichier n'est pas vide
        if ($file['size'] === 0) {
            throw new Exception('Le fichier est vide');
        }
        
        // Vérifier l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new Exception('Type de fichier non autorisé. Extensions autorisées : ' . implode(', ', self::ALLOWED_EXTENSIONS));
        }
        
        // Vérifier le type MIME
        if (!in_array($file['type'], self::ALLOWED_MIME_TYPES)) {
            throw new Exception('Type MIME non autorisé : ' . $file['type']);
        }
    }

    /**
     * Validation avancée du contenu du fichier
     * 
     * @param array $file
     * @throws Exception
     */
    private function validateFileContent(array $file): void
    {
        // Vérifier le type MIME réel du fichier
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($realMimeType, self::ALLOWED_MIME_TYPES)) {
            throw new Exception('Type de fichier réel non autorisé : ' . $realMimeType);
        }
        
        // Vérifier que le fichier n'est pas un script PHP déguisé
        $content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
        if (strpos($content, '<?php') !== false || strpos($content, '<?') !== false) {
            throw new Exception('Fichier contenant du code PHP détecté');
        }
        
        // Vérifier les fichiers images
        if (strpos($realMimeType, 'image/') === 0 && $realMimeType !== 'image/svg+xml') {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                throw new Exception('Fichier image corrompu');
            }
        }
    }

    /**
     * Génère un nom de fichier sécurisé et unique
     * 
     * @param string $originalName
     * @param string $userId
     * @return string
     */
    private function generateSecureFileName(string $originalName, string $userId): string
    {
        // Nettoyer le nom original
        $cleanName = $this->sanitizeFileName($originalName);
        
        // Obtenir l'extension
        $extension = pathinfo($cleanName, PATHINFO_EXTENSION);
        $baseName = pathinfo($cleanName, PATHINFO_FILENAME);
        
        // Générer un nom unique
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        $userPrefix = substr($userId, 0, 8);
        
        return $userPrefix . '_' . $timestamp . '_' . $random . '_' . $baseName . '.' . $extension;
    }

    /**
     * Nettoie un nom de fichier
     * 
     * @param string $fileName
     * @return string
     */
    private function sanitizeFileName(string $fileName): string
    {
        // Remplacer les caractères dangereux
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        
        // Éviter les noms de fichiers trop longs
        if (strlen($fileName) > 100) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $baseName = substr(pathinfo($fileName, PATHINFO_FILENAME), 0, 95);
            $fileName = $baseName . '.' . $extension;
        }
        
        return $fileName;
    }

    /**
     * Obtient le chemin complet d'upload
     * 
     * @param string $fileName
     * @return string
     */
    private function getUploadPath(string $fileName): string
    {
        $uploadDir = self::UPLOAD_DIR;
        
        // Créer le répertoire s'il n'existe pas
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Impossible de créer le répertoire d\'upload');
            }
        }
        
        return $uploadDir . $fileName;
    }

    /**
     * Obtient le message d'erreur d'upload
     * 
     * @param int $errorCode
     * @return string
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée par le serveur';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée par le formulaire';
            case UPLOAD_ERR_PARTIAL:
                return 'Le fichier n\'a été que partiellement uploadé';
            case UPLOAD_ERR_NO_FILE:
                return 'Aucun fichier n\'a été uploadé';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Répertoire temporaire manquant';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Impossible d\'écrire le fichier sur le disque';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload arrêté par une extension PHP';
            default:
                return 'Erreur d\'upload inconnue';
        }
    }

    /**
     * Supprime un fichier uploadé
     * 
     * @param string $filePath
     * @return bool
     */
    public function deleteFile(string $filePath): bool
    {
        $fullPath = 'public/' . $filePath;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }

    /**
     * Vérifie si un fichier existe
     * 
     * @param string $filePath
     * @return bool
     */
    public function fileExists(string $filePath): bool
    {
        return file_exists('public/' . $filePath);
    }

    /**
     * Obtient la taille d'un fichier
     * 
     * @param string $filePath
     * @return int|false
     */
    public function getFileSize(string $filePath)
    {
        $fullPath = 'public/' . $filePath;
        
        if (file_exists($fullPath)) {
            return filesize($fullPath);
        }
        
        return false;
    }
} 