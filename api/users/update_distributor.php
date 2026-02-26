<?php
/**
 * POST /api/users/update_distributor.php
 * Body: { "user_id": 5, "nombre_distribuidor": "Nuevo Nombre" }
 * Actualiza el nombre_distribuidor de un usuario específico (solo admin).
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/cors.php';

$authUser = requireAuth();
if ($authUser['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['status' => 403, 'message' => 'Solo el administrador puede editar el distribuidor.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$userId         = intval($body['user_id'] ?? 0);
$nombreDist     = trim($body['nombre_distribuidor'] ?? '');

if ($userId <= 0 || $nombreDist === '') {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'user_id y nombre_distribuidor son requeridos.']);
    exit;
}

try {
    $db   = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        UPDATE usuarios
        SET nombre_distribuidor = :nombre, updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $stmt->execute([':nombre' => $nombreDist, ':id' => $userId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'Usuario no encontrado.']);
        exit;
    }

    echo json_encode(['status' => 200, 'message' => 'Distribuidor actualizado correctamente.']);

} catch (Exception $e) {
    error_log('Error users/update_distributor.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error al actualizar el distribuidor.']);
}
