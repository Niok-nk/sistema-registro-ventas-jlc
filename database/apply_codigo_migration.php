<?php
/**
 * Script para aplicar migración: Agregar columna codigo a productos_jlc
 * Ejecutar: php database/apply_codigo_migration.php
 */

require_once __DIR__ . '/../api/config/database.php';

echo "🔄 Aplicando migración: add_codigo_productos...\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Leer el archivo de migración SQLite
    $migration = file_get_contents(__DIR__ . '/migrations/add_codigo_productos_sqlite.sql');
    
    // Ejecutar cada statement
    $conn->exec($migration);
    
    echo "✅ Migración aplicada exitosamente\n\n";
    
    // Verificar que la columna existe
    $result = $conn->query("PRAGMA table_info(productos_jlc)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Columnas en productos_jlc:\n";
    foreach ($columns as $col) {
        echo "  - {$col['name']} ({$col['type']})";
        if ($col['name'] === 'codigo') {
            echo " ← ✅ NUEVA COLUMNA";
        }
        echo "\n";
    }
    
    echo "\n✨ ¡Listo! Puedes usar el campo codigo ahora.\n";
    
} catch (PDOException $e) {
    echo "❌ Error al aplicar migración:\n";
    echo $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "\n⚠️  La columna 'codigo' ya existe. No es necesario aplicar la migración.\n";
    }
    
    exit(1);
}
