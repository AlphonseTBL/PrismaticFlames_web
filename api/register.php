<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['error' => 'Método no permitido']);
}

$data = input_data();
$firstName = trim($data['firstName'] ?? $data['nombre'] ?? '');
$lastName = trim($data['lastName'] ?? $data['apellido'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? $data['telefono'] ?? '');
$password = (string)($data['password'] ?? '');
$confirm = (string)($data['confirmPassword'] ?? $data['password_confirm'] ?? '');

if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $password === '') {
    json_response(400, ['error' => 'Todos los campos son obligatorios.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(400, ['error' => 'Email inválido.']);
}

if ($confirm !== '' && $password !== $confirm) {
    json_response(400, ['error' => 'Las contraseñas no coinciden.']);
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT);
if ($passwordHash === false) {
    json_response(500, ['error' => 'No se pudo procesar la contraseña.']);
}

$db = api_db();
$db->begin_transaction();

$insert = $db->prepare('INSERT INTO usuarios (nombre, apellido, email, password_hash, telefono) VALUES (?, ?, ?, ?, ?)');
if (!$insert) {
    $db->rollback();
    $db->close();
    json_response(500, ['error' => 'No se pudo preparar la inserción.']);
}

$insert->bind_param('sssss', $firstName, $lastName, $email, $passwordHash, $phone);
if (!$insert->execute()) {
    $error = $insert->errno;
    $insert->close();
    $db->rollback();
    $db->close();
    if ($error === 1062) {
        json_response(409, ['error' => 'El email ya está registrado.']);
    }
    json_response(500, ['error' => 'No se pudo registrar al usuario.']);
}

$userId = (int)$db->insert_id;
$insert->close();

$roleId = 201001; // Cliente
$roleStmt = $db->prepare('INSERT INTO usuarios_roles (usuario_id, rol_id) VALUES (?, ?)');
if (!$roleStmt) {
    $db->rollback();
    $db->close();
    json_response(500, ['error' => 'No se pudo asignar el rol.']);
}
$roleStmt->bind_param('ii', $userId, $roleId);
if (!$roleStmt->execute()) {
    $roleStmt->close();
    $db->rollback();
    $db->close();
    json_response(500, ['error' => 'No se pudo asignar el rol.']);
}
$roleStmt->close();

$db->commit();
$db->close();

$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $firstName;
$_SESSION['user_last_name'] = $lastName;
$_SESSION['user_email'] = $email;
$_SESSION['user_points'] = 0;
$_SESSION['user_role'] = 'user';

json_response(201, [
    'success' => true,
    'user' => [
        'id' => $userId,
        'nombre' => $firstName,
        'apellido' => $lastName,
        'email' => $email,
        'telefono' => $phone,
    ],
]);

