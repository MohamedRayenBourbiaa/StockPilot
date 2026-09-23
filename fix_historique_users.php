<?php
require_once __DIR__ . '/config.php';
requireRole(['achats', 'admin']);
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Correction des lignes historique (sortie mal attribuée)</h2>";

// On récupère toutes les lignes "sortie" liées à une validation de demande
// (ref_num est rempli sur les lignes récentes, mais NULL sur les anciennes ->
// dans ce cas on retrouve le numéro de demande dans le texte du motif).
$stmt = $pdo->query(
    "SELECT id, art, qte, user, motif, ref_num, service FROM historique WHERE type = 'sortie'"
);
$rows = $stmt->fetchAll();

$demStmt = $pdo->prepare("SELECT demandeur, service FROM demandes WHERE num = ?");

$corrections = [];
foreach ($rows as $r) {
    $num = $r['ref_num'];
    if (!$num && preg_match('/DEM-\d{4}-\d{4}/', $r['motif'], $m)) {
        $num = $m[0];
    }
    if (!$num) continue;

    $demStmt->execute([$num]);
    $dem = $demStmt->fetch();
    if (!$dem) continue;

    if ($r['user'] !== $dem['demandeur'] || $r['service'] !== $dem['service']) {
        $corrections[] = [
            'id' => $r['id'], 'num' => $num, 'art' => $r['art'],
            'avant_user' => $r['user'], 'avant_service' => $r['service'] ?: '(vide)',
            'apres_user' => $dem['demandeur'], 'apres_service' => $dem['service'],
        ];
    }
}

if (!$corrections) {
    echo "<p>Aucune ligne incorrecte trouvée. Tout est déjà correct ✅</p>";
} else {
    echo "<p>" . count($corrections) . " ligne(s) à corriger :</p><ul>";
    $upd = $pdo->prepare("UPDATE historique SET user = ?, service = ?, ref_num = ?, ref_type = 'demande' WHERE id = ?");
    foreach ($corrections as $c) {
        echo "<li>#{$c['id']} — {$c['num']} — {$c['art']} : <strong>{$c['avant_user']}</strong> ({$c['avant_service']}) → <strong>{$c['apres_user']}</strong> ({$c['apres_service']})</li>";
        $upd->execute([$c['apres_user'], $c['apres_service'], $c['num'], $c['id']]);
    }
    echo "</ul><p>✅ Corrigé avec succès.</p>";
}

echo "<p><a href='index.html'>Retour au tableau de bord</a></p>";
echo "<p style='color:#999;font-size:13px'>⚠️ Tu peux supprimer ce fichier (fix_historique_users.php) après usage.</p>";

