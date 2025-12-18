<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/JWT.php';

// Forzar carga del .env
Database::getInstance();

function requireAuth() {
    $headers = apache_request_headers();
    $token = null;

    if (isset($headers['Authorization'])) {
        $matches = [];
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        }
    } elseif (isset($_GET['token'])) {
        // Permitir token por URL para visualización de archivos (window.open)
        $token = $_GET['token'];
    }

    if (!$token) {
        http_response_code(401);
        echo json_encode(['status' => 401, 'message' => 'Acceso no autorizado. Token faltante.']);
        exit;
    }

    $payload = JWT::verify($token);

    if (!$payload) {
        http_response_code(401);
        echo json_encode(['status' => 401, 'message' => 'Acceso no autorizado. Token inválido o expirado.']);
        exit;
    }

    // Retornar datos del usuario para uso en el controlador
    return $payload;
}
