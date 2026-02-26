-- =====================================================
-- Migración: Agregar columna certificado a usuarios
-- Aplicar en producción (MySQL/MariaDB) en Hostinger
-- =====================================================

ALTER TABLE usuarios
  ADD COLUMN certificado VARCHAR(255) NULL
  AFTER llave_breb;
