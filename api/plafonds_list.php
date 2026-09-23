<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();
if ($_SESSION['role'] === 'admin') {
    $stmt = $pdo->query(
        "SELECT p.id, p.user_id, u.nom AS user_nom, p.article_id, a.nom AS article_nom, p.qte_max
         FROM plafonds_achats p
         JOIN utilisateurs u ON u.id = p.user_id
         JOIN articles a ON a.id = p.article_id
         ORDER BY u.nom, a.nom"
    );
    $rows = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare(
        "SELECT p.id, p.user_id, u.nom AS user_nom, p.article_id, a.nom AS article_nom, p.qte_max
         FROM plafonds_achats p
         JOIN utilisateurs u ON u.id = p.user_id
         JOIN articles a ON a.id = p.article_id
         WHERE p.user_id = ?
         ORDER BY a.nom"
    );
    $stmt->execute([(int) ($_SESSION['user_id'] ?? 0)]);
    $rows = $stmt->fetchAll();
}

echo json_encode(['success' => true, 'plafonds' => $rows]);
