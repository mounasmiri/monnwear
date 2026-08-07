<?php
/**
 * track_order.php
 * Suivi de commande — permet à un client de retrouver ses commandes
 * en entrant son adresse email.
 *
 * Fonctionnement :
 *  - GET  : affiche le formulaire de recherche.
 *  - POST : recherche les commandes liées à l'email fourni.
 *           Utilise un prepared statement pour sécuriser la requête.
 */

session_start();
require_once 'includes/db_connect.php';

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantite')) : 0;
$commandes  = [];
$recherche  = false;
$emailSaisi = '';


// ----------------------------------------------------------------
// TRAITEMENT DE LA RECHERCHE (POST)
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $recherche  = true;
    $emailSaisi = trim($_POST['email'] ?? '');

    // Validation basique de l'email avant d'interroger la BDD
    if (!empty($emailSaisi) && filter_var($emailSaisi, FILTER_VALIDATE_EMAIL)) {

        /*
         * Requête principale : récupère toutes les commandes du client
         * identifié par son email, avec le statut de livraison et le total.
         *
         * JOIN Expeditions pour afficher le statut_livraison.
         * SUM(prix × quantite) pour calculer le total de chaque commande.
         */
        $stmt = $pdo->prepare('
            SELECT
                cm.id_commande,
                cm.date_commande,
                cm.statut,
                cl.nom   AS client_nom,
                COALESCE(SUM(p.prix * dc.quantite), 0) AS total,
                ex.statut_livraison,
                ex.date_expedition
            FROM Commandes cm
            INNER JOIN Clients cl          ON cm.id_client  = cl.id_client
            LEFT  JOIN Details_Commande dc ON cm.id_commande = dc.id_commande
            LEFT  JOIN Produits p          ON dc.id_produit  = p.id_produit
            LEFT  JOIN Expeditions ex      ON cm.id_commande = ex.id_commande
            WHERE cl.email = :email
            GROUP BY cm.id_commande, cm.date_commande, cm.statut,
                     cl.nom, ex.statut_livraison, ex.date_expedition
            ORDER BY cm.date_commande DESC
        ');
        $stmt->bindValue(':email', $emailSaisi);
        $stmt->execute();
        $commandes = $stmt->fetchAll();
    }
}


// ----------------------------------------------------------------
// HELPER : classe CSS du badge de statut
// ----------------------------------------------------------------
function statutBadge(string $statut): string {
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
    <title>Suivi de commande — Monnwear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --mw-black:#0a0a0a; --mw-dark:#111111; --mw-card:#1a1a1a; --mw-border:#2a2a2a; --mw-white:#f5f5f5; --mw-muted:#888888; }
        * { box-sizing:border-box; }
        body { background-color:var(--mw-black); color:var(--mw-white); font-family:'Space Grotesk',sans-serif; margin:0; }

        .navbar-mw { background-color:var(--mw-dark); border-bottom:1px solid var(--mw-border); padding:1rem 0; }
        .navbar-brand-mw { font-size:1.5rem; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:var(--mw-white) !important; text-decoration:none; }
        .navbar-mw .nav-link { color:var(--mw-muted) !important; font-size:0.85rem; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; transition:color 0.2s; }
        .navbar-mw .nav-link:hover { color:var(--mw-white) !important; }
        .cart-icon { font-size:1.3rem; color:var(--mw-muted); transition:color 0.2s; text-decoration:none; }
        .cart-icon:hover { color:var(--mw-white); }
        .navbar-toggler { border-color:var(--mw-border); }
        .navbar-toggler-icon { filter:invert(1); }

        /* PAGE */
        .track-section { padding:5rem 0 7rem; }
        .page-title { font-size:2rem; font-weight:700; letter-spacing:-0.02em; margin-bottom:0.4rem; }
        .page-sub { color:var(--mw-muted); font-size:0.88rem; margin-bottom:2.5rem; }

        /* FORMULAIRE */
        .search-card { background-color:var(--mw-card); border:1px solid var(--mw-border); border-radius:4px; padding:2rem; max-width:520px; margin-bottom:3rem; }
        .form-label-mw { font-size:0.72rem; letter-spacing:0.12em; text-transform:uppercase; color:var(--mw-muted); display:block; margin-bottom:0.4rem; }
        .form-control-mw { width:100%; background:#111; border:1px solid var(--mw-border); color:var(--mw-white); padding:0.75rem 1rem; font-size:0.9rem; font-family:inherit; border-radius:2px; transition:border-color 0.2s; }
        .form-control-mw:focus { outline:none; border-color:var(--mw-white); }
        .form-control-mw::placeholder { color:var(--mw-muted); }
        .btn-search { background-color:var(--mw-white); color:var(--mw-black); border:none; padding:0.75rem 1.75rem; font-size:0.82rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; border-radius:2px; cursor:pointer; font-family:inherit; transition:background-color 0.2s; }
        .btn-search:hover { background-color:#e0e0e0; }

        /* RÉSULTATS */
        .order-card { background-color:var(--mw-card); border:1px solid var(--mw-border); border-radius:4px; padding:1.5rem; margin-bottom:1rem; }
        .order-header { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:1.25rem; padding-bottom:1rem; border-bottom:1px solid var(--mw-border); }
        .order-num { font-size:1rem; font-weight:700; }
        .order-meta { font-size:0.75rem; color:var(--mw-muted); margin-top:3px; }

        /* Badge statut */
        .badge-statut { display:inline-block; font-size:0.65rem; letter-spacing:0.08em; text-transform:uppercase; padding:4px 12px; border-radius:2px; font-weight:600; }
        .badge-attente { background:rgba(251,146,60,0.15); color:#fb923c; border:1px solid rgba(251,146,60,0.3); }
        .badge-expedie { background:rgba(96,165,250,0.15); color:#60a5fa; border:1px solid rgba(96,165,250,0.3); }
        .badge-livre   { background:rgba(74,222,128,0.15); color:#4ade80; border:1px solid rgba(74,222,128,0.3); }
        .badge-annule  { background:rgba(248,113,113,0.15); color:#f87171; border:1px solid rgba(248,113,113,0.3); }

        /* Barre de progression livraison */
        .progress-bar-track { display:flex; justify-content:space-between; position:relative; margin-top:1rem; }
        .progress-bar-track::before { content:''; position:absolute; top:10px; left:0; right:0; height:2px; background:var(--mw-border); z-index:0; }
        .progress-step { display:flex; flex-direction:column; align-items:center; gap:6px; z-index:1; flex:1; }
        .progress-dot { width:20px; height:20px; border-radius:50%; background:var(--mw-border); border:2px solid var(--mw-border); transition:all 0.3s; }
        .progress-dot.done { background:var(--mw-white); border-color:var(--mw-white); }
        .progress-dot.active { background:#4ade80; border-color:#4ade80; box-shadow:0 0 8px rgba(74,222,128,0.4); }
        .progress-label { font-size:0.62rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--mw-muted); text-align:center; }
        .progress-label.active { color:var(--mw-white); }

        /* Total */
        .order-total { font-size:1rem; font-weight:700; color:var(--mw-white); }

        /* État vide */
        .empty-state { text-align:center; padding:4rem 1rem; }
        .empty-icon { font-size:3rem; color:var(--mw-border); margin-bottom:1rem; }

        /* FOOTER */
        .footer-mw { background-color:var(--mw-dark); border-top:1px solid var(--mw-border); padding:2rem 0; }
        .footer-mw p { color:var(--mw-muted); font-size:0.8rem; letter-spacing:0.05em; margin:0; }
    </style>
</head>
<body>

<?php $activeLink = ''; require 'includes/navbar.php'; ?>

<!-- CONTENU -->
<section class="track-section">
    <div class="container" style="max-width:780px;">

        <p style="font-size:0.72rem;letter-spacing:0.2em;text-transform:uppercase;color:var(--mw-muted);margin-bottom:0.5rem;">
            <a href="index.php" style="color:inherit;text-decoration:none;">Accueil</a> &nbsp;/&nbsp; Suivi de commande
        </p>
        <h1 class="page-title">Suivi de commande</h1>
        <p class="page-sub">Entrez votre adresse email pour retrouver l'historique de vos commandes.</p>

        <!-- FORMULAIRE DE RECHERCHE -->
        <div class="search-card">
            <form method="POST" action="track_order.php">
                <label class="form-label-mw" for="email">Votre adresse email</label>
                <div class="d-flex gap-3 align-items-end mt-1">
                    <input type="email"
                           id="email"
                           name="email"
                           class="form-control-mw"
                           placeholder="ahmed@email.com"
                           value="<?= htmlspecialchars($emailSaisi) ?>"
                           required>
                    <button type="submit" class="btn-search" style="white-space:nowrap;">
                        <i class="bi bi-search me-1"></i> Rechercher
                    </button>
                </div>
            </form>
        </div>

        <!-- RÉSULTATS -->
        <?php if ($recherche): ?>

            <?php if (empty($commandes)): ?>
            <!-- Aucune commande trouvée -->
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                <h3 style="font-size:1.1rem;font-weight:600;margin-bottom:0.5rem;">Aucune commande trouvée</h3>
                <p style="color:var(--mw-muted);font-size:0.85rem;">
                    Aucune commande n'est associée à l'adresse
                    <strong style="color:var(--mw-white);"><?= htmlspecialchars($emailSaisi) ?></strong>.
                </p>
                <a href="shop.php" style="color:var(--mw-muted);font-size:0.82rem;text-decoration:underline;">
                    Commencer à shopper →
                </a>
            </div>

            <?php else: ?>
            <!-- Commandes trouvées -->
            <p style="font-size:0.8rem;color:var(--mw-muted);margin-bottom:1.5rem;">
                <?= count($commandes) ?> commande<?= count($commandes) > 1 ? 's' : '' ?> trouvée<?= count($commandes) > 1 ? 's' : '' ?>
                pour <strong style="color:var(--mw-white);"><?= htmlspecialchars($emailSaisi) ?></strong>
            </p>

            <?php foreach ($commandes as $cmd):
                $statut = $cmd['statut_livraison'] ?? $cmd['statut'];

                /*
                 * Calcul de l'étape active dans la barre de progression.
                 * 0 = En attente | 1 = Expédié | 2 = Livré | -1 = Annulé
                 */
                $etape = match($statut) {
                    'Expédié' => 1,
                    'Livré'   => 2,
                    'Annulé'  => -1,
                    default   => 0,
                };
            ?>
            <div class="order-card">

                <!-- En-tête de la commande -->
                <div class="order-header">
                    <div>
                        <div class="order-num">
                            Commande #<?= str_pad($cmd['id_commande'], 5, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div class="order-meta">
                            Passée le <?= date('d/m/Y à H:i', strtotime($cmd['date_commande'])) ?>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <span class="badge-statut <?= statutBadge($statut) ?>"><?= htmlspecialchars($statut) ?></span>
                        <div class="order-total mt-2">
                            <?= number_format($cmd['total'], 2, ',', ' ') ?>&nbsp;DT
                        </div>
                    </div>
                </div>

                <?php if ($etape >= 0): ?>
                <!-- Barre de progression de livraison (masquée si Annulé) -->
                <div class="progress-bar-track">

                    <!-- Étape 1 : Commande confirmée -->
                    <div class="progress-step">
                        <div class="progress-dot <?= $etape >= 0 ? 'done' : '' ?>"></div>
                        <span class="progress-label <?= $etape === 0 ? 'active' : '' ?>">Confirmée</span>
                    </div>

                    <!-- Étape 2 : Expédiée -->
                    <div class="progress-step">
                        <div class="progress-dot <?= $etape >= 1 ? ($etape === 1 ? 'active' : 'done') : '' ?>"></div>
                        <span class="progress-label <?= $etape === 1 ? 'active' : '' ?>">Expédiée</span>
                    </div>

                    <!-- Étape 3 : Livrée -->
                    <div class="progress-step">
                        <div class="progress-dot <?= $etape >= 2 ? 'active' : '' ?>"></div>
                        <span class="progress-label <?= $etape === 2 ? 'active' : '' ?>">Livrée</span>
                    </div>

                </div>

                <?php if ($etape === 1 && !empty($cmd['date_expedition'])): ?>
                <p style="font-size:0.75rem;color:var(--mw-muted);margin-top:1rem;margin-bottom:0;">
                    <i class="bi bi-truck me-1"></i>
                    Expédiée le <?= date('d/m/Y', strtotime($cmd['date_expedition'])) ?>
                </p>
                <?php endif; ?>

                <?php else: ?>
                <!-- Statut Annulé -->
                <p style="font-size:0.82rem;color:#f87171;margin:0;">
                    <i class="bi bi-x-circle me-1"></i> Cette commande a été annulée.
                </p>
                <?php endif; ?>

            </div><!-- /.order-card -->
            <?php endforeach; ?>

            <?php endif; ?>
        <?php endif; ?>

    </div>
</section>

<!-- FOOTER -->
<footer class="footer-mw">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <p>© <?= date('Y') ?> Monnwear. Tous droits réservés.</p>
        <p style="letter-spacing:0.2em;font-size:0.7rem;">WEAR THE SILENCE.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
