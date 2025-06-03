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

// Validar ID
if (!isset($data->id)) {
    http_response_code(400);
    echo json_encode(["error" => "ID de solicitud no proporcionado"]);
    exit;
}

try {
    // Verificar que la solicitud existe
    $stmt = $conn->prepare("SELECT id, estatus FROM temp_solicitudes WHERE id = :id");
    $stmt->bindParam(":id", $data->id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Solicitud no encontrada"]);
        exit;
    }
    
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar si hay asignaciones relacionadas con esta solicitud
    $stmt = $conn->prepare("SELECT COUNT(*) FROM temp_asignaciones WHERE solicitud_id = :solicitud_id");
    $stmt->bindParam(":solicitud_id", $data->id);
    $stmt->execute();
    $asignacionesCount = $stmt->fetchColumn();
    
    // Si hay asignaciones, no permitir eliminar
    if ($asignacionesCount > 0) {
        http_response_code(400);
        echo json_encode([
            "error" => "No se puede eliminar la solicitud porque tiene asignaciones asociadas",
            "asignaciones" => $asignacionesCount
        ]);
        exit;
    }
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Eliminar la solicitud
    $stmt = $conn->prepare("DELETE FROM temp_solicitudes WHERE id = :id");
    $stmt->bindParam(":id", $data->id);
    
    if ($stmt->execute()) {
        // Confirmar transacción
        $conn->commit();
        
        http_response_code(200);
        echo json_encode([
            "message" => "Solicitud eliminada exitosamente",
            "id" => $data->id
        ]);
    } else {
        // Revertir transacción en caso de error
        $conn->rollBack();
        
        http_response_code(500);
        echo json_encode(["error" => "Error al eliminar la solicitud"]);
    }
    
} catch(PDOException $e) {
    // Revertir transacción en caso de excepción
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        "error" => "Error en el servidor: " . $e->getMessage()
    ]);
}
?>