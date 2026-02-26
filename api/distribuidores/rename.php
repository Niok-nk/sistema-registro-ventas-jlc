<?php
/**
 * POST /api/distribuidores/rename.php
 * Body: { "nombre_actual": "...", "nombre_nuevo": "..." }
 * Renombra el distribuidor actualizando nombre_distribuidor en todos los usuarios afectados.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/cors.php';

$authUser = requireAuth();
if ($authUser['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['status' => 403, 'message' => 'Solo el administrador puede renombrar distribuidores.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$nombreActual = trim($body['nombre_actual'] ?? '');
$nombreNuevo  = trim($body['nombre_nuevo']  ?? '');

if (empty($nombreActual) || empty($nombreNuevo)) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'nombre_actual y nombre_nuevo son requeridos.']);
    exit;
}

if ($nombreActual === $nombreNuevo) {
    echo json_encode(['status' => 200, 'message' => 'Sin cambios.', 'afectados' => 0]);
    exit;
}

try {
    $db   = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        UPDATE usuarios
        SET nombre_distribuidor = :nuevo, updated_at = CURRENT_TIMESTAMP
        WHERE nombre_distribuidor = :actual
    ");
    $stmt->execute([':nuevo' => $nombreNuevo, ':actual' => $nombreActual]);
    $afectados = $stmt->rowCount();

    echo json_encode([
        'status'    => 200,
        'message'   => "Distribuidor renombrado. $afectados usuario(s) actualizado(s).",
        'afectados' => $afectados,
    ]);

} catch (Exception $e) {
    error_log('Error distribuidores/rename.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error al renombrar el distribuidor.']);
}
