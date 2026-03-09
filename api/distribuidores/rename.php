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
require_once __DIR__ . '/../utils/audit_helper.php';

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

    // 1. Actualizar usuarios
    $stmt = $conn->prepare("
        UPDATE usuarios
        SET nombre_distribuidor = :nuevo, updated_at = CURRENT_TIMESTAMP
        WHERE nombre_distribuidor = :actual
    ");
    $stmt->execute([':nuevo' => $nombreNuevo, ':actual' => $nombreActual]);
    $afectados = $stmt->rowCount();

    // 2. Sincronizar tabla distribuidores
    // Intentar actualizar el nombre en distribuidores
    $updDist = $conn->prepare("
        UPDATE distribuidores SET nombre_distribuidor = :nuevo WHERE nombre_distribuidor = :actual
    ");
    $updDist->execute([':nuevo' => $nombreNuevo, ':actual' => $nombreActual]);

    // Si no existia el nombre_actual en distribuidores, insertar el nuevo
    if ($updDist->rowCount() === 0) {
        $insDist = $conn->prepare("
            INSERT IGNORE INTO distribuidores (nombre_distribuidor) VALUES (:nuevo)
        ");
        $insDist->execute([':nuevo' => $nombreNuevo]);
    }

    // 3. Auditoría
    logAudit(
        $conn,
        (int)$authUser['user_id'],
        'renombrar_distribuidor',
        'distribuidores',
        null,
        ['nombre_anterior' => $nombreActual],
        ['nombre_nuevo'    => $nombreNuevo, 'usuarios_afectados' => $afectados]
    );

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
