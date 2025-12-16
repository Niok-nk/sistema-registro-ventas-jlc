<?php
// Constantes Generales del Sistema
define('APP_NAME', 'JLC Ventas');
define('APP_VERSION', '1.0.0');

// Configuración de Zona Horaria
date_default_timezone_set('America/Bogota');

// Directorios
define('ROOT_PATH', dirname(__DIR__, 2)); // Subir 2 niveles desde api/config
define('UPLOAD_PATH', ROOT_PATH . '/uploads/facturas');

// Límites con validación
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// Seguridad
define('JWT_EXPIRATION_TIME', 8 * 60 * 60); // 8 horas en segundos
