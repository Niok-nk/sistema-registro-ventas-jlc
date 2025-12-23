-- Migración: Agregar campo estado_aprobacion a tabla usuarios
-- Fecha: 2025-12-22
-- Descripción: Permite aprobar/rechazar usuarios antes de dar acceso

-- Paso 1: Agregar columna estado_aprobacion
ALTER TABLE usuarios 
ADD COLUMN estado_aprobacion ENUM('pendiente', 'aprobado', 'rechazado') 
NOT NULL DEFAULT 'pendiente' 
AFTER activo;

-- Paso 2: Aprobar automáticamente todos los usuarios existentes
-- (solo los que ya estaban antes de esta migración)
UPDATE usuarios 
SET estado_aprobacion = 'aprobado' 
WHERE created_at < NOW();

-- Paso 3: Crear índice para mejorar consultas de usuarios pendientes
ALTER TABLE usuarios 
ADD INDEX idx_estado_aprobacion (estado_aprobacion);
