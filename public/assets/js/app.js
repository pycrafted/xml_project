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
    
    // Validation en temps réel des formulaires (sauf chat-form qui a sa propre validation)
    const forms = document.querySelectorAll('form:not(#chat-form)');
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

    // Gestion du bouton de sélection de fichier
    const fileButton = document.getElementById('file-button');
    const fileInput = document.getElementById('file-input');
    const filePreview = document.getElementById('file-preview');
    const fileInfo = document.getElementById('file-info');
    const removeFileButton = document.getElementById('remove-file');
    
    if (fileButton && fileInput) {
        fileButton.addEventListener('click', function() {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                handleFileSelection(file);
            }
        });
    }
    
    if (removeFileButton) {
        removeFileButton.addEventListener('click', function() {
            clearFileSelection();
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

// Système d'alertes avec historique et mode debug
const AlertManager = {
    alerts: [],
    debugMode: false,
    freezeMode: false,  // Nouveau: mode congélation des alertes
    persistentErrorCapture: true,  // Capture automatique des erreurs
    
    // Activer/désactiver le mode debug
    toggleDebugMode: function() {
        this.debugMode = !this.debugMode;
        console.log(`Mode debug alertes: ${this.debugMode ? 'ACTIVÉ' : 'DÉSACTIVÉ'}`);
        showDebugAlert(`Mode debug alertes ${this.debugMode ? 'ACTIVÉ' : 'DÉSACTIVÉ'}`, 'info');
    },
    
    // Activer/désactiver le mode freeze (alertes permanentes)
    toggleFreezeMode: function() {
        this.freezeMode = !this.freezeMode;
        console.log(`Mode freeze alertes: ${this.freezeMode ? 'ACTIVÉ - Alertes permanentes' : 'DÉSACTIVÉ'}`);
        showDebugAlert(`Mode freeze alertes ${this.freezeMode ? 'ACTIVÉ - Alertes permanentes' : 'DÉSACTIVÉ'}`, 'info');
    },
    
    // Sauvegarder une alerte dans l'historique
    saveAlert: function(message, type, timestamp = null) {
        const alert = {
            message: message,
            type: type,
            timestamp: timestamp || new Date().toISOString(),
            id: Date.now() + Math.random()
        };
        
        this.alerts.push(alert);
        
        // Garder seulement les 50 dernières alertes
        if (this.alerts.length > 50) {
            this.alerts.shift();
        }
        
        return alert;
    },
    
    // Afficher toutes les alertes dans la console
    showHistory: function() {
        console.log('=== HISTORIQUE DES ALERTES ===');
        this.alerts.forEach((alert, index) => {
            console.log(`${index + 1}. [${alert.type.toUpperCase()}] ${alert.timestamp} - ${alert.message}`);
        });
    },
    
    // Exporter l'historique
    exportHistory: function() {
        const alertsText = this.alerts.map(alert => 
            `[${alert.timestamp}] [${alert.type.toUpperCase()}] ${alert.message}`
        ).join('\n');
        
        const blob = new Blob([alertsText], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `alerts_history_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    },
    
    // Vider l'historique
    clearHistory: function() {
        this.alerts = [];
        console.log('Historique des alertes vidé');
    },
    
    // Nettoyer toutes les alertes visibles à l'écran
    clearAllAlerts: function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => alert.remove());
        console.log('Toutes les alertes visibles supprimées');
    },
    
    // Sauvegarder une alerte dans localStorage pour survivre aux rechargements
    savePersistentAlert: function(message, type, timestamp) {
        if (!this.persistentErrorCapture) return;
        
        const persistentAlerts = JSON.parse(localStorage.getItem('whatsapp_persistent_alerts') || '[]');
        const alert = {
            message: message,
            type: type,
            timestamp: timestamp,
            id: Date.now() + Math.random(),
            url: window.location.href
        };
        
        persistentAlerts.push(alert);
        
        // Garder seulement les 20 dernières alertes persistantes
        if (persistentAlerts.length > 20) {
            persistentAlerts.shift();
        }
        
        localStorage.setItem('whatsapp_persistent_alerts', JSON.stringify(persistentAlerts));
    },
    
    // Afficher les alertes sauvegardées au rechargement
    showPersistentAlerts: function() {
        const persistentAlerts = JSON.parse(localStorage.getItem('whatsapp_persistent_alerts') || '[]');
        if (persistentAlerts.length === 0) return;
        
        // Créer une section spéciale pour les alertes persistantes
        const persistentSection = document.createElement('div');
        persistentSection.id = 'persistent-alerts-section';
        persistentSection.innerHTML = `
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0; color: #856404;">
                    🔄 Alertes Récupérées (${persistentAlerts.length})
                    <button onclick="AlertManager.clearPersistentAlerts()" style="float: right; background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                        🗑️ Effacer
                    </button>
                </h4>
                <div id="persistent-alerts-content"></div>
            </div>
        `;
        
        const container = document.querySelector('.content-body') || document.body;
        container.insertBefore(persistentSection, container.firstChild);
        
        // Afficher chaque alerte persistante
        const content = document.getElementById('persistent-alerts-content');
        persistentAlerts.forEach(alert => {
            const alertDiv = document.createElement('div');
            alertDiv.style.cssText = `
                background: ${alert.type === 'error' ? '#f8d7da' : alert.type === 'success' ? '#d4edda' : '#d1ecf1'};
                color: ${alert.type === 'error' ? '#721c24' : alert.type === 'success' ? '#155724' : '#0c5460'};
                border: 1px solid ${alert.type === 'error' ? '#f5c6cb' : alert.type === 'success' ? '#c3e6cb' : '#b8daff'};
                padding: 8px 12px;
                margin: 5px 0;
                border-radius: 4px;
                font-size: 13px;
                border-left: 4px solid #ff6b6b;
            `;
            
            const timeAgo = this.getTimeAgo(alert.timestamp);
            alertDiv.innerHTML = `
                <strong>${alert.type === 'error' ? '❌' : alert.type === 'success' ? '✅' : '💡'}</strong>
                ${alert.message}
                <div style="font-size: 11px; opacity: 0.7; margin-top: 5px;">
                    ⏰ ${timeAgo} | 📍 ${alert.url}
                </div>
            `;
            content.appendChild(alertDiv);
        });
        
        console.log(`📋 ${persistentAlerts.length} alertes persistantes récupérées`);
    },
    
    // Vider les alertes persistantes
    clearPersistentAlerts: function() {
        localStorage.removeItem('whatsapp_persistent_alerts');
        const section = document.getElementById('persistent-alerts-section');
        if (section) section.remove();
        console.log('Alertes persistantes effacées');
    },
    
    // Calculer le temps écoulé depuis une alerte
    getTimeAgo: function(timestamp) {
        const now = new Date();
        const alertTime = new Date(timestamp);
        const diffMs = now - alertTime;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        
        if (diffMins < 1) return 'Il y a moins d\'une minute';
        if (diffMins < 60) return `Il y a ${diffMins} minute${diffMins > 1 ? 's' : ''}`;
        if (diffHours < 24) return `Il y a ${diffHours} heure${diffHours > 1 ? 's' : ''}`;
        return `Il y a ${Math.floor(diffHours / 24)} jour${Math.floor(diffHours / 24) > 1 ? 's' : ''}`;
    }
};

/**
 * Affiche une alerte (VERSION AMÉLIORÉE AVEC DEBUG)
 */
function showAlert(message, type = 'info', persistent = false) {
    const timestamp = new Date().toISOString();
    
    // Sauvegarder dans l'historique
    AlertManager.saveAlert(message, type, timestamp);
    
    // Sauvegarder dans localStorage pour survivre aux rechargements
    AlertManager.savePersistentAlert(message, type, timestamp);
    
    // Log dans la console
    console.log(`[ALERT] ${type.toUpperCase()}: ${message}`);
    
    // Si mode freeze, rendre l'alerte permanente
    if (AlertManager.freezeMode) {
        persistent = true;
        message = `[FREEZE] ${message} | ${timestamp}`;
    }
    // Si mode debug, faire l'alerte persistante et ajouter des infos
    else if (AlertManager.debugMode) {
        persistent = true;
        message = `[DEBUG] ${message} | ${timestamp}`;
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.cssText = `
        background: ${type === 'error' ? '#f8d7da' : type === 'success' ? '#d4edda' : '#d1ecf1'};
        color: ${type === 'error' ? '#721c24' : type === 'success' ? '#155724' : '#0c5460'};
        border: 1px solid ${type === 'error' ? '#f5c6cb' : type === 'success' ? '#c3e6cb' : '#b8daff'};
        padding: 10px 15px;
        margin: 10px 0;
        border-radius: 4px;
        position: relative;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
        ${AlertManager.freezeMode ? 'border-left: 5px solid #ff6b6b; box-shadow: 0 0 10px rgba(255,107,107,0.3);' : ''}
    `;
    
    alertDiv.innerHTML = `
        <strong>${type === 'error' ? '❌ Erreur' : type === 'success' ? '✅ Succès' : '💡 Information'}</strong>
        <br>${message}
        <button onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 16px; cursor: pointer; position: absolute; top: 5px; right: 10px;">×</button>
        ${AlertManager.freezeMode ? '<div style="font-size: 10px; margin-top: 5px; opacity: 0.7; color: #ff6b6b; font-weight: bold;">❄️ Mode Freeze - Alerte permanente</div>' : ''}
        ${AlertManager.debugMode && !AlertManager.freezeMode ? '<div style="font-size: 10px; margin-top: 5px; opacity: 0.7;">Mode Debug - Alerte longue durée</div>' : ''}
    `;
    
    const container = document.querySelector('.content-body') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-hide après délai variable selon le mode
    if (!persistent) {
        // Durées augmentées: 30s en debug, 8s normal
        const hideDelay = AlertManager.debugMode ? 30000 : 8000; // 30s en debug, 8s normal
    setTimeout(() => {
            alertDiv.style.transition = 'opacity 0.5s ease-out';
        alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 500);
        }, hideDelay);
    }
    
    // Ajouter animation CSS si pas déjà présente
    if (!document.getElementById('alert-styles')) {
        const style = document.createElement('style');
        style.id = 'alert-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateY(-20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
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

// Exposer les outils de debug globalement
window.AlertManager = AlertManager;
window.Logger = Logger;
window.showDebugPanel = showDebugPanel;

// Système de capture automatique des erreurs JavaScript
window.addEventListener('error', function(event) {
    const errorMessage = `Erreur JavaScript: ${event.message} (${event.filename}:${event.lineno})`;
    console.error('❌ Erreur capturée:', errorMessage);
    
    // Sauvegarder l'erreur automatiquement
    AlertManager.savePersistentAlert(errorMessage, 'error', new Date().toISOString());
    
    // Afficher l'erreur si on est sur la page
    if (AlertManager.debugMode || AlertManager.freezeMode) {
        showAlert(errorMessage, 'error');
    }
});

// Capture des erreurs de promesses non gérées
window.addEventListener('unhandledrejection', function(event) {
    const errorMessage = `Promesse rejetée: ${event.reason}`;
    console.error('❌ Promesse rejetée capturée:', errorMessage);
    
    // Sauvegarder l'erreur automatiquement
    AlertManager.savePersistentAlert(errorMessage, 'error', new Date().toISOString());
    
    // Afficher l'erreur si on est sur la page
    if (AlertManager.debugMode || AlertManager.freezeMode) {
        showAlert(errorMessage, 'error');
    }
});

// Afficher les alertes persistantes au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Attendre un peu pour que le DOM soit complètement chargé
    setTimeout(() => {
        AlertManager.showPersistentAlerts();
    }, 1000);
});

// Afficher un message au démarrage
console.log('🔧 WhatsApp Debug Tools chargés !');
console.log('📋 Commandes disponibles :');
console.log('  - AlertManager.toggleDebugMode() : Activer/désactiver le mode debug alertes (30s durée)');
console.log('  - AlertManager.toggleFreezeMode() : Activer/désactiver le mode freeze (alertes permanentes)');
console.log('  - AlertManager.showHistory() : Afficher l\'historique des alertes');
console.log('  - AlertManager.exportHistory() : Exporter l\'historique');
console.log('  - AlertManager.clearAllAlerts() : Nettoyer toutes les alertes à l\'écran');
console.log('  - AlertManager.showPersistentAlerts() : Afficher les alertes sauvegardées');
console.log('  - AlertManager.clearPersistentAlerts() : Effacer les alertes sauvegardées');
console.log('  - Logger.showHistory() : Afficher l\'historique des logs');
console.log('  - showDebugPanel() : Ouvrir le panneau de debug');
console.log('  - F12 ou Ctrl+Shift+D : Ouvrir le panneau de debug');
console.log('');
console.log('💡 Nouvelles Fonctionnalités :');
console.log('  • Capture automatique des erreurs JavaScript');
console.log('  • Sauvegarde persistante dans localStorage');
console.log('  • Récupération des alertes après rechargement');
console.log('  • Mode Debug = Alertes de 30s avec informations détaillées');
console.log('  • Mode Freeze = Alertes permanentes jusqu\'à fermeture manuelle');
console.log('  • Les alertes freeze ont une bordure rouge et l\'icône ❄️');
console.log('  • Utilisez clearAllAlerts() pour nettoyer l\'écran rapidement');

function showDebugPanel() {
    const debugPanel = document.createElement('div');
    debugPanel.id = 'debug-panel';
    debugPanel.innerHTML = `
        <div style="position: fixed; top: 10px; right: 10px; width: 450px; max-height: 600px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; z-index: 9999; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="background: #007bff; color: white; padding: 10px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <span>🔧 Debug Panel</span>
                <button onclick="document.getElementById('debug-panel').remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">×</button>
            </div>
            <div style="padding: 15px; max-height: 500px; overflow-y: auto;">
                
                <!-- Section Alertes -->
                <div style="margin-bottom: 20px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                    <h5 style="margin: 0 0 10px 0; color: #856404;">🚨 Alertes</h5>
                    <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px;">
                        <button onclick="AlertManager.toggleDebugMode()" style="padding: 3px 8px; background: ${AlertManager.debugMode ? '#dc3545' : '#28a745'}; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            ${AlertManager.debugMode ? '⏸️ Désactiver' : '▶️ Activer'} Mode Debug
                        </button>
                        <button onclick="AlertManager.toggleFreezeMode()" style="padding: 3px 8px; background: ${AlertManager.freezeMode ? '#dc3545' : '#ff6b6b'}; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            ${AlertManager.freezeMode ? '🔥 Désactiver' : '❄️ Activer'} Mode Freeze
                        </button>
                        <button onclick="AlertManager.showHistory()" style="padding: 3px 8px; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            📜 Voir Historique
                        </button>
                        <button onclick="AlertManager.exportHistory()" style="padding: 3px 8px; background: #17a2b8; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            💾 Exporter
                        </button>
                        <button onclick="AlertManager.clearHistory()" style="padding: 3px 8px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            🗑️ Vider Historique
                        </button>
                        <button onclick="AlertManager.clearAllAlerts()" style="padding: 3px 8px; background: #e74c3c; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            🧹 Nettoyer Écran
                        </button>
                    </div>
                    <div style="font-size: 11px; color: #856404;">
                        Alertes enregistrées: <strong>${AlertManager.alerts.length}</strong> | 
                        Mode debug: <strong>${AlertManager.debugMode ? 'ACTIVÉ (30s)' : 'DÉSACTIVÉ'}</strong> | 
                        Mode freeze: <strong>${AlertManager.freezeMode ? 'ACTIVÉ (permanent)' : 'DÉSACTIVÉ'}</strong>
                    </div>
                </div>
                
                <!-- Section Alertes Persistantes -->
                <div style="margin-bottom: 20px; padding: 10px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px;">
                    <h5 style="margin: 0 0 10px 0; color: #0c5460;">💾 Alertes Persistantes (Survivent aux rechargements)</h5>
                    <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px;">
                        <button onclick="AlertManager.showPersistentAlerts()" style="padding: 3px 8px; background: #17a2b8; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            🔄 Afficher Sauvegardées
                        </button>
                        <button onclick="AlertManager.clearPersistentAlerts()" style="padding: 3px 8px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            🗑️ Effacer Sauvegardées
                        </button>
                    </div>
                    <div style="font-size: 11px; color: #0c5460;">
                        Alertes persistantes: <strong>${JSON.parse(localStorage.getItem('whatsapp_persistent_alerts') || '[]').length}</strong> | 
                        Capture auto: <strong>${AlertManager.persistentErrorCapture ? 'ACTIVÉE' : 'DÉSACTIVÉE'}</strong>
                    </div>
                </div>
                
                <!-- Section Logs -->
                <div style="margin-bottom: 20px;">
                    <h5 style="margin: 0 0 10px 0; color: #495057;">📋 Logs</h5>
                    <div style="margin-bottom: 10px;">
                        <button onclick="Logger.clearLogs(); showDebugAlert('Logs cleared', 'info')" style="padding: 5px 10px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Clear Logs</button>
                        <button onclick="Logger.exportLogs()" style="margin-left: 5px; padding: 5px 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Export Logs</button>
                    </div>
                    <div id="debug-logs" style="background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 11px; max-height: 250px; overflow-y: auto;">
                        ${Logger.getLogs().map(log => `<div style="margin-bottom: 3px; ${log.level === 'ERROR' ? 'color: red;' : log.level === 'SUCCESS' ? 'color: green;' : ''}"><strong>[${log.level}]</strong> ${log.message}</div>`).join('')}
                    </div>
                </div>
                
                <!-- Section Contrôles -->
                <div style="margin-bottom: 10px; padding: 10px; background: #e9ecef; border-radius: 4px;">
                    <h5 style="margin: 0 0 10px 0; color: #495057;">⚙️ Contrôles</h5>
                    <button onclick="showAlert('Test d\\'alerte normal', 'info')" style="padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin-right: 5px;">Test Info</button>
                    <button onclick="showAlert('Test d\\'alerte succès', 'success')" style="padding: 5px 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin-right: 5px;">Test Success</button>
                    <button onclick="showAlert('Test d\\'alerte erreur', 'error')" style="padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Test Error</button>
                </div>
                
                <!-- Instructions -->
                <div style="font-size: 11px; color: #6c757d; padding: 10px; background: #f8f9fa; border-radius: 4px; margin-top: 10px;">
                    <strong>Instructions:</strong><br>
                    • <kbd>F12</kbd> ou <kbd>Ctrl+Shift+D</kbd> pour ouvrir/fermer ce panneau<br>
                    • Mode debug alertes: rend toutes les alertes persistantes<br>
                    • Historique: conserve les 50 dernières alertes
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
    
    // Validation unifiée du message
    const content = messageInput.value.trim();
    const fileInputElement = document.getElementById('file-input');
    const hasFile = fileInputElement && fileInputElement.files.length > 0;
    
    if (!content && !hasFile) {
        Logger.error('Message vide sans fichier');
        showAlert('Veuillez saisir un message ou sélectionner un fichier', 'error');
        messageInput.focus();
        return;
    }
    
    // Désactiver le bouton d'envoi pendant l'envoi
    const submitButton = document.querySelector('#chat-form button[type="submit"]');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = '...';
    }
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('content', content);
    formData.append('type', 'text');
    
    // Vérifier s'il y a un fichier sélectionné
    if (fileInputElement && fileInputElement.files.length > 0) {
        formData.append('file', fileInputElement.files[0]);
        Logger.info('Fichier attaché: ' + fileInputElement.files[0].name);
    }
    
    Logger.debug('Contenu du message', { content: content });
    
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
        showAlert('Erreur: Destinataire non spécifié', 'error');
        enableSubmitButton();
        return;
    }
    
    Logger.info('Envoi de la requête AJAX');
    
    fetch('ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        Logger.debug('Réponse reçue', { status: response.status, statusText: response.statusText });
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        Logger.debug('Données JSON reçues', data);
        
        if (data.success) {
            // Ajouter le message à l'interface
            addMessageToChat(data.message, 'sent');
            messageInput.value = '';
            
            // Nettoyer la sélection de fichier
            clearFileSelection();
            
            scrollToBottom(chatMessages);
            Logger.success('Message envoyé avec succès');
            
            // Message de succès plus discret
            console.log('✅ Message envoyé avec succès');
        } else {
            Logger.error('Erreur lors de l\'envoi', data);
            showAlert(data.error || 'Erreur lors de l\'envoi du message', 'error');
        }
    })
    .catch(error => {
        Logger.error('Erreur de connexion', error);
        console.error('❌ Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'error');
    })
    .finally(() => {
        enableSubmitButton();
    });
}

// Fonction utilitaire pour réactiver le bouton d'envoi
function enableSubmitButton() {
    const submitButton = document.querySelector('#chat-form button[type="submit"]');
    if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = '➤';
    }
}

/**
 * Ajoute un message au chat
 */
function addMessageToChat(messageData, type = 'received') {
    const chatMessages = document.querySelector('.chat-messages');
    if (!chatMessages) return;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    
    let messageHtml = '';
    
    // Afficher le nom de l'expéditeur pour les messages de groupe reçus
    if (type === 'received' && messageData.sender_name && isGroupConversation()) {
        messageHtml += `<div style="font-size: 12px; color: #00a884; margin-bottom: 5px;">${escapeHtml(messageData.sender_name)}</div>`;
    }
    
    // Contenu du message
    if (messageData.type === 'file' && messageData.file) {
        messageHtml += `<div class="message-content">`;
        
        // Afficher la prévisualisation pour les images
        if (messageData.file.is_image) {
            messageHtml += `<div style="margin-bottom: 5px;">
                <img src="${messageData.file.path}" alt="${escapeHtml(messageData.file.name)}" 
                     style="max-width: 200px; max-height: 200px; border-radius: 8px; cursor: pointer;"
                     onclick="window.open('${messageData.file.path}', '_blank')">
            </div>`;
        }
        
        // Informations du fichier
        messageHtml += `<div style="display: flex; align-items: center; gap: 8px; padding: 8px; background: rgba(0,0,0,0.1); border-radius: 8px;">
            <span style="font-size: 18px;">${getFileIcon(messageData.file.type || 'application/octet-stream')}</span>
            <div style="flex: 1;">
                <div style="font-weight: bold; font-size: 13px;">${escapeHtml(messageData.file.name)}</div>
                <div style="font-size: 11px; opacity: 0.7;">${messageData.file.formatted_size || formatFileSize(messageData.file.size)}</div>
            </div>
                         <a href="download.php?file=${encodeURIComponent(messageData.file.path)}&message=${encodeURIComponent(messageData.id)}" 
                style="color: #00a884; text-decoration: none; font-size: 12px;">
                 📥 Télécharger
             </a>
        </div>`;
        
        // Texte accompagnant le fichier si présent
        if (messageData.content && messageData.content !== messageData.file.name) {
            messageHtml += `<div style="margin-top: 8px;">${escapeHtml(messageData.content)}</div>`;
        }
        
        messageHtml += `</div>`;
    } else {
        messageHtml += `<div class="message-content">${escapeHtml(messageData.content)}</div>`;
    }
    
    messageHtml += `<div class="message-time">${messageData.timestamp || new Date().toLocaleTimeString()}`;
    
    // Ajouter les indicateurs de statut pour les messages envoyés
    if (type === 'sent') {
        messageHtml += ` <span style="color: #00a884;">✓</span>`;
    }
    
    messageHtml += `</div>`;
    
    messageDiv.innerHTML = messageHtml;
    
    chatMessages.appendChild(messageDiv);
    scrollToBottom(chatMessages);
}

/**
 * Vérifie si la conversation actuelle est un groupe
 */
function isGroupConversation() {
    const conversationId = document.getElementById('conversation-id');
    return conversationId && conversationId.value && conversationId.value.startsWith('group_');
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
 * Gestion du modal de création de groupe (VERSION AMÉLIORÉE)
 */
function openGroupModal() {
    console.log('Tentative d\'ouverture du modal...');
    const modal = document.getElementById('group-modal');
    if (modal) {
        console.log('Modal trouvé dans le DOM');
        
        // Forcer l'affichage avec du CSS inline pour le debug
        modal.style.display = 'block';
        modal.style.opacity = '1';
        modal.style.zIndex = '9999';
        modal.classList.add('active');
        
        console.log('Classes du modal:', modal.className);
        console.log('Style du modal:', modal.style.cssText);
        
        // Ajouter la classe modal-content si elle n'existe pas
        const modalContent = modal.querySelector('.modal-content');
        if (!modalContent) {
            const content = modal.querySelector('div');
            if (content) {
                content.classList.add('modal-content');
            }
        }
        
        console.log('Modal devrait être visible maintenant');
    } else {
        console.log('❌ Modal non trouvé dans le DOM');
    }
}

function closeGroupModal() {
    console.log('Fermeture du modal...');
    const modal = document.getElementById('group-modal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.opacity = '0';
        modal.classList.remove('active');
        console.log('Modal fermé');
    }
}

/**
 * Fermer le modal en cliquant à l'extérieur
 */
document.addEventListener('click', function(event) {
    const modal = document.getElementById('group-modal');
    if (modal && event.target === modal) {
        closeGroupModal();
    }
});

/**
 * Fermer le modal avec la touche Échap
 */
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeGroupModal();
    }
});

/**
 * Amélioration de la validation des formulaires
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    const emailFields = form.querySelectorAll('input[type="email"]');
    
    // Validation des champs requis
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            showFieldError(field, 'Ce champ est requis');
            isValid = false;
        } else {
            field.classList.remove('error');
            hideFieldError(field);
        }
    });
    
    // Validation des emails
    emailFields.forEach(field => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (field.value && !emailRegex.test(field.value)) {
            field.classList.add('error');
            showFieldError(field, 'Format d\'email invalide');
            isValid = false;
        } else if (field.value) {
            field.classList.remove('error');
            hideFieldError(field);
        }
    });
    
    return isValid;
}

/**
 * Afficher une erreur sous un champ
 */
function showFieldError(field, message) {
    hideFieldError(field); // Supprimer l'erreur existante
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.cssText = `
        color: var(--error-color);
        font-size: 12px;
        margin-top: 5px;
        display: block;
    `;
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Masquer l'erreur d'un champ
 */
function hideFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Amélioration de la soumission AJAX pour les groupes
 */
function submitGroupForm(form, action = 'add_member') {
    const formData = new FormData(form);
    formData.set('action', action);
    
    // Afficher un indicateur de chargement
    // Le bouton submit est en dehors du formulaire, utiliser l'attribut form
    const submitButton = document.querySelector('button[form="' + form.id + '"]') || form.querySelector('button[type="submit"]');
    const originalText = submitButton ? submitButton.textContent : 'Ajouter';
    if (submitButton) {
        submitButton.textContent = 'Ajout en cours...';
        submitButton.disabled = true;
    }
    
    fetch('ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeGroupModal();
            // Recharger la page pour voir les changements
            location.reload();
        } else {
            showAlert(data.error || 'Erreur lors de l\'ajout du membre', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion', 'error');
    })
    .finally(() => {
        // Restaurer le bouton
        if (submitButton) {
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        }
    });
}

/**
 * Confirmation de suppression améliorée
 */
function confirmDelete(itemType, itemId, itemName) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${itemType} "${itemName}" ?`)) {
        // Utiliser fetch pour la suppression
        fetch(`${itemType}s.php?action=delete&id=${itemId}`, {
            method: 'POST'
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('success') || data.includes('supprimé')) {
                showAlert(`${itemType} "${itemName}" supprimé avec succès`, 'success');
                // Supprimer l'élément du DOM
                const element = document.querySelector(`[data-id="${itemId}"]`);
                if (element) {
                    element.remove();
                }
            } else {
                showAlert('Erreur lors de la suppression', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showAlert('Erreur de connexion', 'error');
        });
    }
}

/**
 * Gestion de la sélection de fichier
 */
function handleFileSelection(file) {
    // Vérification de la taille (max 10MB)
    if (file.size > 10 * 1024 * 1024) {
        showAlert('Le fichier ne doit pas dépasser 10MB', 'error');
        clearFileSelection();
        return;
    }
    
    // Vérification du type
    const allowedTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp', 'image/svg+xml',
        'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain', 'text/rtf', 'application/zip', 'application/x-rar-compressed',
        'audio/mpeg', 'audio/mp3', 'video/mp4', 'video/avi', 'video/quicktime', 'video/x-ms-wmv'
    ];
    
    if (!allowedTypes.includes(file.type)) {
        showAlert('Type de fichier non autorisé', 'error');
        clearFileSelection();
        return;
    }
    
    // Afficher la prévisualisation
    showFilePreview(file);
    showAlert(`Fichier "${file.name}" sélectionné`, 'info');
}

/**
 * Affiche la prévisualisation du fichier
 */
function showFilePreview(file) {
    const filePreview = document.getElementById('file-preview');
    const fileInfo = document.getElementById('file-info');
    
    if (filePreview && fileInfo) {
        // Formater la taille
        const size = formatFileSize(file.size);
        
        // Obtenir l'icône selon le type
        const icon = getFileIcon(file.type);
        
        fileInfo.innerHTML = `
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 16px;">${icon}</span>
                <div>
                    <div style="font-weight: bold;">${escapeHtml(file.name)}</div>
                    <div style="color: #8696a0;">${size}</div>
                </div>
            </div>
        `;
        
        filePreview.style.display = 'block';
        
        // Mettre à jour le placeholder de l'input
        const messageInput = document.getElementById('message-input');
        if (messageInput) {
            messageInput.placeholder = 'Ajouter un message (optionnel)...';
        }
    }
}

/**
 * Supprime la sélection de fichier
 */
function clearFileSelection() {
    const fileInput = document.getElementById('file-input');
    const filePreview = document.getElementById('file-preview');
    const messageInput = document.getElementById('message-input');
    
    if (fileInput) {
        fileInput.value = '';
    }
    
    if (filePreview) {
        filePreview.style.display = 'none';
    }
    
    if (messageInput) {
        messageInput.placeholder = 'Tapez votre message...';
    }
}

/**
 * Formate la taille du fichier
 */
function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    
    while (bytes >= 1024 && i < units.length - 1) {
        bytes /= 1024;
        i++;
    }
    
    return Math.round(bytes * 100) / 100 + ' ' + units[i];
}

/**
 * Obtient l'icône selon le type de fichier
 */
function getFileIcon(mimeType) {
    if (mimeType.startsWith('image/')) {
        return '🖼️';
    } else if (mimeType.startsWith('video/')) {
        return '🎥';
    } else if (mimeType.startsWith('audio/')) {
        return '🎵';
    } else if (mimeType === 'application/pdf') {
        return '📄';
    } else if (mimeType.includes('word') || mimeType.includes('document')) {
        return '📝';
    } else if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
        return '📊';
    } else if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) {
        return '📽️';
    } else if (mimeType.includes('zip') || mimeType.includes('rar')) {
        return '📦';
    } else {
        return '📄';
    }
} 
// Gestion des fichiers initialisée dans le DOMContentLoaded 