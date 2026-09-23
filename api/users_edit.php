<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireAdmin();

$input = readJsonBody();
$id = (int) ($input['id'] ?? 0);
$nom = trim($input['nom'] ?? '');
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? '';
$service = trim($input['service'] ?? '');
$statut = $input['statut'] ?? 'Actif';

$validRoles = ['Administrateur', 'Responsable Achats', 'Responsable Service'];

if (!$id || $nom === '' || $username === '' || $email === '' || !in_array($role, $validRoles, true)) {
    echo json_encode(['success' => false, 'message' => 'Champs invalides.']);
    exit;
}

$chk = $pdo->prepare("SELECT COUNT(*) AS c FROM utilisateurs WHERE username = ? AND id != ?");
$chk->execute([$username, $id]);
if ((int) $chk->fetch()['c'] > 0) {
    echo json_encode(['success' => false, 'message' => "Ce nom d'utilisateur est déjà utilisé."]);
    exit;
}

if ($password !== '') {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE utilisateurs SET nom=?, username=?, email=?, password=?, role=?, service=?, statut=? WHERE id=?");
    $upd->execute([$nom, $username, $email, $hash, $role, $service, $statut, $id]);
} else {
    $upd = $pdo->prepare("UPDATE utilisateurs SET nom=?, username=?, email=?, role=?, service=?, statut=? WHERE id=?");
    $upd->execute([$nom, $username, $email, $role, $service, $statut, $id]);
}

echo json_encode(['success' => true]);
