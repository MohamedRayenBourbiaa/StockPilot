<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'achats']);

$stmt = $pdo->query(
    "SELECT id, article_id, article_nom, cat, stock, min_seuil, niveau, date_alerte, motif, created_at
     FROM alertes_historique
     ORDER BY created_at DESC, id DESC
     LIMIT 300"
);
echo json_encode(['success' => true, 'alertes' => $stmt->fetchAll()]);
