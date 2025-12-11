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

try {
    $orders = [];
    $stmt = $db->prepare('SELECT id, total, puntos_obtenidos, estado, metodo_pago, direccion_envio, fecha_pedido FROM pedidos WHERE usuario_id = ? ORDER BY fecha_pedido DESC');
    if (!$stmt) {
        throw new RuntimeException('No se pudieron obtener los pedidos');
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($id, $total, $puntos, $estado, $metodo, $direccion, $fecha);
    while ($stmt->fetch()) {
        $orders[] = [
            'id' => (int)$id,
            'total' => (float)$total,
            'puntos_obtenidos' => (int)$puntos,
            'estado' => $estado,
            'metodo_pago' => $metodo,
            'direccion_envio' => $direccion,
            'fecha_pedido' => $fecha,
            'items' => [],
        ];
    }
    $stmt->close();

    if (!empty($orders)) {
        $itemStmt = $db->prepare('SELECT pi.pedido_id, pi.id, pi.libro_id, pi.cantidad, pi.precio_unitario, l.titulo, l.portada_url FROM pedido_items pi LEFT JOIN libros l ON l.id = pi.libro_id WHERE pi.pedido_id = ?');
        if ($itemStmt) {
            foreach ($orders as &$order) {
                $pedidoId = $order['id'];
                $order['items'] = [];
                $itemStmt->bind_param('i', $pedidoId);
                $itemStmt->execute();
                $itemStmt->bind_result($pId, $itemId, $libroId, $cant, $precioU, $titulo, $portada);
                while ($itemStmt->fetch()) {
                    $order['items'][] = [
                        'id' => (int)$itemId,
                        'libro_id' => (int)$libroId,
                        'cantidad' => (int)$cant,
                        'precio_unitario' => (float)$precioU,
                        'titulo' => $titulo,
                        'portada_url' => $portada,
                    ];
                }
            }
            $itemStmt->close();
        }
    }

    echo json_encode(['orders' => $orders]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $db->close();
}


