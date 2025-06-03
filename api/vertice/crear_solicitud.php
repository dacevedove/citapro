<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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

// Validar token
$userData = validateJWT($jwt);
if (!$userData || $userData['role'] !== 'vertice') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (
    !isset($data->numero_id) || !isset($data->cedula_titular) || 
    !isset($data->cedula_paciente) || !isset($data->nombre_paciente) || 
    !isset($data->telefono) || !isset($data->fecha_nacimiento) || 
    !isset($data->sexo) || !isset($data->especialidad_requerida)
) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Verificar que la especialidad exista
    $stmt = $conn->prepare("SELECT id FROM especialidades WHERE id = :id");
    $stmt->bindParam(':id', $data->especialidad_requerida);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(["error" => "La especialidad seleccionada no existe"]);
        exit;
    }
    
    // Insertar nueva solicitud
    $stmt = $conn->prepare("
        INSERT INTO temp_solicitudes 
        (fecha_solicitud, numero_id, cedula_titular, cedula_paciente, nombre_paciente, 
         telefono, fecha_nacimiento, sexo, especialidad_requerida, estatus, creado_por) 
        VALUES 
        (NOW(), :numero_id, :cedula_titular, :cedula_paciente, :nombre_paciente, 
         :telefono, :fecha_nacimiento, :sexo, :especialidad_requerida, 'pendiente', :creado_por)
    ");
    
    $stmt->bindParam(':numero_id', $data->numero_id);
    $stmt->bindParam(':cedula_titular', $data->cedula_titular);
    $stmt->bindParam(':cedula_paciente', $data->cedula_paciente);
    $stmt->bindParam(':nombre_paciente', $data->nombre_paciente);
    $stmt->bindParam(':telefono', $data->telefono);
    $stmt->bindParam(':fecha_nacimiento', $data->fecha_nacimiento);
    $stmt->bindParam(':sexo', $data->sexo);
    $stmt->bindParam(':especialidad_requerida', $data->especialidad_requerida);
    $stmt->bindParam(':creado_por', $userData['id']);
    
    $stmt->execute();
    
    $solicitud_id = $conn->lastInsertId();
    
    http_response_code(201);
    echo json_encode([
        "message" => "Solicitud creada exitosamente",
        "id" => $solicitud_id
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}