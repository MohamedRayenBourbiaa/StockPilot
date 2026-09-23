<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'achats']);

$input = readJsonBody();
$num = trim($input['num'] ?? '');

if ($num === '') {
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
    exit;
}

$stmt = $pdo->prepare("SELECT num FROM demandes WHERE num = ?");
$stmt->execute([$num]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Demande introuvable.']);
    exit;
}

// demande_lignes est en ON DELETE CASCADE sur demande_num (voir database.sql),
// les lignes associées sont donc supprimées automatiquement avec la demande.
$del = $pdo->prepare("DELETE FROM demandes WHERE num = ?");
$del->execute([$num]);

echo json_encode(['success' => true]);
