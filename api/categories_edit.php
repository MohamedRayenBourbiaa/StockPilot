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

$cur = $pdo->prepare("SELECT nom FROM categories WHERE id = ?");
$cur->execute([$id]);
$row = $cur->fetch();
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Catégorie introuvable.']);
    exit;
}
$ancienNom = $row['nom'];

$chk = $pdo->prepare("SELECT COUNT(*) AS c FROM categories WHERE nom = ? AND id != ?");
$chk->execute([$nom, $id]);
if ((int) $chk->fetch()['c'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Ce nom est déjà utilisé.']);
    exit;
}

$pdo->beginTransaction();
$upd = $pdo->prepare("UPDATE categories SET nom=? WHERE id=?");
$upd->execute([$nom, $id]);
if ($ancienNom !== $nom) {
    $updArt = $pdo->prepare("UPDATE articles SET cat=? WHERE cat=?");
    $updArt->execute([$nom, $ancienNom]);
}
$pdo->commit();

echo json_encode(['success' => true]);
