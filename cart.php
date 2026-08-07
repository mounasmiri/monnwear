<?php
/**
 * cart.php
 * Page du panier Monnwear.
 *
 * Fonctionnement :
 *  - Lit le contenu du panier depuis la session PHP ($_SESSION['cart']).
 *  - Affiche chaque article avec image, nom, prix unitaire, sélecteur de quantité et sous-total.
 *  - Affiche le récapitulatif (total, livraison) et un bouton vers checkout.php.
 *  - Affiche un état vide si le panier ne contient aucun article.
 */

// session_start() avant tout output — charge les données de session
session_start();

require_once 'includes/db_connect.php';


// ----------------------------------------------------------------
// 1. LECTURE DU PANIER DEPUIS LA SESSION
// ----------------------------------------------------------------
/*
 * Si $_SESSION['cart'] n'existe pas encore, on l'initialise comme
 * tableau vide pour éviter les erreurs PHP lors des boucles.
 */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartItems = $_SESSION['cart']; // Tableau des articles du panier

// ----------------------------------------------------------------
// 2. CALCUL DU TOTAL ET DU NOMBRE D'ARTICLES
// ----------------------------------------------------------------
$total = 0;
foreach ($cartItems as $item) {
    // Sous-total = prix unitaire × quantité
    $total += $item['prix'] * $item['quantite'];
}

/*
 * array_column() extrait toutes les valeurs 'quantite' du tableau.
 * array_sum() les additionne pour obtenir le nombre total d'articles.
 */
$nbArticles = !empty($cartItems) ? array_sum(array_column($cartItems, 'quantite')) : 0;

// Livraison gratuite dès 200 DT
$fraisLivraison = $total >= 200 ? 0 : 8;
$totalTTC       = $total + $fraisLivraison;

// Badge navbar
$cartCount = $nbArticles;

// Message d'erreur Stripe (posé par create-checkout-session.php si l'API échoue)
$stripeError = '';
if (isset($_SESSION['stripe_error'])) {
    $stripeError = $_SESSION['stripe_error'];
    unset($_SESSION['stripe_error']); // Affichage unique (flash message)
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier — Monnwear</title>

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

        * { box-sizing: border-box; }

        body {
            background-color: var(--mw-black);
            color: var(--mw-white);
            font-family: 'Space Grotesk', sans-serif;
            margin: 0;
        }

        /* NAVBAR */
        .navbar-mw { background-color: var(--mw-dark); border-bottom: 1px solid var(--mw-border); padding: 1rem 0; }
        .navbar-brand-mw { font-size: 1.5rem; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: var(--mw-white) !important; text-decoration: none; }
        .navbar-mw .nav-link { color: var(--mw-muted) !important; font-size: 0.85rem; letter-spacing: 0.08em; text-transform: uppercase; font-weight: 500; transition: color 0.2s; }
        .navbar-mw .nav-link:hover { color: var(--mw-white) !important; }
        .cart-icon { font-size: 1.3rem; color: var(--mw-muted); transition: color 0.2s; text-decoration: none; }
        .cart-icon:hover { color: var(--mw-white); }
        .navbar-toggler { border-color: var(--mw-border); }
        .navbar-toggler-icon { filter: invert(1); }

        /* EN-TÊTE PAGE */
        .page-header {
            background-color: var(--mw-dark);
            border-bottom: 1px solid var(--mw-border);
            padding: 2.5rem 0;
        }
        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0;
        }
        .page-header span {
            font-size: 0.8rem;
            color: var(--mw-muted);
            letter-spacing: 0.05em;
        }

        /* ZONE PRINCIPALE */
        .cart-section { padding: 3rem 0 6rem; }

        /* ARTICLE DU PANIER */
        .cart-item {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 1rem;
            transition: border-color 0.2s;
        }
        .cart-item:hover { border-color: #3a3a3a; }

        /* Petite image de l'article */
        .cart-item-img {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border-radius: 2px;
            background-color: #161616;
            flex-shrink: 0;
        }

        .cart-item-info { flex: 1; min-width: 0; }

        .cart-item-category {
            font-size: 0.65rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin-bottom: 0.3rem;
        }
        .cart-item-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--mw-white);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.3rem;
        }
        .cart-item-unit-price {
            font-size: 0.8rem;
            color: var(--mw-muted);
        }

        /* Sélecteur de quantité */
        .qty-form { display: flex; align-items: center; gap: 6px; }

        .qty-btn {
            width: 28px;
            height: 28px;
            background: transparent;
            border: 1px solid var(--mw-border);
            color: var(--mw-muted);
            font-size: 1rem;
            line-height: 1;
            border-radius: 2px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: border-color 0.2s, color 0.2s;
        }
        .qty-btn:hover { border-color: var(--mw-white); color: var(--mw-white); }

        .qty-input {
            width: 44px;
            height: 28px;
            background: transparent;
            border: 1px solid var(--mw-border);
            color: var(--mw-white);
            font-size: 0.85rem;
            text-align: center;
            border-radius: 2px;
            font-family: inherit;
        }
        .qty-input:focus { outline: none; border-color: var(--mw-white); }

        /* Sous-total par article */
        .cart-item-subtotal {
            font-size: 1rem;
            font-weight: 700;
            color: var(--mw-white);
            min-width: 90px;
            text-align: right;
        }

        /* Bouton supprimer */
        .btn-remove {
            background: transparent;
            border: none;
            color: var(--mw-muted);
            font-size: 1.1rem;
            cursor: pointer;
            padding: 4px;
            transition: color 0.2s;
            flex-shrink: 0;
            text-decoration: none;
        }
        .btn-remove:hover { color: #f87171; }

        /* RÉCAPITULATIF COMMANDE */
        .order-summary {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            padding: 1.75rem;
            position: sticky;
            top: 90px;
        }
        .summary-title {
            font-size: 0.72rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin-bottom: 1.25rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--mw-muted);
            margin-bottom: 0.75rem;
        }
        .summary-row.total {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--mw-white);
            padding-top: 1rem;
            margin-top: 0.5rem;
            border-top: 1px solid var(--mw-border);
        }
        .summary-row .free { color: #4ade80; font-weight: 600; }

        /* Boutons */
        .btn-checkout {
            width: 100%;
            background-color: var(--mw-white);
            color: var(--mw-black);
            border: none;
            padding: 0.95rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            border-radius: 2px;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: background-color 0.2s;
            margin-top: 1.5rem;
        }
        .btn-checkout:hover { background-color: var(--mw-accent); color: var(--mw-black); }

        .btn-stripe {
            width: 100%;
            background-color: #635bff;
            color: #fff;
            border: none;
            padding: 0.95rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            border-radius: 2px;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: background-color 0.2s;
            margin-top: 0.75rem;
        }
        .btn-stripe:hover { background-color: #4f46e5; color: #fff; }

        .stripe-error {
            background-color: rgba(220, 53, 69, 0.12);
            border: 1px solid rgba(220, 53, 69, 0.35);
            color: #f8a0a8;
            border-radius: 4px;
            font-size: 0.83rem;
            padding: 0.6rem 0.85rem;
            margin-top: 0.75rem;
        }

        .btn-continue {
            width: 100%;
            background: transparent;
            border: 1px solid var(--mw-border);
            color: var(--mw-muted);
            padding: 0.75rem;
            font-size: 0.78rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border-radius: 2px;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: border-color 0.2s, color 0.2s;
            margin-top: 0.75rem;
        }
        .btn-continue:hover { border-color: var(--mw-white); color: var(--mw-white); }

        /* ÉTAT PANIER VIDE */
        .empty-cart {
            text-align: center;
            padding: 6rem 1rem;
        }
        .empty-cart-icon { font-size: 4rem; color: var(--mw-border); margin-bottom: 1.5rem; }
        .empty-cart h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: 0.5rem; }
        .empty-cart p { color: var(--mw-muted); font-size: 0.9rem; margin-bottom: 2rem; }

        /* FOOTER */
        .footer-mw { background-color: var(--mw-dark); border-top: 1px solid var(--mw-border); padding: 2rem 0; }
        .footer-mw p { color: var(--mw-muted); font-size: 0.8rem; letter-spacing: 0.05em; margin: 0; }

        @media (max-width: 576px) {
            .cart-item { flex-wrap: wrap; }
            .cart-item-subtotal { min-width: auto; }
        }
    </style>
</head>
<body>

<?php $activeLink = ''; require 'includes/navbar.php'; ?>

<!-- EN-TÊTE PAGE -->
<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p style="font-size:0.72rem;letter-spacing:0.2em;text-transform:uppercase;color:var(--mw-muted);margin-bottom:0.4rem;">
                    <a href="index.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    &nbsp;/&nbsp; Panier
                </p>
                <h1 class="page-header">Mon Panier</h1>
            </div>
            <?php if ($nbArticles > 0): ?>
            <span><?= $nbArticles ?> article<?= $nbArticles > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- CONTENU PRINCIPAL -->
<section class="cart-section">
    <div class="container">

        <?php if (empty($cartItems)): ?>
        <!-- ============================================================
             ÉTAT VIDE : le panier ne contient aucun article
        ============================================================ -->
        <div class="empty-cart">
            <div class="empty-cart-icon"><i class="bi bi-bag-x"></i></div>
            <h2>Votre panier est vide</h2>
            <p>Ajoutez des pièces depuis notre boutique.</p>
            <a href="shop.php" class="btn-checkout" style="width:auto;display:inline-block;padding:0.85rem 2.5rem;">
                Découvrir la boutique
            </a>
        </div>

        <?php else: ?>
        <!-- ============================================================
             PANIER AVEC ARTICLES
        ============================================================ -->
        <div class="row g-4">

            <!-- -------- Colonne gauche : liste des articles -------- -->
            <div class="col-lg-8">

                <?php
                /*
                 * Boucle sur les articles du panier (tableau session).
                 * $idProduit = clé du tableau (= id BDD du produit).
                 * $item      = tableau associatif avec nom, prix, quantite, etc.
                 */
                foreach ($cartItems as $idProduit => $item):
                    $sousTotal = $item['prix'] * $item['quantite'];
                ?>
                <div class="cart-item">

                    <!-- Miniature du produit -->
                    <img src="<?= htmlspecialchars($item['image_url']) ?>"
                         alt="<?= htmlspecialchars($item['nom']) ?>"
                         class="cart-item-img">

                    <!-- Informations produit -->
                    <div class="cart-item-info">
                        <div class="cart-item-name"><?= htmlspecialchars($item['nom']) ?></div>
                        <div class="cart-item-unit-price">
                            <?= number_format($item['prix'], 2, ',', ' ') ?>&nbsp;DT / unité
                        </div>
                    </div>

                    <!--
                        Formulaire de mise à jour de quantité.
                        Envoie id et quantite en POST vers update_cart.php.
                        JavaScript : les boutons +/- modifient l'input et soumettent le formulaire.
                    -->
                    <form method="POST" action="update_cart.php" class="qty-form">
                        <input type="hidden" name="id" value="<?= (int)$idProduit ?>">

                        <!-- Bouton décrémenter -->
                        <button type="button" class="qty-btn"
                                onclick="changeQty(this, -1)">−</button>

                        <!-- Champ quantité -->
                        <input type="number"
                               name="quantite"
                               class="qty-input"
                               value="<?= (int)$item['quantite'] ?>"
                               min="1"
                               max="<?= (int)$item['stock_max'] ?>"
                               onchange="this.form.submit()">

                        <!-- Bouton incrémenter -->
                        <button type="button" class="qty-btn"
                                onclick="changeQty(this, 1)">+</button>
                    </form>

                    <!-- Sous-total pour cet article -->
                    <div class="cart-item-subtotal">
                        <?= number_format($sousTotal, 2, ',', ' ') ?>&nbsp;DT
                    </div>

                    <!--
                        Lien de suppression → remove_from_cart.php?id=X
                        (int) pour sécuriser l'id dans l'URL.
                    -->
                    <a href="remove_from_cart.php?id=<?= (int)$idProduit ?>"
                       class="btn-remove"
                       title="Retirer du panier">
                        <i class="bi bi-trash3"></i>
                    </a>

                </div>
                <?php endforeach; ?>

            </div><!-- /.col-lg-8 -->

            <!-- -------- Colonne droite : récapitulatif -------- -->
            <div class="col-lg-4">
                <div class="order-summary">
                    <p class="summary-title">Récapitulatif</p>

                    <!-- Sous-total -->
                    <div class="summary-row">
                        <span>Sous-total</span>
                        <span><?= number_format($total, 2, ',', ' ') ?>&nbsp;DT</span>
                    </div>

                    <!-- Frais de livraison -->
                    <div class="summary-row">
                        <span>Livraison</span>
                        <?php if ($fraisLivraison === 0): ?>
                            <span class="free">Gratuite</span>
                        <?php else: ?>
                            <span><?= number_format($fraisLivraison, 2, ',', ' ') ?>&nbsp;DT</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($fraisLivraison > 0): ?>
                    <!-- Message d'encouragement pour atteindre la livraison gratuite -->
                    <div style="font-size:0.72rem;color:var(--mw-muted);margin-bottom:0.5rem;">
                        Plus que <?= number_format(200 - $total, 2, ',', ' ') ?>&nbsp;DT
                        pour bénéficier de la livraison gratuite.
                    </div>
                    <?php endif; ?>

                    <!-- Total TTC -->
                    <div class="summary-row total">
                        <span>Total</span>
                        <span><?= number_format($totalTTC, 2, ',', ' ') ?>&nbsp;DT</span>
                    </div>

                    <!-- Bouton commander (checkout interne) -->
                    <a href="checkout.php" class="btn-checkout">
                        <i class="bi bi-lock me-2"></i> Commander
                    </a>

                    <!-- Bouton paiement Stripe -->
                    <a href="create-checkout-session.php" class="btn-stripe">
                        <i class="bi bi-credit-card-2-front me-2"></i> Payer avec Stripe
                    </a>

                    <?php if ($stripeError !== ''): ?>
                    <div class="stripe-error">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        <?= htmlspecialchars($stripeError) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Continuer à shopper -->
                    <a href="shop.php" class="btn-continue">
                        <i class="bi bi-arrow-left me-1"></i> Continuer mes achats
                    </a>

                </div><!-- /.order-summary -->
            </div><!-- /.col-lg-4 -->

        </div><!-- /.row -->
        <?php endif; ?>

    </div><!-- /.container -->
</section>

<!-- FOOTER -->
<footer class="footer-mw">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <p>© <?= date('Y') ?> Monnwear. Tous droits réservés.</p>
        <p style="letter-spacing:0.2em;font-size:0.7rem;">WEAR THE SILENCE.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/**
 * changeQty(btn, delta)
 * Incrémente ou décrémente la quantité dans l'input adjacent,
 * puis soumet automatiquement le formulaire parent vers update_cart.php.
 *
 * @param {HTMLElement} btn   - Le bouton cliqué (+ ou -)
 * @param {number}      delta - +1 ou -1
 */
function changeQty(btn, delta) {
    // On remonte au formulaire parent du bouton pour trouver l'input
    const form  = btn.closest('form');
    const input = form.querySelector('input[name="quantite"]');

    const newVal = parseInt(input.value) + delta;
    const min    = parseInt(input.min);
    const max    = parseInt(input.max);

    // On s'assure de rester dans les bornes [min, max]
    if (newVal >= min && newVal <= max) {
        input.value = newVal;
        form.submit(); // Soumet le formulaire → update_cart.php
    }
}
</script>
</body>
</html>
