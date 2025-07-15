<?php

namespace WhatsApp\Services;

use WhatsApp\Models\Message;
use WhatsApp\Repositories\MessageRepository;
use WhatsApp\Repositories\UserRepository;
use WhatsApp\Repositories\GroupRepository;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Utils\XMLManager;
use Exception;

/**
 * MessageService - Couche de logique métier pour les messages
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class MessageService
{
    private MessageRepository $messageRepository;
    private UserRepository $userRepository;
    private GroupRepository $groupRepository;
    private ContactRepository $contactRepository;

    public function __construct(XMLManager $xmlManager)
    {
        $this->messageRepository = new MessageRepository($xmlManager);
        $this->userRepository = new UserRepository($xmlManager);
        $this->groupRepository = new GroupRepository($xmlManager);
        $this->contactRepository = new ContactRepository($xmlManager);
    }

    /**
     * Envoie un message privé
     * 
     * @param string $fromUserId
     * @param string $toUserId
     * @param string $content
     * @param string $type
     * @return Message
     */
    public function sendPrivateMessage(string $fromUserId, string $toUserId, string $content, string $type = 'text'): Message
    {
        $this->validateMessageCreation($fromUserId, $content);
        
        // Validation renforcée des références
        if (!$this->userRepository->exists($fromUserId)) {
            throw new Exception("Expéditeur non trouvé : {$fromUserId}");
        }
        
        if (!$this->userRepository->exists($toUserId)) {
            throw new Exception("Destinataire non trouvé : {$toUserId}");
        }
        
        // Vérifier qu'il existe un contact entre les deux utilisateurs
        if (!$this->contactRepository->contactExists($fromUserId, $toUserId)) {
            throw new Exception("Aucun contact existant entre {$fromUserId} et {$toUserId}. Ajoutez d'abord ce contact.");
        }

        $messageId = $this->generateMessageId();
        $message = new Message($messageId, $content, $fromUserId, $type);
        $message->setRecipientUser($toUserId);

        if (!$this->messageRepository->create($message)) {
            throw new Exception("Échec d'envoi du message");
        }

        return $message;
    }

    /**
     * Envoie un message de groupe
     * 
     * @param string $fromUserId
     * @param string $groupId
     * @param string $content
     * @param string $type
     * @return Message
     */
    public function sendGroupMessage(string $fromUserId, string $groupId, string $content, string $type = 'text'): Message
    {
        $this->validateMessageCreation($fromUserId, $content);
        
        $group = $this->groupRepository->findById($groupId);
        if (!$group) {
            throw new Exception("Groupe non trouvé : {$groupId}");
        }

        if (!$group->isMember($fromUserId)) {
            throw new Exception("Utilisateur n'est pas membre du groupe");
        }

        $messageId = $this->generateMessageId();
        $message = new Message($messageId, $content, $fromUserId, $type);
        $message->setRecipientGroup($groupId);

        if (!$this->messageRepository->create($message)) {
            throw new Exception("Échec d'envoi du message");
        }

        return $message;
    }

    /**
     * Marque un message comme lu
     * 
     * @param string $messageId
     * @param string $userId
     * @return bool
     */
    public function markAsRead(string $messageId, string $userId): bool
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            throw new Exception("Message non trouvé : {$messageId}");
        }

        // Vérifier que l'utilisateur peut marquer ce message
        if ($message->getToUser() !== $userId && !$this->isUserInGroup($userId, $message->getToGroup())) {
            throw new Exception("Utilisateur non autorisé à marquer ce message");
        }

        $message->markAsRead();
        return $this->messageRepository->update($message);
    }

    /**
     * Obtient la conversation entre deux utilisateurs
     * 
     * @param string $user1Id
     * @param string $user2Id
     * @return Message[]
     */
    public function getConversation(string $user1Id, string $user2Id): array
    {
        if (!$this->userRepository->exists($user1Id) || !$this->userRepository->exists($user2Id)) {
            throw new Exception("Un des utilisateurs n'existe pas");
        }

        // Vérifier qu'il existe un contact entre les utilisateurs
        if (!$this->contactRepository->contactExists($user1Id, $user2Id) && 
            !$this->contactRepository->contactExists($user2Id, $user1Id)) {
            throw new Exception("Aucun contact existant entre ces utilisateurs");
        }

        $messages = $this->messageRepository->findConversation($user1Id, $user2Id);
        
        // Filtrer les messages valides (expéditeur et destinataire existent)
        $validMessages = array_filter($messages, function($message) {
            return $this->userRepository->exists($message->getFromUser()) && 
                   $this->userRepository->exists($message->getToUser());
        });
        
        // Trier par timestamp
        usort($validMessages, fn($a, $b) => strcmp($a->getTimestamp(), $b->getTimestamp()));
        
        return array_values($validMessages);
    }

    /**
     * Obtient les messages d'un groupe
     * 
     * @param string $groupId
     * @param string $userId
     * @return Message[]
     */
    public function getGroupMessages(string $groupId, string $userId): array
    {
        $group = $this->groupRepository->findById($groupId);
        if (!$group) {
            throw new Exception("Groupe non trouvé : {$groupId}");
        }

        if (!$group->isMember($userId)) {
            throw new Exception("Utilisateur n'est pas membre du groupe");
        }

        $messages = $this->messageRepository->findByGroup($groupId);
        
        // Trier par timestamp
        usort($messages, fn($a, $b) => strcmp($a->getTimestamp(), $b->getTimestamp()));
        
        return $messages;
    }

    /**
     * Obtient les statistiques de messages
     * 
     * @return array
     */
    public function getMessageStats(): array
    {
        $messages = $this->messageRepository->findAll();
        
        return [
            'total' => count($messages),
            'private' => count(array_filter($messages, fn($m) => $m->isPrivateMessage())),
            'group' => count(array_filter($messages, fn($m) => $m->isGroupMessage())),
            'with_files' => count(array_filter($messages, fn($m) => $m->hasFile())),
            'read' => count(array_filter($messages, fn($m) => $m->getStatus() === 'read')),
            'sent' => count(array_filter($messages, fn($m) => $m->getStatus() === 'sent'))
        ];
    }

    /**
     * Validation des règles métier
     */
    private function validateMessageCreation(string $fromUserId, string $content): void
    {
        if (!$this->userRepository->exists($fromUserId)) {
            throw new Exception("Expéditeur non trouvé : {$fromUserId}");
        }

        if (empty(trim($content))) {
            throw new Exception("Le contenu du message ne peut pas être vide");
        }

        if (strlen($content) > 4000) {
            throw new Exception("Le message ne peut pas dépasser 4000 caractères");
        }
    }

    /**
     * Vérifie si un utilisateur est membre d'un groupe
     */
    private function isUserInGroup(string $userId, ?string $groupId): bool
    {
        if (!$groupId) {
            return false;
        }

        $group = $this->groupRepository->findById($groupId);
        return $group && $group->isMember($userId);
    }

    /**
     * Nettoie tous les messages orphelins du système
     * 
     * @return array Statistiques de nettoyage
     */
    public function cleanupOrphanedData(): array
    {
        $orphanedMessages = $this->messageRepository->cleanOrphanedMessages();
        
        return [
            'orphaned_messages_deleted' => $orphanedMessages,
            'cleanup_timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Valide l'intégrité des données de messagerie
     * 
     * @return array Rapport de validation
     */
    public function validateDataIntegrity(): array
    {
        $report = [
            'total_messages' => 0,
            'invalid_messages' => 0,
            'missing_users' => [],
            'missing_contacts' => [],
            'validation_timestamp' => date('Y-m-d H:i:s')
        ];
        
        $allMessages = $this->messageRepository->findAll();
        $report['total_messages'] = count($allMessages);
        
        foreach ($allMessages as $message) {
            $isValid = true;
            
            // Vérifier l'expéditeur
            if (!$this->userRepository->exists($message->getFromUser())) {
                $report['missing_users'][] = $message->getFromUser();
                $isValid = false;
            }
            
            // Vérifier le destinataire (messages privés)
            if ($message->getToUser()) {
                if (!$this->userRepository->exists($message->getToUser())) {
                    $report['missing_users'][] = $message->getToUser();
                    $isValid = false;
                }
                
                // Vérifier le contact
                if (!$this->contactRepository->contactExists($message->getFromUser(), $message->getToUser())) {
                    $report['missing_contacts'][] = [
                        'from' => $message->getFromUser(),
                        'to' => $message->getToUser(),
                        'message_id' => $message->getId()
                    ];
                    $isValid = false;
                }
            }
            
            if (!$isValid) {
                $report['invalid_messages']++;
            }
        }
        
        // Supprimer les doublons
        $report['missing_users'] = array_unique($report['missing_users']);
        
        return $report;
    }

    /**
     * Génère un ID unique pour les messages (max 20 chars pour XSD)
     */
    private function generateMessageId(): string
    {
        return 'msg_' . substr(uniqid(), -8) . '_' . substr(time(), -4);
    }
} 