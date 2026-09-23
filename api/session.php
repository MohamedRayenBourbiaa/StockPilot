<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'nom' => $_SESSION['nom'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'service' => $_SESSION['service'] ?? '',
        ],
    ]);
} else {
    echo json_encode(['success' => false]);
}
