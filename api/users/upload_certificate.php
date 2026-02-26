<?php
/**
 * API Endpoint: Subir/actualizar certificado de asesor
 * Method: POST (multipart/form-data)
 * Auth: JWT requerido
 * Input: $_FILES['certificado']
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/cors.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 405, 'message' => 'Método no permitido']);
    exit;
}

$authUser = requireAuth();
$userId   = $authUser['user_id'];

if (!isset($_FILES['certificado']) || $_FILES['certificado']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'No se recibió ningún archivo.']);
    exit;
}

$file   = $_FILES['certificado'];
$error  = $file['error'];

if ($error !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'Error al recibir el archivo (código ' . $error . ').']);
    exit;
}

// Validar tamaño (máx 5 MB)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'El archivo supera el tamaño máximo de 5 MB.']);
    exit;
}

// Validar tipo MIME real
$allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
$finfo        = finfo_open(FILEINFO_MIME_TYPE);
$realMime     = finfo_file($finfo, $file['tmp_name']);

if (!in_array($realMime, $allowedMimes)) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'Tipo de archivo no permitido. Solo JPG, PNG o PDF.']);
    exit;
}

// Extensión según MIME
$extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
$ext    = $extMap[$realMime];

// Nombre único
$filename = 'cert_' . $userId . '_' . time() . '.' . $ext;

$uploadDir = __DIR__ . '/../../uploads/certificados/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'No se pudo guardar el archivo en el servidor.']);
    exit;
}

try {
    $db   = Database::getInstance();
    $conn = $db->getConnection();

    // Si ya tenía certificado, eliminar el antiguo
    $stmt = $conn->prepare('SELECT certificado FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $old = $stmt->fetchColumn();
    if ($old) {
        $oldPath = $uploadDir . basename($old);
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    $stmt = $conn->prepare('UPDATE usuarios SET certificado = :cert WHERE id = :id');
    $stmt->execute([':cert' => $filename, ':id' => $userId]);

    http_response_code(200);
    echo json_encode([
        'status'  => 200,
        'message' => 'Certificado actualizado correctamente.',
        'file'    => $filename,
    ]);

} catch (Exception $e) {
    error_log('Error upload_certificate.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error al guardar en la base de datos.']);
}
