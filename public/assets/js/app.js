/**
 * WhatsApp Web Clone - JavaScript Principal
 * Gestion de l'interactivité et des fonctionnalités AJAX
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===========================================
    // GESTION NAVIGATION
    // ===========================================
    
    // Gestion des liens de navigation
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Retirer la classe active de tous les éléments
            navItems.forEach(nav => nav.classList.remove('active'));
            // Ajouter la classe active à l'élément cliqué
            this.classList.add('active');
        });
    });

    // ===========================================
    // GESTION FORMULAIRES
    // ===========================================
    
    // Validation en temps réel des formulaires
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showAlert('Veuillez corriger les erreurs dans le formulaire', 'error');
            }
        });
    });

    // ===========================================
    // GESTION CHAT
    // ===========================================
    
    // Envoi de messages via AJAX
    const chatForm = document.getElementById('chat-form');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }

    // Auto-scroll des messages
    const chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        scrollToBottom(chatMessages);
    }

    // ===========================================
    // RECHERCHE EN TEMPS RÉEL
    // ===========================================
    
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const listItems = document.querySelectorAll('.list-group-item');
            
            listItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // ===========================================
    // NOTIFICATIONS
    // ===========================================
    
    // Auto-hide alerts après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// ===========================================
// FONCTIONS UTILITAIRES
// ===========================================

/**
 * Valide un formulaire
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    return isValid;
}

/**
 * Affiche une alerte
 */
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <strong>${type === 'error' ? 'Erreur' : 'Information'}</strong>
        ${message}
    `;
    
    const container = document.querySelector('.content-body') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-hide après 5 secondes
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}

/**
 * Scroll automatique vers le bas
 */
function scrollToBottom(element) {
    element.scrollTop = element.scrollHeight;
}

/**
 * Envoi de message AJAX
 */
function sendMessage() {
    const messageInput = document.getElementById('message-input');
    const chatMessages = document.querySelector('.chat-messages');
    const recipientId = document.getElementById('recipient-id');
    
    if (!messageInput.value.trim()) {
        showAlert('Veuillez saisir un message', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('content', messageInput.value);
    formData.append('recipient_id', recipientId ? recipientId.value : '');
    
    fetch('ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ajouter le message à l'interface
            addMessageToChat(data.message, 'sent');
            messageInput.value = '';
            scrollToBottom(chatMessages);
            showAlert('Message envoyé', 'success');
        } else {
            showAlert(data.error || 'Erreur lors de l\'envoi', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion', 'error');
    });
}

/**
 * Ajoute un message au chat
 */
function addMessageToChat(messageData, type = 'received') {
    const chatMessages = document.querySelector('.chat-messages');
    if (!chatMessages) return;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.innerHTML = `
        <div class="message-content">${escapeHtml(messageData.content)}</div>
        <div class="message-time">${messageData.timestamp || new Date().toLocaleTimeString()}</div>
    `;
    
    chatMessages.appendChild(messageDiv);
    scrollToBottom(chatMessages);
}

/**
 * Échappe le HTML pour éviter les XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Charge les messages via AJAX
 */
function loadMessages(conversationId) {
    // Vérifier que l'ID de conversation est valide
    if (!conversationId || conversationId === 'undefined') {
        console.log('ID de conversation invalide:', conversationId);
        return;
    }
    
    fetch(`ajax.php?action=get_messages&conversation_id=${conversationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Vérifier qu'on est toujours sur la même conversation
                const currentConversationElement = document.getElementById('conversation-id');
                if (currentConversationElement && currentConversationElement.value !== conversationId) {
                    console.log('Conversation changée, ignore les messages');
                    return;
                }
                
                const chatMessages = document.querySelector('.chat-messages');
                if (chatMessages) {
                    chatMessages.innerHTML = '';
                    
                    data.messages.forEach(message => {
                        addMessageToChat(message, message.is_sent ? 'sent' : 'received');
                    });
                    
                    scrollToBottom(chatMessages);
                }
            } else {
                console.error('Erreur récupération messages:', data.error);
            }
        })
        .catch(error => {
            console.error('Erreur chargement messages:', error);
        });
}

/**
 * Rafraîchissement automatique des messages
 */
function autoRefreshMessages() {
    // Vérifier qu'on est sur la page de chat
    if (!window.location.pathname.includes('chat.php')) {
        return;
    }
    
    const conversationId = document.getElementById('conversation-id');
    if (conversationId && conversationId.value) {
        loadMessagesIfActive(conversationId.value);
    }
}

// Variable pour stocker l'ID de conversation actuelle
let currentConversationId = null;

// Fonction pour charger les messages seulement si la conversation est active
function loadMessagesIfActive(conversationId) {
    // Vérifier si on est toujours sur la même conversation
    if (currentConversationId && currentConversationId !== conversationId) {
        return; // Ne pas charger si on a changé de conversation
    }
    
    currentConversationId = conversationId;
    loadMessages(conversationId);
}

/**
 * Réinitialise la conversation active (appelé quand on change de conversation)
 */
function resetCurrentConversation() {
    currentConversationId = null;
    const conversationElement = document.getElementById('conversation-id');
    if (conversationElement && conversationElement.value) {
        currentConversationId = conversationElement.value;
        console.log('Conversation réinitialisée:', currentConversationId);
    }
}

// Rafraîchir les messages toutes les 3 secondes seulement sur la page de chat
if (window.location.pathname.includes('chat.php')) {
    setInterval(autoRefreshMessages, 3000);
}

/**
 * Gestion du modal de création de groupe
 */
function openGroupModal() {
    const modal = document.getElementById('group-modal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeGroupModal() {
    const modal = document.getElementById('group-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Confirmation de suppression
 */
function confirmDelete(itemType, itemId, itemName) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${itemType} "${itemName}" ?`)) {
        // Redirection vers la page de suppression
        window.location.href = `${itemType}s.php?action=delete&id=${itemId}`;
    }
}

/**
 * Gestion des uploads de fichiers
 */
function handleFileUpload() {
    const fileInput = document.getElementById('file-input');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Vérification de la taille (max 10MB)
                if (file.size > 10 * 1024 * 1024) {
                    showAlert('Le fichier ne doit pas dépasser 10MB', 'error');
                    this.value = '';
                    return;
                }
                
                // Vérification du type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
                if (!allowedTypes.includes(file.type)) {
                    showAlert('Type de fichier non autorisé', 'error');
                    this.value = '';
                    return;
                }
                
                showAlert(`Fichier "${file.name}" sélectionné`, 'info');
            }
        });
    }
}

// Initialiser la gestion des uploads
handleFileUpload(); 