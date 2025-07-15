<?php

namespace WhatsApp\Services;

use WhatsApp\Models\User;
use WhatsApp\Repositories\UserRepository;
use WhatsApp\Utils\XMLManager;
use Exception;

/**
 * UserService - Couche de logique métier pour les utilisateurs
 * 
 * Implémente les règles métier et coordonne les opérations
 * entre les repositories et les contrôleurs.
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class UserService
{
    private UserRepository $userRepository;

    public function __construct(XMLManager $xmlManager)
    {
        $this->userRepository = new UserRepository($xmlManager);
    }

    /**
     * Crée un nouvel utilisateur avec validation métier
     * 
     * @param string $id
     * @param string $name
     * @param string $email
     * @param array $settings
     * @return User
     * @throws Exception Si validation échoue
     */
    public function createUser(string $id, string $name, string $email, array $settings = []): User
    {
        // Règles métier
        $this->validateUserCreation($id, $name, $email);

        $user = new User($id, $name, $email);
        $user->setSettings($settings);

        if (!$this->userRepository->create($user)) {
            throw new Exception("Échec de création de l'utilisateur");
        }

        return $user;
    }

    /**
     * Met à jour un utilisateur existant
     * 
     * @param string $id
     * @param array $data Données à mettre à jour
     * @return User
     * @throws Exception Si utilisateur inexistant
     */
    public function updateUser(string $id, $nameOrData = null, $email = null, $status = null): User
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new Exception("Utilisateur non trouvé : {$id}");
        }

        // Si le deuxième paramètre est un tableau, utiliser l'ancien format
        if (is_array($nameOrData)) {
            $data = $nameOrData;
            
            // Appliquer les modifications
            if (isset($data['name'])) {
                $this->validateName($data['name']);
                $user->setName($data['name']);
            }

            if (isset($data['email'])) {
                $this->validateEmail($data['email']);
                $user->setEmail($data['email']);
            }

            if (isset($data['status'])) {
                $user->setStatus($data['status']);
            }

            if (isset($data['settings'])) {
                $user->setSettings($data['settings']);
            }
        } else {
            // Nouveau format avec paramètres individuels
            if ($nameOrData !== null) {
                $this->validateName($nameOrData);
                $user->setName($nameOrData);
            }

            if ($email !== null) {
                $this->validateEmail($email);
                $user->setEmail($email);
            }

            if ($status !== null) {
                $user->setStatus($status);
            }
        }

        if (!$this->userRepository->update($user)) {
            throw new Exception("Échec de mise à jour");
        }

        return $user;
    }

    /**
     * Supprime un utilisateur et ses données associées
     * 
     * @param string $id
     * @return bool
     * @throws Exception Si utilisateur inexistant
     */
    public function deleteUser(string $id): bool
    {
        if (!$this->userRepository->exists($id)) {
            throw new Exception("Utilisateur non trouvé : {$id}");
        }

        // TODO: Supprimer les contacts, messages, groupes associés
        
        return $this->userRepository->delete($id);
    }

    /**
     * Recherche d'utilisateurs avec critères
     * 
     * @param array $criteria
     * @return User[]
     */
    public function searchUsers(array $criteria): array
    {
        if (isset($criteria['email'])) {
            return $this->userRepository->findByEmail($criteria['email']);
        }

        if (isset($criteria['name'])) {
            $allUsers = $this->userRepository->findAll();
            return array_filter($allUsers, fn($user) => 
                stripos($user->getName(), $criteria['name']) !== false
            );
        }

        return $this->userRepository->findAll();
    }

    /**
     * Obtient les statistiques utilisateur
     * 
     * @return array
     */
    public function getUserStats(): array
    {
        $users = $this->userRepository->findAll();
        
        return [
            'total_users' => count($users),
            'active_users' => count(array_filter($users, fn($u) => $u->getStatus() === 'active')),
            'inactive_users' => count(array_filter($users, fn($u) => $u->getStatus() !== 'active')),
            // Compatibilité avec l'ancien format
            'total' => count($users),
            'active' => count(array_filter($users, fn($u) => $u->getStatus() === 'active')),
            'inactive' => count(array_filter($users, fn($u) => $u->getStatus() !== 'active'))
        ];
    }

    /**
     * Validation des règles métier pour la création
     */
    private function validateUserCreation(string $id, string $name, string $email): void
    {
        // Vérifier unicité ID
        if ($this->userRepository->exists($id)) {
            throw new Exception("Un utilisateur avec l'ID '{$id}' existe déjà");
        }

        // Vérifier unicité email
        $existingUsers = $this->userRepository->findByEmail($email);
        if (!empty($existingUsers)) {
            throw new Exception("Un utilisateur avec l'email '{$email}' existe déjà");
        }

        $this->validateName($name);
        $this->validateEmail($email);
    }

    /**
     * Validation du nom
     */
    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new Exception("Le nom ne peut pas être vide");
        }

        if (strlen($name) < 2) {
            throw new Exception("Le nom doit contenir au moins 2 caractères");
        }

        if (strlen($name) > 100) {
            throw new Exception("Le nom ne peut pas dépasser 100 caractères");
        }
    }

    /**
     * Validation de l'email
     */
    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format d'email invalide : {$email}");
        }
    }

    /**
     * Trouve un utilisateur par son ID
     * 
     * @param string $id ID de l'utilisateur
     * @return User|null Utilisateur trouvé ou null
     */
    public function findUserById(string $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Trouve un utilisateur par son email
     * 
     * @param string $email Email de l'utilisateur
     * @return User|null Premier utilisateur trouvé ou null
     */
    public function findUserByEmail(string $email): ?User
    {
        $users = $this->userRepository->findByEmail($email);
        return !empty($users) ? $users[0] : null;
    }

    /**
     * Récupère tous les utilisateurs
     * 
     * @return array Tableau de tous les utilisateurs
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * Récupère les statistiques utilisateur (alias pour getUserStats)
     * 
     * @return array Statistiques utilisateur
     */
    public function getUserStatistics(): array
    {
        $users = $this->userRepository->findAll();
        
        return [
            'total_users' => count($users),
            'active_users' => count(array_filter($users, fn($u) => $u->getStatus() !== 'offline')),
            'inactive_users' => count(array_filter($users, fn($u) => $u->getStatus() === 'offline'))
        ];
    }
} 