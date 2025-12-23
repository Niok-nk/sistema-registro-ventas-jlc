<?php
/**
 * API Endpoint: Activar o desactivar usuario
 * Solo accesible por administradores
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/JWT.php';

// CORS headers - wildcard para desarrollo local
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

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
    // Verificar token JWT
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['status' => 401, 'message' => 'Token no proporcionado']);
        exit();
    }
    
    $token = $matches[1];
    $decoded = JWT::verify($token);
    
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['status' => 401, 'message' => 'Token inválido o expirado']);
        exit();
    }
    
    // Verificar que el usuario sea administrador  
    $rol = $decoded['rol'] ?? null;
    if ($rol !== 'administrador') {
        http_response_code(403);
        echo json_encode(['status' => 403, 'message' => 'Acceso denegado. Solo administradores']);
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
    
    // Verificar que el usuario existe
    $stmt = $db->prepare("SELECT id, nombre, apellido, correo, activo FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'Usuario no encontrado']);
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
