<?php
/**
 * GET /api/distribuidores/list.php
 * Retorna los distribuidores únicos derivados de la columna nombre_distribuidor
 * de la tabla usuarios, junto con el número de asesores y la ciudad.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/cors.php';

// Solo admins/auditores
$authUser = requireAuth();
if (!in_array($authUser['rol'], ['administrador', 'auditor'])) {
    http_response_code(403);
    echo json_encode(['status' => 403, 'message' => 'Acceso denegado.']);
    exit;
}

try {
    $db   = Database::getInstance();
    $conn = $db->getConnection();

    // Agrupa por nombre_distribuidor y calcula métricas
    $sql = "
        SELECT
            nombre_distribuidor                                    AS nombre,
            MAX(ciudad_punto_venta)                                AS ciudad,
            COUNT(*)                                               AS total_asesores,
            SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END)           AS asesores_activos,
            MIN(created_at)                                        AS primera_vinculacion
        FROM usuarios
        WHERE nombre_distribuidor IS NOT NULL
          AND nombre_distribuidor != ''
        GROUP BY nombre_distribuidor
        ORDER BY nombre_distribuidor ASC
    ";

    $stmt = $conn->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir a enteros
    foreach ($rows as &$row) {
        $row['total_asesores']   = (int) $row['total_asesores'];
        $row['asesores_activos'] = (int) $row['asesores_activos'];
    }

    echo json_encode($rows);

} catch (Exception $e) {
    error_log('Error distribuidores/list.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error al obtener distribuidores.']);
}
