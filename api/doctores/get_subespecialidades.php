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
if (!$userData) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Verificar si se proporcionó el ID de especialidad
$especialidad_id = isset($_GET['especialidad_id']) ? $_GET['especialidad_id'] : null;

if (!$especialidad_id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de especialidad requerido"]);
    exit;
}

try {
    // Obtener subespecialidades por especialidad
    $stmt = $conn->prepare("
        SELECT id, nombre, descripcion 
        FROM subespecialidades 
        WHERE especialidad_id = :especialidad_id 
        ORDER BY nombre
    ");
    $stmt->bindParam(':especialidad_id', $especialidad_id);
    $stmt->execute();
    $subespecialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode($subespecialidades);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>