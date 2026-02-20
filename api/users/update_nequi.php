<?php
/**
 * API Endpoint: Update Nequi (llave_breb)
 * Method: POST
 * Description: Permite a cualquier usuario autenticado actualizar su número de Nequi
 *              almacenado en el campo llave_breb.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/cors.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 405, 'message' => 'Método no permitido']);
    exit;
}

// Validar autenticación (cualquier rol)
$authUser = requireAuth();

// Leer body JSON
$body = json_decode(file_get_contents('php://input'), true);
$nequi = isset($body['nequi']) ? trim($body['nequi']) : '';

// Validar: solo dígitos, exactamente 10 caracteres
if (!preg_match('/^\d{10}$/', $nequi)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 400,
        'message' => 'El número de Nequi debe tener exactamente 10 dígitos numéricos.'
    ]);
    exit;
}

// Actualizar llave_breb
try {
    $db   = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("UPDATE usuarios SET llave_breb = :nequi WHERE id = :id");
    $stmt->bindValue(':nequi', $nequi, PDO::PARAM_STR);
    $stmt->bindValue(':id',    $authUser['user_id'], PDO::PARAM_INT);
    $stmt->execute();

    http_response_code(200);
    echo json_encode([
        'status'  => 200,
        'message' => 'Número de Nequi actualizado exitosamente.'
    ]);

    // Registrar en auditoría
} catch (Exception $e) {
    error_log("Error en users/update_nequi.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 500,
        'message' => 'Error al actualizar. Intenta de nuevo.'
    ]);
}
