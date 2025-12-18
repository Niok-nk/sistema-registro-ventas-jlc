<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Forzar carga de .env
Database::getInstance();

// Autenticación requerida - Solo usuarios logueados pueden ver facturas
$user = requireAuth();

// Obtener nombre del archivo
$filename = $_GET['file'] ?? '';

// Sanitizar nombre de archivo para evitar Path Traversal (muy importante)
$filename = basename($filename);

if (empty($filename)) {
    http_response_code(400);
    die('Nombre de archivo no proporcionado');
}

// Ruta segura al archivo
$filepath = __DIR__ . '/../../uploads/facturas/' . $filename;

// Verificar que el archivo existe
if (!file_exists($filepath)) {
    http_response_code(404);
    die('Archivo no encontrado');
}

// Detectar tipo MIME
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

// Servir el archivo
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=86400'); // Cachear por 1 día en el navegador

readfile($filepath);
exit;
