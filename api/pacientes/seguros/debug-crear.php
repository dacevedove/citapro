<?php
// api/pacientes/seguros/debug-crear.php
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

// Log inicial
error_log("=== DEBUG CREAR SEGURO ===");

// Obtener JWT del encabezado
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

error_log("JWT recibido: " . ($jwt ? "SÍ" : "NO"));

// Validar token
$userData = validateJWT($jwt);
if (!$userData) {
    error_log("Error: Token inválido");
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

error_log("Usuario autenticado: " . json_encode($userData));

// Obtener datos del cuerpo de la solicitud
$input = file_get_contents("php://input");
error_log("Datos RAW recibidos: " . $input);

$data = json_decode($input);

if ($data === null) {
    error_log("Error: JSON inválido");
    http_response_code(400);
    echo json_encode(["error" => "JSON inválido"]);
    exit;
}

error_log("Datos decodificados: " . json_encode($data));

// Validar datos mínimos
if (!isset($data->paciente_id) || !isset($data->aseguradora_id) || 
    !isset($data->numero_poliza) || !isset($data->fecha_inicio)) {
    error_log("Error: Datos incompletos");
    http_response_code(400);
    echo json_encode([
        "error" => "Datos incompletos",
        "faltantes" => [
            "paciente_id" => !isset($data->paciente_id),
            "aseguradora_id" => !isset($data->aseguradora_id),
            "numero_poliza" => !isset($data->numero_poliza),
            "fecha_inicio" => !isset($data->fecha_inicio)
        ]
    ]);
    exit;
}

try {
    // Verificar tabla pacientes_seguros
    $stmt = $conn->prepare("SHOW TABLES LIKE 'pacientes_seguros'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        throw new Exception("Tabla pacientes_seguros no existe");
    }
    error_log("Tabla pacientes_seguros: OK");
    
    // Verificar estructura de la tabla
    $stmt = $conn->prepare("DESCRIBE pacientes_seguros");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    error_log("Columnas de pacientes_seguros: " . json_encode(array_column($columns, 'Field')));
    
    // Verificar que el paciente existe
    $stmt = $conn->prepare("SELECT id, nombre, apellido FROM pacientes WHERE id = :paciente_id");
    $stmt->bindParam(':paciente_id', $data->paciente_id);
    $stmt->execute();
    $paciente = $stmt->fetch();
    
    if (!$paciente) {
        throw new Exception("Paciente con ID {$data->paciente_id} no encontrado");
    }
    error_log("Paciente encontrado: " . json_encode($paciente));
    
    // Verificar que la aseguradora existe
    $stmt = $conn->prepare("SELECT id, nombre_comercial FROM aseguradoras WHERE id = :aseguradora_id");
    $stmt->bindParam(':aseguradora_id', $data->aseguradora_id);
    $stmt->execute();
    $aseguradora = $stmt->fetch();
    
    if (!$aseguradora) {
        throw new Exception("Aseguradora con ID {$data->aseguradora_id} no encontrada");
    }
    error_log("Aseguradora encontrada: " . json_encode($aseguradora));
    
    // Insertar con datos mínimos
    $stmt = $conn->prepare("
        INSERT INTO pacientes_seguros (
            paciente_id, aseguradora_id, numero_poliza, tipo_cobertura, 
            estado, fecha_inicio, creado_por
        ) VALUES (
            :paciente_id, :aseguradora_id, :numero_poliza, :tipo_cobertura,
            :estado, :fecha_inicio, :creado_por
        )
    ");
    
    $stmt->bindParam(':paciente_id', $data->paciente_id);
    $stmt->bindParam(':aseguradora_id', $data->aseguradora_id);
    $stmt->bindParam(':numero_poliza', $data->numero_poliza);
    $stmt->bindValue(':tipo_cobertura', $data->tipo_cobertura ?? 'principal');
    $stmt->bindValue(':estado', $data->estado ?? 'activo');
    $stmt->bindParam(':fecha_inicio', $data->fecha_inicio);
    $stmt->bindParam(':creado_por', $userData['id']);
    
    error_log("Ejecutando INSERT...");
    $stmt->execute();
    $seguro_id = $conn->lastInsertId();
    
    error_log("Seguro creado con ID: " . $seguro_id);
    
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Seguro creado exitosamente (modo debug)",
        "id" => $seguro_id,
        "debug_info" => [
            "paciente" => $paciente,
            "aseguradora" => $aseguradora,
            "usuario" => $userData
        ]
    ]);
    
} catch(Exception $e) {
    error_log("Error al crear seguro: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        "error" => "Error en el servidor: " . $e->getMessage(),
        "debug" => true
    ]);
}
?>