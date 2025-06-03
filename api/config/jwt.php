<?php
// Configuración para JWT
define('JWT_SECRET', 'tu_clave_secreta_aqui_cambiala_en_produccion');
define('JWT_EXPIRATION', 3600); // 1 hora
define('JWT_ALGORITHM', 'HS256');

// Función para generar un token JWT
function generateJWT($user) {
    $issuedAt = time();
    $expire = $issuedAt + JWT_EXPIRATION;

    $payload = [
        'iat' => $issuedAt,
        'exp' => $expire,
        'data' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ];

    $header = [
        'alg' => JWT_ALGORITHM,
        'typ' => 'JWT'
    ];

    $headerEncoded = base64_encode(json_encode($header));
    $payloadEncoded = base64_encode(json_encode($payload));
    $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
    $signatureEncoded = base64_encode($signature);

    return "$headerEncoded.$payloadEncoded.$signatureEncoded";
}

// Función para validar un token JWT
function validateJWT($token) {
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $token);
    
    $signature = base64_decode($signatureEncoded);
    $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
    
    if (!hash_equals($expectedSignature, $signature)) {
        return false;
    }
    
    $payload = json_decode(base64_decode($payloadEncoded), true);
    
    if ($payload['exp'] < time()) {
        return false;
    }
    
    return $payload['data'];
}
?>