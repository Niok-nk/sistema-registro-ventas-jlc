<?php
/**
 * Inserta un registro en la tabla auditoria.
 * 
 * @param PDO    $conn           Conexión activa
 * @param int|null $usuario_id   ID del usuario que realiza la acción
 * @param string $accion         Nombre de la acción (ej: "login_exitoso")
 * @param string|null $tabla     Tabla afectada (ej: "ventas")
 * @param int|null $registro_id  ID del registro afectado
 * @param mixed  $datos_ant      Datos anteriores (array o string)
 * @param mixed  $datos_nuevos   Datos nuevos (array o string)
 */
function logAudit(
    PDO $conn,
    ?int $usuario_id,
    string $accion,
    ?string $tabla = null,
    ?int $registro_id = null,
    $datos_ant = null,
    $datos_nuevos = null
): void {
    try {
        // Usar REMOTE_ADDR directamente — HTTP_X_FORWARDED_FOR puede ser falsificado
        // Si en el futuro se usa un proxy/CDN conocido, validar la IP del proxy primero
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $stmt = $conn->prepare(
            "INSERT INTO auditoria
                (usuario_id, accion, tabla_afectada, registro_id, datos_anteriores, datos_nuevos, ip_address)
             VALUES
                (:uid, :accion, :tabla, :rid, :ant, :new, :ip)"
        );
        $stmt->execute([
            ':uid'   => $usuario_id,
            ':accion'=> $accion,
            ':tabla' => $tabla,
            ':rid'   => $registro_id,
            ':ant'   => $datos_ant   !== null ? json_encode($datos_ant,   JSON_UNESCAPED_UNICODE) : null,
            ':new'   => $datos_nuevos !== null ? json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE) : null,
            ':ip'    => $ip,
        ]);
    } catch (Exception $e) {
        // No interrumpir el flujo principal si el log falla
        error_log("Audit log error: " . $e->getMessage());
    }
}
