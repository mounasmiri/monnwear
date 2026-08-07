<?php
/**
 * product.php
 * Fiche détaillée d'un produit Monnwear.
 *
 * Fonctionnement :
 *  - Reçoit l'identifiant du produit via l'URL : product.php?id=3
 *  - Valide et sécurise le paramètre GET.
 *  - Récupère les données du produit depuis la BDD avec un prepared statement PDO.
 *  - Affiche : image grande taille, nom, catégorie, prix, stock, description, bouton panier.
 */

// session_start() charge la session (panier, badge navbar)
session_start();

// ----------------------------------------------------------------
// 1. CONNEXION À LA BASE DE DONNÉES
// ----------------------------------------------------------------
require_once 'includes/db_connect.php';

// Badge navbar
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantite')) : 0;

// Détecte si le produit vient d'être ajouté au panier (?added=1)
// posé par add_to_cart.php pour afficher un message de succès.
$addedToCart = isset($_GET['added']) && $_GET['added'] === '1';


// ----------------------------------------------------------------
// 2. VALIDATION DU PARAMÈTRE GET 'id'
// ----------------------------------------------------------------
/*
 * On vérifie que le paramètre 'id' existe dans l'URL ET qu'il est
 * bien un entier positif.
 *
 * (int) cast : transforme n'importe quelle valeur en entier.
 *   → ?id=abc       → 0  (invalide)
 *   → ?id=3         → 3  (valide)
 *   → ?id=3;DROP... → 3  (injection neutralisée)
 *
 * Si l'id est invalide (≤ 0), on redirige vers shop.php immédiatement.
 * header('Location: ...') envoie un en-tête HTTP de redirection.
 * exit() est OBLIGATOIRE après header() pour stopper l'exécution PHP.
 */
$idProduit = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idProduit <= 0) {
    header('Location: shop.php');
    exit();
}


// ----------------------------------------------------------------
// 3. REQUÊTE SQL — RÉCUPÉRATION DU PRODUIT
// ----------------------------------------------------------------
/*
 * On joint la table Categories pour récupérer le nom de la catégorie
 * en une seule requête (évite une 2ème requête séparée).
 *
 * LIMIT 1 : même si l'id est unique (PRIMARY KEY), c'est une bonne
 * habitude d'ajouter LIMIT 1 sur les requêtes qui attendent 1 seul résultat.
 */
$stmt = $pdo->prepare('
    SELECT p.id_produit, p.nom, p.description, p.prix, p.stock, p.image_url,
           c.nom_categorie, c.id_categorie
    FROM Produits p
    INNER JOIN Categories c ON p.id_categorie = c.id_categorie
    WHERE p.id_produit = :id
    LIMIT 1
');

/*
 * bindValue() lie la valeur au placeholder nommé :id.
 * PDO::PARAM_INT garantit que la valeur est traitée comme un entier
 * côté MySQL — protection supplémentaire contre les injections SQL.
 */
$stmt->bindValue(':id', $idProduit, PDO::PARAM_INT);
$stmt->execute();

// fetch() (sans All) retourne une seule ligne, ou false si aucun résultat.
$produit = $stmt->fetch();


// ----------------------------------------------------------------
// 4. VÉRIFICATION — LE PRODUIT EXISTE-T-IL ?
// ----------------------------------------------------------------
/*
 * Si fetch() retourne false, l'id fourni n'existe pas en BDD.
 * On redirige vers shop.php plutôt que d'afficher une page vide.
 */
if ($produit === false) {
    header('Location: shop.php');
    exit();
}


// ----------------------------------------------------------------
// 5. PRÉPARATION DES VARIABLES D'AFFICHAGE
// ----------------------------------------------------------------
// Booléen de disponibilité
$enStock = $produit['stock'] > 0;

// Niveau de stock : normal / faible / rupture
if ($produit['stock'] >= 5) {
    $stockLabel  = 'En stock (' . $produit['stock'] . ')';
    $stockColor  = '#4ade80'; // vert
} elseif ($produit['stock'] > 0) {
    $stockLabel  = 'Stock faible — ' . $produit['stock'] . ' restant' . ($produit['stock'] > 1 ? 's' : '');
    $stockColor  = '#fb923c'; // orange
} else {
    $stockLabel  = 'Rupture de stock';
    $stockColor  = '#f87171'; // rouge
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titre dynamique : nom du produit dans l'onglet navigateur -->
    <title><?= htmlspecialchars($produit['nom']) ?> — Monnwear</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts : Space Grotesk -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           THÈME GLOBAL
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

        * { box-sizing: border-box; }

        body {
            background-color: var(--mw-black);
            color: var(--mw-white);
            font-family: 'Space Grotesk', sans-serif;
            margin: 0;
        }

        /* ============================================================
           NAVBAR
        ============================================================ */
        .navbar-mw {
            background-color: var(--mw-dark);
            border-bottom: 1px solid var(--mw-border);
            padding: 1rem 0;
        }
        .navbar-brand-mw {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--mw-white) !important;
            text-decoration: none;
        }
        .navbar-mw .nav-link {
            color: var(--mw-muted) !important;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 500;
            transition: color 0.2s;
        }
        .navbar-mw .nav-link:hover { color: var(--mw-white) !important; }
        .cart-icon {
            font-size: 1.3rem;
            color: var(--mw-muted);
            transition: color 0.2s;
            text-decoration: none;
        }
        .cart-icon:hover { color: var(--mw-white); }
        .navbar-toggler { border-color: var(--mw-border); }
        .navbar-toggler-icon { filter: invert(1); }

        /* ============================================================
           BREADCRUMB (fil d'Ariane)
        ============================================================ */
        .breadcrumb-mw {
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--mw-border);
            background-color: var(--mw-dark);
        }
        .breadcrumb-mw a {
            color: var(--mw-muted);
            text-decoration: none;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: color 0.2s;
        }
        .breadcrumb-mw a:hover { color: var(--mw-white); }
        .breadcrumb-mw span {
            color: var(--mw-muted);
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            margin: 0 0.5rem;
        }
        .breadcrumb-mw .current {
            color: var(--mw-white);
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            /* Tronque le nom si trop long sur mobile */
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: middle;
        }

        /* ============================================================
           SECTION FICHE PRODUIT
        ============================================================ */
        .product-detail {
            padding: 4rem 0 6rem;
        }

        /* --- Colonne image --- */
        .product-image-col {
            position: sticky;
            /* Colle l'image en haut quand on scrolle le panneau droit */
            top: 90px;
        }

        /* Cadre de l'image principale */
        .product-image-frame {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            overflow: hidden;
            aspect-ratio: 3 / 4; /* ratio portrait typique des vêtements */
        }

        /* L'image occupe tout le cadre sans déformation */
        .product-image-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* --- Colonne infos --- */
        .product-info-col {
            padding-left: 2rem;
        }

        /* Étiquette catégorie */
        .product-category-tag {
            display: inline-block;
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--mw-muted);
            border: 1px solid var(--mw-border);
            padding: 4px 12px;
            border-radius: 2px;
            margin-bottom: 1.25rem;
        }

        /* Nom du produit */
        .product-title {
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.1;
            color: var(--mw-white);
            margin-bottom: 1.5rem;
        }

        /* Indicateur de stock */
        .stock-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
            margin-bottom: 1.5rem;
        }
        .stock-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* Séparateur horizontal */
        .divider {
            border: none;
            border-top: 1px solid var(--mw-border);
            margin: 1.75rem 0;
        }

        /* Bloc prix */
        .price-block {
            margin-bottom: 2rem;
        }
        .price-label {
            font-size: 0.7rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin-bottom: 0.35rem;
        }
        .price-value {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--mw-white);
            line-height: 1;
        }

        /* Description produit */
        .description-block {
            margin-bottom: 2.5rem;
        }
        .description-label {
            font-size: 0.7rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin-bottom: 0.75rem;
        }
        .description-text {
            font-size: 0.9rem;
            color: #aaaaaa;
            line-height: 1.75;
        }

        /* Bouton principal — Ajouter au panier */
        .btn-add-to-cart {
            width: 100%;
            background-color: var(--mw-white);
            color: var(--mw-black);
            border: none;
            padding: 1rem 2rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            border-radius: 2px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background-color 0.2s, color 0.2s;
            cursor: pointer;
        }
        .btn-add-to-cart:hover:not(.disabled) {
            background-color: var(--mw-accent);
            color: var(--mw-black);
        }
        .btn-add-to-cart.disabled {
            opacity: 0.35;
            pointer-events: none;
            cursor: not-allowed;
        }

        /* Bouton secondaire — Continuer à shopper */
        .btn-back {
            width: 100%;
            background-color: transparent;
            color: var(--mw-muted);
            border: 1px solid var(--mw-border);
            padding: 0.85rem 2rem;
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border-radius: 2px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: border-color 0.2s, color 0.2s;
            margin-top: 0.75rem;
        }
        .btn-back:hover {
            border-color: var(--mw-white);
            color: var(--mw-white);
        }

        /* Bloc d'informations livraison */
        .info-pills {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }
        .info-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.78rem;
            color: var(--mw-muted);
        }
        .info-pill i {
            font-size: 0.9rem;
            width: 16px;
            text-align: center;
        }

        /* ============================================================
           FOOTER
        ============================================================ */
        .footer-mw {
            background-color: var(--mw-dark);
            border-top: 1px solid var(--mw-border);
            padding: 2rem 0;
        }
        .footer-mw p {
            color: var(--mw-muted);
            font-size: 0.8rem;
            letter-spacing: 0.05em;
            margin: 0;
        }

        /* ============================================================
           RESPONSIVE — ajustements mobile
        ============================================================ */
        @media (max-width: 991px) {
            .product-info-col {
                padding-left: 0;
                margin-top: 2rem;
            }
            /* Sur mobile l'image n'est plus sticky */
            .product-image-col {
                position: static;
            }
        }
    </style>
</head>
<body>

<?php $activeLink = 'shop'; require 'includes/navbar.php'; ?>

<!-- ================================================================
     BREADCRUMB — fil d'Ariane dynamique
================================================================ -->
<div class="breadcrumb-mw">
    <div class="container">
        <div class="d-flex align-items-center flex-wrap" style="gap: 0;">

            <a href="index.php">Accueil</a>
            <span>/</span>
            <a href="shop.php">Shop</a>
            <span>/</span>

            <!--
                Lien vers shop.php filtré par la catégorie du produit.
                On passe l'id_categorie en paramètre GET.
                (int) sur l'id pour sécuriser l'URL.
            -->
            <a href="shop.php?categorie=<?= (int)$produit['id_categorie'] ?>">
                <?= htmlspecialchars($produit['nom_categorie']) ?>
            </a>
            <span>/</span>

            <!-- Nom du produit (non cliquable, on est déjà dessus) -->
            <span class="current"><?= htmlspecialchars($produit['nom']) ?></span>
        </div>
    </div>
</div>

<!-- ================================================================
     FICHE PRODUIT
================================================================ -->
<section class="product-detail">
    <div class="container">
        <div class="row">

            <!-- ====================================================
                 COLONNE GAUCHE : image du produit
            ==================================================== -->
            <div class="col-lg-6 product-image-col">
                <div class="product-image-frame">
                    <!--
                        Image principale grande taille.
                        htmlspecialchars() sur l'URL évite toute injection d'attribut HTML.
                        ?? '' : protège si image_url est NULL en BDD.
                        alt= : obligatoire pour l'accessibilité (lecteurs d'écran).
                    -->
                    <img src="<?= htmlspecialchars($produit['image_url'] ?? '') ?>"
                         alt="<?= htmlspecialchars($produit['nom']) ?>">
                </div>
            </div>

            <!-- ====================================================
                 COLONNE DROITE : informations produit
            ==================================================== -->
            <div class="col-lg-6 product-info-col">

                <!-- Étiquette catégorie -->
                <span class="product-category-tag">
                    <?= htmlspecialchars($produit['nom_categorie']) ?>
                </span>

                <!-- Nom du produit -->
                <h1 class="product-title">
                    <?= htmlspecialchars($produit['nom']) ?>
                </h1>

                <!-- Indicateur de disponibilité dynamique -->
                <div class="stock-indicator">
                    <!--
                        La couleur du point et le texte sont calculés en PHP
                        selon le niveau de stock (voir section 5 en haut du fichier).
                        style="background-color:..." : injection de la valeur PHP dans le CSS inline.
                    -->
                    <span class="stock-dot" style="background-color: <?= $stockColor ?>;"></span>
                    <span style="color: <?= $stockColor ?>;"><?= $stockLabel ?></span>
                </div>

                <hr class="divider">

                <!-- Bloc prix -->
                <div class="price-block">
                    <div class="price-label">Prix</div>
                    <div class="price-value">
                        <!--
                            number_format() : formate le prix avec 2 décimales.
                            Arguments : (valeur, décimales, séparateur_décimal, séparateur_milliers)
                            Ex : 1200.00 → "1 200,00"
                        -->
                        <?= number_format($produit['prix'], 2, ',', ' ') ?>&nbsp;DT
                    </div>
                </div>

                <!-- Description complète du produit -->
                <div class="description-block">
                    <div class="description-label">Description</div>
                    <p class="description-text">
                        <?= htmlspecialchars($produit['description']) ?>
                    </p>
                </div>

                <!-- ================================================
                     MESSAGE DE SUCCÈS — affiché quand ?added=1 dans l'URL
                     (posé par add_to_cart.php après un ajout réussi)
                ================================================ -->
                <?php if ($addedToCart): ?>
                <div style="background:rgba(74,222,128,0.1); border:1px solid rgba(74,222,128,0.3);
                            color:#4ade80; padding:0.85rem 1.1rem; border-radius:2px;
                            font-size:0.82rem; margin-bottom:1.25rem; display:flex;
                            align-items:center; gap:8px;">
                    <i class="bi bi-check2-circle"></i>
                    Produit ajouté au panier !
                    <a href="cart.php" style="color:#4ade80; margin-left:auto;
                       font-weight:600; text-decoration:underline;">
                        Voir le panier →
                    </a>
                </div>
                <?php endif; ?>

                <!-- ================================================
                     BOUTON AJOUTER AU PANIER
                ================================================ -->
                <!--
                    Si le produit est en stock :
                      → lien vers add_to_cart.php avec l'id du produit en paramètre GET.
                      → (int) sur l'id pour sécuriser l'URL.

                    Si rupture de stock :
                      → lien vers '#' (inactif) + classe CSS 'disabled' qui
                        désactive visuellement le bouton via pointer-events:none.

                    La logique d'ajout au panier (sessions PHP) sera implémentée
                    dans la prochaine étape (add_to_cart.php).
                -->
                <a href="<?= $enStock ? 'add_to_cart.php?id=' . (int)$produit['id_produit'] : '#' ?>"
                   class="btn-add-to-cart <?= !$enStock ? 'disabled' : '' ?>"
                   title="<?= $enStock ? 'Ajouter au panier' : 'Produit indisponible' ?>">
                    <i class="bi bi-bag-plus"></i>
                    <?= $enStock ? 'Ajouter au panier' : 'Indisponible' ?>
                </a>

                <!-- Bouton retour boutique -->
                <a href="shop.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i>
                    Continuer mes achats
                </a>

                <!-- Informations livraison / retours -->
                <div class="info-pills">
                    <div class="info-pill">
                        <i class="bi bi-truck"></i>
                        <span>Livraison gratuite dès 200&nbsp;DT d'achat</span>
                    </div>
                    <div class="info-pill">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Retours acceptés sous 14 jours</span>
                    </div>
                    <div class="info-pill">
                        <i class="bi bi-shield-check"></i>
                        <span>Paiement 100% sécurisé</span>
                    </div>
                </div>

            </div><!-- /.product-info-col -->
        </div><!-- /.row -->
    </div><!-- /.container -->
</section>

<!-- ================================================================
     FOOTER
================================================================ -->
<footer class="footer-mw">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <p>© <?= date('Y') ?> Monnwear. Tous droits réservés.</p>
            <p style="letter-spacing:0.2em; font-size:0.7rem;">WEAR THE SILENCE.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
