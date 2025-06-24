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

// Log para debugging
error_log("Datos recibidos para crear seguro: " . $input);

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
    
    // Preparar campos para inserción
    $tipo_cobertura = $data->tipo_cobertura ?? 'principal';
    $estado = $data->estado ?? 'activo';
    $fecha_vencimiento = isset($data->fecha_vencimiento) && !empty($data->fecha_vencimiento) ? $data->fecha_vencimiento : null;
    $beneficiario_principal = $data->beneficiario_principal ?? 1;
    $parentesco = isset($data->parentesco) && !empty($data->parentesco) ? $data->parentesco : null;
    $cedula_titular = isset($data->cedula_titular) && !empty($data->cedula_titular) ? $data->cedula_titular : null;
    $nombre_titular = isset($data->nombre_titular) && !empty($data->nombre_titular) ? $data->nombre_titular : null;
    $observaciones = isset($data->observaciones) && !empty($data->observaciones) ? $data->observaciones : null;
    
    // Construir SQL dinámicamente solo con campos que tienen valores
    $campos = ['paciente_id', 'aseguradora_id', 'numero_poliza', 'tipo_cobertura', 'estado', 'fecha_inicio', 'creado_por'];
    $valores = [':paciente_id', ':aseguradora_id', ':numero_poliza', ':tipo_cobertura', ':estado', ':fecha_inicio', ':creado_por'];
    $params = [
        ':paciente_id' => $data->paciente_id,
        ':aseguradora_id' => $data->aseguradora_id,
        ':numero_poliza' => $data->numero_poliza,
        ':tipo_cobertura' => $tipo_cobertura,
        ':estado' => $estado,
        ':fecha_inicio' => $data->fecha_inicio,
        ':creado_por' => $userData['id']
    ];
    
    // Agregar campos opcionales solo si tienen valores
    if ($fecha_vencimiento !== null) {
        $campos[] = 'fecha_vencimiento';
        $valores[] = ':fecha_vencimiento';
        $params[':fecha_vencimiento'] = $fecha_vencimiento;
    }
    
    if ($beneficiario_principal !== null) {
        $campos[] = 'beneficiario_principal';
        $valores[] = ':beneficiario_principal';
        $params[':beneficiario_principal'] = $beneficiario_principal;
    }
    
    if ($parentesco !== null) {
        $campos[] = 'parentesco';
        $valores[] = ':parentesco';
        $params[':parentesco'] = $parentesco;
    }
    
    if ($cedula_titular !== null) {
        $campos[] = 'cedula_titular';
        $valores[] = ':cedula_titular';
        $params[':cedula_titular'] = $cedula_titular;
    }
    
    if ($nombre_titular !== null) {
        $campos[] = 'nombre_titular';
        $valores[] = ':nombre_titular';
        $params[':nombre_titular'] = $nombre_titular;
    }
    
    if ($observaciones !== null) {
        $campos[] = 'observaciones';
        $valores[] = ':observaciones';
        $params[':observaciones'] = $observaciones;
    }
    
    $sql = "INSERT INTO pacientes_seguros (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $valores) . ")";
    
    $stmt = $conn->prepare($sql);
    
    // Vincular parámetros
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    error_log("Intentando insertar seguro con datos: " . json_encode([
        'paciente_id' => $data->paciente_id,
        'aseguradora_id' => $data->aseguradora_id,
        'numero_poliza' => $data->numero_poliza,
        'tipo_cobertura' => $tipo_cobertura,
        'estado' => $estado,
        'fecha_inicio' => $data->fecha_inicio,
        'beneficiario_principal' => $beneficiario_principal
    ]));
    
    $stmt->execute();
    $seguro_id = $conn->lastInsertId();
    
    // Registrar en historial (opcional, no falla si la tabla no existe)
    try {
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
    } catch (Exception $historial_error) {
        // Si no existe la tabla de historial, continuar sin error
        error_log("No se pudo registrar en historial: " . $historial_error->getMessage());
    }
    
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