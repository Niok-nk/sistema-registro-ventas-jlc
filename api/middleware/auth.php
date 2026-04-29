<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/JWT.php';

// Forzar carga del .env
Database::getInstance();

function requireAuth() {
    $token = null;

    // 1. Leer desde cookie HttpOnly (método seguro, inaccesible para JS)
    if (!empty($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
    }

    // 2. Fallback: header Authorization: Bearer (compatibilidad / llamadas directas)
    if (!$token) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!empty($authHeader) && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        http_response_code(401);
        echo json_encode(['status' => 401, 'message' => 'Acceso no autorizado. Token faltante.']);
        exit;
    }

    $payload = JWT::verify($token);

    if (!is_array($payload)) {
        http_response_code(401);
        echo json_encode(['status' => 401, 'message' => 'Acceso no autorizado. Token inválido o expirado.']);
        exit;
    }

    // Verificar que el usuario siga activo en BD
    // Esto invalida tokens de cuentas desactivadas sin esperar a que expiren
    $db   = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare('SELECT activo FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([$payload['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u || !$u['activo']) {
        http_response_code(401);
        echo json_encode(['status' => 401, 'message' => 'Cuenta desactivada o no encontrada.']);
        exit;
    }

    return $payload;
}
