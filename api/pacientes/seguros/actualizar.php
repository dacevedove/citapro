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
    $conn->beginTransaction();
    
    // Obtener datos actuales
    $stmt = $conn->prepare("SELECT * FROM pacientes_seguros WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    $seguro_actual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seguro_actual) {
        throw new Exception("Seguro no encontrado");
    }
    
    // Verificar permisos - solo admin, coordinador o el creador pueden modificar
    if ($userData['role'] !== 'admin' && $userData['role'] !== 'coordinador' && 
        $seguro_actual['creado_por'] != $userData['id']) {
        http_response_code(403);
        echo json_encode(["error" => "No tiene permisos para modificar este seguro"]);
        exit;
    }
    
    // Si cambia a cobertura principal, desactivar otras principales
    if (isset($data->tipo_cobertura) && $data->tipo_cobertura === 'principal' && 
        $seguro_actual['tipo_cobertura'] !== 'principal') {
        $stmt = $conn->prepare("
            UPDATE pacientes_seguros 
            SET tipo_cobertura = 'secundario' 
            WHERE paciente_id = :paciente_id AND tipo_cobertura = 'principal' AND id != :id
        ");
        $stmt->bindParam(':paciente_id', $seguro_actual['paciente_id']);
        $stmt->bindParam(':id', $data->id);
        $stmt->execute();
    }
    
    // Verificar si se está cambiando el número de póliza y que no exista
    if (isset($data->numero_poliza) && $data->numero_poliza !== $seguro_actual['numero_poliza']) {
        $stmt = $conn->prepare("
            SELECT id FROM pacientes_seguros 
            WHERE aseguradora_id = :aseguradora_id AND numero_poliza = :numero_poliza AND id != :id
        ");
        $stmt->bindParam(':aseguradora_id', $seguro_actual['aseguradora_id']);
        $stmt->bindParam(':numero_poliza', $data->numero_poliza);
        $stmt->bindParam(':id', $data->id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Ya existe otro seguro con ese número de póliza en esta aseguradora");
        }
    }
    
    // Actualizar seguro
    $updates = [];
    $params = [':id' => $data->id];
    
    $campos_actualizables = [
        'numero_poliza', 'tipo_cobertura', 'estado', 'fecha_inicio', 
        'fecha_vencimiento', 'beneficiario_principal', 'parentesco', 
        'cedula_titular', 'nombre_titular', 'observaciones'
    ];
    
    foreach ($campos_actualizables as $campo) {
        if (isset($data->$campo)) {
            $updates[] = "$campo = :$campo";
            $params[":$campo"] = $data->$campo;
        }
    }
    
    if (!empty($updates)) {
        $sql = "UPDATE pacientes_seguros SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $conn->prepare($sql);
        
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        
        // Registrar en historial
        $stmt = $conn->prepare("
            INSERT INTO pacientes_seguros_historial (
                paciente_seguro_id, accion, estado_anterior, estado_nuevo, 
                datos_anteriores, datos_nuevos, realizado_por
            ) VALUES (
                :seguro_id, 'modificacion', :estado_anterior, :estado_nuevo,
                :datos_anteriores, :datos_nuevos, :usuario_id
            )
        ");
        
        $stmt->bindParam(':seguro_id', $data->id);
        $stmt->bindParam(':estado_anterior', $seguro_actual['estado']);
        $stmt->bindParam(':estado_nuevo', $data->estado ?? $seguro_actual['estado']);
        $stmt->bindParam(':datos_anteriores', json_encode($seguro_actual));
        $stmt->bindParam(':datos_nuevos', json_encode((array)$data));
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
    }
    
    $conn->commit();
    
    http_response_code(200);
    echo json_encode(["message" => "Seguro actualizado exitosamente"]);
    
} catch(Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>