<?php

namespace WhatsApp\Models;

/**
 * Modèle User - Représente un utilisateur de la plateforme
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class User
{
    private string $id;
    private string $name;
    private string $email;
    private string $status;
    private array $settings;
    private string $createdAt;

    /**
     * Constructeur User
     * 
     * @param string $id Identifiant unique
     * @param string $name Nom de l'utilisateur
     * @param string $email Email de l'utilisateur
     */
    public function __construct(string $id, string $name, string $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->status = 'active';
        $this->settings = [];
        $this->createdAt = date('Y-m-d H:i:s');
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getStatus(): string { return $this->status; }
    public function getSettings(): array { return $this->settings; }
    public function getCreatedAt(): string { return $this->createdAt; }

    // Setters
    public function setName(string $name): void { $this->name = $name; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setSettings(array $settings): void { $this->settings = $settings; }
    public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }

    /**
     * Convertit l'utilisateur en array pour XML
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'settings' => $this->settings,
            'created_at' => $this->createdAt
        ];
    }
} 