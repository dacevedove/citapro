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

// Obtener datos enviados
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (!isset($data->solicitud_id)) {
    http_response_code(400);
    echo json_encode(["error" => "ID de solicitud requerido"]);
    exit;
}

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // 1. Obtener información de la asignación
    $stmt = $conn->prepare("
        SELECT ta.id, ta.turno_id, ta.doctor_id, c.id as cita_id
        FROM temp_asignaciones ta
        LEFT JOIN citas c ON c.estado = 'asignada'
        WHERE ta.solicitud_id = :solicitud_id
        LIMIT 1
    ");
    $stmt->bindParam(":solicitud_id", $data->solicitud_id);
    $stmt->execute();
    
    $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$asignacion) {
        throw new Exception("No se encontró la asignación para esta solicitud");
    }
    
    // 2. Liberar el turno
    if ($asignacion['turno_id']) {
        $stmt = $conn->prepare("
            UPDATE temp_turnos_disponibles
            SET estado = 'disponible'
            WHERE id = :turno_id
        ");
        $stmt->bindParam(":turno_id", $asignacion['turno_id']);
        $stmt->execute();
    }
    
    // 3. Cancelar la cita asociada
    if ($asignacion['cita_id']) {
        $stmt = $conn->prepare("
            UPDATE citas
            SET estado = 'cancelada'
            WHERE id = :cita_id
        ");
        $stmt->bindParam(":cita_id", $asignacion['cita_id']);
        $stmt->execute();
    }
    
    // 4. Eliminar la asignación
    $stmt = $conn->prepare("
        DELETE FROM temp_asignaciones
        WHERE id = :asignacion_id
    ");
    $stmt->bindParam(":asignacion_id", $asignacion['id']);
    $stmt->execute();
    
    // 5. Actualizar el estado de la solicitud a 'pendiente'
    $stmt = $conn->prepare("
        UPDATE temp_solicitudes
        SET estatus = 'pendiente'
        WHERE id = :solicitud_id
    ");
    $stmt->bindParam(":solicitud_id", $data->solicitud_id);
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    http_response_code(200);
    echo json_encode([
        "message" => "Asignación eliminada exitosamente",
        "solicitud_id" => $data->solicitud_id
    ]);
    
} catch(Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollBack();
    
    http_response_code(500);
    echo json_encode([
        "error" => "Error en el servidor: " . $e->getMessage()
    ]);
}
?>