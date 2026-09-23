<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();

$demandeurId = (int) ($_SESSION['user_id'] ?? 0);
$demandeurRole = $_SESSION['role'] ?? '';

$input = readJsonBody();
$service = trim($input['service'] ?? '');
$demandeur = trim($input['demandeur'] ?? '');
$date = trim($input['date'] ?? '');
$nbArticles = (int) ($input['articles'] ?? 0);
$qte = (int) ($input['qte'] ?? 0);
$lignes = is_array($input['lignes'] ?? null) ? $input['lignes'] : [];

// Un compte "Responsable Service" ne peut créer une demande que pour son propre service :
// on ignore toute valeur envoyée par le client et on force celle enregistrée sur le compte.
if ($demandeurRole === 'service') {
    $service = trim($_SESSION['service'] ?? $service);
}

if ($service === '' || $demandeur === '' || $nbArticles < 1 || !$lignes) {
    echo json_encode(['success' => false, 'message' => 'Veuillez ajouter au moins un article.']);
    exit;
}

function prochainNumDemande(PDO $pdo): string
{
    $year = date('Y');
    // On se base sur le plus grand numéro déjà utilisé (et non un simple COUNT),
    // car des demandes peuvent avoir été supprimées entre-temps : un COUNT(*)
    // redonnerait alors un numéro déjà pris et ferait échouer l'insertion.
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(num, '-', -1) AS UNSIGNED)) AS m FROM demandes WHERE num LIKE ?");
    $stmt->execute(["DEM-$year-%"]);
    $next = (int) $stmt->fetch()['m'] + 1;
    return sprintf('DEM-%s-%04d', $year, $next);
}

$pdo->beginTransaction();
try {
    $artStmt = $pdo->prepare("SELECT nom, stock FROM articles WHERE id = ? FOR UPDATE");
    $lignesValides = [];
    foreach ($lignes as $l) {
        $articleId = (int) ($l['article_id'] ?? 0);
        $ligneQte = (int) ($l['qte'] ?? 0);
        if ($articleId < 1 || $ligneQte < 1) continue;
        $artStmt->execute([$articleId]);
        $art = $artStmt->fetch();
        if (!$art) continue;
        $lignesValides[] = ['article_id' => $articleId, 'nom' => $art['nom'], 'qte' => $ligneQte, 'stock' => (int) $art['stock']];
    }

    if (!$lignesValides) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Veuillez ajouter au moins un article valide.']);
        exit;
    }

    // Pour chaque article : on sert tout de suite ce que le stock permet (jusqu'à
    // épuisement), et le reste (si la quantité demandée dépasse le stock disponible)
    // part dans une demande "en attente" séparée, à traiter par les achats.
    $servies = [];
    $restantes = [];
    foreach ($lignesValides as $l) {
        $pris = min($l['qte'], $l['stock']);
        $reste = $l['qte'] - $pris;
        if ($pris > 0) $servies[] = ['article_id' => $l['article_id'], 'nom' => $l['nom'], 'qte' => $pris];
        if ($reste > 0) $restantes[] = ['article_id' => $l['article_id'], 'nom' => $l['nom'], 'qte' => $reste];
    }

    $insDemande = $pdo->prepare("INSERT INTO demandes (num, service, demandeur, user_id, date_demande, statut, nb_articles, qte_totale, notif_lue) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insLigne = $pdo->prepare("INSERT INTO demande_lignes (demande_num, article_id, article_nom, qte) VALUES (?, ?, ?, ?)");
    $updArt = $pdo->prepare("UPDATE articles SET stock = stock - ? WHERE id = ?");
    $insHist = $pdo->prepare("INSERT INTO historique (date_mvt, type, art, qte, user, motif, ref_num, ref_type, service) VALUES (?, 'sortie', ?, ?, ?, ?, ?, 'demande', ?)");
    $dateNow = date('d/m/Y H:i');

    $numServie = null;
    $numAttente = null;

    if ($servies) {
        $numServie = prochainNumDemande($pdo);
        $qteServie = array_sum(array_column($servies, 'qte'));
        $insDemande->execute([$numServie, $service, $demandeur, $demandeurId ?: null, $date, 'validee', count($servies), $qteServie, 1]);
        foreach ($servies as $l) {
            $insLigne->execute([$numServie, $l['article_id'], $l['nom'], $l['qte']]);
            $updArt->execute([$l['qte'], $l['article_id']]);
            $insHist->execute([$dateNow, $l['nom'], $l['qte'], $demandeur, "Sortie suite validation automatique de la demande $numServie", $numServie, $service]);
            enregistrerAlerteStock($pdo, $l['article_id'], "Sortie suite validation automatique de la demande $numServie");
        }
    }

    if ($restantes) {
        $numAttente = prochainNumDemande($pdo);
        // Si les deux numéros seraient identiques (aucune demande servie avant), on force un écart.
        if ($numAttente === $numServie) {
            $numAttente = prochainNumDemande($pdo);
        }
        $qteAttente = array_sum(array_column($restantes, 'qte'));
        $insDemande->execute([$numAttente, $service, $demandeur, $demandeurId ?: null, $date, 'attente', count($restantes), $qteAttente, 0]);
        foreach ($restantes as $l) {
            $insLigne->execute([$numAttente, $l['article_id'], $l['nom'], $l['qte']]);
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => "Erreur lors de l'enregistrement : " . $e->getMessage()]);
    exit;
}

echo json_encode([
    'success' => true,
    'num' => $numServie ?: $numAttente,
    'num_validee' => $numServie,
    'num_attente' => $numAttente,
    'partielle' => $numServie !== null && $numAttente !== null,
]);
