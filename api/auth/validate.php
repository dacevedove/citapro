<?php
// api/auth/validate.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Para solicitudes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir archivos de configuración
include_once '../config/database.php';
include_once '../config/jwt.php';

// Obtener JWT del encabezado
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

if (!$jwt) {
    http_response_code(401);
    echo json_encode([
        "valid" => false,
        "error" => "Token no proporcionado"
    ]);
    exit;
}

try {
    // Validar JWT
    $userData = validateJWT($jwt);
    
    if (!$userData) {
        http_response_code(401);
        echo json_encode([
            "valid" => false,
            "error" => "Token inválido"
        ]);
        exit;
    }
    
    // Obtener datos actualizados del usuario desde la base de datos
    $stmt = $conn->prepare("
        SELECT id, nombre, apellido, email, cedula, telefono, role, 
               esta_activo, email_verificado, foto_perfil, ultimo_acceso, creado_en
        FROM usuarios 
        WHERE id = :user_id AND esta_activo = 1
        LIMIT 1
    ");
    
    $stmt->bindParam(':user_id', $userData['id']);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode([
            "valid" => false,
            "error" => "Usuario no encontrado o inactivo"
        ]);
        exit;
    }
    
    // Formatear fechas
    if ($user['ultimo_acceso']) {
        $user['ultimo_acceso'] = date('Y-m-d H:i:s', strtotime($user['ultimo_acceso']));
    }
    $user['creado_en'] = date('Y-m-d H:i:s', strtotime($user['creado_en']));
    
    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        "valid" => true,
        "user" => $user
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        "valid" => false,
        "error" => "Error interno del servidor"
    ]);
    error_log("Error en validación de token: " . $e->getMessage());
}
?>