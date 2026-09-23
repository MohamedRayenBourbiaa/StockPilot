<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireAdmin();

$input = readJsonBody();
$id = (int) ($input['id'] ?? 0);

$stmt = $pdo->prepare("SELECT username FROM utilisateurs WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
    exit;
}

if ($row['username'] === 'rayenyoussef') {
    echo json_encode(['success' => false, 'message' => "Impossible de supprimer le compte administrateur principal."]);
    exit;
}

$del = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
$del->execute([$id]);

echo json_encode(['success' => true]);
