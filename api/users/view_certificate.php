<?php
/**
 * API Endpoint: Ver certificado de asesor
 * Method: GET
 * Auth: JWT requerido — solo usuarios autenticados pueden ver certificados
 * Params: ?file=nombre_archivo.ext
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Forzar carga de .env
Database::getInstance();

// Autenticación requerida
$user = requireAuth();

// Obtener nombre del archivo desde query string
$filename = $_GET['file'] ?? '';

// Sanitizar: solo el basename para evitar Path Traversal
$filename = basename($filename);

if (empty($filename)) {
    http_response_code(400);
    die(json_encode(['status' => 400, 'message' => 'Nombre de archivo no proporcionado']));
}

// Ruta segura al archivo
$filepath = __DIR__ . '/../../uploads/certificados/' . $filename;

// Verificar que el archivo existe
if (!file_exists($filepath)) {
    http_response_code(404);
    die(json_encode(['status' => 404, 'message' => 'Certificado no encontrado']));
}

// Detectar tipo MIME según extensión
$extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
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

// Servir el archivo de forma segura
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=3600');
header('Content-Disposition: inline; filename="' . $filename . '"');

readfile($filepath);
exit;
