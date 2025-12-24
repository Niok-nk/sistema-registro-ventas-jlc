<?php
class JWT {
    private static $algorithm = 'HS256';
    private static $envLoaded = false;

    private static function loadEnv() {
        if (self::$envLoaded) {
            return;
        }

        $envFile = __DIR__ . '/../../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Ignorar comentarios y líneas vacías
                if (strpos(trim($line), '#') === 0 || trim($line) === '') {
                    continue;
                }
                
                if (strpos($line, '=') === false) {
                    continue;
                }
                
                // Separar nombre y valor
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remover comillas si existen
                $value = trim($value, '"\'');
                
                // Establecer variable de entorno solo si no existe
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
        
        self::$envLoaded = true;
    }

    /**
     * Valida que JWT_SECRET sea seguro
     * Previene uso de valores por defecto o débiles
     */
    private static function validateSecret() {
        self::loadEnv();
        
        $secret = getenv('JWT_SECRET');
        
        // Lista de secretos inseguros prohibidos
        $unsafeSecrets = [
            'cambiar_esto_por_una_clave_segura_base64',
            'secret',
            'test',
            '123456',
            'password',
            ''
        ];
        
        if (in_array($secret, $unsafeSecrets)) {
            error_log('CRITICAL SECURITY: JWT_SECRET inseguro detectado');
            http_response_code(500);
            die(json_encode([
                'status' => 500,
                'message' => 'Configuración de seguridad incorrecta',
                'hint' => 'Genera un JWT_SECRET único con: php -r "echo base64_encode(random_bytes(32));"'
            ]));
        }
        
        // El secret debe tener al menos 32 caracteres
        if ($secret && strlen($secret) < 32) {
            error_log('CRITICAL SECURITY: JWT_SECRET demasiado corto (' . strlen($secret) . ' caracteres)');
            http_response_code(500);
            die(json_encode([
                'status' => 500,
                'message' => 'Configuración de seguridad incorrecta',
                'hint' => 'JWT_SECRET debe tener al menos 32 caracteres'
            ]));
        }
    }

    public static function generate($data) {
        // Validar que el secret sea seguro antes de generar tokens
        self::validateSecret();
        
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => self::$algorithm
        ]);

        // Agregar timestamp y expiración por defecto (8 horas)
        $payload = json_encode(array_merge($data, [
            'iat' => time(),
            'exp' => time() + (8 * 60 * 60)
        ]));

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);

        $signature = hash_hmac('sha256', 
            $base64UrlHeader . "." . $base64UrlPayload, 
            self::getSecret(), 
            true
        );

        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function verify($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) return false;

        [$header64, $payload64, $sig64] = $parts;

        $sig = self::base64UrlDecode($sig64);
        $expectedSig = hash_hmac('sha256', 
            $header64 . "." . $payload64, 
            self::getSecret(), 
            true
        );

        if (!hash_equals($sig, $expectedSig)) return false;

        $payload = json_decode(self::base64UrlDecode($payload64), true);
        
        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private static function getSecret() {
        // Cargar .env primero
        self::loadEnv();
        
        $secret = getenv('JWT_SECRET');
        if (!$secret) {
            // Fallback para desarrollo local (NUNCA usar en producción)
            $secret = 'dev-secret-key-change-in-production-' . md5(__DIR__);
            error_log("ADVERTENCIA: Usando JWT_SECRET por defecto. Configura JWT_SECRET en .env para producción");
        }
        return $secret;
    }
}
