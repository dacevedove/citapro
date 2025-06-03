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

// Verificar que se proporcionó la cédula
if (!isset($_GET['cedula'])) {
    http_response_code(400);
    echo json_encode(["error" => "Debe proporcionar una cédula para la búsqueda"]);
    exit;
}

$cedula = $_GET['cedula'];

try {
    // Buscar el titular por cédula
    $sql = "
        SELECT t.*, a.nombre_comercial as aseguradora_nombre
        FROM titulares t
        LEFT JOIN aseguradoras a ON t.aseguradora_id = a.id
        WHERE t.cedula = :cedula
    ";
    
    // Filtrar según rol de usuario
    if ($userData['role'] === 'aseguradora') {
        $stmt = $conn->prepare("SELECT id FROM aseguradoras WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $aseguradora = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aseguradora) {
            $sql .= " AND t.aseguradora_id = :aseguradora_id";
        } else {
            // Si es aseguradora pero no está vinculada a ninguna, no debería ver titulares
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos para esta acción"]);
            exit;
        }
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':cedula', $cedula);
    
    if ($userData['role'] === 'aseguradora' && isset($aseguradora)) {
        $stmt->bindParam(':aseguradora_id', $aseguradora['id']);
    }
    
    $stmt->execute();
    $titular = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($titular) {
        http_response_code(200);
        echo json_encode($titular);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Titular no encontrado"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>