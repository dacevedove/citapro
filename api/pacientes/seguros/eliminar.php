<?php
// api/pacientes/seguros/eliminar.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

include_once '../../config/database.php';
include_once '../../config/jwt.php';

// Obtener JWT del encabezado
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Validar token
$userData = validateJWT($jwt);
if (!$userData || ($userData['role'] !== 'admin' && $userData['role'] !== 'coordinador')) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id)) {
    http_response_code(400);
    echo json_encode(["error" => "ID del seguro requerido"]);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Obtener datos del seguro
    $stmt = $conn->prepare("SELECT * FROM pacientes_seguros WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    $seguro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seguro) {
        throw new Exception("Seguro no encontrado");
    }
    
    // Verificar si hay citas asociadas
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE paciente_seguro_id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        throw new Exception("No se puede eliminar el seguro porque tiene citas asociadas");
    }
    
    // Registrar en historial antes de eliminar
    $stmt = $conn->prepare("
        INSERT INTO pacientes_seguros_historial (
            paciente_seguro_id, accion, estado_anterior, datos_anteriores, realizado_por
        ) VALUES (
            :seguro_id, 'desactivacion', :estado, :datos, :usuario_id
        )
    ");
    
    $stmt->bindParam(':seguro_id', $data->id);
    $stmt->bindParam(':estado', $seguro['estado']);
    $stmt->bindParam(':datos', json_encode($seguro));
    $stmt->bindParam(':usuario_id', $userData['id']);
    $stmt->execute();
    
    // Eliminar seguro
    $stmt = $conn->prepare("DELETE FROM pacientes_seguros WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    
    $conn->commit();
    
    http_response_code(200);
    echo json_encode(["message" => "Seguro eliminado exitosamente"]);
    
} catch(Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>