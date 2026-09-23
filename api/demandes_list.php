<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();
$pdo->beginTransaction();
try {
    autoValiderDemandesEnAttente($pdo, $_SESSION['nom'] ?? '');
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
}
if ($_SESSION['role'] === 'service') {
    $stmt = $pdo->prepare(
        "SELECT id, num, service, demandeur, date_demande AS date, statut, nb_articles AS articles, qte_totale AS qte, notif_lue
         FROM demandes WHERE demandeur = ? ORDER BY id DESC"
    );
    $stmt->execute([$_SESSION['nom'] ?? '']);
} else {
    $stmt = $pdo->query("SELECT id, num, service, demandeur, date_demande AS date, statut, nb_articles AS articles, qte_totale AS qte, notif_lue FROM demandes ORDER BY id DESC");
}
echo json_encode(['success' => true, 'demandes' => $stmt->fetchAll()]);
