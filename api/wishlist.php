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
        if ($bookId <= 0) {
            json_response(400, ['error' => 'Libro inválido']);
        }

        $exists = $db->prepare('SELECT id FROM wishlist WHERE usuario_id = ? AND libro_id = ?');
        if ($exists) {
            $exists->bind_param('ii', $userId, $bookId);
            $exists->execute();
            $exists->store_result();
            if ($exists->num_rows > 0) {
                $exists->close();
                json_response(200, ['success' => true, 'message' => 'Ya estaba en favoritos']);
            }
            $exists->close();
        }

        $insert = $db->prepare('INSERT INTO wishlist (usuario_id, libro_id) VALUES (?, ?)');
        if (!$insert) {
            json_response(500, ['error' => 'No se pudo agregar a favoritos']);
        }
        $insert->bind_param('ii', $userId, $bookId);
        $insert->execute();
        $insert->close();

        json_response(201, ['success' => true]);
    }

    if (($action === 'remove' || $method === 'DELETE') && ($method === 'POST' || $method === 'DELETE')) {
        $bookId = isset($data['libro_id']) ? (int)$data['libro_id'] : (int)($data['libros_id'] ?? 0);
        if ($bookId <= 0) {
            json_response(400, ['error' => 'Libro inválido']);
        }
        $del = $db->prepare('DELETE FROM wishlist WHERE usuario_id = ? AND libro_id = ?');
        if ($del) {
            $del->bind_param('ii', $userId, $bookId);
            $del->execute();
            $del->close();
        }
        json_response(200, ['success' => true]);
    }

    // Listar wishlist
    $sql = "
        SELECT w.id, w.libro_id, w.fecha_agregado, l.titulo, l.precio, l.portada_url
        FROM wishlist w
        INNER JOIN libros l ON l.id = w.libro_id
        WHERE w.usuario_id = ?
        ORDER BY w.fecha_agregado DESC
    ";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        json_response(500, ['error' => 'No se pudo listar favoritos']);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($id, $libroId, $fecha, $titulo, $precio, $portada);

    $items = [];
    while ($stmt->fetch()) {
        $items[] = [
            'id' => (int)$id,
            'libro_id' => (int)$libroId,
            'titulo' => $titulo,
            'precio' => (float)$precio,
            'portada_url' => $portada,
            'fecha_agregado' => $fecha,
        ];
    }
    $stmt->close();

    json_response(200, ['wishlist' => $items]);
} finally {
    $db->close();
}

