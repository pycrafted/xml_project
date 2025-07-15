<?php

namespace WhatsApp\Repositories;

use WhatsApp\Models\User;
use WhatsApp\Utils\XMLManager;
use Exception;

/**
 * UserRepository - Gestion des opérations CRUD pour les utilisateurs
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class UserRepository
{
    private XMLManager $xmlManager;

    public function __construct(XMLManager $xmlManager)
    {
        $this->xmlManager = $xmlManager;
    }

    /**
     * Crée un nouvel utilisateur
     * 
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        $userData = [
            'attributes' => ['id' => $user->getId()],
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'status' => $user->getStatus(),
            'settings' => $this->settingsToArray($user->getSettings())
        ];

        return $this->xmlManager->addElement('//wa:users', 'user', $userData);
    }

    /**
     * Trouve un utilisateur par ID
     * 
     * @param string $id
     * @return User|null
     */
    public function findById(string $id): ?User
    {
        $element = $this->xmlManager->findElementById('user', $id);
        if (!$element) {
            return null;
        }

        return $this->elementToUser($element);
    }

    /**
     * Trouve tous les utilisateurs
     * 
     * @return User[]
     */
    public function findAll(): array
    {
        $simpleXML = $this->xmlManager->getSimpleXML();
        $users = [];

        // Gérer les namespaces
        $namespaces = $simpleXML->getNamespaces(true);
        $defaultNS = $namespaces[''] ?? null;
        
        if ($defaultNS) {
            $usersNode = $simpleXML->children($defaultNS)->users;
            if ($usersNode) {
                $userNodes = $usersNode->children($defaultNS);
                foreach ($userNodes as $userXml) {
                    // Accès aux attributs avec namespace
                    $attributes = $userXml->attributes();
                    $id = (string) $attributes['id'];
                    
                    if (!empty($id)) {
                        $user = new User(
                            $id,
                            (string) $userXml->children($defaultNS)->name,
                            (string) $userXml->children($defaultNS)->email
                        );
                        
                        $statusNode = $userXml->children($defaultNS)->status;
                        if ($statusNode) {
                            $user->setStatus((string) $statusNode);
                        }
                        
                        // Charger les settings
                        $settings = [];
                        $settingsNode = $userXml->children($defaultNS)->settings;
                        if ($settingsNode) {
                            $settingNodes = $settingsNode->children($defaultNS);
                            foreach ($settingNodes as $setting) {
                                $settingAttrs = $setting->attributes();
                                $key = (string) $settingAttrs['key'];
                                $value = (string) $settingAttrs['value'];
                                if (!empty($key)) {
                                    $settings[$key] = $value;
                                }
                            }
                        }
                        $user->setSettings($settings);
                        
                        $users[] = $user;
                    }
                }
            }
        }

        return $users;
    }

    /**
     * Met à jour un utilisateur
     * 
     * @param User $user
     * @return bool
     */
    public function update(User $user): bool
    {
        // Supprimer l'ancien et ajouter le nouveau
        $this->xmlManager->deleteElementById('user', $user->getId());
        return $this->create($user);
    }

    /**
     * Supprime un utilisateur
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        return $this->xmlManager->deleteElementById('user', $id);
    }

    /**
     * Vérifie si un utilisateur existe
     * 
     * @param string $id
     * @return bool
     */
    public function exists(string $id): bool
    {
        return $this->findById($id) !== null;
    }

    /**
     * Trouve des utilisateurs par email
     * 
     * @param string $email
     * @return User[]
     */
    public function findByEmail(string $email): array
    {
        $allUsers = $this->findAll();
        return array_values(array_filter($allUsers, fn($user) => $user->getEmail() === $email));
    }

    /**
     * Convertit l'array settings en format XML
     */
    private function settingsToArray(array $settings): array
    {
        // Retourner directement les settings sans wrapper "setting"
        return $settings;
    }

    /**
     * Convertit un élément DOM en objet User
     */
    private function elementToUser(\DOMElement $element): User
    {
        $user = new User(
            $element->getAttribute('id'),
            $element->getElementsByTagName('name')->item(0)->textContent,
            $element->getElementsByTagName('email')->item(0)->textContent
        );

        $statusElement = $element->getElementsByTagName('status')->item(0);
        if ($statusElement) {
            $user->setStatus($statusElement->textContent);
        }

        // Charger les settings
        $settings = [];
        $settingsElement = $element->getElementsByTagName('settings')->item(0);
        if ($settingsElement) {
            $settingElements = $settingsElement->getElementsByTagName('setting');
            foreach ($settingElements as $settingElement) {
                $key = $settingElement->getAttribute('key');
                $value = $settingElement->getAttribute('value');
                $settings[$key] = $value;
            }
        }
        $user->setSettings($settings);

        return $user;
    }

    /**
     * Met à jour un utilisateur
     */
    public function updateUser(User $user): bool
    {
        try {
            $users = $this->getAllUsers();
            $updated = false;
            
            foreach ($users as $key => $existingUser) {
                if ($existingUser->getId() === $user->getId()) {
                    $users[$key] = $user;
                    $updated = true;
                    break;
                }
            }
            
            if ($updated) {
                return $this->saveUsers($users);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Erreur updateUser: " . $e->getMessage());
            return false;
        }
    }
} 