<?php
/**
 * checkout.php
 * Page de finalisation de commande.
 *
 * Fonctionnement :
 *  - Vérifie que le panier n'est pas vide (sinon redirige vers cart.php).
 *  - Affiche un récapitulatif de commande (droite) + formulaire client (gauche).
 *  - Soumet les données en POST vers place_order.php.
 *  - Affiche les erreurs de validation retournées par place_order.php (via session flash).
 */

session_start();
require_once 'includes/db_connect.php';


// ----------------------------------------------------------------
// 1. VÉRIFICATION DU PANIER
// ----------------------------------------------------------------
/*
 * Si le panier est vide ou inexistant, on redirige vers cart.php.
 * Il n'y a rien à commander sans article.
 */
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}


// ----------------------------------------------------------------
// 2. CALCUL DU RÉCAPITULATIF
// ----------------------------------------------------------------
$cartItems = $_SESSION['cart'];

$total = 0;
foreach ($cartItems as $item) {
    $total += $item['prix'] * $item['quantite'];
}

$nbArticles     = array_sum(array_column($cartItems, 'quantite'));
$fraisLivraison = $total >= 200 ? 0 : 8;
$totalTTC       = $total + $fraisLivraison;
$cartCount      = $nbArticles;


// ----------------------------------------------------------------
// 3. RÉCUPÉRATION DU MESSAGE D'ERREUR FLASH (si renvoyé par place_order.php)
// ----------------------------------------------------------------
/*
 * place_order.php stocke les erreurs dans $_SESSION['checkout_error']
 * avant de rediriger ici. On lit le message une seule fois puis on le supprime.
 * C'est le pattern "flash message" : message à usage unique.
 */
$erreurFlash = '';
if (isset($_SESSION['checkout_error'])) {
    $erreurFlash = $_SESSION['checkout_error'];
    unset($_SESSION['checkout_error']); // Suppression après lecture
}

// Pré-remplir le formulaire avec les valeurs soumises si une erreur s'est produite
$oldNom     = htmlspecialchars($_SESSION['checkout_old']['nom']     ?? '');
$oldEmail   = htmlspecialchars($_SESSION['checkout_old']['email']   ?? '');
$oldAdresse = htmlspecialchars($_SESSION['checkout_old']['adresse'] ?? '');
unset($_SESSION['checkout_old']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander — Monnwear</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --mw-black:  #0a0a0a; --mw-dark: #111111; --mw-card: #1a1a1a;
            --mw-border: #2a2a2a; --mw-white: #f5f5f5; --mw-muted: #888888;
        }
        * { box-sizing: border-box; }
        body { background-color: var(--mw-black); color: var(--mw-white); font-family: 'Space Grotesk', sans-serif; margin: 0; }

        /* NAVBAR */
        .navbar-mw { background-color: var(--mw-dark); border-bottom: 1px solid var(--mw-border); padding: 1rem 0; }
        .navbar-brand-mw { font-size: 1.5rem; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: var(--mw-white) !important; text-decoration: none; }
        .navbar-mw .nav-link { color: var(--mw-muted) !important; font-size: 0.85rem; letter-spacing: 0.08em; text-transform: uppercase; font-weight: 500; transition: color 0.2s; }
        .navbar-mw .nav-link:hover { color: var(--mw-white) !important; }
        .cart-icon { font-size: 1.3rem; color: var(--mw-muted); transition: color 0.2s; text-decoration: none; }
        .cart-icon:hover { color: var(--mw-white); }
        .navbar-toggler { border-color: var(--mw-border); }
        .navbar-toggler-icon { filter: invert(1); }

        /* EN-TÊTE */
        .page-header { background-color: var(--mw-dark); border-bottom: 1px solid var(--mw-border); padding: 2.5rem 0; }
        .page-title { font-size: 2rem; font-weight: 700; letter-spacing: -0.02em; margin: 0; }

        /* SECTION PRINCIPALE */
        .checkout-section { padding: 3rem 0 6rem; }

        /* ÉTAPES VISUELLES */
        .steps {
            display: flex;
            gap: 0;
            margin-bottom: 2.5rem;
        }
        .step {
            flex: 1;
            text-align: center;
            font-size: 0.7rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 0.75rem 0;
            border-bottom: 2px solid var(--mw-border);
            color: var(--mw-muted);
        }
        .step.active {
            border-color: var(--mw-white);
            color: var(--mw-white);
            font-weight: 600;
        }
        .step.done {
            border-color: #4ade80;
            color: #4ade80;
        }

        /* FORMULAIRE CLIENT */
        .form-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            padding: 2rem;
        }
        .form-section-title {
            font-size: 0.72rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin-bottom: 1.5rem;
        }

        /* Champs de formulaire */
        .form-label-mw {
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin-bottom: 0.4rem;
            display: block;
        }
        .form-control-mw {
            width: 100%;
            background-color: #111;
            border: 1px solid var(--mw-border);
            color: var(--mw-white);
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            font-family: inherit;
            border-radius: 2px;
            transition: border-color 0.2s;
        }
        .form-control-mw:focus {
            outline: none;
            border-color: var(--mw-white);
        }
        .form-control-mw::placeholder { color: var(--mw-muted); }

        /* MESSAGE D'ERREUR FLASH */
        .alert-error {
            background-color: rgba(248, 113, 113, 0.1);
            border: 1px solid rgba(248, 113, 113, 0.3);
            color: #f87171;
            padding: 1rem 1.25rem;
            border-radius: 2px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        /* RÉCAPITULATIF DROITE */
        .summary-card {
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

        /* Mini-article dans le récap */
        .summary-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }
        .summary-item-img {
            width: 50px;
            height: 60px;
            object-fit: cover;
            border-radius: 2px;
            background-color: #161616;
            flex-shrink: 0;
        }
        .summary-item-info { flex: 1; min-width: 0; }
        .summary-item-name {
            font-size: 0.82rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .summary-item-qty {
            font-size: 0.72rem;
            color: var(--mw-muted);
        }
        .summary-item-price {
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .summary-divider { border: none; border-top: 1px solid var(--mw-border); margin: 1rem 0; }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--mw-muted);
            margin-bottom: 0.6rem;
        }
        .summary-row.total {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--mw-white);
            margin-top: 0.5rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--mw-border);
        }
        .free { color: #4ade80; font-weight: 600; }

        /* BOUTON COMMANDER */
        .btn-place-order {
            width: 100%;
            background-color: var(--mw-white);
            color: var(--mw-black);
            border: none;
            padding: 1rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            border-radius: 2px;
            cursor: pointer;
            transition: background-color 0.2s;
            font-family: inherit;
            margin-top: 1.25rem;
        }
        .btn-place-order:hover { background-color: #e0e0e0; }

        /* FOOTER */
        .footer-mw { background-color: var(--mw-dark); border-top: 1px solid var(--mw-border); padding: 2rem 0; }
        .footer-mw p { color: var(--mw-muted); font-size: 0.8rem; letter-spacing: 0.05em; margin: 0; }
    </style>
</head>
<body>

<?php $activeLink = ''; $showAllNavLinks = false; require 'includes/navbar.php'; ?>

<!-- EN-TÊTE -->
<div class="page-header">
    <div class="container">
        <p style="font-size:0.72rem;letter-spacing:0.2em;text-transform:uppercase;color:var(--mw-muted);margin-bottom:0.4rem;">
            <a href="cart.php" style="color:inherit;text-decoration:none;">Panier</a> &nbsp;/&nbsp; Commande
        </p>
        <h1 class="page-title">Finaliser la commande</h1>
    </div>
</div>

<!-- CONTENU PRINCIPAL -->
<section class="checkout-section">
    <div class="container">

        <!-- Étapes visuelles -->
        <div class="steps mb-4">
            <div class="step done"><i class="bi bi-check2 me-1"></i> Panier</div>
            <div class="step active">Informations</div>
            <div class="step">Confirmation</div>
        </div>

        <div class="row g-4">

            <!-- -------- Colonne gauche : formulaire -------- -->
            <div class="col-lg-7">
                <div class="form-card">

                    <!-- Message d'erreur flash (si place_order.php a renvoyé une erreur) -->
                    <?php if (!empty($erreurFlash)): ?>
                    <div class="alert-error">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($erreurFlash) ?>
                    </div>
                    <?php endif; ?>

                    <p class="form-section-title">Vos coordonnées</p>

                    <!--
                        Le formulaire envoie les données en POST vers place_order.php.
                        method="POST" : les données ne s'affichent pas dans l'URL (sécurité).
                        action="place_order.php" : page qui traitera la commande.
                    -->
                    <form method="POST" action="place_order.php">

                        <!-- Champ Nom complet -->
                        <div class="mb-3">
                            <label class="form-label-mw" for="nom">Nom complet *</label>
                            <input type="text"
                                   id="nom"
                                   name="nom"
                                   class="form-control-mw"
                                   placeholder="Ex : Ahmed Ben Ali"
                                   value="<?= $oldNom ?>"
                                   required
                                   maxlength="150">
                        </div>

                        <!-- Champ Email -->
                        <div class="mb-3">
                            <label class="form-label-mw" for="email">Adresse email *</label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control-mw"
                                   placeholder="Ex : ahmed@email.com"
                                   value="<?= $oldEmail ?>"
                                   required
                                   maxlength="200">
                            <small style="color:var(--mw-muted);font-size:0.72rem;margin-top:4px;display:block;">
                                Si vous avez déjà commandé, votre historique sera conservé.
                            </small>
                        </div>

                        <!-- Champ Adresse de livraison -->
                        <div class="mb-4">
                            <label class="form-label-mw" for="adresse">Adresse de livraison *</label>
                            <textarea id="adresse"
                                      name="adresse"
                                      class="form-control-mw"
                                      placeholder="Rue, ville, code postal, gouvernorat"
                                      rows="3"
                                      required
                                      maxlength="500"><?= $oldAdresse ?></textarea>
                        </div>

                        <!-- Bouton de soumission -->
                        <button type="submit" class="btn-place-order">
                            <i class="bi bi-lock me-2"></i> Confirmer la commande
                        </button>

                        <p style="font-size:0.72rem;color:var(--mw-muted);text-align:center;margin-top:1rem;">
                            En confirmant, vous acceptez nos conditions générales de vente.
                        </p>

                    </form>
                </div>
            </div><!-- /.col-lg-7 -->

            <!-- -------- Colonne droite : récapitulatif -------- -->
            <div class="col-lg-5">
                <div class="summary-card">

                    <p class="summary-title">Votre commande (<?= $nbArticles ?> article<?= $nbArticles > 1 ? 's' : '' ?>)</p>

                    <?php
                    /*
                     * Affichage de chaque article du panier en version compacte.
                     * Pas de formulaire ici — c'est un récapitulatif en lecture seule.
                     */
                    foreach ($cartItems as $idProduit => $item): ?>
                    <div class="summary-item">
                        <img src="<?= htmlspecialchars($item['image_url']) ?>"
                             alt="<?= htmlspecialchars($item['nom']) ?>"
                             class="summary-item-img">
                        <div class="summary-item-info">
                            <div class="summary-item-name"><?= htmlspecialchars($item['nom']) ?></div>
                            <div class="summary-item-qty">Qté : <?= (int)$item['quantite'] ?></div>
                        </div>
                        <div class="summary-item-price">
                            <?= number_format($item['prix'] * $item['quantite'], 2, ',', ' ') ?>&nbsp;DT
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <hr class="summary-divider">

                    <!-- Totaux -->
                    <div class="summary-row">
                        <span>Sous-total</span>
                        <span><?= number_format($total, 2, ',', ' ') ?>&nbsp;DT</span>
                    </div>
                    <div class="summary-row">
                        <span>Livraison</span>
                        <?php if ($fraisLivraison === 0): ?>
                            <span class="free">Gratuite</span>
                        <?php else: ?>
                            <span><?= number_format($fraisLivraison, 2, ',', ' ') ?>&nbsp;DT</span>
                        <?php endif; ?>
                    </div>
                    <div class="summary-row total">
                        <span>Total à payer</span>
                        <span><?= number_format($totalTTC, 2, ',', ' ') ?>&nbsp;DT</span>
                    </div>

                </div>
            </div><!-- /.col-lg-5 -->

        </div><!-- /.row -->
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
</body>
</html>
