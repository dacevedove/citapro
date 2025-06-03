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

// Verificar que el parámetro q exista
if (!isset($_GET['q']) || strlen($_GET['q']) < 3) {
    http_response_code(400);
    echo json_encode(["error" => "El término de búsqueda debe tener al menos 3 caracteres"]);
    exit;
}

$searchTerm = '%' . $_GET['q'] . '%';

try {
    // Consulta para buscar titulares por nombre, apellido o cédula
    $sql = "
        SELECT t.*, a.nombre_comercial as aseguradora_nombre
        FROM titulares t
        LEFT JOIN aseguradoras a ON t.aseguradora_id = a.id
        WHERE t.nombre LIKE :term
        OR t.apellido LIKE :term
        OR t.cedula LIKE :term
    ";
    
    // Filtrar según rol de usuario
    if ($userData['role'] === 'aseguradora') {
        $stmt = $conn->prepare("SELECT id FROM aseguradoras WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $aseguradora = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aseguradora) {
            $sql .= " AND t.aseguradora_id = :aseguradora_id";
            $aseguradoraId = $aseguradora['id'];
        } else {
            // Si no está vinculado a una aseguradora, no debería ver ningún titular
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos para esta acción"]);
            exit;
        }
    }
    
    $sql .= " ORDER BY t.nombre ASC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':term', $searchTerm);
    
    if ($userData['role'] === 'aseguradora' && isset($aseguradoraId)) {
        $stmt->bindParam(':aseguradora_id', $aseguradoraId);
    }
    
    $stmt->execute();
    
    $titulares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode($titulares);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>