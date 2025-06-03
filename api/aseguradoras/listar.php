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
if (!$userData || ($userData['role'] !== 'admin')) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

try {
    // Consultar aseguradoras con información de usuarios
    $stmt = $conn->prepare("
        SELECT a.id, a.nombre_comercial, a.estado, u.email, u.telefono
        FROM aseguradoras a
        JOIN usuarios u ON a.usuario_id = u.id
    ");
    
    $stmt->execute();
    
    $aseguradoras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode($aseguradoras);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>