-- Migración MySQL: Agregar rol 'auditor' al ENUM
-- Fecha: 2026-01-26
-- Descripción: Modifica el campo 'rol' para incluir el nuevo rol 'auditor'

-- En MySQL, para modificar un ENUM necesitamos usar ALTER TABLE MODIFY
ALTER TABLE `usuarios` 
MODIFY COLUMN `rol` ENUM('asesor', 'administrador', 'auditor') NOT NULL DEFAULT 'asesor';

-- Verificar el cambio
-- SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'rol';
