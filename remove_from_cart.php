<?php
/**
 * remove_from_cart.php
 * Supprime un article du panier (session) et redirige vers cart.php.
 * Aucun affichage HTML — page de traitement uniquement.
 *
 * Flux : cart.php → remove_from_cart.php?id=X → cart.php
 */

// session_start() obligatoire pour accéder à $_SESSION
session_start();


// ----------------------------------------------------------------
// SUPPRESSION DE L'ARTICLE
// ----------------------------------------------------------------
$idProduit = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/*
 * Double vérification avant unset() :
 * 1. L'id est un entier positif valide.
 * 2. L'article est bien présent dans le panier session.
 * Cela évite toute erreur PHP en cas d'URL forgée.
 */
if ($idProduit > 0 && isset($_SESSION['cart'][$idProduit])) {
    unset($_SESSION['cart'][$idProduit]);
}

// Redirection vers le panier (qui sera maintenant mis à jour)
header('Location: cart.php');
exit();
?>
