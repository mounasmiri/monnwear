<?php
/**
 * collections.php
 * Page des collections — présente chaque catégorie avec ses produits.
 *
 * Fonctionnement :
 *  - Récupère toutes les catégories avec le nombre de produits associés.
 *  - Pour chaque catégorie, affiche une carte visuelle cliquable
 *    qui renvoie vers shop.php?categorie=X (filtre déjà implémenté).
 *  - Affiche également les 3 derniers produits ajoutés en "Nouveautés".
 */

session_start();
require_once 'includes/db_connect.php';

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantite')) : 0;


// ----------------------------------------------------------------
// 1. CATÉGORIES AVEC COMPTEUR ET PREMIÈRE IMAGE
// ----------------------------------------------------------------
/*
 * Pour chaque catégorie, on récupère :
 *  - Le nombre de produits (COUNT)
 *  - L'image_url du premier produit (pour la vignette de catégorie)
 *    via une sous-requête scalaire (SELECT ... LIMIT 1).
 */
$stmtCats = $pdo->prepare("
    SELECT
        c.id_categorie,
        c.nom_categorie,
        COUNT(p.id_produit) AS nb_produits,
        (
            SELECT p2.image_url
            FROM Produits p2
            WHERE p2.id_categorie = c.id_categorie
              AND p2.image_url IS NOT NULL
            ORDER BY p2.id_produit ASC
            LIMIT 1
        ) AS image_url
    FROM Categories c
    LEFT JOIN Produits p ON c.id_categorie = p.id_categorie
    GROUP BY c.id_categorie, c.nom_categorie
    ORDER BY nb_produits DESC
");
$stmtCats->execute();
$categories = $stmtCats->fetchAll();


// ----------------------------------------------------------------
// 2. NOUVEAUTÉS (3 derniers produits par id)
// ----------------------------------------------------------------
$stmtNew = $pdo->prepare('
    SELECT p.id_produit, p.nom, p.prix, p.image_url, c.nom_categorie
    FROM Produits p
    INNER JOIN Categories c ON p.id_categorie = c.id_categorie
    ORDER BY p.id_produit DESC
    LIMIT 3
');
$stmtNew->execute();
$nouveautes = $stmtNew->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collections — Monnwear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --mw-black:#0a0a0a; --mw-dark:#111111; --mw-card:#1a1a1a;
            --mw-border:#2a2a2a; --mw-white:#f5f5f5; --mw-muted:#888888;
        }
        * { box-sizing:border-box; }
        body { background-color:var(--mw-black); color:var(--mw-white); font-family:'Space Grotesk',sans-serif; margin:0; }

        /* NAVBAR */
        .navbar-mw { background-color:var(--mw-dark); border-bottom:1px solid var(--mw-border); padding:1rem 0; }
        .navbar-brand-mw { font-size:1.5rem; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:var(--mw-white) !important; text-decoration:none; }
        .navbar-mw .nav-link { color:var(--mw-muted) !important; font-size:0.85rem; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; transition:color 0.2s; }
        .navbar-mw .nav-link:hover, .navbar-mw .nav-link.active-link { color:var(--mw-white) !important; }
        .cart-icon { font-size:1.3rem; color:var(--mw-muted); transition:color 0.2s; text-decoration:none; }
        .cart-icon:hover { color:var(--mw-white); }
        .navbar-toggler { border-color:var(--mw-border); }
        .navbar-toggler-icon { filter:invert(1); }

        /* EN-TÊTE PAGE */
        .page-header {
            background-color: var(--mw-dark);
            border-bottom: 1px solid var(--mw-border);
            padding: 3.5rem 0 2.5rem;
        }
        .eyebrow { font-size:0.72rem; letter-spacing:0.25em; text-transform:uppercase; color:var(--mw-muted); margin-bottom:0.6rem; }
        .page-title { font-size:2.5rem; font-weight:700; letter-spacing:-0.02em; margin:0; }

        /* SECTION */
        .section { padding:5rem 0; }
        .section-label { font-size:0.72rem; letter-spacing:0.25em; text-transform:uppercase; color:var(--mw-muted); margin-bottom:0.5rem; }
        .section-title { font-size:2rem; font-weight:700; letter-spacing:-0.01em; margin-bottom:3rem; }

        /* CARTE CATÉGORIE */
        .cat-card {
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            aspect-ratio: 3/4;
            display: block;
            text-decoration: none;
            transition: transform 0.3s, border-color 0.3s;
        }
        .cat-card:hover { transform: translateY(-5px); border-color: #444; }

        /* Image de fond de la carte catégorie */
        .cat-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }
        .cat-card:hover .cat-card-img { transform: scale(1.06); }

        /* Overlay sombre au bas de la carte */
        .cat-card-overlay {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            padding: 2rem 1.5rem 1.5rem;
            background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 100%);
        }
        .cat-card-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--mw-white);
            letter-spacing: 0.02em;
            margin-bottom: 0.25rem;
        }
        .cat-card-count {
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(245,245,245,0.6);
        }
        .cat-card-arrow {
            position: absolute;
            top: 1rem; right: 1rem;
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem;
            color: var(--mw-white);
            transition: background 0.2s;
        }
        .cat-card:hover .cat-card-arrow { background: rgba(255,255,255,0.18); }

        /* Carte "Voir tout" */
        .cat-card-all {
            background: linear-gradient(135deg, #1c1c1c 0%, #111 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 1rem;
        }
        .cat-card-all-icon { font-size:2.5rem; color:var(--mw-border); }
        .cat-card-all-text { font-size:0.85rem; font-weight:600; color:var(--mw-muted); letter-spacing:0.1em; text-transform:uppercase; }
        .cat-card:hover .cat-card-all-icon { color:#555; }
        .cat-card:hover .cat-card-all-text { color:var(--mw-white); }

        /* CARTE NOUVEAUTÉ */
        .new-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            overflow: hidden;
            text-decoration: none;
            display: block;
            transition: transform 0.25s, border-color 0.25s;
        }
        .new-card:hover { transform: translateY(-4px); border-color: #444; }
        .new-card-img { width:100%; height:240px; object-fit:cover; display:block; transition:transform 0.4s; }
        .new-card:hover .new-card-img { transform:scale(1.04); }
        .new-card-body { padding:1.1rem 1.25rem 1.4rem; }
        .new-card-cat { font-size:0.65rem; letter-spacing:0.15em; text-transform:uppercase; color:var(--mw-muted); margin-bottom:0.3rem; }
        .new-card-name { font-size:0.92rem; font-weight:600; color:var(--mw-white); margin-bottom:0.5rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .new-card-price { font-size:1rem; font-weight:700; color:var(--mw-white); }

        /* FOOTER */
        .footer-mw { background-color:var(--mw-dark); border-top:1px solid var(--mw-border); padding:2rem 0; }
        .footer-mw p { color:var(--mw-muted); font-size:0.8rem; letter-spacing:0.05em; margin:0; }
    </style>
</head>
<body>

<?php $activeLink = 'collections'; require 'includes/navbar.php'; ?>

<!-- EN-TÊTE -->
<div class="page-header">
    <div class="container">
        <p class="eyebrow">Monnwear — SS'26</p>
        <h1 class="page-title">Nos Collections</h1>
    </div>
</div>

<!-- ================================================================
     GRILLE DES CATÉGORIES
================================================================ -->
<section class="section">
    <div class="container">

        <p class="section-label">Explorer par univers</p>
        <h2 class="section-title">Toutes les catégories</h2>

        <div class="row g-3">

            <?php
            /*
             * Boucle sur les catégories récupérées en BDD.
             * Chaque carte renvoie vers shop.php avec le filtre de catégorie.
             */
            foreach ($categories as $cat):
                $hasImage = !empty($cat['image_url']);
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="shop.php?categorie=<?= (int)$cat['id_categorie'] ?>" class="cat-card">

                    <!-- Image de fond : vraie image du produit ou fond sombre -->
                    <?php if ($hasImage): ?>
                        <img src="<?= htmlspecialchars($cat['image_url']) ?>"
                             alt="<?= htmlspecialchars($cat['nom_categorie']) ?>"
                             class="cat-card-img">
                    <?php else: ?>
                        <!-- Fond sombre avec initiale si pas d'image disponible -->
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#161616;">
                            <span style="font-size:4rem;font-weight:700;color:var(--mw-border);">
                                <?= strtoupper($cat['nom_categorie'][0]) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Icône flèche -->
                    <div class="cat-card-arrow"><i class="bi bi-arrow-right"></i></div>

                    <!-- Overlay avec nom + compteur -->
                    <div class="cat-card-overlay">
                        <div class="cat-card-name"><?= htmlspecialchars($cat['nom_categorie']) ?></div>
                        <div class="cat-card-count">
                            <?= $cat['nb_produits'] ?> article<?= $cat['nb_produits'] > 1 ? 's' : '' ?>
                        </div>
                    </div>

                </a>
            </div>
            <?php endforeach; ?>

            <!-- Carte "Voir tout le catalogue" -->
            <div class="col-6 col-md-4 col-lg-3">
                <a href="shop.php" class="cat-card cat-card-all">
                    <i class="bi bi-grid cat-card-all-icon"></i>
                    <span class="cat-card-all-text">Voir tout</span>
                </a>
            </div>

        </div><!-- /.row -->
    </div>
</section>

<!-- ================================================================
     NOUVEAUTÉS
================================================================ -->
<?php if (!empty($nouveautes)): ?>
<section class="section" style="padding-top:0; border-top:1px solid var(--mw-border);">
    <div class="container" style="padding-top:5rem;">
        <p class="section-label">Dernières arrivées</p>
        <h2 class="section-title">Nouveautés</h2>

        <div class="row g-4">
            <?php foreach ($nouveautes as $prod): ?>
            <div class="col-md-4">
                <a href="product.php?id=<?= (int)$prod['id_produit'] ?>" class="new-card">
                    <img src="<?= htmlspecialchars($prod['image_url'] ?? '') ?>"
                         alt="<?= htmlspecialchars($prod['nom']) ?>"
                         class="new-card-img">
                    <div class="new-card-body">
                        <div class="new-card-cat"><?= htmlspecialchars($prod['nom_categorie']) ?></div>
                        <div class="new-card-name"><?= htmlspecialchars($prod['nom']) ?></div>
                        <div class="new-card-price"><?= number_format($prod['prix'], 2, ',', ' ') ?>&nbsp;DT</div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

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
