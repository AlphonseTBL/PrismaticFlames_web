<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

$userId = require_user_id();
$db = api_db();

$sql = "
    SELECT
        p.id,
        p.total,
        p.puntos_obtenidos,
        p.estado,
        p.metodo_pago,
        p.direccion_envio,
        p.fecha_pedido,
        pi.id AS item_id,
        pi.libro_id,
        pi.cantidad,
        pi.precio_unitario,
        l.titulo,
        l.portada_url
    FROM pedidos p
    LEFT JOIN pedido_items pi ON pi.pedido_id = p.id
    LEFT JOIN libros l ON l.id = pi.libro_id
    WHERE p.usuario_id = ?
    ORDER BY p.fecha_pedido DESC, p.id DESC
";

$stmt = $db->prepare($sql);
if (!$stmt) {
    $db->close();
    json_response(500, ['error' => 'No se pudo obtener pedidos']);
}
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($pedidoId, $total, $puntos, $estado, $metodo, $direccion, $fecha, $itemId, $libroId, $cantidad, $precioUnitario, $titulo, $portada);

$orders = [];
while ($stmt->fetch()) {
    if (!isset($orders[$pedidoId])) {
        $orders[$pedidoId] = [
            'id' => (int)$pedidoId,
            'total' => (float)$total,
            'puntos_obtenidos' => (int)$puntos,
            'estado' => $estado,
            'metodo_pago' => $metodo,
            'direccion_envio' => $direccion,
            'fecha_pedido' => $fecha,
            'items' => [],
        ];
    }
    if ($itemId !== null) {
        $orders[$pedidoId]['items'][] = [
            'id' => (int)$itemId,
            'libro_id' => (int)$libroId,
            'titulo' => $titulo,
            'cantidad' => (int)$cantidad,
            'precio_unitario' => (float)$precioUnitario,
            'portada_url' => $portada,
        ];
    }
}
$stmt->close();
$db->close();

json_response(200, ['orders' => array_values($orders)]);




