<?php

namespace WhatsApp\Repositories;

use WhatsApp\Models\Group;
use WhatsApp\Utils\XMLManager;
use Exception;

/**
 * GroupRepository - Gestion des opérations CRUD pour les groupes
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class GroupRepository
{
    private XMLManager $xmlManager;

    public function __construct(XMLManager $xmlManager)
    {
        $this->xmlManager = $xmlManager;
    }

    /**
     * Crée un nouveau groupe
     * 
     * @param Group $group
     * @return bool
     */
    public function create(Group $group): bool
    {
        $groupData = [
            'attributes' => ['id' => $group->getId()],
            'name' => $group->getName()
        ];

        if ($group->getDescription()) {
            $groupData['description'] = $group->getDescription();
        }

        // Gérer les membres
        $membersData = [];
        foreach ($group->getMembers() as $userId => $role) {
            $membersData[] = [
                'attributes' => ['user_id' => $userId, 'role' => $role]
            ];
        }

        // Ne créer l'élément members que s'il y a des membres
        if (!empty($membersData)) {
            $groupData['members'] = [
                'member' => $membersData
            ];
        }
        // Si pas de membres, on ne crée pas l'élément members du tout

        return $this->xmlManager->addElement('//wa:groups', 'group', $groupData);
    }

    /**
     * Trouve un groupe par ID
     * 
     * @param string $id
     * @return Group|null
     */
    public function findById(string $id): ?Group
    {
        $element = $this->xmlManager->findElementById('group', $id);
        if (!$element) {
            return null;
        }

        return $this->elementToGroup($element);
    }

    /**
     * Trouve tous les groupes
     * 
     * @return Group[]
     */
    public function findAll(): array
    {
        $simpleXML = $this->xmlManager->getSimpleXML();
        $groups = [];

        // Gérer les namespaces
        $namespaces = $simpleXML->getNamespaces(true);
        $defaultNS = $namespaces[''] ?? null;
        
        if ($defaultNS) {
            $groupsNode = $simpleXML->children($defaultNS)->groups;
            if ($groupsNode) {
                $groupNodes = $groupsNode->children($defaultNS);
                foreach ($groupNodes as $groupXml) {
                    $attributes = $groupXml->attributes();
                    $id = (string) $attributes['id'];
                    
                    if (!empty($id)) {
                        $group = new Group(
                            $id,
                            (string) $groupXml->children($defaultNS)->name
                        );

                        // Description optionnelle
                        $descriptionNode = $groupXml->children($defaultNS)->description;
                        if ($descriptionNode && !empty((string) $descriptionNode)) {
                            $group->setDescription((string) $descriptionNode);
                        }

                        // Charger les membres
                        $membersNode = $groupXml->children($defaultNS)->members;
                        if ($membersNode) {
                            $memberNodes = $membersNode->children($defaultNS);
                            foreach ($memberNodes as $memberNode) {
                                $memberAttrs = $memberNode->attributes();
                                $userId = (string) $memberAttrs['user_id'];
                                $role = (string) $memberAttrs['role'];
                                
                                if (!empty($userId)) {
                                    $group->addMember($userId, $role ?: 'member');
                                }
                            }
                        }
                        
                        $groups[] = $group;
                    }
                }
            }
        }

        return $groups;
    }

    /**
     * Met à jour un groupe
     * 
     * @param Group $group
     * @return bool
     */
    public function update(Group $group): bool
    {
        // Supprimer l'ancien et ajouter le nouveau
        $this->xmlManager->deleteElementById('group', $group->getId());
        return $this->create($group);
    }

    /**
     * Supprime un groupe
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        return $this->xmlManager->deleteElementById('group', $id);
    }

    /**
     * Vérifie si un groupe existe
     * 
     * @param string $id
     * @return bool
     */
    public function exists(string $id): bool
    {
        return $this->findById($id) !== null;
    }

    /**
     * Trouve des groupes par nom
     * 
     * @param string $name
     * @return Group[]
     */
    public function findByName(string $name): array
    {
        $allGroups = $this->findAll();
        return array_values(array_filter($allGroups, fn($group) => 
            stripos($group->getName(), $name) !== false
        ));
    }

    /**
     * Trouve les groupes dont un utilisateur est membre
     * 
     * @param string $userId
     * @return Group[]
     */
    public function findByMember(string $userId): array
    {
        $allGroups = $this->findAll();
        return array_values(array_filter($allGroups, fn($group) => 
            $group->isMember($userId)
        ));
    }

    /**
     * Trouve les groupes dont un utilisateur est admin
     * 
     * @param string $userId
     * @return Group[]
     */
    public function findByAdmin(string $userId): array
    {
        $allGroups = $this->findAll();
        return array_values(array_filter($allGroups, fn($group) => 
            $group->isAdmin($userId)
        ));
    }

    /**
     * Convertit un élément DOM en objet Group
     */
    private function elementToGroup(\DOMElement $element): Group
    {
        $group = new Group(
            $element->getAttribute('id'),
            $element->getElementsByTagName('name')->item(0)->textContent
        );

        // Description optionnelle
        $descriptionElement = $element->getElementsByTagName('description')->item(0);
        if ($descriptionElement) {
            $group->setDescription($descriptionElement->textContent);
        }

        // Charger les membres
        $membersElement = $element->getElementsByTagName('members')->item(0);
        if ($membersElement) {
            $memberElements = $membersElement->getElementsByTagName('member');
            foreach ($memberElements as $memberElement) {
                $userId = $memberElement->getAttribute('user_id');
                $role = $memberElement->getAttribute('role') ?: 'member';
                
                if (!empty($userId)) {
                    $group->addMember($userId, $role);
                }
            }
        }

        return $group;
    }

    /**
     * Trouve les groupes par ID utilisateur (utilisateur membre)
     * 
     * @param string $userId ID de l'utilisateur
     * @return array Tableau de groupes
     */
    public function findByUserId(string $userId): array
    {
        $allGroups = $this->findAll();
        return array_values(array_filter($allGroups, function($group) use ($userId) {
            return $group->isMember($userId);
        }));
    }

    /**
     * Alias pour findByUserId (compatibilité interface web)
     * 
     * @param string $userId ID de l'utilisateur
     * @return array Tableau de groupes
     */
    public function getGroupsByUserId(string $userId): array
    {
        return $this->findByUserId($userId);
    }

    /**
     * Trouve un groupe par son ID
     * 
     * @param string $id ID du groupe
     * @return Group|null Groupe trouvé ou null
     */
    public function getGroupById(string $id): ?Group
    {
        return $this->findById($id);
    }

    /**
     * Crée un nouveau groupe
     * 
     * @param string $name Nom du groupe
     * @param string $description Description du groupe
     * @param string $creatorId ID du créateur du groupe (sera ajouté comme admin)
     * @return string ID du groupe créé
     */
    public function createGroup(string $name, string $description = '', string $creatorId = null): string
    {
        $groupId = 'group_' . time() . '_' . uniqid();
        $group = new Group($groupId, $name, $description);
        
        // Ajouter le créateur comme admin si fourni
        if ($creatorId) {
            $group->addMember($creatorId, 'admin');
        }
        
        $this->create($group);
        return $groupId;
    }

    /**
     * Supprime un groupe
     * 
     * @param string $id ID du groupe à supprimer
     * @return bool True si supprimé avec succès
     */
    public function deleteGroup(string $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Ajoute un membre au groupe
     * 
     * @param string $groupId ID du groupe
     * @param string $userId ID de l'utilisateur
     * @param string $role Rôle du membre
     * @return bool True si ajouté avec succès
     */
    public function addMemberToGroup(string $groupId, string $userId, string $role = 'member'): bool
    {
        $group = $this->findById($groupId);
        if ($group) {
            $group->addMember($userId, $role);
            return $this->update($group);
        }
        return false;
    }

    /**
     * Retire un membre du groupe
     * 
     * @param string $groupId ID du groupe
     * @param string $userId ID de l'utilisateur
     * @return bool True si retiré avec succès
     */
    public function removeMemberFromGroup(string $groupId, string $userId): bool
    {
        $group = $this->findById($groupId);
        if ($group) {
            $group->removeMember($userId);
            return $this->update($group);
        }
        return false;
    }

    /**
     * Vérifie si un utilisateur est admin du groupe
     * 
     * @param string $groupId ID du groupe
     * @param string $userId ID de l'utilisateur
     * @return bool True si admin
     */
    public function isUserAdminOfGroup(string $groupId, string $userId): bool
    {
        $group = $this->findById($groupId);
        return $group && $group->isAdmin($userId);
    }

    /**
     * Récupère les membres d'un groupe
     * 
     * @param string $groupId ID du groupe
     * @return array Tableau des membres avec leurs rôles
     */
    public function getGroupMembers(string $groupId): array
    {
        $group = $this->findById($groupId);
        if ($group) {
            return $group->getMembers();
        }
        return [];
    }
} 