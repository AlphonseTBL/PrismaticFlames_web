<?php

declare(strict_types=1);

session_start();

$redirectBase = '../login.html';

function redirect_with_status(string $status, ?string $reason = null): void
{
    global $redirectBase;
    $location = $redirectBase . '?status=' . urlencode($status);
    if ($reason !== null) {
        $location .= '&reason=' . urlencode($reason);
    }
    header('Location: ' . $location);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_status('error');
}

$email = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    redirect_with_status('error', 'missing_fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_status('error', 'invalid_email');
}

$mysqli = @new mysqli(
    '127.0.0.1',
    'lgunprmiuy_admin',
    'UChD1dZxhwSn',
    'lgunprmiuy_PrismaticFlames'
);

if ($mysqli->connect_errno) {
    redirect_with_status('error', 'db_connect');
}

$mysqli->set_charset('utf8mb4');

$statement = $mysqli->prepare(
    'SELECT id, nombre, apellido, email, password_hash, puntos_acumulados FROM usuarios WHERE email = ? LIMIT 1'
);

if ($statement === false) {
    $mysqli->close();
    redirect_with_status('error', 'db_execute');
}

$statement->bind_param('s', $email);

if (!$statement->execute()) {
    $statement->close();
    $mysqli->close();
    redirect_with_status('error', 'db_execute');
}

$result = $statement->get_result();
$user = $result ? $result->fetch_assoc() : null;
$statement->close();

if (!$user) {
    $mysqli->close();
    redirect_with_status('error', 'invalid_credentials');
}

$hash = $user['password_hash'] ?? '';

if (!is_string($hash) || $hash === '' || !password_verify($password, $hash)) {
    $mysqli->close();
    redirect_with_status('error', 'invalid_credentials');
}

if (password_needs_rehash($hash, PASSWORD_BCRYPT)) {
    $newHash = password_hash($password, PASSWORD_BCRYPT);
    if ($newHash !== false) {
        $rehashStatement = $mysqli->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
        if ($rehashStatement) {
            $userId = (int)$user['id'];
            $rehashStatement->bind_param('si', $newHash, $userId);
            $rehashStatement->execute();
            $rehashStatement->close();
        }
    }
}

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_name'] = $user['nombre'] ?? '';
$_SESSION['user_last_name'] = $user['apellido'] ?? '';
$_SESSION['user_email'] = $user['email'] ?? $email;
$_SESSION['user_points'] = isset($user['puntos_acumulados']) ? (int)$user['puntos_acumulados'] : 0;

$mysqli->close();

header('Location: ../my-account.html?status=login_success');
exit;

