# Migración: Agregar Estado de Aprobación de Usuarios

## 📋 Descripción

Esta migración agrega el campo `estado_aprobacion` a la tabla `usuarios` para implementar un sistema de aprobación de usuarios por parte de administradores.

## 🔧 Cómo ejecutar

### Opción 1: Desde phpMyAdmin (Recomendado)

1. Accede a phpMyAdmin
2. Selecciona tu base de datos
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido completo de `add_estado_aprobacion.sql`
5. Click en "Go" o "Continuar"

### Opción 2: Desde MySQL CLI

```bash
mysql -u tu_usuario -p tu_base_de_datos < database/migrations/add_estado_aprobacion.sql
```

### Opción 3: Desde PHP

Si tienes acceso a ejecutar scripts PHP en el servidor:

```php
<?php
require_once 'config/database.php';
$db = getDBConnection();
$migration = file_get_contents(__DIR__ . '/migrations/add_estado_aprobacion.sql');
$db->exec($migration);
echo "Migración completada";
?>
```

## ✅ Verificación

Después de ejecutar la migración, verifica:

```sql
-- Ver estructura de la tabla
DESCRIBE usuarios;

-- Verificar que usuarios existentes estén aprobados
SELECT id, nombre, apellido, estado_aprobacion FROM usuarios;
```

Deberías ver:
- Campo `estado_aprobacion` en la tabla
- Todos los usuarios existentes con `estado_aprobacion = 'aprobado'`

## ⚠️ Notas Importantes

- **Backup primero**: Haz un backup de tu base de datos antes de ejecutar
- **Usuarios existentes**: Se aprobarán automáticamente con la migración
- **Nuevos usuarios**: Tendrán `estado_aprobacion = 'pendiente'` por defecto
- **No revertible fácilmente**: Una vez ejecutada, necesitarás un script de rollback si quieres revertir

## 🔄 Rollback (Si es necesario)

Si necesitas revertir la migración:

```sql
ALTER TABLE usuarios DROP COLUMN estado_aprobacion;
ALTER TABLE usuarios DROP INDEX idx_estado_aprobacion;
```

> ⚠️ **Advertencia**: Esto eliminará todos los datos de aprobación de usuarios

## 3. Cambiar Default del Campo `activo` (2025-12-22)

**Propósito:** Los nuevos usuarios deben ser inactivos por defecto hasta que un administrador los active.

### MySQL (Producción)

```bash
# Ejecutar en MySQL
mysql -u tu_usuario -p tu_database < change_activo_default.sql
```

O ejecuta directamente:
```sql
ALTER TABLE usuarios 
MODIFY COLUMN activo tinyint(1) NOT NULL DEFAULT 0;
```

### SQLite (Desarrollo Local)

**SQLite no permite cambiar el DEFAULT de una columna existente.**

El cambio ya está en `schema.sql` y se aplicará automáticamente a **nuevos registros**.

**Para usuarios existentes:** No es necesario hacer nada. Los usuarios actuales mantienen su estado actual.

### Verificación

```sql
-- Ver la definición de la tabla
SHOW CREATE TABLE usuarios; -- MySQL
.schema usuarios -- SQLite

-- Ver estado de usuarios actuales
SELECT activo, COUNT(*) as cantidad 
FROM usuarios 
GROUP BY activo;
```

---
