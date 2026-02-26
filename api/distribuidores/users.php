<?php
/**
 * GET /api/distribuidores/users.php?nombre=...
 * Retorna los usuarios que pertenecen a un distribuidor dado.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/cors.php';

$authUser = requireAuth();
if (!in_array($authUser['rol'], ['administrador', 'auditor'])) {
    http_response_code(403);
    echo json_encode(['status' => 403, 'message' => 'Acceso denegado.']);
    exit;
}

$nombre = trim($_GET['nombre'] ?? '');
if (empty($nombre)) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'Parámetro nombre requerido.']);
    exit;
}

try {
    $db   = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT
            id, nombre, apellido, correo, cedula,
            whatsapp, cargo, ciudad_punto_venta,
            rol, activo, llave_breb, created_at
        FROM usuarios
        WHERE nombre_distribuidor = :nombre
        ORDER BY nombre ASC
    ");
    $stmt->execute([':nombre' => $nombre]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['activo'] = (bool) $row['activo'];
    }

    echo json_encode(['status' => 200, 'data' => $rows]);

} catch (Exception $e) {
    error_log('Error distribuidores/users.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error al obtener usuarios.']);
}
