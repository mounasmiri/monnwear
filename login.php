<?php
/**
 * login.php
 * Page de connexion pour clients et administrateurs.
 * Après vérification des identifiants, l'utilisateur est redirigé
 * vers son espace dédié selon son rôle stocké en session.
 */

// ----------------------------------------------------------------
// SESSION — doit être appelé AVANT tout output HTML
// session_start() initialise (ou reprend) la session PHP.
// Elle crée un cookie côté navigateur (PHPSESSID) qui permet au
// serveur de retrouver les données $_SESSION de cet utilisateur.
// ----------------------------------------------------------------
session_start();

// Si l'utilisateur est déjà connecté, on le redirige directement
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_role'] === 'admin' ? 'admin/index.php' : 'index.php'));
    exit;
}

require_once 'includes/db_connect.php';

$error = '';

// Message de succès après inscription (flash message posé par register.php)
$flashSuccess = '';
if (isset($_SESSION['flash_success'])) {
    $flashSuccess = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']); // Lu une seule fois puis supprimé
}

// ----------------------------------------------------------------
// TRAITEMENT DU FORMULAIRE — uniquement sur POST
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {

        // --- REQUÊTE SÉCURISÉE (protection contre l'injection SQL) ---
        // On utilise un prepared statement PDO :
        //   1. prepare()  → envoie la structure SQL au serveur sans données
        //   2. execute()  → envoie ensuite les données séparément
        // Ainsi, même si $email contient du code SQL malveillant (ex: ' OR 1=1 --),
        // il est traité comme une simple chaîne de caractères, jamais comme du SQL.
        $stmt = $pdo->prepare('SELECT id_client, email, mot_de_passe, role FROM clients WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(); // Retourne un tableau associatif ou false

        // --- VÉRIFICATION DU MOT DE PASSE (protection contre le brute force via hash) ---
        // password_verify() compare le mot de passe en clair saisi par l'utilisateur
        // avec le hash bcrypt stocké en base de données.
        // On ne stocke JAMAIS un mot de passe en clair — seulement son empreinte (hash).
        // password_verify() recalcule le hash et compare de façon sécurisée (timing-safe).
        if ($user && password_verify($password, $user['mot_de_passe'])) {

            // --- RÉGÉNÉRATION DE L'ID DE SESSION ---
            // Empêche les attaques par fixation de session (session fixation attack) :
            // un attaquant qui connaîtrait l'ancien ID de session pré-connexion
            // ne pourra plus l'utiliser après ce regenerate.
            session_regenerate_id(true);

            // --- STOCKAGE DES DONNÉES EN SESSION ---
            // $_SESSION est un tableau superglobal persistant côté serveur.
            // Ces valeurs sont accessibles sur toutes les pages tant que la session est active.
            $_SESSION['user_id']   = $user['id_client']; // Identifiant unique de l'utilisateur
            $_SESSION['user_email'] = $user['email'];    // Email (pratique pour l'affichage)
            $_SESSION['user_role'] = $user['role'];      // 'admin' ou 'client'

            // --- REDIRECTION SELON LE RÔLE ---
            // Les administrateurs accèdent au back-office, les clients au front-office.
            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit; // Stopper l'exécution après un header() de redirection est OBLIGATOIRE

        } else {
            // Message volontairement vague : ne pas révéler si c'est l'email ou le mdp qui est faux
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Monnwear</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts : Space Grotesk -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           THÈME GLOBAL — palette sombre Monnwear
        ============================================================ */
        :root {
            --mw-black:  #0a0a0a;
            --mw-dark:   #111111;
            --mw-card:   #1a1a1a;
            --mw-border: #2a2a2a;
            --mw-white:  #f5f5f5;
            --mw-muted:  #888888;
            --mw-accent: #e8e8e8;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background-color: var(--mw-black);
            color: var(--mw-white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 12px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
        }

        .login-card .brand {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--mw-white);
        }

        .login-card .subtitle {
            font-size: 0.85rem;
            color: var(--mw-muted);
            margin-top: 0.25rem;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--mw-muted);
        }

        .form-control {
            background-color: var(--mw-dark);
            border: 1px solid var(--mw-border);
            color: var(--mw-white);
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            background-color: var(--mw-dark);
            border-color: var(--mw-accent);
            color: var(--mw-white);
            box-shadow: none;
        }

        .form-control::placeholder { color: var(--mw-muted); }

        .btn-login {
            background-color: var(--mw-white);
            color: var(--mw-black);
            border: none;
            border-radius: 8px;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.7rem;
            width: 100%;
            transition: background-color 0.2s, color 0.2s;
        }

        .btn-login:hover {
            background-color: var(--mw-accent);
            color: var(--mw-black);
        }

        .divider {
            border-color: var(--mw-border);
            margin: 1.5rem 0;
        }

        .register-link {
            font-size: 0.85rem;
            color: var(--mw-muted);
            text-align: center;
        }

        .register-link a {
            color: var(--mw-white);
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover { text-decoration: underline; }

        .alert-success {
            background-color: rgba(74, 222, 128, 0.12);
            border: 1px solid rgba(74, 222, 128, 0.3);
            color: #86efac;
            border-radius: 8px;
            font-size: 0.88rem;
            padding: 0.65rem 0.9rem;
        }

        .alert-error {
            background-color: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #f8a0a8;
            border-radius: 8px;
            font-size: 0.88rem;
            padding: 0.65rem 0.9rem;
        }
    </style>
</head>
<body>

    <div class="login-card">

        <!-- En-tête -->
        <div class="text-center mb-4">
            <div class="brand">Monnwear</div>
            <div class="subtitle">Connectez-vous à votre compte</div>
        </div>

        <!-- Message de succès après inscription (flash message depuis register.php) -->
        <?php if ($flashSuccess !== ''): ?>
            <div class="alert-success mb-3">
                <i class="bi bi-check-circle me-1"></i>
                <?= htmlspecialchars($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <!-- Message d'erreur (affiché uniquement en cas d'échec) -->
        <?php if ($error !== ''): ?>
            <div class="alert-error mb-3">
                <i class="bi bi-exclamation-circle me-1"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire de connexion -->
        <form method="POST" action="login.php" novalidate>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="votre@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
            </button>

        </form>

        <hr class="divider">

        <p class="register-link">
            Pas encore de compte ? <a href="register.php">Créer un compte</a>
        </p>

    </div>

    <!-- Bootstrap 5 JS (bundle inclut Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
