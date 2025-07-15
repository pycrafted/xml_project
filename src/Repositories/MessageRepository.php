<?php

namespace WhatsApp\Repositories;

use WhatsApp\Models\Message;
use WhatsApp\Utils\XMLManager;
use Exception;

/**
 * MessageRepository - Gestion des opérations CRUD pour les messages
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class MessageRepository
{
    private XMLManager $xmlManager;

    public function __construct(XMLManager $xmlManager)
    {
        $this->xmlManager = $xmlManager;
    }

    /**
     * Crée un nouveau message
     * 
     * @param Message $message
     * @return bool
     */
    public function create(Message $message): bool
    {
        $messageData = [
            'attributes' => ['id' => $message->getId()],
            'content' => $message->getContent(),
            'type' => $message->getType(),
            'timestamp' => $message->getTimestamp(),
            'status' => $message->getStatus(),
            'from_user' => $message->getFromUser()
        ];

        // Ajouter destinataire si défini
        if ($message->getToUser()) {
            $messageData['to_user'] = $message->getToUser();
        }

        if ($message->getToGroup()) {
            $messageData['to_group'] = $message->getToGroup();
        }

        if ($message->getFilePath()) {
            $messageData['file_path'] = $message->getFilePath();
        }

        return $this->xmlManager->addElement('//wa:messages', 'message', $messageData);
    }

    /**
     * Trouve un message par ID
     * 
     * @param string $id
     * @return Message|null
     */
    public function findById(string $id): ?Message
    {
        $element = $this->xmlManager->findElementById('message', $id);
        if (!$element) {
            return null;
        }

        return $this->elementToMessage($element);
    }

    /**
     * Trouve tous les messages
     * 
     * @return Message[]
     */
    public function findAll(): array
    {
        $simpleXML = $this->xmlManager->getSimpleXML();
        $messages = [];

        // Gérer les namespaces
        $namespaces = $simpleXML->getNamespaces(true);
        $defaultNS = $namespaces[''] ?? null;
        
        if ($defaultNS) {
            $messagesNode = $simpleXML->children($defaultNS)->messages;
            if ($messagesNode) {
                $messageNodes = $messagesNode->children($defaultNS);
                foreach ($messageNodes as $messageXml) {
                    $attributes = $messageXml->attributes();
                    $id = (string) $attributes['id'];
                    
                    if (!empty($id)) {
                        $message = new Message(
                            $id,
                            (string) $messageXml->children($defaultNS)->content,
                            (string) $messageXml->children($defaultNS)->from_user,
                            (string) $messageXml->children($defaultNS)->type
                        );
                        
                        $message->setTimestamp((string) $messageXml->children($defaultNS)->timestamp);
                        $message->setStatus((string) $messageXml->children($defaultNS)->status);
                        
                        // Destinataires optionnels
                        $toUserNode = $messageXml->children($defaultNS)->to_user;
                        if ($toUserNode && !empty((string) $toUserNode)) {
                            $message->setToUser((string) $toUserNode);
                        }
                        
                        $toGroupNode = $messageXml->children($defaultNS)->to_group;
                        if ($toGroupNode && !empty((string) $toGroupNode)) {
                            $message->setToGroup((string) $toGroupNode);
                        }
                        
                        $filePathNode = $messageXml->children($defaultNS)->file_path;
                        if ($filePathNode && !empty((string) $filePathNode)) {
                            $message->setFilePath((string) $filePathNode);
                        }
                        
                        $messages[] = $message;
                    }
                }
            }
        }

        return $messages;
    }

    /**
     * Met à jour un message
     * 
     * @param Message $message
     * @return bool
     */
    public function update(Message $message): bool
    {
        // Supprimer l'ancien et ajouter le nouveau
        $this->xmlManager->deleteElementById('message', $message->getId());
        return $this->create($message);
    }

    /**
     * Supprime un message
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        return $this->xmlManager->deleteElementById('message', $id);
    }

    /**
     * Trouve les messages d'un utilisateur
     * 
     * @param string $userId
     * @return Message[]
     */
    public function findByUser(string $userId): array
    {
        $allMessages = $this->findAll();
        return array_values(array_filter($allMessages, fn($msg) => 
            $msg->getFromUser() === $userId || $msg->getToUser() === $userId
        ));
    }

    /**
     * Alias pour findByUser - compatibilité interface web
     * 
     * @param string $userId
     * @return Message[]
     */
    public function getMessagesByUserId(string $userId): array
    {
        return $this->findByUser($userId);
    }

    /**
     * Trouve les messages d'un groupe
     * 
     * @param string $groupId
     * @return Message[]
     */
    public function findByGroup(string $groupId): array
    {
        $allMessages = $this->findAll();
        return array_values(array_filter($allMessages, fn($msg) => 
            $msg->getToGroup() === $groupId
        ));
    }

    /**
     * Alias pour findByGroup - compatibilité interface web
     * 
     * @param string $groupId
     * @return Message[]
     */
    public function getGroupMessages(string $groupId): array
    {
        return $this->findByGroup($groupId);
    }

    /**
     * Trouve les messages entre deux utilisateurs
     * 
     * @param string $user1Id
     * @param string $user2Id
     * @return Message[]
     */
    public function findConversation(string $user1Id, string $user2Id): array
    {
        $allMessages = $this->findAll();
        return array_values(array_filter($allMessages, fn($msg) => 
            ($msg->getFromUser() === $user1Id && $msg->getToUser() === $user2Id) ||
            ($msg->getFromUser() === $user2Id && $msg->getToUser() === $user1Id)
        ));
    }

    /**
     * Alias pour findConversation - compatibilité interface web
     * 
     * @param string $user1Id
     * @param string $user2Id
     * @return Message[]
     */
    public function getMessagesBetweenUsers(string $user1Id, string $user2Id): array
    {
        return $this->findConversation($user1Id, $user2Id);
    }

    /**
     * Supprime tous les messages d'un utilisateur spécifique
     * 
     * @param string $userId
     * @return int Nombre de messages supprimés
     */
    public function deleteByUserId(string $userId): int
    {
        $messages = $this->findByUser($userId);
        $deleted = 0;
        
        foreach ($messages as $message) {
            if ($this->delete($message->getId())) {
                $deleted++;
            }
        }
        
        return $deleted;
    }

    /**
     * Supprime tous les messages entre deux utilisateurs
     * 
     * @param string $user1Id
     * @param string $user2Id
     * @return int Nombre de messages supprimés
     */
    public function deleteConversation(string $user1Id, string $user2Id): int
    {
        $messages = $this->findConversation($user1Id, $user2Id);
        $deleted = 0;
        
        foreach ($messages as $message) {
            if ($this->delete($message->getId())) {
                $deleted++;
            }
        }
        
        return $deleted;
    }

    /**
     * Supprime tous les messages orphelins (où le destinataire n'existe plus)
     * 
     * @return int Nombre de messages supprimés
     */
    public function cleanOrphanedMessages(): int
    {
        $allMessages = $this->findAll();
        $deleted = 0;
        
        // Obtenir les repositories pour validation
        $userRepo = new \WhatsApp\Repositories\UserRepository($this->xmlManager);
        $contactRepo = new \WhatsApp\Repositories\ContactRepository($this->xmlManager);
        
        foreach ($allMessages as $message) {
            $isOrphaned = false;
            
            // Vérifier si l'expéditeur existe encore
            if (!$userRepo->exists($message->getFromUser())) {
                $isOrphaned = true;
            }
            
            // Vérifier si le destinataire existe encore (pour messages privés)
            if ($message->getToUser()) {
                if (!$userRepo->exists($message->getToUser())) {
                    $isOrphaned = true;
                }
                // Vérifier si le contact existe encore
                elseif (!$contactRepo->contactExists($message->getFromUser(), $message->getToUser())) {
                    $isOrphaned = true;
                }
            }
            
            if ($isOrphaned && $this->delete($message->getId())) {
                $deleted++;
            }
        }
        
        return $deleted;
    }

    /**
     * Convertit un élément DOM en objet Message
     */
    private function elementToMessage(\DOMElement $element): Message
    {
        $message = new Message(
            $element->getAttribute('id'),
            $element->getElementsByTagName('content')->item(0)->textContent,
            $element->getElementsByTagName('from_user')->item(0)->textContent,
            $element->getElementsByTagName('type')->item(0)->textContent
        );

        $message->setTimestamp($element->getElementsByTagName('timestamp')->item(0)->textContent);
        $message->setStatus($element->getElementsByTagName('status')->item(0)->textContent);

        // Destinataires optionnels
        $toUserElement = $element->getElementsByTagName('to_user')->item(0);
        if ($toUserElement) {
            $message->setToUser($toUserElement->textContent);
        }

        $toGroupElement = $element->getElementsByTagName('to_group')->item(0);
        if ($toGroupElement) {
            $message->setToGroup($toGroupElement->textContent);
        }

        $filePathElement = $element->getElementsByTagName('file_path')->item(0);
        if ($filePathElement) {
            $message->setFilePath($filePathElement->textContent);
        }

        return $message;
    }
} 