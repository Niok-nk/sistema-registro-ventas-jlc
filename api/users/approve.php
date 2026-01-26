<?php
/**
 * API Endpoint: Aprobar o rechazar usuario
 * Accesible por administradores y auditores
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/JWT.php';

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
    
    // Verificar que el usuario sea administrador o auditor
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
    $estado = $data['estado'] ?? null;
    
    if (!$userId || !$estado) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'userId y estado son requeridos']);
        exit();
    }
    
    // Validar estado
    if (!in_array($estado, ['aprobado', 'rechazado', 'pendiente'])) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'Estado inválido. Debe ser: aprobado, rechazado o pendiente']);
        exit();
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Verificar que el usuario existe
    $stmt = $db->prepare("SELECT id, nombre, apellido, correo FROM usuarios WHERE id = :id AND activo = 1");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    // Actualizar estado de aprobación
    $stmt = $db->prepare("UPDATE usuarios SET estado_aprobacion = :estado WHERE id = :id");
    $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Registrar en auditoría (si existe la tabla)
    try {
        $accion = "Cambió estado de aprobación a: {$estado}";
        $stmtAudit = $db->prepare("INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id) VALUES (:admin_id, :accion, 'usuarios', :user_id)");
        $adminId = $decoded['user_id'] ?? null;
        $stmtAudit->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
        $stmtAudit->bindParam(':accion', $accion, PDO::PARAM_STR);
        $stmtAudit->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtAudit->execute();
    } catch (PDOException $e) {
        // Ignorar si la tabla de auditoría no existe
        error_log("Advertencia: No se pudo registrar en auditoría: " . $e->getMessage());
    }
    
    $mensaje = '';
    switch ($estado) {
        case 'aprobado':
            $mensaje = "Usuario {$user['nombre']} {$user['apellido']} aprobado exitosamente";
            break;
        case 'rechazado':
            $mensaje = "Usuario {$user['nombre']} {$user['apellido']} rechazado";
            break;
        case 'pendiente':
            $mensaje = "Usuario {$user['nombre']} {$user['apellido']} marcado como pendiente";
            break;
    }
    
    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'message' => $mensaje,
        'data' => [
            'userId' => $userId,
            'estado' => $estado
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en approve.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error en el servidor']);
} catch (Exception $e) {
    error_log("Error en approve.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
}
