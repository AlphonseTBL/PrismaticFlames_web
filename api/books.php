<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

$db = api_db();

$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = trim($_GET['q'] ?? '');

$baseSql = "
    SELECT
        l.id,
        l.titulo,
        l.descripcion,
        l.precio,
        l.stock,
        l.portada_url,
        l.fecha_publicacion,
        l.isbn,
        GROUP_CONCAT(DISTINCT a.nombre ORDER BY a.nombre SEPARATOR ', ') AS autores,
        GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS categorias
    FROM libros l
    LEFT JOIN libros_autores la ON la.libro_id = l.id
    LEFT JOIN autores a ON a.id = la.autor_id
    LEFT JOIN libros_categorias lc ON lc.libro_id = l.id
    LEFT JOIN categorias c ON c.id = lc.categoria_id
";

$conditions = [];
$params = [];
$types = '';

if ($bookId > 0) {
    $conditions[] = 'l.id = ?';
    $params[] = $bookId;
    $types .= 'i';
}

if ($search !== '') {
    $conditions[] = '(l.titulo LIKE ? OR l.descripcion LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($conditions) {
    $baseSql .= ' WHERE ' . implode(' AND ', $conditions);
}

$baseSql .= '
GROUP BY l.id, l.titulo, l.descripcion, l.precio, l.stock, l.portada_url, l.fecha_publicacion, l.isbn
ORDER BY l.titulo ASC
';

if ($params) {
    $stmt = $db->prepare($baseSql);
    if (!$stmt) {
        $db->close();
        json_response(500, ['error' => 'No se pudo preparar la consulta.']);
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $db->query($baseSql);
}

if ($result === false) {
    $db->close();
    json_response(500, ['error' => 'No se pudo obtener los libros.']);
}

$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = [
        'id' => (int)$row['id'],
        'titulo' => $row['titulo'],
        'descripcion' => $row['descripcion'],
        'precio' => (float)$row['precio'],
        'stock' => (int)$row['stock'],
        'portada_url' => $row['portada_url'],
        'fecha_publicacion' => $row['fecha_publicacion'],
        'isbn' => $row['isbn'],
        'autores' => $row['autores'],
        'categorias' => $row['categorias'],
    ];
}

if ($result instanceof mysqli_result) {
    $result->free();
}

$db->close();

$response = ['books' => $books];
if ($bookId > 0) {
    $response['book'] = $books[0] ?? null;
    if (!$response['book']) {
        json_response(404, ['error' => 'Libro no encontrado']);
    }
}

json_response(200, $response);




