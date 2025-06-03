<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
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

// Verificar si se proporcionó el ID de la solicitud
if (!isset($_GET['solicitud_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID de solicitud requerido"]);
    exit;
}

$solicitudId = $_GET['solicitud_id'];

try {
    // Consulta optimizada para obtener la información de la asignación
    $sql = "
        SELECT 
            ta.id as asignacion_id,
            ta.solicitud_id,
            ta.turno_id,
            ta.doctor_id,
            ta.estado as asignacion_estado,
            
            c.id as cita_id,
            c.fecha as fecha_cita,
            c.hora as hora_inicio,
            c.estado as cita_estado,
            
            d.id as doctor_id,
            CONCAT(u.nombre, ' ', u.apellido) as doctor_nombre,
            e.nombre as especialidad_nombre,
            
            co.id as consultorio_id,
            co.nombre as consultorio_nombre,
            co.ubicacion as consultorio_ubicacion,
            
            ttd.hora_inicio as turno_hora_inicio,
            ttd.hora_fin as turno_hora_fin,
            thd.fecha as horario_fecha
            
        FROM temp_asignaciones ta
        LEFT JOIN doctores d ON ta.doctor_id = d.id
        LEFT JOIN usuarios u ON d.usuario_id = u.id
        LEFT JOIN especialidades e ON d.especialidad_id = e.id
        LEFT JOIN citas c ON ta.solicitud_id = :solicitud_id AND c.estado = 'asignada'
        LEFT JOIN consultorios co ON c.consultorio_id = co.id
        LEFT JOIN temp_turnos_disponibles ttd ON ta.turno_id = ttd.id
        LEFT JOIN temp_horarios_doctores thd ON ttd.horario_id = thd.id
        WHERE ta.solicitud_id = :solicitud_id
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':solicitud_id', $solicitudId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Usar los datos del turno si no hay datos en la cita
        if (empty($asignacion['fecha_cita']) && !empty($asignacion['horario_fecha'])) {
            $asignacion['fecha_cita'] = $asignacion['horario_fecha'];
        }
        
        if (empty($asignacion['hora_inicio']) && !empty($asignacion['turno_hora_inicio'])) {
            $asignacion['hora_inicio'] = $asignacion['turno_hora_inicio'];
        }
        
        // Registrar datos para depuración
        error_log("Datos de asignación para solicitud ID $solicitudId: " . json_encode($asignacion));
        
        http_response_code(200);
        echo json_encode([
            "message" => "Datos de asignación obtenidos exitosamente",
            "data" => $asignacion
        ]);
    } else {
        // Si no se encuentra asignación con la consulta principal, intentar buscar información básica
        $sqlSimple = "
            SELECT 
                ta.id as asignacion_id,
                ta.solicitud_id,
                ta.turno_id,
                ta.doctor_id,
                ta.estado as asignacion_estado,
                
                d.id as doctor_id,
                CONCAT(u.nombre, ' ', u.apellido) as doctor_nombre,
                e.nombre as especialidad_nombre
                
            FROM temp_asignaciones ta
            LEFT JOIN doctores d ON ta.doctor_id = d.id
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            LEFT JOIN especialidades e ON d.especialidad_id = e.id
            WHERE ta.solicitud_id = :solicitud_id
            LIMIT 1
        ";
        
        $stmtSimple = $conn->prepare($sqlSimple);
        $stmtSimple->bindParam(':solicitud_id', $solicitudId);
        $stmtSimple->execute();
        
        if ($stmtSimple->rowCount() > 0) {
            $asignacionSimple = $stmtSimple->fetch(PDO::FETCH_ASSOC);
            
            // Consulta específica para obtener la fecha y hora del turno
            $sqlTurno = "
                SELECT 
                    ttd.hora_inicio,
                    ttd.hora_fin,
                    thd.fecha
                FROM temp_asignaciones ta
                JOIN temp_turnos_disponibles ttd ON ta.turno_id = ttd.id
                JOIN temp_horarios_doctores thd ON ttd.horario_id = thd.id
                WHERE ta.solicitud_id = :solicitud_id
                LIMIT 1
            ";
            
            $stmtTurno = $conn->prepare($sqlTurno);
            $stmtTurno->bindParam(':solicitud_id', $solicitudId);
            $stmtTurno->execute();
            
            if ($stmtTurno->rowCount() > 0) {
                $turnoData = $stmtTurno->fetch(PDO::FETCH_ASSOC);
                $asignacionSimple['fecha_cita'] = $turnoData['fecha'];
                $asignacionSimple['hora_inicio'] = $turnoData['hora_inicio'];
                $asignacionSimple['hora_fin'] = $turnoData['hora_fin'];
            }
            
            http_response_code(200);
            echo json_encode([
                "message" => "Datos básicos de asignación obtenidos",
                "data" => $asignacionSimple
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                "message" => "No se encontró asignación para esta solicitud",
                "data" => null
            ]);
        }
    }
    
} catch(PDOException $e) {
    error_log("Error en obtener_asignacion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => "Error en el servidor: " . $e->getMessage(),
        "data" => null
    ]);
}