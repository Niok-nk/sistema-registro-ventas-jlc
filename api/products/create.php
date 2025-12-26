<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Verificar autenticación y rol de administrador
$user = requireAuth();

// Solo administradores pueden crear productos
if ($user['rol'] !== 'admin' && $user['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode([
        'status' => 403,
        'message' => 'Acceso denegado. Solo administradores pueden crear productos.'
    ]);
    exit;
}

try {
    // Obtener datos del request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['modelo']) || !isset($data['codigo']) || !isset($data['descripcion'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Faltan campos requeridos: modelo, codigo y descripcion'
        ]);
        exit;
    }
    
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
    
    // Verificar si el modelo ya existe
    $checkSql = "SELECT id FROM productos_jlc WHERE modelo = :modelo";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute(['modelo' => $modelo]);
    
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'status' => 409,
            'message' => 'Ya existe un producto con ese modelo'
        ]);
        exit;
    }
    
    // Insertar nuevo producto
    $sql = "INSERT INTO productos_jlc (modelo, codigo, descripcion, activo) VALUES (:modelo, :codigo, :descripcion, :activo)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'modelo' => $modelo,
        'codigo' => $codigo,
        'descripcion' => $descripcion,
        'activo' => $activo
    ]);
    
    http_response_code(201);
    echo json_encode([
        'status' => 201,
        'message' => 'Producto creado exitosamente',
        'data' => [
            'id' => $conn->lastInsertId(),
            'modelo' => $modelo,
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'activo' => $activo
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error creating product: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Error al crear producto'
    ]);
}
