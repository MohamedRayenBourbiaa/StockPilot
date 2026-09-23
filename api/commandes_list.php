<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();

$stmt = $pdo->query(
    "SELECT id, num, fournisseur, article_id, article_nom, qte, prix_unitaire, montant,
            date_commande AS date, statut
     FROM commandes ORDER BY id DESC"
);
echo json_encode(['success' => true, 'commandes' => $stmt->fetchAll()]);
