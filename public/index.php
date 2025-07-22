<?php
/**
 * Wakhtaan - Page d'accueil
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
$pageTitle = "Wakhtaan";
$currentUser = null;
$error = '';
$success = '';
$signup_error = '';
$signup_success = '';

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
                    // 1. Chercher l'utilisateur dans la base XML
                    $users = $userRepository->findByEmail($email);
                    $user = !empty($users) ? $users[0] : null;
                    if ($user) {
                        $settings = $user->getSettings();
                        if (isset($settings['password_hash']) && password_verify($password, $settings['password_hash'])) {
                            // Connexion réussie
                            $_SESSION['user_id'] = $user->getId();
                            $_SESSION['user_name'] = $user->getName();
                            $_SESSION['user_email'] = $user->getEmail();
                            header('Location: dashboard.php');
                            exit;
                        } else {
                            $error = "Email ou mot de passe incorrect";
                        }
                    } else {
                        // Fallback : comptes de démo codés en dur
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
                            $users = $userRepository->findByEmail($email);
                            $user = !empty($users) ? $users[0] : null;
                            if ($user) {
                                $_SESSION['user_id'] = $user->getId();
                                $_SESSION['user_name'] = $user->getName();
                                $_SESSION['user_email'] = $user->getEmail();
                                header('Location: dashboard.php');
                                exit;
                            } else {
                                $error = "Utilisateur non trouvé";
                            }
                        } else {
                            $error = "Email ou mot de passe incorrect";
                        }
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
            
        case 'register':
            function log_register_debug($msg) {
                file_put_contents(__DIR__ . '/../data/register_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
            }
            log_register_debug('Début inscription: ' . json_encode($_POST));
            $signup_name = trim($_POST['signup_name'] ?? '');
            $signup_email = trim($_POST['signup_email'] ?? '');
            $signup_password = $_POST['signup_password'] ?? '';
            $signup_password_confirm = $_POST['signup_password_confirm'] ?? '';

            // Validation
            if (empty($signup_name) || empty($signup_email) || empty($signup_password) || empty($signup_password_confirm)) {
                $signup_error = "Tous les champs sont obligatoires.";
                log_register_debug('Erreur: champs manquants');
            } elseif (!filter_var($signup_email, FILTER_VALIDATE_EMAIL)) {
                $signup_error = "Format d'email invalide.";
                log_register_debug('Erreur: email invalide');
            } elseif (strlen($signup_password) < 6) {
                $signup_error = "Le mot de passe doit contenir au moins 6 caractères.";
                log_register_debug('Erreur: mot de passe trop court');
            } elseif ($signup_password !== $signup_password_confirm) {
                $signup_error = "Les mots de passe ne correspondent pas.";
                log_register_debug('Erreur: mots de passe différents');
            } else {
                // Vérifier unicité email
                $existingUsers = $userRepository->findByEmail($signup_email);
                log_register_debug('Utilisateurs existants pour cet email: ' . count($existingUsers));
                if (!empty($existingUsers)) {
                    $signup_error = "Un utilisateur avec cet email existe déjà.";
                    log_register_debug('Erreur: email déjà utilisé');
                } else {
                    // Générer un ID unique
                    $user_id = 'user_' . time() . '_' . bin2hex(random_bytes(4));
                    $settings = [
                        'password_hash' => password_hash($signup_password, PASSWORD_DEFAULT)
                    ];
                    try {
                        $userService->createUser($user_id, $signup_name, $signup_email, $settings);
                        $signup_success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                        log_register_debug('Succès: utilisateur créé avec id ' . $user_id);
                    } catch (Exception $e) {
                        $signup_error = $e->getMessage();
                        log_register_debug('Exception: ' . $e->getMessage());
                    }
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
            <!-- Nouvelle page de connexion moderne et épurée -->
            <style>
                body {
                    min-height: 100vh;
                    height: 100vh;
                    background: linear-gradient(135deg, #e3f2fd 0%, #2196f3 100%);
                    margin: 0;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                .login-center-wrapper {
                    min-height: 100vh;
                    height: 100vh;
                    width: 100vw;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .login-card {
                    background: rgba(255,255,255,0.98);
                    border-radius: 18px;
                    box-shadow: 0 8px 32px rgba(33,150,243,0.08);
                    padding: 40px 32px 32px 32px;
                    max-width: 400px;
                    width: 100%;
                    margin: 0;
                    display: flex;
                    flex-direction: column;
                    align-items: stretch;
                }
                .login-title {
                    color: #1976d2;
                    font-size: 2.1rem;
                    font-weight: 700;
                    margin-bottom: 8px;
                    text-align: center;
                    letter-spacing: -1px;
                }
                .login-subtitle {
                    color: #667781;
                    font-size: 1.08rem;
                    text-align: center;
                    margin-bottom: 28px;
                }
                .form-group {
                    margin-bottom: 22px;
                }
                .form-group label {
                    font-weight: 500;
                    color: #54656f;
                    margin-bottom: 7px;
                    display: block;
                }
                .form-control {
                    width: 100%;
                    padding: 12px 15px;
                    border: 1px solid #d1d7db;
                    border-radius: 7px;
                    font-size: 1rem;
                    background: #f8f9fa;
                    transition: border-color 0.3s;
                }
                .form-control:focus {
                    outline: none;
                    border-color: #2196f3;
                    box-shadow: 0 0 0 2px rgba(33,150,243,0.13);
                }
                .btn-login {
                    width: 100%;
                    background: #2196f3;
                    color: #fff;
                    border: none;
                    border-radius: 7px;
                    padding: 13px 0;
                    font-size: 1.1rem;
                    font-weight: 600;
                    letter-spacing: 0.5px;
                    box-shadow: 0 2px 8px rgba(33,150,243,0.08);
                    transition: background 0.2s;
                    margin-top: 8px;
                    cursor: pointer;
                }
                .btn-login:hover {
                    background: #1976d2;
                }
                .btn-signup {
                    width: 100%;
                    background: #e3f2fd;
                    color: #1976d2;
                    border: 1px solid #2196f3;
                    border-radius: 7px;
                    padding: 13px 0;
                    font-size: 1.1rem;
                    font-weight: 600;
                    letter-spacing: 0.5px;
                    margin-top: 12px;
                    transition: background 0.2s, color 0.2s;
                    cursor: pointer;
                }
                .btn-signup:hover {
                    background: #bbdefb;
                    color: #1565c0;
                }
                .alert {
                    padding: 13px 18px;
                    border-radius: 7px;
                    margin-bottom: 18px;
                    font-weight: 500;
                    font-size: 1rem;
                    box-shadow: 0 2px 8px rgba(33,150,243,0.07);
                }
                .alert-error {
                    background: #fdecea;
                    color: #c62828;
                    border: 1px solid #f44336;
                }
                .alert-success {
                    background: #e3f2fd;
                    color: #1976d2;
                    border: 1px solid #2196f3;
                }
                .modal-signup {
                    display: none;
                    position: fixed;
                    top: 0; left: 0;
                    width: 100vw; height: 100vh;
                    background: linear-gradient(135deg, #e3f2fd 0%, #2196f3 100%);
                    z-index: 9999;
                    align-items: center;
                    justify-content: center;
                }
                .modal-signup.show {
                    display: flex;
                }
                .modal-signup-content {
                    background: rgba(255,255,255,0.98);
                    border-radius: 18px;
                    box-shadow: 0 8px 32px rgba(33,150,243,0.08);
                    max-width: 400px;
                    width: 100%;
                    padding: 40px 32px 32px 32px;
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    align-items: stretch;
                }
            </style>
            <div class="login-center-wrapper">
                <div class="login-card">
                    <div class="login-title">Connexion à Wakhtaan</div>
                    <div class="login-subtitle">Bienvenue, veuillez vous connecter pour accéder à votre espace.</div>
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
                    <form method="POST" id="login-form" autocomplete="off">
                        <input type="hidden" name="action" value="login">
                        <div class="form-group">
                            <label for="email">Email</label>
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
                            <label for="password">Mot de passe</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="Votre mot de passe"
                                required
                            >
                        </div>
                        <button type="submit" class="btn-login">Se connecter</button>
                        <button type="button" class="btn-signup" id="open-signup-modal">Inscription</button>
                    </form>
                </div>
            </div>
            <!-- Modale d'inscription -->
            <div class="modal-signup" id="modal-signup">
                <div class="modal-signup-content">
                    <button type="button" id="close-signup-modal" style="position:absolute; top:12px; right:16px; background:none; border:none; font-size:22px; color:#1976d2; cursor:pointer;">&times;</button>
                    <div class="login-title" style="margin-bottom: 6px;">Créer un compte</div>
                    <div class="login-subtitle" style="margin-bottom: 22px;">Remplissez les informations pour vous inscrire.</div>
                    <form method="POST" id="signup-form" autocomplete="off">
                        <input type="hidden" name="action" value="register">
                        <?php if ($signup_error): ?>
                            <div class="alert alert-error">
                                <strong>Erreur :</strong> <?= htmlspecialchars($signup_error) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($signup_success): ?>
                            <div class="alert alert-success" style="max-width: 400px; margin: 30px auto 0 auto;">
                                <strong>Inscription réussie !</strong> Vous pouvez maintenant vous connecter.
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                // Fermer la modale d'inscription si succès
                                var modalSignup = document.getElementById('modal-signup');
                                if (modalSignup) {
                                    setTimeout(function() { modalSignup.classList.remove('show'); }, 1200);
                                }
                            });
                            </script>
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="signup_name">Nom complet</label>
                            <input type="text" id="signup_name" name="signup_name" class="form-control" placeholder="Votre nom complet" required value="<?= htmlspecialchars($_POST['signup_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="signup_email">Email</label>
                            <input type="email" id="signup_email" name="signup_email" class="form-control" placeholder="votre.email@example.com" required value="<?= htmlspecialchars($_POST['signup_email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="signup_password">Mot de passe</label>
                            <input type="password" id="signup_password" name="signup_password" class="form-control" placeholder="Mot de passe" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="signup_password_confirm">Confirmer le mot de passe</label>
                            <input type="password" id="signup_password_confirm" name="signup_password_confirm" class="form-control" placeholder="Confirmez le mot de passe" required minlength="6">
                        </div>
                        <button type="submit" class="btn-login" style="margin-bottom: 8px;">Créer mon compte</button>
                        <button type="button" class="btn-signup" id="cancel-signup-modal">Annuler</button>
                    </form>
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
    <script>
        // JS pour ouvrir/fermer la modale d'inscription
        const openSignupBtn = document.getElementById('open-signup-modal');
        const modalSignup = document.getElementById('modal-signup');
        const closeSignupBtn = document.getElementById('close-signup-modal');
        const cancelSignupBtn = document.getElementById('cancel-signup-modal');

        openSignupBtn.addEventListener('click', function() {
            modalSignup.classList.add('show');
        });
        closeSignupBtn.addEventListener('click', function() {
            modalSignup.classList.remove('show');
        });
        cancelSignupBtn.addEventListener('click', function() {
            modalSignup.classList.remove('show');
        });
        window.addEventListener('click', function(e) {
            if (e.target === modalSignup) {
                modalSignup.classList.remove('show');
            }
        });
    </script>
</body>
</html> 