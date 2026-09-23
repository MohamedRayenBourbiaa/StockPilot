<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();

$input = readJsonBody();
$date = trim($input['date'] ?? '');
$type = $input['type'] ?? '';
$art = trim($input['art'] ?? '');
$qte = (int) ($input['qte'] ?? 0);
$user = trim($input['user'] ?? '');
$motif = trim($input['motif'] ?? '');

if (!in_array($type, ['entree', 'sortie'], true) || $art === '' || $user === '') {
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
    exit;
}

$ins = $pdo->prepare("INSERT INTO historique (date_mvt, type, art, qte, user, motif) VALUES (?, ?, ?, ?, ?, ?)");
$ins->execute([$date, $type, $art, $qte, $user, $motif]);

echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
