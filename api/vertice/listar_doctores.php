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
    // Consultar doctores con información de activación
    $stmt = $conn->prepare("
        SELECT d.id, u.nombre, u.apellido, e.nombre as especialidad, 
               IFNULL(tda.activo, 0) as activo 
        FROM doctores d
        JOIN usuarios u ON d.usuario_id = u.id
        JOIN especialidades e ON d.especialidad_id = e.id
        LEFT JOIN temp_doctores_activos tda ON d.id = tda.doctor_id
        ORDER BY u.apellido, u.nombre
    ");
    $stmt->execute();
    
    $doctores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode(["data" => $doctores]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}