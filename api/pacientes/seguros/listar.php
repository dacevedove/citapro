<?php
// api/pacientes/seguros/listar.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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

// Obtener ID del paciente
$paciente_id = isset($_GET['paciente_id']) ? $_GET['paciente_id'] : null;

if (!$paciente_id) {
    http_response_code(400);
    echo json_encode(["error" => "ID del paciente requerido"]);
    exit;
}

try {
    // Consultar seguros del paciente usando la vista
    $stmt = $conn->prepare("
        SELECT * FROM v_pacientes_seguros_completo 
        WHERE paciente_id = :paciente_id 
        ORDER BY tipo_cobertura ASC, estado ASC, fecha_inicio DESC
    ");
    $stmt->bindParam(':paciente_id', $paciente_id);
    $stmt->execute();
    
    $seguros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos
    foreach ($seguros as &$seguro) {
        $seguro['estado_vigencia_texto'] = [
            'vigente' => 'Vigente',
            'vence_hoy' => 'Vence hoy',
            'vencido' => 'Vencido',
            'indefinido' => 'Sin vencimiento'
        ][$seguro['estado_vigencia']] ?? $seguro['estado_vigencia'];
        
        $seguro['tipo_cobertura_texto'] = [
            'principal' => 'Principal',
            'secundario' => 'Secundario',
            'complementario' => 'Complementario'
        ][$seguro['tipo_cobertura']] ?? $seguro['tipo_cobertura'];
    }
    
    http_response_code(200);
    echo json_encode($seguros);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>

<?php
// api/pacientes/seguros/crear.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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

<?php
// api/pacientes/seguros/eliminar.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
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
if (!$userData || ($userData['role'] !== 'admin' && $userData['role'] !== 'coordinador')) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id)) {
    http_response_code(400);
    echo json_encode(["error" => "ID del seguro requerido"]);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Obtener datos del seguro
    $stmt = $conn->prepare("SELECT * FROM pacientes_seguros WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    $seguro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seguro) {
        throw new Exception("Seguro no encontrado");
    }
    
    // Verificar si hay citas asociadas
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE paciente_seguro_id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        throw new Exception("No se puede eliminar el seguro porque tiene citas asociadas");
    }
    
    // Registrar en historial antes de eliminar
    $stmt = $conn->prepare("
        INSERT INTO pacientes_seguros_historial (
            paciente_seguro_id, accion, estado_anterior, datos_anteriores, realizado_por
        ) VALUES (
            :seguro_id, 'desactivacion', :estado, :datos, :usuario_id
        )
    ");
    
    $stmt->bindParam(':seguro_id', $data->id);
    $stmt->bindParam(':estado', $seguro['estado']);
    $stmt->bindParam(':datos', json_encode($seguro));
    $stmt->bindParam(':usuario_id', $userData['id']);
    $stmt->execute();
    
    // Eliminar seguro
    $stmt = $conn->prepare("DELETE FROM pacientes_seguros WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    
    $conn->commit();
    
    http_response_code(200);
    echo json_encode(["message" => "Seguro eliminado exitosamente"]);
    
} catch(Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>

<?php
// api/pacientes/seguros/buscar-poliza.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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

// Obtener parámetros
$numero_poliza = isset($_GET['numero_poliza']) ? $_GET['numero_poliza'] : null;
$aseguradora_id = isset($_GET['aseguradora_id']) ? $_GET['aseguradora_id'] : null;

if (!$numero_poliza) {
    http_response_code(400);
    echo json_encode(["error" => "Número de póliza requerido"]);
    exit;
}

try {
    $sql = "SELECT * FROM v_pacientes_seguros_completo WHERE numero_poliza = :numero_poliza";
    $params = [':numero_poliza' => $numero_poliza];
    
    if ($aseguradora_id) {
        $sql .= " AND aseguradora_id = :aseguradora_id";
        $params[':aseguradora_id'] = $aseguradora_id;
    }
    
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $seguro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($seguro) {
        http_response_code(200);
        echo json_encode($seguro);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Póliza no encontrada"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>