<?php

namespace WhatsApp\Models;

/**
 * Modèle Contact - Représente un contact d'un utilisateur
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class Contact
{
    private string $id;
    private string $name;
    private string $userId;  // ID de l'utilisateur qui possède ce contact
    private ?string $contactUserId = null;  // ID de l'utilisateur contacté (optionnel)

    /**
     * Constructeur Contact
     * 
     * @param string $id Identifiant unique du contact
     * @param string $name Nom d'affichage du contact
     * @param string $userId ID de l'utilisateur qui possède ce contact
     * @param string|null $contactUserId ID de l'utilisateur contacté (optionnel)
     */
    public function __construct(string $id, string $name, string $userId, ?string $contactUserId = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->userId = $userId;
        $this->contactUserId = $contactUserId ?? $userId;
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getUserId(): string { return $this->userId; }
    public function getContactUserId(): string { return $this->contactUserId ?? $this->userId; }

    // Setters
    public function setName(string $name): void { $this->name = $name; }
    public function setUserId(string $userId): void { $this->userId = $userId; }
    public function setContactUserId(string $contactUserId): void { $this->contactUserId = $contactUserId; }

    /**
     * Convertit le contact en array pour XML
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'user_id' => $this->userId
        ];
    }
} 