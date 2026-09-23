<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin']);

$input = readJsonBody();
$id = (int) ($input['id'] ?? 0);
$nom = trim($input['nom'] ?? '');

if (!$id || $nom === '') {
    echo json_encode(['success' => false, 'message' => 'Champs invalides.']);
    exit;
}

$cur = $pdo->prepare("SELECT nom FROM services WHERE id = ?");
$cur->execute([$id]);
$row = $cur->fetch();
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Service introuvable.']);
    exit;
}
$ancienNom = $row['nom'];

$chk = $pdo->prepare("SELECT COUNT(*) AS c FROM services WHERE nom = ? AND id != ?");
$chk->execute([$nom, $id]);
if ((int) $chk->fetch()['c'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Ce nom est déjà utilisé.']);
    exit;
}

$pdo->beginTransaction();
$upd = $pdo->prepare("UPDATE services SET nom=? WHERE id=?");
$upd->execute([$nom, $id]);
if ($ancienNom !== $nom) {
    $updUsers = $pdo->prepare("UPDATE utilisateurs SET service=? WHERE service=?");
    $updUsers->execute([$nom, $ancienNom]);
    $updDemandes = $pdo->prepare("UPDATE demandes SET service=? WHERE service=?");
    $updDemandes->execute([$nom, $ancienNom]);
}
$pdo->commit();

echo json_encode(['success' => true]);
