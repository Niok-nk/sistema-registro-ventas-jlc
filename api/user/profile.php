<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

// Autenticación requerida
$user = requireAuth();

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Obtener datos completos del usuario autenticado
    $sql = "SELECT id, cedula, rol, nombre, apellido, tipo_documento, numero_documento,
                   fecha_nacimiento, ciudad_residencia, departamento, whatsapp, telefono,
                   correo, nombre_distribuidor, ciudad_punto_venta, direccion_punto_venta,
                   cargo, antiguedad_meses, llave_breb, certificado,
                   acepta_tratamiento_datos, acepta_contacto_comercial, declara_info_verdadera,
                   activo, created_at, updated_at
            FROM usuarios
            WHERE id = :user_id
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $user['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Convertir valores booleanos para mejor manejo en frontend
    $userData['acepta_tratamiento_datos'] = (bool)$userData['acepta_tratamiento_datos'];
    $userData['acepta_contacto_comercial'] = (bool)$userData['acepta_contacto_comercial'];
    $userData['declara_info_verdadera'] = (bool)$userData['declara_info_verdadera'];
    $userData['activo'] = (bool)$userData['activo'];

    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'data' => $userData
    ]);

} catch (PDOException $e) {
    error_log("Error obteniendo perfil de usuario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error al obtener datos del usuario']);
}
