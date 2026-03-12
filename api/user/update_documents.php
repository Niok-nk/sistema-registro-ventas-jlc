<?php
/**
 * Endpoint: Actualizar documentos del perfil de usuario autenticado
 * PUT/POST multipart/form-data
 * Campos aceptados:
 *   - rut (texto, opcional)
 *   - certificado (archivo, opcional — solo si no tiene uno o quiere reemplazarlo)
 *   - certificado_rut (archivo, opcional)
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Autenticación requerida — solo el propio usuario puede actualizar sus documentos
$authUser = requireAuth();
$userId   = (int)$authUser['user_id'];

// Detectar POST excesivo antes de procesar
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
$postMaxSize   = (int)ini_get('post_max_size') * 1024 * 1024;
if ($contentLength > 0 && $postMaxSize > 0 && empty($_POST) && empty($_FILES) && $contentLength > $postMaxSize) {
    http_response_code(413);
    echo json_encode(['status' => 413, 'message' => 'Los archivos superan el límite del servidor (' . round($postMaxSize/1024/1024) . ' MB)']);
    exit;
}

// ── Helper para subir un archivo ──────────────────────────────────────────────
function subirArchivo(string $inputName, int $userId): ?string {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$inputName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'Error al subir archivo ' . $inputName . ' (código: ' . $file['error'] . ')']);
        exit;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => "El archivo '$inputName' supera el máximo de 5 MB."]);
        exit;
    }
    $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    if (!in_array($realMime, $allowedMimes)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => "Formato no permitido para '$inputName' (solo JPG, PNG o PDF)."]);
        exit;
    }
    $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
    $ext      = $extMap[$realMime];
    $filename = $inputName . '_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $uploadDir = __DIR__ . '/../../uploads/certificados/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        http_response_code(500);
        echo json_encode(['status' => 500, 'message' => "No se pudo guardar el archivo '$inputName'."]);
        exit;
    }
    return $filename;
}

try {
    $db   = Database::getInstance();
    $conn = $db->getConnection();

    $updates = [];
    $params  = [':id' => $userId];

    // Campo texto: rut
    if (isset($_POST['rut'])) {
        $rut = trim($_POST['rut']);
        $updates[] = 'rut = :rut';
        $params[':rut'] = $rut !== '' ? $rut : null;
    }

    // Campo texto: llave_breb (número Nequi)
    if (isset($_POST['llave_breb'])) {
        $llaveBreb = trim($_POST['llave_breb']);
        $updates[] = 'llave_breb = :llave_breb';
        $params[':llave_breb'] = $llaveBreb !== '' ? $llaveBreb : null;
    }

    // Archivo: certificado Nequi
    $certFilename = subirArchivo('certificado', $userId);
    if ($certFilename !== null) {
        $updates[] = 'certificado = :certificado';
        $params[':certificado'] = $certFilename;
    }

    // Archivo: certificado RUT
    $certRutFilename = subirArchivo('certificado_rut', $userId);
    if ($certRutFilename !== null) {
        $updates[] = 'certificado_rut = :certificado_rut';
        $params[':certificado_rut'] = $certRutFilename;
    }

    if (empty($updates)) {
        echo json_encode(['status' => 400, 'message' => 'No se enviaron datos para actualizar.']);
        exit;
    }

    $sql = 'UPDATE usuarios SET ' . implode(', ', $updates) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    // Devolver los valores guardados para que el frontend actualice el DOM
    $response = ['status' => 200, 'message' => 'Documentos actualizados correctamente.'];
    if ($certFilename)    $response['certificado']     = $certFilename;
    if ($certRutFilename) $response['certificado_rut'] = $certRutFilename;
    if (isset($_POST['rut']))        $response['rut']        = $_POST['rut'];
    if (isset($_POST['llave_breb'])) $response['llave_breb'] = $_POST['llave_breb'];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log('Error update_documents: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error al guardar los documentos.']);
}
