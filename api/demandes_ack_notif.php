<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();

$input = readJsonBody();
$num = trim($input['num'] ?? '');
if ($num === '') {
    echo json_encode(['success' => false, 'message' => 'Numéro de demande manquant.']);
    exit;
}

$stmt = $pdo->prepare("SELECT demandeur FROM demandes WHERE num = ?");
$stmt->execute([$num]);
$row = $stmt->fetch();
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Demande introuvable.']);
    exit;
}
if ($row['demandeur'] !== ($_SESSION['nom'] ?? '') && $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => "Vous n'avez pas les droits nécessaires pour cette action."]);
    exit;
}

$upd = $pdo->prepare("UPDATE demandes SET notif_lue = 1 WHERE num = ?");
$upd->execute([$num]);

echo json_encode(['success' => true]);
