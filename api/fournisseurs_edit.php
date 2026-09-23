<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'achats']);

$input = readJsonBody();
$id = (int) ($input['id'] ?? 0);
$nom = trim($input['nom'] ?? '');

if (!$id || $nom === '') {
    echo json_encode(['success' => false, 'message' => 'Champs invalides.']);
    exit;
}

$chk = $pdo->prepare("SELECT COUNT(*) AS c FROM fournisseurs WHERE nom = ? AND id != ?");
$chk->execute([$nom, $id]);
if ((int) $chk->fetch()['c'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Ce nom est déjà utilisé.']);
    exit;
}

$upd = $pdo->prepare("UPDATE fournisseurs SET nom=? WHERE id=?");
$upd->execute([$nom, $id]);

echo json_encode(['success' => true]);
