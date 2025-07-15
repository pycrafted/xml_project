<?php

namespace WhatsApp\Models;

/**
 * Modèle Message - Représente un message échangé sur la plateforme
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class Message
{
    private string $id;
    private string $content;
    private string $type;
    private string $timestamp;
    private string $status;
    private string $fromUser;
    private ?string $toUser;
    private ?string $toGroup;
    private ?string $filePath;
    private ?string $fileName;
    private ?int $fileSize;

    /**
     * Constructeur Message
     * 
     * @param string $id Identifiant unique
     * @param string $content Contenu du message
     * @param string $fromUser ID de l'expéditeur
     * @param string $type Type de message (text|file)
     */
    public function __construct(string $id, string $content, string $fromUser, string $type = 'text')
    {
        $this->id = $id;
        $this->content = $content;
        $this->fromUser = $fromUser;
        $this->type = $type;
        $this->timestamp = date('Y-m-d H:i:s');
        $this->status = 'sent';
        $this->toUser = null;
        $this->toGroup = null;
        $this->filePath = null;
        $this->fileName = null;
        $this->fileSize = null;
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getContent(): string { return $this->content; }
    public function getType(): string { return $this->type; }
    public function getTimestamp(): string { return $this->timestamp; }
    public function getStatus(): string { return $this->status; }
    public function getFromUser(): string { return $this->fromUser; }
    public function getFromUserId(): string { return $this->fromUser; } // Alias pour compatibilité
    public function getToUser(): ?string { return $this->toUser; }
    public function getToGroup(): ?string { return $this->toGroup; }
    public function getFilePath(): ?string { return $this->filePath; }
    public function getFileName(): ?string { return $this->fileName; }
    public function getFileSize(): ?int { return $this->fileSize; }

    // Setters
    public function setContent(string $content): void { $this->content = $content; }
    public function setType(string $type): void { $this->type = $type; }
    public function setTimestamp(string $timestamp): void { $this->timestamp = $timestamp; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setToUser(?string $toUser): void { $this->toUser = $toUser; }
    public function setToGroup(?string $toGroup): void { $this->toGroup = $toGroup; }
    public function setFilePath(?string $filePath): void { $this->filePath = $filePath; }
    public function setFileName(?string $fileName): void { $this->fileName = $fileName; }
    public function setFileSize(?int $fileSize): void { $this->fileSize = $fileSize; }

    /**
     * Définit le destinataire (utilisateur)
     */
    public function setRecipientUser(string $userId): void
    {
        $this->toUser = $userId;
        $this->toGroup = null;
    }

    /**
     * Définit le destinataire (groupe)
     */
    public function setRecipientGroup(string $groupId): void
    {
        $this->toGroup = $groupId;
        $this->toUser = null;
    }

    /**
     * Vérifie si c'est un message privé
     */
    public function isPrivateMessage(): bool
    {
        return $this->toUser !== null;
    }

    /**
     * Vérifie si c'est un message de groupe
     */
    public function isGroupMessage(): bool
    {
        return $this->toGroup !== null;
    }

    /**
     * Vérifie si c'est un message avec fichier
     */
    public function hasFile(): bool
    {
        return $this->type === 'file' && $this->filePath !== null;
    }

    /**
     * Obtient la taille du fichier formatée
     */
    public function getFormattedFileSize(): string
    {
        if (!$this->fileSize) {
            return 'Taille inconnue';
        }
        
        $bytes = $this->fileSize;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Obtient l'extension du fichier
     */
    public function getFileExtension(): string
    {
        if (!$this->fileName) {
            return '';
        }
        
        $ext = pathinfo($this->fileName, PATHINFO_EXTENSION);
        return strtolower($ext);
    }

    /**
     * Vérifie si le fichier est une image
     */
    public function isImage(): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        return in_array($this->getFileExtension(), $imageExtensions);
    }

    /**
     * Marque le message comme lu
     */
    public function markAsRead(): void
    {
        $this->status = 'read';
    }

    /**
     * Marque le message comme reçu
     */
    public function markAsReceived(): void
    {
        $this->status = 'received';
    }

    /**
     * Convertit le message en array pour XML
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'content' => $this->content,
            'type' => $this->type,
            'timestamp' => $this->timestamp,
            'status' => $this->status,
            'from_user' => $this->fromUser
        ];

        if ($this->toUser !== null) {
            $data['to_user'] = $this->toUser;
        }

        if ($this->toGroup !== null) {
            $data['to_group'] = $this->toGroup;
        }

        if ($this->filePath !== null) {
            $data['file_path'] = $this->filePath;
        }

        if ($this->fileName !== null) {
            $data['file_name'] = $this->fileName;
        }

        if ($this->fileSize !== null) {
            $data['file_size'] = $this->fileSize;
        }

        return $data;
    }
} 