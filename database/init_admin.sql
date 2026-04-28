-- Script de Inicialización - Crear Usuario Administrador
-- Sistema JLC - Registro de Ventas
-- Este script crea el primer usuario administrador

-- ========================================
-- USUARIO ADMINISTRADOR
-- ========================================
-- Credenciales:
-- Cédula: admin
-- Contraseña: Admin123! (cambiar después del primer login)
-- ========================================

INSERT INTO `usuarios` (
    `cedula`,
    `password`,
    `rol`,
    `nombre`,
    `apellido`,
    `tipo_documento`,
    `numero_documento`,
    `fecha_nacimiento`,
    `ciudad_residencia`,
    `departamento`,
    `whatsapp`,
    `correo`,
    `nombre_distribuidor`,
    `ciudad_punto_venta`,
    `cargo`,
    `acepta_tratamiento_datos`,
    `acepta_contacto_comercial`,
    `declara_info_verdadera`,
    `activo`
) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Password: Admin123!
    'administrador',
    'Administrador',
    'Sistema',
    'CC',
    '00000000',
    '1990-01-01',
    'Bogotá',
    'Cundinamarca',
    '3000000000',
    'admin@jlc.com',
    'JLC Distribución Colombia',
    'Bogotá',
    'Administrador de Sistema',
    1,
    1,
    1,
    1
);

-- ========================================
-- PRODUCTOS JLC DE EJEMPLO
-- ========================================
-- Algunos productos para poder empezar a registrar ventas

INSERT INTO `productos_jlc` (`modelo`, `descripcion`, `activo`) VALUES
('JLC-AIR-001', 'Aire Acondicionado Split 12000 BTU', 1),
('JLC-AIR-002', 'Aire Acondicionado Split 18000 BTU', 1),
('JLC-AIR-003', 'Aire Acondicionado Split 24000 BTU', 1),
('JLC-REF-001', 'Refrigerador No Frost 350L', 1),
('JLC-REF-002', 'Refrigerador No Frost 450L', 1),
('JLC-LAV-001', 'Lavadora Automática 18kg', 1),
('JLC-LAV-002', 'Lavadora Automática 24kg', 1);

-- ========================================
-- VERIFICACIÓN
-- ========================================
-- Verifica que el usuario fue creado correctamente

SELECT 
    id, 
    cedula, 
    nombre, 
    apellido, 
    rol, 
    correo, 
    activo,
    created_at 
FROM `usuarios` 
WHERE cedula = 'admin';

SELECT COUNT(*) as total_productos FROM `productos_jlc`;
