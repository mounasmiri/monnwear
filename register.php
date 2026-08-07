<?php
/**
 * register.php
 * Page d'inscription — crée un nouveau compte client.
 */

session_start();

// Déjà connecté → redirection directe
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db_connect.php';

$errors = [];
$form   = ['nom' => '', 'email' => '', 'adresse' => ''];

// ----------------------------------------------------------------
// TRAITEMENT DU FORMULAIRE
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupération et nettoyage des champs
    $nom      = trim($_POST['nom']      ?? '');
    $email    = trim($_POST['email']    ?? '');
    $adresse  = trim($_POST['adresse']  ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    // Conserver les valeurs pour re-remplir le formulaire en cas d'erreur
    $form = compact('nom', 'email', 'adresse');

    // --- VALIDATION ---
    if ($nom === '')     $errors[] = 'Le nom complet est obligatoire.';
    if ($email === '')   $errors[] = "L'adresse email est obligatoire.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
                         $errors[] = "L'adresse email n'est pas valide.";
    if ($adresse === '') $errors[] = "L'adresse de livraison est obligatoire.";
    if ($password === '') $errors[] = 'Le mot de passe est obligatoire.';
    elseif (strlen($password) < 8)
                         $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if ($password !== $confirm) $errors[] = 'Les mots de passe ne correspondent pas.';

    // --- UNICITÉ DE L'EMAIL ---
    if (empty($errors)) {
        $stmtCheck = $pdo->prepare('SELECT id_client FROM clients WHERE email = :email LIMIT 1');
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            $errors[] = 'Un compte existe déjà avec cette adresse email.';
        }
    }

    // --- INSERTION EN BASE ---
    if (empty($errors)) {
        // password_hash() génère un hash bcrypt sécurisé à partir du mot de passe en clair.
        // On ne stocke JAMAIS le mot de passe en clair — uniquement ce hash.
        // PASSWORD_DEFAULT utilise bcrypt, l'algorithme recommandé.
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmtInsert = $pdo->prepare('
            INSERT INTO clients (nom, email, adresse, mot_de_passe, role)
            VALUES (:nom, :email, :adresse, :mot_de_passe, :role)
        ');
        $stmtInsert->execute([
            ':nom'          => $nom,
            ':email'        => $email,
            ':adresse'      => $adresse,
            ':mot_de_passe' => $hash,
            ':role'         => 'client', // Tous les inscrits sont clients par défaut
        ]);

        // Message flash : stocké en session, lu et supprimé une fois affiché par login.php
        $_SESSION['flash_success'] = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte — Monnwear</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
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
            padding: 2rem 1rem;
        }

        .register-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 12px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 460px;
        }

        .register-card .brand {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--mw-white);
        }

        .register-card .subtitle {
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

        .btn-register {
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
            transition: background-color 0.2s;
        }

        .btn-register:hover { background-color: var(--mw-accent); color: var(--mw-black); }

        .divider { border-color: var(--mw-border); margin: 1.5rem 0; }

        .login-link {
            font-size: 0.85rem;
            color: var(--mw-muted);
            text-align: center;
        }

        .login-link a { color: var(--mw-white); text-decoration: none; font-weight: 500; }
        .login-link a:hover { text-decoration: underline; }

        .alert-error {
            background-color: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #f8a0a8;
            border-radius: 8px;
            font-size: 0.88rem;
            padding: 0.65rem 0.9rem;
        }

        .alert-error ul { margin: 0; padding-left: 1.25rem; }
        .alert-error li + li { margin-top: 0.2rem; }
    </style>
</head>
<body>

    <div class="register-card">

        <div class="text-center mb-4">
            <div class="brand">Monnwear</div>
            <div class="subtitle">Créer un nouveau compte</div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert-error mb-3">
                <i class="bi bi-exclamation-circle me-1"></i>
                <?php if (count($errors) === 1): ?>
                    <?= htmlspecialchars($errors[0]) ?>
                <?php else: ?>
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>

            <div class="mb-3">
                <label for="nom" class="form-label">Nom complet</label>
                <input type="text" id="nom" name="nom" class="form-control"
                       placeholder="Jean Dupont"
                       value="<?= htmlspecialchars($form['nom']) ?>"
                       required autocomplete="name">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="votre@email.com"
                       value="<?= htmlspecialchars($form['email']) ?>"
                       required autocomplete="email">
            </div>

            <div class="mb-3">
                <label for="adresse" class="form-label">Adresse de livraison</label>
                <textarea id="adresse" name="adresse" class="form-control"
                          rows="2" placeholder="12 Rue de la Paix, Tunis"
                          required><?= htmlspecialchars($form['adresse']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Min. 8 caractères"
                       required autocomplete="new-password">
            </div>

            <div class="mb-4">
                <label for="confirm" class="form-label">Confirmer le mot de passe</label>
                <input type="password" id="confirm" name="confirm" class="form-control"
                       placeholder="••••••••"
                       required autocomplete="new-password">
            </div>

            <button type="submit" class="btn-register">
                <i class="bi bi-person-plus me-1"></i> Créer mon compte
            </button>

        </form>

        <hr class="divider">

        <p class="login-link">
            Déjà un compte ? <a href="login.php">Se connecter</a>
        </p>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
