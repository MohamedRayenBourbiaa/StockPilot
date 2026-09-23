<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'achats']);

$input = readJsonBody();
$num = trim($input['num'] ?? '');
if ($num === '') {
    echo json_encode(['success' => false, 'message' => 'Numéro de commande manquant.']);
    exit;
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT * FROM commandes WHERE num = ? FOR UPDATE");
    $stmt->execute([$num]);
    $cm = $stmt->fetch();

    if (!$cm) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Commande introuvable.']);
        exit;
    }
    if ($cm['statut'] === 'recue') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Cette commande a déjà été reçue.']);
        exit;
    }

    $pdo->prepare("UPDATE articles SET stock = stock + ? WHERE id = ?")
        ->execute([$cm['qte'], $cm['article_id']]);

    $dateNow = date('d/m/Y H:i');
    $userNom = $_SESSION['nom'] ?? '';
    $pdo->prepare("INSERT INTO historique (date_mvt, type, art, qte, user, motif, ref_num, ref_type) VALUES (?, 'entree', ?, ?, ?, ?, ?, 'commande')")
        ->execute([$dateNow, $cm['article_nom'], $cm['qte'], $userNom, "Réception {$num}", $num]);
    enregistrerAlerteStock($pdo, (int) $cm['article_id'], "Réception {$num}");

    $pdo->prepare("UPDATE commandes SET statut = 'recue' WHERE num = ?")->execute([$num]);
    autoValiderDemandesEnAttente($pdo, $userNom, (int) $cm['article_id']);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la réception : ' . $e->getMessage()]);
    exit;
}

echo json_encode(['success' => true]);
