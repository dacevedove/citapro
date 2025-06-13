<?php
// En la función listarHorarios(), agregar estos logs de debug:

function listarHorarios() {
    global $conn, $userData;
    
    $doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
    $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
    
    // LOG DE DEBUG
    error_log("=== DEBUG LISTAR HORARIOS ===");
    error_log("doctor_id recibido: " . ($doctor_id ?? 'NULL'));
    error_log("fecha_inicio recibida: " . ($fecha_inicio ?? 'NULL'));
    error_log("Parámetros GET completos: " . json_encode($_GET));
    
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
        error_log("Doctor ID desde usuario: " . $doctor_id);
    }
    
    if (!$doctor_id) {
        http_response_code(400);
        echo json_encode(["error" => "ID de doctor requerido"]);
        return;
    }
    
    // CONSULTA SIMPLIFICADA PARA DEBUG
    $sql = "SELECT h.*, tb.nombre as tipo_nombre, tb.color, tb.descripcion as tipo_descripcion
            FROM horarios_doctores h
            JOIN tipos_bloque_horario tb ON h.tipo_bloque_id = tb.id
            WHERE h.doctor_id = :doctor_id AND h.activo = 1";
    
    $params = [':doctor_id' => $doctor_id];
    
    // Agregar filtro de fecha solo si se proporciona
    if ($fecha_inicio) {
        // CAMBIAR LA LÓGICA DE FILTRO DE FECHA
        // En lugar de buscar por fecha_inicio exacta, buscar registros de esa semana
        $sql .= " AND h.fecha_inicio = :fecha_inicio";
        $params[':fecha_inicio'] = $fecha_inicio;
        error_log("Agregando filtro de fecha_inicio: " . $fecha_inicio);
    }
    
    $sql .= " ORDER BY h.fecha_inicio, h.dia_semana, h.hora_inicio";
    
    error_log("SQL final: " . $sql);
    error_log("Parámetros SQL: " . json_encode($params));
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->execute();
    
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Horarios encontrados: " . count($horarios));
    error_log("Detalles de horarios: " . json_encode($horarios));
    
    // Formatear datos
    foreach ($horarios as &$horario) {
        $horario['duracion_minutos'] = calcularDuracionMinutos($horario['hora_inicio'], $horario['hora_fin']);
        $horario['activo'] = (bool)$horario['activo'];
    }
    
    error_log("=== FIN DEBUG LISTAR HORARIOS ===");
    
    http_response_code(200);
    echo json_encode($horarios);
}

// También agregar esta función de debug temporal para verificar qué hay en la BD:
function debugBaseDatos() {
    global $conn;
    
    error_log("=== DEBUG BASE DE DATOS ===");
    
    // Verificar todos los horarios en la tabla
    $stmt = $conn->prepare("SELECT * FROM horarios_doctores ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Últimos 5 horarios en BD: " . json_encode($todos));
    
    // Verificar tipos de bloque
    $stmt = $conn->prepare("SELECT * FROM tipos_bloque_horario WHERE activo = 1");
    $stmt->execute();
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Tipos de bloque activos: " . json_encode($tipos));
    
    error_log("=== FIN DEBUG BASE DE DATOS ===");
}

// Llamar debug al inicio
debugBaseDatos();
?>