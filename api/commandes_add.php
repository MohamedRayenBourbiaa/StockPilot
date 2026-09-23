<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'achats']);

$input = readJsonBody();
$fournisseur = trim($input['fournisseur'] ?? '');
$articleId = (int) ($input['article_id'] ?? 0);
$qte = (int) ($input['qte'] ?? 0);
$pu = (float) ($input['pu'] ?? 0);
$date = trim($input['date'] ?? '') ?: date('d/m/Y');

if ($fournisseur === '' || $articleId < 1 || $qte < 1) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs correctement.']);
    exit;
}

$art = $pdo->prepare("SELECT nom FROM articles WHERE id = ?");
$art->execute([$articleId]);
$article = $art->fetch();
if (!$article) {
    echo json_encode(['success' => false, 'message' => 'Article introuvable.']);
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($_SESSION['role'] === 'achats') {
    $plStmt = $pdo->prepare("SELECT qte_max FROM plafonds_achats WHERE user_id = ? AND article_id = ?");
    $plStmt->execute([$userId, $articleId]);
    $plafond = $plStmt->fetch();
    if ($plafond) {
        $sumStmt = $pdo->prepare("SELECT COALESCE(SUM(qte), 0) AS total FROM commandes WHERE user_id = ? AND article_id = ?");
        $sumStmt->execute([$userId, $articleId]);
        $dejaCommande = (int) $sumStmt->fetch()['total'];
        $qteMax = (int) $plafond['qte_max'];
        if ($dejaCommande + $qte > $qteMax) {
            $restant = max(0, $qteMax - $dejaCommande);
            echo json_encode([
                'success' => false,
                'message' => "Plafond dépassé pour \"{$article['nom']}\" : il vous reste $restant unité(s) sur un plafond de $qteMax.",
            ]);
            exit;
        }
    }
}

$year = date('Y');
// MAX plutôt que COUNT : évite un numéro déjà pris si une commande a été supprimée.
$stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(num, '-', -1) AS UNSIGNED)) AS m FROM commandes WHERE num LIKE ?");
$stmt->execute(["CMD-$year-%"]);
$next = (int) $stmt->fetch()['m'] + 1;
$num = sprintf('CMD-%s-%04d', $year, $next);

$montant = $qte * $pu;

$ins = $pdo->prepare(
    "INSERT INTO commandes (num, fournisseur, article_id, article_nom, qte, prix_unitaire, montant, date_commande, statut, user_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'attente', ?)"
);
$ins->execute([$num, $fournisseur, $articleId, $article['nom'], $qte, $pu, $montant, $date, $userId ?: null]);

echo json_encode(['success' => true, 'num' => $num, 'id' => (int) $pdo->lastInsertId()]);
