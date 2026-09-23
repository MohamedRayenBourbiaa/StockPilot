<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();

$stmt = $pdo->query("SELECT id, nom FROM categories ORDER BY nom");
echo json_encode(['success' => true, 'categories' => $stmt->fetchAll()]);
