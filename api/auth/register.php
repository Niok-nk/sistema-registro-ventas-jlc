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

require_once __DIR__ . '/../controllers/UsuarioController.php';

// Soporta multipart/form-data (con archivo) y application/json (sin archivo)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'multipart/form-data') !== false) {
    // Registro con archivo adjunto — datos en $_POST
    $data = $_POST;
    // Convertir checkboxes que llegan como string "true"/"false"/"on"
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
    // Registro sin archivo — JSON puro (compatibilidad)
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

// Manejar archivo de certificado si viene adjunto
$certificadoFilename = null;

if (isset($_FILES['certificado'])) {
    if ($_FILES['certificado']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = $_FILES['certificado']['error'];
        $errorMsg = 'Error al subir el certificado. Código de error PHP: ' . $uploadError;
        if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
            $errorMsg = 'El archivo supera el tamaño máximo permitido por el servidor.';
        } elseif ($uploadError === UPLOAD_ERR_NO_FILE) {
            // Se asume obligatorio ahora, así que lanzamos error
            $errorMsg = 'No se adjuntó ningún certificado de cuenta Nequi.';
        }
        
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => $errorMsg]);
        exit;
    }

    $file    = $_FILES['certificado'];
    $maxSize = 5 * 1024 * 1024; // 5 MB

    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'El certificado supera el tamaño máximo de 5 MB.']);
        exit;
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
    $finfo        = finfo_open(FILEINFO_MIME_TYPE);
    $realMime     = finfo_file($finfo, $file['tmp_name']);

    if (!in_array($realMime, $allowedMimes)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'Formato de certificado no permitido (solo JPG, PNG o PDF). Detectado: ' . $realMime]);
        exit;
    }

    $extMap  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
    $ext     = $extMap[$realMime];

    $certificadoFilename = 'cert_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $uploadDir = __DIR__ . '/../../uploads/certificados/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $certificadoFilename)) {
        http_response_code(500);
        echo json_encode(['status' => 500, 'message' => 'No se pudo guardar el certificado.']);
        exit;
    }
} else {
    // Es obligatorio
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'El campo certificado es obligatorio.']);
    exit;
}

// Pasar nombre de archivo al controlador
$data['certificado'] = $certificadoFilename;

$controller = new UsuarioController();
$result     = $controller->registrar($data);

http_response_code($result['status']);
echo json_encode($result);
