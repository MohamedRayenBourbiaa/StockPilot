<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/html; charset=utf-8');

$pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('Administrateur','Responsable Achats','Responsable Service') NOT NULL,
  service VARCHAR(50) NOT NULL,
  statut ENUM('Actif','Inactif') NOT NULL DEFAULT 'Actif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  nom VARCHAR(150) NOT NULL,
  cat VARCHAR(50) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  min INT NOT NULL DEFAULT 0,
  max INT NOT NULL DEFAULT 0,
  emp VARCHAR(30) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS fournisseurs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS demandes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  num VARCHAR(30) NOT NULL UNIQUE,
  service VARCHAR(50) NOT NULL,
  demandeur VARCHAR(100) NOT NULL,
  date_demande VARCHAR(20) NOT NULL,
  statut ENUM('attente','validee','refusee') NOT NULL DEFAULT 'attente',
  nb_articles INT NOT NULL DEFAULT 0,
  qte_totale INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS historique (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date_mvt VARCHAR(30) NOT NULL,
  type ENUM('entree','sortie') NOT NULL,
  art VARCHAR(150) NOT NULL,
  qte INT NOT NULL,
  user VARCHAR(100) NOT NULL,
  motif VARCHAR(255) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM utilisateurs WHERE username = 'rayenyoussef'");
$stmt->execute();
$exists = (int) $stmt->fetch()['c'] > 0;

if (!$exists) {
    $hash = password_hash('123456', PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO utilisateurs (nom, username, email, password, role, service, statut) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $ins->execute(['Rayen Youssef', 'rayenyoussef', 'rayenyoussef@entreprise.com', $hash, 'Administrateur', 'Direction', 'Actif']);
    $msg = "Installation terminée. Compte administrateur créé : <b>rayenyoussef</b> / <b>123456</b>";
} else {
    $msg = "La base est déjà installée, le compte administrateur existe déjà.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Installation StockPilot</title>
<style>body{font-family:sans-serif;background:#f2f4f9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.box{background:#fff;padding:32px 40px;border-radius:12px;box-shadow:0 8px 24px -12px rgba(15,24,48,.2);max-width:420px}
a{display:inline-block;margin-top:16px;background:#2f6fed;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600}</style>
</head>
<body>
  <div class="box">
    <h2>StockPilot — Installation</h2>
    <p><?= $msg ?></p>
    <a href="index.html">Aller à l'application</a>
  </div>
</body>
</html>
