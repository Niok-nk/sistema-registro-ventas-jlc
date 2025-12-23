-- Migración SQLite: Cambiar default del campo activo a 0
-- Nota: SQLite no soporta ALTER COLUMN para cambiar DEFAULT
-- Esta migración es solo documentativa; el default se aplicará en schema.sql para nuevas instancias

-- Para SQLite existente, el cambio de DEFAULT no requiere migración
-- El nuevo default se aplicará automáticamente a NUEVOS registros después de actualizar el schema

-- Si necesitas cambiar todos los usuarios existentes a inactivos, ejecuta:
-- UPDATE usuarios SET activo = 0 WHERE activo = 1;
-- ADVERTENCIA: Esto desactivará TODOS los usuarios. Úsalo con precaución.

-- Verificar usuarios activos antes de ejecutar:
SELECT COUNT(*) as total_activos FROM usuarios WHERE activo = 1;

-- Verificar usuarios inactivos:
SELECT COUNT(*) as total_inactivos FROM usuarios WHERE activo = 0;
