<?php
// Evitar duplicación si se incluye múltiples veces
if (defined('CORS_APPLIED')) return;
define('CORS_APPLIED', true);

// Limpiar headers previos por seguridad
header_remove("Access-Control-Allow-Origin");
header_remove("Access-Control-Allow-Methods");
header_remove("Access-Control-Allow-Headers");

// Aplicar Headers CORS con validación de origen
// Lista blanca de orígenes permitidos
$allowedOrigins = [
    'http://localhost:4321',
    'http://127.0.0.1:4321',
    'http://localhost:3000',
    // IMPORTANTE: Agregar tu dominio de producción aquí
    // 'https://tu-dominio.com',
    // 'https://www.tu-dominio.com'
];

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$environment = getenv('ENVIRONMENT') ?: 'development';

// En desarrollo: permitir localhost y orígenes de la lista
// En producción: solo dominios específicos de la lista
if ($environment === 'development') {
    // Modo permisivo para desarrollo local
    if (strpos($requestOrigin, 'localhost') !== false || 
        strpos($requestOrigin, '127.0.0.1') !== false ||
        in_array($requestOrigin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $requestOrigin");
    } else {
        // Desarrollo: permitir cualquier origen como fallback (menos seguro pero práctico)
        header("Access-Control-Allow-Origin: *");
    }
} else {
    // Producción: estricto - solo orígenes en lista blanca
    if (in_array($requestOrigin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $requestOrigin");
    } else {
        error_log("CORS: Origin no permitido en producción: $requestOrigin");
        // En producción, rechazar orígenes no autorizados
        http_response_code(403);
        echo json_encode(['status' => 403, 'message' => 'Origin not allowed']);
        exit;
    }
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
// Lista exhaustiva de headers permitidos para evitar bloqueos
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Origin, Accept");

// Manejo inmediato de Preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(["status" => 200, "message" => "CORS Preflight OK"]);
    exit;
}
