-- StockPilot — schéma de base de données
-- À importer dans phpMyAdmin (XAMPP) : crée la base et la table des utilisateurs.
-- Le compte administrateur (rayenyoussef) est créé automatiquement au premier
-- accès à install.php, avec un mot de passe correctement haché par PHP.

CREATE DATABASE IF NOT EXISTS stockpilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stockpilot;

CREATE TABLE IF NOT EXISTS utilisateurs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('Administrateur','Responsable Achats','Responsable Service') NOT NULL,
  service VARCHAR(50) NOT NULL,
  statut ENUM('Actif','Inactif') NOT NULL DEFAULT 'Actif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Après avoir importé ce fichier, ouvrez install.php dans votre navigateur
-- (http://localhost/stockpilot/install.php) pour créer le compte
-- administrateur rayenyoussef / 123456 avec un mot de passe haché correctement.

CREATE TABLE IF NOT EXISTS articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  nom VARCHAR(150) NOT NULL,
  cat VARCHAR(50) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  min INT NOT NULL DEFAULT 0,
  max INT NOT NULL DEFAULT 0,
  emp VARCHAR(30) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fournisseurs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS demandes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  num VARCHAR(30) NOT NULL UNIQUE,
  service VARCHAR(50) NOT NULL,
  demandeur VARCHAR(100) NOT NULL,
  date_demande VARCHAR(20) NOT NULL,
  statut ENUM('attente','validee','refusee') NOT NULL DEFAULT 'attente',
  nb_articles INT NOT NULL DEFAULT 0,
  qte_totale INT NOT NULL DEFAULT 0,
  notif_lue TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migration pour une base déjà existante (ne fait rien si la colonne existe déjà)
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS notif_lue TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER demandeur;

CREATE TABLE IF NOT EXISTS demande_lignes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  demande_num VARCHAR(30) NOT NULL,
  article_id INT NOT NULL,
  article_nom VARCHAR(150) NOT NULL,
  qte INT NOT NULL,
  FOREIGN KEY (demande_num) REFERENCES demandes(num) ON DELETE CASCADE,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS commandes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  num VARCHAR(30) NOT NULL UNIQUE,
  fournisseur VARCHAR(150) NOT NULL,
  article_id INT NOT NULL,
  article_nom VARCHAR(150) NOT NULL,
  qte INT NOT NULL,
  prix_unitaire DECIMAL(10,3) NOT NULL DEFAULT 0,
  montant DECIMAL(12,3) NOT NULL DEFAULT 0,
  date_commande VARCHAR(20) NOT NULL,
  statut ENUM('attente','recue') NOT NULL DEFAULT 'attente',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (article_id) REFERENCES articles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS historique (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date_mvt VARCHAR(30) NOT NULL,
  type ENUM('entree','sortie') NOT NULL,
  art VARCHAR(150) NOT NULL,
  qte INT NOT NULL,
  user VARCHAR(100) NOT NULL,
  motif VARCHAR(255) NOT NULL DEFAULT '',
  ref_num VARCHAR(30) NULL,
  ref_type ENUM('demande','commande') NULL,
  service VARCHAR(100) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migration pour une base déjà existante (ne fait rien si les colonnes existent déjà)
ALTER TABLE historique ADD COLUMN IF NOT EXISTS ref_num VARCHAR(30) NULL AFTER motif;
ALTER TABLE historique ADD COLUMN IF NOT EXISTS ref_type ENUM('demande','commande') NULL AFTER ref_num;
ALTER TABLE historique ADD COLUMN IF NOT EXISTS service VARCHAR(100) NOT NULL DEFAULT '' AFTER ref_type;

-- Migration pour une base déjà existante : la fonctionnalité "Accès produits" a été
-- retirée, tous les utilisateurs ont désormais accès à tous les articles.
DROP TABLE IF EXISTS acces_produits;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plafonds_achats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  article_id INT NOT NULL,
  qte_max INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_article (user_id, article_id),
  FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migration pour une base déjà existante : trace qui a créé chaque commande,
-- nécessaire pour calculer la quantité déjà commandée par un compte "achats" plafonné.
ALTER TABLE commandes ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER fournisseur;

-- Historique des alertes : trace chaque fois qu'un mouvement de stock laisse un article
-- en dessous (ou à) son seuil minimum, même si l'alerte a ensuite été résolue (ex :
-- réapprovisionnement). Permet de consulter l'historique dans la page "Alertes", pas
-- seulement l'état actuel.
CREATE TABLE IF NOT EXISTS alertes_historique (
  id INT AUTO_INCREMENT PRIMARY KEY,
  article_id INT NOT NULL,
  article_nom VARCHAR(150) NOT NULL,
  cat VARCHAR(50) NOT NULL DEFAULT '',
  stock INT NOT NULL,
  min_seuil INT NOT NULL,
  niveau ENUM('bas','critique') NOT NULL,
  date_alerte VARCHAR(30) NOT NULL,
  motif VARCHAR(255) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Valeurs de départ (n'écrase rien si elles existent déjà)
INSERT IGNORE INTO categories (nom) VALUES ('Informatique'),('Bureautique'),('Impression'),('Réseau');
INSERT IGNORE INTO services (nom) VALUES ('Direction'),('Achats'),('Informatique'),('Comptabilité'),('RH'),('Marketing');
