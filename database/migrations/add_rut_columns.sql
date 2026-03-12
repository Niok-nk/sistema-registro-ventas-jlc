ALTER TABLE `usuarios` ADD COLUMN `rut` VARCHAR(30) NULL AFTER `certificado`;
ALTER TABLE `usuarios` ADD COLUMN `certificado_rut` VARCHAR(255) NULL AFTER `rut`;
