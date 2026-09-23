<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireAdmin();

$input = readJsonBody();
$nom = trim($input['nom'] ?? '');
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? '';
$service = trim($input['service'] ?? '');
$statut = $input['statut'] ?? 'Actif';

$validRoles = ['Administrateur', 'Responsable Achats', 'Responsable Service'];

if ($nom === '' || $username === '' || $email === '' || $password === '' || !in_array($role, $validRoles, true)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires.']);
    exit;
}

$chk = $pdo->prepare("SELECT COUNT(*) AS c FROM utilisateurs WHERE username = ?");
$chk->execute([$username]);
if ((int) $chk->fetch()['c'] > 0) {
    echo json_encode(['success' => false, 'message' => "Ce nom d'utilisateur existe déjà."]);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$ins = $pdo->prepare("INSERT INTO utilisateurs (nom, username, email, password, role, service, statut) VALUES (?, ?, ?, ?, ?, ?, ?)");
$ins->execute([$nom, $username, $email, $hash, $role, $service, $statut]);

echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
