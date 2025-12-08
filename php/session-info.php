<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'authenticated' => false,
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$response = [
    'authenticated' => true,
    'id' => $userId,
    'name' => '',
    'points' => (int)($_SESSION['user_points'] ?? 0),
];

$fullName = trim(
    trim((string)($_SESSION['user_name'] ?? '')) . ' ' .
    trim((string)($_SESSION['user_last_name'] ?? ''))
);

if ($fullName !== '') {
    $response['name'] = $fullName;
} elseif (!empty($_SESSION['user_email'])) {
    $response['name'] = (string)$_SESSION['user_email'];
} else {
    $response['name'] = 'Cliente';
}

$needsRefresh = !isset($_SESSION['user_points']) || $response['name'] === 'Cliente';

if ($needsRefresh) {
    $mysqli = @new mysqli(
        '127.0.0.1',
        'lgunprmiuy_admin',
        'UChD1dZxhwSn',
        'lgunprmiuy_PrismaticFlames'
    );

    if (!$mysqli->connect_errno) {
        $mysqli->set_charset('utf8mb4');
        $statement = $mysqli->prepare(
            'SELECT nombre, apellido, puntos_acumulados FROM usuarios WHERE id = ? LIMIT 1'
        );
        if ($statement) {
            $statement->bind_param('i', $userId);
            if ($statement->execute()) {
                $result = $statement->get_result();
                $user = $result ? $result->fetch_assoc() : null;
                if ($user) {
                    $firstName = trim((string)$user['nombre']);
                    $lastName = trim((string)$user['apellido']);
                    $name = trim($firstName . ' ' . $lastName);
                    if ($name !== '') {
                        $response['name'] = $name;
                        $_SESSION['user_name'] = $firstName;
                        $_SESSION['user_last_name'] = $lastName;
                    }
                    if (isset($user['puntos_acumulados'])) {
                        $points = (int)$user['puntos_acumulados'];
                        $response['points'] = $points;
                        $_SESSION['user_points'] = $points;
                    }
                }
            }
            $statement->close();
        }
        $mysqli->close();
    }
}

echo json_encode($response);


