<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();

$num = trim($_GET['num'] ?? '');
if ($num === '') {
    echo json_encode(['success' => false, 'message' => 'Numéro de demande manquant.']);
    exit;
}

if ($_SESSION['role'] === 'service') {
    $chk = $pdo->prepare("SELECT demandeur FROM demandes WHERE num = ?");
    $chk->execute([$num]);
    $dem = $chk->fetch();
    if (!$dem || $dem['demandeur'] !== ($_SESSION['nom'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => "Vous n'avez pas accès à cette demande."]);
        exit;
    }
}

$stmt = $pdo->prepare("SELECT article_id, article_nom AS nom, qte FROM demande_lignes WHERE demande_num = ? ORDER BY id");
$stmt->execute([$num]);
echo json_encode(['success' => true, 'lignes' => $stmt->fetchAll()]);
