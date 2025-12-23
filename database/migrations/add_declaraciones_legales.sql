-- Migración: Agregar declaraciones legales del Programa de Incentivos
-- Propósito: Agregar 8 campos booleanos para registrar aceptación de términos legales
-- Fecha: 2025-12-23

-- Agregar campos de declaraciones legales
ALTER TABLE usuarios 
ADD COLUMN declara_naturaleza_comercial tinyint(1) NOT NULL DEFAULT 0 AFTER declara_info_verdadera,
ADD COLUMN reconoce_no_salario tinyint(1) NOT NULL DEFAULT 0 AFTER declara_naturaleza_comercial,
ADD COLUMN declara_no_subordinacion tinyint(1) NOT NULL DEFAULT 0 AFTER reconoce_no_salario,
ADD COLUMN declara_relacion_autonoma tinyint(1) NOT NULL DEFAULT 0 AFTER declara_no_subordinacion,
ADD COLUMN acepta_liberalidades tinyint(1) NOT NULL DEFAULT 0 AFTER declara_relacion_autonoma,
ADD COLUMN asume_obligaciones_tributarias tinyint(1) NOT NULL DEFAULT 0 AFTER acepta_liberalidades,
ADD COLUMN declara_no_contrato tinyint(1) NOT NULL DEFAULT 0 AFTER asume_obligaciones_tributarias,
ADD COLUMN acepta_terminos_programa tinyint(1) NOT NULL DEFAULT 0 AFTER declara_no_contrato;

-- Nota: Usuarios existentes quedarán con valores en 0 (no aplicable retroactivamente)
-- Nuevos usuarios deberán aceptar todas para registrarse
