<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'achats']);

$input = readJsonBody();
$code = trim($input['code'] ?? '');
$nom = trim($input['nom'] ?? '');
$cat = trim($input['cat'] ?? '');
$stock = (int) ($input['stock'] ?? 0);
$min = (int) ($input['min'] ?? 0);
$max = (int) ($input['max'] ?? 0);
$emp = trim($input['emp'] ?? '');

if ($code === '' || $nom === '' || $cat === '') {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir le code, la désignation et la catégorie.']);
    exit;
}

$chk = $pdo->prepare("SELECT COUNT(*) AS c FROM articles WHERE code = ?");
$chk->execute([$code]);
if ((int) $chk->fetch()['c'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Ce code article existe déjà.']);
    exit;
}

$ins = $pdo->prepare("INSERT INTO articles (code, nom, cat, stock, min, max, emp) VALUES (?, ?, ?, ?, ?, ?, ?)");
$ins->execute([$code, $nom, $cat, $stock, $min, $max, $emp]);
$newId = (int) $pdo->lastInsertId();
enregistrerAlerteStock($pdo, $newId, "Création de l'article");

echo json_encode(['success' => true, 'id' => $newId]);
