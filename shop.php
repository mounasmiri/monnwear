<?php
/**
 * shop.php
 * Page boutique de Monnwear.
 *
 * Fonctionnalités :
 *  - Affiche tous les produits, ou filtre par catégorie si un paramètre GET est passé.
 *  - Les catégories sont chargées dynamiquement depuis la BDD.
 *  - Toutes les requêtes utilisent PDO avec des prepared statements.
 */

// ----------------------------------------------------------------
// 1. CONNEXION À LA BASE DE DONNÉES
// ----------------------------------------------------------------
// require_once évite d'inclure le fichier deux fois si jamais
// une autre inclusion l'a déjà chargé.
// session_start() charge la session existante (panier, badge)
session_start();

require_once 'includes/db_connect.php';

// Badge navbar : nombre total d'articles dans le panier
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantite')) : 0;


// ----------------------------------------------------------------
// 2. RÉCUPÉRATION DES CATÉGORIES (pour construire les boutons de filtre)
// ----------------------------------------------------------------
// On récupère TOUTES les catégories pour les afficher comme filtres,
// indépendamment du filtre actif.
$stmtCats = $pdo->prepare('SELECT id_categorie, nom_categorie FROM Categories ORDER BY nom_categorie ASC');
$stmtCats->execute();
$categories = $stmtCats->fetchAll(); // Tableau associatif de toutes les catégories


// ----------------------------------------------------------------
// 3. LECTURE ET VALIDATION DU FILTRE DE CATÉGORIE (paramètre GET)
// ----------------------------------------------------------------
/*
 * L'URL de filtrage ressemble à : shop.php?categorie=2
 * On vérifie d'abord si le paramètre 'categorie' existe dans $_GET.
 * isset() retourne true si la variable existe ET n'est pas null.
 *
 * (int) caste la valeur en entier pour se protéger des injections :
 * si quelqu'un envoie ?categorie=abc, (int)"abc" = 0, donc aucun filtre.
 */
$categorieActive = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;
// $categorieActive = 0 signifie "pas de filtre" (afficher tous les produits)


// ----------------------------------------------------------------
// 4. CONSTRUCTION DE LA REQUÊTE SQL DYNAMIQUE
// ----------------------------------------------------------------
/*
 * Selon si un filtre est actif ou non, la requête SQL change.
 * C'est ici qu'on utilise les prepared statements de façon sécurisée.
 *
 * POURQUOI les prepared statements ?
 * → Sans eux, un utilisateur pourrait injecter du SQL malveillant
 *   dans l'URL (ex: ?categorie=1 OR 1=1).
 * → Avec PDO + bindValue(), la valeur est envoyée séparément du SQL :
 *   MySQL la traite toujours comme une DONNÉE, jamais comme du code SQL.
 */

if ($categorieActive > 0) {
    /*
     * CAS A : un filtre de catégorie est sélectionné.
     * On ajoute une clause WHERE pour filtrer par id_categorie.
     * Le :id_categorie est un "marqueur de position nommé" (placeholder).
     */
    $sql = '
        SELECT p.id_produit, p.nom, p.description, p.prix, p.stock, p.image_url, c.nom_categorie
        FROM Produits p
        INNER JOIN Categories c ON p.id_categorie = c.id_categorie
        WHERE p.id_categorie = :id_categorie
        ORDER BY p.nom ASC
    ';
    $stmt = $pdo->prepare($sql);

    /*
     * bindValue() lie la valeur $categorieActive au placeholder :id_categorie.
     * PDO::PARAM_INT précise que c'est un entier (encore plus sécurisé).
     */
    $stmt->bindValue(':id_categorie', $categorieActive, PDO::PARAM_INT);

} else {
    /*
     * CAS B : aucun filtre, on affiche tous les produits.
     * Pas de clause WHERE, pas de paramètre à binder.
     */
    $sql = '
        SELECT p.id_produit, p.nom, p.description, p.prix, p.stock, p.image_url, c.nom_categorie
        FROM Produits p
        INNER JOIN Categories c ON p.id_categorie = c.id_categorie
        ORDER BY p.nom ASC
    ';
    $stmt = $pdo->prepare($sql);
}

// On exécute la requête (dans les deux cas)
$stmt->execute();

// fetchAll() récupère l'intégralité des lignes résultantes
$produits = $stmt->fetchAll();

// Nombre total de produits trouvés (pour l'affichage)
$nbProduits = count($produits);


// ----------------------------------------------------------------
// 5. TROUVER LE NOM DE LA CATÉGORIE ACTIVE (pour le titre de la page)
// ----------------------------------------------------------------
/*
 * Si un filtre est actif, on cherche le nom de la catégorie dans le
 * tableau $categories déjà chargé — sans refaire une requête BDD.
 */
$nomCategorieActive = 'Tous les produits'; // Valeur par défaut

if ($categorieActive > 0) {
    foreach ($categories as $cat) {
        if ((int)$cat['id_categorie'] === $categorieActive) {
            $nomCategorieActive = $cat['nom_categorie'];
            break; // On a trouvé, inutile de continuer la boucle
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop — Monnwear</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts : Space Grotesk -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           THÈME GLOBAL — reprend exactement les variables de index.php
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
           NAVBAR (identique à index.php)
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
        .navbar-mw .nav-link:hover,
        .navbar-mw .nav-link.active-link { color: var(--mw-white) !important; }
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
           EN-TÊTE DE PAGE (bannière shop)
        ============================================================ */
        .shop-header {
            background-color: var(--mw-dark);
            border-bottom: 1px solid var(--mw-border);
            padding: 3.5rem 0 2.5rem;
        }
        .shop-eyebrow {
            font-size: 0.72rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin-bottom: 0.6rem;
        }
        .shop-title {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--mw-white);
            margin: 0;
        }
        /* Compteur de produits */
        .product-count {
            font-size: 0.8rem;
            color: var(--mw-muted);
            letter-spacing: 0.05em;
        }

        /* ============================================================
           BARRE DE FILTRES PAR CATÉGORIE
        ============================================================ */
        .filter-bar {
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--mw-border);
            background-color: var(--mw-dark);
            position: sticky;
            top: 73px; /* hauteur de la navbar */
            z-index: 100;
        }

        /* Bouton filtre inactif */
        .btn-filter {
            background-color: transparent;
            border: 1px solid var(--mw-border);
            color: var(--mw-muted);
            font-size: 0.78rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.5rem 1.2rem;
            border-radius: 2px;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-filter:hover {
            border-color: #555;
            color: var(--mw-white);
        }

        /* Bouton filtre ACTIF (catégorie sélectionnée) */
        .btn-filter.active {
            background-color: var(--mw-white);
            border-color: var(--mw-white);
            color: var(--mw-black);
            font-weight: 600;
        }

        /* ============================================================
           GRILLE PRODUITS
        ============================================================ */
        .section-products {
            padding: 3rem 0 5rem;
        }

        /* Carte produit (identique à index.php) */
        .product-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.25s, border-color 0.25s;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-4px);
            border-color: #444;
        }

        /* Zone image du produit */
        .product-img-wrap {
            background-color: #161616;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Image réelle — couvre toute la zone sans déformation */
        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }
        .product-card:hover .product-img {
            transform: scale(1.04);
        }

        /* Badge catégorie sur l'image */
        .product-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background-color: rgba(255,255,255,0.07);
            color: var(--mw-muted);
            font-size: 0.65rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 2px;
            border: 1px solid var(--mw-border);
            backdrop-filter: blur(4px);
        }

        /* Indicateur de stock */
        .stock-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        .stock-dot.in-stock  { background-color: #4ade80; }
        .stock-dot.out-stock { background-color: #f87171; }

        .product-body { padding: 1.25rem 1.25rem 1.5rem; }

        .product-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--mw-white);
            margin-bottom: 0.35rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-desc {
            font-size: 0.78rem;
            color: var(--mw-muted);
            line-height: 1.55;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-price {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--mw-white);
        }

        /* Bouton voir le produit */
        .btn-view {
            background-color: transparent;
            border: 1px solid var(--mw-border);
            color: var(--mw-muted);
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.45rem 0.9rem;
            border-radius: 2px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-view:hover {
            border-color: var(--mw-white);
            color: var(--mw-white);
        }

        /* ============================================================
           MESSAGE "AUCUN PRODUIT"
        ============================================================ */
        .empty-state {
            text-align: center;
            padding: 5rem 1rem;
        }
        .empty-icon {
            font-size: 3rem;
            color: var(--mw-border);
            margin-bottom: 1rem;
        }
        .empty-state h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--mw-white);
            margin-bottom: 0.5rem;
        }
        .empty-state p {
            font-size: 0.85rem;
            color: var(--mw-muted);
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
    </style>
</head>
<body>

<?php $activeLink = 'shop'; require 'includes/navbar.php'; ?>

<!-- ================================================================
     EN-TÊTE DE LA PAGE BOUTIQUE
================================================================ -->
<div class="shop-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div>
                <!-- Fil d'Ariane (breadcrumb) minimal -->
                <p class="shop-eyebrow">
                    <a href="index.php" style="color:inherit; text-decoration:none;">Accueil</a>
                    &nbsp;/&nbsp;
                    <?php
                    /*
                     * Si un filtre est actif, on affiche le nom de la catégorie
                     * dans le fil d'Ariane. Sinon, on affiche juste "Shop".
                     * htmlspecialchars() protège contre les attaques XSS en
                     * échappant les caractères spéciaux HTML (<, >, ", etc.).
                     */
                    if ($categorieActive > 0): ?>
                        <a href="shop.php" style="color:inherit; text-decoration:none;">Shop</a>
                        &nbsp;/&nbsp;
                        <?= htmlspecialchars($nomCategorieActive) ?>
                    <?php else: ?>
                        Shop
                    <?php endif; ?>
                </p>

                <!-- Titre dynamique selon le filtre actif -->
                <h1 class="shop-title">
                    <?= htmlspecialchars($nomCategorieActive) ?>
                </h1>
            </div>

            <!-- Compteur de produits -->
            <p class="product-count">
                <?= $nbProduits ?> article<?= $nbProduits > 1 ? 's' : '' ?>
            </p>
        </div>
    </div>
</div>

<!-- ================================================================
     BARRE DE FILTRES PAR CATÉGORIE (sticky sous la navbar)
================================================================ -->
<div class="filter-bar">
    <div class="container">
        <!--
            On utilise un scrollable horizontal sur mobile (overflow-x: auto)
            pour que tous les boutons restent visibles même sur petit écran.
        -->
        <div class="d-flex gap-2" style="overflow-x: auto; padding-bottom: 2px;">

            <!--
                Bouton "Tous" — actif si $categorieActive === 0.
                Le lien pointe vers shop.php sans paramètre GET,
                ce qui réinitialise le filtre.
            -->
            <a href="shop.php"
               class="btn-filter <?= $categorieActive === 0 ? 'active' : '' ?>">
                Tous
            </a>

            <?php
            /*
             * Boucle sur les catégories récupérées depuis la BDD.
             * Pour chaque catégorie, on génère un bouton de filtre.
             * Le lien passe l'id de la catégorie en paramètre GET.
             */
            foreach ($categories as $cat):
                /*
                 * On détermine si ce bouton est "actif" (= catégorie filtrée).
                 * (int) pour comparer deux entiers de manière stricte.
                 */
                $isActive = (int)$cat['id_categorie'] === $categorieActive;
            ?>
            <a href="shop.php?categorie=<?= (int)$cat['id_categorie'] ?>"
               class="btn-filter <?= $isActive ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['nom_categorie']) ?>
            </a>
            <?php endforeach; ?>

        </div>
    </div>
</div>

<!-- ================================================================
     GRILLE DE PRODUITS
================================================================ -->
<section class="section-products">
    <div class="container">

        <?php if ($nbProduits === 0): ?>
        <!--
            CAS VIDE : aucun produit ne correspond au filtre.
            Ce cas peut arriver si une catégorie existe mais n'a pas de produits.
        -->
        <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-bag-x"></i></div>
            <h3>Aucun produit trouvé</h3>
            <p>Cette catégorie ne contient pas encore de produits.</p>
            <a href="shop.php" class="btn-filter active mt-3 d-inline-block">
                Voir tous les produits
            </a>
        </div>

        <?php else: ?>
        <!--
            CAS NORMAL : on affiche les produits dans une grille responsive.
            row-cols-* définit le nombre de colonnes par breakpoint Bootstrap.
        -->
        <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4">

            <?php
            /*
             * Boucle principale d'affichage des produits.
             * Pour chaque produit retourné par la requête SQL :
             *   - on génère les initiales (placeholder visuel)
             *   - on détermine si le produit est en stock
             *   - on construit la carte HTML
             */
            foreach ($produits as $produit):

                // Booléen de disponibilité
                $enStock = $produit['stock'] > 0;
            ?>

            <div class="col">
                <div class="product-card">

                    <!-- Zone image / placeholder cliquable -->
                    <a href="product.php?id=<?= (int)$produit['id_produit'] ?>"
                       style="text-decoration:none; display:block;">
                        <div class="product-img-wrap">
                            <!-- Badge catégorie -->
                            <span class="product-badge">
                                <?= htmlspecialchars($produit['nom_categorie']) ?>
                            </span>
                            <!--
                                Image réelle du produit (URL stockée en BDD).
                                htmlspecialchars() sur l'URL protège contre l'injection d'attributs HTML.
                                ?? '' : retourne une chaîne vide si image_url est NULL en BDD.
                                loading="lazy" : chargement différé pour meilleures performances.
                            -->
                            <img src="<?= htmlspecialchars($produit['image_url'] ?? '') ?>"
                                 alt="<?= htmlspecialchars($produit['nom']) ?>"
                                 class="product-img"
                                 loading="lazy">
                        </div>
                    </a>

                    <!-- Corps de la carte -->
                    <div class="product-body d-flex flex-column" style="height: calc(100% - 300px);">

                        <!-- Indicateur de disponibilité -->
                        <div class="mb-2" style="font-size:0.72rem; color:var(--mw-muted);">
                            <span class="stock-dot <?= $enStock ? 'in-stock' : 'out-stock' ?>"></span>
                            <?php if ($enStock): ?>
                                En stock
                                <!-- Alerte si stock faible (moins de 5 unités) -->
                                <?php if ($produit['stock'] < 5): ?>
                                    <span style="color:#fb923c;">(<?= $produit['stock'] ?> restant<?= $produit['stock'] > 1 ? 's' : '' ?>)</span>
                                <?php else: ?>
                                    (<?= $produit['stock'] ?>)
                                <?php endif; ?>
                            <?php else: ?>
                                Rupture de stock
                            <?php endif; ?>
                        </div>

                        <!-- Nom du produit -->
                        <div class="product-name">
                            <?= htmlspecialchars($produit['nom']) ?>
                        </div>

                        <!-- Description tronquée -->
                        <div class="product-desc">
                            <?= htmlspecialchars($produit['description']) ?>
                        </div>

                        <!-- Pied de carte : prix + boutons -->
                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2"
                             style="border-top: 1px solid var(--mw-border);">

                            <!-- Prix formaté -->
                            <span class="product-price">
                                <?= number_format($produit['prix'], 2, ',', ' ') ?>&nbsp;DT
                            </span>

                            <!-- Boutons d'action -->
                            <div class="d-flex gap-2">
                                <!--
                                    Bouton "Voir" → fiche produit (product.php, Step 3)
                                    (int) sur l'id évite toute injection dans l'URL.
                                -->
                                <a href="product.php?id=<?= (int)$produit['id_produit'] ?>"
                                   class="btn-view" title="Voir le produit">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <!--
                                    Bouton "Ajouter au panier" → add_to_cart.php (Step suivant)
                                    Désactivé visuellement si rupture de stock.
                                -->
                                <a href="<?= $enStock ? 'add_to_cart.php?id=' . (int)$produit['id_produit'] : '#' ?>"
                                   class="btn-view <?= !$enStock ? 'disabled' : '' ?>"
                                   title="<?= $enStock ? 'Ajouter au panier' : 'Rupture de stock' ?>"
                                   style="<?= !$enStock ? 'opacity:0.35; pointer-events:none;' : '' ?>">
                                    <i class="bi bi-bag-plus"></i>
                                </a>
                            </div>

                        </div>
                    </div><!-- /.product-body -->
                </div><!-- /.product-card -->
            </div><!-- /.col -->

            <?php endforeach; ?>

        </div><!-- /.row -->
        <?php endif; ?>

    </div><!-- /.container -->
</section>

<!-- ================================================================
     FOOTER
================================================================ -->
<footer class="footer-mw">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <p>© <?= date('Y') ?> Monnwear. Tous droits réservés.</p>
            <div style="display:flex;gap:1.5rem;">
                <a href="track_order.php" style="color:var(--mw-muted);font-size:0.78rem;text-decoration:none;" onmouseover="this.style.color='#f5f5f5'" onmouseout="this.style.color='#888'">Suivi commande</a>
                <a href="contact.php"     style="color:var(--mw-muted);font-size:0.78rem;text-decoration:none;" onmouseover="this.style.color='#f5f5f5'" onmouseout="this.style.color='#888'">Contact</a>
            </div>
            <p style="letter-spacing:0.2em; font-size:0.7rem;">WEAR THE SILENCE.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
