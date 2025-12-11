<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

$userId = require_user_id();
$db = api_db();
$method = $_SERVER['REQUEST_METHOD'];
$data = input_data();
$action = $data['action'] ?? ($method === 'GET' ? 'list' : '');

try {
    if ($action === 'add' && $method === 'POST') {
        $bookId = isset($data['libro_id']) ? (int)$data['libro_id'] : (int)($data['libros_id'] ?? 0);
        $quantity = isset($data['cantidad']) ? (int)$data['cantidad'] : 1;
        if ($bookId <= 0 || $quantity <= 0) {
            json_response(400, ['error' => 'Parámetros inválidos']);
        }

        $cartId = ensure_cart($db, $userId);

        $priceStmt = $db->prepare('SELECT precio FROM libros WHERE id = ?');
        if (!$priceStmt) {
            json_response(500, ['error' => 'No se pudo obtener el precio']);
        }
        $priceStmt->bind_param('i', $bookId);
        $priceStmt->execute();
        $priceStmt->bind_result($price);
        if (!$priceStmt->fetch()) {
            $priceStmt->close();
            json_response(404, ['error' => 'Libro no encontrado']);
        }
        $priceStmt->close();

        $itemStmt = $db->prepare('SELECT id, cantidad FROM carrito_items WHERE carrito_id = ? AND libro_id = ?');
        if ($itemStmt) {
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
                $insert = $db->prepare('INSERT INTO carrito_items (carrito_id, libro_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
                if ($insert) {
                    $insert->bind_param('iiid', $cartId, $bookId, $quantity, $price);
                    $insert->execute();
                    $insert->close();
                }
            }
        }

        json_response(200, ['success' => true, 'cart' => get_cart_payload($db, $cartId)]);
    }

    if ($action === 'update' && ($method === 'POST' || $method === 'PUT')) {
        $bookId = isset($data['libro_id']) ? (int)$data['libro_id'] : (int)($data['libros_id'] ?? 0);
        $quantity = isset($data['cantidad']) ? (int)$data['cantidad'] : 0;
        if ($bookId <= 0) {
            json_response(400, ['error' => 'Libro inválido']);
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
        json_response(200, ['success' => true, 'cart' => get_cart_payload($db, $cartId)]);
    }

    if ($action === 'remove' && ($method === 'POST' || $method === 'DELETE')) {
        $bookId = isset($data['libro_id']) ? (int)$data['libro_id'] : (int)($data['libros_id'] ?? 0);
        if ($bookId <= 0) {
            json_response(400, ['error' => 'Libro inválido']);
        }
        $cartId = ensure_cart($db, $userId);
        $del = $db->prepare('DELETE FROM carrito_items WHERE carrito_id = ? AND libro_id = ?');
        if ($del) {
            $del->bind_param('ii', $cartId, $bookId);
            $del->execute();
            $del->close();
        }
        json_response(200, ['success' => true, 'cart' => get_cart_payload($db, $cartId)]);
    }

    if ($action === 'clear') {
        $cartId = ensure_cart($db, $userId);
        $db->query('DELETE FROM carrito_items WHERE carrito_id = ' . $cartId);
        json_response(200, ['success' => true, 'cart' => get_cart_payload($db, $cartId)]);
    }

    // Listado por defecto
    $cartId = ensure_cart($db, $userId);
    json_response(200, get_cart_payload($db, $cartId));
} finally {
    $db->close();
}

