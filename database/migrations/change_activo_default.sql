-- Migración: Cambiar default del campo activo a 0 (inactivo)
-- Propósito: Los nuevos usuarios deben ser inactivos por defecto hasta ser aprobados por un admin
-- Fecha: 2025-12-22

-- Para MySQL
ALTER TABLE usuarios 
MODIFY COLUMN activo tinyint(1) NOT NULL DEFAULT 0;

-- Nota: Esta migración NO afecta usuarios existentes, solo nuevos registros
-- Los usuarios ya existentes mantendrán su valor actual de activo
