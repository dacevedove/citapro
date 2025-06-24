<?php
//api/citas/crear.php
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

// Verificar permisos - admin, coordinador, doctor y aseguradora pueden crear citas
if (!in_array($userData['role'], ['admin', 'coordinador', 'doctor', 'aseguradora', 'paciente'])) {
    http_response_code(403);
    echo json_encode(["error" => "No tiene permisos para crear citas"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (!isset($data->paciente_id) || !isset($data->especialidad_id) || !isset($data->descripcion)) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos: paciente_id, especialidad_id y descripcion son requeridos"]);
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
        case 'coordinador':
            $tipoSolicitante = "direccion_medica";
            break;
        case 'paciente':
            $tipoSolicitante = "paciente";
            break;
        case 'doctor':
            $tipoSolicitante = "direccion_medica"; // Los doctores crean como dirección médica
            break;
        default:
            http_response_code(400);
            echo json_encode(["error" => "Rol no válido para crear citas"]);
            exit;
    }
    
    // Determinar estado inicial - si se asigna directamente a un horario, marcar como 'asignada'
    $estadoInicial = (isset($data->horario_id) && !empty($data->horario_id)) ? 'asignada' : 'solicitada';
    
    // Preparar la consulta SQL
    $sql = "INSERT INTO citas (paciente_id, especialidad_id, descripcion, estado, tipo_solicitante, creado_por";
    $values = "VALUES (:paciente_id, :especialidad_id, :descripcion, :estado, :tipo_solicitante, :creado_por";
    
    // Añadir tipo_bloque_id si se proporciona
    if (isset($data->tipo_bloque_id) && !empty($data->tipo_bloque_id)) {
        $sql .= ", tipo_bloque_id";
        $values .= ", :tipo_bloque_id";
    }
    
    // Añadir paciente_seguro_id si se proporciona
    if (isset($data->paciente_seguro_id) && !empty($data->paciente_seguro_id)) {
        $sql .= ", paciente_seguro_id";
        $values .= ", :paciente_seguro_id";
    }
    
    // Añadir horario_id si se proporciona (cuando se asigna directamente a un horario)
    if (isset($data->horario_id) && !empty($data->horario_id)) {
        $sql .= ", horario_id";
        $values .= ", :horario_id";
    }
    
    // Añadir fecha si se proporciona
    if (isset($data->fecha) && !empty($data->fecha)) {
        $sql .= ", fecha";
        $values .= ", :fecha";
    }
    
    // Añadir hora si se proporciona
    if (isset($data->hora) && !empty($data->hora)) {
        $sql .= ", hora";
        $values .= ", :hora";
    }
    
    // Añadir doctor_id si se proporciona (asignación directa)
    if (isset($data->doctor_id) && !empty($data->doctor_id)) {
        $sql .= ", doctor_id";
        $values .= ", :doctor_id";
    }
    
    $sql .= ") " . $values . ")";
    
    // Insertar cita
    $stmt = $conn->prepare($sql);
    
    $stmt->bindParam(':paciente_id', $data->paciente_id);
    $stmt->bindParam(':especialidad_id', $data->especialidad_id);
    $stmt->bindParam(':descripcion', $data->descripcion);
    $stmt->bindParam(':estado', $estadoInicial);
    $stmt->bindParam(':tipo_solicitante', $tipoSolicitante);
    $stmt->bindParam(':creado_por', $userData['id']);
    
    if (isset($data->tipo_bloque_id) && !empty($data->tipo_bloque_id)) {
        $stmt->bindParam(':tipo_bloque_id', $data->tipo_bloque_id);
    }
    
    if (isset($data->paciente_seguro_id) && !empty($data->paciente_seguro_id)) {
        $stmt->bindParam(':paciente_seguro_id', $data->paciente_seguro_id);
    }
    
    if (isset($data->horario_id) && !empty($data->horario_id)) {
        $stmt->bindParam(':horario_id', $data->horario_id);
    }
    
    if (isset($data->fecha) && !empty($data->fecha)) {
        $stmt->bindParam(':fecha', $data->fecha);
    }
    
    if (isset($data->hora) && !empty($data->hora)) {
        $stmt->bindParam(':hora', $data->hora);
    }
    
    if (isset($data->doctor_id) && !empty($data->doctor_id)) {
        $stmt->bindParam(':doctor_id', $data->doctor_id);
    }
    
    $stmt->execute();
    
    $citaId = $conn->lastInsertId();
    
    // Log de auditoría
    $logStmt = $conn->prepare("
        INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_nuevos, direccion_ip) 
        VALUES (:usuario_id, 'citas', :registro_id, 'crear', :datos_nuevos, :ip)
    ");
    
    $logStmt->bindParam(':usuario_id', $userData['id']);
    $logStmt->bindParam(':registro_id', $citaId);
    
    $datosNuevos = json_encode([
        'paciente_id' => $data->paciente_id,
        'especialidad_id' => $data->especialidad_id,
        'tipo_bloque_id' => $data->tipo_bloque_id ?? null,
        'paciente_seguro_id' => $data->paciente_seguro_id ?? null,
        'horario_id' => $data->horario_id ?? null,
        'fecha' => $data->fecha ?? null,
        'hora' => $data->hora ?? null,
        'doctor_id' => $data->doctor_id ?? null,
        'descripcion' => $data->descripcion,
        'tipo_solicitante' => $tipoSolicitante
    ]);
    $logStmt->bindParam(':datos_nuevos', $datosNuevos);
    
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logStmt->bindParam(':ip', $ip);
    
    $logStmt->execute();
    
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Cita creada exitosamente",
        "id" => $citaId
    ]);
    
} catch(PDOException $e) {
    error_log("Error al crear cita: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>