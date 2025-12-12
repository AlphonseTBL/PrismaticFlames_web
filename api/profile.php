<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

$userId = require_user_id();
$db = api_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT id, nombre, apellido, email, telefono, puntos_acumulados FROM usuarios WHERE id = ? LIMIT 1');
    if (!$stmt) {
        $db->close();
        json_response(500, ['error' => 'No se pudo obtener el perfil']);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($id, $nombre, $apellido, $email, $telefono, $puntos);
    if (!$stmt->fetch()) {
        $stmt->close();
        $db->close();
        json_response(404, ['error' => 'Usuario no encontrado']);
    }
    $stmt->close();
    $db->close();
    json_response(200, [
        'user' => [
            'id' => (int)$id,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $email,
            'telefono' => $telefono,
            'puntos' => (int)$puntos,
        ],
    ]);
}

if ($method === 'PUT' || ($method === 'POST' && ($_POST['action'] ?? '') === 'update')) {
    $data = input_data();
    $fields = [];
    $params = [];
    $types = '';

    if (isset($data['nombre'])) {
        $fields[] = 'nombre = ?';
        $params[] = trim((string)$data['nombre']);
        $types .= 's';
    }
    if (isset($data['apellido'])) {
        $fields[] = 'apellido = ?';
        $params[] = trim((string)$data['apellido']);
        $types .= 's';
    }
    if (isset($data['email'])) {
        $email = trim((string)$data['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $db->close();
            json_response(400, ['error' => 'Email inválido']);
        }
        $fields[] = 'email = ?';
        $params[] = $email;
        $types .= 's';
    }
    if (isset($data['telefono'])) {
        $fields[] = 'telefono = ?';
        $params[] = trim((string)$data['telefono']);
        $types .= 's';
    }
    if (!empty($data['password'])) {
        $hash = password_hash((string)$data['password'], PASSWORD_BCRYPT);
        if ($hash === false) {
            $db->close();
            json_response(500, ['error' => 'No se pudo actualizar la contraseña']);
        }
        $fields[] = 'password_hash = ?';
        $params[] = $hash;
        $types .= 's';
    }

    if (!$fields) {
        $db->close();
        json_response(400, ['error' => 'No hay cambios para guardar']);
    }

    $types .= 'i';
    $params[] = $userId;
    $sql = 'UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id = ? LIMIT 1';

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        $db->close();
        json_response(500, ['error' => 'No se pudo preparar la actualización']);
    }
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        $error = $stmt->errno;
        $stmt->close();
        $db->close();
        if ($error === 1062) {
            json_response(409, ['error' => 'El email ya está registrado']);
        }
        json_response(500, ['error' => 'No se pudo actualizar el perfil']);
    }
    $stmt->close();

    // Refrescar datos en sesión
    $refresh = $db->prepare('SELECT nombre, apellido, email, telefono, puntos_acumulados FROM usuarios WHERE id = ? LIMIT 1');
    if ($refresh) {
        $refresh->bind_param('i', $userId);
        $refresh->execute();
        $refresh->bind_result($nombre, $apellido, $email, $telefono, $puntos);
        if ($refresh->fetch()) {
            $_SESSION['user_name'] = $nombre;
            $_SESSION['user_last_name'] = $apellido;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_points'] = (int)$puntos;
        }
        $refresh->close();
    }

    $db->close();
    json_response(200, ['success' => true]);
}

if ($method === 'DELETE') {
    $db->begin_transaction();
    try {
        $delWishlist = $db->prepare('DELETE FROM wishlist WHERE usuario_id = ?');
        if ($delWishlist) {
            $delWishlist->bind_param('i', $userId);
            $delWishlist->execute();
            $delWishlist->close();
        }

        $db->query('DELETE ci FROM carrito_items ci INNER JOIN carritos c ON ci.carrito_id = c.id WHERE c.usuario_id = ' . $userId);
        $delCarts = $db->prepare('DELETE FROM carritos WHERE usuario_id = ?');
        if ($delCarts) {
            $delCarts->bind_param('i', $userId);
            $delCarts->execute();
            $delCarts->close();
        }

        $db->query('DELETE pi FROM pedido_items pi INNER JOIN pedidos p ON pi.pedido_id = p.id WHERE p.usuario_id = ' . $userId);
        $delOrders = $db->prepare('DELETE FROM pedidos WHERE usuario_id = ?');
        if ($delOrders) {
            $delOrders->bind_param('i', $userId);
            $delOrders->execute();
            $delOrders->close();
        }

        $delRoles = $db->prepare('DELETE FROM usuarios_roles WHERE usuario_id = ?');
        if ($delRoles) {
            $delRoles->bind_param('i', $userId);
            $delRoles->execute();
            $delRoles->close();
        }

        $delUser = $db->prepare('DELETE FROM usuarios WHERE id = ? LIMIT 1');
        if ($delUser) {
            $delUser->bind_param('i', $userId);
            $delUser->execute();
            $delUser->close();
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        $db->close();
        json_response(500, ['error' => 'No se pudo eliminar la cuenta']);
    }

    $db->close();
    $_SESSION = [];
    session_destroy();
    json_response(200, ['success' => true]);
}

$db->close();
json_response(405, ['error' => 'Método no permitido']);




