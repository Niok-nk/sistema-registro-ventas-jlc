<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Detectar cuando PHP trunca el POST por exceder post_max_size
// En ese caso $_POST y $_FILES quedan vacíos pero Content-Length está presente
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
$postMaxSize   = (int)ini_get('post_max_size') * 1024 * 1024;
if ($contentLength > 0 && $postMaxSize > 0 && empty($_POST) && empty($_FILES) && $contentLength > $postMaxSize) {
    http_response_code(413);
    $maxMB = round($postMaxSize / 1024 / 1024, 0);
    echo json_encode([
        'status'  => 413,
        'message' => "Los archivos adjuntos superan el límite del servidor ({$maxMB} MB). Por favor reduzca el tamaño de los archivos e intente de nuevo."
    ]);
    exit;
}

require_once __DIR__ . '/../controllers/UsuarioController.php';

// Soporta multipart/form-data (con archivo) y application/json (sin archivo)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
    $boolFields = [
        'acepta_tratamiento_datos', 'acepta_contacto_comercial', 'declara_info_verdadera',
        'declara_naturaleza_comercial', 'reconoce_no_salario', 'declara_no_subordinacion',
        'declara_relacion_autonoma', 'acepta_liberalidades', 'asume_obligaciones_tributarias',
        'declara_no_contrato', 'acepta_terminos_programa'
    ];
    foreach ($boolFields as $field) {
        if (isset($data[$field])) {
            $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
        }
    }
} else {
    $data = json_decode(file_get_contents("php://input"), true);
    if (is_null($data)) {
        $data = $_POST;
    }
}

if (empty($data)) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'No se recibieron datos.']);
    exit;
}

// ── Helper para subir un archivo de certificado ──────────────────────────────
function subirCertificado(string $inputName): ?string {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // campo opcional
    }

    $file = $_FILES[$inputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $code = $file['error'];
        $msg  = ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE)
            ? 'El archivo supera el tamaño máximo permitido.'
            : 'Error al subir el archivo. Código: ' . $code;
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => $msg]);
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => "El archivo '$inputName' supera el tamaño máximo de 5 MB."]);
        exit;
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);

    if (!in_array($realMime, $allowedMimes)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => "Formato no permitido para '$inputName' (solo JPG, PNG o PDF). Detectado: $realMime"]);
        exit;
    }

    $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
    $ext      = $extMap[$realMime];
    $filename = $inputName . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
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

// ── Certificado Nequi (obligatorio) ─────────────────────────────────────────
if (!isset($_FILES['certificado']) || $_FILES['certificado']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'El certificado de cuenta Nequi es obligatorio.']);
    exit;
}
$data['certificado']     = subirCertificado('certificado');

// ── Certificado RUT (opcional) ───────────────────────────────────────────────
$data['certificado_rut'] = subirCertificado('certificado_rut');

$controller = new UsuarioController();
$result     = $controller->registrar($data);

http_response_code($result['status']);
echo json_encode($result);
