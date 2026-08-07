-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2026 at 01:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `monnwear_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id_categorie` int(11) NOT NULL,
  `nom_categorie` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id_categorie`, `nom_categorie`) VALUES
(1, 'Hoodies'),
(2, 'T-shirts'),
(3, 'Pantalons'),
(4, 'Vestes'),
(5, 'Accessoires');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id_client` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `adresse` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id_client`, `nom`, `email`, `adresse`) VALUES
(1, 'mouna', 'mounasmiri9@gmail.com', 'soukra'),
(2, 'moemen', 'mounasmiri9@email.com', 'ariena'),
(3, 'dali', 'dali@email.com', 'isi'),
(4, 'rayen', 'rayen@gmail.com', 'isi ariana');

-- --------------------------------------------------------

--
-- Table structure for table `commandes`
--

CREATE TABLE `commandes` (
  `id_commande` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `date_commande` datetime NOT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'En attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commandes`
--

INSERT INTO `commandes` (`id_commande`, `id_client`, `date_commande`, `statut`) VALUES
(1, 1, '2026-05-06 00:00:20', 'En attente'),
(2, 2, '2026-05-06 09:59:53', 'En attente'),
(3, 1, '2026-05-06 10:11:01', 'En attente'),
(4, 1, '2026-05-06 11:00:43', 'En attente'),
(5, 3, '2026-05-06 11:35:36', 'En attente'),
(6, 4, '2026-05-06 11:43:31', 'En attente');

-- --------------------------------------------------------

--
-- Table structure for table `details_commande`
--

CREATE TABLE `details_commande` (
  `id_commande` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `details_commande`
--

INSERT INTO `details_commande` (`id_commande`, `id_produit`, `quantite`) VALUES
(1, 2, 2),
(2, 2, 1),
(3, 2, 1),
(4, 2, 4),
(4, 3, 10),
(5, 1, 1),
(6, 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `expeditions`
--

CREATE TABLE `expeditions` (
  `id_expedition` int(11) NOT NULL,
  `id_commande` int(11) NOT NULL,
  `date_expedition` date DEFAULT NULL,
  `adresse_livraison` text DEFAULT NULL,
  `statut_livraison` varchar(50) NOT NULL DEFAULT 'En attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expeditions`
--

INSERT INTO `expeditions` (`id_expedition`, `id_commande`, `date_expedition`, `adresse_livraison`, `statut_livraison`) VALUES
(1, 1, NULL, 'soukra', 'En attente'),
(2, 2, NULL, 'ariena', 'En attente'),
(3, 3, NULL, 'ezahra', 'En attente'),
(4, 4, NULL, 'soukra', 'En attente'),
(5, 5, NULL, 'isi', 'En attente'),
(6, 6, NULL, 'isi ariana', 'En attente');

-- --------------------------------------------------------

--
-- Table structure for table `produits`
--

CREATE TABLE `produits` (
  `id_produit` int(11) NOT NULL,
  `id_categorie` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produits`
--

INSERT INTO `produits` (`id_produit`, `id_categorie`, `nom`, `description`, `prix`, `stock`, `image_url`) VALUES
(1, 1, 'Monnwear Essential Hoodie', 'Sweat à capuche épais, coupe oversize avec logo brodé central.', 120.00, 4, 'https://calienteclo.com/wp-content/uploads/2025/11/25.png'),
(2, 2, 'Classic Signature Tee', 'T-shirt 100% coton premium, logo minimaliste sur la poitrine.', 55.00, 22, 'https://calienteclo.com/wp-content/uploads/2025/08/29-800x1000.png'),
(3, 3, 'Le Sweatpants  Everyday', 'Pantalon cargo utilitaire avec poches latérales, noir mat.', 145.00, 10, 'https://calienteclo.com/wp-content/uploads/2025/11/18.png\r\n'),
(4, 2, 'Vintage Washed Tee', 'T-shirt effet délavé gris foncé, coupe ample pour un style urbain.', 65.00, 20, 'https://calienteclo.com/wp-content/uploads/2025/07/21.png'),
(5, 4, 'Monnwear Track Jacket', 'Veste zippée légère avec bandes latérales, parfaite pour la mi-saison.', 160.00, 100, 'https://calienteclo.com/wp-content/uploads/2026/02/110-800x1000.png'),
(6, 5, 'Everyday Beanie', 'La Casquette NFTM – black avec étiquette Monnwear.', 35.00, 24, 'https://calienteclo.com/wp-content/uploads/2024/09/124.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_categorie`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id_client`);

--
-- Indexes for table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id_commande`),
  ADD KEY `id_client` (`id_client`);

--
-- Indexes for table `details_commande`
--
ALTER TABLE `details_commande`
  ADD PRIMARY KEY (`id_commande`,`id_produit`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Indexes for table `expeditions`
--
ALTER TABLE `expeditions`
  ADD PRIMARY KEY (`id_expedition`),
  ADD KEY `id_commande` (`id_commande`);

--
-- Indexes for table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id_produit`),
  ADD KEY `id_categorie` (`id_categorie`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id_client` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id_commande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `expeditions`
--
ALTER TABLE `expeditions`
  MODIFY `id_expedition` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `produits`
--
ALTER TABLE `produits`
  MODIFY `id_produit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`);

--
-- Constraints for table `details_commande`
--
ALTER TABLE `details_commande`
  ADD CONSTRAINT `details_commande_ibfk_1` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`),
  ADD CONSTRAINT `details_commande_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`);

--
-- Constraints for table `expeditions`
--
ALTER TABLE `expeditions`
  ADD CONSTRAINT `expeditions_ibfk_1` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`);

--
-- Constraints for table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categories` (`id_categorie`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
