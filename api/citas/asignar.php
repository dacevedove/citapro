<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
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
if (!$userData || $userData['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (!isset($data->cita_id) || !isset($data->especialidad_id) || 
    (!isset($data->doctor_id) && !isset($data->asignacion_libre))) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Actualizar la cita con asignación a doctor o asignación libre
    if (isset($data->doctor_id)) {
        $stmt = $conn->prepare("
            UPDATE citas SET 
            especialidad_id = :especialidad_id,
            doctor_id = :doctor_id,
            asignacion_libre = FALSE,
            estado = 'asignada'
            WHERE id = :cita_id
        ");
        
        $stmt->bindParam(':especialidad_id', $data->especialidad_id);
        $stmt->bindParam(':doctor_id', $data->doctor_id);
        $stmt->bindParam(':cita_id', $data->cita_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE citas SET 
            especialidad_id = :especialidad_id,
            doctor_id = NULL,
            asignacion_libre = TRUE,
            estado = 'asignada'
            WHERE id = :cita_id
        ");
        
        $stmt->bindParam(':especialidad_id', $data->especialidad_id);
        $stmt->bindParam(':cita_id', $data->cita_id);
    }
    
    $stmt->execute();
    
    http_response_code(200);
    echo json_encode([
        "message" => "Cita asignada exitosamente"
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>