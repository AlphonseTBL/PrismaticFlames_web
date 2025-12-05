<?php

declare(strict_types=1);

$redirectBase = '../register.html';

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

$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirmPassword'] ?? '');
$acceptedTerms = isset($_POST['registerTerms']);

if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $password === '' || $confirmPassword === '') {
    redirect_with_status('error', 'missing_fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_status('error', 'invalid_email');
}

if ($password !== $confirmPassword) {
    redirect_with_status('error', 'password_mismatch');
}

if (!$acceptedTerms) {
    redirect_with_status('error', 'terms_required');
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT);
if ($passwordHash === false) {
    redirect_with_status('error', 'hash_error');
}

$mysqli = @new mysqli(
    'localhost',
    'lgunprmiuy_admin',
    'WRO0a7cSCGxD',
    'lgunprmiuy_prismaticflames'
);

if ($mysqli->connect_errno) {
    redirect_with_status('error', 'db_connect');
}

$mysqli->set_charset('utf8mb4');
$mysqli->begin_transaction();

$statement = $mysqli->prepare(
    'INSERT INTO usuarios (nombre, apellido, email, password_hash, telefono) VALUES (?, ?, ?, ?, ?)'
);

if ($statement === false) {
    $mysqli->rollback();
    $mysqli->close();
    redirect_with_status('error', 'db_execute');
}

$statement->bind_param('sssss', $firstName, $lastName, $email, $passwordHash, $phone);

if (!$statement->execute()) {
    $errorCode = $statement->errno;
    $statement->close();
    $mysqli->rollback();
    $mysqli->close();
    if ($errorCode === 1062) {
        redirect_with_status('error', 'duplicate_email');
    }
    redirect_with_status('error', 'db_execute');
}

$userId = (int)$mysqli->insert_id;
$statement->close();

$roleId = 201001;
$roleStatement = $mysqli->prepare(
    'INSERT INTO usuarios_roles (usuario_id, rol_id) VALUES (?, ?)'
);

if ($roleStatement === false) {
    $mysqli->rollback();
    $mysqli->close();
    redirect_with_status('error', 'role_assign');
}

$roleStatement->bind_param('ii', $userId, $roleId);

if (!$roleStatement->execute()) {
    $roleStatement->close();
    $mysqli->rollback();
    $mysqli->close();
    redirect_with_status('error', 'role_assign');
}

$roleStatement->close();
$mysqli->commit();
$mysqli->close();
redirect_with_status('success');

