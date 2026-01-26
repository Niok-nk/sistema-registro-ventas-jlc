<?php
/**
 * API Endpoint: Listar usuarios pendientes de aprobación
 * Accesible por administradores (ven todos) y auditores (solo ven asesores)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/JWT.php';

// CORS headers - wildcard para desarrollo local
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Manejar preflight
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
    // El token tiene la estructura: user_id, cedula, rol, nombre
    $rol = $decoded['rol'] ?? null;
    if ($rol !== 'administrador' && $rol !== 'auditor') {
        http_response_code(403);
        echo json_encode(['status' => 403, 'message' => 'Acceso denegado. Solo administradores y auditores']);
        exit();
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Obtener parámetro opcional de filtro (por defecto: todos)
    $filtro = $_GET['filtro'] ?? 'todos'; // todos, activos, inactivos
    
    // Validar filtro
    if (!in_array($filtro, ['todos', 'activos', 'inactivos'])) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'Filtro inválido']);
        exit();
    }
    
    // Construir query - Filtrar según el rol del usuario autenticado
    $sql = "SELECT 
                id,
                cedula,
                nombre,
                apellido,
                correo,
                rol,
                nombre_distribuidor,
                ciudad_punto_venta,
                cargo,
                activo,
                estado_aprobacion,
                created_at
            FROM usuarios
            WHERE 1=1"; // Siempre true para agregar condiciones dinámicamente
    
    // Si es auditor, solo mostrar asesores
    if ($rol === 'auditor') {
        $sql .= " AND rol = 'asesor'";
    }
    // Si es administrador, mostrar todos (no agregar filtro adicional)
    
    // Agregar filtro de activo si no es 'todos'
    if ($filtro === 'activos') {
        $sql .= " AND activo = 1";
    } else if ($filtro === 'inactivos') {
        $sql .= " AND activo = 0";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $db->prepare($sql);
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'data' => $users,
        'count' => count($users)
    ]);
    
} catch (PDOException $e) {
    error_log("Error en pending.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error en el servidor']);
} catch (Exception $e) {
    error_log("Error en pending.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
}
