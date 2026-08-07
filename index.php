<?php
/**
 * index.php
 * Page d'accueil de la boutique Monnwear.
 * Affiche le hero, la présentation de la marque et les 6 produits phares.
 */

// session_start() charge la session existante (pour lire le panier et afficher le badge)
session_start();

// Inclusion du fichier de connexion PDO
require_once 'includes/db_connect.php';

// Nombre total d'articles dans le panier (pour le badge navbar)
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantite')) : 0;

// --- Récupération des produits depuis la base de données ---
// On utilise un prepared statement (bonne pratique, même sans paramètres variables)
$stmt = $pdo->prepare('
    SELECT p.id_produit, p.nom, p.description, p.prix, p.stock, p.image_url, c.nom_categorie
    FROM Produits p
    INNER JOIN Categories c ON p.id_categorie = c.id_categorie
    ORDER BY p.id_produit ASC
    LIMIT 6
');
$stmt->execute();

// fetchAll() récupère toutes les lignes d'un coup sous forme de tableau
$produits = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monnwear — Streetwear Minimaliste</title>

    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons via CDN (pour l'icône du panier, etc.) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Fonts : Space Grotesk pour un look moderne/urbain -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           THÈME GLOBAL — palette sombre et minimaliste
        ============================================================ */
        :root {
            --mw-black:   #0a0a0a;
            --mw-dark:    #111111;
            --mw-card:    #1a1a1a;
            --mw-border:  #2a2a2a;
            --mw-white:   #f5f5f5;
            --mw-muted:   #888888;
            --mw-accent:  #e8e8e8;
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

        /* Logo texte de la marque */
        .navbar-brand-mw {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--mw-white) !important;
            text-decoration: none;
        }

        /* Liens de navigation */
        .navbar-mw .nav-link {
            color: var(--mw-muted) !important;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 500;
            transition: color 0.2s;
        }
        .navbar-mw .nav-link:hover {
            color: var(--mw-white) !important;
        }

        /* Icône panier avec badge */
        .cart-icon {
            font-size: 1.3rem;
            color: var(--mw-muted);
            transition: color 0.2s;
            text-decoration: none;
        }
        .cart-icon:hover { color: var(--mw-white); }

        /* ============================================================
           HERO SECTION
        ============================================================ */
        .hero-section {
            /* Dégradé sombre en guise de fond (image placeholder) */
            background: linear-gradient(135deg, #111111 0%, #1c1c1c 50%, #0f0f0f 100%);
            min-height: 88vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        /* Effet graphique décoratif en arrière-plan */
        .hero-section::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, transparent 70%);
            top: -100px;
            right: -100px;
            pointer-events: none;
        }

        .hero-eyebrow {
            font-size: 0.75rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin-bottom: 1.25rem;
        }

        .hero-title {
            font-size: clamp(2.8rem, 6vw, 5.5rem);
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: -0.02em;
            color: var(--mw-white);
        }

        .hero-title span {
            /* Mot accentué en style "outline" */
            -webkit-text-stroke: 1px var(--mw-white);
            color: transparent;
        }

        .hero-sub {
            font-size: 1rem;
            color: var(--mw-muted);
            max-width: 420px;
            line-height: 1.7;
            margin-top: 1.25rem;
        }

        /* Boutons du hero */
        .btn-mw-primary {
            background-color: var(--mw-white);
            color: var(--mw-black);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border-radius: 2px;
            transition: background-color 0.2s, color 0.2s;
            text-decoration: none;
        }
        .btn-mw-primary:hover {
            background-color: var(--mw-accent);
            color: var(--mw-black);
        }

        .btn-mw-outline {
            background-color: transparent;
            color: var(--mw-white);
            border: 1px solid var(--mw-border);
            padding: 0.75rem 2rem;
            font-weight: 500;
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border-radius: 2px;
            transition: border-color 0.2s, color 0.2s;
            text-decoration: none;
        }
        .btn-mw-outline:hover {
            border-color: var(--mw-white);
            color: var(--mw-white);
        }

        /* Statistiques marque dans le hero */
        .hero-stats {
            margin-top: 3.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--mw-border);
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--mw-white);
        }
        .stat-label {
            font-size: 0.72rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--mw-muted);
        }

        /* ============================================================
           SECTION PRODUITS
        ============================================================ */
        .section-products {
            padding: 5rem 0;
        }

        .section-label {
            font-size: 0.72rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--mw-muted);
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: var(--mw-white);
        }

        /* Carte produit */
        .product-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.25s, border-color 0.25s;
        }
        .product-card:hover {
            transform: translateY(-4px);
            border-color: #444444;
        }

        /* Zone image du produit */
        .product-img-wrap {
            background-color: #161616;
            height: 280px;
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

        /* Badge catégorie superposé sur l'image */
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

        /* Badge "En stock" / "Rupture" */
        .stock-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        .stock-dot.in-stock  { background-color: #4ade80; } /* vert  */
        .stock-dot.out-stock { background-color: #f87171; } /* rouge */

        .product-body {
            padding: 1.25rem 1.25rem 1.5rem;
        }

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
            /* Limite la description à 2 lignes */
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

        /* Bouton ajout au panier dans la carte */
        .btn-add-cart {
            background-color: transparent;
            border: 1px solid var(--mw-border);
            color: var(--mw-muted);
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.45rem 0.9rem;
            border-radius: 2px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-add-cart:hover {
            border-color: var(--mw-white);
            color: var(--mw-white);
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
           RESPONSIVE — toggler navbar sombre
        ============================================================ */
        .navbar-toggler {
            border-color: var(--mw-border);
        }
        .navbar-toggler-icon {
            filter: invert(1);
        }
    </style>
</head>
<body>

<?php $activeLink = 'home'; require 'includes/navbar.php'; ?>

<!-- ================================================================
     HERO SECTION
================================================================ -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">

                <!-- Sous-titre accrocheur -->
                <p class="hero-eyebrow">Collection Printemps — 2026</p>

                <!-- Titre principal avec un mot en style "outline" -->
                <h1 class="hero-title">
                    Wear The<br>
                    <span>Silence</span><br>
                    Own The&nbsp;Street.
                </h1>

                <!-- Description courte de la marque -->
                <p class="hero-sub">
                    Monnwear redéfinit le streetwear minimaliste. Des pièces conçues pour durer,
                    pensées pour ceux qui n'ont pas besoin de parler fort.
                </p>

                <!-- Boutons d'action -->
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="shop.php" class="btn-mw-primary">Shop Now</a>
                    <a href="about.php" class="btn-mw-outline">Our Story</a>
                </div>

                <!-- Statistiques marque -->
                <div class="hero-stats row g-4">
                    <div class="col-auto">
                        <div class="stat-number">6</div>
                        <div class="stat-label">Pièces Phares</div>
                    </div>
                    <div class="col-auto">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Coton Premium</div>
                    </div>
                    <div class="col-auto">
                        <div class="stat-number">SS'26</div>
                        <div class="stat-label">Collection Actuelle</div>
                    </div>
                </div>

            </div>
            <!-- Colonne droite : visuel décoratif (peut être remplacé par une image) -->
            <div class="col-lg-5 d-none d-lg-flex justify-content-end align-items-center">
                <div style="width:380px; height:500px; border:1px solid #2a2a2a; border-radius:4px;
                            display:flex; align-items:center; justify-content:center; flex-direction:column;
                            background:linear-gradient(180deg,#161616 0%,#0f0f0f 100%);">
                    <span style="font-size:5rem; font-weight:700; color:#2a2a2a; letter-spacing:-0.05em;">MW</span>
                    <span style="font-size:0.65rem; letter-spacing:0.3em; color:#444; text-transform:uppercase; margin-top:0.5rem;">
                        Monnwear&nbsp;2026
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================
     SECTION PRODUITS
================================================================ -->
<section class="section-products">
    <div class="container">

        <!-- En-tête de section -->
        <div class="row mb-5">
            <div class="col-12">
                <p class="section-label mb-2">Notre Catalogue</p>
                <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
                    <h2 class="section-title mb-0">Pièces Essentielles</h2>
                    <a href="shop.php" class="btn-mw-outline" style="white-space:nowrap;">
                        Voir tout <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Grille de produits -->
        <div class="row g-4">

            <?php
            /*
             * Boucle PHP : pour chaque produit récupéré depuis la BDD,
             * on génère dynamiquement une carte Bootstrap.
             */
            foreach ($produits as $produit):

                // Détermine si le produit est en stock
                $enStock = $produit['stock'] > 0;
            ?>

            <!-- Colonne responsive : 1 col sur xs, 2 sur sm, 3 sur lg -->
            <div class="col-sm-6 col-lg-4">
                <div class="product-card h-100">

                    <!-- Zone image / placeholder -->
                    <div class="product-img-wrap">
                        <!-- Badge catégorie -->
                        <span class="product-badge">
                            <?= htmlspecialchars($produit['nom_categorie']) ?>
                        </span>

                        <!--
                            Image réelle du produit (URL stockée en BDD).
                            htmlspecialchars() sur l'URL protège contre l'injection d'attributs HTML.
                            ?? '' : retourne une chaîne vide si image_url est NULL en BDD.
                            loading="lazy" : le navigateur charge l'image seulement quand
                            elle devient visible → meilleure performance de page.
                        -->
                        <img src="<?= htmlspecialchars($produit['image_url'] ?? '') ?>"
                             alt="<?= htmlspecialchars($produit['nom']) ?>"
                             class="product-img"
                             loading="lazy">
                    </div>

                    <!-- Corps de la carte -->
                    <div class="product-body">

                        <!-- Indicateur de stock -->
                        <div class="mb-2" style="font-size:0.72rem; color:var(--mw-muted); letter-spacing:0.05em;">
                            <span class="stock-dot <?= $enStock ? 'in-stock' : 'out-stock' ?>"></span>
                            <?= $enStock ? 'En stock (' . $produit['stock'] . ')' : 'Rupture de stock' ?>
                        </div>

                        <!-- Nom du produit (échappé pour sécurité XSS) -->
                        <div class="product-name">
                            <?= htmlspecialchars($produit['nom']) ?>
                        </div>

                        <!-- Description courte -->
                        <div class="product-desc">
                            <?= htmlspecialchars($produit['description']) ?>
                        </div>

                        <!-- Prix et bouton d'action -->
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <span class="product-price">
                                <!-- number_format formate le prix : 2 décimales, virgule comme séparateur -->
                                <?= number_format($produit['prix'], 2, ',', ' ') ?>&nbsp;DT
                            </span>
                            <!--
                                Le bouton "Ajouter" pointe vers add_to_cart.php
                                avec l'id du produit en paramètre GET.
                                La logique du panier sera implémentée dans une prochaine étape.
                            -->
                            <a href="add_to_cart.php?id=<?= (int)$produit['id_produit'] ?>"
                               class="btn-add-cart <?= !$enStock ? 'disabled' : '' ?>"
                               title="Ajouter au panier">
                                <i class="bi bi-bag-plus me-1"></i> Add
                            </a>
                        </div>

                    </div><!-- /.product-body -->
                </div><!-- /.product-card -->
            </div><!-- /.col -->

            <?php endforeach; ?>

        </div><!-- /.row (grille produits) -->
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
                <a href="track_order.php" style="color:var(--mw-muted);font-size:0.78rem;text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#f5f5f5'" onmouseout="this.style.color='#888'">Suivi commande</a>
                <a href="contact.php"     style="color:var(--mw-muted);font-size:0.78rem;text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#f5f5f5'" onmouseout="this.style.color='#888'">Contact</a>
                <a href="about.php"       style="color:var(--mw-muted);font-size:0.78rem;text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#f5f5f5'" onmouseout="this.style.color='#888'">About</a>
            </div>
            <p style="letter-spacing:0.2em; font-size:0.7rem;">WEAR THE SILENCE.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle (inclut Popper.js) via CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
