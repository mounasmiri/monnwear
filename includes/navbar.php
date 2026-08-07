<?php
/**
 * includes/navbar.php
 * Barre de navigation partagée — à inclure avec require depuis chaque page.
 *
 * Variables attendues depuis la page parente :
 *   $cartCount       (int)    — nombre d'articles dans le panier
 *   $activeLink      (string) — 'home' | 'shop' | 'collections' | 'about' | ''
 *   $showAllNavLinks (bool)   — passer false sur checkout / order_confirmation
 */

// Valeurs par défaut si la page n'a pas défini ces variables
$activeLink      = $activeLink      ?? '';
$showAllNavLinks = $showAllNavLinks ?? true;

// ----------------------------------------------------------------
// Lecture de la session — session_start() a déjà été appelé par
// la page parente, donc $_SESSION est disponible ici.
// ----------------------------------------------------------------
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin    = $isLoggedIn && ($_SESSION['user_role'] ?? '') === 'admin';
?>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-mw sticky-top">
    <div class="container">
        <a class="navbar-brand-mw" href="index.php">Monnwear</a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">

            <!-- Liens principaux centrés -->
            <ul class="navbar-nav mx-auto gap-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= $activeLink === 'home'        ? 'active-link' : '' ?>" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeLink === 'shop'        ? 'active-link' : '' ?>" href="shop.php">Shop</a>
                </li>
                <?php if ($showAllNavLinks): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeLink === 'collections' ? 'active-link' : '' ?>" href="collections.php">Collections</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeLink === 'about'       ? 'active-link' : '' ?>" href="about.php">About</a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- --------------------------------------------------------
                 Liens d'authentification (droite)
                 isset($_SESSION['user_id']) = vrai si l'utilisateur est
                 connecté (valeur posée par login.php après vérification).
                 -------------------------------------------------------- -->
            <ul class="navbar-nav gap-lg-1 align-items-lg-center me-lg-3">
                <?php if ($isLoggedIn): ?>

                    <?php if ($isAdmin): ?>
                    <!-- Lien tableau de bord — affiché uniquement si role === 'admin' -->
                    <li class="nav-item">
                        <a class="nav-link" href="admin/index.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Lien de déconnexion — appelle logout.php qui détruit la session -->
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>

                <?php else: ?>

                    <!-- Lien de connexion — visible si aucun utilisateur en session -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="bi bi-person me-1"></i>Login
                        </a>
                    </li>

                <?php endif; ?>
            </ul>

            <!-- Icône panier avec badge dynamique -->
            <a href="cart.php" class="cart-icon position-relative">
                <i class="bi bi-bag"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                      style="background:#f5f5f5; color:#0a0a0a; font-size:0.6rem;">
                    <?= $cartCount ?>
                </span>
            </a>

        </div>
    </div>
</nav>
