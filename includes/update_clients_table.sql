-- =============================================================
-- update_clients_table.sql
-- Met à jour la table `clients` pour supporter le système
-- de connexion sécurisé (login.php).
--
-- À exécuter UNE SEULE FOIS dans phpMyAdmin > onglet SQL.
-- =============================================================

-- 1. Ajouter la colonne mot_de_passe (hash bcrypt — 255 chars suffisent)
--    Si la colonne existe déjà, MySQL retournera une erreur bénigne.
ALTER TABLE clients
    ADD COLUMN mot_de_passe VARCHAR(255) NOT NULL DEFAULT '' AFTER email;

-- 2. Ajouter la colonne role ('client' par défaut pour les nouveaux inscrits)
ALTER TABLE clients
    ADD COLUMN role ENUM('client', 'admin') NOT NULL DEFAULT 'client' AFTER mot_de_passe;

-- 3. Contrainte d'unicité sur l'email (empêche les doublons)
ALTER TABLE clients
    ADD UNIQUE INDEX uq_email (email);

-- =============================================================
-- NOTE : Pour insérer les comptes de test (admin + client),
-- exécutez includes/seed_test_users.php depuis votre navigateur.
-- Les mots de passe doivent être hashés par PHP (password_hash),
-- c'est pourquoi ils ne peuvent pas être générés ici en SQL pur.
-- =============================================================
