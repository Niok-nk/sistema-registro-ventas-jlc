<?php
class JWT {
    private static $secret_key = 'JLC_SECRET_KEY_CHANGE_ME_IN_ENV'; // En producción usar getenv()
    private static $algorithm = 'HS256';

    public static function generate($data) {
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
        return getenv('JWT_SECRET') ?: self::$secret_key;
    }
}
