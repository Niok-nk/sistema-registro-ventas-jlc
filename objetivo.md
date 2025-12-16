# Sistema de Registro de Ventas JLC

## Documento de Arquitectura y Especificación Técnica

**Versión:** 1.0

**Fecha:** Diciembre 2025

**Cliente:** Distribuidores JLC

**Alcance:** Sistema web para gestión de ventas de asesores

---

## 1. RESUMEN EJECUTIVO

### 1.1 Objetivo del Sistema

Desarrollar una aplicación web para registrar y gestionar las ventas realizadas por asesores de distribución JLC en Colombia, facilitando el seguimiento, control y generación de reportes de la actividad comercial.

### 1.2 Stack Tecnológico Seleccionado

**Frontend:**

- Astro (generación de sitios estáticos)
- JavaScript nativo para interactividad
- CSS

**Backend:**

- PHP 8.x (nativo en Hostinger)
- JWT (JSON Web Tokens) para autenticación
- PDO para operaciones de base de datos

**Base de Datos:**

- MySQL 8.0
- Alojamiento: Hostinger (servidor Colombia)
- Gestión: phpMyAdmin

**Infraestructura:**

- Hosting: Hostinger Colombia
- Versionado: GitHub
- Deploy: GitHub Actions (automático vía FTP)
- Almacenamiento de archivos: servidor local

### 1.3 Capacidad del Sistema

- **Usuarios concurrentes:** 100-150 sin optimizaciones adicionales
- **Tiempo de respuesta objetivo:** < 1 segundo
- **Disponibilidad:** 99.5% (con infraestructura Hostinger)
- **Almacenamiento de imágenes:** Ilimitado (según plan Hostinger)

---

## 2. ARQUITECTURA DEL SISTEMA

### 2.1 Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                    USUARIOS (100 asesores)                   │
│                         Colombia 🇨🇴                          │
└────────────────────┬────────────────────────────────────────┘
                     │ HTTPS
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                   HOSTINGER COLOMBIA                         │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              FRONTEND (Astro Static)                  │  │
│  │  • HTML/CSS/JS optimizado                            │  │
│  │  • Páginas pre-renderizadas                          │  │
│  │  • Assets comprimidos                                │  │
│  └───────────────────┬──────────────────────────────────┘  │
│                      │                                       │
│                      │ Fetch API                             │
│                      ▼                                       │
│  ┌──────────────────────────────────────────────────────┐  │
│  │                  BACKEND PHP                          │  │
│  │                                                       │  │
│  │  ┌─────────────────────────────────────────────┐    │  │
│  │  │         CAPA DE AUTENTICACIÓN               │    │  │
│  │  │  • JWT Token validation                     │    │  │
│  │  │  • Role-based access control                │    │  │
│  │  │  • Session management                       │    │  │
│  │  └─────────────────────────────────────────────┘    │  │
│  │                                                       │  │
│  │  ┌─────────────────────────────────────────────┐    │  │
│  │  │          LÓGICA DE NEGOCIO                  │    │  │
│  │  │  • Registro de usuarios                     │    │  │
│  │  │  • Gestión de ventas                        │    │  │
│  │  │  • Generación de reportes                   │    │  │
│  │  │  • Validación de datos                      │    │  │
│  │  └─────────────────────────────────────────────┘    │  │
│  │                                                       │  │
│  │  ┌─────────────────────────────────────────────┐    │  │
│  │  │          CAPA DE DATOS (PDO)                │    │  │
│  │  │  • Connection pooling                       │    │  │
│  │  │  • Prepared statements                      │    │  │
│  │  │  • Transaction management                   │    │  │
│  │  └──────────────────┬──────────────────────────┘    │  │
│  └─────────────────────┼──────────────────────────────┘  │
│                        │                                   │
│                        ▼                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │                  BASE DE DATOS MySQL                  │  │
│  │                                                       │  │
│  │  • Usuarios (asesores + admin)                       │  │
│  │  • Ventas (con fotos de facturas)                    │  │
│  │  • Productos JLC                                     │  │
│  │  • Sesiones                                          │  │
│  │  • Auditoría                                         │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │            ALMACENAMIENTO DE ARCHIVOS                 │  │
│  │  /uploads/facturas/                                  │  │
│  │  • Fotos de facturas (JPG, PNG, PDF)                │  │
│  │  • Máximo 5MB por archivo                           │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                           │
                           │ GitHub Actions
                           │ (Deploy automático)
                           ▼
                    ┌──────────────┐
                    │    GITHUB    │
                    │  Repository  │
                    └──────────────┘

```

### 2.2 Flujo de Autenticación

```
1. Usuario ingresa cédula + contraseña
   ↓
2. Frontend envía credenciales a /api/auth/login.php
   ↓
3. Backend valida en BD (password_verify)
   ↓
4. Si válido: genera JWT con datos del usuario
   ↓
5. Frontend almacena JWT en localStorage
   ↓
6. Todas las peticiones incluyen: Authorization: Bearer {token}
   ↓
7. Middleware valida JWT en cada request
   ↓
8. Si token válido: permite acceso
   Si token inválido: retorna 401 Unauthorized

```

### 2.3 Estructura de Carpetas

```
jlc-ventas/
│
├── src/                          # Frontend Astro
│   ├── pages/                    # Páginas del sitio
│   ├── components/               # Componentes reutilizables
│   ├── layouts/                  # Plantillas de diseño
│   └── styles/                   # CSS global
│
├── api/                          # Backend PHP
│   ├── config/                   # Configuraciones
│   │   ├── database.php          # Conexión MySQL
│   │   ├── jwt.php               # Manejo de tokens
│   │   └── constants.php         # Constantes del sistema
│   │
│   ├── middleware/               # Capas de seguridad
│   │   ├── auth.php              # Verificar autenticación
│   │   └── admin.php             # Verificar rol admin
│   │
│   ├── controllers/              # Lógica de negocio
│   │   ├── AuthController.php
│   │   ├── UsuarioController.php
│   │   └── VentaController.php
│   │
│   ├── models/                   # Modelos de datos
│   │   ├── Usuario.php
│   │   └── Venta.php
│   │
│   ├── utils/                    # Utilidades
│   │   ├── Validator.php         # Validación de datos
│   │   ├── FileUpload.php        # Manejo de archivos
│   │   └── ExcelExport.php       # Exportación de reportes
│   │
│   └── routes/                   # Endpoints API
│       ├── auth.php              # /api/auth/*
│       ├── usuarios.php          # /api/usuarios/*
│       ├── ventas.php            # /api/ventas/*
│       └── reportes.php          # /api/reportes/*
│
├── database/                     # Scripts de base de datos
│   ├── schema.sql                # Estructura completa
│   ├── migrations/               # Migraciones versionadas
│   └── seeds/                    # Datos iniciales
│
├── uploads/                      # Archivos subidos
│   └── facturas/                 # Fotos de facturas
│
└── .github/workflows/            # Automatización
    └── deploy.yml                # Deploy a Hostinger

```

---

## 3. BASE DE DATOS

### 3.1 Modelo de Datos

**Tabla: usuarios**

```
Propósito: Almacenar información completa de asesores y administradores

Campos principales:
├── Autenticación
│   ├── cedula (VARCHAR 20, UNIQUE) - Login del usuario
│   ├── password (VARCHAR 255) - Hash bcrypt
│   └── rol (ENUM: 'asesor', 'administrador')
│
├── Información Personal
│   ├── nombre, apellido (VARCHAR 100)
│   ├── tipo_documento (ENUM: CC, CE, TI, Pasaporte)
│   ├── numero_documento (VARCHAR 20)
│   ├── fecha_nacimiento (DATE)
│   ├── ciudad_residencia (VARCHAR 100)
│   ├── departamento (VARCHAR 100)
│   ├── whatsapp (VARCHAR 20)
│   ├── telefono (VARCHAR 20)
│   └── correo (VARCHAR 150, UNIQUE)
│
├── Información de Distribuidor
│   ├── nombre_distribuidor (VARCHAR 200)
│   ├── ciudad_punto_venta (VARCHAR 100)
│   ├── direccion_punto_venta (VARCHAR 255, opcional)
│   ├── cargo (VARCHAR 100)
│   └── antiguedad_meses (INT)
│
├── Información Financiera
│   ├── metodo_pago_preferido (ENUM: Nequi, Daviplata, etc)
│   └── llave_breb (VARCHAR 100)
│
└── Políticas y Permisos
    ├── acepta_tratamiento_datos (BOOLEAN)
    ├── acepta_contacto_comercial (BOOLEAN)
    └── declara_info_verdadera (BOOLEAN)

Índices:
- PRIMARY KEY (id)
- UNIQUE (cedula)
- UNIQUE (correo)
- INDEX (rol, activo) - Para filtros rápidos

```

**Tabla: productos_jlc**

```
Propósito: Catálogo de productos JLC

Campos:
├── id (INT, AUTO_INCREMENT)
├── modelo (VARCHAR 100, UNIQUE)
├── descripcion (VARCHAR 255)
└── activo (BOOLEAN)

Relación: Referenciada por ventas.producto_id

```

**Tabla: ventas**

```
Propósito: Registro de ventas realizadas por asesores

Campos:
├── id (INT, AUTO_INCREMENT)
├── asesor_id (INT) → FK a usuarios.id
├── numero_factura (VARCHAR 50)
├── foto_factura (VARCHAR 255) - Path relativo
├── producto_id (INT) → FK a productos_jlc.id
├── numero_serie (VARCHAR 100)
├── fecha_venta (DATE)
└── created_at, updated_at (TIMESTAMP)

Índices:
- INDEX (asesor_id, fecha_venta) - Consultas por asesor
- INDEX (fecha_venta DESC) - Ordenamiento cronológico
- INDEX (numero_factura) - Búsqueda por factura

Restricciones:
- ON DELETE CASCADE en asesor_id (si se borra usuario, se borran sus ventas)

```

**Tabla: sesiones**

```
Propósito: Tracking de sesiones activas (opcional)

Campos:
├── id (INT)
├── usuario_id (INT) → FK a usuarios.id
├── token_hash (VARCHAR 64, UNIQUE)
├── expires_at (DATETIME)
└── revoked (BOOLEAN) - Para invalidar tokens

Uso: Blacklist de tokens JWT revocados

```

**Tabla: auditoria**

```
Propósito: Log de acciones importantes del sistema

Campos:
├── usuario_id (INT)
├── accion (VARCHAR 100) - Ej: "crear_venta", "editar_perfil"
├── tabla_afectada (VARCHAR 50)
├── registro_id (INT)
├── datos_anteriores (TEXT) - JSON
├── datos_nuevos (TEXT) - JSON
├── ip_address (VARCHAR 45)
└── created_at (TIMESTAMP)

Casos de uso:
- Rastrear modificaciones de datos
- Investigar problemas
- Cumplimiento normativo

```

### 3.2 Optimizaciones de Base de Datos

**Índices Críticos:**

```sql
-- Búsquedas frecuentes
CREATE INDEX idx_ventas_asesor_fecha ON ventas(asesor_id, fecha_venta);
CREATE INDEX idx_usuarios_rol_activo ON usuarios(rol, activo);
CREATE INDEX idx_ventas_fecha_desc ON ventas(fecha_venta DESC);

-- Impacto: Reduce queries de 2s → 50ms

```

**Connection Pooling:**

```
Configuración PDO:
- ATTR_PERSISTENT = true
- Reutiliza conexiones existentes
- Reduce overhead de conexión en 90%
- Soporta 10-15 usuarios concurrentes por conexión

```

**Prepared Statements:**

```
Todas las queries usan PDO prepared statements:
- Previene SQL injection 100%
- Mejora performance (query plan caching)
- Validación automática de tipos

```

---

## 4. FUNCIONALIDADES DEL SISTEMA

### 4.1 Módulo de Registro de Usuarios

**Página:** `/registro`

**Flujo:**

1. Usuario completa formulario de 4 secciones:
    - Información Personal (12 campos)
    - Información de Distribuidor (5 campos)
    - Información Financiera (2 campos)
    - Aceptación de Políticas (3 checkboxes)
2. Frontend valida datos en tiempo real:
    - Cédula: solo números, 6-10 dígitos
    - Email: formato válido
    - Whatsapp: formato colombiano (+57)
    - Fecha nacimiento: mayor de 18 años
    - Campos obligatorios completados
3. Backend recibe datos en `/api/auth/register.php`:
    - Re-valida todos los campos
    - Verifica que cédula no exista
    - Verifica que email no exista
    - Hash del password con `password_hash()` bcrypt
    - Inserta en tabla `usuarios`
    - Retorna JWT token
4. Usuario es redirigido automáticamente a su dashboard

**Validaciones Específicas:**

- **Cédula:** Única en el sistema, sirve como username
- **Contraseña:** Mínimo 8 caracteres, al menos 1 número
- **Llave BRE-B:** Advertencia visual de que debe coincidir con nombre
- **Políticas:** Todas deben estar aceptadas para continuar

### 4.2 Módulo de Autenticación

**Login (Página: `/login`)**

Campos:

- Cédula de ciudadanía
- Contraseña
- [Checkbox] Recordarme

Proceso:

1. Usuario ingresa credenciales
2. POST a `/api/auth/login.php`
3. Backend valida contra tabla `usuarios`
4. Si válido: genera JWT (válido 8 horas)
5. Redirige según rol:
    - Asesor → `/dashboard/asesor`
    - Admin → `/dashboard/admin`

**Logout**

- DELETE a `/api/auth/logout.php`
- Invalida token en tabla `sesiones`
- Limpia localStorage del frontend
- Redirige a `/login`

### 4.3 Dashboard de Asesor

**Página:** `/dashboard/asesor`

**Secciones:**

**A. Header Personal**

```
┌────────────────────────────────────────┐
│  Bienvenido, Juan Pérez                │
│  Distribuidor: JLC Pasto               │
│  Ventas este mes: 24                   │
└────────────────────────────────────────┘

```

**B. Registro Rápido de Venta**

```
[Botón destacado: + Nueva Venta]
→ Redirige a /ventas/nueva

```

**C. Mis Ventas Recientes**

```
Tabla con columnas:
- Fecha
- N° Factura
- Producto
- N° Serie
- Estado
- Acciones [Ver foto]

Paginación: 20 registros por página
Filtros: Por fecha, producto

```

**D. Estadísticas Personales**

```
┌─────────────┬─────────────┬─────────────┐
│ Esta semana │  Este mes   │   Total     │
│     5       │     24      │    187      │
└─────────────┴─────────────┴─────────────┘

```

### 4.4 Registro de Nueva Venta

**Página:** `/ventas/nueva`

**Formulario:**

```
┌─────────────────────────────────────────┐
│  Registrar Nueva Venta                  │
├─────────────────────────────────────────┤
│                                         │
│  N° de Factura: [_____________]         │
│                                         │
│  Foto de Factura:                       │
│  [Arrastrar o Click para subir]        │
│  Formatos: JPG, PNG, PDF (Max 5MB)     │
│                                         │
│  Producto JLC: [▼ Seleccionar]         │
│  ├─ JLC-2024-A1                        │
│  ├─ JLC-2024-A2                        │
│  └─ ... (lista completa)               │
│                                         │
│  N° de Serie: [_____________]           │
│                                         │
│  Fecha de Venta: [📅 DD/MM/YYYY]       │
│                                         │
│  [Cancelar]  [Registrar Venta]         │
└─────────────────────────────────────────┘

```

**Proceso:**

1. Usuario completa formulario
2. Valida que todos los campos estén llenos
3. Valida formato de imagen/PDF
4. POST multipart/form-data a `/api/ventas/crear.php`
5. Backend:
    - Valida sesión del usuario
    - Valida datos de la venta
    - Sube imagen a `/uploads/facturas/{asesor_id}/{timestamp}_{filename}`
    - Inserta registro en tabla `ventas`
    - Retorna confirmación
6. Frontend muestra mensaje de éxito
7. Redirige a lista de ventas

**Validaciones:**

- N° Factura: único por asesor (no puede repetirse)
- Foto: Max 5MB, formatos JPG/PNG/PDF
- Producto: Debe existir en catálogo
- N° Serie: Formato alfanumérico
- Fecha: No puede ser futura

### 4.5 Dashboard de Administrador

**Página:** `/dashboard/admin`

**Secciones:**

**A. Resumen General**

```
┌──────────────────────────────────────────────────────┐
│  Estadísticas del Sistema                            │
├──────────────┬──────────────┬──────────────────────┤
│ Total        │ Ventas       │ Ventas              │
│ Asesores     │ Hoy          │ Este Mes            │
│    127       │    45        │     1,234           │
└──────────────┴──────────────┴──────────────────────┘

```

**B. Panel de Filtros**

```
┌─────────────────────────────────────────┐
│  Filtrar Reportes                       │
├─────────────────────────────────────────┤
│  Rango de Fechas:                      │
│  Desde: [📅] Hasta: [📅]               │
│                                         │
│  Asesor: [▼ Todos / Seleccionar]       │
│                                         │
│  Distribuidor: [▼ Todos / Filtrar]     │
│                                         │
│  Ciudad: [▼ Todas / Filtrar]           │
│                                         │
│  Producto: [▼ Todos / Filtrar]         │
│                                         │
│  [Limpiar]  [Aplicar Filtros]          │
│                                         │
│  [📥 Descargar Excel]                   │
└─────────────────────────────────────────┘

```

**C. Tabla de Ventas**

```
Vista completa de todas las ventas con columnas:
- ID Venta
- Fecha
- Asesor (nombre completo)
- Cédula
- Distribuidor
- Ciudad
- N° Factura
- Producto
- N° Serie
- Acciones [Ver detalle] [Ver foto]

Paginación: 50 registros por página
Ordenamiento: Por fecha DESC (más recientes primero)

```

**D. Gestión de Usuarios**

```
Acceso a: /admin/usuarios

Lista de todos los asesores con:
- Datos personales completos
- Estado (activo/inactivo)
- Total de ventas
- Última actividad
- Acciones: [Ver perfil] [Editar] [Desactivar]

```

### 4.6 Generación de Reportes Excel

**Endpoint:** `GET /api/reportes/excel.php`

**Parámetros de Query:**

```
?fecha_desde=2024-01-01
&fecha_hasta=2024-12-31
&asesor_id=123
&distribuidor=JLC+Pasto
&ciudad=Pasto
&producto_id=5

```

**Proceso:**

1. Admin selecciona filtros y presiona "Descargar Excel"
2. Frontend construye URL con parámetros
3. Backend recibe request
4. Valida que usuario sea admin
5. Construye query SQL con filtros aplicados
6. Utiliza biblioteca PHPSpreadsheet
7. Genera archivo Excel con columnas especificadas:

**Columnas del Excel:**

```
A: ID Asesor (Cédula)
B: Nombre
C: Apellido
D: Ciudad Residencia
E: WhatsApp
F: Correo
G: Distribuidor
H: Ciudad Punto Venta
I: Llave BRE-B
J: N° Factura
K: Producto
L: N° Serie
M: Fecha Venta

```

**Formato del archivo:**

- Nombre: `ventas_jlc_{fecha_desde}_{fecha_hasta}.xlsx`
- Headers con formato (negrita, color de fondo)
- Filtros Excel habilitados
- Autoajuste de columnas
- Total de registros al final
1. Retorna archivo para descarga
2. Navegador descarga automáticamente

---

## 5. SEGURIDAD

### 5.1 Autenticación y Autorización

**JWT (JSON Web Tokens)**

Estructura del token:

```json
{
  "iss": "jlc-ventas",
  "aud": "jlc-users",
  "iat": 1703001600,
  "exp": 1703030400,
  "data": {
    "usuario_id": 123,
    "cedula": "1234567890",
    "rol": "asesor",
    "nombre": "Juan Pérez"
  }
}

```

Características:

- Firmado con HS256
- Válido por 8 horas
- Renovable automáticamente
- Almacenado en localStorage (frontend)
- Enviado en header: `Authorization: Bearer {token}`

**Control de Acceso Basado en Roles (RBAC)**

Roles:

1. **Asesor:**
    - Puede ver solo sus propias ventas
    - Puede registrar nuevas ventas
    - Puede editar su perfil
    - NO puede ver datos de otros asesores
2. **Administrador:**
    - Puede ver todas las ventas
    - Puede ver todos los asesores
    - Puede generar reportes completos
    - Puede gestionar usuarios
    - Puede ver auditoría del sistema

Middleware de verificación:

```
Para rutas de asesor: requireAuth()
Para rutas de admin: requireAdmin()

```

### 5.2 Protección de Datos

**Contraseñas:**

- Hash: bcrypt (cost factor 10)
- Función: `password_hash($password, PASSWORD_BCRYPT)`
- Verificación: `password_verify($input, $hash)`
- NUNCA se almacenan en texto plano
- NUNCA se transmiten en logs

**SQL Injection:**

- 100% prevenido con PDO prepared statements
- Ejemplo:
    
    ```php
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE cedula = :cedula");$stmt->execute(['cedula' => $input]);
    
    ```
    
- Nunca se concatenan strings en queries

**XSS (Cross-Site Scripting):**

- Todos los outputs sanitizados con `htmlspecialchars()`
- Headers CSP (Content Security Policy)
- Validación de inputs en frontend Y backend

**CSRF (Cross-Site Request Forgery):**

- Tokens CSRF en formularios
- Verificación de origen de requests
- SameSite cookies

**File Upload Security:**

- Validación de extensión (whitelist)
- Validación de MIME type real
- Renombrado de archivos
- Almacenamiento fuera de webroot cuando posible
- Límite de tamaño (5MB)
- Protección con .htaccess en carpeta uploads

### 5.3 Validación de Datos

**Frontend (JavaScript):**

- Validación en tiempo real (UX)
- Mensajes de error claros
- Prevención de envíos inválidos

**Backend (PHP):**

- RE-validación de TODOS los datos
- No confía en validación de frontend
- Sanitización de inputs
- Type checking estricto

Ejemplo de validación de cédula:

```php
function validarCedula($cedula) {
    // Solo números
    if (!ctype_digit($cedula)) return false;

    // Longitud 6-10
    $len = strlen($cedula);
    if ($len < 6 || $len > 10) return false;

    // No existe ya en BD
    return !existeCedulaEnBD($cedula);
}

```

### 5.4 Auditoría y Logs

**Eventos Registrados:**

- Login exitoso/fallido
- Creación de usuarios
- Registro de ventas
- Modificación de datos
- Descargas de reportes
- Cambios de rol

**Información Capturada:**

- Usuario que realizó la acción
- Timestamp
- Tipo de acción
- Datos antes/después (JSON)
- IP del cliente
- User Agent

**Propósito:**

- Debugging de problemas
- Investigación de incidentes
- Cumplimiento legal (GDPR, LOPD)
- Análisis de uso

---

## 6. DESPLIEGUE Y OPERACIONES

### 6.1 Proceso de Deploy Automático

**Trigger:** Push a rama `main` en GitHub

**Pasos:**

1. GitHub Actions detecta push
2. Ejecuta workflow definido en `.github/workflows/deploy.yml`
3. Instala dependencias Node.js
4. Compila Astro (`npm run build`)
5. Deploy frontend vía FTP a `/public_html/`
6. Deploy backend PHP vía FTP a `/api/`
7. Deploy carpeta uploads vía FTP a `/uploads/`
8. Notifica resultado (éxito/error)

**Tiempo estimado:** 2-3 minutos

**Rollback:**

- Revertir commit en GitHub
- Push automático redespliega versión anterior

### 6.2 Configuración de Hostinger

**Requerimientos:**

- PHP 8.0 o superior
- MySQL 8.0
- Extensiones PHP: PDO, pdo_mysql, gd, mbstring, json
- mod_rewrite habilitado
- HTTPS habilitado (certificado SSL incluido)

**Estructura en servidor:**

```
/home/usuario/
├── public_html/          # Frontend (acceso público)
│   ├── index.html
│   ├── assets/
│   └── ...
├── api/                  # Backend PHP
│   ├── config/
│   ├── controllers/
│   └── ...
├── uploads/              # Archivos subidos
│   └── facturas/
└── .env                  # Variables de entorno (NO en public_html)

```

**Permisos:**

- public_html/: 755
- api/: 755 (no directamente accesible vía web, usa .htaccess)
- uploads/: 755 con .htaccess para proteger acceso directo
- .env: 600 (solo lectura para owner)

### 6.3 Variables de Entorno

Archivo: `.env` (NO versionar en Git)

```
DB_HOST=localhost
DB_NAME=nombre_base_datos
DB_USER=usuario_mysql
DB_PASS=contraseña_segura

JWT_SECRET=clave_aleatoria_muy_larga_y_segura_cambiar_en_produccion

APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

```

**Gestión:**

- Valores diferentes para desarrollo/producción
- Actualizar via FTP/SSH cuando sea necesario
- Nunca exponer en código fuente

### 

<function_calls>
<invoke name="artifacts">
<parameter name="command">update</parameter>
<parameter name="id">jlc-php-structure</parameter>
<parameter name="old_str">### </parameter>
<parameter name="new_str">### 6.4 Monitoreo y Mantenimiento

**Métricas a Vigilar:**

- Tiempo de respuesta de API (objetivo: <500ms)
- Tasa de errores (objetivo: <0.5%)
- Uso de disco (uploads)
- Conexiones activas a BD
- Intentos de login fallidos

**Herramientas:**

- Logs de Hostinger (cPanel)
- Google Analytics (frontend)
- Logs personalizados PHP (error_log)

**Tareas de Mantenimiento:**

```
Diarias:
- Revisar logs de errores
- Backup automático de BD (configurar en Hostinger)

Semanales:
- Revisar métricas de uso
- Verificar espacio en disco

Mensuales:
- Limpiar archivos antiguos
- Actualizar dependencias PHP/Composer
- Revisar auditoría de seguridad

```

**Plan de Backup:**

- Base de datos: Backup diario automático (Hostinger)
- Archivos uploads: Backup semanal
- Código: Versionado en GitHub (backup implícito)
- Retención: 30 días