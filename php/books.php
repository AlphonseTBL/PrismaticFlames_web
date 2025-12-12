<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

/**
 * Normaliza una cadena eliminando acentos, espacios y símbolos.
 */
function normalize_key(string $value): string
{
    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $value);
    $normalized = strtolower($transliterated !== false ? $transliterated : $value);
    return preg_replace('/[^a-z0-9]/', '', $normalized) ?? '';
}

/**
 * Convierte una ruta absoluta a una ruta relativa dentro del proyecto.
 */
function to_public_path(string $absolutePath): string
{
    $projectRoot = realpath(__DIR__ . '/..');
    if ($projectRoot === false) {
        return '';
    }
    $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/') . '/';
    $normalized = str_replace('\\', '/', $absolutePath);
    if (strpos($normalized, $projectRoot) === 0) {
        return ltrim(substr($normalized, strlen($projectRoot)), '/');
    }
    return ltrim($normalized, '/');
}

/**
 * Construye un índice de portadas disponibles en disco para compararlas rápidamente.
 *
 * @return array<int, array{normalized: string, public: string}>
 */
function build_cover_index(string $directory): array
{
    $absoluteDir = realpath($directory);
    if ($absoluteDir === false || !is_dir($absoluteDir)) {
        return [];
    }

    $extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $index = [];
    foreach ($extensions as $extension) {
        $pattern = rtrim($absoluteDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.' . $extension;
        foreach ((glob($pattern) ?: []) as $file) {
            $index[] = [
                'normalized' => normalize_key(pathinfo((string)$file, PATHINFO_FILENAME) ?: ''),
                'public' => to_public_path((string)$file),
            ];
        }
    }

    return $index;
}

/**
 * Devuelve la mejor ruta de portada para un libro, priorizando la almacenada en base de datos.
 *
 * @param array<string, mixed> $row
 * @param array<int, array{normalized: string, public: string}> $coverIndex
 */
function resolve_cover_path(array $row, array $coverIndex): string
{
    $dbValue = trim((string)($row['portada_url'] ?? ''));
    if ($dbValue !== '') {
        if (preg_match('#^(https?:)?//#i', $dbValue)) {
            return $dbValue;
        }
        return ltrim(str_replace('\\', '/', $dbValue), '/');
    }

    $candidates = [];
    if (!empty($row['titulo'])) {
        $candidates[] = normalize_key((string)$row['titulo']);
    }
    if (!empty($row['isbn'])) {
        $candidates[] = normalize_key((string)$row['isbn']);
    }
    if (!empty($row['id'])) {
        $candidates[] = normalize_key((string)$row['id']);
    }

    foreach ($candidates as $candidate) {
        if ($candidate === '') {
            continue;
        }
        foreach ($coverIndex as $item) {
            $normalizedFilename = $item['normalized'];
            if ($normalizedFilename === '') {
                continue;
            }
            if (
                strpos($normalizedFilename, $candidate) !== false ||
                strpos($candidate, $normalizedFilename) !== false
            ) {
                return $item['public'];
            }
        }
    }

    return '';
}

$coverIndex = build_cover_index(__DIR__ . '/../images/Books');

$connection = @new mysqli(
    '127.0.0.1',
    'lgunprmiuy_admin',
    'UChD1dZxhwSn',
    'lgunprmiuy_PrismaticFlames'
);

if ($connection->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'error' => 'No se pudo conectar a la base de datos',
    ]);
    exit;
}

$connection->set_charset('utf8mb4');

$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = trim($_GET['q'] ?? '');

$sql = <<<SQL
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
SQL;

$conditions = [];
if ($bookId > 0) {
    $conditions[] = "l.id = {$bookId}";
}
if ($search !== '') {
    $like = $connection->real_escape_string('%' . $search . '%');
    $conditions[] = "(l.titulo LIKE '{$like}' OR l.descripcion LIKE '{$like}' OR a.nombre LIKE '{$like}')";
}

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= "
GROUP BY l.id, l.titulo, l.descripcion, l.precio, l.stock, l.portada_url, l.fecha_publicacion, l.isbn
ORDER BY l.titulo ASC
";

$result = $connection->query($sql);

if ($result === false) {
    $connection->close();
    http_response_code(500);
    echo json_encode([
        'error' => 'No se pudo obtener la información de los libros',
    ]);
    exit;
}

$books = [];
while ($row = $result->fetch_assoc()) {
    $coverPath = resolve_cover_path($row, $coverIndex);
    $books[] = [
        'id' => (int)$row['id'],
        'titulo' => $row['titulo'],
        'descripcion' => $row['descripcion'],
        'precio' => (float)$row['precio'],
        'stock' => (int)$row['stock'],
        'portada' => $coverPath,
        'portada_url' => $row['portada_url'],
        'fecha_publicacion' => $row['fecha_publicacion'],
        'isbn' => $row['isbn'],
        'autores' => $row['autores'],
        'categorias' => $row['categorias'],
    ];
}

$result->free();
$connection->close();

$response = ['books' => $books];
if ($bookId > 0) {
    $response['book'] = $books[0] ?? null;
    if (!$response['book']) {
        http_response_code(404);
        $response['error'] = 'Libro no encontrado';
    }
}

echo json_encode($response);

