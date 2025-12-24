<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Verificar autenticación y rol de administrador
$user = requireAuth();

// Solo administradores pueden modificar productos
if ($user['rol'] !== 'admin' && $user['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode([
        'status' => 403,
        'message' => 'Acceso denegado. Solo administradores pueden modificar productos.'
    ]);
    exit;
}

try {
    // Obtener datos del request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['activo'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Faltan campos requeridos: id y activo'
        ]);
        exit;
    }
    
    $id = (int)$data['id'];
    $activo = (int)$data['activo'];
    
    if ($activo !== 0 && $activo !== 1) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'El campo activo debe ser 0 o 1'
        ]);
        exit;
    }
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verificar que el producto existe
    $checkSql = "SELECT id FROM productos_jlc WHERE id = :id";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute(['id' => $id]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'status' => 404,
            'message' => 'Producto no encontrado'
        ]);
        exit;
    }
    
    // Actualizar estado del producto
    $sql = "UPDATE productos_jlc SET activo = :activo WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'activo' => $activo,
        'id' => $id
    ]);
    
    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'message' => 'Estado del producto actualizado exitosamente',
        'data' => [
            'id' => $id,
            'activo' => $activo
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error toggling product status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Error al cambiar estado del producto'
    ]);
}
