<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'achats']);

$input = readJsonBody();
$num = trim($input['num'] ?? '');
$fournisseur = trim($input['fournisseur'] ?? '');
$montant = (float) ($input['montant'] ?? 0);

if ($num === '' || $fournisseur === '') {
    echo json_encode(['success' => false, 'message' => 'Champs invalides.']);
    exit;
}

$upd = $pdo->prepare("UPDATE commandes SET fournisseur = ?, montant = ? WHERE num = ?");
$upd->execute([$fournisseur, $montant, $num]);

echo json_encode(['success' => true]);
