<?php
/**
 * Script de Inicialización de Base de Datos
 * Ejecuta el schema.sql para crear todas las tablas
 */

require_once __DIR__ . '/../api/config/database.php';

try {
    echo "=== Inicializando Base de Datos ===\n\n";
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Determinar qué schema usar
    $driver = getenv('DB_CONNECTION') ?: 'mysql';
    
    if ($driver === 'sqlite') {
        $schemaFile = __DIR__ . '/schema_sqlite.sql';
        echo "Usando: schema_sqlite.sql\n";
    } else {
        $schemaFile = __DIR__ . '/schema.sql';
        echo "Usando: schema.sql\n";
    }
    
    if (!file_exists($schemaFile)) {
        die("ERROR: No se encontró el archivo de schema\n");
    }
    
    echo "Leyendo schema...\n";
    $schema = file_get_contents($schemaFile);
    
    // Para SQLite, necesitamos ejecutar cada statement por separado
    $driver = getenv('DB_CONNECTION') ?: 'mysql';
    
    if ($driver === 'sqlite') {
        echo "Modo: SQLite\n";
        echo "Ejecutando schema para SQLite...\n\n";
        
        // Remover líneas específicas de MySQL
        $schema = preg_replace('/SET SQL_MODE.*?;/is', '', $schema);
        $schema = preg_replace('/START TRANSACTION;/i', '', $schema);
        $schema = preg_replace('/SET time_zone.*?;/is', '', $schema);
        $schema = preg_replace('/COMMIT;/i', '', $schema);
        $schema = preg_replace('/ENGINE=InnoDB.*?;/i', ';', $schema);
        
        // Convertir tipos MySQL a SQLite
        $schema = preg_replace('/int\(\d+\)/i', 'INTEGER', $schema);
        $schema = preg_replace('/tinyint\(1\)/i', 'INTEGER', $schema);
        $schema = preg_replace('/timestamp/i', 'TEXT', $schema);
        $schema = preg_replace('/ENUM\([^)]+\)/i', 'TEXT', $schema);
        $schema = preg_replace('/AUTO_INCREMENT/i', 'AUTOINCREMENT', $schema);
        $schema = preg_replace('/current_timestamp\(\)/i', "datetime('now','localtime')", $schema);
        $schema = preg_replace('/ON UPDATE current_timestamp\(\)/i', '', $schema);
        $schema = preg_replace('/UNIQUE KEY `([^`]+)` \(`([^`]+)`\)/i', 'UNIQUE ($2)', $schema);
        $schema = preg_replace('/KEY `[^`]+` \([^)]+\)/i', '', $schema);
        $schema = preg_replace('/,\s*,/i', ',', $schema);
        $schema = preg_replace('/CONSTRAINT.*?FOREIGN KEY.*?;/is', ';', $schema);
        
        // Limpiar
        $schema = preg_replace('/,(\s*)\)/i', '$1)', $schema);
    } else {
        echo "Modo: MySQL\n";
    }
    
    // Dividir en statements
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) && 
                   stripos($stmt, '--') !== 0 &&
                   stripos($stmt, 'COMMIT') === false;
        }
    );
    
    echo "Ejecutando " . count($statements) . " statements...\n\n";
    
    foreach ($statements as $i => $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $conn->exec($statement);
            
            // Detectar qué tabla se creó
            if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Tabla '{$matches[1]}' creada\n";
            }
        } catch (PDOException $e) {
            echo "⚠ Warning en statement " . ($i + 1) . ": " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Verificación ===\n\n";
    
    // Verificar tablas creadas
    if ($driver === 'sqlite') {
        $tables = $conn->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    }
    
    echo "Tablas creadas:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    echo "\n✅ Base de datos inicializada correctamente\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
