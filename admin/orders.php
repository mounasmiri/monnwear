<?php
/**
 * admin/orders.php
 * Gestion des commandes : liste complète + mise à jour du statut de livraison.
 *
 * Fonctionnement :
 *  - Affiche toutes les commandes avec client, date, total et statut d'expédition.
 *  - Permet de mettre à jour le statut_livraison de la table Expeditions via POST.
 */

require_once '../includes/db_connect.php';

$messageFlash = '';

// ----------------------------------------------------------------
// TRAITEMENT DE LA MISE À JOUR DU STATUT (POST)
// ----------------------------------------------------------------
/*
 * Le formulaire de chaque ligne envoie :
 *   - id_expedition : id de l'expédition à modifier
 *   - statut        : nouveau statut choisi dans le <select>
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_expedition'], $_POST['statut'])) {

    $idExp      = (int)$_POST['id_expedition'];
    $nouveauStat = trim($_POST['statut'] ?? '');

    /*
     * On valide que le statut fait partie de la liste autorisée.
     * in_array() avec true (strict) évite les comparaisons laxistes.
     * Cela empêche d'insérer une valeur arbitraire en BDD.
     */
    $statutsAutorisés = ['En attente', 'Expédié', 'Livré', 'Annulé'];

    if ($idExp > 0 && in_array($nouveauStat, $statutsAutorisés, true)) {

        // Met à jour Expeditions ET Commandes en même temps pour cohérence
        $pdo->beginTransaction();
        try {
            // 1. Mise à jour du statut de livraison dans Expeditions
            $stmtExp = $pdo->prepare('
                UPDATE Expeditions
                SET statut_livraison = :statut,
                    date_expedition  = CASE WHEN :statut2 = \'Expédié\' AND date_expedition IS NULL
                                           THEN NOW() ELSE date_expedition END
                WHERE id_expedition = :id
            ');
            $stmtExp->bindValue(':statut',  $nouveauStat);
            $stmtExp->bindValue(':statut2', $nouveauStat); // PDO ne permet pas de réutiliser un placeholder
            $stmtExp->bindValue(':id',      $idExp, PDO::PARAM_INT);
            $stmtExp->execute();

            // 2. Synchronise le statut de la commande associée
            $stmtCmd = $pdo->prepare('
                UPDATE Commandes cm
                INNER JOIN Expeditions ex ON cm.id_commande = ex.id_commande
                SET cm.statut = :statut
                WHERE ex.id_expedition = :id_exp
            ');
            $stmtCmd->bindValue(':statut',  $nouveauStat);
            $stmtCmd->bindValue(':id_exp',  $idExp, PDO::PARAM_INT);
            $stmtCmd->execute();

            $pdo->commit();
            $messageFlash = ['type' => 'success', 'texte' => 'Statut mis à jour avec succès.'];

        } catch (PDOException $e) {
            $pdo->rollBack();
            $messageFlash = ['type' => 'error', 'texte' => 'Erreur lors de la mise à jour.'];
        }
    }
}


// ----------------------------------------------------------------
// RÉCUPÉRATION DE TOUTES LES COMMANDES
// ----------------------------------------------------------------
/*
 * On joint :
 *   - Clients         → nom et email du client
 *   - Details_Commande + Produits → calcul du total (SUM prix × quantité)
 *   - Expeditions     → statut de livraison
 *
 * LEFT JOIN sur Expeditions : une commande peut ne pas encore avoir d'expédition.
 * GROUP BY obligatoire car on utilise SUM() (fonction d'agrégation).
 */
$stmt = $pdo->prepare('
    SELECT
        cm.id_commande,
        cm.date_commande,
        cm.statut,
        cl.nom   AS client_nom,
        cl.email AS client_email,
        COALESCE(SUM(p.prix * dc.quantite), 0) AS total,
        ex.id_expedition,
        ex.statut_livraison,
        ex.date_expedition
    FROM Commandes cm
    INNER JOIN Clients cl          ON cm.id_client  = cl.id_client
    LEFT  JOIN Details_Commande dc ON cm.id_commande = dc.id_commande
    LEFT  JOIN Produits p          ON dc.id_produit  = p.id_produit
    LEFT  JOIN Expeditions ex      ON cm.id_commande = ex.id_commande
    GROUP BY
        cm.id_commande, cm.date_commande, cm.statut,
        cl.nom, cl.email,
        ex.id_expedition, ex.statut_livraison, ex.date_expedition
    ORDER BY cm.date_commande DESC
');
$stmt->execute();
$commandes = $stmt->fetchAll();

// Fonction helper pour choisir la classe CSS du badge de statut
function badgeClass(string $statut): string {
    return match($statut) {
        'Expédié' => 'badge-expedie',
        'Livré'   => 'badge-livre',
        'Annulé'  => 'badge-annule',
        default   => 'badge-attente',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes — Admin Monnwear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --mw-black:#0a0a0a; --mw-dark:#111111; --mw-card:#1a1a1a; --mw-border:#2a2a2a; --mw-white:#f5f5f5; --mw-muted:#888888; }
        * { box-sizing:border-box; }
        body { background-color:var(--mw-black); color:var(--mw-white); font-family:'Space Grotesk',sans-serif; margin:0; display:flex; min-height:100vh; }
        .sidebar { width:220px; background-color:var(--mw-dark); border-right:1px solid var(--mw-border); padding:2rem 1.25rem; flex-shrink:0; position:fixed; height:100vh; overflow-y:auto; }
        .sidebar-brand { font-size:1.1rem; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:var(--mw-white); margin-bottom:0.25rem; }
        .sidebar-sub { font-size:0.65rem; letter-spacing:0.2em; text-transform:uppercase; color:var(--mw-muted); margin-bottom:2rem; }
        .nav-section-label { font-size:0.6rem; letter-spacing:0.2em; text-transform:uppercase; color:var(--mw-muted); margin:1.5rem 0 0.5rem; padding:0 0.5rem; }
        .sidebar-link { display:flex; align-items:center; gap:10px; padding:0.55rem 0.75rem; border-radius:3px; color:var(--mw-muted); text-decoration:none; font-size:0.82rem; font-weight:500; transition:background-color 0.15s,color 0.15s; margin-bottom:2px; }
        .sidebar-link:hover { background-color:rgba(255,255,255,0.05); color:var(--mw-white); }
        .sidebar-link.active { background-color:rgba(255,255,255,0.08); color:var(--mw-white); }
        .sidebar-link i { font-size:0.95rem; width:18px; text-align:center; }
        .sidebar-divider { border:none; border-top:1px solid var(--mw-border); margin:1rem 0; }
        .main-content { margin-left:220px; flex:1; padding:2.5rem; }
        .page-heading { margin-bottom:2rem; }
        .page-heading h1 { font-size:1.6rem; font-weight:700; letter-spacing:-0.01em; margin:0 0 0.25rem; }
        .page-heading p { color:var(--mw-muted); font-size:0.83rem; margin:0; }

        /* TABLE */
        .table-card { background-color:var(--mw-card); border:1px solid var(--mw-border); border-radius:4px; overflow:hidden; }
        .admin-table { width:100%; border-collapse:collapse; font-size:0.84rem; }
        .admin-table th { text-align:left; font-size:0.65rem; letter-spacing:0.15em; text-transform:uppercase; color:var(--mw-muted); padding:1rem 1.25rem 0.75rem; border-bottom:1px solid var(--mw-border); white-space:nowrap; }
        .admin-table td { padding:0.9rem 1.25rem; border-bottom:1px solid var(--mw-border); color:var(--mw-white); vertical-align:middle; }
        .admin-table tr:last-child td { border-bottom:none; }
        .admin-table tr:hover td { background-color:rgba(255,255,255,0.02); }

        /* BADGES */
        .badge-statut { display:inline-block; font-size:0.65rem; letter-spacing:0.08em; text-transform:uppercase; padding:3px 10px; border-radius:2px; font-weight:600; }
        .badge-attente { background:rgba(251,146,60,0.15); color:#fb923c; border:1px solid rgba(251,146,60,0.3); }
        .badge-expedie { background:rgba(96,165,250,0.15); color:#60a5fa; border:1px solid rgba(96,165,250,0.3); }
        .badge-livre   { background:rgba(74,222,128,0.15); color:#4ade80; border:1px solid rgba(74,222,128,0.3); }
        .badge-annule  { background:rgba(248,113,113,0.15); color:#f87171; border:1px solid rgba(248,113,113,0.3); }

        /* SELECT STATUT dans la table */
        .select-statut {
            background-color:#111;
            border:1px solid var(--mw-border);
            color:var(--mw-white);
            padding:0.35rem 0.6rem;
            font-size:0.75rem;
            font-family:inherit;
            border-radius:2px;
            cursor:pointer;
            transition:border-color 0.2s;
        }
        .select-statut:focus { outline:none; border-color:var(--mw-white); }
        .select-statut option { background:#1a1a1a; }

        /* Bouton Enregistrer compact */
        .btn-save { background-color:var(--mw-white); color:var(--mw-black); border:none; padding:0.35rem 0.85rem; font-size:0.72rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; border-radius:2px; cursor:pointer; font-family:inherit; transition:background-color 0.2s; }
        .btn-save:hover { background-color:#e0e0e0; }

        /* FLASH */
        .flash-success { background:rgba(74,222,128,0.1); border:1px solid rgba(74,222,128,0.3); color:#4ade80; padding:0.85rem 1.25rem; border-radius:2px; font-size:0.84rem; margin-bottom:1.5rem; }
        .flash-error   { background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.3); color:#f87171; padding:0.85rem 1.25rem; border-radius:2px; font-size:0.84rem; margin-bottom:1.5rem; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">Monnwear</div>
    <div class="sidebar-sub">Administration</div>
    <p class="nav-section-label">Principal</p>
    <a href="index.php"  class="sidebar-link"><i class="bi bi-grid"></i> Dashboard</a>
    <a href="orders.php" class="sidebar-link active"><i class="bi bi-bag-check"></i> Commandes</a>
    <p class="nav-section-label">Catalogue</p>
    <a href="products.php"    class="sidebar-link"><i class="bi bi-box-seam"></i> Produits</a>
    <a href="add_product.php" class="sidebar-link"><i class="bi bi-plus-circle"></i> Ajouter produit</a>
    <hr class="sidebar-divider">
    <a href="../index.php" class="sidebar-link"><i class="bi bi-arrow-left"></i> Voir la boutique</a>
</aside>

<main class="main-content">

    <div class="page-heading">
        <h1>Commandes</h1>
        <p><?= count($commandes) ?> commande<?= count($commandes) > 1 ? 's' : '' ?> au total</p>
    </div>

    <!-- Message flash -->
    <?php if (!empty($messageFlash)): ?>
    <div class="flash-<?= $messageFlash['type'] ?>">
        <i class="bi bi-<?= $messageFlash['type'] === 'success' ? 'check2-circle' : 'exclamation-triangle' ?> me-2"></i>
        <?= htmlspecialchars($messageFlash['texte']) ?>
    </div>
    <?php endif; ?>

    <?php if (empty($commandes)): ?>
    <div style="text-align:center;padding:4rem;color:var(--mw-muted);">
        <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>
        Aucune commande pour le moment.
    </div>

    <?php else: ?>
    <div class="table-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Expédition</th>
                    <th>Nouveau statut</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($commandes as $cmd): ?>
            <tr>
                <!-- Numéro de commande formaté sur 5 chiffres -->
                <td style="color:var(--mw-muted); font-weight:600;">
                    #<?= str_pad($cmd['id_commande'], 5, '0', STR_PAD_LEFT) ?>
                </td>

                <!-- Client -->
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($cmd['client_nom']) ?></div>
                    <div style="font-size:0.75rem;color:var(--mw-muted);"><?= htmlspecialchars($cmd['client_email']) ?></div>
                </td>

                <!-- Date -->
                <td style="color:var(--mw-muted); white-space:nowrap;">
                    <?= date('d/m/Y', strtotime($cmd['date_commande'])) ?>
                    <div style="font-size:0.72rem;"><?= date('H:i', strtotime($cmd['date_commande'])) ?></div>
                </td>

                <!-- Total -->
                <td style="font-weight:600;"><?= number_format($cmd['total'], 2, ',', ' ') ?> DT</td>

                <!-- Statut actuel (badge) -->
                <td>
                    <?php
                    $statActuel = $cmd['statut_livraison'] ?? $cmd['statut'];
                    ?>
                    <span class="badge-statut <?= badgeClass($statActuel) ?>">
                        <?= htmlspecialchars($statActuel) ?>
                    </span>
                    <?php if (!empty($cmd['date_expedition'])): ?>
                    <div style="font-size:0.7rem;color:var(--mw-muted);margin-top:3px;">
                        Exp. le <?= date('d/m/Y', strtotime($cmd['date_expedition'])) ?>
                    </div>
                    <?php endif; ?>
                </td>

                <!-- Formulaire de mise à jour du statut -->
                <td>
                    <?php if (!empty($cmd['id_expedition'])): ?>
                    <!--
                        Chaque ligne a son propre formulaire POST.
                        On envoie : id_expedition + nouveau statut choisi.
                        onchange sur le <select> soumet automatiquement le formulaire
                        pour une UX fluide (pas besoin de cliquer sur un bouton).
                    -->
                    <form method="POST" action="orders.php" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="id_expedition" value="<?= (int)$cmd['id_expedition'] ?>">

                        <select name="statut" class="select-statut" onchange="this.form.submit()">
                            <option value="En attente" <?= $statActuel === 'En attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="Expédié"    <?= $statActuel === 'Expédié'    ? 'selected' : '' ?>>Expédié</option>
                            <option value="Livré"      <?= $statActuel === 'Livré'      ? 'selected' : '' ?>>Livré</option>
                            <option value="Annulé"     <?= $statActuel === 'Annulé'     ? 'selected' : '' ?>>Annulé</option>
                        </select>
                    </form>
                    <?php else: ?>
                    <span style="color:var(--mw-muted);font-size:0.75rem;">Pas d'expédition</span>
                    <?php endif; ?>
                </td>

            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
