-- Migración: Agregar columna 'codigo' a productos_jlc
-- Fecha: 2025-12-26
-- Descripción: Agrega código de producto para mejor identificación

ALTER TABLE productos_jlc 
ADD COLUMN codigo VARCHAR(50) NULL 
AFTER modelo;

-- Índice para búsquedas rápidas por código (opcional pero recomendado)
CREATE INDEX idx_productos_codigo ON productos_jlc(codigo);
