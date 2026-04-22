<?php
/**
 * POST /api/auth/logout.php
 * Cierra la sesión: expira la cookie HttpOnly auth_token.
 * No requiere token válido — si ya expiró o no existe, simplemente devuelve 200.
 */

require_once __DIR__ . '/../config/cors.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Expirar la cookie — el browser la elimina al recibir maxage=0 / expires en el pasado
setcookie('auth_token', '', [
    'expires'  => time() - 3600,  // En el pasado → el browser la borra
    'path'     => '/',
    'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Intentar invalidar la sesión en BD (no crítico si falla)
try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../utils/JWT.php';

    $token = $_COOKIE['auth_token'] ?? null;
    if ($token) {
        $tokenHash = hash('sha256', $token);
        $db   = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("DELETE FROM sesiones WHERE token_hash = :hash");
        $stmt->execute([':hash' => $tokenHash]);
    }
} catch (Exception $e) {
    error_log('logout.php - error invalidando sesión: ' . $e->getMessage());
}

http_response_code(200);
echo json_encode(['status' => 200, 'message' => 'Sesión cerrada correctamente.']);
