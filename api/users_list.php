<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireAdmin();

$stmt = $pdo->query("SELECT id, nom, username, email, role, service, statut FROM utilisateurs ORDER BY id");
echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
