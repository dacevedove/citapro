<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
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
if (
    !isset($data->id) || 
    !isset($data->nombre_comercial) || 
    !isset($data->email) || 
    !isset($data->telefono) ||
    !isset($data->estado)
) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Obtener usuario_id de la aseguradora
    $stmt = $conn->prepare("SELECT usuario_id FROM aseguradoras WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    
    $aseguradora = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aseguradora) {
        throw new Exception("Aseguradora no encontrada");
    }
    
    // Actualizar información del usuario
    $stmt = $conn->prepare("
        UPDATE usuarios 
        SET email = :email, telefono = :telefono
        WHERE id = :usuario_id
    ");
    
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':telefono', $data->telefono);
    $stmt->bindParam(':usuario_id', $aseguradora['usuario_id']);
    
    $stmt->execute();
    
    // Actualizar información de la aseguradora
    $stmt = $conn->prepare("
        UPDATE aseguradoras 
        SET nombre_comercial = :nombre_comercial, estado = :estado
        WHERE id = :id
    ");
    
    $stmt->bindParam(':nombre_comercial', $data->nombre_comercial);
    $stmt->bindParam(':estado', $data->estado);
    $stmt->bindParam(':id', $data->id);
    
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    http_response_code(200);
    echo json_encode([
        "message" => "Aseguradora actualizada exitosamente"
    ]);
    
} catch(Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollBack();
    
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>