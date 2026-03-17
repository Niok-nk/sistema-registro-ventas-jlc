<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 405, 'message' => 'Método no permitido']);
    exit();
}


// Catálogo interno — solo usuarios autenticados
requireAuth();

try {

    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Obtener todos los productos (el filtro se hace en el cliente)
    $sql = "SELECT id, modelo, codigo, descripcion, activo FROM productos_jlc ORDER BY modelo ASC";
    $stmt = $conn->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'data' => $products
    ]);

} catch (PDOException $e) {
    error_log("Error listing products: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Error al obtener productos'
    ]);
}
