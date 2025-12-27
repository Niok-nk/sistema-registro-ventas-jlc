<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Autenticación requerida
$user = requireAuth();

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Consulta - Obtener ventas según el rol del usuario
    // Admin ve todas las ventas, Asesor solo ve las suyas
    $sql = "SELECT v.id, v.numero_factura, v.fecha_venta, v.estado, v.numero_serie, v.producto_id, v.foto_factura,
                   p.modelo as modelo_producto, p.descripcion as desc_producto,
                   u.nombre as nombre_asesor, u.apellido as apellido_asesor, u.cedula as cedula_asesor,
                   u.nombre_distribuidor, u.llave_breb
            FROM ventas v
            JOIN productos_jlc p ON v.producto_id = p.id
            JOIN usuarios u ON v.asesor_id = u.id";

    // Si el usuario NO es admin, filtrar solo sus ventas
    $params = [];
    if ($user['rol'] !== 'admin' && $user['rol'] !== 'administrador') {
        $sql .= " WHERE v.asesor_id = :asesor_id";
        $params[':asesor_id'] = $user['user_id'];
    }

    $sql .= " ORDER BY v.fecha_venta DESC, v.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener el token JWT del header para construir URLs seguras
    $headers = apache_request_headers();
    $token = null;
    if (isset($headers['Authorization'])) {
        $matches = [];
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        }
    }

    // Agregar URL completa de foto a cada venta
    foreach ($ventas as &$venta) {
        if (!empty($venta['foto_factura']) && $token) {
            // Construir URL segura con endpoint view_receipt.php
            $apiUrl = getenv('API_URL') ?: 'http://localhost:8000/api';
            $apiUrl = rtrim($apiUrl, '/'); // Asegurarse de que no haya una barra al final

            // Formato: https://<dominio>/api/sales/view_receipt.php?file=NOMBRE&token=JWT
            $venta['foto_url'] = $apiUrl . '/sales/view_receipt.php?file=' . urlencode($venta['foto_factura']) . '&token=' . $token;
        } else {
            $venta['foto_url'] = null;
        }
    }
    unset($venta); // Romper referencia

    http_response_code(200);
    echo json_encode(['status' => 200, 'data' => $ventas]);
} catch (PDOException $e) {
    error_log("Error listando ventas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error al obtener historial']);
}
