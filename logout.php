<?php
/**
 * logout.php
 * Déconnecte l'utilisateur en détruisant sa session, puis le redirige vers login.php.
 */

// session_start() est obligatoire pour accéder à une session existante
// avant de pouvoir la détruire.
session_start();

// 1. Vider le tableau $_SESSION (supprime toutes les données en mémoire)
$_SESSION = [];

// 2. Supprimer le cookie de session côté navigateur
//    Sans cela, le cookie PHPSESSID reste présent mais ne correspond plus à rien.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// 3. Détruire la session côté serveur (supprime le fichier de session)
session_destroy();

// 4. Rediriger vers la page de connexion
header('Location: login.php');
exit;
