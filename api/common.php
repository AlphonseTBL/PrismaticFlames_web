<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();

/**
 * Devuelve una conexión MySQLi o responde con 500 en caso de fallo.
 */
function api_db(): mysqli
{
    $db = @new mysqli(
        '127.0.0.1',
        'lgunprmiuy_admin',
        'UChD1dZxhwSn',
        'lgunprmiuy_PrismaticFlames'
    );

    if ($db->connect_errno) {
        json_response(500, ['error' => 'No se pudo conectar a la base de datos']);
    }

    $db->set_charset('utf8mb4');
    return $db;
}

/**
 * Envía una respuesta JSON y termina la ejecución.
 */
function json_response(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

/**
 * Lee el cuerpo JSON y devuelve un array asociativo.
 */
function read_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Mezcla POST con el cuerpo JSON para soportar distintos clientes.
 */
function input_data(): array
{
    $data = $_POST;
    $json = read_json_input();
    if (!empty($json)) {
        $data = array_merge($data, $json);
    }
    return $data;
}

/**
 * Exige un usuario autenticado y devuelve su id.
 */
function require_user_id(): int
{
    if (empty($_SESSION['user_id'])) {
        json_response(401, ['error' => 'Debes iniciar sesión.']);
    }
    return (int)$_SESSION['user_id'];
}

/**
 * Verifica si el usuario es Administrador o Moderador.
 */
function user_is_admin_or_moderator(mysqli $db, int $userId): bool
{
    $sql = "
        SELECT 1
        FROM usuarios_roles ur
        INNER JOIN roles r ON r.id = ur.rol_id
        WHERE ur.usuario_id = ?
          AND r.nombre IN ('Moderador', 'Administrador')
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->store_result();
    $isAdmin = $stmt->num_rows > 0;
    $stmt->close();
    return $isAdmin;
}

/**
 * Garantiza la existencia de un carrito y devuelve su id.
 */
function ensure_cart(mysqli $db, int $userId): int
{
    $cartId = 0;
    $stmt = $db->prepare('SELECT id FROM carritos WHERE usuario_id = ? ORDER BY id DESC LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($cartId);
        if ($stmt->fetch()) {
            $stmt->close();
            return (int)$cartId;
        }
        $stmt->close();
    }

    $insert = $db->prepare('INSERT INTO carritos (usuario_id) VALUES (?)');
    if (!$insert) {
        json_response(500, ['error' => 'No se pudo crear el carrito']);
    }
    $insert->bind_param('i', $userId);
    if (!$insert->execute()) {
        $insert->close();
        json_response(500, ['error' => 'No se pudo crear el carrito']);
    }
    $cartId = (int)$db->insert_id;
    $insert->close();
    return $cartId;
}

/**
 * Obtiene items y totales del carrito.
 *
 * @return array{cart_id:int, items:array<int,array<string,mixed>>, totals:array<string,mixed>}
 */
function get_cart_payload(mysqli $db, int $cartId): array
{
    $items = [];
    $totals = ['items' => 0, 'quantity' => 0, 'subtotal' => 0.0];

    $sql = "
        SELECT ci.id, ci.libro_id, ci.cantidad, ci.precio_unitario, l.titulo, l.portada_url
        FROM carrito_items ci
        INNER JOIN libros l ON l.id = ci.libro_id
        WHERE ci.carrito_id = ?
    ";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $cartId);
        $stmt->execute();
        $stmt->bind_result($id, $libroId, $cantidad, $precioUnitario, $titulo, $portadaUrl);
        while ($stmt->fetch()) {
            $qty = (int)$cantidad;
            $price = (float)$precioUnitario;
            $subtotal = $qty * $price;
            $items[] = [
                'item_id' => (int)$id,
                'libro_id' => (int)$libroId,
                'titulo' => $titulo,
                'cantidad' => $qty,
                'precio' => $price,
                'subtotal' => $subtotal,
                'portada_url' => $portadaUrl ?? '',
            ];
            $totals['items']++;
            $totals['quantity'] += $qty;
            $totals['subtotal'] += $subtotal;
        }
        $stmt->close();
    }

    return ['cart_id' => $cartId, 'items' => $items, 'totals' => $totals];
}

/**
 * Calcula puntos obtenidos a partir del total.
 */
function earned_points(float $total): int
{
    return (int)floor($total / 10.0);
}

