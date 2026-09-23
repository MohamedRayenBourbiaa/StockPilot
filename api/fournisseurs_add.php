<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'achats']);

$input = readJsonBody();
$nom = trim($input['nom'] ?? '');

if ($nom === '') {
    echo json_encode(['success' => false, 'message' => 'Veuillez saisir un nom.']);
    exit;
}

$chk = $pdo->prepare("SELECT COUNT(*) AS c FROM fournisseurs WHERE nom = ?");
$chk->execute([$nom]);
if ((int) $chk->fetch()['c'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Ce fournisseur existe déjà.']);
    exit;
}

$ins = $pdo->prepare("INSERT INTO fournisseurs (nom) VALUES (?)");
$ins->execute([$nom]);

echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
