<?php
/**
 * API Endpoint: Generar URL firmada temporal para acceso a un archivo
 * Method: POST
 * Auth: JWT de sesión requerido por header Authorization: Bearer
 * Body: { "file": "nombre.ext", "type": "factura|certificado" }
 * Response: { "signed_url": "...", "expires_in": 300 }
 *
 * El ftoken generado es válido solo 5 minutos y solo para ese archivo/directorio.
 * Esto evita exponer el JWT de sesión (8h) en URLs, logs o historial del navegador.
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 405, 'message' => 'Método no permitido']);
    exit();
}

try {
    // Autenticación normal por header Bearer
    $authUser = requireAuth();

    // Leer body
    $data = json_decode(file_get_contents('php://input'), true);

    $file = isset($data['file']) ? basename($data['file']) : '';
    $type = $data['type'] ?? '';

    if (empty($file)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'El campo "file" es requerido']);
        exit();
    }

    // Validar tipo y mapear a directorio real
    $dirMap = [
        'factura'     => 'facturas',
        'certificado' => 'certificados',
    ];

    if (!array_key_exists($type, $dirMap)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'El campo "type" debe ser "factura" o "certificado"']);
        exit();
    }

    $dirName = $dirMap[$type];

    // Confirmar que el archivo existe antes de firmar
    $filePath = __DIR__ . '/../../uploads/' . $dirName . '/' . $file;
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'Archivo no encontrado']);
        exit();
    }

    // Generar token efímero: 5 minutos, atado a archivo + directorio + usuario
    // Nota: JWT::generate() añade 'iat' y 'exp' automáticamente (8h por defecto).
    // Para sobreescribir 'exp' a 5 min, se incluye aquí y el verify() lo respetará.
    $ttl = 300; // 5 minutos en segundos
    $ftoken = JWT::generate([
        'tipo'    => 'file_access',
        'file'    => $file,
        'dir'     => $dirName,
        'user_id' => $authUser['user_id'],
        'exp'     => time() + $ttl,   // Sobreescribe el exp de 8h del generate()
    ]);

    // Construir la URL firmada según tipo
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

    if ($type === 'factura') {
        $endpoint = '/sales/view_receipt.php';
    } else {
        $endpoint = '/users/view_certificate.php';
    }

    // En desarrollo, el servidor PHP se lanza DESDE la carpeta api/ como raíz,
    // por lo que la URL no necesita el prefijo /api.
    // En producción con Apache/Nginx, la carpeta api/ está bajo /api/.
    $isDev = (str_contains($host, ':8000') || str_contains($host, 'localhost'));
    $apiPrefix = $isDev ? '' : '/api';

    $signedUrl = $scheme . '://' . $host . $apiPrefix . $endpoint
               . '?file=' . urlencode($file)
               . '&ftoken=' . urlencode($ftoken);

    http_response_code(200);
    echo json_encode([
        'status'     => 200,
        'signed_url' => $signedUrl,
        'expires_in' => $ttl,
    ]);

} catch (Exception $e) {
    error_log('Error en signed_url.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error interno del servidor']);
}
