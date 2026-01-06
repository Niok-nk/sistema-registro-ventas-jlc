<?php
/**
 * API Endpoint: Import CSV for Bulk Status Updates
 * Method: POST
 * Description: Actualización masiva de estados de facturas (solo admin)
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

// Validar autenticación
$user = requireAuth();

// Solo admin puede importar
if ($user['rol'] !== 'admin' && $user['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode([
        'status' => 403,
        'message' => 'Acceso denegado. Solo administradores pueden importar CSV.'
    ]);
    exit;
}

try {
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['updates']) || !is_array($input['updates'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Datos inválidos'
        ]);
        exit;
    }

    $updates = $input['updates'];
    
    // Validar que no esté vacío
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'No se encontraron datos para actualizar'
        ]);
        exit;
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Estados válidos
    $validStates = ['pendiente', 'aprobada', 'rechazada'];
    
    // Contadores
    $total = count($updates);
    $updated = 0;
    $skipped = 0;
    $errors = [];
    $successes = [];
    
    // Procesar cada actualización
    foreach ($updates as $index => $update) {
        $rowNum = $index + 2; // +2 porque: +1 por header, +1 por index base-0
        
        // Validar campos requeridos
        if (!isset($update['id']) || !isset($update['numero_factura']) || !isset($update['estado'])) {
            $skipped++;
            $errors[] = "Fila {$rowNum}: Faltan campos requeridos (ID, N° Factura o Estado)";
            continue;
        }
        
        $id = intval($update['id']);
        $numero_factura = trim($update['numero_factura']);
        $estado = trim($update['estado']);
        
        // Validar ID válido
        if ($id <= 0) {
            $skipped++;
            $errors[] = "Fila {$rowNum}: ID inválido ({$update['id']})";
            continue;
        }
        
        // Validar N° Factura no vacío
        if (empty($numero_factura)) {
            $skipped++;
            $errors[] = "Fila {$rowNum}: N° Factura vacío";
            continue;
        }
        
        // Validar estado válido (case-sensitive)
        if (!in_array($estado, $validStates, true)) {
            $skipped++;
            $errors[] = "Fila {$rowNum}: Estado '{$estado}' inválido (debe ser: pendiente, aprobada o rechazada)";
            continue;
        }
        
        // Verificar que ID y N° Factura coincidan
        $checkStmt = $conn->prepare("
            SELECT id FROM ventas 
            WHERE id = :id AND numero_factura = :numero_factura
        ");
        $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $checkStmt->bindValue(':numero_factura', $numero_factura, PDO::PARAM_STR);
        $checkStmt->execute();
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$exists) {
            $skipped++;
            $errors[] = "Fila {$rowNum}: ID {$id} y N° Factura '{$numero_factura}' no coinciden o no existen";
            continue;
        }
        
        // Actualizar estado
        $updateStmt = $conn->prepare("
            UPDATE ventas 
            SET estado = :estado,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND numero_factura = :numero_factura
        ");
        
        $updateStmt->bindValue(':estado', $estado, PDO::PARAM_STR);
        $updateStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $updateStmt->bindValue(':numero_factura', $numero_factura, PDO::PARAM_STR);
        
        if ($updateStmt->execute()) {
            $updated++;
            $successes[] = "Fila {$rowNum}: ID {$id} - {$numero_factura} → {$estado}";
        } else {
            $skipped++;
            $errors[] = "Fila {$rowNum}: Error al actualizar";
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'message' => 'Importación completada',
        'data' => [
            'total' => $total,
            'updated' => $updated,
            'skipped' => $skipped,
            'successes' => $successes,
            'errors' => $errors
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en import_csv.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Error al procesar importación'
    ]);
}
