<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión para pagar.']);
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

function table_exists(mysqli $db, string $table): bool
{
    $safe = $db->real_escape_string($table);
    $sql = "SHOW TABLES LIKE '$safe'";
    $res = $db->query($sql);
    if (!$res) {
        return false;
    }
    $exists = $res->num_rows > 0;
    $res->close();
    return $exists;
}

function column_exists(mysqli $db, string $table, string $column): bool
{
    $safeTable = $db->real_escape_string($table);
    $safeCol = $db->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `$safeTable` LIKE '$safeCol'";
    $res = $db->query($sql);
    if (!$res) {
        return false;
    }
    $exists = $res->num_rows > 0;
    $res->close();
    return $exists;
}

try {
    // Obtener carrito del usuario
    $cartId = 0;
    $cartStmt = $db->prepare('SELECT id FROM carritos WHERE usuario_id = ? ORDER BY id DESC LIMIT 1');
    if ($cartStmt) {
        $cartStmt->bind_param('i', $userId);
        $cartStmt->execute();
        $cartStmt->bind_result($cartId);
        $cartStmt->fetch();
        $cartStmt->close();
    }
    if ($cartId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'No tienes un carrito activo.']);
        exit;
    }

    // Calcular total del carrito
    $total = 0.0;
    $items = [];
    $itemsStmt = $db->prepare('SELECT libro_id, cantidad, precio_unitario FROM carrito_items WHERE carrito_id = ?');
    if (!$itemsStmt) {
        throw new RuntimeException('No se pudo leer el carrito');
    }
    $itemsStmt->bind_param('i', $cartId);
    $itemsStmt->execute();
    $itemsStmt->bind_result($bookIdRow, $qty, $price);
    while ($itemsStmt->fetch()) {
        $items[] = [
            'libro_id' => (int)$bookIdRow,
            'cantidad' => (int)$qty,
            'precio' => (float)$price,
        ];
        $total += ((int)$qty) * ((float)$price);
    }
    $itemsStmt->close();

    if ($total <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'El carrito está vacío.']);
        exit;
    }

    // Datos de entrada
    $metodoSeleccion = $_POST['metodo_pago'] ?? '';
    $metodoPago = 'no_especificado';
    if ($metodoSeleccion === 'credit') {
        $metodoPago = 'tarjeta_credito';
    } elseif ($metodoSeleccion === 'debit') {
        $metodoPago = 'tarjeta_debito';
    } elseif ($metodoSeleccion === 'paypal') {
        $metodoPago = 'paypal';
    }

    $direccion = trim((string)($_POST['direccion_envio'] ?? ''));
    if ($direccion === '') {
        $direccion = trim((string)($_POST['direccion'] ?? ''));
    }
    if ($direccion === '') {
        http_response_code(400);
        echo json_encode(['error' => 'La dirección es requerida.']);
        exit;
    }

    $puntos = (int)floor($total / 10);

    // Insertar pedido (columnas fijas)
    $sql = 'INSERT INTO pedidos (`usuario_id`, `total`, `puntos_obtenidos`, `estado`, `metodo_pago`, `direccion_envio`, `fecha_pedido`) VALUES (?, ?, ?, ?, ?, ?, NOW())';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar el pedido: ' . $db->error);
    }

    $estado = 'pendiente';
    $stmt->bind_param('idisss', $userId, $total, $puntos, $estado, $metodoPago, $direccion);
    if (!$stmt->execute()) {
        throw new RuntimeException('No se pudo guardar el pedido: ' . $stmt->error);
    }
    $pedidoId = (int)$db->insert_id;
    $stmt->close();

    // Insertar items del pedido
    if (!empty($items)) {
        $itemStmt = $db->prepare('INSERT INTO pedido_items (`pedido_id`, `libro_id`, `cantidad`, `precio_unitario`) VALUES (?, ?, ?, ?)');
        if (!$itemStmt) {
            throw new RuntimeException('No se pudo preparar los items del pedido: ' . $db->error);
        }
        foreach ($items as $it) {
            $libroId = $it['libro_id'];
            $cantidad = $it['cantidad'];
            $precio = $it['precio'];
            $itemStmt->bind_param('iiid', $pedidoId, $libroId, $cantidad, $precio);
            $itemStmt->execute();
        }
        $itemStmt->close();
    }

    // Guardar puntos en pedidos_puntos si existe la tabla
    if (table_exists($db, 'pedidos_puntos')) {
        $ppPuntosCol = column_exists($db, 'pedidos_puntos', 'puntos_obtenidos') ? 'puntos_obtenidos' : (column_exists($db, 'pedidos_puntos', 'puntos') ? 'puntos' : null);
        $ppFechaCol = column_exists($db, 'pedidos_puntos', 'fecha') ? 'fecha' : (column_exists($db, 'pedidos_puntos', 'fecha_registro') ? 'fecha_registro' : null);

        if ($ppPuntosCol !== null && column_exists($db, 'pedidos_puntos', 'usuario_id') && column_exists($db, 'pedidos_puntos', 'pedido_id')) {
            $ppCols = ['`usuario_id`', '`pedido_id`', "`$ppPuntosCol`"];
            $ppPlaceholders = ['?', '?', '?'];
            $ppTypes = 'iii';
            $ppUser = $userId;
            $ppPedido = $pedidoId;
            $ppPts = $puntos;

            if ($ppFechaCol !== null) {
                $ppCols[] = "`$ppFechaCol`";
                $ppPlaceholders[] = 'NOW()';
            }

            $ppSql = 'INSERT INTO pedidos_puntos (' . implode(',', $ppCols) . ') VALUES (' . implode(',', $ppPlaceholders) . ')';
            $ppStmt = $db->prepare($ppSql);
            if ($ppStmt) {
                $ppStmt->bind_param($ppTypes, $ppUser, $ppPedido, $ppPts);
                $ppStmt->execute();
                $ppStmt->close();
            }
        }
    }

    // Actualizar puntos del usuario si existe la columna
    if (column_exists($db, 'usuarios', 'puntos_acumulados')) {
        $upd = $db->prepare('UPDATE usuarios SET puntos_acumulados = puntos_acumulados + ? WHERE id = ?');
        if ($upd) {
            $upd->bind_param('ii', $puntos, $userId);
            $upd->execute();
            $upd->close();
            $_SESSION['user_points'] = ($_SESSION['user_points'] ?? 0) + $puntos;
        }
    }

    // Vaciar carrito
    $clear = $db->prepare('DELETE FROM carrito_items WHERE carrito_id = ?');
    if ($clear) {
        $clear->bind_param('i', $cartId);
        $clear->execute();
        $clear->close();
    }

    echo json_encode([
        'success' => true,
        'pedido_id' => $pedidoId,
        'total' => $total,
        'puntos' => $puntos,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $db->close();
}

