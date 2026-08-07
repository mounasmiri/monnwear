<?php
/**
 * cancel.php
 * Stripe redirige ici si l'utilisateur clique "Annuler" sur la page de paiement.
 * Le panier est conservé — rien n'a été débité.
 */

session_start();

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantite')) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement annulé — Monnwear</title>

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

        .navbar-mw { background-color: var(--mw-dark); border-bottom: 1px solid var(--mw-border); padding: 1rem 0; }
        .navbar-brand-mw { font-size: 1.5rem; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: var(--mw-white) !important; text-decoration: none; }
        .cart-icon { font-size: 1.3rem; color: var(--mw-muted); transition: color 0.2s; text-decoration: none; }
        .cart-icon:hover { color: var(--mw-white); }
        .navbar-toggler { border-color: var(--mw-border); }
        .navbar-toggler-icon { filter: invert(1); }

        .cancel-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 1rem;
        }

        .cancel-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 12px;
            padding: 3rem 2.5rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        .cancel-icon {
            width: 72px;
            height: 72px;
            background-color: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.75rem;
            font-size: 2rem;
            color: #fbbf24;
        }

        .cancel-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--mw-white);
            margin-bottom: 0.75rem;
        }

        .cancel-sub {
            font-size: 0.9rem;
            color: var(--mw-muted);
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .btn-cart {
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

        .btn-cart:hover { background-color: var(--mw-accent); color: var(--mw-black); }

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

        .footer-mw { background-color: var(--mw-dark); border-top: 1px solid var(--mw-border); padding: 1.5rem 0; }
        .footer-mw p { color: var(--mw-muted); font-size: 0.78rem; margin: 0; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-mw">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand-mw" href="index.php">Monnwear</a>
        <a href="cart.php" class="cart-icon position-relative">
            <i class="bi bi-bag"></i>
            <?php if ($cartCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                  style="background:#f5f5f5;color:#0a0a0a;font-size:0.6rem;"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
    </div>
</nav>

<!-- CONTENU -->
<section class="cancel-section">
    <div class="cancel-card">

        <div class="cancel-icon">
            <i class="bi bi-x-lg"></i>
        </div>

        <h1 class="cancel-title">Paiement annulé</h1>

        <p class="cancel-sub">
            Votre paiement a été annulé. Aucun montant n'a été débité.
            Vos articles sont toujours dans votre panier.
        </p>

        <div>
            <a href="cart.php" class="btn-cart">
                <i class="bi bi-bag me-1"></i> Retour au panier
            </a>
            <a href="shop.php" class="btn-shop">
                <i class="bi bi-arrow-left me-1"></i> Continuer mes achats
            </a>
        </div>

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
