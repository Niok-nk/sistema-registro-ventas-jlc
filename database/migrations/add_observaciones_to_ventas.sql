-- Migración: agregar columna observaciones a ventas
-- Fecha: 2026-03-09

ALTER TABLE ventas ADD COLUMN observaciones TEXT DEFAULT NULL;
