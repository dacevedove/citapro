<?php
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

// Incluir archivos necesarios
include_once '../config/database.php';
include_once '../config/jwt.php';

// Obtener el token del encabezado Authorization
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Verificar si se proporcionó un token
if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Token no proporcionado", "valid" => false]);
    exit;
}

// Extraer el token
$token = $matches[1];

// Validar el token
$userData = validateJWT($token);

if (!$userData) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Token inválido o expirado", "valid" => false]);
    exit;
}

try {
    // Verificar que el usuario siga existiendo en la base de datos
    $stmt = $conn->prepare("SELECT id, nombre, apellido, email, cedula, telefono, role FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $userData['id']);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar si se encontró el usuario
    if (!$user) {
        http_response_code(401); // Unauthorized
        echo json_encode(["error" => "Usuario no encontrado", "valid" => false]);
        exit;
    }
    
    // Responder con los datos del usuario
    http_response_code(200);
    echo json_encode([
        "message" => "Token válido",
        "valid" => true,
        "user" => $user
    ]);
    
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage(), "valid" => false]);
}
?>