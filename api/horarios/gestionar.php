<?php
// api/horarios/gestionar.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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

// Verificar permisos (admin, doctor, coordinador)
if (!in_array($userData['role'], ['admin', 'doctor', 'coordinador'])) {
    http_response_code(403);
    echo json_encode(["error" => "No tiene permisos para esta acción"]);
    exit;
}

// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            listarHorarios();
            break;
            
        case 'POST':
            crearHorario();
            break;
            
        case 'PUT':
            actualizarHorario();
            break;
            
        case 'DELETE':
            eliminarHorario();
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido"]);
            break;
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}

function listarHorarios() {
    global $conn, $userData;
    
    $doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
    $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
    
    // Si es doctor, solo puede ver sus propios horarios
    if ($userData['role'] === 'doctor') {
        $stmt = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$doctor) {
            http_response_code(404);
            echo json_encode(["error" => "Doctor no encontrado"]);
            return;
        }
        
        $doctor_id = $doctor['id'];
    }
    
    if (!$doctor_id) {
        http_response_code(400);
        echo json_encode(["error" => "ID de doctor requerido"]);
        return;
    }
    
    // Construir consulta
    $sql = "SELECT h.*, tb.nombre as tipo_nombre, tb.color, tb.descripcion as tipo_descripcion
            FROM horarios_doctores h
            JOIN tipos_bloque_horario tb ON h.tipo_bloque_id = tb.id
            WHERE h.doctor_id = :doctor_id AND h.activo = 1";
    
    $params = [':doctor_id' => $doctor_id];
    
    if ($fecha_inicio) {
        // Calcular fecha fin de la semana
        $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . ' +6 days'));
        $sql .= " AND h.fecha_inicio BETWEEN :fecha_inicio AND :fecha_fin";
        $params[':fecha_inicio'] = $fecha_inicio;
        $params[':fecha_fin'] = $fecha_fin;
    }
    
    $sql .= " ORDER BY h.fecha_inicio, h.dia_semana, h.hora_inicio";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->execute();
    
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos
    foreach ($horarios as &$horario) {
        $horario['duracion_minutos'] = calcularDuracionMinutos($horario['hora_inicio'], $horario['hora_fin']);
        $horario['activo'] = (bool)$horario['activo'];
    }
    
    http_response_code(200);
    echo json_encode($horarios);
}

function crearHorario() {
    global $conn, $userData;
    
    $data = json_decode(file_get_contents("php://input"));
    
    // Validar datos requeridos
    $camposRequeridos = ['doctor_id', 'tipo_bloque_id', 'fecha', 'dia_semana', 'hora_inicio', 'hora_fin'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($data->$campo) || empty($data->$campo)) {
            http_response_code(400);
            echo json_encode(["error" => "Campo requerido: $campo"]);
            return;
        }
    }
    
    // Verificar permisos sobre el doctor
    if (!verificarPermisoDoctor($data->doctor_id)) {
        http_response_code(403);
        echo json_encode(["error" => "No tiene permisos para gestionar este doctor"]);
        return;
    }
    
    // Validar horarios
    if ($data->hora_inicio >= $data->hora_fin) {
        http_response_code(400);
        echo json_encode(["error" => "La hora de inicio debe ser menor que la hora de fin"]);
        return;
    }
    
    // Calcular fecha de inicio de la semana
    $fecha_inicio_semana = calcularInicioSemana($data->fecha);
    
    // Verificar solapamientos
    if (verificarSolapamiento($data->doctor_id, $fecha_inicio_semana, $data->dia_semana, $data->hora_inicio, $data->hora_fin)) {
        http_response_code(400);
        echo json_encode(["error" => "Ya existe un horario que se solapa con este período"]);
        return;
    }
    
    // Calcular duración en minutos
    $duracion = calcularDuracionMinutos($data->hora_inicio, $data->hora_fin);
    
    $stmt = $conn->prepare("
        INSERT INTO horarios_doctores 
        (doctor_id, tipo_bloque_id, fecha_inicio, dia_semana, hora_inicio, hora_fin, duracion_minutos, notas, creado_por) 
        VALUES (:doctor_id, :tipo_bloque_id, :fecha_inicio, :dia_semana, :hora_inicio, :hora_fin, :duracion_minutos, :notas, :creado_por)
    ");
    
    $stmt->bindParam(':doctor_id', $data->doctor_id);
    $stmt->bindParam(':tipo_bloque_id', $data->tipo_bloque_id);
    $stmt->bindParam(':fecha_inicio', $fecha_inicio_semana);
    $stmt->bindParam(':dia_semana', $data->dia_semana);
    $stmt->bindParam(':hora_inicio', $data->hora_inicio);
    $stmt->bindParam(':hora_fin', $data->hora_fin);
    $stmt->bindParam(':duracion_minutos', $duracion);
    $notas = isset($data->notas) ? $data->notas : null;
    $stmt->bindParam(':notas', $notas);
    $stmt->bindParam(':creado_por', $userData['id']);
    
    $stmt->execute();
    
    $horario_id = $conn->lastInsertId();
    
    // Registrar en logs
    registrarLogHorario($horario_id, 'crear', null, [
        'doctor_id' => $data->doctor_id,
        'tipo_bloque_id' => $data->tipo_bloque_id,
        'fecha_inicio' => $fecha_inicio_semana,
        'dia_semana' => $data->dia_semana,
        'hora_inicio' => $data->hora_inicio,
        'hora_fin' => $data->hora_fin
    ]);
    
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Horario creado exitosamente",
        "id" => $horario_id
    ]);
}

function actualizarHorario() {
    global $conn, $userData;
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->id)) {
        http_response_code(400);
        echo json_encode(["error" => "ID de horario requerido"]);
        return;
    }
    
    // Obtener horario actual
    $stmt = $conn->prepare("SELECT * FROM horarios_doctores WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    $horarioActual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$horarioActual) {
        http_response_code(404);
        echo json_encode(["error" => "Horario no encontrado"]);
        return;
    }
    
    // Verificar permisos
    if (!verificarPermisoDoctor($horarioActual['doctor_id'])) {
        http_response_code(403);
        echo json_encode(["error" => "No tiene permisos para gestionar este horario"]);
        return;
    }
    
    // Construir campos a actualizar
    $updates = [];
    $params = [':id' => $data->id];
    
    if (isset($data->tipo_bloque_id)) {
        $updates[] = "tipo_bloque_id = :tipo_bloque_id";
        $params[':tipo_bloque_id'] = $data->tipo_bloque_id;
    }
    
    if (isset($data->fecha)) {
        $fecha_inicio_semana = calcularInicioSemana($data->fecha);
        $updates[] = "fecha_inicio = :fecha_inicio";
        $params[':fecha_inicio'] = $fecha_inicio_semana;
    }
    
    if (isset($data->dia_semana)) {
        $updates[] = "dia_semana = :dia_semana";
        $params[':dia_semana'] = $data->dia_semana;
    }
    
    if (isset($data->hora_inicio)) {
        $updates[] = "hora_inicio = :hora_inicio";
        $params[':hora_inicio'] = $data->hora_inicio;
    }
    
    if (isset($data->hora_fin)) {
        $updates[] = "hora_fin = :hora_fin";
        $params[':hora_fin'] = $data->hora_fin;
    }
    
    if (isset($data->notas)) {
        $updates[] = "notas = :notas";
        $params[':notas'] = $data->notas;
    }
    
    if (isset($data->activo)) {
        $updates[] = "activo = :activo";
        $params[':activo'] = $data->activo;
    }
    
    // Validar horarios si se actualizan
    if (isset($data->hora_inicio) || isset($data->hora_fin)) {
        $nueva_hora_inicio = isset($data->hora_inicio) ? $data->hora_inicio : $horarioActual['hora_inicio'];
        $nueva_hora_fin = isset($data->hora_fin) ? $data->hora_fin : $horarioActual['hora_fin'];
        
        if ($nueva_hora_inicio >= $nueva_hora_fin) {
            http_response_code(400);
            echo json_encode(["error" => "La hora de inicio debe ser menor que la hora de fin"]);
            return;
        }
        
        // Actualizar duración
        $duracion = calcularDuracionMinutos($nueva_hora_inicio, $nueva_hora_fin);
        $updates[] = "duracion_minutos = :duracion_minutos";
        $params[':duracion_minutos'] = $duracion;
        
        // Verificar solapamientos (excluyendo el horario actual)
        $fecha_verificar = isset($data->fecha) ? calcularInicioSemana($data->fecha) : $horarioActual['fecha_inicio'];
        $dia_verificar = isset($data->dia_semana) ? $data->dia_semana : $horarioActual['dia_semana'];
        
        if (verificarSolapamiento($horarioActual['doctor_id'], $fecha_verificar, $dia_verificar, $nueva_hora_inicio, $nueva_hora_fin, $data->id)) {
            http_response_code(400);
            echo json_encode(["error" => "Ya existe un horario que se solapa con este período"]);
            return;
        }
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(["error" => "No hay campos para actualizar"]);
        return;
    }
    
    $sql = "UPDATE horarios_doctores SET " . implode(", ", $updates) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    
    // Registrar en logs
    registrarLogHorario($data->id, 'modificar', $horarioActual, array_filter((array)$data, function($key) {
        return $key !== 'id';
    }, ARRAY_FILTER_USE_KEY));
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Horario actualizado exitosamente"
    ]);
}

function eliminarHorario() {
    global $conn, $userData;
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->id)) {
        http_response_code(400);
        echo json_encode(["error" => "ID de horario requerido"]);
        return;
    }
    
    // Obtener horario actual
    $stmt = $conn->prepare("SELECT * FROM horarios_doctores WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$horario) {
        http_response_code(404);
        echo json_encode(["error" => "Horario no encontrado"]);
        return;
    }
    
    // Verificar permisos
    if (!verificarPermisoDoctor($horario['doctor_id'])) {
        http_response_code(403);
        echo json_encode(["error" => "No tiene permisos para gestionar este horario"]);
        return;
    }
    
    // Verificar si hay citas programadas en este horario
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM citas_horarios WHERE horario_id = :horario_id AND estado != 'cancelada'");
    $stmt->bindParam(':horario_id', $data->id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        http_response_code(400);
        echo json_encode(["error" => "No se puede eliminar el horario porque tiene citas programadas"]);
        return;
    }
    
    // Eliminar horario
    $stmt = $conn->prepare("DELETE FROM horarios_doctores WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    
    // Registrar en logs
    registrarLogHorario($data->id, 'eliminar', $horario, null);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Horario eliminado exitosamente"
    ]);
}

// Funciones auxiliares
function verificarPermisoDoctor($doctor_id) {
    global $userData, $conn;
    
    // Admin y coordinador pueden gestionar cualquier doctor
    if (in_array($userData['role'], ['admin', 'coordinador'])) {
        return true;
    }
    
    // Doctor solo puede gestionar sus propios horarios
    if ($userData['role'] === 'doctor') {
        $stmt = $conn->prepare("SELECT id FROM doctores WHERE id = :doctor_id AND usuario_id = :usuario_id");
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        
        return $stmt->fetch() !== false;
    }
    
    return false;
}

function calcularInicioSemana($fecha) {
    $timestamp = strtotime($fecha);
    $dia_semana = date('w', $timestamp); // 0 = domingo, 1 = lunes, etc.
    
    // Ajustar para que lunes sea el primer día
    $dias_hasta_lunes = ($dia_semana === 0) ? 6 : $dia_semana - 1;
    
    return date('Y-m-d', strtotime("-$dias_hasta_lunes days", $timestamp));
}

function calcularDuracionMinutos($hora_inicio, $hora_fin) {
    $inicio = strtotime($hora_inicio);
    $fin = strtotime($hora_fin);
    
    return ($fin - $inicio) / 60;
}

function verificarSolapamiento($doctor_id, $fecha_inicio, $dia_semana, $hora_inicio, $hora_fin, $excluir_id = null) {
    global $conn;
    
    $sql = "SELECT id FROM horarios_doctores 
            WHERE doctor_id = :doctor_id 
            AND fecha_inicio = :fecha_inicio 
            AND dia_semana = :dia_semana 
            AND activo = 1
            AND (
                (hora_inicio < :hora_fin AND hora_fin > :hora_inicio)
            )";
    
    $params = [
        ':doctor_id' => $doctor_id,
        ':fecha_inicio' => $fecha_inicio,
        ':dia_semana' => $dia_semana,
        ':hora_inicio' => $hora_inicio,
        ':hora_fin' => $hora_fin
    ];
    
    if ($excluir_id) {
        $sql .= " AND id != :excluir_id";
        $params[':excluir_id'] = $excluir_id;
    }
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->execute();
    
    return $stmt->fetch() !== false;
}

function registrarLogHorario($horario_id, $accion, $datos_anteriores, $datos_nuevos) {
    global $conn, $userData;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO logs_horarios (horario_id, accion, datos_anteriores, datos_nuevos, usuario_id) 
            VALUES (:horario_id, :accion, :datos_anteriores, :datos_nuevos, :usuario_id)
        ");
        
        $stmt->bindParam(':horario_id', $horario_id);
        $stmt->bindParam(':accion', $accion);
        $stmt->bindParam(':datos_anteriores', $datos_anteriores ? json_encode($datos_anteriores) : null);
        $stmt->bindParam(':datos_nuevos', $datos_nuevos ? json_encode($datos_nuevos) : null);
        $stmt->bindParam(':usuario_id', $userData['id']);
        
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error al registrar log de horario: " . $e->getMessage());
    }
}
?>