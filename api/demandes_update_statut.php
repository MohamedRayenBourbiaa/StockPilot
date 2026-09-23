<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['achats']);

$input = readJsonBody();
$num = trim($input['num'] ?? '');
$statut = $input['statut'] ?? '';

if (!in_array($statut, ['validee', 'attente'], true) || $num === '') {
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
    exit;
}

$demStmt = $pdo->prepare("SELECT * FROM demandes WHERE num = ?");
$demStmt->execute([$num]);
$demande = $demStmt->fetch();
if (!$demande) {
    echo json_encode(['success' => false, 'message' => 'Demande introuvable.']);
    exit;
}

if ($statut === 'validee') {
    $pdo->beginTransaction();
    try {
        $lignesStmt = $pdo->prepare(
            "SELECT dl.article_id, dl.qte, a.nom AS art_nom, a.stock
             FROM demande_lignes dl
             JOIN articles a ON a.id = dl.article_id
             WHERE dl.demande_num = ?
             FOR UPDATE"
        );
        $lignesStmt->execute([$num]);
        $lignes = $lignesStmt->fetchAll();

        if (!$lignes) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => "Cette demande ne contient aucun article enregistré, impossible de la valider."]);
            exit;
        }

        foreach ($lignes as $l) {
            if ((int) $l['stock'] < (int) $l['qte']) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => "Stock insuffisant pour \"{$l['art_nom']}\" (disponible : {$l['stock']}, demandé : {$l['qte']}).",
                ]);
                exit;
            }
        }

        $updArt = $pdo->prepare("UPDATE articles SET stock = stock - ? WHERE id = ?");
        $insHist = $pdo->prepare("INSERT INTO historique (date_mvt, type, art, qte, user, motif, ref_num, ref_type, service) VALUES (?, 'sortie', ?, ?, ?, ?, ?, 'demande', ?)");
        $dateNow = date('d/m/Y H:i');
        foreach ($lignes as $l) {
            $updArt->execute([$l['qte'], $l['article_id']]);
            $insHist->execute([$dateNow, $l['art_nom'], $l['qte'], $demande['demandeur'], "Sortie suite validation de la demande $num", $num, $demande['service']]);
            enregistrerAlerteStock($pdo, $l['article_id'], "Sortie suite validation de la demande $num");
        }

        $upd = $pdo->prepare("UPDATE demandes SET statut = 'validee', notif_lue = 0 WHERE num = ?");
        $upd->execute([$num]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la validation : ' . $e->getMessage()]);
        exit;
    }
} else {
    $upd = $pdo->prepare("UPDATE demandes SET statut = ?, notif_lue = 0 WHERE num = ?");
    $upd->execute([$statut, $num]);
}

echo json_encode(['success' => true]);
