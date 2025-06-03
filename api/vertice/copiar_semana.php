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
    !isset($data->semana_origen_inicio) || 
    !isset($data->semana_origen_fin) || 
    !isset($data->semana_destino_inicio) || 
    !isset($data->semana_destino_fin)
) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // 1. Obtener horarios de la semana origen
    $sql = "SELECT * FROM temp_horarios_doctores 
            WHERE doctor_id = :doctor_id AND (
                (fecha IS NULL) OR 
                (fecha BETWEEN :fecha_inicio AND :fecha_fin)
            )";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':doctor_id', $data->doctor_id);
    $stmt->bindParam(':fecha_inicio', $data->semana_origen_inicio);
    $stmt->bindParam(':fecha_fin', $data->semana_origen_fin);
    $stmt->execute();
    
    $horarios_origen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($horarios_origen)) {
        throw new Exception("No hay horarios para copiar en la semana seleccionada");
    }
    
    // 2. Crear horarios en la semana destino
    $contador = 0;
    
    foreach ($horarios_origen as $horario) {
        // Determinar el día de la semana para este horario
        $dia_semana = $horario['dia_semana'];
        
        // Si tiene fecha, calcular el día de la semana
        if ($horario['fecha']) {
            $fecha_origen = new DateTime($horario['fecha']);
            $dia_semana = $fecha_origen->format('N'); // 1 (lunes) a 7 (domingo)
        }
        
        // Si no tenemos día de semana (aunque esto no debería ocurrir), usar un valor por defecto
        if (!$dia_semana) {
            $dia_semana = 1; // Lunes
        }
        
        // Calcular la fecha correspondiente en la semana destino
        $fecha_inicio_destino = new DateTime($data->semana_destino_inicio);
        $dias_a_sumar = $dia_semana - 1; // Lunes = 0, Martes = 1, etc.
        $fecha_inicio_destino->modify("+{$dias_a_sumar} days");
        $fecha_especifica = $fecha_inicio_destino->format('Y-m-d');
        
        // Comprobar si es la semana actual
        $hoy = date('Y-m-d');
        $es_semana_destino_actual = ($data->semana_destino_inicio <= $hoy && $data->semana_destino_fin >= $hoy);
        
        // Para semana actual, usamos días recurrentes; para otras, usamos fecha específica
        $nueva_fecha = null;
        $nuevo_dia_semana = null;
        
        if ($es_semana_destino_actual) {
            $nuevo_dia_semana = $dia_semana;
        } else {
            $nueva_fecha = $fecha_especifica;
            $nuevo_dia_semana = $dia_semana; // Mantenemos el día también para evitar errores de nulos
        }
        
        // Crear el nuevo horario
        $stmt = $conn->prepare("
            INSERT INTO temp_horarios_doctores 
            (doctor_id, dia_semana, fecha, hora_inicio, hora_fin, creado_por) 
            VALUES 
            (:doctor_id, :dia_semana, :fecha, :hora_inicio, :hora_fin, :creado_por)
        ");
        
        $stmt->bindParam(':doctor_id', $data->doctor_id);
        $stmt->bindParam(':dia_semana', $nuevo_dia_semana);
        $stmt->bindParam(':fecha', $nueva_fecha);
        $stmt->bindParam(':hora_inicio', $horario['hora_inicio']);
        $stmt->bindParam(':hora_fin', $horario['hora_fin']);
        $stmt->bindParam(':creado_por', $userData['id']);
        
        $stmt->execute();
        
        $horario_id = $conn->lastInsertId();
        
        // Generar turnos para este horario
        $hora_inicio = strtotime($horario['hora_inicio']);
        $hora_fin = strtotime($horario['hora_fin']);
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
        
        $contador++;
    }
    
    // Confirmar transacción
    $conn->commit();
    
    http_response_code(200);
    echo json_encode([
        "message" => "Horarios copiados exitosamente",
        "copiados" => $contador
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