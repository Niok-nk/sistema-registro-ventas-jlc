<?php
/**
 * Inicializador de SQLite para JLC Ventas
 * Convierte y ejecuta el esquema MySQL en SQLite
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verificar si es SQLite
    if ($conn->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
        die("Este script es solo para inicializar SQLite. Tu conexión actual es MySQL.");
    }

    echo "Inicializando base de datos SQLite...\n\n";

    // Esquema adaptado para SQLite
    $queries = [
        "DROP TABLE IF EXISTS auditoria",
        "DROP TABLE IF EXISTS sesiones",
        "DROP TABLE IF EXISTS ventas",
        "DROP TABLE IF EXISTS productos_jlc",
        "DROP TABLE IF EXISTS usuarios",

        // Usuarios
        "CREATE TABLE usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cedula TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            rol TEXT NOT NULL DEFAULT 'asesor', -- enum simulado
            nombre TEXT NOT NULL,
            apellido TEXT NOT NULL,
            tipo_documento TEXT NOT NULL DEFAULT 'CC', -- enum simulado
            numero_documento TEXT NOT NULL,
            fecha_nacimiento TEXT NOT NULL, -- date almacenado como texto
            ciudad_residencia TEXT NOT NULL,
            departamento TEXT NOT NULL,
            whatsapp TEXT NOT NULL,
            telefono TEXT DEFAULT NULL,
            correo TEXT NOT NULL UNIQUE,
            nombre_distribuidor TEXT NOT NULL,
            ciudad_punto_venta TEXT NOT NULL,
            direccion_punto_venta TEXT DEFAULT NULL,
            cargo TEXT NOT NULL,
            antiguedad_meses INTEGER DEFAULT 0,
            metodo_pago_preferido TEXT DEFAULT NULL,
            llave_breb TEXT DEFAULT NULL,
            acepta_tratamiento_datos INTEGER NOT NULL DEFAULT 0,
            acepta_contacto_comercial INTEGER NOT NULL DEFAULT 0,
            declara_info_verdadera INTEGER NOT NULL DEFAULT 0,
            activo INTEGER NOT NULL DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",

        // Productos
        "CREATE TABLE productos_jlc (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            modelo TEXT NOT NULL UNIQUE,
            descripcion TEXT NOT NULL,
            activo INTEGER NOT NULL DEFAULT 1
        )",

        // Ventas
        "CREATE TABLE ventas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            asesor_id INTEGER NOT NULL,
            numero_factura TEXT NOT NULL,
            foto_factura TEXT NOT NULL,
            producto_id INTEGER NOT NULL,
            numero_serie TEXT NOT NULL,
            fecha_venta TEXT NOT NULL,
            estado TEXT NOT NULL DEFAULT 'pendiente', -- enum simulado
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (asesor_id) REFERENCES usuarios (id) ON DELETE CASCADE,
            FOREIGN KEY (producto_id) REFERENCES productos_jlc (id)
        )",

        // Sesiones
        "CREATE TABLE sesiones (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            token_hash TEXT NOT NULL UNIQUE,
            expires_at TEXT NOT NULL,
            revoked INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
        )",
        
         // Auditoría
        "CREATE TABLE auditoria (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER DEFAULT NULL,
            accion TEXT NOT NULL,
            tabla_afectada TEXT DEFAULT NULL,
            registro_id INTEGER DEFAULT NULL,
            datos_anteriores TEXT DEFAULT NULL,
            datos_nuevos TEXT DEFAULT NULL,
            ip_address TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Usuario Admin por defecto
        "INSERT INTO usuarios (
            cedula, password, rol, nombre, apellido, numero_documento, 
            fecha_nacimiento, ciudad_residencia, departamento, whatsapp, correo,
            nombre_distribuidor, ciudad_punto_venta, cargo
        ) VALUES (
            '1234567890',
            '" . password_hash('Admin2024!', PASSWORD_DEFAULT) . "',
            'administrador', 'Admin', 'Sistema', '1234567890', 
            '2000-01-01', 'Bogotá', 'Cundinamarca', '3000000000', 'admin@jlc.com',
            'JLC Principal', 'Bogotá', 'Administrador'
        )",
        
        // Producto de prueba
        "INSERT INTO productos_jlc (modelo, descripcion) VALUES ('X100', 'Auriculares Pro')"
    ];

    foreach ($queries as $query) {
        $conn->exec($query);
        // Mostrar primera línea de la query para feedback
        $short = substr($query, 0, 50) . (strlen($query) > 50 ? '...' : '');
        echo "Ejecutado: $short\n";
    }

    echo "\n¡Base de datos SQLite inicializada correctamente!";
    echo "\nUsuario Admin: 1234567890 / Admin2024!";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
