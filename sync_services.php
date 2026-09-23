<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/html; charset=utf-8');

// Script à usage unique : ajoute dans la table "services" tout service déjà utilisé par un
// compte utilisateur mais absent de cette table (comparaison insensible à la casse).
// Supprimez ce fichier une fois utilisé.

$existing = $pdo->query("SELECT nom FROM services")->fetchAll(PDO::FETCH_COLUMN);
$existingLower = array_map('mb_strtolower', $existing);

$used = $pdo->query("SELECT DISTINCT service FROM utilisateurs WHERE service IS NOT NULL AND service <> ''")->fetchAll(PDO::FETCH_COLUMN);

$ins = $pdo->prepare("INSERT INTO services (nom) VALUES (?)");
$added = [];
foreach ($used as $service) {
    $service = trim($service);
    if ($service === '') continue;
    if (!in_array(mb_strtolower($service), $existingLower, true)) {
        $ins->execute([$service]);
        $existingLower[] = mb_strtolower($service);
        $added[] = $service;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Synchronisation des services</title>
<style>body{font-family:sans-serif;background:#f6f3ee;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.box{background:#fff;padding:32px 40px;border-radius:12px;box-shadow:0 8px 24px -12px rgba(15,24,48,.2);max-width:480px}
a{display:inline-block;margin-top:16px;background:#a6835a;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600}
ul{margin:12px 0}</style>
</head>
<body>
  <div class="box">
    <h2>Synchronisation des services</h2>
    <?php if ($added): ?>
      <p><?= count($added) ?> service(s) ajouté(s) à la table "services" :</p>
      <ul><?php foreach ($added as $a): ?><li><?= htmlspecialchars($a) ?></li><?php endforeach; ?></ul>
    <?php else: ?>
      <p>Rien à ajouter, tous les services utilisés étaient déjà enregistrés.</p>
    <?php endif; ?>
    <p style="color:#c0392b;font-size:13px;">⚠️ Supprimez ce fichier (sync_services.php) du dossier maintenant.</p>
    <a href="index.html">Aller à l'application</a>
  </div>
</body>
</html>
