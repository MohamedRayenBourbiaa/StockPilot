<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'achats']);

$input = readJsonBody();
$id = (int) ($input['id'] ?? 0);

$stmt = $pdo->prepare("SELECT id FROM fournisseurs WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Fournisseur introuvable.']);
    exit;
}

$del = $pdo->prepare("DELETE FROM fournisseurs WHERE id = ?");
$del->execute([$id]);

echo json_encode(['success' => true]);
