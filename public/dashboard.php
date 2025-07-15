<?php
/**
 * WhatsApp Web Clone - Dashboard Principal
 * Interface principale avec navigation vers toutes les fonctionnalités
 */

require_once '../vendor/autoload.php';

use WhatsApp\Services\UserService;
use WhatsApp\Services\MessageService;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Repositories\GroupRepository;
use WhatsApp\Repositories\MessageRepository;

session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Initialisation des services
$xmlManager = new WhatsApp\Utils\XMLManager();
$userService = new UserService($xmlManager);
$messageService = new MessageService($xmlManager);
$contactRepo = new ContactRepository($xmlManager);
$groupRepo = new GroupRepository($xmlManager);
$messageRepo = new MessageRepository($xmlManager);

// Variables
$currentUser = $userService->findUserById($_SESSION['user_id']);
$pageTitle = "Dashboard - WhatsApp Web";
$error = '';
$success = '';

// Récupération des statistiques
$stats = [
    'contacts' => 0,
    'groups' => 0,
    'messages_sent' => 0,
    'messages_received' => 0
];

try {
    $stats['contacts'] = count($contactRepo->getContactsByUserId($_SESSION['user_id']));
$stats['groups'] = count($groupRepo->getGroupsByUserId($_SESSION['user_id']));
    
    $userMessages = $messageRepo->findByUser($_SESSION['user_id']);
    foreach ($userMessages as $message) {
        if ($message->getFromUserId() === $_SESSION['user_id']) {
            $stats['messages_sent']++;
        } else {
            $stats['messages_received']++;
        }
    }
} catch (Exception $e) {
    $error = "Erreur lors du chargement des statistiques : " . $e->getMessage();
}

// Récupération des messages récents
$recentMessages = [];
try {
    $allMessages = $messageRepo->getMessagesByUserId($_SESSION['user_id']);
    $recentMessages = array_slice(array_reverse($allMessages), 0, 5);
} catch (Exception $e) {
    // Pas de messages récents
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- dashboard-page-marker -->
        <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>💬 WhatsApp Web</h2>
                <p>Bienvenue <?= htmlspecialchars($currentUser ? $currentUser->getName() : 'Utilisateur') ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    📊 Dashboard
                </a>
                <a href="contacts.php" class="nav-item">
                    👥 Mes Contacts
                </a>
                <a href="groups.php" class="nav-item">
                    👫 Mes Groupes
                </a>
                <a href="chat.php" class="nav-item">
                    💬 Messages
                </a>
                <a href="profile.php" class="nav-item">
                    ⚙️ Mon Profil
                </a>
                <a href="index.php?action=logout" class="nav-item" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                    🚪 Déconnexion
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>📊 Tableau de bord</h1>
                <p>Vue d'ensemble de votre activité</p>
            </div>

            <div class="content-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <strong>Erreur :</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <strong>Succès :</strong> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <!-- Statistiques principales -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <h3 style="color: #00a884; font-size: 36px; margin: 0;"><?= $stats['contacts'] ?></h3>
                            <p style="margin: 5px 0; color: #667781;">Contacts</p>
                            <a href="contacts.php" class="btn btn-primary btn-sm">Gérer</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <h3 style="color: #00a884; font-size: 36px; margin: 0;"><?= $stats['groups'] ?></h3>
                            <p style="margin: 5px 0; color: #667781;">Groupes</p>
                            <a href="groups.php" class="btn btn-primary btn-sm">Gérer</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <h3 style="color: #00a884; font-size: 36px; margin: 0;"><?= $stats['messages_sent'] ?></h3>
                            <p style="margin: 5px 0; color: #667781;">Messages envoyés</p>
                            <a href="chat.php" class="btn btn-primary btn-sm">Écrire</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <h3 style="color: #00a884; font-size: 36px; margin: 0;"><?= $stats['messages_received'] ?></h3>
                            <p style="margin: 5px 0; color: #667781;">Messages reçus</p>
                            <a href="chat.php" class="btn btn-primary btn-sm">Lire</a>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="card">
                    <div class="card-header">
                        🚀 Actions rapides
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <a href="contacts.php?action=create" class="btn btn-primary">
                                ➕ Ajouter un contact
                            </a>
                            <a href="groups.php?action=create" class="btn btn-primary">
                                👥 Créer un groupe
                            </a>
                            <a href="chat.php" class="btn btn-primary">
                                💬 Nouvelle conversation
                            </a>
                            <a href="profile.php" class="btn btn-secondary">
                                ⚙️ Modifier mon profil
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Messages récents -->
                <div class="card">
                    <div class="card-header">
                        📬 Messages récents
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentMessages)): ?>
                            <p style="text-align: center; color: #667781; margin: 20px 0;">
                                Aucun message récent. <a href="chat.php">Commencez une conversation !</a>
                            </p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($recentMessages as $message): ?>
                                    <li class="list-group-item">
                                        <div>
                                            <strong>
                                                <?php if ($message->getFromUserId() === $_SESSION['user_id']): ?>
                                    Vous → <?= htmlspecialchars($message->getToUser() ?: $message->getToGroup()) ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($message->getFromUserId()) ?> → Vous
                                                <?php endif; ?>
                                            </strong>
                                            <br>
                                            <span style="color: #667781;">
                                                <?= htmlspecialchars(mb_substr($message->getContent(), 0, 50)) ?>
                                                <?= mb_strlen($message->getContent()) > 50 ? '...' : '' ?>
                                            </span>
                                        </div>
                                        <small style="color: #667781;">
                                            <?= $message->getTimestamp() ? date('H:i', strtotime($message->getTimestamp())) : 'Maintenant' ?>
                                        </small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div style="text-align: center; margin-top: 15px;">
                                <a href="chat.php" class="btn btn-primary btn-sm">Voir tous les messages</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Informations du profil -->
                <div class="card">
                    <div class="card-header">
                        👤 Mon profil
                    </div>
                    <div class="card-body">
                        <?php if ($currentUser): ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <strong>Nom :</strong><br>
                                    <?= htmlspecialchars($currentUser->getName()) ?>
                                </div>
                                <div>
                                    <strong>Email :</strong><br>
                                    <?= htmlspecialchars($currentUser->getEmail()) ?>
                                </div>
                                <div>
                                    <strong>Statut :</strong><br>
                                    <?= htmlspecialchars($currentUser->getStatus() ?: 'En ligne') ?>
                                </div>
                                <div>
                                    <strong>Membre depuis :</strong><br>
                                    <?= $currentUser->getCreatedAt() ? date('d/m/Y', strtotime($currentUser->getCreatedAt())) : 'Aujourd\'hui' ?>
                                </div>
                            </div>
                            <div style="margin-top: 15px; text-align: center;">
                                <a href="profile.php" class="btn btn-secondary btn-sm">Modifier mon profil</a>
                            </div>
                        <?php else: ?>
                            <p style="text-align: center; color: #667781;">
                                Erreur lors du chargement du profil.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Actualisation automatique des statistiques toutes les 30 secondes
        setInterval(function() {
            // Recharger silencieusement les statistiques via AJAX
            fetch('ajax.php?action=get_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour les statistiques dans l'interface
                        console.log('Statistiques mises à jour');
                    }
                })
                .catch(error => {
                    console.log('Erreur lors de la mise à jour des statistiques');
                });
        }, 30000);

        // Marquer le dashboard comme actif dans la navigation
        document.addEventListener('DOMContentLoaded', function() {
            const dashboardLink = document.querySelector('a[href="dashboard.php"]');
            if (dashboardLink) {
                dashboardLink.classList.add('active');
            }
        });
    </script>
</body>
</html> 