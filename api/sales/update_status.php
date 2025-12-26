<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Verificar autenticación y rol de administrador
$user = requireAuth();

// Solo administradores pueden cambiar el estado de ventas
if ($user['rol'] !== 'admin' && $user['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode([
        'status' => 403,
        'message' => 'Acceso denegado. Solo administradores pueden cambiar estados de ventas.'
    ]);
    exit;
}

try {
    // Obtener datos del request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['estado'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Faltan campos requeridos: id y estado'
        ]);
        exit;
    }
    
    $id = (int)$data['id'];
    $estado = trim($data['estado']);
    
    // Validar estado
    $estadosValidos = ['pendiente', 'aprobada', 'rechazada'];
    if (!in_array($estado, $estadosValidos)) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Estado inválido. Debe ser: pendiente, aprobada o rechazada'
        ]);
        exit;
    }
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verificar que la venta existe
    $checkSql = "SELECT id FROM ventas WHERE id = :id";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute(['id' => $id]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'status' => 404,
            'message' => 'Venta no encontrada'
        ]);
        exit;
    }
    
    // Actualizar estado
    $sql = "UPDATE ventas SET estado = :estado WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'id' => $id,
        'estado' => $estado
    ]);
    
    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'message' => 'Estado de venta actualizado exitosamente',
        'data' => [
            'id' => $id,
            'estado' => $estado
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error updating sale status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Error al actualizar estado de venta'
    ]);
}
