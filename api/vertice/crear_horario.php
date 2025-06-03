<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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
if (
    !isset($data->doctor_id) || 
    (!isset($data->dia_semana) && !isset($data->fecha)) || 
    !isset($data->hora_inicio) || !isset($data->hora_fin)
) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Verificar si el doctor existe y está activo
    $stmt = $conn->prepare("
        SELECT d.id 
        FROM doctores d
        JOIN temp_doctores_activos tda ON d.id = tda.doctor_id
        WHERE d.id = :doctor_id AND tda.activo = 1
    ");
    $stmt->bindParam(':doctor_id', $data->doctor_id);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        throw new Exception("El doctor no existe o no está activo");
    }
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Determinar si usamos fecha específica o día recurrente
    $dia_semana = isset($data->dia_semana) ? $data->dia_semana : null;
    $fecha = isset($data->fecha) ? $data->fecha : null;
    
    // Comprobar si es la semana actual
    $es_semana_actual = false;
    if (isset($data->fecha_inicio) && isset($data->fecha_fin)) {
        $hoy = date('Y-m-d');
        $es_semana_actual = ($data->fecha_inicio <= $hoy && $data->fecha_fin >= $hoy);
    }
    
    // Para la semana actual, usamos día recurrente; para otras, usamos fecha específica
    if ($es_semana_actual) {
        $fecha = null; // No usar fecha específica para semana actual
    } else if (!$fecha && isset($data->dia_semana) && isset($data->fecha_inicio)) {
        // Calcular fecha específica basada en el día de la semana y la fecha de inicio
        $fecha_inicio = new DateTime($data->fecha_inicio);
        
        // Ajustar al día correcto dentro de la semana
        // El día de la semana en PHP: 1 (lunes) a 7 (domingo)
        $dia_actual = $fecha_inicio->format('N');
        $dias_a_sumar = $data->dia_semana - $dia_actual;
        if ($dias_a_sumar < 0) {
            $dias_a_sumar += 7;
        }
        
        $fecha_inicio->modify("+{$dias_a_sumar} days");
        $fecha = $fecha_inicio->format('Y-m-d');
    }
    
    // Si no tenemos día de semana pero tenemos fecha, calcular el día de la semana
    if (($dia_semana === null || $dia_semana === '') && $fecha) {
        $fecha_obj = new DateTime($fecha);
        $dia_semana = $fecha_obj->format('N'); // 1 (lunes) a 7 (domingo)
    }
    
    // Si aún no tenemos día de semana, usar un valor por defecto (1 = Lunes)
    if ($dia_semana === null || $dia_semana === '') {
        $dia_semana = 1;
    }
    
    // Insertar horario
    $stmt = $conn->prepare("
        INSERT INTO temp_horarios_doctores 
        (doctor_id, dia_semana, fecha, hora_inicio, hora_fin, creado_por) 
        VALUES 
        (:doctor_id, :dia_semana, :fecha, :hora_inicio, :hora_fin, :creado_por)
    ");
    
    $stmt->bindParam(':doctor_id', $data->doctor_id);
    $stmt->bindParam(':dia_semana', $dia_semana);
    $stmt->bindParam(':fecha', $fecha);
    $stmt->bindParam(':hora_inicio', $data->hora_inicio);
    $stmt->bindParam(':hora_fin', $data->hora_fin);
    $stmt->bindParam(':creado_por', $userData['id']);
    
    $stmt->execute();
    
    $horario_id = $conn->lastInsertId();
    
    // Generar turnos de media hora
    $hora_inicio = strtotime($data->hora_inicio);
    $hora_fin = strtotime($data->hora_fin);
    $intervalo = 30 * 60; // 30 minutos en segundos
    
    for ($hora = $hora_inicio; $hora < $hora_fin; $hora += $intervalo) {
        $inicio_turno = date('H:i:s', $hora);
        $fin_turno = date('H:i:s', $hora + $intervalo);
        
        $stmt = $conn->prepare("
            INSERT INTO temp_turnos_disponibles 
            (horario_id, hora_inicio, hora_fin, estado) 
            VALUES 
            (:horario_id, :hora_inicio, :hora_fin, 'disponible')
        ");
        
        $stmt->bindParam(':horario_id', $horario_id);
        $stmt->bindParam(':hora_inicio', $inicio_turno);
        $stmt->bindParam(':hora_fin', $fin_turno);
        
        $stmt->execute();
    }
    
    // Confirmar transacción
    $conn->commit();
    
    http_response_code(201);
    echo json_encode([
        "message" => "Horario creado exitosamente",
        "id" => $horario_id
    ]);
    
} catch(Exception $e) {
    // Revertir cambios en caso de error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>