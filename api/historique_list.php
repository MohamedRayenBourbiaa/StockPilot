<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();

if ($_SESSION['role'] === 'service') {
    $stmt = $pdo->prepare(
        "SELECT h.id, h.date_mvt AS date, h.type, h.art, h.qte, h.user, h.motif, h.ref_num, h.ref_type,
                COALESCE(NULLIF(u.service, ''), h.service) AS service
         FROM historique h
         LEFT JOIN utilisateurs u ON u.nom = h.user
         WHERE h.user = ?
         ORDER BY h.id DESC"
    );
    $stmt->execute([$_SESSION['nom'] ?? '']);
} else {
    $stmt = $pdo->query(
        "SELECT h.id, h.date_mvt AS date, h.type, h.art, h.qte, h.user, h.motif, h.ref_num, h.ref_type,
                COALESCE(NULLIF(u.service, ''), h.service) AS service
         FROM historique h
         LEFT JOIN utilisateurs u ON u.nom = h.user
         ORDER BY h.id DESC"
    );
}
$rows = $stmt->fetchAll();

// Anciens mouvements enregistrés avant l'ajout de ref_num : on retrouve le numéro
// de demande/commande à partir du motif (ex : "... de la demande DEM-2026-0007").
foreach ($rows as &$r) {
    if (empty($r['ref_num']) && preg_match('/\b(DEM|CMD)-\d{4}-\d+\b/', $r['motif'], $m)) {
        $r['ref_num'] = $m[0];
        $r['ref_type'] = $m[1] === 'DEM' ? 'demande' : 'commande';
    }
}
unset($r);

echo json_encode(['success' => true, 'historique' => $rows]);
