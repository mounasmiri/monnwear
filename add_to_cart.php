<?php
/**
 * add_to_cart.php
 * Ajoute un produit au panier (stocké en session PHP).
 * Pas d'affichage HTML — redirige après traitement.
 *
 * Flux : product.php → add_to_cart.php?id=X → product.php?id=X&added=1
 */

// session_start() DOIT être appelé AVANT tout output ET avant d'utiliser $_SESSION.
// C'est lui qui charge (ou crée) le fichier de session côté serveur.
session_start();

// Connexion PDO (pour vérifier le produit en BDD)
require_once 'includes/db_connect.php';


// ----------------------------------------------------------------
// 1. VALIDATION DE L'ID PRODUIT
// ----------------------------------------------------------------
/*
 * (int) sécurise la valeur : si ?id=abc → 0 (invalide).
 * Si l'id est invalide, on redirige vers la boutique.
 */
$idProduit = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idProduit <= 0) {
    header('Location: shop.php');
    exit();
}


// ----------------------------------------------------------------
// 2. VÉRIFICATION DU PRODUIT EN BASE DE DONNÉES
// ----------------------------------------------------------------
/*
 * On ne fait jamais confiance uniquement à la session — le stock BDD
 * est la seule source de vérité. Il peut avoir changé depuis la dernière visite.
 */
$stmt = $pdo->prepare('
    SELECT id_produit, nom, prix, stock, image_url
    FROM Produits
    WHERE id_produit = :id
    LIMIT 1
');
$stmt->bindValue(':id', $idProduit, PDO::PARAM_INT);
$stmt->execute();
$produit = $stmt->fetch();

// Produit inexistant ou en rupture de stock → retour boutique
if ($produit === false || $produit['stock'] <= 0) {
    header('Location: shop.php');
    exit();
}


// ----------------------------------------------------------------
// 3. INITIALISATION DU PANIER EN SESSION
// ----------------------------------------------------------------
/*
 * $_SESSION['cart'] est un tableau associatif indexé par id_produit :
 *
 *   $_SESSION['cart'] = [
 *       1 => ['nom' => '...', 'prix' => 120.00, 'quantite' => 2,
 *             'image_url' => '...', 'stock_max' => 15],
 *       3 => [...],
 *   ];
 *
 * Si le panier n'existe pas encore en session, on le crée vide.
 */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


// ----------------------------------------------------------------
// 4. AJOUT OU INCRÉMENTATION
// ----------------------------------------------------------------
if (isset($_SESSION['cart'][$idProduit])) {
    /*
     * Le produit est déjà dans le panier.
     * On incrémente la quantité de 1, sans dépasser le stock disponible.
     */
    $nouvelleQte = $_SESSION['cart'][$idProduit]['quantite'] + 1;

    if ($nouvelleQte <= $produit['stock']) {
        $_SESSION['cart'][$idProduit]['quantite'] = $nouvelleQte;
    }
    // Sinon on garde la quantité actuelle (stock max atteint)

} else {
    /*
     * Nouveau produit dans le panier — ajout avec quantite = 1.
     * stock_max est conservé en session pour limiter les mises à jour
     * côté cart.php sans refaire une requête BDD.
     */
    $_SESSION['cart'][$idProduit] = [
        'nom'       => $produit['nom'],
        'prix'      => (float)$produit['prix'],
        'quantite'  => 1,
        'image_url' => $produit['image_url'] ?? '',
        'stock_max' => (int)$produit['stock'],
    ];
}


// ----------------------------------------------------------------
// 5. REDIRECTION VERS LA FICHE PRODUIT
// ----------------------------------------------------------------
// Le paramètre added=1 permet à product.php d'afficher un message de succès.
header('Location: product.php?id=' . $idProduit . '&added=1');
exit();
?>
