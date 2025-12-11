<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

$db = null;

if (!empty($_SESSION['user_id'])) {
    $db = api_db();
    $userId = (int)$_SESSION['user_id'];
    $isAdmin = user_is_admin_or_moderator($db, $userId);
    $_SESSION['user_role'] = $isAdmin ? 'admin' : ($_SESSION['user_role'] ?? 'user');
}

$payload = [
    'authenticated' => !empty($_SESSION['user_id']),
];

if (!empty($_SESSION['user_id'])) {
    $payload['user'] = [
        'id' => (int)$_SESSION['user_id'],
        'nombre' => $_SESSION['user_name'] ?? '',
        'apellido' => $_SESSION['user_last_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'puntos' => (int)($_SESSION['user_points'] ?? 0),
        'role' => $_SESSION['user_role'] ?? 'user',
    ];
}

if ($db instanceof mysqli) {
    $db->close();
}

json_response(200, $payload);

