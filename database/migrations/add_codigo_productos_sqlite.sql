-- Migración: Agregar columna 'codigo' a productos_jlc (SQLite)
-- Fecha: 2025-12-26
-- Descripción: Agrega código de producto para mejor identificación

ALTER TABLE productos_jlc 
ADD COLUMN codigo TEXT;

-- SQLite crea índices automáticamente, pero podemos agregar uno explícito
CREATE INDEX IF NOT EXISTS idx_productos_codigo ON productos_jlc(codigo);
