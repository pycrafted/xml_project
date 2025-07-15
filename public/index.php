<?php
/**
 * WhatsApp Web Clone - Page d'accueil
 * Page principale de l'interface web avec authentification
 */

require_once '../vendor/autoload.php';

use WhatsApp\Services\UserService;
use WhatsApp\Utils\XMLManager;
use WhatsApp\Repositories\UserRepository;
use WhatsApp\Repositories\ContactRepository;
use WhatsApp\Models\Contact;

session_start();

// Initialisation des services
$xmlManager = new XMLManager();
$userService = new UserService($xmlManager);
$userRepository = new UserRepository($xmlManager);
$contactRepository = new ContactRepository($xmlManager);

// Variables de template
$pageTitle = "WhatsApp Web Clone";
$currentUser = null;
$error = '';
$success = '';

// Gestion de l'authentification
// Gestion de la déconnexion via GET
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'login':
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            
            if (empty($email) || empty($password)) {
                $error = "Email et mot de passe sont requis";
            } else {
                try {
                    // Vérifier les credentials avec les comptes par défaut
                    $validCredentials = [
                        'admin@whatsapp.com' => 'admin123',
                        'demo@whatsapp.com' => 'demo123',
                        'test@whatsapp.com' => 'test123',
                        'alice@test.com' => 'password123',
                        'bob@test.com' => 'password123',
                        'charlie@test.com' => 'password123',
                        'diana@test.com' => 'password123',
                        'erik@test.com' => 'password123'
                    ];
                    
                    if (isset($validCredentials[$email]) && $validCredentials[$email] === $password) {
                        // Chercher l'utilisateur existant
                        $users = $userRepository->findByEmail($email);
                        $user = !empty($users) ? $users[0] : null;
                        
                        if ($user) {
                            $_SESSION['user_id'] = $user->getId();
                            $_SESSION['user_name'] = $user->getName();
                            $_SESSION['user_email'] = $user->getEmail();
                            
                            // Redirection vers le dashboard
                            header('Location: dashboard.php');
                            exit;
                        } else {
                            $error = "Utilisateur non trouvé";
                        }
                    } else {
                        $error = "Email ou mot de passe incorrect";
                    }
                } catch (Exception $e) {
                    $error = "Erreur lors de la connexion : " . $e->getMessage();
                }
            }
            break;
            
        case 'create_contact':
            // Vérifier si l'utilisateur est connecté
            if (!isset($_SESSION['user_id'])) {
                $error = "Vous devez être connecté pour créer un contact";
                break;
            }
            
            $contactName = trim($_POST['contact_name'] ?? '');
            $contactEmail = trim($_POST['contact_email'] ?? '');
            
            if (empty($contactName) || empty($contactEmail)) {
                $error = "Nom et email du contact sont requis";
            } else {
                try {
                    // Vérifier si l'utilisateur contacté existe
                    $contactUsers = $userRepository->findByEmail($contactEmail);
                    
                    if (empty($contactUsers)) {
                        $error = "Aucun utilisateur trouvé avec cet email";
                    } else {
                        $contactUser = $contactUsers[0];
                        $currentUserId = $_SESSION['user_id'];
                        
                        // Vérifier si le contact existe déjà
                        $existingContacts = $contactRepository->findByUserId($currentUserId);
                        $contactExists = false;
                        
                        foreach ($existingContacts as $existingContact) {
                            if ($existingContact->getContactUserId() === $contactUser->getId()) {
                                $contactExists = true;
                                break;
                            }
                        }
                        
                        if ($contactExists) {
                            $error = "Ce contact existe déjà dans votre liste";
                        } else {
                            // Créer le nouveau contact
                            $contactId = 'contact_' . $currentUserId . '_' . $contactUser->getId() . '_' . time();
                            
                            $newContact = new Contact(
                                $contactId,
                                $contactName,
                                $currentUserId,
                                $contactUser->getId()
                            );
                            
                            if ($contactRepository->create($newContact)) {
                                $success = "Contact '{$contactName}' créé avec succès!";
                            } else {
                                $error = "Erreur lors de la création du contact";
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error = "Erreur lors de la création du contact : " . $e->getMessage();
                }
            }
            break;
            
        case 'logout':
            session_destroy();
            header('Location: index.php');
            exit;
            break;
    }
}

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    // Si l'utilisateur est connecté, afficher l'interface de création de contact
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email']
    ];
    
    // Si pas de redirection vers dashboard, on peut rester sur cette page
    // pour permettre la création de contact
    if (!isset($_POST['action']) || $_POST['action'] !== 'create_contact') {
        // Optionnel: rediriger vers dashboard si aucune action spécifique
        // header('Location: dashboard.php');
        // exit;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php if ($currentUser): ?>
            <!-- Interface pour utilisateur connecté -->
            <div class="form-container" style="margin-top: 5vh;">
                <div class="text-center" style="margin-bottom: 20px;">
                    <h1 style="color: #00a884; margin-bottom: 10px;">👋 Bonjour, <?= htmlspecialchars($currentUser['name']) ?></h1>
                    <p style="color: #667781;">Créez un nouveau contact ou accédez à votre dashboard</p>
                </div>

                <!-- Boutons de navigation -->
                <div style="margin-bottom: 20px; text-align: center;">
                    <a href="dashboard.php" class="btn btn-primary" style="margin-right: 10px;">
                        📱 Accéder au Dashboard
                    </a>
                    <a href="index.php?action=logout" class="btn btn-secondary">
                        🚪 Se déconnecter
                    </a>
                </div>

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

                <!-- Formulaire de création de contact -->
                <div class="card">
                    <div class="card-header">
                        👥 Créer un nouveau contact
                    </div>
                    <div class="card-body">
                        <form method="POST" id="contact-form">
                            <input type="hidden" name="action" value="create_contact">
                            
                            <div class="form-group">
                                <label for="contact_name">Nom du contact :</label>
                                <input 
                                    type="text" 
                                    id="contact_name" 
                                    name="contact_name" 
                                    class="form-control" 
                                    placeholder="Nom d'affichage du contact"
                                    value="<?= htmlspecialchars($_POST['contact_name'] ?? '') ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="contact_email">Email du contact :</label>
                                <input 
                                    type="email" 
                                    id="contact_email" 
                                    name="contact_email" 
                                    class="form-control" 
                                    placeholder="email@example.com"
                                    value="<?= htmlspecialchars($_POST['contact_email'] ?? '') ?>"
                                    required
                                >
                                <small style="color: #667781;">L'email doit correspondre à un utilisateur existant</small>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    ➕ Créer le contact
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Utilisateurs disponibles -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        📋 Utilisateurs disponibles
                    </div>
                    <div class="card-body">
                        <div style="font-size: 13px; color: #667781;">
                            <strong>Admin:</strong> admin@whatsapp.com<br>
                            <strong>Demo:</strong> demo@whatsapp.com<br>
                            <strong>Test:</strong> test@whatsapp.com<br>
                            <strong>Alice:</strong> alice@test.com<br>
                            <strong>Bob:</strong> bob@test.com<br>
                            <strong>Charlie:</strong> charlie@test.com<br>
                            <strong>Diana:</strong> diana@test.com<br>
                            <strong>Erik:</strong> erik@test.com
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Page de connexion -->
            <div class="form-container" style="margin-top: 10vh;">
                <div class="text-center" style="margin-bottom: 30px;">
                    <h1 style="color: #00a884; margin-bottom: 10px;">💬 WhatsApp Web</h1>
                    <p style="color: #667781;">Plateforme de discussions en ligne</p>
                </div>

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

                <form method="POST" id="login-form">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="email">Email :</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control" 
                            placeholder="votre.email@example.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe :</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Votre mot de passe"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            Se connecter
                        </button>
                    </div>
                </form>

                <!-- Information sur la création de contact -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        👥 Créer des contacts
                    </div>
                    <div class="card-body">
                        <p style="color: #667781; margin: 0;">
                            Connectez-vous pour pouvoir créer et gérer vos contacts directement depuis cette page.
                        </p>
                    </div>
                </div>

                <!-- Comptes de démonstration -->
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                    <h4 style="color: #54656f; margin-bottom: 10px;">🔑 Comptes de démonstration</h4>
                    <div style="font-size: 13px; color: #667781;">
                        <strong>👨‍💼 Admin:</strong> admin@whatsapp.com / admin123<br>
                        <strong>🎪 Demo:</strong> demo@whatsapp.com / demo123<br>
                        <strong>🧪 Test:</strong> test@whatsapp.com / test123<br>
                        <strong>🔬 Alice:</strong> alice@test.com / password123
                    </div>
                </div>

                <!-- Statistiques système -->
                <div class="card" style="margin-top: 30px;">
                    <div class="card-header">
                        📊 Statistiques de la plateforme
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $stats = $userService->getUserStats();
                            ?>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; text-align: center;">
                                <div>
                                    <strong style="color: #00a884; font-size: 24px;"><?= $stats['total_users'] ?></strong>
                                    <br><small>Utilisateurs</small>
                                </div>
                                <div>
                                    <strong style="color: #00a884; font-size: 24px;"><?= $stats['active_users'] ?></strong>
                                    <br><small>Actifs</small>
                                </div>
                            </div>
                            <?php
                        } catch (Exception $e) {
                            echo '<p style="text-align: center; color: #667781;">Statistiques non disponibles</p>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Informations techniques -->
                <div style="margin-top: 30px; text-align: center;">
                    <h3 style="color: #54656f; margin-bottom: 15px;">🛠️ Technologies utilisées</h3>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; font-size: 12px;">
                        <div style="padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            <strong>Backend</strong><br>
                            PHP 8.0+<br>
                            SimpleXML<br>
                            XML + XSD
                        </div>
                        <div style="padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            <strong>Frontend</strong><br>
                            HTML5<br>
                            CSS3<br>
                            JavaScript ES6
                        </div>
                        <div style="padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            <strong>Architecture</strong><br>
                            MVC Pattern<br>
                            Repository Pattern<br>
                            Service Layer
                        </div>
                    </div>
                </div>

                <!-- Crédits académiques -->
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 6px; text-align: center;">
                    <h4 style="color: #54656f; margin-bottom: 10px;">🎓 Projet Académique</h4>
                    <p style="margin: 0; font-size: 14px; color: #667781;">
                        <strong>Master en Génie Logiciel</strong><br>
                        UCAD/DGI/ESP - Professeur Ibrahima FALL<br>
                        Année académique 2024-2025
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Validation du formulaire
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            const contactForm = document.getElementById('contact-form');
            
            if (loginForm) {
                const emailInput = document.getElementById('email');
                const passwordInput = document.getElementById('password');

                // Auto-focus sur le champ email
                emailInput.focus();

                // Validation en temps réel de l'email
                emailInput.addEventListener('blur', function() {
                    const email = this.value.trim();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (email && !emailRegex.test(email)) {
                        this.style.borderColor = '#dc3545';
                    } else {
                        this.style.borderColor = '#d1d7db';
                    }
                });

                // Raccourci clavier pour les comptes de démonstration
                document.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && e.key === '1') {
                        e.preventDefault();
                        emailInput.value = 'admin@whatsapp.com';
                        passwordInput.value = 'admin123';
                        emailInput.focus();
                    }
                    if (e.ctrlKey && e.key === '2') {
                        e.preventDefault();
                        emailInput.value = 'demo@whatsapp.com';
                        passwordInput.value = 'demo123';
                        emailInput.focus();
                    }
                    if (e.ctrlKey && e.key === '3') {
                        e.preventDefault();
                        emailInput.value = 'test@whatsapp.com';
                        passwordInput.value = 'test123';
                        emailInput.focus();
                    }
                });
            }

            if (contactForm) {
                const contactNameInput = document.getElementById('contact_name');
                const contactEmailInput = document.getElementById('contact_email');

                // Auto-focus sur le champ nom du contact
                contactNameInput.focus();

                // Validation en temps réel de l'email du contact
                contactEmailInput.addEventListener('blur', function() {
                    const email = this.value.trim();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (email && !emailRegex.test(email)) {
                        this.style.borderColor = '#dc3545';
                    } else {
                        this.style.borderColor = '#d1d7db';
                    }
                });

                // Raccourcis pour remplir automatiquement les emails
                document.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && e.shiftKey && e.key === '1') {
                        e.preventDefault();
                        contactEmailInput.value = 'admin@whatsapp.com';
                        contactNameInput.value = 'Admin';
                    }
                    if (e.ctrlKey && e.shiftKey && e.key === '2') {
                        e.preventDefault();
                        contactEmailInput.value = 'demo@whatsapp.com';
                        contactNameInput.value = 'Demo';
                    }
                    if (e.ctrlKey && e.shiftKey && e.key === '3') {
                        e.preventDefault();
                        contactEmailInput.value = 'test@whatsapp.com';
                        contactNameInput.value = 'Test';
                    }
                });
            }
        });
    </script>
</body>
</html> 