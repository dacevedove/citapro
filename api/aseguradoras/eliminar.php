<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Para manejar la solicitud OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
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
if (!$userData || ($userData['role'] !== 'admin')) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (!isset($data->id)) {
    http_response_code(400);
    echo json_encode(["error" => "ID no proporcionado"]);
    exit;
}

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Verificar si la aseguradora existe y obtener su usuario_id
    $stmt = $conn->prepare("SELECT usuario_id FROM aseguradoras WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    
    $aseguradora = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aseguradora) {
        throw new Exception("Aseguradora no encontrada");
    }
    
    // Verificar si existen titulares asociados
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM titulares WHERE aseguradora_id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        throw new Exception("No se puede eliminar la aseguradora porque tiene titulares asociados");
    }
    
    // Eliminar aseguradora
    $stmt = $conn->prepare("DELETE FROM aseguradoras WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    
    // Eliminar usuario asociado
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :usuario_id");
    $stmt->bindParam(':usuario_id', $aseguradora['usuario_id']);
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    http_response_code(200);
    echo json_encode([
        "message" => "Aseguradora eliminada exitosamente"
    ]);
    
} catch(Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollBack();
    
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>