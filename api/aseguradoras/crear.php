<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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
if (!$userData || ($userData['role'] !== 'admin')) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (
    !isset($data->nombre_comercial) || 
    !isset($data->email) || 
    !isset($data->password) || 
    !isset($data->cedula) ||
    !isset($data->telefono)
) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Crear usuario para la aseguradora
    $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO usuarios (nombre, apellido, email, password, cedula, telefono, role) 
        VALUES (:nombre, :apellido, :email, :password, :cedula, :telefono, 'aseguradora')
    ");
    
    $stmt->bindParam(':nombre', $data->nombre);
    $stmt->bindParam(':apellido', $data->apellido);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':password', $password_hash);
    $stmt->bindParam(':cedula', $data->cedula);
    $stmt->bindParam(':telefono', $data->telefono);
    
    $stmt->execute();
    
    $usuario_id = $conn->lastInsertId();
    
    // Crear aseguradora
    $stmt = $conn->prepare("
        INSERT INTO aseguradoras (usuario_id, nombre_comercial) 
        VALUES (:usuario_id, :nombre_comercial)
    ");
    
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->bindParam(':nombre_comercial', $data->nombre_comercial);
    
    $stmt->execute();
    
    $aseguradora_id = $conn->lastInsertId();
    
    // Confirmar transacción
    $conn->commit();
    
    http_response_code(201);
    echo json_encode([
        "message" => "Aseguradora creada exitosamente",
        "id" => $aseguradora_id
    ]);
    
} catch(PDOException $e) {
    // Revertir cambios en caso de error
    $conn->rollBack();
    
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>