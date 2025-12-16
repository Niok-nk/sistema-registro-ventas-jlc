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

require_once __DIR__ . '/../controllers/AuthController.php';

$auth = new AuthController();

// Obtener datos del cuerpo de la solicitud JSON
$data = json_decode(file_get_contents("php://input"), true);

// Fallback para x-www-form-urlencoded
if (is_null($data)) {
    $data = $_POST;
}

$result = $auth->login($data);

http_response_code($result['status']);
echo json_encode($result);
