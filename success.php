<?php
/**
 * success.php
 * Page de confirmation après un paiement Stripe réussi.
 * Stripe redirige ici avec ?session_id=cs_test_... après le paiement.
 */

session_start();

// Vider le panier après paiement confirmé
// (Stripe a déjà traité le paiement — on nettoie la session côté PHP)
unset($_SESSION['cart']);

// Badge navbar
$cartCount = 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement confirmé — Monnwear</title>

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
            flex-direction: column;
        }

        /* NAVBAR */
        .navbar-mw { background-color: var(--mw-dark); border-bottom: 1px solid var(--mw-border); padding: 1rem 0; }
        .navbar-brand-mw { font-size: 1.5rem; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: var(--mw-white) !important; text-decoration: none; }
        .navbar-toggler { border-color: var(--mw-border); }
        .navbar-toggler-icon { filter: invert(1); }

        .success-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 1rem;
        }

        .success-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 12px;
            padding: 3rem 2.5rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
        }

        .success-icon {
            width: 72px;
            height: 72px;
            background-color: rgba(74, 222, 128, 0.12);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.75rem;
            font-size: 2rem;
            color: #4ade80;
        }

        .success-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--mw-white);
            margin-bottom: 0.75rem;
        }

        .success-sub {
            font-size: 0.9rem;
            color: var(--mw-muted);
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .btn-home {
            display: inline-block;
            background-color: var(--mw-white);
            color: var(--mw-black);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.75rem 2rem;
            text-decoration: none;
            transition: background-color 0.2s;
            margin: 0.35rem;
        }

        .btn-home:hover { background-color: var(--mw-accent); color: var(--mw-black); }

        .btn-shop {
            display: inline-block;
            background-color: transparent;
            color: var(--mw-white);
            border: 1px solid var(--mw-border);
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.88rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.75rem 2rem;
            text-decoration: none;
            transition: border-color 0.2s;
            margin: 0.35rem;
        }

        .btn-shop:hover { border-color: var(--mw-white); color: var(--mw-white); }

        .divider-mw { border-color: var(--mw-border); margin: 1.75rem 0; }

        .stripe-badge {
            font-size: 0.75rem;
            color: var(--mw-muted);
            letter-spacing: 0.04em;
        }

        .footer-mw { background-color: var(--mw-dark); border-top: 1px solid var(--mw-border); padding: 1.5rem 0; }
        .footer-mw p { color: var(--mw-muted); font-size: 0.78rem; margin: 0; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-mw">
    <div class="container">
        <a class="navbar-brand-mw" href="index.php">Monnwear</a>
    </div>
</nav>

<!-- CONTENU -->
<section class="success-section">
    <div class="success-card">

        <!-- Icône succès -->
        <div class="success-icon">
            <i class="bi bi-check-lg"></i>
        </div>

        <h1 class="success-title">Paiement confirmé !</h1>

        <p class="success-sub">
            Merci pour votre commande. Votre paiement a été traité avec succès
            par Stripe. Vous recevrez une confirmation par email sous peu.
        </p>

        <div>
            <a href="index.php" class="btn-home">
                <i class="bi bi-house me-1"></i> Accueil
            </a>
            <a href="shop.php" class="btn-shop">
                <i class="bi bi-bag me-1"></i> Continuer mes achats
            </a>
        </div>

        <hr class="divider-mw">

        <p class="stripe-badge">
            <i class="bi bi-shield-lock me-1"></i>
            Paiement sécurisé traité par Stripe
        </p>

    </div>
</section>

<!-- FOOTER -->
<footer class="footer-mw">
    <div class="container text-center">
        <p>© <?= date('Y') ?> Monnwear. Tous droits réservés.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
