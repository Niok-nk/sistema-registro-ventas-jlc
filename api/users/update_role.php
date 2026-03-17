<?php
/**
 * API Endpoint: Actualizar rol de usuario
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
    $requesterId  = $decoded['user_id'] ?? null;
    $requesterRol = $decoded['rol'] ?? null;
    
    if ($requesterRol !== 'administrador') {
        http_response_code(403);
        echo json_encode(['status' => 403, 'message' => 'Acceso denegado. Solo administradores pueden cambiar roles']);
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
    $newRole = $data['newRole'] ?? null;
    
    if (!$userId || !$newRole) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'userId y newRole son requeridos']);
        exit();
    }
    
    // SEGURIDAD: Validar que el nuevo rol sea válido (whitelist)
    $validRoles = ['administrador', 'asesor', 'auditor'];
    if (!in_array($newRole, $validRoles)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'Rol inválido. Debe ser: administrador, asesor o auditor']);
        exit();
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Verificar que el usuario objetivo existe y obtener su rol actual
    $stmt = $db->prepare("SELECT id, nombre, apellido, rol FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    $previousRole = $targetUser['rol'];
    
    // Si el rol no ha cambiado, no hacer nada
    if ($previousRole === $newRole) {
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'message' => 'El usuario ya tiene ese rol',
            'data' => [
                'userId' => $userId,
                'currentRole' => $newRole
            ]
        ]);
        exit();
    }
    
    // Actualizar el rol del usuario
    $stmt = $db->prepare("UPDATE usuarios SET rol = :newRole WHERE id = :id");
    $stmt->bindParam(':newRole', $newRole, PDO::PARAM_STR);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Registrar en auditoría (si existe la tabla)
    try {
        $accion = "Cambió rol de '{$previousRole}' a '{$newRole}'";
        $stmtAudit = $db->prepare("INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id) VALUES (:admin_id, :accion, 'usuarios', :user_id)");
        $stmtAudit->bindParam(':admin_id', $requesterId, PDO::PARAM_INT);
        $stmtAudit->bindParam(':accion', $accion, PDO::PARAM_STR);
        $stmtAudit->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtAudit->execute();
    } catch (PDOException $e) {
        // Ignorar si la tabla de auditoría no existe
        error_log("Advertencia: No se pudo registrar en auditoría: " . $e->getMessage());
    }
    
    $mensaje = "Rol de {$targetUser['nombre']} {$targetUser['apellido']} actualizado de '{$previousRole}' a '{$newRole}'";
    
    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'message' => $mensaje,
        'data' => [
            'userId' => $userId,
            'previousRole' => $previousRole,
            'newRole' => $newRole
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en update_role.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error en el servidor']);
} catch (Exception $e) {
    error_log("Error en update_role.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
}
