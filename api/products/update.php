<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Verificar autenticación y rol de administrador
$user = requireAuth();

// Solo administradores pueden actualizar productos
if ($user['rol'] !== 'admin' && $user['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode([
        'status' => 403,
        'message' => 'Acceso denegado. Solo administradores pueden actualizar productos.'
    ]);
    exit;
}

try {
    // Obtener datos del request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['modelo']) || !isset($data['codigo']) || !isset($data['descripcion'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Faltan campos requeridos: id, modelo, codigo y descripcion'
        ]);
        exit;
    }
    
    $id = (int)$data['id'];
    $modelo = trim($data['modelo']);
    $codigo = trim($data['codigo']);
    $descripcion = trim($data['descripcion']);
    $activo = isset($data['activo']) ? (int)$data['activo'] : 1;
    
    // Validaciones
    if (empty($modelo)) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'El modelo no puede estar vacío'
        ]);
        exit;
    }
    
    if (empty($codigo)) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'El código no puede estar vacío'
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
    
    // Verificar si el modelo ya existe en otro producto
    $checkModelSql = "SELECT id FROM productos_jlc WHERE modelo = :modelo AND id != :id";
    $checkModelStmt = $conn->prepare($checkModelSql);
    $checkModelStmt->execute(['modelo' => $modelo, 'id' => $id]);
    
    if ($checkModelStmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'status' => 409,
            'message' => 'Ya existe otro producto con ese modelo'
        ]);
        exit;
    }
    
    // Actualizar producto
    $sql = "UPDATE productos_jlc SET modelo = :modelo, codigo = :codigo, descripcion = :descripcion, activo = :activo WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'id' => $id,
        'modelo' => $modelo,
        'codigo' => $codigo,
        'descripcion' => $descripcion,
        'activo' => $activo
    ]);
    
    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'message' => 'Producto actualizado exitosamente',
        'data' => [
            'id' => $id,
            'modelo' => $modelo,
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'activo' => $activo
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error updating product: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Error al actualizar producto'
    ]);
}
