<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (!isset($data->paciente_id) || !isset($data->especialidad_id) || !isset($data->descripcion)) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Determinar tipo de solicitante basado en el rol del usuario
    $tipoSolicitante = "";
    switch ($userData['role']) {
        case 'aseguradora':
            $tipoSolicitante = "aseguradora";
            break;
        case 'admin':
            $tipoSolicitante = "direccion_medica";
            break;
        case 'paciente':
            $tipoSolicitante = "paciente";
            break;
        default:
            http_response_code(400);
            echo json_encode(["error" => "Rol no válido para crear citas"]);
            exit;
    }
    
    // Insertar cita
    $stmt = $conn->prepare("
        INSERT INTO citas (paciente_id, especialidad_id, descripcion, estado, tipo_solicitante, creado_por) 
        VALUES (:paciente_id, :especialidad_id, :descripcion, 'solicitada', :tipo_solicitante, :creado_por)
    ");
    
    $stmt->bindParam(':paciente_id', $data->paciente_id);
    $stmt->bindParam(':especialidad_id', $data->especialidad_id);
    $stmt->bindParam(':descripcion', $data->descripcion);
    $stmt->bindParam(':tipo_solicitante', $tipoSolicitante);
    $stmt->bindParam(':creado_por', $userData['id']);
    
    $stmt->execute();
    
    $citaId = $conn->lastInsertId();
    
    http_response_code(201);
    echo json_encode([
        "message" => "Cita creada exitosamente",
        "id" => $citaId
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>