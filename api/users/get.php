<?php
/**
 * API Endpoint: Get User by ID
 * Method: GET
 * Description: Obtener información completa de un usuario por ID (solo admin)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/cors.php';

// Validar autenticación
$user = requireAuth();

// Verificar que sea admin
if ($user['rol'] !== 'admin' && $user['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode([
        'status' => 403,
        'message' => 'Acceso denegado. Solo administradores pueden ver perfiles de otros usuarios.'
    ]);
    exit;
}

// Obtener ID del usuario solicitado
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 400,
        'message' => 'ID de usuario inválido'
    ]);
    exit;
}

try {
    // Obtener instancia de la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Consulta para obtener TODOS los datos del usuario
    $stmt = $conn->prepare("
        SELECT 
            id,
            cedula,
            nombre,
            apellido,
            tipo_documento,
            numero_documento,
            fecha_nacimiento,
            ciudad_residencia,
            departamento,
            correo,
            whatsapp,
            telefono,
            nombre_distribuidor,
            ciudad_punto_venta,
            direccion_punto_venta,
            cargo,
            antiguedad_meses,
            rol,
            activo,
            metodo_pago_preferido,
            llave_breb,
            created_at,
            updated_at
        FROM usuarios 
        WHERE id = :id
    ");
    
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        http_response_code(404);
        echo json_encode([
            'status' => 404,
            'message' => 'Usuario no encontrado'
        ]);
        exit;
    }
    
    // Convertir activo a booleano
    $userData['activo'] = (bool) $userData['activo'];
    
    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'message' => 'Usuario encontrado',
        'data' => $userData
    ]);
    
} catch (Exception $e) {
    error_log("Error en users/get.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Error al obtener usuario'
    ]);
}
