<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/JWT.php';

class AuthController {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function login($data) {
        // Validar datos de entrada
        if (!isset($data['cedula']) || !isset($data['password'])) {
            return ['status' => 400, 'message' => 'Faltan credenciales (cédula o contraseña)'];
        }

        $cedula = trim($data['cedula']);
        $password = $data['password'];

        try {
            // Buscar usuario
            $query = "SELECT id, cedula, password, rol, nombre, apellido, activo FROM usuarios WHERE cedula = :cedula LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cedula', $cedula);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Verificar contraseña
                // NOTA: Para el primer usuario hardcodeado que no tiene hash, hacemos una excepción temporal durante desarrollo
                // En producción o con usuarios registrados, SIEMPRE usar password_verify
                $password_valid = password_verify($password, $user['password']);
                
                // Fallback para desarrollo si la contraseña no está hasheada en DB (solo si password coincide texto plano)
                if (!$password_valid && $password === $user['password']) {
                   // Advertencia interna, idealmente actualizar hash aquí
                   $password_valid = true; 
                }

                if ($password_valid) {
                    if ($user['activo'] == 0) {
                         return ['status' => 403, 'message' => 'Usuario inactivo. Contacte al administrador.'];
                    }

                    // Generar Token
                    $token_payload = [
                        'user_id' => $user['id'],
                        'cedula' => $user['cedula'],
                        'rol' => $user['rol'],
                        'nombre' => $user['nombre']
                    ];
                    
                    $jwt = JWT::generate($token_payload);

                    // Eliminar password del objeto usuario antes de enviar
                    unset($user['password']);

                    return [
                        'status' => 200,
                        'message' => 'Login exitoso',
                        'token' => $jwt,
                        'user' => $user
                    ];
                }
            }

            return ['status' => 401, 'message' => 'Credenciales inválidas'];

        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return ['status' => 500, 'message' => 'Error del servidor procesando login'];
        }
    }
}
