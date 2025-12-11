<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión para usar la lista de deseos.']);
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

/**
 * Devuelve el nombre de la columna de libro en la tabla wishlist.
 * Soporta esquemas con "libro_id" o "libros_id".
 */
function wishlist_book_column(mysqli $db): string
{
    static $col = null;
    if ($col !== null) {
        return $col;
    }
    $col = 'libro_id';
    $check = $db->query("SHOW COLUMNS FROM wishlist LIKE 'libro_id'");
    if ($check && $check->num_rows === 0) {
        $col = 'libros_id';
    }
    if ($check) {
        $check->close();
    }
    return $col;
}

function get_wishlist(mysqli $db, int $userId): array
{
    $items = [];
    $bookCol = wishlist_book_column($db);
    $sql = "
        SELECT w.id, w.{$bookCol} AS libros_id, w.fecha_agregado, l.titulo, l.precio, l.portada_url
        FROM wishlist w
        INNER JOIN libros l ON l.id = w.{$bookCol}
        WHERE w.usuario_id = ?
        ORDER BY w.id DESC
    ";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return $items;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($id, $librosId, $fechaAgregado, $titulo, $precio, $portadaUrl);
    while ($stmt->fetch()) {
        $items[] = [
            'id' => (int)$id,
            'libros_id' => (int)$librosId,
            'fecha_agregado' => $fechaAgregado,
            'titulo' => $titulo,
            'precio' => (float)$precio,
            'portada_url' => $portadaUrl ?? '',
        ];
    }
    $stmt->close();
    return $items;
}

try {
    if ($action === 'add') {
        $bookId = isset($_POST['libro_id']) ? (int)$_POST['libro_id'] : (int)($_POST['libros_id'] ?? 0);
        if ($bookId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Libro inválido']);
            exit;
        }

        $bookCol = wishlist_book_column($db);

        // Evitar duplicados
        $exists = $db->prepare("SELECT id FROM wishlist WHERE usuario_id = ? AND {$bookCol} = ? LIMIT 1");
        if ($exists) {
            $exists->bind_param('ii', $userId, $bookId);
            $exists->execute();
            $exists->bind_result($wid);
            if ($exists->fetch()) {
                $exists->close();
                echo json_encode(['success' => true, 'items' => get_wishlist($db, $userId)]);
                exit;
            }
            $exists->close();
        }

        $ins = $db->prepare("INSERT INTO wishlist (usuario_id, {$bookCol}, fecha_agregado) VALUES (?, ?, NOW())");
        if (!$ins) {
            throw new RuntimeException('No se pudo agregar a la lista de deseos');
        }
        $ins->bind_param('ii', $userId, $bookId);
        $ins->execute();
        $ins->close();

        echo json_encode(['success' => true, 'items' => get_wishlist($db, $userId)]);
        exit;
    }

    if ($action === 'remove') {
        $bookId = isset($_POST['libro_id']) ? (int)$_POST['libro_id'] : (int)($_POST['libros_id'] ?? 0);
        if ($bookId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Libro inválido']);
            exit;
        }
        $bookCol = wishlist_book_column($db);
        $del = $db->prepare("DELETE FROM wishlist WHERE usuario_id = ? AND {$bookCol} = ?");
        if ($del) {
            $del->bind_param('ii', $userId, $bookId);
            $del->execute();
            $del->close();
        }
        echo json_encode(['success' => true, 'items' => get_wishlist($db, $userId)]);
        exit;
    }

    // Listado
    echo json_encode(['items' => get_wishlist($db, $userId)]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $db->close();
}

