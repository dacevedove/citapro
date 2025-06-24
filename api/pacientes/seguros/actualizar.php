<?php
// api/pacientes/seguros/actualizar.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
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
$data = json_decode(file_get_contents("php://input"));

// Validar datos requeridos
if (!isset($data->id)) {
    http_response_code(400);
    echo json_encode(["error" => "ID del seguro requerido"]);
    exit;
}

try {
    // Verificar que el seguro existe
    $stmt = $conn->prepare("SELECT id, paciente_id FROM pacientes_seguros WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    $seguro_actual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seguro_actual) {
        throw new Exception("Seguro no encontrado");
    }
    
    // Actualizar con campos básicos
    $stmt = $conn->prepare("
        UPDATE pacientes_seguros SET 
            numero_poliza = :numero_poliza,
            tipo_cobertura = :tipo_cobertura,
            estado = :estado,
            fecha_inicio = :fecha_inicio,
            fecha_vencimiento = :fecha_vencimiento
        WHERE id = :id
    ");
    
    $fecha_vencimiento = (isset($data->fecha_vencimiento) && !empty($data->fecha_vencimiento)) ? $data->fecha_vencimiento : null;
    
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':numero_poliza', $data->numero_poliza);
    $stmt->bindParam(':tipo_cobertura', $data->tipo_cobertura);
    $stmt->bindParam(':estado', $data->estado);
    $stmt->bindParam(':fecha_inicio', $data->fecha_inicio);
    $stmt->bindParam(':fecha_vencimiento', $fecha_vencimiento);
    
    $stmt->execute();
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Seguro actualizado exitosamente"
    ]);
    
} catch(Exception $e) {
    error_log("Error al actualizar seguro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>