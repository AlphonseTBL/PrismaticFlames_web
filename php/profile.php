<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$db = @new mysqli(
    '127.0.0.1',
    'lgunprmiuy_admin',
    'UChD1dZxhwSn',
    'lgunprmiuy_PrismaticFlames'
);

if ($db->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo conectar a la base de datos']);
    exit;
}

$db->set_charset('utf8mb4');
$action = $_REQUEST['action'] ?? 'get';

function get_profile(mysqli $db, int $userId): array
{
    $stmt = $db->prepare('SELECT id, nombre, apellido, email, telefono, puntos_acumulados FROM usuarios WHERE id = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('No se pudo obtener el perfil');
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    if (!$row) {
        throw new RuntimeException('Usuario no encontrado');
    }
    return [
        'id' => (int)$row['id'],
        'nombre' => $row['nombre'] ?? '',
        'apellido' => $row['apellido'] ?? '',
        'email' => $row['email'] ?? '',
        'telefono' => $row['telefono'] ?? '',
        'puntos' => isset($row['puntos_acumulados']) ? (int)$row['puntos_acumulados'] : 0,
    ];
}

try {
    if ($action === 'get') {
        echo json_encode(['profile' => get_profile($db, $userId)]);
        exit;
    }

    if ($action === 'update') {
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $apellido = trim((string)($_POST['apellido'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($nombre === '' || $apellido === '' || $email === '' || $telefono === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Todos los campos son obligatorios, excepto la contraseña.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Correo electrónico inválido.']);
            exit;
        }

        // Validar duplicado de email
        $dup = $db->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1');
        if ($dup) {
            $dup->bind_param('si', $email, $userId);
            $dup->execute();
            $dup->store_result();
            if ($dup->num_rows > 0) {
                $dup->close();
                http_response_code(400);
                echo json_encode(['error' => 'El correo ya está registrado.']);
                exit;
            }
            $dup->close();
        }

        $db->begin_transaction();
        $passwordSql = '';
        $types = 'ssssi';
        $params = [$nombre, $apellido, $email, $telefono, $userId];
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            if ($hash === false) {
                throw new RuntimeException('No se pudo encriptar la contraseña');
            }
            $passwordSql = ', password_hash = ?';
            $types = 'ssssis';
            $params = [$nombre, $apellido, $email, $telefono, $userId, $hash];
        }

        $stmt = $db->prepare('UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ? ' . $passwordSql . ' WHERE id = ?');
        if (!$stmt) {
            $db->rollback();
            throw new RuntimeException('No se pudo actualizar el perfil');
        }

        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            $db->rollback();
            throw new RuntimeException('No se pudo actualizar el perfil');
        }
        $stmt->close();
        $db->commit();

        // Refrescar sesión
        $_SESSION['user_name'] = $nombre;
        $_SESSION['user_last_name'] = $apellido;
        $_SESSION['user_email'] = $email;

        echo json_encode(['success' => true, 'profile' => get_profile($db, $userId)]);
        exit;
    }

    if ($action === 'delete') {
        $db->begin_transaction();

        // Borrar carrito e items
        $db->query('DELETE ci FROM carrito_items ci INNER JOIN carritos c ON ci.carrito_id = c.id WHERE c.usuario_id = ' . $userId);
        $delCart = $db->prepare('DELETE FROM carritos WHERE usuario_id = ?');
        if ($delCart) {
            $delCart->bind_param('i', $userId);
            $delCart->execute();
            $delCart->close();
        }

        // Wishlist
        $delWish = $db->prepare('DELETE FROM wishlist WHERE usuario_id = ?');
        if ($delWish) {
            $delWish->bind_param('i', $userId);
            $delWish->execute();
            $delWish->close();
        }

        // Puntos y pedidos
        $db->query('DELETE pp FROM pedidos_puntos pp WHERE pp.usuario_id = ' . $userId);
        $db->query('DELETE pi FROM pedido_items pi INNER JOIN pedidos p ON pi.pedido_id = p.id WHERE p.usuario_id = ' . $userId);
        $delPedidos = $db->prepare('DELETE FROM pedidos WHERE usuario_id = ?');
        if ($delPedidos) {
            $delPedidos->bind_param('i', $userId);
            $delPedidos->execute();
            $delPedidos->close();
        }

        // Roles
        $delRoles = $db->prepare('DELETE FROM usuarios_roles WHERE usuario_id = ?');
        if ($delRoles) {
            $delRoles->bind_param('i', $userId);
            $delRoles->execute();
            $delRoles->close();
        }

        // Usuario
        $delUser = $db->prepare('DELETE FROM usuarios WHERE id = ?');
        if ($delUser) {
            $delUser->bind_param('i', $userId);
            $delUser->execute();
            $delUser->close();
        }

        $db->commit();
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Acción no soportada']);
} catch (Throwable $e) {
    if ($db->errno) {
        $db->rollback();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $db->close();
}

