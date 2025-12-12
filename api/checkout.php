<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['error' => 'Método no permitido']);
}

$userId = require_user_id();
$db = api_db();
$data = input_data();

$direccion = trim($data['direccion_envio'] ?? $data['direccion'] ?? $data['address'] ?? '');
$metodo = trim($data['metodo_pago'] ?? $data['payment_method'] ?? '');

if ($direccion === '' || $metodo === '') {
    $db->close();
    json_response(400, ['error' => 'Dirección y método de pago son obligatorios']);
}

$cartId = ensure_cart($db, $userId);
$cart = get_cart_payload($db, $cartId);

if (empty($cart['items'])) {
    $db->close();
    json_response(400, ['error' => 'El carrito está vacío']);
}

$total = 0.0;
foreach ($cart['items'] as $item) {
    $total += (float)$item['subtotal'];
}
$puntos = earned_points($total);
$estado = 'pendiente';

$db->begin_transaction();

$pedidoStmt = $db->prepare('INSERT INTO pedidos (usuario_id, total, puntos_obtenidos, estado, metodo_pago, direccion_envio) VALUES (?, ?, ?, ?, ?, ?)');
if (!$pedidoStmt) {
    $db->rollback();
    $db->close();
    json_response(500, ['error' => 'No se pudo crear el pedido']);
}
$pedidoStmt->bind_param('idisss', $userId, $total, $puntos, $estado, $metodo, $direccion);
if (!$pedidoStmt->execute()) {
    $pedidoStmt->close();
    $db->rollback();
    $db->close();
    json_response(500, ['error' => 'No se pudo crear el pedido']);
}
$pedidoId = (int)$db->insert_id;
$pedidoStmt->close();

$itemStmt = $db->prepare('INSERT INTO pedido_items (pedido_id, libro_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
if (!$itemStmt) {
    $db->rollback();
    $db->close();
    json_response(500, ['error' => 'No se pudo registrar los items']);
}

foreach ($cart['items'] as $item) {
    $libroId = (int)$item['libro_id'];
    $cantidad = (int)$item['cantidad'];
    $precioUnitario = (float)$item['precio'];
    $itemStmt->bind_param('iiid', $pedidoId, $libroId, $cantidad, $precioUnitario);
    $itemStmt->execute();
}
$itemStmt->close();

$updatePoints = $db->prepare('UPDATE usuarios SET puntos_acumulados = puntos_acumulados + ? WHERE id = ?');
if ($updatePoints) {
    $updatePoints->bind_param('ii', $puntos, $userId);
    $updatePoints->execute();
    $updatePoints->close();
}

$db->query('DELETE FROM carrito_items WHERE carrito_id = ' . $cartId);

$db->commit();
$db->close();

$_SESSION['user_points'] = ($_SESSION['user_points'] ?? 0) + $puntos;

json_response(201, [
    'success' => true,
    'pedido_id' => $pedidoId,
    'total' => $total,
    'puntos_obtenidos' => $puntos,
    'estado' => $estado,
]);




