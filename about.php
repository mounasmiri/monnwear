<?php
/**
 * about.php
 * Page "Our Story" — présentation de la marque Monnwear.
 * Page principalement statique, sans requête BDD.
 */
session_start();
require_once 'includes/db_connect.php';
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantite')) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Story — Monnwear</title>
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

        /* HERO */
        .about-hero {
            min-height: 60vh;
            background: linear-gradient(160deg, #111111 0%, #1a1a1a 60%, #0f0f0f 100%);
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--mw-border);
            position: relative;
            overflow: hidden;
        }
        .about-hero::after {
            content: 'MW';
            position: absolute;
            right: -2rem;
            bottom: -3rem;
            font-size: 18rem;
            font-weight: 700;
            color: rgba(255,255,255,0.02);
            letter-spacing: -0.05em;
            pointer-events: none;
            line-height: 1;
        }
        .eyebrow { font-size:0.72rem; letter-spacing:0.25em; text-transform:uppercase; color:var(--mw-muted); margin-bottom:1rem; }
        .hero-title { font-size:clamp(2.5rem,5vw,5rem); font-weight:700; letter-spacing:-0.03em; line-height:1.05; margin-bottom:1.5rem; }
        .hero-title span { -webkit-text-stroke:1px var(--mw-white); color:transparent; }
        .hero-lead { font-size:1rem; color:var(--mw-muted); max-width:500px; line-height:1.75; }

        /* DIVIDER */
        .section-divider { border:none; border-top:1px solid var(--mw-border); margin:0; }

        /* VALEURS */
        .values-section { padding:6rem 0; }
        .value-card {
            border-left: 2px solid var(--mw-border);
            padding: 0 0 0 1.5rem;
            transition: border-color 0.3s;
        }
        .value-card:hover { border-color: var(--mw-white); }
        .value-number { font-size:0.65rem; letter-spacing:0.3em; text-transform:uppercase; color:var(--mw-muted); margin-bottom:0.75rem; }
        .value-title { font-size:1.15rem; font-weight:700; margin-bottom:0.6rem; }
        .value-text { font-size:0.85rem; color:var(--mw-muted); line-height:1.7; }

        /* SECTION MANIFESTE */
        .manifesto-section {
            background-color: var(--mw-dark);
            border-top: 1px solid var(--mw-border);
            border-bottom: 1px solid var(--mw-border);
            padding: 6rem 0;
            text-align: center;
        }
        .manifesto-quote {
            font-size: clamp(1.4rem, 3vw, 2.5rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.3;
            max-width: 800px;
            margin: 0 auto;
        }
        .manifesto-quote span { -webkit-text-stroke:1px var(--mw-white); color:transparent; }
        .manifesto-author { font-size:0.72rem; letter-spacing:0.2em; text-transform:uppercase; color:var(--mw-muted); margin-top:2rem; }

        /* TIMELINE */
        .timeline-section { padding:6rem 0; }
        .timeline-item { display:flex; gap:2rem; margin-bottom:3rem; }
        .timeline-year { font-size:0.72rem; letter-spacing:0.2em; text-transform:uppercase; color:var(--mw-muted); min-width:60px; padding-top:3px; }
        .timeline-content { border-left:1px solid var(--mw-border); padding-left:1.5rem; flex:1; }
        .timeline-title { font-size:1rem; font-weight:700; margin-bottom:0.35rem; }
        .timeline-text { font-size:0.84rem; color:var(--mw-muted); line-height:1.65; }

        /* STATS */
        .stats-section {
            background-color: var(--mw-dark);
            border-top: 1px solid var(--mw-border);
            border-bottom: 1px solid var(--mw-border);
            padding: 4rem 0;
        }
        .stat-item { text-align:center; }
        .stat-num { font-size:2.5rem; font-weight:700; letter-spacing:-0.03em; }
        .stat-lbl { font-size:0.7rem; letter-spacing:0.15em; text-transform:uppercase; color:var(--mw-muted); margin-top:0.3rem; }

        /* CTA */
        .cta-section { padding:6rem 0; text-align:center; }
        .cta-title { font-size:2rem; font-weight:700; letter-spacing:-0.02em; margin-bottom:1rem; }
        .cta-sub { color:var(--mw-muted); font-size:0.9rem; margin-bottom:2.5rem; }
        .btn-mw-primary { background-color:var(--mw-white); color:var(--mw-black); border:none; padding:0.9rem 2.5rem; font-size:0.85rem; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; border-radius:2px; text-decoration:none; transition:background-color 0.2s; display:inline-block; }
        .btn-mw-primary:hover { background-color:#e0e0e0; color:var(--mw-black); }

        /* FOOTER */
        .footer-mw { background-color:var(--mw-dark); border-top:1px solid var(--mw-border); padding:2rem 0; }
        .footer-mw p { color:var(--mw-muted); font-size:0.8rem; letter-spacing:0.05em; margin:0; }
    </style>
</head>
<body>

<?php $activeLink = 'about'; require 'includes/navbar.php'; ?>

<!-- HERO -->
<section class="about-hero">
    <div class="container">
        <p class="eyebrow">Notre histoire</p>
        <h1 class="hero-title">
            Nés dans<br>
            l'<span>Ombre</span>,<br>
            Portés au soleil.
        </h1>
        <p class="hero-lead">
            Monnwear est né d'une conviction simple : les vêtements les plus forts
            sont ceux qui ne cherchent pas à impressionner. Juste à durer.
            Juste à appartenir à ceux qui les portent.
        </p>
    </div>
</section>

<hr class="section-divider">

<!-- VALEURS -->
<section class="values-section">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12">
                <p class="eyebrow">Ce en quoi nous croyons</p>
                <h2 style="font-size:1.8rem;font-weight:700;letter-spacing:-0.02em;">Nos valeurs fondatrices</h2>
            </div>
        </div>
        <div class="row g-5">
            <div class="col-md-6 col-lg-3">
                <div class="value-card">
                    <div class="value-number">01</div>
                    <div class="value-title">Le silence comme esthétique</div>
                    <p class="value-text">
                        Pas de logos criards. Pas de tendances éphémères.
                        Chaque pièce Monnwear parle par sa coupe, sa matière et sa sobriété.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="value-card">
                    <div class="value-number">02</div>
                    <div class="value-title">La qualité sans compromis</div>
                    <p class="value-text">
                        Coton 100% premium, coutures renforcées, finitions soignées.
                        On préfère produire moins pour produire mieux.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="value-card">
                    <div class="value-number">03</div>
                    <div class="value-title">L'identité avant la mode</div>
                    <p class="value-text">
                        Monnwear s'adresse à ceux qui construisent leur style
                        de l'intérieur, pas en suivant ce que portent les autres.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="value-card">
                    <div class="value-number">04</div>
                    <div class="value-title">La durabilité réelle</div>
                    <p class="value-text">
                        Des pièces conçues pour traverser les saisons, pas pour
                        finir dans un tiroir au bout de trois mois.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MANIFESTE -->
<section class="manifesto-section">
    <div class="container">
        <p class="eyebrow" style="margin-bottom:2rem;">Notre manifeste</p>
        <p class="manifesto-quote">
            "Porter Monnwear, c'est choisir de ne pas<br>
            avoir à <span>choisir</span> chaque matin.<br>
            C'est avoir confiance en sa garde-robe."
        </p>
        <p class="manifesto-author">— Fondateur, Monnwear 2024</p>
    </div>
</section>

<!-- TIMELINE -->
<section class="timeline-section">
    <div class="container" style="max-width:700px;">
        <p class="eyebrow mb-2">Notre parcours</p>
        <h2 style="font-size:1.8rem;font-weight:700;letter-spacing:-0.02em;margin-bottom:3.5rem;">De l'idée à la marque</h2>

        <div class="timeline-item">
            <div class="timeline-year">2022</div>
            <div class="timeline-content">
                <div class="timeline-title">L'idée germe</div>
                <p class="timeline-text">
                    Frustrés par un marché saturé de logos et de tendances sans âme,
                    les fondateurs de Monnwear décident de créer une alternative radicalement simple.
                </p>
            </div>
        </div>

        <div class="timeline-item">
            <div class="timeline-year">2023</div>
            <div class="timeline-content">
                <div class="timeline-title">Les premières pièces</div>
                <p class="timeline-text">
                    Après des mois de tests avec des ateliers locaux, les premières
                    collections Monnwear voient le jour : le Hoodie Essential et le Classic Tee.
                    Sold out en 48h.
                </p>
            </div>
        </div>

        <div class="timeline-item">
            <div class="timeline-year">2024</div>
            <div class="timeline-content">
                <div class="timeline-title">La communauté se construit</div>
                <p class="timeline-text">
                    Plus de 500 clients fidèles. Le Cargo Tech Pants et la Track Jacket rejoignent
                    le catalogue. Monnwear commence à être porté dans toute la Tunisie.
                </p>
            </div>
        </div>

        <div class="timeline-item">
            <div class="timeline-year">2025</div>
            <div class="timeline-content">
                <div class="timeline-title">Collection complète</div>
                <p class="timeline-text">
                    6 pièces iconiques. Une identité visuelle assumée. Et la conviction que
                    le meilleur est encore à venir.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num">500+</div>
                    <div class="stat-lbl">Clients satisfaits</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num">6</div>
                    <div class="stat-lbl">Pièces au catalogue</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num">100%</div>
                    <div class="stat-lbl">Coton premium</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num">TN</div>
                    <div class="stat-lbl">Fabriqué localement</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Prêt à rejoindre la communauté ?</h2>
        <p class="cta-sub">Explorez nos collections et trouvez votre prochaine pièce essentielle.</p>
        <a href="shop.php" class="btn-mw-primary">
            <i class="bi bi-bag me-2"></i> Découvrir la boutique
        </a>
    </div>
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
