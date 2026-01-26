-- Esquema de Base de Datos JLC Ventas
-- Versión: 1.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "-05:00";

-- --------------------------------------------------------

-- Tabla: usuarios
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('asesor','administrador','auditor') NOT NULL DEFAULT 'asesor',
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `tipo_documento` enum('CC','CE','TI','Pasaporte') NOT NULL DEFAULT 'CC',
  `numero_documento` varchar(20) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `ciudad_residencia` varchar(100) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `whatsapp` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(150) NOT NULL,
  `nombre_distribuidor` varchar(200) NOT NULL,
  `ciudad_punto_venta` varchar(100) NOT NULL,
  `direccion_punto_venta` varchar(255) DEFAULT NULL,
  `cargo` varchar(100) NOT NULL,
  `antiguedad_meses` int(11) DEFAULT 0,
  `llave_breb` varchar(100) DEFAULT NULL,
  `acepta_tratamiento_datos` tinyint(1) NOT NULL DEFAULT 0,
  `acepta_contacto_comercial` tinyint(1) NOT NULL DEFAULT 0,
  `declara_info_verdadera` tinyint(1) NOT NULL DEFAULT 0,
  `declara_naturaleza_comercial` tinyint(1) NOT NULL DEFAULT 0,
  `reconoce_no_salario` tinyint(1) NOT NULL DEFAULT 0,
  `declara_no_subordinacion` tinyint(1) NOT NULL DEFAULT 0,
  `declara_relacion_autonoma` tinyint(1) NOT NULL DEFAULT 0,
  `acepta_liberalidades` tinyint(1) NOT NULL DEFAULT 0,
  `asume_obligaciones_tributarias` tinyint(1) NOT NULL DEFAULT 0,
  `declara_no_contrato` tinyint(1) NOT NULL DEFAULT 0,
  `acepta_terminos_programa` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 0,
  `estado_aprobacion` ENUM('pendiente', 'aprobado', 'rechazado') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  UNIQUE KEY `correo` (`correo`),
  KEY `idx_rol_activo` (`rol`,`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Tabla: productos_jlc
CREATE TABLE `productos_jlc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modelo` varchar(100) NOT NULL,
  `codigo` varchar(50) NULL,
  `descripcion` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modelo` (`modelo`),
  KEY `idx_productos_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Tabla: ventas
CREATE TABLE `ventas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asesor_id` int(11) NOT NULL,
  `numero_factura` varchar(50) NOT NULL,
  `foto_factura` varchar(255) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `numero_serie` varchar(100) NOT NULL,
  `fecha_venta` date NOT NULL,
  `estado` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_asesor_fecha` (`asesor_id`,`fecha_venta`),
  KEY `idx_fecha_desc` (`fecha_venta` DESC),
  KEY `idx_numero_factura` (`numero_factura`),
  CONSTRAINT `fk_ventas_usuario` FOREIGN KEY (`asesor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ventas_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos_jlc` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Tabla: sesiones (Revocación JWT)
CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Tabla: auditoria
CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `datos_anteriores` text DEFAULT NULL,
  `datos_nuevos` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
