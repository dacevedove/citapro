<?php
// Archivo api/auth/update_profile.php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Incluir la conexión a la base de datos y configuración JWT
require_once '../config/database.php';
require_once '../config/jwt.php';

// Verificar el método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

// Verificar el token JWT
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token no proporcionado']);
    exit;
}

$token = $matches[1];
$isValid = verifyJWT($token);

if (!$isValid) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido o expirado']);
    exit;
}

// Validar datos del usuario
if (!isset($data['id']) || !isset($data['nombre']) || !isset($data['apellido'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$id = $data['id'];
$nombre = $data['nombre'];
$apellido = $data['apellido'];
$telefono = isset($data['telefono']) ? $data['telefono'] : '';

try {
    $conn = getConnection();
    
    // Actualizar información del usuario
    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, telefono = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nombre, $apellido, $telefono, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Perfil actualizado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el perfil: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>