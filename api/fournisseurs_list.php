<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();

$stmt = $pdo->query("SELECT id, nom FROM fournisseurs ORDER BY id");
echo json_encode(['success' => true, 'fournisseurs' => $stmt->fetchAll()]);
