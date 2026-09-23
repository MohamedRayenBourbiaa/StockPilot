<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin']);

$input = readJsonBody();
$id = (int) ($input['id'] ?? 0);

$stmt = $pdo->prepare("SELECT nom FROM categories WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Catégorie introuvable.']);
    exit;
}

$chk = $pdo->prepare("SELECT COUNT(*) AS c FROM articles WHERE cat = ?");
$chk->execute([$row['nom']]);
if ((int) $chk->fetch()['c'] > 0) {
    echo json_encode(['success' => false, 'message' => "Impossible de supprimer : des articles utilisent encore cette catégorie."]);
    exit;
}

$del = $pdo->prepare("DELETE FROM categories WHERE id = ?");
$del->execute([$id]);

echo json_encode(['success' => true]);
