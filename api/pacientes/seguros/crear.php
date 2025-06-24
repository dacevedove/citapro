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
$data = json_decode(file_get_contents("php://input"));

// Validar datos requeridos
if (!isset($data->paciente_id) || !isset($data->aseguradora_id) || 
    !isset($data->numero_poliza) || !isset($data->fecha_inicio)) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    $conn->beginTransaction();
    
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
    
    // Verificar que no exista ya ese número de póliza para esa aseguradora
    $stmt = $conn->prepare("
        SELECT id FROM pacientes_seguros 
        WHERE aseguradora_id = :aseguradora_id AND numero_poliza = :numero_poliza
    ");
    $stmt->bindParam(':aseguradora_id', $data->aseguradora_id);
    $stmt->bindParam(':numero_poliza', $data->numero_poliza);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        throw new Exception("Ya existe un paciente con ese número de póliza en esta aseguradora");
    }
    
    // Si es cobertura principal, desactivar otras coberturas principales
    if (isset($data->tipo_cobertura) && $data->tipo_cobertura === 'principal') {
        $stmt = $conn->prepare("
            UPDATE pacientes_seguros 
            SET tipo_cobertura = 'secundario' 
            WHERE paciente_id = :paciente_id AND tipo_cobertura = 'principal'
        ");
        $stmt->bindParam(':paciente_id', $data->paciente_id);
        $stmt->execute();
    }
    
    // Insertar nuevo seguro
    $stmt = $conn->prepare("
        INSERT INTO pacientes_seguros (
            paciente_id, aseguradora_id, numero_poliza, tipo_cobertura, 
            estado, fecha_inicio, fecha_vencimiento, beneficiario_principal,
            parentesco, cedula_titular, nombre_titular, observaciones, creado_por
        ) VALUES (
            :paciente_id, :aseguradora_id, :numero_poliza, :tipo_cobertura,
            :estado, :fecha_inicio, :fecha_vencimiento, :beneficiario_principal,
            :parentesco, :cedula_titular, :nombre_titular, :observaciones, :creado_por
        )
    ");
    
    $stmt->bindParam(':paciente_id', $data->paciente_id);
    $stmt->bindParam(':aseguradora_id', $data->aseguradora_id);
    $stmt->bindParam(':numero_poliza', $data->numero_poliza);
    $stmt->bindParam(':tipo_cobertura', $data->tipo_cobertura ?? 'principal');
    $stmt->bindParam(':estado', $data->estado ?? 'activo');
    $stmt->bindParam(':fecha_inicio', $data->fecha_inicio);
    $stmt->bindParam(':fecha_vencimiento', $data->fecha_vencimiento ?? null);
    $stmt->bindParam(':beneficiario_principal', $data->beneficiario_principal ?? 1);
    $stmt->bindParam(':parentesco', $data->parentesco ?? null);
    $stmt->bindParam(':cedula_titular', $data->cedula_titular ?? null);
    $stmt->bindParam(':nombre_titular', $data->nombre_titular ?? null);
    $stmt->bindParam(':observaciones', $data->observaciones ?? null);
    $stmt->bindParam(':creado_por', $userData['id']);
    
    $stmt->execute();
    $seguro_id = $conn->lastInsertId();
    
    // Registrar en historial
    $stmt = $conn->prepare("
        INSERT INTO pacientes_seguros_historial (
            paciente_seguro_id, accion, estado_nuevo, datos_nuevos, realizado_por
        ) VALUES (
            :seguro_id, 'activacion', :estado, :datos, :usuario_id
        )
    ");
    
    $datos_nuevos = json_encode([
        'numero_poliza' => $data->numero_poliza,
        'tipo_cobertura' => $data->tipo_cobertura ?? 'principal',
        'aseguradora_id' => $data->aseguradora_id
    ]);
    
    $stmt->bindParam(':seguro_id', $seguro_id);
    $stmt->bindParam(':estado', $data->estado ?? 'activo');
    $stmt->bindParam(':datos', $datos_nuevos);
    $stmt->bindParam(':usuario_id', $userData['id']);
    $stmt->execute();
    
    $conn->commit();
    
    http_response_code(201);
    echo json_encode([
        "message" => "Seguro agregado exitosamente",
        "id" => $seguro_id
    ]);
    
} catch(Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>