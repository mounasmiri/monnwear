<?php
/**
 * db_connect.php
 * Fichier de connexion à la base de données via PDO.
 * Ce fichier est inclus dans toutes les pages qui ont besoin d'accéder à la BDD.
 */

// --- Paramètres de connexion ---
// Modifiez ces valeurs selon votre configuration locale (XAMPP)
define('DB_HOST', 'localhost');      // Hôte du serveur MySQL
define('DB_NAME', 'monnwear_db');   // Nom de la base de données
define('DB_USER', 'root');          // Nom d'utilisateur MySQL
define('DB_PASS', '');              // Mot de passe MySQL (vide par défaut sur XAMPP)
define('DB_CHARSET', 'utf8mb4');    // Encodage des caractères (supporte les emojis)

// --- Création de la connexion PDO ---
try {
    // Le DSN (Data Source Name) indique à PDO comment se connecter
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    // Options PDO pour un comportement robuste et sécurisé
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lance des exceptions en cas d'erreur SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Retourne les résultats en tableaux associatifs
        PDO::ATTR_EMULATE_PREPARES   => false,                   // Utilise les vrais prepared statements natifs
    ];

    // Instanciation de l'objet PDO — représente la connexion à la BDD
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // En cas d'échec de connexion, on arrête l'exécution et on affiche un message d'erreur
    // En production, ne jamais afficher $e->getMessage() directement (sécurité)
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}
?>
