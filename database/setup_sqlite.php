<?php
/**
 * Setup rápido de BD SQLite
 */
require_once __DIR__ . '/../api/config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "Creando tablas...\n\n";

// Tabla usuarios con TODAS las columnas incluyendo declaraciones legales
$conn->exec("
CREATE TABLE IF NOT EXISTS usuarios (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  cedula TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL,
  rol TEXT NOT NULL DEFAULT 'asesor',
  nombre TEXT NOT NULL,
  apellido TEXT NOT NULL,
  tipo_documento TEXT NOT NULL DEFAULT 'CC',
  numero_documento TEXT NOT NULL UNIQUE,
  fecha_nacimiento TEXT NOT NULL,
  ciudad_residencia TEXT NOT NULL,
  departamento TEXT NOT NULL,
  whatsapp TEXT NOT NULL,
  telefono TEXT,
  correo TEXT NOT NULL UNIQUE,
  nombre_distribuidor TEXT NOT NULL,
  ciudad_punto_venta TEXT NOT NULL,
  direccion_punto_venta TEXT,
  cargo TEXT NOT NULL,
  antiguedad_meses INTEGER DEFAULT 0,
  metodo_pago_preferido TEXT,
  llave_breb TEXT,
  acepta_tratamiento_datos INTEGER NOT NULL DEFAULT 0,
  acepta_contacto_comercial INTEGER NOT NULL DEFAULT 0,
  declara_info_verdadera INTEGER NOT NULL DEFAULT 0,
  declara_naturaleza_comercial INTEGER NOT NULL DEFAULT 0,
  reconoce_no_salario INTEGER NOT NULL DEFAULT 0,
  declara_no_subordinacion INTEGER NOT NULL DEFAULT 0,
  declara_relacion_autonoma INTEGER NOT NULL DEFAULT 0,
  acepta_liberalidades INTEGER NOT NULL DEFAULT 0,
  asume_obligaciones_tributarias INTEGER NOT NULL DEFAULT 0,
  declara_no_contrato INTEGER NOT NULL DEFAULT 0,
  acepta_terminos_programa INTEGER NOT NULL DEFAULT 0,
  activo INTEGER NOT NULL DEFAULT 0,
  estado_aprobacion TEXT NOT NULL DEFAULT 'pendiente',
  created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
)
");
echo "✓ Tabla usuarios creada (con 11 checkboxes)\n";

// Otras tablas
$conn->exec("CREATE TABLE IF NOT EXISTS productos_jlc (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  modelo TEXT NOT NULL UNIQUE,
  descripcion TEXT NOT NULL,
  activo INTEGER NOT NULL DEFAULT 1
)");
echo "✓ Tabla productos_jlc creada\n";

$conn->exec("CREATE TABLE IF NOT EXISTS ventas (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  asesor_id INTEGER NOT NULL,
  numero_factura TEXT NOT NULL,
  foto_factura TEXT NOT NULL,
  producto_id INTEGER NOT NULL,
  numero_serie TEXT NOT NULL,
  fecha_venta TEXT NOT NULL,
  estado TEXT NOT NULL DEFAULT 'pendiente',
  created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
  FOREIGN KEY (asesor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos_jlc (id)
)");
echo "✓ Tabla ventas creada\n";

$conn->exec("CREATE TABLE IF NOT EXISTS sesiones (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  usuario_id INTEGER NOT NULL,
  token_hash TEXT NOT NULL UNIQUE,
  expires_at TEXT NOT NULL,
  revoked INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
)");
echo "✓ Tabla sesiones creada\n";

$conn->exec("CREATE TABLE IF NOT EXISTS auditoria (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  usuario_id INTEGER,
  accion TEXT NOT NULL,
  tabla_afectada TEXT,
  registro_id INTEGER,
  datos_anteriores TEXT,
  datos_nuevos TEXT,
  ip_address TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
)");
echo "✓ Tabla auditoria creada\n";

// Insertar productos
$conn->exec("INSERT OR IGNORE INTO productos_jlc (modelo, descripcion, activo) VALUES
('JL1500', 'Juego de Luces 1500', 1),
('JL2000', 'Juego de Luces 2000', 1),
('JL3000', 'Juego de Luces 3000', 1)");
echo "✓ Productos iniciales insertados\n";

echo "\n✅ Base de datos inicializada correctamente\n";
echo "\nTablas creadas:\n";
$tables = $conn->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "  - $table\n";
}
