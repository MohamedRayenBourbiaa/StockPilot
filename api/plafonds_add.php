<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireAdmin();

$input = readJsonBody();
$userId = (int) ($input['user_id'] ?? 0);
$articleId = (int) ($input['article_id'] ?? 0);
$qteMax = (int) ($input['qte_max'] ?? 0);

if ($userId < 1 || $articleId < 1 || $qteMax < 1) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs correctement.']);
    exit;
}

$u = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = ?");
$u->execute([$userId]);
$user = $u->fetch();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
    exit;
}
if ($user['role'] !== 'Responsable Achats') {
    echo json_encode(['success' => false, 'message' => 'Un plafond ne peut être défini que pour un compte "Responsable Achats".']);
    exit;
}

$art = $pdo->prepare("SELECT id FROM articles WHERE id = ?");
$art->execute([$articleId]);
if (!$art->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Article introuvable.']);
    exit;
}

$ins = $pdo->prepare(
    "INSERT INTO plafonds_achats (user_id, article_id, qte_max) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE qte_max = VALUES(qte_max)"
);
$ins->execute([$userId, $articleId, $qteMax]);

echo json_encode(['success' => true]);
