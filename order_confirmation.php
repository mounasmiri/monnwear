<?php
/**
 * order_confirmation.php
 * Page de confirmation après une commande réussie.
 *
 * Fonctionnement :
 *  - Lit l'id de commande depuis $_SESSION['last_order_id'] (posé par place_order.php).
 *  - Récupère les détails de la commande en BDD.
 *  - Affiche le récapitulatif complet : numéro, client, articles, total.
 *  - Supprime last_order_id de la session (page à usage unique).
 */

session_start();
require_once 'includes/db_connect.php';


// ----------------------------------------------------------------
// 1. VÉRIFICATION DE L'ID DE COMMANDE EN SESSION
// ----------------------------------------------------------------
/*
 * Si $_SESSION['last_order_id'] n'existe pas, l'utilisateur a accédé
 * directement à cette URL sans passer par le tunnel de commande.
 * On le redirige vers l'accueil.
 */
if (!isset($_SESSION['last_order_id'])) {
    header('Location: index.php');
    exit();
}

$idCommande = (int)$_SESSION['last_order_id'];

/*
 * On supprime la clé de session IMMÉDIATEMENT après lecture.
 * Cela empêche l'utilisateur de recharger la page et de revoir
 * la confirmation (pattern "flash" à usage unique).
 */
unset($_SESSION['last_order_id']);


// ----------------------------------------------------------------
// 2. RÉCUPÉRATION DE LA COMMANDE ET DU CLIENT
// ----------------------------------------------------------------
$stmtCommande = $pdo->prepare('
    SELECT cm.id_commande, cm.date_commande, cm.statut,
           cl.nom AS client_nom, cl.email AS client_email, cl.adresse AS client_adresse
    FROM Commandes cm
    INNER JOIN Clients cl ON cm.id_client = cl.id_client
    WHERE cm.id_commande = :id
    LIMIT 1
');
$stmtCommande->bindValue(':id', $idCommande, PDO::PARAM_INT);
$stmtCommande->execute();
$commande = $stmtCommande->fetch();

// Commande introuvable en BDD (ne devrait pas arriver) → accueil
if ($commande === false) {
    header('Location: index.php');
    exit();
}


// ----------------------------------------------------------------
// 3. RÉCUPÉRATION DES ARTICLES DE LA COMMANDE
// ----------------------------------------------------------------
/*
 * On joint Details_Commande avec Produits pour obtenir
 * le nom, prix et image de chaque article commandé.
 */
$stmtItems = $pdo->prepare('
    SELECT dc.quantite, p.nom, p.prix, p.image_url
    FROM Details_Commande dc
    INNER JOIN Produits p ON dc.id_produit = p.id_produit
    WHERE dc.id_commande = :id
    ORDER BY p.nom ASC
');
$stmtItems->bindValue(':id', $idCommande, PDO::PARAM_INT);
$stmtItems->execute();
$items = $stmtItems->fetchAll();


// ----------------------------------------------------------------
// 4. CALCUL DU TOTAL
// ----------------------------------------------------------------
$total = 0;
foreach ($items as $item) {
    $total += $item['prix'] * $item['quantite'];
}
$fraisLivraison = $total >= 200 ? 0 : 8;
$totalTTC       = $total + $fraisLivraison;

// Badge panier (vide après commande)
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantite')) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée — Monnwear</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --mw-black: #0a0a0a; --mw-dark: #111111; --mw-card: #1a1a1a;
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

        /* ZONE PRINCIPALE */
        .confirm-section { padding: 4rem 0 7rem; }

        /* BANDEAU DE SUCCÈS */
        .success-banner {
            text-align: center;
            margin-bottom: 3rem;
        }
        .success-icon {
            width: 72px;
            height: 72px;
            background-color: rgba(74, 222, 128, 0.1);
            border: 2px solid #4ade80;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #4ade80;
            margin: 0 auto 1.5rem;
        }
        .success-title {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }
        .success-subtitle {
            color: var(--mw-muted);
            font-size: 0.9rem;
            max-width: 440px;
            margin: 0 auto;
            line-height: 1.65;
        }

        /* ÉTAPES VISUELLES */
        .steps { display: flex; gap: 0; margin-bottom: 2.5rem; }
        .step { flex: 1; text-align: center; font-size: 0.7rem; letter-spacing: 0.12em; text-transform: uppercase; padding: 0.75rem 0; border-bottom: 2px solid var(--mw-border); color: var(--mw-muted); }
        .step.done { border-color: #4ade80; color: #4ade80; }

        /* CARTE RÉCAPITULATIF */
        .recap-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .recap-section-title {
            font-size: 0.72rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--mw-border);
        }

        /* Numéro de commande */
        .order-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--mw-white);
        }
        .order-meta {
            font-size: 0.8rem;
            color: var(--mw-muted);
            margin-top: 0.25rem;
        }

        /* Badge statut */
        .status-badge {
            display: inline-block;
            font-size: 0.7rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 2px;
            background-color: rgba(74, 222, 128, 0.12);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.25);
            margin-top: 0.5rem;
        }

        /* Infos client */
        .info-row {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
            font-size: 0.88rem;
        }
        .info-label { color: var(--mw-muted); width: 80px; flex-shrink: 0; }
        .info-value { color: var(--mw-white); }

        /* Article commandé */
        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 0;
            border-bottom: 1px solid var(--mw-border);
        }
        .order-item:last-child { border-bottom: none; }
        .order-item-img {
            width: 56px;
            height: 70px;
            object-fit: cover;
            border-radius: 2px;
            background-color: #161616;
            flex-shrink: 0;
        }
        .order-item-info { flex: 1; min-width: 0; }
        .order-item-name { font-size: 0.9rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .order-item-qty { font-size: 0.75rem; color: var(--mw-muted); margin-top: 3px; }
        .order-item-price { font-size: 0.9rem; font-weight: 600; white-space: nowrap; }

        /* Totaux */
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--mw-muted);
            margin-bottom: 0.6rem;
        }
        .total-row.grand-total {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--mw-white);
            padding-top: 0.75rem;
            margin-top: 0.25rem;
            border-top: 1px solid var(--mw-border);
        }
        .free { color: #4ade80; font-weight: 600; }

        /* Boutons */
        .btn-shop-again {
            display: inline-block;
            background-color: var(--mw-white);
            color: var(--mw-black);
            border: none;
            padding: 0.85rem 2.5rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            border-radius: 2px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .btn-shop-again:hover { background-color: #e0e0e0; color: var(--mw-black); }

        /* FOOTER */
        .footer-mw { background-color: var(--mw-dark); border-top: 1px solid var(--mw-border); padding: 2rem 0; }
        .footer-mw p { color: var(--mw-muted); font-size: 0.8rem; letter-spacing: 0.05em; margin: 0; }
    </style>
</head>
<body>

<?php $activeLink = ''; $showAllNavLinks = false; require 'includes/navbar.php'; ?>

<!-- CONTENU -->
<section class="confirm-section">
    <div class="container" style="max-width: 760px;">

        <!-- Étapes visuelles (toutes complétées) -->
        <div class="steps mb-5">
            <div class="step done"><i class="bi bi-check2 me-1"></i> Panier</div>
            <div class="step done"><i class="bi bi-check2 me-1"></i> Informations</div>
            <div class="step done"><i class="bi bi-check2 me-1"></i> Confirmation</div>
        </div>

        <!-- Bandeau de succès -->
        <div class="success-banner">
            <div class="success-icon"><i class="bi bi-check2"></i></div>
            <h1 class="success-title">Commande confirmée !</h1>
            <p class="success-subtitle">
                Merci <?= htmlspecialchars($commande['client_nom']) ?>&nbsp;!
                Votre commande a bien été enregistrée. Vous recevrez un suivi
                dès l'expédition de votre colis.
            </p>
        </div>

        <!-- CARTE : Numéro de commande -->
        <div class="recap-card">
            <p class="recap-section-title">Détails de la commande</p>
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <div class="order-number">
                        Commande #<?= str_pad($commande['id_commande'], 5, '0', STR_PAD_LEFT) ?>
                    </div>
                    <div class="order-meta">
                        <!--
                            date() formate la date MySQL (format Y-m-d H:i:s)
                            en date lisible. strtotime() convertit la chaîne en timestamp Unix.
                        -->
                        Passée le <?= date('d/m/Y à H:i', strtotime($commande['date_commande'])) ?>
                    </div>
                    <span class="status-badge">
                        <i class="bi bi-clock me-1"></i><?= htmlspecialchars($commande['statut']) ?>
                    </span>
                </div>
                <a href="shop.php" class="btn-shop-again">
                    <i class="bi bi-bag me-2"></i> Recontinuer mes achats
                </a>
            </div>
        </div>

        <!-- CARTE : Informations client -->
        <div class="recap-card">
            <p class="recap-section-title">Livraison à</p>
            <div class="info-row">
                <span class="info-label">Nom</span>
                <span class="info-value"><?= htmlspecialchars($commande['client_nom']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value"><?= htmlspecialchars($commande['client_email']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Adresse</span>
                <span class="info-value"><?= htmlspecialchars($commande['client_adresse']) ?></span>
            </div>
        </div>

        <!-- CARTE : Articles commandés + totaux -->
        <div class="recap-card">
            <p class="recap-section-title">Articles (<?= count($items) ?>)</p>

            <?php
            /*
             * Affichage de chaque article commandé.
             * Les données viennent de la jointure Details_Commande × Produits.
             */
            foreach ($items as $item): ?>
            <div class="order-item">
                <img src="<?= htmlspecialchars($item['image_url'] ?? '') ?>"
                     alt="<?= htmlspecialchars($item['nom']) ?>"
                     class="order-item-img">
                <div class="order-item-info">
                    <div class="order-item-name"><?= htmlspecialchars($item['nom']) ?></div>
                    <div class="order-item-qty">
                        <?= (int)$item['quantite'] ?> × <?= number_format($item['prix'], 2, ',', ' ') ?>&nbsp;DT
                    </div>
                </div>
                <div class="order-item-price">
                    <?= number_format($item['prix'] * $item['quantite'], 2, ',', ' ') ?>&nbsp;DT
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Totaux -->
            <div style="margin-top:1.25rem;">
                <div class="total-row">
                    <span>Sous-total</span>
                    <span><?= number_format($total, 2, ',', ' ') ?>&nbsp;DT</span>
                </div>
                <div class="total-row">
                    <span>Livraison</span>
                    <?php if ($fraisLivraison === 0): ?>
                        <span class="free">Gratuite</span>
                    <?php else: ?>
                        <span><?= number_format($fraisLivraison, 2, ',', ' ') ?>&nbsp;DT</span>
                    <?php endif; ?>
                </div>
                <div class="total-row grand-total">
                    <span>Total payé</span>
                    <span><?= number_format($totalTTC, 2, ',', ' ') ?>&nbsp;DT</span>
                </div>
            </div>

        </div><!-- /.recap-card -->

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
