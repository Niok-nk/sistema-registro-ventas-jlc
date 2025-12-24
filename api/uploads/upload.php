<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Forzar carga de .env
Database::getInstance();

// Autenticación requerida
$user = requireAuth();

// Validar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 405, 'message' => 'Método no permitido']);
    exit;
}

// Validar que venga un archivo
if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'No se recibió ningún archivo']);
    exit;
}

$file = $_FILES['file'];

// Validar errores de upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido',
        UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo',
        UPLOAD_ERR_EXTENSION => 'Upload detenido por extensión'
    ];
    
    http_response_code(400);
    echo json_encode([
        'status' => 400, 
        'message' => $errors[$file['error']] ?? 'Error desconocido al subir archivo'
    ]);
    exit;
}

// Configuración
$maxSize = (int)(getenv('UPLOAD_MAX_SIZE') ?: 5242880); // 5MB por defecto
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

// Validar tamaño
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode([
        'status' => 400, 
        'message' => 'El archivo excede el tamaño máximo de ' . round($maxSize / 1048576, 2) . 'MB'
    ]);
    exit;
}

// Validar MIME type real (no confiar en extensión)
// Esto previene que archivos PHP/maliciosos sean disfrazados como JPG
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMimeType = finfo_file($finfo, $file['tmp_name']);
    // finfo_close() removido - deprecated en PHP 8.5, se libera automáticamente
    
    if (!in_array($realMimeType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => "Tipo de archivo no permitido. Solo JPG, PNG y PDF",
            'detected' => $realMimeType
        ]);
        exit;
    }
} else {
    // Fallback si fileinfo no está disponible
    error_log('WARNING: fileinfo no disponible, validación MIME deshabilitada');
}

// Validar extensión (segunda capa de validación)
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode([
        'status' => 400, 
        'message' => 'Extensión de archivo no permitida'
    ]);
    exit;
}

// Crear directorio si no existe
$uploadDir = __DIR__ . '/../../uploads/facturas/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generar nombre único y seguro
$timestamp = time();
$randomString = bin2hex(random_bytes(8));
$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
$safeName = substr($safeName, 0, 50); // Limitar longitud
$newFileName = "{$safeName}_{$timestamp}_{$randomString}.{$extension}";
$destination = $uploadDir . $newFileName;

// Mover archivo
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode([
        'status' => 500, 
        'message' => 'Error al guardar el archivo en el servidor'
    ]);
    exit;
}

// Retornar URL relativa
$relativeUrl = "/uploads/facturas/{$newFileName}";

http_response_code(200);
echo json_encode([
    'status' => 200,
    'message' => 'Archivo subido exitosamente',
    'data' => [
        'url' => $relativeUrl,
        'filename' => $newFileName,
        'size' => $file['size']
    ]
]);
