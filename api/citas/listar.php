<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
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

// Obtener parámetros de filtro
$doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
$paciente_id = isset($_GET['paciente_id']) ? $_GET['paciente_id'] : null;
$especialidad_id = isset($_GET['especialidad_id']) ? $_GET['especialidad_id'] : null;
$estado = isset($_GET['estado']) ? $_GET['estado'] : null;
$asignacion_libre = isset($_GET['asignacion_libre']) ? $_GET['asignacion_libre'] : null;
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;

try {
    // Construir consulta SQL base
    $sql = "
        SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, 
               p.telefono as paciente_telefono, p.email as paciente_email,
               e.nombre as especialidad, 
               CONCAT(u.nombre, ' ', u.apellido) as doctor_nombre,
               co.nombre as consultorio_nombre, co.ubicacion as consultorio_ubicacion
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN especialidades e ON c.especialidad_id = e.id
        LEFT JOIN doctores d ON c.doctor_id = d.id
        LEFT JOIN usuarios u ON d.usuario_id = u.id
        LEFT JOIN consultorios co ON c.consultorio_id = co.id
    ";
    
    // Preparar condiciones según filtros y rol de usuario
    $condiciones = [];
    $params = [];
    
    // Filtrar según rol de usuario
    if ($userData['role'] === 'doctor') {
        // Obtener ID del doctor
        $stmt = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doctor) {
            if ($asignacion_libre && $asignacion_libre == 1) {
                // Doctor viendo citas de asignación libre de su especialidad
                $stmt = $conn->prepare("SELECT especialidad_id FROM doctores WHERE id = :doctor_id");
                $stmt->bindParam(':doctor_id', $doctor['id']);
                $stmt->execute();
                $doctorEsp = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($doctorEsp) {
                    $condiciones[] = "(c.asignacion_libre = 1 AND c.especialidad_id = :especialidad_id)";
                    $params[':especialidad_id'] = $doctorEsp['especialidad_id'];
                }
            } else {
                // Doctor viendo sus propias citas
                $condiciones[] = "c.doctor_id = :doctor_id";
                $params[':doctor_id'] = $doctor['id'];
            }
        }
    } else if ($userData['role'] === 'aseguradora') {
        // Aseguradora viendo citas que ha creado
        $condiciones[] = "c.creado_por = :creado_por";
        $params[':creado_por'] = $userData['id'];
    } else if ($userData['role'] === 'paciente') {
        // Paciente viendo sus propias citas
        $stmt = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($paciente) {
            $condiciones[] = "c.paciente_id = :paciente_id";
            $params[':paciente_id'] = $paciente['id'];
        }
    }
    
    // Aplicar filtros adicionales
    if ($doctor_id && $userData['role'] !== 'doctor') {
        $condiciones[] = "c.doctor_id = :doctor_id";
        $params[':doctor_id'] = $doctor_id;
    }
    
    if ($paciente_id && $userData['role'] !== 'paciente') {
        $condiciones[] = "c.paciente_id = :paciente_id";
        $params[':paciente_id'] = $paciente_id;
    }
    
    if ($especialidad_id) {
        $condiciones[] = "c.especialidad_id = :especialidad_id";
        $params[':especialidad_id'] = $especialidad_id;
    }
    
    if ($estado) {
        $condiciones[] = "c.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    if ($asignacion_libre && $userData['role'] !== 'doctor') {
        $condiciones[] = "c.asignacion_libre = :asignacion_libre";
        $params[':asignacion_libre'] = $asignacion_libre;
    }
    
    if ($fecha_desde) {
        $condiciones[] = "c.fecha >= :fecha_desde";
        $params[':fecha_desde'] = $fecha_desde;
    }
    
    if ($fecha_hasta) {
        $condiciones[] = "c.fecha <= :fecha_hasta";
        $params[':fecha_hasta'] = $fecha_hasta;
    }
    
    // Añadir condiciones a la consulta
    if (!empty($condiciones)) {
        $sql .= " WHERE " . implode(" AND ", $condiciones);
    }
    
    // Ordenar resultados
    $sql .= " ORDER BY c.creado_en DESC";
    
    // Preparar y ejecutar consulta
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode($citas);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>