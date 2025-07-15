<?php

namespace WhatsApp\Repositories;

use WhatsApp\Models\Contact;
use WhatsApp\Utils\XMLManager;
use WhatsApp\Repositories\MessageRepository;
use Exception;

/**
 * ContactRepository - Gestion des opérations CRUD pour les contacts
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class ContactRepository
{
    private XMLManager $xmlManager;
    private MessageRepository $messageRepository;

    public function __construct(XMLManager $xmlManager)
    {
        $this->xmlManager = $xmlManager;
        $this->messageRepository = new MessageRepository($xmlManager);
    }

    /**
     * Crée un nouveau contact
     * 
     * @param Contact $contact
     * @return bool
     */
    public function create(Contact $contact): bool
    {
        $contactData = [
            'attributes' => ['id' => $contact->getId()],
            'name' => $contact->getName(),
            'user_id' => $contact->getUserId(),
            'contact_user_id' => $contact->getContactUserId()
        ];

        return $this->xmlManager->addElement('//wa:contacts', 'contact', $contactData);
    }

    /**
     * Trouve un contact par ID
     * 
     * @param string $id
     * @return Contact|null
     */
    public function findById(string $id): ?Contact
    {
        $element = $this->xmlManager->findElementById('contact', $id);
        if (!$element) {
            return null;
        }

        return $this->elementToContact($element);
    }

    /**
     * Trouve tous les contacts
     * 
     * @return Contact[]
     */
    public function findAll(): array
    {
        $simpleXML = $this->xmlManager->getSimpleXML();
        $contacts = [];

        // Gérer les namespaces
        $namespaces = $simpleXML->getNamespaces(true);
        $defaultNS = $namespaces[''] ?? null;
        
        if ($defaultNS) {
            $contactsNode = $simpleXML->children($defaultNS)->contacts;
            if ($contactsNode) {
                $contactNodes = $contactsNode->children($defaultNS);
                foreach ($contactNodes as $contactXml) {
                    $attributes = $contactXml->attributes();
                    $id = (string) $attributes['id'];
                    
                    if (!empty($id)) {
                        $contactUserIdNode = $contactXml->children($defaultNS)->contact_user_id;
                        $contactUserId = $contactUserIdNode ? (string) $contactUserIdNode : null;
                        
                        $contact = new Contact(
                            $id,
                            (string) $contactXml->children($defaultNS)->name,
                            (string) $contactXml->children($defaultNS)->user_id,
                            $contactUserId
                        );
                        
                        $contacts[] = $contact;
                    }
                }
            }
        }

        return $contacts;
    }

    /**
     * Met à jour un contact
     * 
     * @param Contact $contact
     * @return bool
     */
    public function update(Contact $contact): bool
    {
        // Supprimer l'ancien et ajouter le nouveau
        $this->xmlManager->deleteElementById('contact', $contact->getId());
        return $this->create($contact);
    }

    /**
     * Supprime un contact
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        return $this->xmlManager->deleteElementById('contact', $id);
    }

    /**
     * Vérifie si un contact existe
     * 
     * @param string $id
     * @return bool
     */
    public function exists(string $id): bool
    {
        return $this->findById($id) !== null;
    }

    /**
     * Trouve des contacts par nom
     * 
     * @param string $name
     * @return Contact[]
     */
    public function findByName(string $name): array
    {
        $allContacts = $this->findAll();
        return array_values(array_filter($allContacts, fn($contact) => 
            stripos($contact->getName(), $name) !== false
        ));
    }

    /**
     * Trouve des contacts par user_id
     * 
     * @param string $userId
     * @return Contact[]
     */
    public function findByUserId(string $userId): array
    {
        $allContacts = $this->findAll();
        return array_values(array_filter($allContacts, fn($contact) => 
            $contact->getUserId() === $userId
        ));
    }

    /**
     * Convertit un élément DOM en objet Contact
     */
    private function elementToContact(\DOMElement $element): Contact
    {
        $contactUserIdNode = $element->getElementsByTagName('contact_user_id')->item(0);
        $contactUserId = $contactUserIdNode ? $contactUserIdNode->textContent : null;
        
        return new Contact(
            $element->getAttribute('id'),
            $element->getElementsByTagName('name')->item(0)->textContent,
            $element->getElementsByTagName('user_id')->item(0)->textContent,
            $contactUserId
        );
    }

    /**
     * Alias pour findByUserId (compatibilité interface web)
     * 
     * @param string $userId ID de l'utilisateur
     * @return array Tableau de contacts
     */
    public function getContactsByUserId(string $userId): array
    {
        return $this->findByUserId($userId);
    }

    /**
     * Trouve un contact par son ID
     * 
     * @param string $id ID du contact
     * @return Contact|null Contact trouvé ou null
     */
    public function getContactById(string $id): ?Contact
    {
        return $this->findById($id);
    }

    /**
     * Crée un nouveau contact bidirectionnel
     * 
     * @param string $name Nom du contact
     * @param string $userId ID de l'utilisateur propriétaire
     * @param string $contactUserId ID de l'utilisateur contacté
     * @return string ID du contact créé
     */
    public function createContact(string $name, string $userId, string $contactUserId): string
    {
        $contactId = 'contact_' . time() . '_' . uniqid();
        $contact = new Contact($contactId, $name, $userId, $contactUserId);
        $this->create($contact);
        
        // Créer le contact inverse pour la bidirectionnalité
        $this->createReverseContact($userId, $contactUserId);
        
        return $contactId;
    }

    /**
     * Crée le contact inverse pour la bidirectionnalité
     * 
     * @param string $originalUserId ID de l'utilisateur qui a créé le contact
     * @param string $contactUserId ID de l'utilisateur contacté
     */
    private function createReverseContact(string $originalUserId, string $contactUserId): void
    {
        // Vérifier si le contact inverse existe déjà
        if (!$this->contactExists($contactUserId, $originalUserId)) {
            // Obtenir le nom de l'utilisateur original
            $userRepo = new \WhatsApp\Repositories\UserRepository($this->xmlManager);
            $originalUser = $userRepo->findById($originalUserId);
            
            if ($originalUser) {
                $reverseContactId = 'contact_' . time() . '_' . uniqid() . '_reverse';
                $reverseContact = new Contact(
                    $reverseContactId, 
                    $originalUser->getName(), 
                    $contactUserId, 
                    $originalUserId
                );
                $this->create($reverseContact);
            }
        }
    }

    /**
     * Supprime un contact et tous les messages associés (suppression en cascade)
     * 
     * @param string $id ID du contact à supprimer
     * @return bool True si supprimé avec succès
     */
    public function deleteContact(string $id): bool
    {
        $contact = $this->findById($id);
        if (!$contact) {
            return false;
        }
        
        // Supprimer tous les messages entre l'utilisateur et le contact
        $deletedMessages = $this->messageRepository->deleteConversation(
            $contact->getUserId(), 
            $contact->getContactUserId()
        );
        
        // Supprimer le contact lui-même
        $contactDeleted = $this->delete($id);
        
        return $contactDeleted;
    }

    /**
     * Supprime un contact (méthode simple sans cascade)
     * 
     * @param string $id ID du contact à supprimer
     * @return bool True si supprimé avec succès
     */
    public function deleteContactSimple(string $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Vérifie si un contact existe entre deux utilisateurs
     * 
     * @param string $userId ID de l'utilisateur propriétaire
     * @param string $contactUserId ID de l'utilisateur contacté
     * @return bool True si le contact existe
     */
    public function contactExists(string $userId, string $contactUserId): bool
    {
        $contacts = $this->findByUserId($userId);
        
        foreach ($contacts as $contact) {
            if ($contact->getContactUserId() === $contactUserId) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Trouve un contact par utilisateur propriétaire et utilisateur contacté
     * 
     * @param string $userId ID de l'utilisateur propriétaire
     * @param string $contactUserId ID de l'utilisateur contacté
     * @return Contact|null
     */
    public function findByUserIds(string $userId, string $contactUserId): ?Contact
    {
        $contacts = $this->findByUserId($userId);
        
        foreach ($contacts as $contact) {
            if ($contact->getContactUserId() === $contactUserId) {
                return $contact;
            }
        }
        
        return null;
    }

    /**
     * Répare tous les contacts existants pour les rendre bidirectionnels
     * 
     * @return int Nombre de contacts inverses créés
     */
    public function repairBidirectionalContacts(): int
    {
        $allContacts = $this->findAll();
        $repaired = 0;
        
        foreach ($allContacts as $contact) {
            $originalUserId = $contact->getUserId();
            $contactUserId = $contact->getContactUserId();
            
            // Vérifier si le contact inverse existe
            if (!$this->contactExists($contactUserId, $originalUserId)) {
                // Créer le contact inverse
                $userRepo = new \WhatsApp\Repositories\UserRepository($this->xmlManager);
                $originalUser = $userRepo->findById($originalUserId);
                
                if ($originalUser) {
                    $reverseContactId = 'contact_repair_' . time() . '_' . uniqid();
                    $reverseContact = new Contact(
                        $reverseContactId, 
                        $originalUser->getName(), 
                        $contactUserId, 
                        $originalUserId
                    );
                    
                    if ($this->create($reverseContact)) {
                        $repaired++;
                    }
                }
            }
        }
        
        return $repaired;
    }
} 