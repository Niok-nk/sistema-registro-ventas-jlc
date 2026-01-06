<?php
// Evitar duplicación si se incluye múltiples veces
if (defined('CORS_APPLIED')) return;
define('CORS_APPLIED', true);

// Limpiar headers previos por seguridad
header_remove("Access-Control-Allow-Origin");
header_remove("Access-Control-Allow-Methods");
header_remove("Access-Control-Allow-Headers");


// Lista blanca de orígenes permitidos
$allowedOrigins = [
    'http://localhost:4321',
    'http://127.0.0.1:4321',
    'http://localhost:3000',
    'http://localhost:8000',
    // Producción
    'https://ventas.jlc-electronics.com',
    'https://www.ventas.jlc-electronics.com',
];

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Verificar si el origin está en la lista blanca
if (in_array($requestOrigin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $requestOrigin");
    header("Access-Control-Allow-Credentials: true");
} elseif (strpos($requestOrigin, 'localhost') !== false || 
          strpos($requestOrigin, '127.0.0.1') !== false) {
    // Permitir cualquier localhost en desarrollo
    header("Access-Control-Allow-Origin: $requestOrigin");
    header("Access-Control-Allow-Credentials: true");
} else {
    // Log del origen rechazado
    error_log("CORS: Origin no permitido: $requestOrigin");
    // NO rechazar, solo no enviar header CORS (el navegador lo manejará)
    // Esto evita errores 403 confusos
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
