<?php
/**
 * Script para ejecutar migración de SQLite: Agregar estado_aprobacion
 * Ejecutar desde terminal: php database/migrations/run_sqlite_migration.php
 */

// Configuración
$db_file = __DIR__ . '/../database.sqlite';
$migration_file = __DIR__ . '/add_estado_aprobacion_sqlite.sql';

echo "=== Migración SQLite: Agregar estado_aprobacion ===\n\n";

// Verificar que existe la base de datos
if (!file_exists($db_file)) {
    die("❌ Error: No se encontró la base de datos en: $db_file\n");
}

// Verificar que existe el archivo de migración
if (!file_exists($migration_file)) {
    die("❌ Error: No se encontró el archivo de migración en: $migration_file\n");
}

try {
    // Conectar a SQLite
    echo "📁 Conectando a SQLite: $db_file\n";
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar si ya tiene el campo
    echo "🔍 Verificando si la migración ya fue ejecutada...\n";
    $result = $pdo->query("PRAGMA table_info(usuarios)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $hasEstadoAprobacion = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'estado_aprobacion') {
            $hasEstadoAprobacion = true;
            break;
        }
    }
    
    if ($hasEstadoAprobacion) {
        echo "✅ La migración ya fue ejecutada anteriormente.\n";
        echo "   El campo 'estado_aprobacion' ya existe en la tabla usuarios.\n";
        exit(0);
    }
    
    // Hacer backup primero
    $backup_file = $db_file . '.backup_' . date('Y-m-d_H-i-s');
    echo "💾 Creando backup en: $backup_file\n";
    copy($db_file, $backup_file);
    echo "✅ Backup creado exitosamente\n\n";
    
    // Leer el archivo SQL
    echo "📄 Leyendo archivo de migración...\n";
    $migration_sql = file_get_contents($migration_file);
    
    if (!$migration_sql) {
        die("❌ Error: No se pudo leer el archivo de migración\n");
    }
    
    // Ejecutar migración
    echo "⚙️  Ejecutando migración...\n";
    $pdo->exec($migration_sql);
    
    // Verificar que se ejecutó correctamente
    echo "🔍 Verificando migración...\n";
    $result = $pdo->query("PRAGMA table_info(usuarios)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $migrationSuccess = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'estado_aprobacion') {
            $migrationSuccess = true;
            break;
        }
    }
    
    if ($migrationSuccess) {
        echo "✅ Migración ejecutada exitosamente!\n\n";
        
        // Mostrar estadísticas
        $count = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        echo "📊 Estadísticas:\n";
        echo "   - Total de usuarios: $count\n";
        
        $aprobados = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado_aprobacion = 'aprobado'")->fetchColumn();
        echo "   - Usuarios aprobados: $aprobados\n";
        
        $pendientes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado_aprobacion = 'pendiente'")->fetchColumn();
        echo "   - Usuarios pendientes: $pendientes\n\n";
        
        echo "🎉 ¡Migración completada con éxito!\n";
        echo "   Todos los usuarios existentes fueron aprobados automáticamente.\n";
        echo "   Los nuevos usuarios tendrán estado 'pendiente' por defecto.\n";
    } else {
        throw new Exception("La verificación falló: campo estado_aprobacion no encontrado");
    }
    
} catch (PDOException $e) {
    echo "\n❌ Error de base de datos:\n";
    echo "   " . $e->getMessage() . "\n\n";
    
    if (isset($backup_file) && file_exists($backup_file)) {
        echo "💡 Tip: Puedes restaurar el backup desde: $backup_file\n";
    }
    
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}
