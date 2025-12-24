-- Migración SQLite: Agregar declaraciones legales del Programa de Incentivos
-- Nota: SQLite no soporta ADD COLUMN múltiple, se ejecutan uno por uno

-- Agregar campos de declaraciones legales
ALTER TABLE usuarios ADD COLUMN declara_naturaleza_comercial INTEGER NOT NULL DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN reconoce_no_salario INTEGER NOT NULL DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN declara_no_subordinacion INTEGER NOT NULL DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN declara_relacion_autonoma INTEGER NOT NULL DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN acepta_liberalidades INTEGER NOT NULL DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN asume_obligaciones_tributarias INTEGER NOT NULL DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN declara_no_contrato INTEGER NOT NULL DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN acepta_terminos_programa INTEGER NOT NULL DEFAULT 0;

-- Verificar campos agregados
-- .schema usuarios
