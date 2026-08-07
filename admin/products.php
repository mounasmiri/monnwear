<?php
/**
 * admin/products.php
 * Liste tous les produits avec options Modifier / Supprimer.
 *
 * La suppression est traitée directement sur cette page via POST
 * pour éviter la suppression accidentelle via un simple lien GET.
 */

require_once '../includes/db_connect.php';


// ----------------------------------------------------------------
// TRAITEMENT DE LA SUPPRESSION (POST)
// ----------------------------------------------------------------
/*
 * On accepte uniquement la méthode POST pour les suppressions.
 * Un lien GET (ex: ?delete=3) pourrait être déclenché par erreur
 * (navigation, pré-chargement de liens, etc.).
 *
 * Un formulaire POST avec bouton de confirmation est plus sûr.
 */
$messageFlash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $idDelete = (int)$_POST['delete_id'];

    if ($idDelete > 0) {
        /*
         * ATTENTION : avant de supprimer un produit, il faut vérifier
         * qu'il n'est pas référencé dans Details_Commande (contrainte FK).
         * Si c'est le cas, MySQL lèvera une erreur.
         * On gère ça avec un try/catch PDOException.
         */
        try {
            $stmtDel = $pdo->prepare('DELETE FROM Produits WHERE id_produit = :id');
            $stmtDel->bindValue(':id', $idDelete, PDO::PARAM_INT);
            $stmtDel->execute();

            $messageFlash = ['type' => 'success', 'texte' => 'Produit supprimé avec succès.'];

        } catch (PDOException $e) {
            // Contrainte FK violée : le produit est dans une commande existante
            $messageFlash = ['type' => 'error', 'texte' => 'Impossible de supprimer ce produit — il est lié à des commandes existantes.'];
        }
    }
}


// ----------------------------------------------------------------
// RÉCUPÉRATION DE TOUS LES PRODUITS
// ----------------------------------------------------------------
/*
 * On joint Categories pour afficher le nom de catégorie.
 * ORDER BY nom_categorie, nom : trie par catégorie puis par nom de produit.
 */
$stmt = $pdo->prepare('
    SELECT p.id_produit, p.nom, p.prix, p.stock, p.image_url,
           c.nom_categorie
    FROM Produits p
    INNER JOIN Categories c ON p.id_categorie = c.id_categorie
    ORDER BY c.nom_categorie ASC, p.nom ASC
');
$stmt->execute();
$produits = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits — Admin Monnwear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --mw-black:#0a0a0a; --mw-dark:#111111; --mw-card:#1a1a1a; --mw-border:#2a2a2a; --mw-white:#f5f5f5; --mw-muted:#888888; }
        * { box-sizing:border-box; }
        body { background-color:var(--mw-black); color:var(--mw-white); font-family:'Space Grotesk',sans-serif; margin:0; display:flex; min-height:100vh; }

        /* SIDEBAR */
        .sidebar { width:220px; background-color:var(--mw-dark); border-right:1px solid var(--mw-border); padding:2rem 1.25rem; flex-shrink:0; position:fixed; height:100vh; overflow-y:auto; }
        .sidebar-brand { font-size:1.1rem; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:var(--mw-white); margin-bottom:0.25rem; }
        .sidebar-sub { font-size:0.65rem; letter-spacing:0.2em; text-transform:uppercase; color:var(--mw-muted); margin-bottom:2rem; }
        .nav-section-label { font-size:0.6rem; letter-spacing:0.2em; text-transform:uppercase; color:var(--mw-muted); margin:1.5rem 0 0.5rem; padding:0 0.5rem; }
        .sidebar-link { display:flex; align-items:center; gap:10px; padding:0.55rem 0.75rem; border-radius:3px; color:var(--mw-muted); text-decoration:none; font-size:0.82rem; font-weight:500; transition:background-color 0.15s,color 0.15s; margin-bottom:2px; }
        .sidebar-link:hover { background-color:rgba(255,255,255,0.05); color:var(--mw-white); }
        .sidebar-link.active { background-color:rgba(255,255,255,0.08); color:var(--mw-white); }
        .sidebar-link i { font-size:0.95rem; width:18px; text-align:center; }
        .sidebar-divider { border:none; border-top:1px solid var(--mw-border); margin:1rem 0; }

        /* MAIN */
        .main-content { margin-left:220px; flex:1; padding:2.5rem; }
        .page-heading { margin-bottom:2rem; }
        .page-heading h1 { font-size:1.6rem; font-weight:700; letter-spacing:-0.01em; margin:0 0 0.25rem; }
        .page-heading p { color:var(--mw-muted); font-size:0.83rem; margin:0; }

        /* BOUTONS */
        .btn-mw { display:inline-flex; align-items:center; gap:6px; padding:0.6rem 1.2rem; font-size:0.78rem; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; border-radius:2px; text-decoration:none; transition:all 0.2s; cursor:pointer; font-family:inherit; border:none; }
        .btn-mw-white { background-color:var(--mw-white); color:var(--mw-black); }
        .btn-mw-white:hover { background-color:#e0e0e0; color:var(--mw-black); }
        .btn-mw-ghost { background:transparent; border:1px solid var(--mw-border); color:var(--mw-muted); }
        .btn-mw-ghost:hover { border-color:var(--mw-white); color:var(--mw-white); }
        .btn-mw-danger { background:transparent; border:1px solid rgba(248,113,113,0.3); color:#f87171; }
        .btn-mw-danger:hover { background:rgba(248,113,113,0.1); border-color:#f87171; }

        /* TABLE */
        .table-card { background-color:var(--mw-card); border:1px solid var(--mw-border); border-radius:4px; overflow:hidden; }
        .admin-table { width:100%; border-collapse:collapse; font-size:0.84rem; }
        .admin-table th { text-align:left; font-size:0.65rem; letter-spacing:0.15em; text-transform:uppercase; color:var(--mw-muted); padding:1rem 1.25rem 0.75rem; border-bottom:1px solid var(--mw-border); }
        .admin-table td { padding:0.9rem 1.25rem; border-bottom:1px solid var(--mw-border); color:var(--mw-white); vertical-align:middle; }
        .admin-table tr:last-child td { border-bottom:none; }
        .admin-table tr:hover td { background-color:rgba(255,255,255,0.02); }

        /* Miniature produit */
        .prod-thumb { width:44px; height:54px; object-fit:cover; border-radius:2px; background:#161616; }

        /* Stock badge */
        .stock-ok    { color:#4ade80; font-weight:600; }
        .stock-low   { color:#fb923c; font-weight:600; }
        .stock-zero  { color:#f87171; font-weight:600; }

        /* FLASH */
        .flash-success { background:rgba(74,222,128,0.1); border:1px solid rgba(74,222,128,0.3); color:#4ade80; padding:0.85rem 1.25rem; border-radius:2px; font-size:0.84rem; margin-bottom:1.5rem; }
        .flash-error   { background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.3); color:#f87171; padding:0.85rem 1.25rem; border-radius:2px; font-size:0.84rem; margin-bottom:1.5rem; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">Monnwear</div>
    <div class="sidebar-sub">Administration</div>
    <p class="nav-section-label">Principal</p>
    <a href="index.php"   class="sidebar-link"><i class="bi bi-grid"></i> Dashboard</a>
    <a href="orders.php"  class="sidebar-link"><i class="bi bi-bag-check"></i> Commandes</a>
    <p class="nav-section-label">Catalogue</p>
    <a href="products.php"    class="sidebar-link active"><i class="bi bi-box-seam"></i> Produits</a>
    <a href="add_product.php" class="sidebar-link"><i class="bi bi-plus-circle"></i> Ajouter produit</a>
    <hr class="sidebar-divider">
    <a href="../index.php" class="sidebar-link"><i class="bi bi-arrow-left"></i> Voir la boutique</a>
</aside>

<!-- MAIN -->
<main class="main-content">

    <div class="page-heading d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1>Produits</h1>
            <p><?= count($produits) ?> produit<?= count($produits) > 1 ? 's' : '' ?> au catalogue</p>
        </div>
        <a href="add_product.php" class="btn-mw btn-mw-white">
            <i class="bi bi-plus-lg"></i> Ajouter un produit
        </a>
    </div>

    <!-- Message flash -->
    <?php if (!empty($messageFlash)): ?>
    <div class="flash-<?= $messageFlash['type'] ?>">
        <i class="bi bi-<?= $messageFlash['type'] === 'success' ? 'check2-circle' : 'exclamation-triangle' ?> me-2"></i>
        <?= htmlspecialchars($messageFlash['texte']) ?>
    </div>
    <?php endif; ?>

    <!-- TABLE DES PRODUITS -->
    <div class="table-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($produits as $prod):
                // Classe CSS selon le niveau de stock
                if ($prod['stock'] == 0)     $stockClass = 'stock-zero';
                elseif ($prod['stock'] < 5)  $stockClass = 'stock-low';
                else                         $stockClass = 'stock-ok';
            ?>
            <tr>
                <!-- Miniature -->
                <td style="width:60px;">
                    <img src="<?= htmlspecialchars($prod['image_url'] ?? '') ?>"
                         alt="" class="prod-thumb">
                </td>

                <!-- Nom -->
                <td style="font-weight:600;"><?= htmlspecialchars($prod['nom']) ?></td>

                <!-- Catégorie -->
                <td style="color:var(--mw-muted);"><?= htmlspecialchars($prod['nom_categorie']) ?></td>

                <!-- Prix -->
                <td><?= number_format($prod['prix'], 2, ',', ' ') ?> DT</td>

                <!-- Stock -->
                <td class="<?= $stockClass ?>">
                    <?= $prod['stock'] == 0 ? 'Rupture' : $prod['stock'] ?>
                </td>

                <!-- Actions : Modifier + Supprimer -->
                <td>
                    <div class="d-flex gap-2">

                        <!-- Bouton Modifier → edit_product.php -->
                        <a href="edit_product.php?id=<?= (int)$prod['id_produit'] ?>"
                           class="btn-mw btn-mw-ghost" style="padding:0.4rem 0.8rem;">
                            <i class="bi bi-pencil"></i>
                        </a>

                        <!--
                            Bouton Supprimer.
                            Utilise un formulaire POST (sécurisé) + confirmation JS
                            pour éviter les suppressions accidentelles.
                        -->
                        <form method="POST" action="products.php"
                              onsubmit="return confirm('Supprimer « <?= htmlspecialchars($prod['nom'], ENT_QUOTES) ?> » ?');">
                            <input type="hidden" name="delete_id" value="<?= (int)$prod['id_produit'] ?>">
                            <button type="submit" class="btn-mw btn-mw-danger" style="padding:0.4rem 0.8rem;">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>

                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div><!-- /.table-card -->

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
