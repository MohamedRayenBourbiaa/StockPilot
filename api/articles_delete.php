<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'achats']);

$input = readJsonBody();
$id = (int) ($input['id'] ?? 0);

$stmt = $pdo->prepare("SELECT id FROM articles WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Article introuvable.']);
    exit;
}

$del = $pdo->prepare("DELETE FROM articles WHERE id = ?");
$del->execute([$id]);

echo json_encode(['success' => true]);
