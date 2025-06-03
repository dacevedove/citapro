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
if (!$userData) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (!isset($data->cita_id)) {
    http_response_code(400);
    echo json_encode(["error" => "ID de cita no proporcionado"]);
    exit;
}

try {
    // Verificar que la cita existe
    $stmt = $conn->prepare("SELECT * FROM citas WHERE id = :cita_id");
    $stmt->bindParam(':cita_id', $data->cita_id);
    $stmt->execute();
    
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cita) {
        http_response_code(404);
        echo json_encode(["error" => "Cita no encontrada"]);
        exit;
    }
    
    // Verificar permisos según el rol
    $puedeActualizar = false;
    
    if ($userData['role'] === 'admin') {
        // Admin puede actualizar cualquier cita
        $puedeActualizar = true;
    } else if ($userData['role'] === 'doctor' && $cita['doctor_id']) {
        // Verificar si el doctor está asignado a esta cita
        $stmt = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doctor && $doctor['id'] === $cita['doctor_id']) {
            $puedeActualizar = true;
        }
    } else if ($userData['role'] === 'aseguradora' || $userData['role'] === 'paciente') {
        // Verificar si la aseguradora o paciente creó esta cita
        if ($cita['creado_por'] === $userData['id']) {
            $puedeActualizar = true;
        }
    }
    
    if (!$puedeActualizar) {
        http_response_code(403);
        echo json_encode(["error" => "No tiene permisos para actualizar esta cita"]);
        exit;
    }
    
    // Preparar campos para actualización
    $campos = [];
    $valores = [];
    
    if (isset($data->doctor_id)) {
        $campos[] = "doctor_id = :doctor_id";
        $valores[':doctor_id'] = $data->doctor_id;
    }
    
    if (isset($data->especialidad_id)) {
        $campos[] = "especialidad_id = :especialidad_id";
        $valores[':especialidad_id'] = $data->especialidad_id;
    }
    
    if (isset($data->consultorio_id)) {
        $campos[] = "consultorio_id = :consultorio_id";
        $valores[':consultorio_id'] = $data->consultorio_id;
    }
    
    if (isset($data->fecha)) {
        $campos[] = "fecha = :fecha";
        $valores[':fecha'] = $data->fecha;
    }
    
    if (isset($data->hora)) {
        $campos[] = "hora = :hora";
        $valores[':hora'] = $data->hora;
    }
    
    if (isset($data->estado)) {
        $campos[] = "estado = :estado";
        $valores[':estado'] = $data->estado;
    }
    
    if (isset($data->descripcion)) {
        $campos[] = "descripcion = :descripcion";
        $valores[':descripcion'] = $data->descripcion;
    }
    
    if (isset($data->asignacion_libre)) {
        $campos[] = "asignacion_libre = :asignacion_libre";
        $valores[':asignacion_libre'] = $data->asignacion_libre ? 1 : 0;
    }
    
    if (isset($data->motivo_cancelacion)) {
        $campos[] = "motivo_cancelacion = :motivo_cancelacion";
        $valores[':motivo_cancelacion'] = $data->motivo_cancelacion;
    }
    
    if (isset($data->observaciones)) {
        $campos[] = "observaciones = :observaciones";
        $valores[':observaciones'] = $data->observaciones;
    }
    
    // Verificar si hay campos para actualizar
    if (empty($campos)) {
        http_response_code(400);
        echo json_encode(["error" => "No se proporcionaron campos para actualizar"]);
        exit;
    }
    
    // Construir consulta SQL
    $sql = "UPDATE citas SET " . implode(", ", $campos) . " WHERE id = :cita_id";
    $valores[':cita_id'] = $data->cita_id;
    
    // Ejecutar actualización
    $stmt = $conn->prepare($sql);
    foreach ($valores as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->execute();
    
    // Verificar si la actualización cambió el estado a 'confirmada'
    if (isset($data->estado) && $data->estado === 'confirmada') {
        // Enviar notificaciones
        include_once '../notificaciones/email.php';
        include_once '../notificaciones/sms.php';
        
        // Enviar email de confirmación
        notificarConfirmacionCita($data->cita_id, $conn);
        
        // Enviar SMS de confirmación
        notificarConfirmacionCitaSMS($data->cita_id, $conn);
    }
    
    http_response_code(200);
    echo json_encode([
        "message" => "Cita actualizada exitosamente",
        "cita_id" => $data->cita_id
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>