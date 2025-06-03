<?php
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
    echo json_encode(["error" => "Acceso denegado. Se requiere rol de doctor"]);
    exit;
}

try {
    // Consulta para obtener la información del doctor
    $sql = "
        SELECT 
            d.id, 
            d.especialidad_id, 
            d.subespecialidad_id, 
            u.nombre, 
            u.apellido, 
            u.email, 
            u.cedula, 
            u.telefono,
            e.nombre as especialidad,
            s.nombre as subespecialidad
        FROM doctores d
        JOIN usuarios u ON d.usuario_id = u.id
        JOIN especialidades e ON d.especialidad_id = e.id
        LEFT JOIN subespecialidades s ON d.subespecialidad_id = s.id
        WHERE d.usuario_id = :usuario_id
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':usuario_id', $userData['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Devolver la información del doctor
        http_response_code(200);
        echo json_encode($doctor);
    } else {
        // Si no se encuentra información del doctor
        http_response_code(404);
        echo json_encode(["error" => "No se encontró información del doctor"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>