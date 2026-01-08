<?php
/**
 * API Endpoint: Delete Sale
 * Method: DELETE
 * Description: Eliminar una venta por ID (solo administradores)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/cors.php';

// Solo DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['status' => 405, 'message' => 'Método no permitido']);
    exit;
}

// Validar autenticación
$user = requireAuth();

// Solo admin puede eliminar
if ($user['rol'] !== 'admin' && $user['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode([
        'status' => 403,
        'message' => 'Acceso denegado. Solo administradores pueden eliminar ventas.'
    ]);
    exit;
}

try {
    // Obtener ID de la venta
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || !is_numeric($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'ID de venta inválido'
        ]);
        exit;
    }

    $saleId = intval($input['id']);

    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verificar que la venta existe y obtener info de la foto
    $checkStmt = $conn->prepare("SELECT id, foto_factura FROM ventas WHERE id = :id");
    $checkStmt->bindValue(':id', $saleId, PDO::PARAM_INT);
    $checkStmt->execute();
    $sale = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        http_response_code(404);
        echo json_encode([
            'status' => 404,
            'message' => 'Venta no encontrada'
        ]);
        exit;
    }
    
    // Eliminar venta de la base de datos
    $deleteStmt = $conn->prepare("DELETE FROM ventas WHERE id = :id");
    $deleteStmt->bindValue(':id', $saleId, PDO::PARAM_INT);
    
    if ($deleteStmt->execute()) {
        // Si había una foto, intentar eliminarla del servidor
        if ($sale['foto_factura']) {
            $photoPath = __DIR__ . '/../../' . $sale['foto_factura'];
            if (file_exists($photoPath)) {
                @unlink($photoPath); // @ para suprimir warnings si no se puede eliminar
            }
        }
        
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'message' => 'Venta eliminada exitosamente',
            'data' => [
                'id' => $saleId,
                'deleted_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Error al eliminar la venta de la base de datos');
    }
    
} catch (Exception $e) {
    error_log("Error en delete.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Error al eliminar la venta'
    ]);
}
