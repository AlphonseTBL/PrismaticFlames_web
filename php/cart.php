<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión para usar el carrito.']);
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

$action = $_REQUEST['action'] ?? 'list';

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
        throw new RuntimeException('No se pudo crear el carrito');
    }
    $insert->bind_param('i', $userId);
    if (!$insert->execute()) {
        $insert->close();
        throw new RuntimeException('No se pudo crear el carrito');
    }
    $cartId = (int)$db->insert_id;
    $insert->close();
    return $cartId;
}

/**
 * @return array{cart_id:int, items:array<int,array<string,mixed>>, totals:array<string,mixed>}
 */
function get_cart_data(mysqli $db, int $cartId): array
{
    $items = [];
    $totals = [
        'items' => 0,
        'quantity' => 0,
        'subtotal' => 0.0,
    ];

    $sql = "
        SELECT ci.id, ci.libro_id AS libros_id, ci.cantidad, ci.precio_unitario, l.titulo, l.portada_url
        FROM carrito_items ci
        INNER JOIN libros l ON l.id = ci.libro_id
        WHERE ci.carrito_id = ?
    ";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return ['cart_id' => $cartId, 'items' => [], 'totals' => $totals];
    }
    $stmt->bind_param('i', $cartId);
    $stmt->execute();
    $stmt->bind_result($id, $librosId, $cantidad, $precioUnitario, $titulo, $portadaUrl);
    while ($stmt->fetch()) {
        $quantity = (int)$cantidad;
        $price = (float)$precioUnitario;
        $subtotal = $quantity * $price;
        $items[] = [
            'item_id' => (int)$id,
            'libros_id' => (int)$librosId,
            'titulo' => $titulo,
            'cantidad' => $quantity,
            'precio' => $price,
            'subtotal' => $subtotal,
            'portada_url' => $portadaUrl ?? '',
        ];
        $totals['items']++;
        $totals['quantity'] += $quantity;
        $totals['subtotal'] += $subtotal;
    }
    $stmt->close();
    return [
        'cart_id' => $cartId,
        'items' => $items,
        'totals' => $totals,
    ];
}

try {
    if ($action === 'add') {
        $bookId = isset($_POST['libro_id']) ? (int)$_POST['libro_id'] : (int)($_POST['libros_id'] ?? 0);
        $quantity = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
        if ($bookId <= 0 || $quantity <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Parámetros inválidos']);
            exit;
        }

        $cartId = ensure_cart($db, $userId);

        // Obtener precio actual del libro
        $priceStmt = $db->prepare('SELECT precio FROM libros WHERE id = ?');
        if (!$priceStmt) {
            throw new RuntimeException('No se pudo preparar la consulta de precio');
        }
        $priceStmt->bind_param('i', $bookId);
        $priceStmt->execute();
        $priceStmt->bind_result($price);
        if (!$priceStmt->fetch()) {
            $priceStmt->close();
            http_response_code(404);
            echo json_encode(['error' => 'Libro no encontrado']);
            exit;
        }
        $priceStmt->close();

        // Verificar si ya existe el item
        $itemStmt = $db->prepare('SELECT id, cantidad FROM carrito_items WHERE carrito_id = ? AND libro_id = ?');
        if (!$itemStmt) {
            throw new RuntimeException('No se pudo preparar la consulta del carrito');
        }
        $itemStmt->bind_param('ii', $cartId, $bookId);
        $itemStmt->execute();
        $itemStmt->bind_result($itemId, $currentQty);
        if ($itemStmt->fetch()) {
            $itemStmt->close();
            $newQty = $currentQty + $quantity;
            $update = $db->prepare('UPDATE carrito_items SET cantidad = ? WHERE id = ?');
            if ($update) {
                $update->bind_param('ii', $newQty, $itemId);
                $update->execute();
                $update->close();
            }
        } else {
            $itemStmt->close();
            $insertItem = $db->prepare('INSERT INTO carrito_items (carrito_id, libro_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
            if (!$insertItem) {
                throw new RuntimeException('No se pudo insertar el item');
            }
            $insertItem->bind_param('iiid', $cartId, $bookId, $quantity, $price);
            $insertItem->execute();
            $insertItem->close();
        }

        echo json_encode(['success' => true, 'cart' => get_cart_data($db, $cartId)]);
        exit;
    }

    if ($action === 'update') {
        $bookId = isset($_POST['libro_id']) ? (int)$_POST['libro_id'] : (int)($_POST['libros_id'] ?? 0);
        $quantity = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
        if ($bookId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Libro inválido']);
            exit;
        }

        $cartId = ensure_cart($db, $userId);

        if ($quantity <= 0) {
            $del = $db->prepare('DELETE FROM carrito_items WHERE carrito_id = ? AND libro_id = ?');
            if ($del) {
                $del->bind_param('ii', $cartId, $bookId);
                $del->execute();
                $del->close();
            }
        } else {
            $upd = $db->prepare('UPDATE carrito_items SET cantidad = ? WHERE carrito_id = ? AND libro_id = ?');
            if ($upd) {
                $upd->bind_param('iii', $quantity, $cartId, $bookId);
                $upd->execute();
                $upd->close();
            }
        }

        echo json_encode(['success' => true, 'cart' => get_cart_data($db, $cartId)]);
        exit;
    }

    if ($action === 'remove') {
        $bookId = isset($_POST['libro_id']) ? (int)$_POST['libro_id'] : (int)($_POST['libros_id'] ?? 0);
        if ($bookId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Libro inválido']);
            exit;
        }
        $cartId = ensure_cart($db, $userId);
        $del = $db->prepare('DELETE FROM carrito_items WHERE carrito_id = ? AND libro_id = ?');
        if ($del) {
            $del->bind_param('ii', $cartId, $bookId);
            $del->execute();
            $del->close();
        }
        echo json_encode(['success' => true, 'cart' => get_cart_data($db, $cartId)]);
        exit;
    }

    if ($action === 'clear') {
        $cartId = ensure_cart($db, $userId);
        $db->query('DELETE FROM carrito_items WHERE carrito_id = ' . $cartId);
        echo json_encode(['success' => true, 'cart' => get_cart_data($db, $cartId)]);
        exit;
    }

    // Acción por defecto: listar
    $cartId = ensure_cart($db, $userId);
    echo json_encode(get_cart_data($db, $cartId));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $db->close();
}

