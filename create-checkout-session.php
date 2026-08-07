<?php
/**
 * create-checkout-session.php
 * Crée une session de paiement Stripe via l'API REST (sans Composer / SDK).
 *
 * Flux complet :
 *   cart.php  →  [clic "Payer avec Stripe"]
 *   →  ce fichier (calcule le panier, appelle l'API Stripe via cURL)
 *   →  Stripe Checkout (le client saisit sa carte sur stripe.com)
 *   →  success.php  (paiement accepté)
 *   →  cancel.php   (paiement annulé)
 */

session_start();

// Panier vide → retour au panier
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// ================================================================
// CONFIGURATION — NE PAS COMMITTER CES CLÉS DANS GIT !
// En production, les stocker dans des variables d'environnement
// (ex : putenv() ou un fichier .env hors du dossier public).
// ================================================================
define('STRIPE_SECRET_KEY', 'sk_test_51TU9VNBBmnJbipw3lTIHFIkbGLv708TVyXYnNANuvKFVhbww8WcZU0MbLgm42EaFYodEhNmtKAYrOSRYLDjywS7A00XPhiPfCn');
define('STRIPE_CHECKOUT_URL', 'https://api.stripe.com/v1/checkout/sessions');

// ----------------------------------------------------------------
// ÉTAPE 1 — URL DE BASE (pour success_url et cancel_url)
// ----------------------------------------------------------------
// On reconstruit dynamiquement l'URL racine du projet pour ne pas
// avoir à la coder en dur (fonctionne en local comme en production).
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl  = $protocol . '://' . $_SERVER['HTTP_HOST']
           . rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');

// ----------------------------------------------------------------
// ÉTAPE 2 — CONSTRUCTION DES LINE ITEMS (articles du panier)
// ----------------------------------------------------------------
/*
 * L'API Stripe attend un tableau "line_items" où chaque article est
 * décrit par :
 *   - price_data.currency      : devise ISO (eur, usd…)
 *   - price_data.unit_amount   : prix en CENTIMES (ex: 89.90€ → 8990)
 *   - price_data.product_data  : nom (et optionnellement images)
 *   - quantity                 : nombre d'unités commandées
 *
 * Note : Stripe ne supporte pas le Dinar Tunisien (TND).
 * Ce projet utilise EUR. En production, ajuster selon la devise acceptée.
 *
 * Structure de $_SESSION['cart'] :
 *   [id_produit => ['nom' => '...', 'prix' => 89.90, 'quantite' => 2, ...]]
 */
$lineItems = [];

foreach ($_SESSION['cart'] as $item) {
    $lineItems[] = [
        'price_data' => [
            'currency'     => 'eur',
            'unit_amount'  => (int) round($item['prix'] * 100), // Conversion DT → centimes EUR
            'product_data' => [
                'name' => $item['nom'],
            ],
        ],
        'quantity' => (int) $item['quantite'],
    ];
}

// ----------------------------------------------------------------
// ÉTAPE 3 — PARAMÈTRES DE LA SESSION STRIPE
// ----------------------------------------------------------------
/*
 * 'mode' = 'payment'    → paiement unique (vs 'subscription' pour les abonnements)
 * 'success_url'         → URL vers laquelle Stripe redirige après succès
 *                          {CHECKOUT_SESSION_ID} est un placeholder que Stripe
 *                          remplace automatiquement par l'ID réel de la session
 * 'cancel_url'          → URL vers laquelle Stripe redirige si l'utilisateur annule
 */
$postData = [
    'mode'                 => 'payment',
    'payment_method_types' => ['card'],
    'line_items'           => $lineItems,
    'success_url'          => $baseUrl . '/success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'           => $baseUrl . '/cancel.php',
];

// ----------------------------------------------------------------
// ÉTAPE 4 — REQUÊTE HTTP VERS L'API STRIPE VIA cURL
// ----------------------------------------------------------------
/*
 * Qu'est-ce que cURL ?
 * ─────────────────────
 * cURL (Client URL) est une bibliothèque qui permet à PHP d'envoyer
 * des requêtes HTTP vers des serveurs distants — exactement comme
 * un navigateur envoie une requête quand vous visitez un site.
 *
 * Cycle de vie d'une requête cURL :
 *   1. curl_init()       Crée un "handle" — un objet représentant la connexion
 *   2. curl_setopt()     Configure la requête (méthode, URL, headers, données…)
 *   3. curl_exec()       Déclenche l'envoi et attend la réponse du serveur
 *   4. curl_getinfo()    Lit des métadonnées sur la réponse (code HTTP, durée…)
 *   5. curl_close()      Libère les ressources mémoire
 *
 * Authentification Bearer :
 * ─────────────────────────
 * Stripe exige que chaque requête prouve son identité.
 * On passe la clé secrète dans l'en-tête HTTP "Authorization" :
 *
 *   Authorization: Bearer sk_test_...
 *
 * C'est l'équivalent d'un badge d'accès : Stripe vérifie le token et
 * sait à quel compte Stripe rattacher la session de paiement.
 *
 * Format des données : application/x-www-form-urlencoded
 * ────────────────────────────────────────────────────────
 * http_build_query() sérialise le tableau PHP en chaîne de paramètres URL :
 *   mode=payment&line_items[0][price_data][currency]=eur&...
 * C'est le même format qu'un formulaire HTML soumis en POST.
 */

$ch = curl_init(STRIPE_CHECKOUT_URL);

curl_setopt_array($ch, [

    // Retourner la réponse comme chaîne (pas l'afficher directement à l'écran)
    CURLOPT_RETURNTRANSFER => true,

    // Utiliser la méthode HTTP POST (on envoie des données, pas juste une lecture)
    CURLOPT_POST           => true,

    // Corps de la requête : tableau PHP → chaîne form-encoded
    // ex: "mode=payment&line_items%5B0%5D%5Bprice_data%5D%5Bcurrency%5D=eur"
    CURLOPT_POSTFIELDS     => http_build_query($postData),

    // En-têtes HTTP de la requête
    CURLOPT_HTTPHEADER     => [
        // Clé secrète → prouve à Stripe que la requête vient de notre compte
        'Authorization: Bearer ' . STRIPE_SECRET_KEY,
        // Type MIME des données envoyées
        'Content-Type: application/x-www-form-urlencoded',
    ],

    // Vérifier le certificat SSL de stripe.com (obligatoire en production)
    CURLOPT_SSL_VERIFYPEER => true,

    // Timeout : abandon si Stripe ne répond pas en 30 secondes
    CURLOPT_TIMEOUT        => 30,
]);

// curl_exec() envoie la requête et retourne la réponse brute (chaîne JSON)
$responseRaw = curl_exec($ch);

// Code de statut HTTP de la réponse : 200 = OK, 400/401/500 = erreur
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch); // Libère le handle cURL

// ----------------------------------------------------------------
// ÉTAPE 5 — TRAITEMENT DE LA RÉPONSE JSON
// ----------------------------------------------------------------
/*
 * Stripe répond toujours en JSON, quelle que soit la situation.
 *
 * Réponse succès (HTTP 200) :
 *   { "id": "cs_test_...", "url": "https://checkout.stripe.com/c/pay/...", ... }
 *
 * Réponse erreur (HTTP 4xx) :
 *   { "error": { "message": "Invalid API Key provided", "type": "..." } }
 *
 * json_decode($json, true) → convertit la chaîne JSON en tableau PHP associatif.
 * Le paramètre "true" force le retour d'un tableau (et non d'un objet stdClass).
 */
$response = json_decode($responseRaw, true);

if ($httpCode === 200 && isset($response['url'])) {
    // Succès : on redirige le navigateur vers la page de paiement hébergée par Stripe
    header('Location: ' . $response['url']);
    exit;
} else {
    // Erreur API Stripe : on stocke le message en session et on retourne au panier
    $errMsg = $response['error']['message'] ?? 'Une erreur est survenue avec Stripe. Réessayez.';
    $_SESSION['stripe_error'] = $errMsg;
    header('Location: cart.php');
    exit;
}
