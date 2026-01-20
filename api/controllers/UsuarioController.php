<?php
require_once __DIR__ . '/../config/database.php';

class UsuarioController {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function registrar($data) {
        // 1. Validar datos de entrada
        $validation = $this->validarDatosRegistro($data);
        if ($validation !== true) {
            return ['status' => 400, 'message' => $validation];
        }

        try {
            // 2. Verificar unicidad de cédula y correo
            $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE numero_documento = :numero_documento OR correo = :correo");
            $stmt->execute([
                ':numero_documento' => $data['numero_documento'],
                ':correo' => $data['correo']
            ]);
            if ($stmt->fetch()) {
                return ['status' => 409, 'message' => 'El número de documento o el correo electrónico ya están registrados.'];
            }

            // 3. Hash de la contraseña
            $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);

            // 4. Insertar usuario en la base de datos (con 8 declaraciones legales)
            $sql = "INSERT INTO usuarios (
                        nombre, apellido, tipo_documento, numero_documento, cedula, fecha_nacimiento,
                        ciudad_residencia, departamento, whatsapp, telefono, correo,
                        nombre_distribuidor, ciudad_punto_venta, direccion_punto_venta, cargo,
                        antiguedad_meses, llave_breb,
                        password, acepta_tratamiento_datos, acepta_contacto_comercial,
                        declara_info_verdadera,
                        declara_naturaleza_comercial, reconoce_no_salario,
                        declara_no_subordinacion, declara_relacion_autonoma,
                        acepta_liberalidades, asume_obligaciones_tributarias,
                        declara_no_contrato, acepta_terminos_programa,
                        rol, activo, estado_aprobacion
                    ) VALUES (
                        :nombre, :apellido, :tipo_documento, :numero_documento, :cedula, :fecha_nacimiento,
                        :ciudad_residencia, :departamento, :whatsapp, :telefono, :correo,
                        :nombre_distribuidor, :ciudad_punto_venta, :direccion_punto_venta, :cargo,
                        :antiguedad_meses, :llave_breb,
                        :password, :acepta_datos, :acepta_contacto, :declara_verdad,
                        :declara_naturaleza, :reconoce_salario,
                        :declara_subordinacion, :declara_relacion,
                        :acepta_liberal, :asume_tributarias,
                        :declara_contrato, :acepta_terminos,
                        'asesor', 0, 'pendiente'
                    )";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':nombre' => $data['nombre'],
                ':apellido' => $data['apellido'],
                ':tipo_documento' => $data['tipo_documento'],
                ':numero_documento' => $data['numero_documento'],
                ':cedula' => $data['numero_documento'], // Asumimos que cedula es igual a numero_documento para el login
                ':fecha_nacimiento' => $data['fecha_nacimiento'],
                ':ciudad_residencia' => $data['ciudad_residencia'],
                ':departamento' => $data['departamento'],
                ':whatsapp' => $data['whatsapp'],
                ':telefono' => $data['telefono'] ?? null,
                ':correo' => $data['correo'],
                ':nombre_distribuidor' => $data['nombre_distribuidor'],
                ':ciudad_punto_venta' => $data['ciudad_punto_venta'],
                ':direccion_punto_venta' => $data['direccion_punto_venta'] ?? null,
                ':cargo' => $data['cargo'],
                ':antiguedad_meses' => $data['antiguedad_meses'],
                ':llave_breb' => $data['llave_breb'],
                ':password' => $password_hash,
                ':acepta_datos' => $data['acepta_tratamiento_datos'] ? 1 : 0,
                ':acepta_contacto' => $data['acepta_contacto_comercial'] ? 1 : 0,
                ':declara_verdad' => $data['declara_info_verdadera'] ? 1 : 0,
                // 8 nuevas declaraciones legales
                ':declara_naturaleza' => $data['declara_naturaleza_comercial'] ? 1 : 0,
                ':reconoce_salario' => $data['reconoce_no_salario'] ? 1 : 0,
                ':declara_subordinacion' => $data['declara_no_subordinacion'] ? 1 : 0,
                ':declara_relacion' => $data['declara_relacion_autonoma'] ? 1 : 0,
                ':acepta_liberal' => $data['acepta_liberalidades'] ? 1 : 0,
                ':asume_tributarias' => $data['asume_obligaciones_tributarias'] ? 1 : 0,
                ':declara_contrato' => $data['declara_no_contrato'] ? 1 : 0,
                ':acepta_terminos' => $data['acepta_terminos_programa'] ? 1 : 0
            ]);

            $new_user_id = $this->conn->lastInsertId();

            // 5. Devolver respuesta exitosa
            return [
                'status' => 201,
                'message' => 'Usuario registrado exitosamente. Su cuenta está pendiente de aprobación por un administrador.',
                'userId' => $new_user_id
            ];

        } catch (PDOException $e) {
            error_log("Error de registro: " . $e->getMessage());
            return ['status' => 500, 'message' => 'Error interno del servidor al procesar el registro.'];
        }
    }

    private function validarDatosRegistro($data) {
        $required_fields = [
            'nombre', 'apellido', 'tipo_documento', 'numero_documento', 'fecha_nacimiento',
            'ciudad_residencia', 'departamento', 'whatsapp', 'correo', 'nombre_distribuidor', 'ciudad_punto_venta',
            'cargo', 'antiguedad_meses', 'llave_breb',
            'password', 'password_confirm'
        ];

        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return "El campo '$field' es obligatorio.";
            }
        }

        // Validar correo
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            return "El formato del correo electrónico no es válido.";
        }

        // Validar contraseña
        if (strlen($data['password']) < 8 || !preg_match('/[0-9]/', $data['password'])) {
            return "La contraseña debe tener al menos 8 caracteres y contener al menos un número.";
        }
        if ($data['password'] !== $data['password_confirm']) {
            return "Las contraseñas no coinciden.";
        }

        // Validar edad
        $fecha_nacimiento = new DateTime($data['fecha_nacimiento']);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nacimiento)->y;
        if ($edad < 18) {
            return "Debe ser mayor de 18 años para registrarse.";
        }

        // Validar checkboxes de políticas originales
        if (empty($data['acepta_tratamiento_datos']) || empty($data['acepta_contacto_comercial']) || empty($data['declara_info_verdadera'])) {
            return "Debe aceptar todas las políticas para continuar.";
        }

        // Validar 8 declaraciones legales del Programa de Incentivos
        $declaraciones_legales = [
            'declara_naturaleza_comercial', 'reconoce_no_salario',
            'declara_no_subordinacion', 'declara_relacion_autonoma',
            'acepta_liberalidades', 'asume_obligaciones_tributarias',
            'declara_no_contrato', 'acepta_terminos_programa'
        ];

        foreach ($declaraciones_legales as $declaracion) {
            if (empty($data[$declaracion])) {
                return "Debe aceptar todas las declaraciones legales del Programa de Incentivos.";
            }
        }

        return true;
    }
}
