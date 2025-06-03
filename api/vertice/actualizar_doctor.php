<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
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
if (!isset($data->doctor_id) || !isset($data->activo)) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Verificar si el doctor existe
    $stmt = $conn->prepare("SELECT id FROM doctores WHERE id = :doctor_id");
    $stmt->bindParam(':doctor_id', $data->doctor_id);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        throw new Exception("El doctor no existe");
    }
    
    // Verificar si ya hay un registro de activación para este doctor
    $stmt = $conn->prepare("SELECT id FROM temp_doctores_activos WHERE doctor_id = :doctor_id");
    $stmt->bindParam(':doctor_id', $data->doctor_id);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        // Actualizar registro existente
        $stmt = $conn->prepare("
            UPDATE temp_doctores_activos 
            SET activo = :activo, actualizado_en = NOW()
            WHERE doctor_id = :doctor_id
        ");
    } else {
        // Crear nuevo registro
        $stmt = $conn->prepare("
            INSERT INTO temp_doctores_activos 
            (doctor_id, activo, creado_por, creado_en, actualizado_en) 
            VALUES 
            (:doctor_id, :activo, :creado_por, NOW(), NOW())
        ");
        $stmt->bindParam(':creado_por', $userData['id']);
    }
    
    $stmt->bindParam(':doctor_id', $data->doctor_id);
    $stmt->bindParam(':activo', $data->activo, PDO::PARAM_BOOL);
    $stmt->execute();
    
    http_response_code(200);
    echo json_encode([
        "message" => "Estado del doctor actualizado exitosamente"
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}