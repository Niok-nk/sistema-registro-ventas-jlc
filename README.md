# Sistema de Registro de Ventas JLC

Sistema web estático (Astro) con backend ligero en PHP para la gestión de ventas de asesores de distribución JLC.

## 🚀 Stack Tecnológico

- **Frontend:** Astro 5.0 (Static Site Generation), JavaScript Vanilla, CSS.
- **Backend:** PHP 8.x (Nativo), PDO, JWT.
- **Base de Datos:** Híbrida (SQLite en local / MySQL en producción).
- **Infraestructura:** Hostinger (Deploy vía GitHub Actions).

## 🛠️ Configuración Local

### Prerrequisitos
- Node.js v22.21.1+
- PHP 8.0+
- Extensiones PHP: `pdo`, `pdo_mysql`, `pdo_sqlite`

### Instalación

1. **Clonar el repositorio:**
   ```bash
   git clone <url-repo>
   cd <nombre-repo>
   ```

2. **Backend (PHP):**
   - Copiar `.env.example` a `.env`:
     ```bash
     cp .env.example .env
     ```
   - **Opción A (Recomendada): SQLite (Sin instalación)**
     - Asegúrate de que `DB_CONNECTION=sqlite` en tu `.env`.
     - Inicializa la base de datos:
       ```bash
       php -f api/init_sqlite.php
       ```
   - **Opción B: MySQL**
     - Configura `DB_CONNECTION=mysql` y tus credenciales en `.env`.
     - Importa `database/schema.sql`.

3. **Frontend (Astro):**
   ```bash
   npm install
   ```

### Ejecución

1. **Frontend Dev Server:**
   ```bash
   npm run dev
   ```
   Disponible en: `http://localhost:4321`

2. **Backend Dev Server (PHP):**
   ```bash
   # En una terminal separada
   php -S localhost:8000 -t api
   ```
   Disponible en: `http://localhost:8000`

## 📁 Estructura del Proyecto

```
/
├── src/              # Frontend (Astro)
├── api/              # Backend (PHP Native)
├── database/         # SQL Scripts
├── uploads/          # Almacenamiento seguro de facturas
└── public/           # Assets estáticos
```

## 🔐 Seguridad

- **Autenticación:** JWT (JSON Web Tokens).
- **Base de Datos:** PDO Prepared Statements.
- **Uploads:** Validación de MIME type y almacenamiento restringido.

## 🚀 Despliegue a Hostinger

Este proyecto utiliza GitHub Actions para desplegar automáticamente a Hostinger vía FTP.

### Configuración Inicial

#### 1. Crear Base de Datos MySQL en Hostinger

1. Accede al hPanel de Hostinger
2. Ve a **Bases de Datos** → **Administrar**
3. Crea una nueva base de datos MySQL
4. Anota: nombre de BD, usuario, contraseña y host

#### 2. Configurar Secretos en GitHub

Ve a tu repositorio → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

Crea los siguientes secretos:

**Credenciales FTP:**
- `FTP_SERVER`: Dirección del servidor FTP de Hostinger (ej: `ftp.tudominio.com`)
- `FTP_USERNAME`: Tu usuario FTP
- `FTP_PASSWORD`: Tu contraseña FTP

**Variables de Base de Datos:**
- `DB_HOST`: Host de MySQL (ej: `localhost` o el que te proporcione Hostinger)
- `DB_NAME`: Nombre de tu base de datos
- `DB_USER`: Usuario de la base de datos
- `DB_PASS`: Contraseña de la base de datos

**Variables de Aplicación:**
- `APP_URL`: URL completa de tu app (ej: `https://ventas.ejemplo.com`)
- `API_URL`: URL de tu API (ej: `https://ventas.ejemplo.com/api`)
- `PUBLIC_APP_URL`: Mismo valor que APP_URL (para el build de Astro)
- `PUBLIC_API_URL`: Mismo valor que API_URL (para el build de Astro)

**Seguridad:**
- `JWT_SECRET`: Clave secreta para JWT (genera una aleatoria de 64 caracteres)
- `JWT_EXPIRATION`: `28800` (8 horas en segundos)

**Configuración:**
- `UPLOAD_MAX_SIZE`: `5242880` (5MB en bytes)

#### 3. Importar Schema de Base de Datos

Después del primer despliegue, importa el schema SQL:

```bash
# Conéctate a tu servidor MySQL de Hostinger (via phpMyAdmin o CLI)
# Ejecuta el archivo database/schema.sql
```

#### 4. Ejecutar Despliegue

```bash
# Asegúrate de estar en la rama deploy
git checkout deploy

# Haz merge de tus cambios desde main
git merge main

# Push para activar el despliegue automático
git push origin deploy
```

El workflow se ejecutará automáticamente y desplegará tu aplicación a `public_html/ventas/` en Hostinger.

### Verificar Despliegue

1. Monitorea la ejecución en GitHub: **Actions** → **Desplegar a Hostinger**
2. Una vez completado, verifica:
   - Frontend: `https://ventas.ejemplo.com`
   - API: `https://ventas.ejemplo.com/api/test_db.php` (debería mostrar conexión exitosa)

### Permisos Post-Despliegue

Si tienes problemas con uploads, verifica permisos vía SSH o File Manager:

```bash
chmod 755 uploads/
chmod 644 uploads/.htaccess
```

### Troubleshooting

**Error de conexión a BD:**
- Verifica que los secretos `DB_*` estén correctos
- Confirma que la BD esté creada en Hostinger
- Revisa que el archivo `.env` se haya creado correctamente en el servidor

**Archivos no se suben:**
- Revisa la configuración `server-dir` en `.github/workflows/deploy.yml`
- Verifica credenciales FTP
- Confirma que el directorio exista en Hostinger

**Error 500 en API:**
- Revisa logs de PHP en hPanel
- Verifica permisos de archivos (644 para archivos, 755 para directorios)
- Confirma que las extensiones PHP requeridas estén activas

---
**Agente Supervisor:** `AstroPHP-Guardian`
**Objetivo de Arquitectura:** `objetivo.md`

