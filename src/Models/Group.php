<?php

namespace WhatsApp\Models;

/**
 * Modèle Group - Représente un groupe de discussion
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class Group
{
    private string $id;
    private string $name;
    private ?string $description;
    private array $members; // Format: ['user_id' => 'role']

    /**
     * Constructeur Group
     * 
     * @param string $id Identifiant unique du groupe
     * @param string $name Nom du groupe
     * @param string|null $description Description du groupe
     */
    public function __construct(string $id, string $name, ?string $description = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->members = [];
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getMembers(): array { return $this->members; }

    // Setters
    public function setName(string $name): void { $this->name = $name; }
    public function setDescription(?string $description): void { $this->description = $description; }

    /**
     * Ajoute un membre au groupe
     * 
     * @param string $userId ID de l'utilisateur
     * @param string $role Rôle (admin|member)
     */
    public function addMember(string $userId, string $role = 'member'): void
    {
        $this->members[$userId] = $role;
    }

    /**
     * Supprime un membre du groupe
     * 
     * @param string $userId ID de l'utilisateur
     */
    public function removeMember(string $userId): void
    {
        unset($this->members[$userId]);
    }

    /**
     * Vérifie si un utilisateur est membre du groupe
     * 
     * @param string $userId ID de l'utilisateur
     * @return bool
     */
    public function isMember(string $userId): bool
    {
        return isset($this->members[$userId]);
    }

    /**
     * Vérifie si un utilisateur est admin du groupe
     * 
     * @param string $userId ID de l'utilisateur
     * @return bool
     */
    public function isAdmin(string $userId): bool
    {
        return isset($this->members[$userId]) && $this->members[$userId] === 'admin';
    }

    /**
     * Obtient la liste des IDs des membres
     * 
     * @return string[]
     */
    public function getMemberIds(): array
    {
        return array_keys($this->members);
    }

    /**
     * Obtient le nombre de membres
     * 
     * @return int
     */
    public function getMemberCount(): int
    {
        return count($this->members);
    }
}