<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$input = readJsonBody();
$identifiant = trim($input['username'] ?? '');

if ($identifiant === '') {
    echo json_encode(['success' => false, 'message' => "Veuillez saisir votre nom d'utilisateur ou votre email."]);
    exit;
}

// Recherche par nom d'utilisateur OU par email, selon ce que la personne a saisi.
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = ? OR email = ? LIMIT 1");
$stmt->execute([$identifiant, $identifiant]);
$user = $stmt->fetch();
$genericMsg = "Si ce compte existe, un nouveau mot de passe a été envoyé à l'adresse email associée.";

if (!$user || $user['statut'] !== 'Actif') {
    echo json_encode(['success' => true, 'message' => $genericMsg]);
    exit;
}
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
$tempPassword = '';
for ($i = 0; $i < 10; $i++) {
    $tempPassword .= $alphabet[random_int(0, strlen($alphabet) - 1)];
}

$hash = password_hash($tempPassword, PASSWORD_DEFAULT);
$upd = $pdo->prepare("UPDATE utilisateurs SET password = ? WHERE id = ?");
$upd->execute([$hash, $user['id']]);

$subject = 'StockPilot — Votre nouveau mot de passe';
$bodyHtml = '
<div style="font-family:Arial,sans-serif;font-size:14px;color:#222;line-height:1.5;">
  <p>Bonjour ' . htmlspecialchars($user['nom']) . ',</p>
  <p>Une demande de réinitialisation de mot de passe a été effectuée pour le compte <strong>' . htmlspecialchars($user['username']) . '</strong>.</p>
  <p>Voici votre nouveau mot de passe temporaire :</p>
  <p style="font-size:20px;font-weight:bold;background:#f4f5f7;padding:12px 18px;border-radius:8px;display:inline-block;letter-spacing:1px;">' . htmlspecialchars($tempPassword) . '</p>
  <p>Connectez-vous avec ce mot de passe, puis changez-le dès que possible depuis votre profil.</p>
  <p style="color:#888;font-size:12px;">Si vous n\'êtes pas à l\'origine de cette demande, contactez un administrateur immédiatement.</p>
</div>';

$error = null;
$sent = sendMailSmtp($user['email'], $subject, $bodyHtml, $error);

if (!$sent) {
    error_log('forgot_password.php: échec envoi email pour ' . $user['username'] . ' — ' . $error);
    echo json_encode([
        'success' => false,
        'message' => "Le mot de passe a été réinitialisé mais l'email n'a pas pu être envoyé. Vérifiez la configuration SMTP (config.php) ou contactez un administrateur.",
    ]);
    exit;
}

echo json_encode(['success' => true, 'message' => $genericMsg]);
