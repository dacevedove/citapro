<?php
// api/pacientes/seguros/test.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../config/jwt.php';

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

// Obtener ID del paciente
$paciente_id = isset($_GET['paciente_id']) ? $_GET['paciente_id'] : null;

if (!$paciente_id) {
    http_response_code(400);
    echo json_encode(["error" => "ID del paciente requerido"]);
    exit;
}

try {
    // Verificar que el paciente existe
    $stmt = $conn->prepare("SELECT id, nombre, apellido FROM pacientes WHERE id = :paciente_id");
    $stmt->bindParam(':paciente_id', $paciente_id);
    $stmt->execute();
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paciente) {
        http_response_code(404);
        echo json_encode(["error" => "Paciente no encontrado"]);
        exit;
    }
    
    // Verificar que la tabla de seguros existe
    $stmt = $conn->prepare("SHOW TABLES LIKE 'pacientes_seguros'");
    $stmt->execute();
    $tabla_existe = $stmt->fetch();
    
    if (!$tabla_existe) {
        http_response_code(500);
        echo json_encode(["error" => "Tabla pacientes_seguros no existe"]);
        exit;
    }
    
    // Contar seguros del paciente
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pacientes_seguros WHERE paciente_id = :paciente_id");
    $stmt->bindParam(':paciente_id', $paciente_id);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "message" => "Test exitoso",
        "paciente" => $paciente,
        "total_seguros" => $count['total'],
        "usuario_autenticado" => $userData['username'] ?? 'usuario'
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>