<?php
/**
 * Script de prueba para verificar carga de .env y JWT_SECRET
 */

// Cargar .env
$envFile = __DIR__ . '/../.env';

echo "=== TEST: .env Loading ===\n";
echo "Looking for .env at: $envFile\n";
echo ".env exists: " . (file_exists($envFile) ? 'YES' : 'NO') . "\n\n";

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    echo "=== Loading .env ===\n";
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || trim($line) === '') {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        
        // Solo mostrar primeros caracteres del secret
        if ($name === 'JWT_SECRET') {
            echo "  ✓ $name = " . substr($value, 0, 20) . "... (length: " . strlen($value) . ")\n";
        } else {
            echo "  ✓ $name = $value\n";
        }
    }
}

echo "\n=== Verificando getenv() ===\n";
$jwtSecret = getenv('JWT_SECRET');
echo "JWT_SECRET via getenv(): " . ($jwtSecret ? substr($jwtSecret, 0, 20) . '... (length: ' . strlen($jwtSecret) . ')' : 'NOT FOUND') . "\n";
echo "DB_CONNECTION via getenv(): " . (getenv('DB_CONNECTION') ?: 'NOT FOUND') . "\n";
echo "ENVIRONMENT via getenv(): " . (getenv('ENVIRONMENT') ?: 'NOT FOUND') . "\n";

echo "\n=== Test JWT Class ===\n";
require_once __DIR__ . '/../utils/JWT.php';

try {
    // Generar token de prueba
    $testPayload = [
        'user_id' => 999,
        'cedula' => 'TEST123',
        'rol' => 'administrador',
        'nombre' => 'Test User'
    ];
    
    $token = JWT::generate($testPayload);
    echo "✓ Token generado: " . substr($token, 0, 50) . "...\n";
    
    // Verificar token
    $decoded = JWT::verify($token);
    echo "✓ Token verificado exitosamente\n";
    echo "  Payload: " . json_encode($decoded) . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== DONE ===\n";
