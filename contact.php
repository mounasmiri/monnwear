<?php
/**
 * contact.php
 * Page de contact Monnwear.
 *
 * Fonctionnement :
 *  - GET  : affiche le formulaire de contact.
 *  - POST : valide les données, simule l'envoi (affiche un message de succès).
 *           Dans un projet réel, on utiliserait mail() ou une lib comme PHPMailer.
 */

session_start();
require_once 'includes/db_connect.php';

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantite')) : 0;

$erreurs = [];
$succes  = false;
$old     = ['nom' => '', 'email' => '', 'sujet' => '', 'message' => ''];

// ----------------------------------------------------------------
// TRAITEMENT DU FORMULAIRE (POST)
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom     = trim($_POST['nom']     ?? '');
    $email   = trim($_POST['email']   ?? '');
    $sujet   = trim($_POST['sujet']   ?? '');
    $message = trim($_POST['message'] ?? '');

    // Conservation pour pré-remplissage en cas d'erreur
    $old = compact('nom', 'email', 'sujet', 'message');

    // ---- VALIDATION ----
    if (empty($nom))                                             $erreurs[] = 'Votre nom est requis.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Adresse email invalide.';
    if (empty($sujet))                                           $erreurs[] = 'Le sujet est requis.';
    if (empty($message) || mb_strlen($message) < 10)            $erreurs[] = 'Le message doit contenir au moins 10 caractères.';

    if (empty($erreurs)) {
        /*
         * Dans un projet réel, on enverrait un email ici avec mail() :
         *
         *   mail('contact@monnwear.tn', $sujet,
         *        "De : $nom <$email>\n\n$message");
         *
         * Pour ce projet, on simule simplement le succès.
         */
        $succes = true;
        $old    = ['nom' => '', 'email' => '', 'sujet' => '', 'message' => ''];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact — Monnwear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --mw-black:#0a0a0a; --mw-dark:#111111; --mw-card:#1a1a1a;
            --mw-border:#2a2a2a; --mw-white:#f5f5f5; --mw-muted:#888888;
        }
        * { box-sizing:border-box; }
        body { background-color:var(--mw-black); color:var(--mw-white); font-family:'Space Grotesk',sans-serif; margin:0; }

        /* NAVBAR */
        .navbar-mw { background-color:var(--mw-dark); border-bottom:1px solid var(--mw-border); padding:1rem 0; }
        .navbar-brand-mw { font-size:1.5rem; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:var(--mw-white) !important; text-decoration:none; }
        .navbar-mw .nav-link { color:var(--mw-muted) !important; font-size:0.85rem; letter-spacing:0.08em; text-transform:uppercase; font-weight:500; transition:color 0.2s; }
        .navbar-mw .nav-link:hover { color:var(--mw-white) !important; }
        .cart-icon { font-size:1.3rem; color:var(--mw-muted); transition:color 0.2s; text-decoration:none; }
        .cart-icon:hover { color:var(--mw-white); }
        .navbar-toggler { border-color:var(--mw-border); }
        .navbar-toggler-icon { filter:invert(1); }

        /* EN-TÊTE */
        .page-header {
            background-color: var(--mw-dark);
            border-bottom: 1px solid var(--mw-border);
            padding: 3.5rem 0 2.5rem;
        }
        .eyebrow { font-size:0.72rem; letter-spacing:0.25em; text-transform:uppercase; color:var(--mw-muted); margin-bottom:0.6rem; }
        .page-title { font-size:2.5rem; font-weight:700; letter-spacing:-0.02em; margin:0; }

        /* SECTION */
        .contact-section { padding:5rem 0 7rem; }

        /* CARTES INFO */
        .info-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            padding: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
            transition: border-color 0.2s;
        }
        .info-card:hover { border-color: #3a3a3a; }
        .info-icon {
            width: 42px; height: 42px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--mw-border);
            border-radius: 3px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .info-label { font-size:0.68rem; letter-spacing:0.15em; text-transform:uppercase; color:var(--mw-muted); margin-bottom:0.25rem; }
        .info-value { font-size:0.9rem; font-weight:500; color:var(--mw-white); }

        /* HORAIRES */
        .hours-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        .hours-title { font-size:0.68rem; letter-spacing:0.15em; text-transform:uppercase; color:var(--mw-muted); margin-bottom:1rem; }
        .hours-row { display:flex; justify-content:space-between; font-size:0.84rem; padding:0.45rem 0; border-bottom:1px solid var(--mw-border); }
        .hours-row:last-child { border-bottom:none; }
        .hours-day { color:var(--mw-muted); }
        .hours-time { color:var(--mw-white); font-weight:500; }

        /* FORMULAIRE */
        .form-card {
            background-color: var(--mw-card);
            border: 1px solid var(--mw-border);
            border-radius: 4px;
            padding: 2rem;
        }
        .form-section-title { font-size:0.7rem; letter-spacing:0.2em; text-transform:uppercase; color:var(--mw-muted); margin-bottom:1.5rem; }
        .form-label-mw { font-size:0.72rem; letter-spacing:0.12em; text-transform:uppercase; color:var(--mw-muted); display:block; margin-bottom:0.4rem; }
        .form-control-mw { width:100%; background:#111; border:1px solid var(--mw-border); color:var(--mw-white); padding:0.75rem 1rem; font-size:0.88rem; font-family:inherit; border-radius:2px; transition:border-color 0.2s; }
        .form-control-mw:focus { outline:none; border-color:var(--mw-white); }
        .form-control-mw::placeholder { color:var(--mw-muted); }

        /* FLASH */
        .flash-success { background:rgba(74,222,128,0.1); border:1px solid rgba(74,222,128,0.3); color:#4ade80; padding:1rem 1.25rem; border-radius:2px; font-size:0.85rem; margin-bottom:1.5rem; }
        .flash-error   { background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.3); color:#f87171; padding:1rem 1.25rem; border-radius:2px; font-size:0.85rem; margin-bottom:1.5rem; }

        /* BOUTON */
        .btn-submit {
            background-color: var(--mw-white);
            color: var(--mw-black);
            border: none;
            padding: 0.85rem 2rem;
            font-size:0.82rem;
            font-weight:700;
            letter-spacing:0.12em;
            text-transform:uppercase;
            border-radius:2px;
            cursor:pointer;
            font-family:inherit;
            transition:background-color 0.2s;
        }
        .btn-submit:hover { background-color:#e0e0e0; }

        /* FOOTER */
        .footer-mw { background-color:var(--mw-dark); border-top:1px solid var(--mw-border); padding:2rem 0; }
        .footer-mw p { color:var(--mw-muted); font-size:0.8rem; letter-spacing:0.05em; margin:0; }
    </style>
</head>
<body>

<?php $activeLink = ''; require 'includes/navbar.php'; ?>

<!-- EN-TÊTE -->
<div class="page-header">
    <div class="container">
        <p class="eyebrow">
            <a href="index.php" style="color:inherit;text-decoration:none;">Accueil</a>
            &nbsp;/&nbsp; Contact
        </p>
        <h1 class="page-title">Contactez-nous</h1>
    </div>
</div>

<!-- CONTENU PRINCIPAL -->
<section class="contact-section">
    <div class="container">
        <div class="row g-5">

            <!-- ====================================================
                 COLONNE GAUCHE : infos de contact
            ==================================================== -->
            <div class="col-lg-4">

                <!-- Email -->
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-envelope"></i></div>
                    <div>
                        <div class="info-label">Email</div>
                        <div class="info-value">contact@monnwear.tn</div>
                    </div>
                </div>

                <!-- Téléphone -->
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-telephone"></i></div>
                    <div>
                        <div class="info-label">Téléphone</div>
                        <div class="info-value">+216 70 000 000</div>
                    </div>
                </div>

                <!-- Adresse -->
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-geo-alt"></i></div>
                    <div>
                        <div class="info-label">Adresse</div>
                        <div class="info-value">Rue de la Mode, Tunis, Tunisie</div>
                    </div>
                </div>

                <!-- Suivi commande -->
                <div class="info-card" style="border-color:rgba(255,255,255,0.08);">
                    <div class="info-icon"><i class="bi bi-bag-check"></i></div>
                    <div>
                        <div class="info-label">Suivi de commande</div>
                        <div class="info-value" style="font-size:0.84rem;">
                            <a href="track_order.php"
                               style="color:var(--mw-muted);text-decoration:underline;transition:color 0.2s;"
                               onmouseover="this.style.color='#f5f5f5'"
                               onmouseout="this.style.color='#888'">
                                Suivre ma commande →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Horaires -->
                <div class="hours-card">
                    <div class="hours-title">Horaires du service client</div>
                    <div class="hours-row">
                        <span class="hours-day">Lun — Ven</span>
                        <span class="hours-time">9h — 18h</span>
                    </div>
                    <div class="hours-row">
                        <span class="hours-day">Samedi</span>
                        <span class="hours-time">10h — 14h</span>
                    </div>
                    <div class="hours-row">
                        <span class="hours-day">Dimanche</span>
                        <span class="hours-time" style="color:var(--mw-muted);">Fermé</span>
                    </div>
                </div>

            </div><!-- /.col-lg-4 -->

            <!-- ====================================================
                 COLONNE DROITE : formulaire de contact
            ==================================================== -->
            <div class="col-lg-8">
                <div class="form-card">

                    <p class="form-section-title">Envoyer un message</p>

                    <!-- Message de succès -->
                    <?php if ($succes): ?>
                    <div class="flash-success">
                        <i class="bi bi-check2-circle me-2"></i>
                        Votre message a bien été envoyé. Nous vous répondrons sous 24h.
                    </div>
                    <?php endif; ?>

                    <!-- Erreurs de validation -->
                    <?php if (!empty($erreurs)): ?>
                    <div class="flash-error">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= implode(' &nbsp;·&nbsp; ', array_map('htmlspecialchars', $erreurs)) ?>
                    </div>
                    <?php endif; ?>

                    <!--
                        method="POST" : les données sont envoyées dans le corps
                        de la requête HTTP, pas dans l'URL.
                        action="" : la même page traite le formulaire (self-processing form).
                    -->
                    <form method="POST" action="">

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label-mw" for="nom">Nom complet *</label>
                                <input type="text" id="nom" name="nom" class="form-control-mw"
                                       placeholder="Ahmed Ben Ali"
                                       value="<?= htmlspecialchars($old['nom']) ?>"
                                       required maxlength="100">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label-mw" for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control-mw"
                                       placeholder="ahmed@email.com"
                                       value="<?= htmlspecialchars($old['email']) ?>"
                                       required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-mw" for="sujet">Sujet *</label>
                            <!--
                                Le <select> permet de catégoriser le message
                                (commande, produit, autre) pour faciliter le traitement.
                            -->
                            <select id="sujet" name="sujet" class="form-control-mw" required>
                                <option value="">— Choisir un sujet —</option>
                                <option value="Suivi de commande"  <?= $old['sujet'] === 'Suivi de commande'  ? 'selected' : '' ?>>Suivi de commande</option>
                                <option value="Retour / Échange"   <?= $old['sujet'] === 'Retour / Échange'   ? 'selected' : '' ?>>Retour / Échange</option>
                                <option value="Question produit"   <?= $old['sujet'] === 'Question produit'   ? 'selected' : '' ?>>Question sur un produit</option>
                                <option value="Problème technique"  <?= $old['sujet'] === 'Problème technique'  ? 'selected' : '' ?>>Problème technique</option>
                                <option value="Autre"              <?= $old['sujet'] === 'Autre'              ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-mw" for="message">Message *</label>
                            <textarea id="message" name="message" class="form-control-mw"
                                      rows="6"
                                      placeholder="Décrivez votre demande en détail..."
                                      required
                                      minlength="10"><?= htmlspecialchars($old['message']) ?></textarea>
                        </div>

                        <div class="d-flex align-items-center gap-4 flex-wrap">
                            <button type="submit" class="btn-submit">
                                <i class="bi bi-send me-2"></i> Envoyer le message
                            </button>
                            <p style="font-size:0.72rem;color:var(--mw-muted);margin:0;">
                                Réponse garantie sous 24h ouvrées.
                            </p>
                        </div>

                    </form>
                </div>
            </div><!-- /.col-lg-8 -->

        </div><!-- /.row -->
    </div><!-- /.container -->
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
