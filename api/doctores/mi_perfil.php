<?php
// Archivo: api/doctores/mi-perfil.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivos de configuración
include_once '../config/database.php';
include_once '../config/jwt.php';

// Obtener JWT del encabezado
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Validar token
$userData = validateJWT($jwt);
if (!$userData) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Verificar que el usuario sea un doctor
if ($userData['role'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(["error" => "Solo los doctores pueden acceder a esta información"]);
    exit;
}

try {
    // Obtener información del doctor
    $stmt = $conn->prepare("
        SELECT d.*, 
               u.nombre, u.apellido, u.email, u.telefono, u.cedula,
               e.nombre as especialidad_nombre,
               s.nombre as subespecialidad_nombre
        FROM doctores d
        JOIN usuarios u ON d.usuario_id = u.id
        LEFT JOIN especialidades e ON d.especialidad_id = e.id
        LEFT JOIN subespecialidades s ON d.subespecialidad_id = s.id
        WHERE d.usuario_id = :usuario_id
    ");
    
    $stmt->bindParam(':usuario_id', $userData['id']);
    $stmt->execute();
    
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        http_response_code(404);
        echo json_encode(["error" => "Perfil de doctor no encontrado"]);
        exit;
    }
    
    // Eliminar datos sensibles
    unset($doctor['usuario_id']);
    
    http_response_code(200);
    echo json_encode($doctor);
    
} catch(PDOException $e) {
    error_log("Error al obtener perfil de doctor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor"]);
}
?>