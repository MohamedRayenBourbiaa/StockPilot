<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'achats']);

$input = readJsonBody();
$id = (int) ($input['id'] ?? 0);
$code = trim($input['code'] ?? '');
$nom = trim($input['nom'] ?? '');
$cat = trim($input['cat'] ?? '');
$stock = (int) ($input['stock'] ?? 0);
$min = (int) ($input['min'] ?? 0);
$max = (int) ($input['max'] ?? 0);
$emp = trim($input['emp'] ?? '');

if (!$id || $code === '' || $nom === '' || $cat === '') {
    echo json_encode(['success' => false, 'message' => 'Champs invalides.']);
    exit;
}

$chk = $pdo->prepare("SELECT COUNT(*) AS c FROM articles WHERE code = ? AND id != ?");
$chk->execute([$code, $id]);
if ((int) $chk->fetch()['c'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Ce code article est déjà utilisé.']);
    exit;
}

$curStmt = $pdo->prepare("SELECT stock FROM articles WHERE id = ?");
$curStmt->execute([$id]);
$cur = $curStmt->fetch();
$ancienStock = $cur ? (int) $cur['stock'] : 0;

$pdo->beginTransaction();
try {
    $upd = $pdo->prepare("UPDATE articles SET code=?, nom=?, cat=?, stock=?, min=?, max=?, emp=? WHERE id=?");
    $upd->execute([$code, $nom, $cat, $stock, $min, $max, $emp, $id]);
    enregistrerAlerteStock($pdo, $id, "Modification manuelle de l'article");

    if ($stock > $ancienStock) {
        autoValiderDemandesEnAttente($pdo, $_SESSION['nom'] ?? '', $id);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification : ' . $e->getMessage()]);
    exit;
}

echo json_encode(['success' => true]);
