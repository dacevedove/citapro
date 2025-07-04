<?php
// api/pacientes/seguros/crear.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

include_once '../../config/database.php';
include_once '../../config/jwt.php';

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
$input = file_get_contents("php://input");
$data = json_decode($input);


// Validar que se pudo decodificar el JSON
if ($data === null) {
    http_response_code(400);
    echo json_encode(["error" => "JSON inválido"]);
    exit;
}

// Validar datos requeridos
if (!isset($data->paciente_id) || !isset($data->aseguradora_id) || 
    !isset($data->numero_poliza) || !isset($data->fecha_inicio)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Datos incompletos",
        "recibido" => [
            "paciente_id" => isset($data->paciente_id) ? $data->paciente_id : 'faltante',
            "aseguradora_id" => isset($data->aseguradora_id) ? $data->aseguradora_id : 'faltante',
            "numero_poliza" => isset($data->numero_poliza) ? $data->numero_poliza : 'faltante',
            "fecha_inicio" => isset($data->fecha_inicio) ? $data->fecha_inicio : 'faltante'
        ]
    ]);
    exit;
}

try {
    // Verificar que el paciente existe
    $stmt = $conn->prepare("SELECT id FROM pacientes WHERE id = :paciente_id");
    $stmt->bindParam(':paciente_id', $data->paciente_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Paciente no encontrado");
    }
    
    // Verificar que la aseguradora existe
    $stmt = $conn->prepare("SELECT id FROM aseguradoras WHERE id = :aseguradora_id");
    $stmt->bindParam(':aseguradora_id', $data->aseguradora_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Aseguradora no encontrada");
    }
    
    // Insertar con campos básicos incluyendo fecha_vencimiento
    $stmt = $conn->prepare("
        INSERT INTO pacientes_seguros (
            paciente_id, aseguradora_id, numero_poliza, tipo_cobertura, 
            estado, fecha_inicio, fecha_vencimiento, creado_por
        ) VALUES (
            :paciente_id, :aseguradora_id, :numero_poliza, :tipo_cobertura,
            :estado, :fecha_inicio, :fecha_vencimiento, :creado_por
        )
    ");
    
    $tipo_cobertura = $data->tipo_cobertura ?? 'principal';
    $estado = $data->estado ?? 'activo';
    $fecha_vencimiento = (isset($data->fecha_vencimiento) && !empty($data->fecha_vencimiento)) ? $data->fecha_vencimiento : null;
    
    $stmt->bindParam(':paciente_id', $data->paciente_id);
    $stmt->bindParam(':aseguradora_id', $data->aseguradora_id);
    $stmt->bindParam(':numero_poliza', $data->numero_poliza);
    $stmt->bindParam(':tipo_cobertura', $tipo_cobertura);
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':fecha_inicio', $data->fecha_inicio);
    $stmt->bindParam(':fecha_vencimiento', $fecha_vencimiento);
    $stmt->bindParam(':creado_por', $userData['id']);
    
    
    $stmt->execute();
    $seguro_id = $conn->lastInsertId();
    
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Seguro agregado exitosamente",
        "id" => $seguro_id
    ]);
    
} catch(Exception $e) {
    error_log("Error al crear seguro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>