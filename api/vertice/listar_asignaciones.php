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

try {
    // Consultar asignaciones
    $stmt = $conn->prepare("
        SELECT a.*, 
               s.nombre_paciente, s.cedula_paciente, s.cedula_titular,
               u.nombre as doctor_nombre, u.apellido as doctor_apellido,
               thd.fecha, td.hora_inicio, td.hora_fin
        FROM temp_asignaciones a
        JOIN temp_solicitudes s ON a.solicitud_id = s.id
        JOIN temp_turnos_disponibles td ON a.turno_id = td.id
        JOIN temp_horarios_doctores thd ON td.horario_id = thd.id
        JOIN doctores d ON a.doctor_id = d.id
        JOIN usuarios u ON d.usuario_id = u.id
        ORDER BY thd.fecha, td.hora_inicio
    ");
    
    $stmt->execute();
    
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode(["data" => $asignaciones]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}