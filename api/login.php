<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['error' => 'Método no permitido']);
}

$data = input_data();
$email = trim($data['email'] ?? '');
$password = (string)($data['password'] ?? '');

if ($email === '' || $password === '') {
    json_response(400, ['error' => 'Faltan email o contraseña.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(400, ['error' => 'Email inválido.']);
}

$db = api_db();

$stmt = $db->prepare('SELECT id, nombre, apellido, email, password_hash, puntos_acumulados FROM usuarios WHERE email = ? LIMIT 1');
if (!$stmt) {
    $db->close();
    json_response(500, ['error' => 'No se pudo preparar la consulta.']);
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$user || !is_string($user['password_hash'] ?? '') || $user['password_hash'] === '') {
    $db->close();
    json_response(401, ['error' => 'Credenciales inválidas.']);
}

$hash = (string)$user['password_hash'];
if (!password_verify($password, $hash)) {
    $db->close();
    json_response(401, ['error' => 'Credenciales inválidas.']);
}

if (password_needs_rehash($hash, PASSWORD_BCRYPT)) {
    $newHash = password_hash($password, PASSWORD_BCRYPT);
    if ($newHash !== false) {
        $rehash = $db->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
        if ($rehash) {
            $userId = (int)$user['id'];
            $rehash->bind_param('si', $newHash, $userId);
            $rehash->execute();
            $rehash->close();
        }
    }
}

$userId = (int)$user['id'];
$isAdmin = user_is_admin_or_moderator($db, $userId);
$role = $isAdmin ? 'admin' : 'user';

$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $user['nombre'] ?? '';
$_SESSION['user_last_name'] = $user['apellido'] ?? '';
$_SESSION['user_email'] = $user['email'] ?? $email;
$_SESSION['user_points'] = isset($user['puntos_acumulados']) ? (int)$user['puntos_acumulados'] : 0;
$_SESSION['user_role'] = $role;

$db->close();

json_response(200, [
    'success' => true,
    'user' => [
        'id' => $userId,
        'nombre' => $user['nombre'] ?? '',
        'apellido' => $user['apellido'] ?? '',
        'email' => $user['email'] ?? $email,
        'puntos' => isset($user['puntos_acumulados']) ? (int)$user['puntos_acumulados'] : 0,
    ],
    'role' => $role,
]);




