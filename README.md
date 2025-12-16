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

---
**Agente Supervisor:** `AstroPHP-Guardian`
**Objetivo de Arquitectura:** `objetivo.md`
