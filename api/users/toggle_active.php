<?php
/**
 * API Endpoint: Activar o desactivar usuario
 * Solo accesible por administradores
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 405, 'message' => 'Método no permitido']);
    exit();
}

try {
    // requireAuth() verifica JWT y que el usuario esté activo en BD
    $decoded = requireAuth();
    $rol = $decoded['rol'] ?? null;
    if ($rol !== 'administrador' && $rol !== 'auditor') {
        http_response_code(403);
        echo json_encode(['status' => 403, 'message' => 'Acceso denegado. Solo administradores y auditores']);
        exit();
    }
    
    // Leer datos del request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'Datos inválidos']);
        exit();
    }
    
    // Validar campos requeridos
    $userId = $data['userId'] ?? null;
    $activo = $data['activo'] ?? null;
    
    if (!$userId || !isset($activo)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'userId y activo son requeridos']);
        exit();
    }
    
    // Validar valor booleano
    if (!is_bool($activo) && $activo !== 0 && $activo !== 1) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'activo debe ser true/false o 1/0']);
        exit();
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Verificar que el usuario existe Y obtener su rol
    $stmt = $db->prepare("SELECT id, nombre, apellido, correo, activo, rol FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    // SEGURIDAD CRÍTICA: Los auditores solo pueden modificar asesores
    if ($rol === 'auditor' && $user['rol'] !== 'asesor') {
        http_response_code(403);
        echo json_encode(['status' => 403, 'message' => 'Los auditores solo pueden gestionar usuarios con rol asesor']);
        exit();
    }
    
    // Convertir a entero para SQL
    $activoInt = $activo ? 1 : 0;
    
    // Determinar estado_aprobacion basado en activo
    // Si se activa → aprobado, si se desactiva → pendiente
    $estadoAprobacion = $activoInt ? 'aprobado' : 'pendiente';
    
    // Actualizar estado activo Y estado_aprobacion simultáneamente
    $stmt = $db->prepare("UPDATE usuarios SET activo = :activo, estado_aprobacion = :estado_aprobacion WHERE id = :id");
    $stmt->bindParam(':activo', $activoInt, PDO::PARAM_INT);
    $stmt->bindParam(':estado_aprobacion', $estadoAprobacion, PDO::PARAM_STR);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Registrar en auditoría (si existe la tabla)
    try {
        $accion = "Cambió estado: activo=" . ($activoInt ? 'SI' : 'NO') . ", aprobación=" . $estadoAprobacion;
        $adminId = $decoded['user_id'] ?? null;
        $stmtAudit = $db->prepare("INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id) VALUES (:admin_id, :accion, 'usuarios', :user_id)");
        $stmtAudit->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
        $stmtAudit->bindParam(':accion', $accion, PDO::PARAM_STR);
        $stmtAudit->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtAudit->execute();
    } catch (PDOException $e) {
        // Ignorar si la tabla de auditoría no existe
        error_log("Advertencia: No se pudo registrar en auditoría: " . $e->getMessage());
    }
    
    $estadoTexto = $activoInt ? 'activado y aprobado' : 'desactivado';
    $mensaje = "Usuario {$user['nombre']} {$user['apellido']} $estadoTexto exitosamente";
    
    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'message' => $mensaje,
        'data' => [
            'userId' => $userId,
            'activo' => $activoInt,
            'estado_aprobacion' => $estadoAprobacion
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en toggle_active.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error en el servidor']);
} catch (Exception $e) {
    error_log("Error en toggle_active.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
}
