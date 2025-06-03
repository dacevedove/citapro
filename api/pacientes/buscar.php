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

// Obtener parámetros de búsqueda
$cedula = isset($_GET['cedula']) ? $_GET['cedula'] : null;
$titular_id = isset($_GET['titular_id']) ? $_GET['titular_id'] : null;
$es_titular = isset($_GET['es_titular']) ? $_GET['es_titular'] : null;

// Verificar que se proporcionó al menos un parámetro de búsqueda
if (!$cedula && !$titular_id) {
    http_response_code(400);
    echo json_encode(["error" => "Debe proporcionar al menos un parámetro de búsqueda"]);
    exit;
}

try {
    // Construir consulta SQL
    $sql = "SELECT p.*, t.numero_afiliado, t.aseguradora_id, a.nombre_comercial as aseguradora_nombre 
            FROM pacientes p
            LEFT JOIN titulares t ON p.titular_id = t.id
            LEFT JOIN aseguradoras a ON t.aseguradora_id = a.id
            WHERE 1=1";
    
    $params = [];
    
    if ($cedula) {
        $sql .= " AND p.cedula = :cedula";
        $params[':cedula'] = $cedula;
    }
    
    if ($titular_id) {
        $sql .= " AND p.titular_id = :titular_id";
        $params[':titular_id'] = $titular_id;
    }
    
    if ($es_titular !== null) {
        $sql .= " AND p.es_titular = :es_titular";
        $params[':es_titular'] = $es_titular;
    }
    
    // Filtrar según rol de usuario
    if ($userData['role'] === 'aseguradora') {
        $stmt = $conn->prepare("SELECT id FROM aseguradoras WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $aseguradora = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aseguradora) {
            $sql .= " AND t.aseguradora_id = :aseguradora_id";
            $params[':aseguradora_id'] = $aseguradora['id'];
        }
    } else if ($userData['role'] === 'paciente') {
        // Un paciente solo puede ver su propia información
        $stmt = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($paciente) {
            // Puede ver su info o la de sus beneficiarios si es titular
            $sql .= " AND (p.id = :paciente_id OR (p.titular_id IN 
                    (SELECT titular_id FROM pacientes WHERE id = :paciente_id2 AND es_titular = 1)))";
            $params[':paciente_id'] = $paciente['id'];
            $params[':paciente_id2'] = $paciente['id'];
        } else {
            // Si el usuario es paciente pero no tiene registro de paciente, no debería ver nada
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos para esta acción"]);
            exit;
        }
    }
    
    // Ejecutar la consulta
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($paciente) {
        http_response_code(200);
        echo json_encode($paciente);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Paciente no encontrado"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>