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

function is_admin_or_mod(mysqli $db, int $userId): bool
{
    $sql = "
        SELECT 1
        FROM usuarios_roles ur
        INNER JOIN roles r ON r.id = ur.rol_id
        WHERE ur.usuario_id = ?
          AND r.nombre IN ('Moderador','Administrador')
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->store_result();
    $ok = $stmt->num_rows > 0;
    $stmt->close();
    return $ok;
}

if (!is_admin_or_mod($db, $userId)) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    $db->close();
    exit;
}

$action = $_REQUEST['action'] ?? 'tables';
$dbName = $db->real_escape_string($db->query('SELECT DATABASE()')->fetch_row()[0]);

function list_tables(mysqli $db, string $dbName): array
{
    $tables = [];
    $res = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '$dbName'");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $tables[] = $row['table_name'];
        }
        $res->close();
    }
    return $tables;
}

function is_valid_identifier(string $name): bool
{
    return preg_match('/^[A-Za-z0-9_]+$/', $name) === 1;
}

function user_is_admin(mysqli $db, int $userId): bool
{
    $sql = "
        SELECT 1
        FROM usuarios_roles ur
        INNER JOIN roles r ON r.id = ur.rol_id
        WHERE ur.usuario_id = ?
          AND r.nombre = 'Administrador'
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->store_result();
    $is = $stmt->num_rows > 0;
    $stmt->close();
    return $is;
}

function get_table_columns(mysqli $db, string $table): array
{
    $safe = $db->real_escape_string($table);
    $cols = [];
    $res = $db->query("DESCRIBE `$safe`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cols[$row['Field']] = $row;
        }
        $res->close();
    }
    return $cols;
}

function bind_values(mysqli_stmt $stmt, array $values): void
{
    if (empty($values)) {
        return;
    }
    $types = str_repeat('s', count($values));
    $stmt->bind_param($types, ...$values);
}

try {
    if ($action === 'tables') {
        echo json_encode(['tables' => list_tables($db, $dbName)]);
        exit;
    }

    if ($action === 'query') {
        $sql = trim((string)($_POST['sql'] ?? ''));
        if ($sql === '') {
            http_response_code(400);
            echo json_encode(['error' => 'SQL vacío']);
            exit;
        }
        $result = $db->query($sql);
        if ($result === false) {
            throw new RuntimeException($db->error);
        }
        if ($result instanceof mysqli_result) {
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->close();
            echo json_encode(['rows' => $rows]);
        } else {
            echo json_encode(['affected' => $db->affected_rows]);
        }
        exit;
    }

    if ($action === 'select') {
        $table = trim((string)($_GET['table'] ?? ''));
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        if ($limit <= 0 || $limit > 500) $limit = 50;
        if ($offset < 0) $offset = 0;
        if (!is_valid_identifier($table)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tabla inválida']);
            exit;
        }
        $sql = "SELECT * FROM `$table` LIMIT $offset, $limit";
        $res = $db->query($sql);
        if (!$res) {
            throw new RuntimeException($db->error);
        }
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $res->close();
        echo json_encode(['rows' => $rows]);
        exit;
    }

    if ($action === 'columns') {
        $table = trim((string)($_GET['table'] ?? ''));
        if (!is_valid_identifier($table)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tabla inválida']);
            exit;
        }
        $cols = get_table_columns($db, $table);
        echo json_encode(['columns' => array_values($cols)]);
        exit;
    }

    if ($action === 'insert') {
        $table = trim((string)($_POST['table'] ?? ''));
        if (!is_valid_identifier($table)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tabla inválida']);
            exit;
        }
        $payload = $_POST['data'] ?? '';
        $data = is_string($payload) ? json_decode($payload, true) : (is_array($payload) ? $payload : []);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos inválidos']);
            exit;
        }
        // Solo admin puede tocar roles/usuarios_roles
        if (in_array($table, ['roles', 'usuarios_roles'], true) && !user_is_admin($db, $userId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Solo Administrador puede modificar esta tabla']);
            exit;
        }

        $cols = get_table_columns($db, $table);
        if (empty($cols)) {
            http_response_code(400);
            echo json_encode(['error' => 'No se pudo leer columnas']);
            exit;
        }
        $insertCols = [];
        $values = [];
        foreach ($data as $k => $v) {
            if (!isset($cols[$k])) continue;
            if (strpos((string)$cols[$k]['Extra'], 'auto_increment') !== false) continue;
            $insertCols[] = "`$k`";
            $values[] = $v;
        }
        if (empty($insertCols)) {
            http_response_code(400);
            echo json_encode(['error' => 'Sin columnas válidas']);
            exit;
        }
        $placeholders = implode(',', array_fill(0, count($insertCols), '?'));
        $sql = 'INSERT INTO `' . $table . '` (' . implode(',', $insertCols) . ') VALUES (' . $placeholders . ')';
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException($db->error);
        }
        bind_values($stmt, $values);
        if (!$stmt->execute()) {
            throw new RuntimeException($stmt->error);
        }
        $id = $db->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    if ($action === 'update') {
        $table = trim((string)($_POST['table'] ?? ''));
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!is_valid_identifier($table) || $id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos inválidos']);
            exit;
        }

        if (in_array($table, ['roles', 'usuarios_roles'], true) && !user_is_admin($db, $userId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Solo Administrador puede modificar esta tabla']);
            exit;
        }

        $payload = $_POST['data'] ?? '';
        $data = is_string($payload) ? json_decode($payload, true) : (is_array($payload) ? $payload : []);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos inválidos']);
            exit;
        }
        $cols = get_table_columns($db, $table);
        if (empty($cols) || !isset($cols['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tabla sin id o inválida']);
            exit;
        }
        $set = [];
        $values = [];
        foreach ($data as $k => $v) {
            if (!isset($cols[$k])) continue;
            if ($k === 'id') continue;
            $set[] = "`$k` = ?";
            $values[] = $v;
        }
        if (empty($set)) {
            http_response_code(400);
            echo json_encode(['error' => 'Sin columnas para actualizar']);
            exit;
        }
        $values[] = $id;
        $sql = 'UPDATE `' . $table . '` SET ' . implode(',', $set) . ' WHERE `id` = ?';
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException($db->error);
        }
        bind_values($stmt, $values);
        if (!$stmt->execute()) {
            throw new RuntimeException($stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'affected' => $db->affected_rows]);
        exit;
    }

    if ($action === 'delete') {
        $table = trim((string)($_POST['table'] ?? ''));
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!is_valid_identifier($table) || $id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos inválidos']);
            exit;
        }

        if (in_array($table, ['roles', 'usuarios_roles'], true) && !user_is_admin($db, $userId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Solo Administrador puede modificar esta tabla']);
            exit;
        }

        $sql = 'DELETE FROM `' . $table . '` WHERE `id` = ?';
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException($db->error);
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new RuntimeException($stmt->error);
        }
        $stmt->close();
        echo json_encode(['success' => true, 'affected' => $db->affected_rows]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Acción no soportada']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $db->close();
}

