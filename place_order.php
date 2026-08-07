<?php
/**
 * place_order.php
 * Traitement et enregistrement de la commande en base de données.
 * Aucun affichage HTML — redirige vers order_confirmation.php (succès)
 * ou vers checkout.php (erreur).
 *
 * Flux complet :
 *   checkout.php (POST) → place_order.php → order_confirmation.php
 *                                         ↘ checkout.php (si erreur)
 */

session_start();
require_once 'includes/db_connect.php';


// ----------------------------------------------------------------
// 1. SÉCURITÉ : accepter uniquement les requêtes POST
// ----------------------------------------------------------------
/*
 * $_SERVER['REQUEST_METHOD'] contient la méthode HTTP de la requête.
 * Si quelqu'un accède directement à place_order.php via l'URL (GET),
 * on le redirige vers checkout.php.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit();
}


// ----------------------------------------------------------------
// 2. VÉRIFICATION DU PANIER
// ----------------------------------------------------------------
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}


// ----------------------------------------------------------------
// 3. VALIDATION DES DONNÉES DU FORMULAIRE
// ----------------------------------------------------------------
/*
 * trim() supprime les espaces en début et fin de chaîne.
 * ?? '' : valeur par défaut si le champ n'existe pas dans $_POST.
 */
$nom     = trim($_POST['nom']     ?? '');
$email   = trim($_POST['email']   ?? '');
$adresse = trim($_POST['adresse'] ?? '');

$erreurs = [];

// Validation du nom (non vide, max 150 caractères)
if (empty($nom)) {
    $erreurs[] = 'Le nom est requis.';
} elseif (mb_strlen($nom) > 150) {
    $erreurs[] = 'Le nom ne doit pas dépasser 150 caractères.';
}

/*
 * filter_var() avec FILTER_VALIDATE_EMAIL vérifie le format de l'email
 * (présence d'un @, d'un domaine, etc.).
 * C'est plus fiable qu'une regex manuelle.
 */
if (empty($email)) {
    $erreurs[] = 'L\'adresse email est requise.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erreurs[] = 'L\'adresse email n\'est pas valide.';
}

if (empty($adresse)) {
    $erreurs[] = 'L\'adresse de livraison est requise.';
}

// Si des erreurs de validation existent, on retourne à checkout.php
if (!empty($erreurs)) {
    $_SESSION['checkout_error'] = implode(' ', $erreurs);
    // On sauvegarde les valeurs saisies pour pré-remplir le formulaire
    $_SESSION['checkout_old'] = ['nom' => $nom, 'email' => $email, 'adresse' => $adresse];
    header('Location: checkout.php');
    exit();
}


// ----------------------------------------------------------------
// 4. TRANSACTION PDO
// ----------------------------------------------------------------
/*
 * Une TRANSACTION garantit que toutes les opérations SQL réussissent
 * ou qu'aucune n'est appliquée (tout ou rien).
 *
 * Sans transaction, si une insertion échoue à mi-chemin, on pourrait
 * se retrouver avec une commande sans détails, ou un stock décrémenté
 * sans commande enregistrée.
 *
 *   beginTransaction() → démarre la transaction
 *   commit()           → valide toutes les modifications
 *   rollBack()         → annule toutes les modifications depuis beginTransaction()
 */
try {
    $pdo->beginTransaction();


    // ------------------------------------------------------------
    // ÉTAPE A : Trouver ou créer le client
    // ------------------------------------------------------------
    /*
     * On cherche un client existant par son email (clé fonctionnelle unique).
     * Si trouvé → on réutilise son id_client.
     * Si non trouvé → on crée un nouveau client.
     *
     * Cette logique permet à un client de passer plusieurs commandes
     * sans recréer un compte à chaque fois.
     */
    $stmtClient = $pdo->prepare('
        SELECT id_client FROM Clients
        WHERE email = :email
        LIMIT 1
    ');
    $stmtClient->bindValue(':email', $email);
    $stmtClient->execute();
    $clientExistant = $stmtClient->fetch();

    if ($clientExistant) {
        // Client existant — on utilise son id
        $idClient = (int)$clientExistant['id_client'];
    } else {
        // Nouveau client — on l'insère dans la table Clients
        $stmtNewClient = $pdo->prepare('
            INSERT INTO Clients (nom, email, adresse)
            VALUES (:nom, :email, :adresse)
        ');
        $stmtNewClient->bindValue(':nom',     $nom);
        $stmtNewClient->bindValue(':email',   $email);
        $stmtNewClient->bindValue(':adresse', $adresse);
        $stmtNewClient->execute();

        /*
         * lastInsertId() retourne l'id auto-incrémenté généré
         * par le dernier INSERT dans la connexion PDO courante.
         */
        $idClient = (int)$pdo->lastInsertId();
    }


    // ------------------------------------------------------------
    // ÉTAPE B : Créer la commande
    // ------------------------------------------------------------
    /*
     * NOW() est une fonction MySQL qui insère la date et l'heure actuelles.
     * Le statut initial est 'En attente' (la commande vient d'être passée).
     */
    $stmtCommande = $pdo->prepare("
        INSERT INTO Commandes (id_client, date_commande, statut)
        VALUES (:id_client, NOW(), 'En attente')
    ");
    $stmtCommande->bindValue(':id_client', $idClient, PDO::PARAM_INT);
    $stmtCommande->execute();

    // Récupère l'id de la commande qui vient d'être créée
    $idCommande = (int)$pdo->lastInsertId();


    // ------------------------------------------------------------
    // ÉTAPE C : Insérer les détails + mettre à jour le stock
    // ------------------------------------------------------------
    foreach ($_SESSION['cart'] as $idProduit => $item) {

        // C1 — Insérer la ligne dans Details_Commande
        $stmtDetail = $pdo->prepare('
            INSERT INTO Details_Commande (id_commande, id_produit, quantite)
            VALUES (:id_commande, :id_produit, :quantite)
        ');
        $stmtDetail->bindValue(':id_commande', $idCommande,        PDO::PARAM_INT);
        $stmtDetail->bindValue(':id_produit',  (int)$idProduit,    PDO::PARAM_INT);
        $stmtDetail->bindValue(':quantite',    $item['quantite'],  PDO::PARAM_INT);
        $stmtDetail->execute();

        /*
         * C2 — Décrémenter le stock de manière ATOMIQUE.
         *
         * La clause "AND stock >= :qty" est cruciale :
         * elle vérifie ET décrémente en une seule opération SQL.
         * Si deux utilisateurs commandent le dernier article en même temps,
         * un seul réussira ce UPDATE (l'autre aura rowCount() = 0).
         *
         * Cela évite le problème de "stock négatif" (concurrence).
         */
        /*
         * CORRECTION : PDO interdit d'utiliser deux fois le même
         * placeholder nommé dans une requête quand EMULATE_PREPARES = false.
         * On utilise donc :qty pour SET et :stock_min pour la clause WHERE.
         * Les deux reçoivent la même valeur ($item['quantite']).
         */
        $stmtStock = $pdo->prepare('
            UPDATE Produits
            SET stock = stock - :qty
            WHERE id_produit = :id
              AND stock >= :stock_min
        ');
        $stmtStock->bindValue(':qty',       $item['quantite'], PDO::PARAM_INT);
        $stmtStock->bindValue(':stock_min', $item['quantite'], PDO::PARAM_INT);
        $stmtStock->bindValue(':id',        (int)$idProduit,  PDO::PARAM_INT);
        $stmtStock->execute();

        /*
         * rowCount() retourne le nombre de lignes affectées par le dernier UPDATE.
         * Si = 0 → le stock était insuffisant → on annule toute la transaction.
         */
        if ($stmtStock->rowCount() === 0) {
            $pdo->rollBack(); // Annule tout ce qui a été fait depuis beginTransaction()
            $_SESSION['checkout_error'] = 'Stock insuffisant pour "' .
                htmlspecialchars($item['nom']) .
                '". Veuillez ajuster votre panier.';
            $_SESSION['checkout_old'] = ['nom' => $nom, 'email' => $email, 'adresse' => $adresse];
            header('Location: checkout.php');
            exit();
        }
    }


    // ------------------------------------------------------------
    // ÉTAPE D : Créer l'expédition associée à la commande
    // ------------------------------------------------------------
    /*
     * On insère un enregistrement dans Expeditions avec :
     * - date_expedition = NULL (pas encore expédiée)
     * - statut_livraison = 'En attente'
     * - adresse_livraison = adresse fournie par le client
     */
    /*
     * On omet date_expedition de la liste des colonnes pour laisser
     * MySQL appliquer sa valeur par défaut (NULL si la colonne l'accepte).
     * Insérer explicitement NULL échoue si la colonne est définie NOT NULL.
     */
    $stmtExp = $pdo->prepare("
        INSERT INTO Expeditions (id_commande, adresse_livraison, statut_livraison)
        VALUES (:id_commande, :adresse, 'En attente')
    ");
    $stmtExp->bindValue(':id_commande', $idCommande, PDO::PARAM_INT);
    $stmtExp->bindValue(':adresse',     $adresse);
    $stmtExp->execute();


    // ------------------------------------------------------------
    // ÉTAPE E : Valider la transaction
    // ------------------------------------------------------------
    /*
     * commit() envoie définitivement toutes les modifications à MySQL.
     * Avant ce point, aucune modification n'est visible pour les autres requêtes.
     */
    $pdo->commit();


    // ----------------------------------------------------------------
    // 5. NETTOYAGE DE LA SESSION ET REDIRECTION
    // ----------------------------------------------------------------
    // On vide le panier — la commande est enregistrée en BDD
    unset($_SESSION['cart']);

    /*
     * On stocke l'id de la commande en session pour que
     * order_confirmation.php puisse l'afficher.
     */
    $_SESSION['last_order_id'] = $idCommande;

    header('Location: order_confirmation.php');
    exit();


} catch (PDOException $e) {
    // ----------------------------------------------------------------
    // GESTION D'ERREUR : rollBack et message flash
    // ----------------------------------------------------------------
    /*
     * En cas d'erreur inattendue (connexion perdue, contrainte violée, etc.),
     * on annule toute la transaction pour préserver la cohérence de la BDD.
     *
     * En production, on loguerait $e->getMessage() dans un fichier de log
     * plutôt que de l'afficher à l'utilisateur (sécurité).
     */
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['checkout_error'] = 'Une erreur technique s\'est produite. Veuillez réessayer.';
    $_SESSION['checkout_old']   = ['nom' => $nom, 'email' => $email, 'adresse' => $adresse];
    header('Location: checkout.php');
    exit();
}
