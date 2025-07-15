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
    
    // Les alertes sont gérées par la fonction showAlert()
    // Pas besoin de code automatique ici
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
function showAlert(message, type = 'info', persistent = false) {
    console.log(`[ALERT] ${type.toUpperCase()}: ${message}`);
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <strong>${type === 'error' ? 'Erreur' : type === 'success' ? 'Succès' : 'Information'}</strong>
        ${message}
        ${persistent ? '<button onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 16px; cursor: pointer;">×</button>' : ''}
    `;
    
    const container = document.querySelector('.content-body') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-hide après 15 secondes, sauf si persistent
    if (!persistent) {
    setTimeout(() => {
            alertDiv.style.transition = 'opacity 0.5s ease-out';
        alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 500);
        }, 15000); // Augmenté à 15 secondes pour une meilleure lisibilité
    }
}

/**
 * Affiche une alerte de débogage (persiste jusqu'à fermeture manuelle)
 */
function showDebugAlert(message, type = 'info') {
    showAlert(`[DEBUG] ${message}`, type, true);
}

/**
 * Système de logging avancé
 */
const Logger = {
    logs: [],
    
    log: function(level, message, data = null) {
        const timestamp = new Date().toISOString();
        const logEntry = { timestamp, level, message, data };
        
        this.logs.push(logEntry);
        console.log(`[${timestamp}] [${level}] ${message}`, data || '');
        
        // Garder seulement les 100 derniers logs
        if (this.logs.length > 100) {
            this.logs.shift();
        }
    },
    
    info: function(message, data = null) {
        this.log('INFO', message, data);
    },
    
    error: function(message, data = null) {
        this.log('ERROR', message, data);
        showDebugAlert(`ERROR: ${message}`, 'error');
    },
    
    success: function(message, data = null) {
        this.log('SUCCESS', message, data);
    },
    
    debug: function(message, data = null) {
        this.log('DEBUG', message, data);
    },
    
    getLogs: function() {
        return this.logs;
    },
    
    clearLogs: function() {
        this.logs = [];
    },
    
    exportLogs: function() {
        const logsText = this.logs.map(log => 
            `[${log.timestamp}] [${log.level}] ${log.message}${log.data ? ' | Data: ' + JSON.stringify(log.data) : ''}`
        ).join('\n');
        
        const blob = new Blob([logsText], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `whatsapp_logs_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    }
};

// Ajouter un panneau de debug (accessible via F12 ou Ctrl+Shift+D)
document.addEventListener('keydown', function(e) {
    if ((e.key === 'F12') || (e.ctrlKey && e.shiftKey && e.key === 'D')) {
        e.preventDefault();
        showDebugPanel();
    }
});

function showDebugPanel() {
    const debugPanel = document.createElement('div');
    debugPanel.id = 'debug-panel';
    debugPanel.innerHTML = `
        <div style="position: fixed; top: 10px; right: 10px; width: 400px; max-height: 500px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; z-index: 9999; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="background: #007bff; color: white; padding: 10px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <span>🔧 Debug Panel</span>
                <button onclick="document.getElementById('debug-panel').remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">×</button>
            </div>
            <div style="padding: 15px; max-height: 400px; overflow-y: auto;">
                <button onclick="Logger.clearLogs(); showDebugAlert('Logs cleared', 'info')" style="margin-bottom: 10px; padding: 5px 10px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Clear Logs</button>
                <button onclick="Logger.exportLogs()" style="margin-bottom: 10px; margin-left: 5px; padding: 5px 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">Export Logs</button>
                <div id="debug-logs" style="background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">
                    ${Logger.getLogs().map(log => `<div style="margin-bottom: 5px; ${log.level === 'ERROR' ? 'color: red;' : log.level === 'SUCCESS' ? 'color: green;' : ''}"><strong>[${log.level}]</strong> ${log.message}</div>`).join('')}
                </div>
            </div>
        </div>
    `;
    
    // Supprimer le panneau existant s'il y en a un
    const existingPanel = document.getElementById('debug-panel');
    if (existingPanel) {
        existingPanel.remove();
    }
    
    document.body.appendChild(debugPanel);
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
    Logger.info('Début de l\'envoi de message');
    
    const messageInput = document.getElementById('message-input');
    const chatMessages = document.querySelector('.chat-messages');
    const recipientId = document.getElementById('recipient-id');
    const conversationId = document.getElementById('conversation-id');
    
    Logger.debug('Éléments DOM récupérés', {
        messageInput: messageInput ? 'OK' : 'MANQUANT',
        chatMessages: chatMessages ? 'OK' : 'MANQUANT',
        recipientId: recipientId ? recipientId.value : 'MANQUANT',
        conversationId: conversationId ? conversationId.value : 'MANQUANT'
    });
    
    if (!messageInput.value.trim()) {
        Logger.error('Message vide');
        showAlert('Veuillez saisir un message', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('content', messageInput.value);
    
    Logger.debug('Contenu du message', { content: messageInput.value });
    
    // Pour les conversations privées, utiliser recipient_id
    if (recipientId && recipientId.value) {
        formData.append('recipient_id', recipientId.value);
        Logger.info('Message privé', { recipient_id: recipientId.value });
    }
    // Pour les groupes, utiliser group_id
    else if (conversationId && conversationId.value.startsWith('group_')) {
        const groupId = conversationId.value.substring(6); // Enlever "group_"
        formData.append('group_id', groupId);
        formData.append('action', 'send_group_message');
        Logger.info('Message de groupe', { group_id: groupId });
    }
    else {
        Logger.error('Destinataire non spécifié');
        showAlert('Destinataire non spécifié', 'error');
        return;
    }
    
    Logger.info('Envoi de la requête AJAX');
    
    fetch('ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        Logger.debug('Réponse reçue', { status: response.status, statusText: response.statusText });
        return response.json();
    })
    .then(data => {
        Logger.debug('Données JSON reçues', data);
        
        if (data.success) {
            // Ajouter le message à l'interface
            addMessageToChat(data.message, 'sent');
            messageInput.value = '';
            scrollToBottom(chatMessages);
            Logger.success('Message envoyé avec succès');
            showAlert('Message envoyé', 'success');
        } else {
            Logger.error('Erreur lors de l\'envoi', data);
            showAlert(data.error || 'Erreur lors de l\'envoi', 'error');
        }
    })
    .catch(error => {
        Logger.error('Erreur de connexion', error);
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