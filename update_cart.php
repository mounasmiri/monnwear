<?php
/**
 * update_cart.php
 * Met à jour la quantité d'un article dans le panier.
 * Reçoit les données via POST depuis cart.php.
 * Aucun affichage HTML — redirige vers cart.php après traitement.
 *
 * Flux : cart.php (formulaire) → POST → update_cart.php → cart.php
 */

session_start();

// Bloquer l'accès direct (non-POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit();
}


// ----------------------------------------------------------------
// MISE À JOUR DE LA QUANTITÉ
// ----------------------------------------------------------------
/*
 * On récupère l'id et la nouvelle quantité depuis $_POST.
 * (int) pour sécuriser les deux valeurs.
 */
$idProduit = isset($_POST['id'])       ? (int)$_POST['id']       : 0;
$quantite  = isset($_POST['quantite']) ? (int)$_POST['quantite'] : 0;

if ($idProduit > 0 && isset($_SESSION['cart'][$idProduit])) {

    if ($quantite <= 0) {
        /*
         * Quantité nulle ou négative → on supprime l'article du panier.
         * Cela permet de retirer un article en mettant la quantité à 0.
         */
        unset($_SESSION['cart'][$idProduit]);

    } else {
        /*
         * On plafonne la quantité au stock maximum disponible.
         * stock_max a été stocké dans la session lors de l'ajout (add_to_cart.php)
         * pour éviter une requête BDD supplémentaire ici.
         * min(a, b) retourne le plus petit des deux — empêche de commander plus que le stock.
         */
        $stockMax = $_SESSION['cart'][$idProduit]['stock_max'];
        $_SESSION['cart'][$idProduit]['quantite'] = min($quantite, $stockMax);
    }
}

header('Location: cart.php');
exit();
?>
