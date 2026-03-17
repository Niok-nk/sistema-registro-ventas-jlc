<?php
/**
 * API Endpoint: Servir certificado de asesor
 * Method: GET
 * Auth: Token efímero de file_access (?ftoken=) — válido 5 minutos, atado al archivo.
 *       NO acepta JWT de sesión en query string para evitar exposición en logs/historial.
 * Params: ?file=nombre.ext&ftoken=<token_efimero>
 *
 * Para obtener el ftoken, llamar primero a POST /api/files/signed_url.php
 * con el JWT de sesión normal por header Authorization: Bearer.
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/JWT.php';

// Forzar carga de .env
Database::getInstance();

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['status' => 405, 'message' => 'Método no permitido']));
}

// Leer el token efímero — SOLO desde ?ftoken=, nunca desde ?token= (JWT de sesión)
$ftoken = $_GET['ftoken'] ?? '';

if (empty($ftoken)) {
    http_response_code(401);
    die(json_encode(['status' => 401, 'message' => 'Token de acceso requerido (ftoken)']));
}

// Verificar firma y expiración del token efímero
$payload = JWT::verify($ftoken);
if (!$payload) {
    http_response_code(401);
    die(json_encode(['status' => 401, 'message' => 'Token inválido o expirado']));
}

// Verificar que el token sea específicamente del tipo file_access (no un JWT de sesión)
if (($payload['tipo'] ?? '') !== 'file_access') {
    http_response_code(403);
    die(json_encode(['status' => 403, 'message' => 'Tipo de token no válido para esta operación']));
}

// Verificar que el token sea para el directorio correcto (evita reutilizar ftoken de factura aquí)
if (($payload['dir'] ?? '') !== 'certificados') {
    http_response_code(403);
    die(json_encode(['status' => 403, 'message' => 'Token no válido para este tipo de archivo']));
}

// Sanitizar el nombre del archivo solicitado
$filename = basename($_GET['file'] ?? '');

if (empty($filename)) {
    http_response_code(400);
    die(json_encode(['status' => 400, 'message' => 'Nombre de archivo no proporcionado']));
}

// Verificar que el archivo solicitado coincide exactamente con el firmado en el token
if ($filename !== ($payload['file'] ?? '')) {
    http_response_code(403);
    die(json_encode(['status' => 403, 'message' => 'El archivo solicitado no corresponde al token']));
}

// Ruta segura al archivo
$filepath = __DIR__ . '/../../uploads/certificados/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    die(json_encode(['status' => 404, 'message' => 'Certificado no encontrado']));
}

// Detectar tipo MIME según extensión
$extension   = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$contentType = 'application/octet-stream';

switch ($extension) {
    case 'jpg':
    case 'jpeg':
        $contentType = 'image/jpeg';
        break;
    case 'png':
        $contentType = 'image/png';
        break;
    case 'pdf':
        $contentType = 'application/pdf';
        break;
}

// Servir el archivo
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, no-store'); // No cachear; el ftoken expira en 5 min
header('Content-Disposition: inline; filename="' . $filename . '"');

readfile($filepath);
exit;
