<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
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

// Obtener ID del horario
$horario_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$horario_id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de horario no proporcionado"]);
    exit;
}

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Eliminar primero los turnos asociados a este horario
    $stmt = $conn->prepare("DELETE FROM temp_turnos_disponibles WHERE horario_id = :horario_id");
    $stmt->bindParam(':horario_id', $horario_id);
    $stmt->execute();
    
    // Luego eliminar el horario
    $stmt = $conn->prepare("DELETE FROM temp_horarios_doctores WHERE id = :horario_id");
    $stmt->bindParam(':horario_id', $horario_id);
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    http_response_code(200);
    echo json_encode(["message" => "Horario eliminado exitosamente"]);
    
} catch(Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollBack();
    
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}