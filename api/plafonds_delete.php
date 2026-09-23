<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireAdmin();

$input = readJsonBody();
$id = (int) ($input['id'] ?? 0);

if ($id < 1) {
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
    exit;
}

$del = $pdo->prepare("DELETE FROM plafonds_achats WHERE id = ?");
$del->execute([$id]);

echo json_encode(['success' => true]);
