-- Migración SQLite: Agregar campo estado_aprobacion a tabla usuarios
-- Fecha: 2025-12-22
-- Nota: SQLite no soporta ALTER COLUMN, debemos crear nueva tabla y copiar datos

-- Paso 1: Crear nueva tabla con el campo adicional
CREATE TABLE usuarios_new (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  cedula TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL,
  rol TEXT NOT NULL DEFAULT 'asesor' CHECK(rol IN ('asesor', 'administrador')),
  nombre TEXT NOT NULL,
  apellido TEXT NOT NULL,
  tipo_documento TEXT NOT NULL DEFAULT 'CC' CHECK(tipo_documento IN ('CC', 'CE', 'TI', 'Pasaporte')),
  numero_documento TEXT NOT NULL,
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
  activo INTEGER NOT NULL DEFAULT 1,
  estado_aprobacion TEXT NOT NULL DEFAULT 'pendiente' CHECK(estado_aprobacion IN ('pendiente', 'aprobado', 'rechazado')),
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Paso 2: Copiar datos de la tabla antigua a la nueva (usuarios existentes se aprueban automáticamente)
INSERT INTO usuarios_new 
  (id, cedula, password, rol, nombre, apellido, tipo_documento, numero_documento, 
   fecha_nacimiento, ciudad_residencia, departamento, whatsapp, telefono, correo,
   nombre_distribuidor, ciudad_punto_venta, direccion_punto_venta, cargo, 
   antiguedad_meses, metodo_pago_preferido, llave_breb, acepta_tratamiento_datos,
   acepta_contacto_comercial, declara_info_verdadera, activo, estado_aprobacion, 
   created_at, updated_at)
SELECT 
  id, cedula, password, rol, nombre, apellido, tipo_documento, numero_documento,
  fecha_nacimiento, ciudad_residencia, departamento, whatsapp, telefono, correo,
  nombre_distribuidor, ciudad_punto_venta, direccion_punto_venta, cargo,
  antiguedad_meses, metodo_pago_preferido, llave_breb, acepta_tratamiento_datos,
  acepta_contacto_comercial, declara_info_verdadera, activo, 'aprobado',
  created_at, updated_at
FROM usuarios;

-- Paso 3: Eliminar tabla antigua
DROP TABLE usuarios;

-- Paso 4: Renombrar nueva tabla
ALTER TABLE usuarios_new RENAME TO usuarios;

-- Paso 5: Crear índices
CREATE INDEX idx_rol_activo ON usuarios(rol, activo);
CREATE INDEX idx_estado_aprobacion ON usuarios(estado_aprobacion);
CREATE UNIQUE INDEX idx_cedula ON usuarios(cedula);
CREATE UNIQUE INDEX idx_correo ON usuarios(correo);
