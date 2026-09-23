<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();

$stmt = $pdo->query("SELECT id, code, nom, cat, stock, min, max, emp FROM articles ORDER BY id");
echo json_encode(['success' => true, 'articles' => $stmt->fetchAll()]);
