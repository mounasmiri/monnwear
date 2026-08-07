<?php
/**
 * admin/index.php
 * Tableau de bord de l'administration Monnwear.
 *
 * Affiche des statistiques clés :
 *  - Nombre total de commandes et chiffre d'affaires
 *  - Commandes récentes
 *  - Produits en rupture ou stock faible
 */

// Le chemin vers db_connect.php est relatif au dossier admin/ → ../
require_once '../includes/db_connect.php';


// ----------------------------------------------------------------
// 1. STATISTIQUES GÉNÉRALES
// ----------------------------------------------------------------

// Nombre total de commandes
$stmtNbCmd = $pdo->prepare('SELECT COUNT(*) AS total FROM Commandes');
$stmtNbCmd->execute();
$nbCommandes = (int)$stmtNbCmd->fetch()['total'];

/*
 * Chiffre d'affaires total.
 * On multiplie prix × quantite pour chaque ligne de Details_Commande
 * et on somme le tout avec SUM().
 * COALESCE(x, 0) retourne 0 si la table est vide (SUM retourne NULL).
 */
$stmtCA = $pdo->prepare('
    SELECT COALESCE(SUM(p.prix * dc.quantite), 0) AS ca
    FROM Details_Commande dc
    INNER JOIN Produits p ON dc.id_produit = p.id_produit
');
$stmtCA->execute();
$chiffreAffaires = (float)$stmtCA->fetch()['ca'];

// Nombre de clients enregistrés
$stmtClients = $pdo->prepare('SELECT COUNT(*) AS total FROM Clients');
$stmtClients->execute();
$nbClients = (int)$stmtClients->fetch()['total'];

// Nombre de produits en stock faible (stock < 5) ou en rupture (stock = 0)
$stmtStockFaible = $pdo->prepare('SELECT COUNT(*) AS total FROM Produits WHERE stock < 5');
$stmtStockFaible->execute();
$nbStockFaible = (int)$stmtStockFaible->fetch()['total'];


// ----------------------------------------------------------------
// 2. COMMANDES RÉCENTES (les 5 dernières)
// ----------------------------------------------------------------
$stmtRecentes = $pdo->prepare('
    SELECT cm.id_commande, cm.date_commande, cm.statut,
           cl.nom AS client_nom,
           COALESCE(SUM(p.prix * dc.quantite), 0) AS total
    FROM Commandes cm
    INNER JOIN Clients cl ON cm.id_client = cl.id_client
    LEFT JOIN Details_Commande dc ON cm.id_commande = dc.id_commande
    LEFT JOIN Produits p ON dc.id_produit = p.id_produit
    GROUP BY cm.id_commande, cm.date_commande, cm.statut, cl.nom
    ORDER BY cm.date_commande DESC
    LIMIT 5
');
$stmtRecentes->execute();
$commandesRecentes = $stmtRecentes->fetchAll();


// ----------------------------------------------------------------
// 3. PRODUITS EN STOCK FAIBLE
// ----------------------------------------------------------------
$stmtProdFaible = $pdo->prepare('
    SELECT id_produit, nom, stock
    FROM Produits
    WHERE stock < 5
    ORDER BY stock ASC
');
$stmtProdFaible->execute();
$produitsFaibles = $stmtProdFaible->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Monnwear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --mw-black: #0a0a0a; --mw-dark: #111111; --mw-card: #1a1a1a;
            --mw-border: #2a2a2a; --mw-white: #f5f5f5; --mw-muted: #888888;
        }
        * { box-sizing: border-box; }
        body { background-color: var(--mw-black); color: var(--mw-white); font-family: 'Space Grotesk', sans-serif; margin: 0; display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: 220px;
            background-color: var(--mw-dark);
            border-right: 1px solid var(--mw-border);
            padding: 2rem 1.25rem;
            flex-shrink: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-brand {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--mw-white);
            margin-bottom: 0.25rem;
        }
        .sidebar-sub {
            font-size: 0.65rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin-bottom: 2rem;
        }
        .nav-section-label {
            font-size: 0.6rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin: 1.5rem 0 0.5rem;
            padding: 0 0.5rem;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.55rem 0.75rem;
            border-radius: 3px;
            color: var(--mw-muted);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 500;
            transition: background-color 0.15s, color 0.15s;
            margin-bottom: 2px;
        }
        .sidebar-link:hover { background-color: rgba(255,255,255,0.05); color: var(--mw-white); }
        .sidebar-link.active { background-color: rgba(255,255,255,0.08); color: var(--mw-white); }
        .sidebar-link i { font-size: 0.95rem; width: 18px; text-align: center; }
        .sidebar-divider { border: none; border-top: 1px solid var(--mw-border); margin: 1rem 0; }

        /* MAIN CONTENT */
        .main-content { margin-left: 220px; flex: 1; padding: 2.5rem; }

        /* PAGE HEADER */
        .page-heading { margin-bottom: 2.5rem; }
        .page-heading h1 { font-size: 1.6rem; font-weight: 700; letter-spacing: -0.01em; margin: 0 0 0.25rem; }
        .page-heading p { color: var(--mw-muted); font-size: 0.83rem; margin: 0; }

        /* STAT CARDS */
        .stat-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            padding: 1.5rem;
        }
        .stat-label { font-size: 0.68rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--mw-muted); margin-bottom: 0.6rem; }
        .stat-value { font-size: 2rem; font-weight: 700; letter-spacing: -0.02em; line-height: 1; }
        .stat-icon { font-size: 1.4rem; opacity: 0.2; }

        /* TABLES */
        .admin-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
        .admin-table th {
            text-align: left;
            font-size: 0.65rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--mw-muted);
            padding: 0 1rem 0.75rem;
            border-bottom: 1px solid var(--mw-border);
        }
        .admin-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--mw-border);
            color: var(--mw-white);
            vertical-align: middle;
        }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tr:hover td { background-color: rgba(255,255,255,0.02); }

        /* BADGES STATUT */
        .badge-statut {
            display: inline-block;
            font-size: 0.65rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 2px;
            font-weight: 600;
        }
        .badge-attente  { background: rgba(251,146,60,0.15); color: #fb923c; border: 1px solid rgba(251,146,60,0.3); }
        .badge-expedie  { background: rgba(96,165,250,0.15); color: #60a5fa; border: 1px solid rgba(96,165,250,0.3); }
        .badge-livre    { background: rgba(74,222,128,0.15); color: #4ade80; border: 1px solid rgba(74,222,128,0.3); }
        .badge-annule   { background: rgba(248,113,113,0.15); color: #f87171; border: 1px solid rgba(248,113,113,0.3); }

        /* SECTION CARD */
        .section-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .section-card-title {
            font-size: 0.7rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--mw-muted);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--mw-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-card-title a {
            font-size: 0.72rem;
            color: var(--mw-muted);
            text-decoration: none;
            transition: color 0.2s;
        }
        .section-card-title a:hover { color: var(--mw-white); }

        /* ALERTE STOCK */
        .stock-alert {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.65rem 0;
            border-bottom: 1px solid var(--mw-border);
            font-size: 0.83rem;
        }
        .stock-alert:last-child { border-bottom: none; }
    </style>
</head>
<body>

<!-- ================================================================
     SIDEBAR
================================================================ -->
<aside class="sidebar">
    <div class="sidebar-brand">Monnwear</div>
    <div class="sidebar-sub">Administration</div>

    <p class="nav-section-label">Principal</p>
    <a href="index.php" class="sidebar-link active">
        <i class="bi bi-grid"></i> Dashboard
    </a>
    <a href="orders.php" class="sidebar-link">
        <i class="bi bi-bag-check"></i> Commandes
    </a>

    <p class="nav-section-label">Catalogue</p>
    <a href="products.php" class="sidebar-link">
        <i class="bi bi-box-seam"></i> Produits
    </a>
    <a href="add_product.php" class="sidebar-link">
        <i class="bi bi-plus-circle"></i> Ajouter produit
    </a>

    <hr class="sidebar-divider">
    <a href="../index.php" class="sidebar-link">
        <i class="bi bi-arrow-left"></i> Voir la boutique
    </a>
</aside>

<!-- ================================================================
     CONTENU PRINCIPAL
================================================================ -->
<main class="main-content">

    <!-- En-tête -->
    <div class="page-heading">
        <h1>Dashboard</h1>
        <p>Vue d'ensemble de la boutique Monnwear</p>
    </div>

    <!-- ============================================================
         CARTES DE STATISTIQUES
    ============================================================ -->
    <div class="row g-3 mb-4">

        <!-- Total commandes -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Commandes</div>
                    <div class="stat-value"><?= $nbCommandes ?></div>
                </div>
                <i class="bi bi-bag-check stat-icon"></i>
            </div>
        </div>

        <!-- Chiffre d'affaires -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Chiffre d'affaires</div>
                    <!--
                        number_format() : 3 arguments — (valeur, décimales, séparateur_décimal)
                        On n'affiche pas les centimes pour le CA global.
                    -->
                    <div class="stat-value"><?= number_format($chiffreAffaires, 0, ',', ' ') ?> <span style="font-size:1rem;font-weight:500;">DT</span></div>
                </div>
                <i class="bi bi-graph-up stat-icon"></i>
            </div>
        </div>

        <!-- Clients -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Clients</div>
                    <div class="stat-value"><?= $nbClients ?></div>
                </div>
                <i class="bi bi-people stat-icon"></i>
            </div>
        </div>

        <!-- Alertes stock -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex justify-content-between align-items-start"
                 style="<?= $nbStockFaible > 0 ? 'border-color:rgba(251,146,60,0.4)' : '' ?>">
                <div>
                    <div class="stat-label">Alertes stock</div>
                    <div class="stat-value" style="<?= $nbStockFaible > 0 ? 'color:#fb923c' : '' ?>">
                        <?= $nbStockFaible ?>
                    </div>
                </div>
                <i class="bi bi-exclamation-triangle stat-icon" style="<?= $nbStockFaible > 0 ? 'opacity:0.6;color:#fb923c' : '' ?>"></i>
            </div>
        </div>

    </div><!-- /.row stats -->

    <!-- ============================================================
         TABLES : commandes récentes + alertes stock
    ============================================================ -->
    <div class="row g-3">

        <!-- Commandes récentes -->
        <div class="col-lg-7">
            <div class="section-card">
                <div class="section-card-title">
                    <span>Commandes récentes</span>
                    <a href="orders.php">Voir toutes <i class="bi bi-arrow-right"></i></a>
                </div>

                <?php if (empty($commandesRecentes)): ?>
                <p style="color:var(--mw-muted);font-size:0.85rem;text-align:center;padding:2rem 0;">
                    Aucune commande pour le moment.
                </p>
                <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($commandesRecentes as $cmd):
                        // Choix de la classe CSS du badge selon le statut
                        $badgeClass = match($cmd['statut']) {
                            'Expédié'   => 'badge-expedie',
                            'Livré'     => 'badge-livre',
                            'Annulé'    => 'badge-annule',
                            default     => 'badge-attente',
                        };
                    ?>
                    <tr>
                        <td style="color:var(--mw-muted);">
                            #<?= str_pad($cmd['id_commande'], 5, '0', STR_PAD_LEFT) ?>
                        </td>
                        <td><?= htmlspecialchars($cmd['client_nom']) ?></td>
                        <td style="color:var(--mw-muted);">
                            <?= date('d/m/Y', strtotime($cmd['date_commande'])) ?>
                        </td>
                        <td><?= number_format($cmd['total'], 2, ',', ' ') ?> DT</td>
                        <td><span class="badge-statut <?= $badgeClass ?>"><?= htmlspecialchars($cmd['statut']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alertes stock faible -->
        <div class="col-lg-5">
            <div class="section-card">
                <div class="section-card-title">
                    <span>Alertes stock</span>
                    <a href="products.php">Gérer <i class="bi bi-arrow-right"></i></a>
                </div>

                <?php if (empty($produitsFaibles)): ?>
                <p style="color:#4ade80;font-size:0.83rem;text-align:center;padding:2rem 0;">
                    <i class="bi bi-check2-circle me-1"></i> Tous les stocks sont suffisants.
                </p>
                <?php else: ?>
                <?php foreach ($produitsFaibles as $prod): ?>
                <div class="stock-alert">
                    <span><?= htmlspecialchars($prod['nom']) ?></span>
                    <span style="font-weight:700; color:<?= $prod['stock'] == 0 ? '#f87171' : '#fb923c' ?>;">
                        <?= $prod['stock'] == 0 ? 'Rupture' : $prod['stock'] . ' restant' . ($prod['stock'] > 1 ? 's' : '') ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.row tables -->
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
