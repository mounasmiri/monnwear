<?php
/**
 * admin/edit_product.php
 * Formulaire de modification d'un produit existant.
 *
 * Fonctionnement :
 *  - GET  : charge les données actuelles du produit et affiche le formulaire pré-rempli.
 *  - POST : valide les nouvelles données et met à jour la ligne en BDD via UPDATE.
 */

require_once '../includes/db_connect.php';


// ----------------------------------------------------------------
// 1. VALIDATION DE L'ID (paramètre GET)
// ----------------------------------------------------------------
$idProduit = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idProduit <= 0) {
    header('Location: products.php');
    exit();
}


// ----------------------------------------------------------------
// 2. RÉCUPÉRATION DU PRODUIT EXISTANT
// ----------------------------------------------------------------
$stmtProd = $pdo->prepare('SELECT * FROM Produits WHERE id_produit = :id LIMIT 1');
$stmtProd->bindValue(':id', $idProduit, PDO::PARAM_INT);
$stmtProd->execute();
$produit = $stmtProd->fetch();

if ($produit === false) {
    header('Location: products.php');
    exit();
}


// ----------------------------------------------------------------
// 3. RÉCUPÉRATION DES CATÉGORIES
// ----------------------------------------------------------------
$stmtCats = $pdo->prepare('SELECT id_categorie, nom_categorie FROM Categories ORDER BY nom_categorie ASC');
$stmtCats->execute();
$categories = $stmtCats->fetchAll();


// ----------------------------------------------------------------
// 4. TRAITEMENT DU FORMULAIRE (POST)
// ----------------------------------------------------------------
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom          = trim($_POST['nom']           ?? '');
    $description  = trim($_POST['description']   ?? '');
    $prix         = trim($_POST['prix']          ?? '');
    $stock        = trim($_POST['stock']         ?? '');
    $image_url    = trim($_POST['image_url']     ?? '');
    $id_categorie = (int)($_POST['id_categorie'] ?? 0);

    // Mise à jour de $produit pour pré-remplir le formulaire en cas d'erreur
    $produit = array_merge($produit, compact('nom', 'description', 'prix', 'stock', 'image_url', 'id_categorie'));

    // ---- VALIDATION (identique à add_product.php) ----
    if (empty($nom))                                   $erreurs[] = 'Le nom est requis.';
    if (empty($description))                           $erreurs[] = 'La description est requise.';
    if (!is_numeric($prix) || (float)$prix <= 0)       $erreurs[] = 'Prix invalide.';
    if (!is_numeric($stock) || (int)$stock < 0)        $erreurs[] = 'Stock invalide.';
    if ($id_categorie <= 0)                            $erreurs[] = 'Catégorie requise.';

    if (empty($erreurs)) {
        /*
         * UPDATE modifie une ligne existante identifiée par son id_produit.
         * On ne touche pas à l'id — il ne change jamais.
         */
        $stmtUpdate = $pdo->prepare('
            UPDATE Produits
            SET id_categorie = :id_categorie,
                nom          = :nom,
                description  = :description,
                prix         = :prix,
                stock        = :stock,
                image_url    = :image_url
            WHERE id_produit = :id
        ');
        $stmtUpdate->bindValue(':id_categorie', $id_categorie,                PDO::PARAM_INT);
        $stmtUpdate->bindValue(':nom',          $nom);
        $stmtUpdate->bindValue(':description',  $description);
        $stmtUpdate->bindValue(':prix',         (float)$prix);
        $stmtUpdate->bindValue(':stock',        (int)$stock,                  PDO::PARAM_INT);
        $stmtUpdate->bindValue(':image_url',    !empty($image_url) ? $image_url : null);
        $stmtUpdate->bindValue(':id',           $idProduit,                   PDO::PARAM_INT);
        $stmtUpdate->execute();

        header('Location: products.php?updated=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier — Admin Monnwear</title>
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
        .form-card { background-color:var(--mw-card); border:1px solid var(--mw-border); border-radius:4px; padding:2rem; max-width:680px; }
        .form-label-mw { font-size:0.72rem; letter-spacing:0.12em; text-transform:uppercase; color:var(--mw-muted); display:block; margin-bottom:0.4rem; }
        .form-control-mw { width:100%; background-color:#111; border:1px solid var(--mw-border); color:var(--mw-white); padding:0.7rem 1rem; font-size:0.88rem; font-family:inherit; border-radius:2px; transition:border-color 0.2s; }
        .form-control-mw:focus { outline:none; border-color:var(--mw-white); }
        .form-control-mw::placeholder { color:var(--mw-muted); }
        select.form-control-mw option { background-color:#1a1a1a; }
        .hint { font-size:0.71rem; color:var(--mw-muted); margin-top:4px; }
        .flash-error { background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.3); color:#f87171; padding:0.85rem 1.25rem; border-radius:2px; font-size:0.84rem; margin-bottom:1.5rem; max-width:680px; }
        .btn-mw { display:inline-flex; align-items:center; gap:6px; padding:0.7rem 1.5rem; font-size:0.8rem; font-weight:600; letter-spacing:0.1em; text-transform:uppercase; border-radius:2px; cursor:pointer; font-family:inherit; border:none; text-decoration:none; transition:all 0.2s; }
        .btn-mw-white { background-color:var(--mw-white); color:var(--mw-black); }
        .btn-mw-white:hover { background-color:#e0e0e0; }
        .btn-mw-ghost { background:transparent; border:1px solid var(--mw-border); color:var(--mw-muted); }
        .btn-mw-ghost:hover { border-color:var(--mw-white); color:var(--mw-white); }
        /* Aperçu image */
        .img-preview { width:80px; height:100px; object-fit:cover; border-radius:2px; border:1px solid var(--mw-border); background:#161616; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">Monnwear</div>
    <div class="sidebar-sub">Administration</div>
    <p class="nav-section-label">Principal</p>
    <a href="index.php"  class="sidebar-link"><i class="bi bi-grid"></i> Dashboard</a>
    <a href="orders.php" class="sidebar-link"><i class="bi bi-bag-check"></i> Commandes</a>
    <p class="nav-section-label">Catalogue</p>
    <a href="products.php"    class="sidebar-link active"><i class="bi bi-box-seam"></i> Produits</a>
    <a href="add_product.php" class="sidebar-link"><i class="bi bi-plus-circle"></i> Ajouter produit</a>
    <hr class="sidebar-divider">
    <a href="../index.php" class="sidebar-link"><i class="bi bi-arrow-left"></i> Voir la boutique</a>
</aside>

<main class="main-content">

    <div class="page-heading">
        <h1>Modifier le produit</h1>
        <p><?= htmlspecialchars($produit['nom']) ?></p>
    </div>

    <?php if (!empty($erreurs)): ?>
    <div class="flash-error">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= implode(' &nbsp;·&nbsp; ', array_map('htmlspecialchars', $erreurs)) ?>
    </div>
    <?php endif; ?>

    <div class="form-card">

        <!-- Aperçu image actuelle -->
        <?php if (!empty($produit['image_url'])): ?>
        <div class="mb-4 d-flex align-items-center gap-3">
            <img src="<?= htmlspecialchars($produit['image_url']) ?>"
                 alt="Aperçu" class="img-preview" id="imgPreview">
            <div style="font-size:0.78rem; color:var(--mw-muted);">
                Image actuelle<br>
                <span style="font-size:0.7rem;">L'aperçu se met à jour en temps réel.</span>
            </div>
        </div>
        <?php endif; ?>

        <!--
            L'action pointe vers la même page avec l'id en paramètre GET.
            Ainsi, le traitement POST peut lire l'id pour l'UPDATE.
        -->
        <form method="POST" action="edit_product.php?id=<?= (int)$idProduit ?>">

            <div class="mb-3">
                <label class="form-label-mw" for="id_categorie">Catégorie *</label>
                <select name="id_categorie" id="id_categorie" class="form-control-mw" required>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id_categorie'] ?>"
                            <?= (int)$produit['id_categorie'] === (int)$cat['id_categorie'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nom_categorie']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label-mw" for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" class="form-control-mw"
                       value="<?= htmlspecialchars($produit['nom']) ?>" required maxlength="150">
            </div>

            <div class="mb-3">
                <label class="form-label-mw" for="description">Description *</label>
                <textarea id="description" name="description" class="form-control-mw"
                          rows="3" required><?= htmlspecialchars($produit['description']) ?></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label-mw" for="prix">Prix (DT) *</label>
                    <input type="number" id="prix" name="prix" class="form-control-mw"
                           step="0.01" min="0.01"
                           value="<?= htmlspecialchars($produit['prix']) ?>" required>
                </div>
                <div class="col-sm-6">
                    <label class="form-label-mw" for="stock">Stock *</label>
                    <input type="number" id="stock" name="stock" class="form-control-mw"
                           min="0" value="<?= htmlspecialchars($produit['stock']) ?>" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label-mw" for="image_url">URL de l'image</label>
                <input type="url" id="image_url" name="image_url" class="form-control-mw"
                       value="<?= htmlspecialchars($produit['image_url'] ?? '') ?>"
                       oninput="updatePreview(this.value)">
                <p class="hint">Laissez vide pour supprimer l'image.</p>
            </div>

            <div class="d-flex gap-3">
                <button type="submit" class="btn-mw btn-mw-white">
                    <i class="bi bi-check2"></i> Enregistrer
                </button>
                <a href="products.php" class="btn-mw btn-mw-ghost">Annuler</a>
            </div>

        </form>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Met à jour l'aperçu de l'image en temps réel quand l'URL change
function updatePreview(url) {
    const img = document.getElementById('imgPreview');
    if (img) img.src = url;
}
</script>
</body>
</html>
