<?php
/**
 * Test de Conexión a Base de Datos
 * Sistema JLC
 *
 * Verificar en:
 * - Local: http://localhost:8000/test_db.php
 * - Producción: https://ventas.jlc-electronics.com/api/test_db.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config/database.php';

try {
    // ===================================
    // PASO PREVIO: Inicializar Database (Carga ENV)
    // ===================================
    // Verificar extensiones antes de instanciar para evitar errores fatales
    if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
         throw new Exception('PDO o PDO_MySQL extension no está disponible');
    }

    $database = Database::getInstance();

    $response = [
        'status' => 'error',
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => [
            'php_version' => phpversion(),
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
        ],
        'extensions' => [
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'openssl' => extension_loaded('openssl'),
            'json' => extension_loaded('json')
        ],
        'env_variables' => [
            'DB_HOST' => getenv('DB_HOST') ?: '❌ NOT SET',
            'DB_NAME' => getenv('DB_NAME') ?: '❌ NOT SET',
            'DB_USER' => getenv('DB_USER') ?: '❌ NOT SET',
            'DB_PASS' => getenv('DB_PASS') ? '✅ SET (hidden)' : '❌ NOT SET',
            'ENVIRONMENT' => getenv('ENVIRONMENT') ?: 'NOT SET (Defaults to development)'
        ],
        'database' => [
            'status' => 'disconnected',
            'connection_test' => false
        ]
    ];

    // ===================================
    // PASO 3: Test de conexión básico
    // ===================================
    $connection_test = $database->testConnection();
    $response['database']['connection_test'] = $connection_test;

    if (!$connection_test) {
        throw new Exception('La conexión a la base de datos falló en el test básico');
    }

    // ===================================
    // PASO 4: Obtener conexión PDO
    // ===================================
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('No se pudo obtener la conexión PDO');
    }

    // ===================================
    // PASO 5: Obtener info de la BD
    // ===================================
    $db_info = $database->getDatabaseInfo();
    $response['database']['info'] = $db_info;

    // ===================================
    // PASO 6: Verificar versión MySQL
    // ===================================
    // ===================================
    // PASO 6: Verificar versión BD
    // ===================================
    $db_driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    $response['database']['driver'] = $db_driver;

    if ($db_driver === 'sqlite') {
        $stmt = $conn->query("SELECT sqlite_version() as version");
    } else {
        $stmt = $conn->query("SELECT VERSION() as version");
    }

    $version_info = $stmt->fetch();
    $response['database']['version'] = $version_info['version'];

    // ===================================
    // PASO 7: Listar tablas existentes
    // ===================================
    // ===================================
    // PASO 7: Listar tablas existentes
    // ===================================
    if ($db_driver === 'sqlite') {
        $stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    } else {
        $stmt = $conn->query("SHOW TABLES");
    }
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $response['database']['tables'] = $tables;
    $response['database']['table_count'] = count($tables);

    // ===================================
    // PASO 8: Verificar tablas esperadas
    // ===================================
    $expected_tables = ['usuarios', 'ventas', 'productos_jlc', 'sesiones'];
    $missing_tables = [];

    foreach ($expected_tables as $table) {
        if (!in_array($table, $tables)) {
            $missing_tables[] = $table;
        }
    }

    if (!empty($missing_tables)) {
        $response['database']['warning'] = 'Faltan algunas tablas esperadas';
        $response['database']['missing_tables'] = $missing_tables;
        $response['database']['note'] = 'Necesitas ejecutar database/schema.sql';
    }

    // ===================================
    // PASO 9: Contar registros (si existen tablas)
    // ===================================
    $counts = [];
    foreach (['usuarios', 'ventas', 'productos_jlc'] as $table) {
        if (in_array($table, $tables)) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch();
            $counts[$table] = (int)$count['count'];
        }
    }

    if (!empty($counts)) {
        $response['database']['record_counts'] = $counts;
    }

    // ===================================
    // PASO 10: Test de escritura (opcional)
    // ===================================
    try {
        $conn->query("CREATE TEMPORARY TABLE test_write (id INT)");
        $conn->query("DROP TEMPORARY TABLE test_write");
        $response['database']['write_test'] = '✅ OK';
    } catch (PDOException $e) {
        $response['database']['write_test'] = '❌ FAILED: ' . $e->getMessage();
    }

    // ===================================
    // TODO OK
    // ===================================
    $response['status'] = 'success';
    $response['database']['status'] = '✅ connected';
    $response['message'] = 'Conexión a base de datos exitosa';

    http_response_code(200);

} catch (Exception $e) {
    // ===================================
    // MANEJO DE ERRORES
    // ===================================
    http_response_code(500);

    $response['status'] = 'error';
    $response['error'] = [
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ];

    // Sugerencias según el error
    $error_msg = strtolower($e->getMessage());
    $suggestions = [];

    if (strpos($error_msg, 'access denied') !== false) {
        $suggestions[] = 'Verifica DB_USER y DB_PASS en tu archivo .env';
        $suggestions[] = 'Asegúrate que el usuario tenga permisos en la base de datos';
    }

    if (strpos($error_msg, 'unknown database') !== false) {
        $suggestions[] = 'La base de datos no existe. Créala en phpMyAdmin';
        $suggestions[] = 'Verifica que DB_NAME en .env sea correcto';
    }

    if (strpos($error_msg, 'connection') !== false) {
        $suggestions[] = 'Verifica que MySQL esté corriendo';
        $suggestions[] = 'Verifica DB_HOST en .env (debería ser "localhost")';
    }

    if (strpos($error_msg, '.env') !== false) {
        $suggestions[] = 'Archivo .env no encontrado o no se puede leer';
        $suggestions[] = 'Crea .env en la raíz del proyecto usando .env.example como plantilla';
        $suggestions[] = 'Verifica los permisos del archivo .env (chmod 600)';
    }

    if (!empty($suggestions)) {
        $response['suggestions'] = $suggestions;
    }

    // Log del error
    error_log("DATABASE TEST FAILED: " . $e->getMessage());
}

// ===================================
// AGREGAR AYUDA AL FOOTER
// ===================================
$response['help'] = [
    'status_codes' => [
        '200' => 'Todo funcionó correctamente',
        '500' => 'Error de servidor o conexión'
    ],
    'next_steps' => []
];

if ($response['status'] === 'success') {
    if (isset($response['database']['missing_tables']) && !empty($response['database']['missing_tables'])) {
        $response['help']['next_steps'][] = '1. Importa database/schema.sql en phpMyAdmin';
        $response['help']['next_steps'][] = '2. Importa database/migrations/002_insert_admin.sql';
        $response['help']['next_steps'][] = '3. Importa database/seeds/productos_jlc.sql';
    } else if (isset($response['database']['record_counts'])) {
        $user_count = $response['database']['record_counts']['usuarios'] ?? 0;

        if ($user_count === 0) {
            $response['help']['next_steps'][] = 'Base de datos vacía. Importa las migraciones y seeds.';
        } else {
            $response['help']['next_steps'][] = '✅ Todo listo! Puedes empezar a desarrollar.';
            $response['help']['credentials'] = [
                'admin' => [
                    'cedula' => '*',
                    'password' => '*',
                    'note' => '*'
                ]
            ];
        }
    }
} else {
    $response['help']['next_steps'][] = 'Revisa las sugerencias arriba para resolver el error';
    $response['help']['next_steps'][] = 'Verifica que el archivo .env existe y tiene las credenciales correctas';
}

// ===================================
// OUTPUT JSON
// ===================================
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
