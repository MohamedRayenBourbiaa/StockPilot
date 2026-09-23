<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$input = readJsonBody();
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => "Veuillez saisir un nom d'utilisateur et un mot de passe."]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => "Nom d'utilisateur ou mot de passe incorrect."]);
    exit;
}

if ($user['statut'] !== 'Actif') {
    echo json_encode(['success' => false, 'message' => "Ce compte est désactivé."]);
    exit;
}

$roleCode = ROLE_CODE[$user['role']] ?? 'service';

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $roleCode;
$_SESSION['nom'] = $user['nom'];
$_SESSION['service'] = $user['service'];

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'nom' => $user['nom'],
        'username' => $user['username'],
        'role' => $roleCode,
        'service' => $user['service'],
    ],
]);
