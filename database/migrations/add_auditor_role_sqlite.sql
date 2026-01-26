-- Migración SQLite: Agregar rol 'auditor'
-- SQLite no soporta ALTER para modificar CHECK constraints
-- Esta migración recreará la tabla usuarios con el nuevo rol

-- Paso 1: Crear tabla temporal con el nuevo constraint
CREATE TABLE usuarios_temp (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  cedula TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL,
  rol TEXT NOT NULL DEFAULT 'asesor' CHECK(rol IN ('asesor', 'administrador', 'auditor')),
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
  estado_aprobacion TEXT NOT NULL DEFAULT 'pendiente' CHECK(estado_aprobacion IN ('pendiente', 'aprobado', 'rechazado')),
  created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);

-- Paso 2: Copiar todos los datos
INSERT INTO usuarios_temp SELECT * FROM usuarios;

-- Paso 3: Eliminar tabla original
DROP TABLE usuarios;

-- Paso 4: Renombrar tabla temporal
ALTER TABLE usuarios_temp RENAME TO usuarios;

-- Paso 5: Recrear índices
CREATE INDEX IF NOT EXISTS idx_usuarios_rol ON usuarios(rol);
CREATE INDEX IF NOT EXISTS idx_usuarios_estado_aprobacion ON usuarios(estado_aprobacion);
CREATE INDEX IF NOT EXISTS idx_usuarios_activo ON usuarios(activo);
