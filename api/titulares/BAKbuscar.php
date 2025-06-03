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

// Verificar permisos (solo admin o aseguradora)
if ($userData['role'] !== 'admin' && $userData['role'] !== 'aseguradora') {
    http_response_code(403);
    echo json_encode(["error" => "No tiene permisos para esta acción"]);
    exit;
}

// Obtener parámetros de búsqueda
$cedula = isset($_GET['cedula']) ? $_GET['cedula'] : null;
$numero_afiliado = isset($_GET['numero_afiliado']) ? $_GET['numero_afiliado'] : null;
$titular_id = isset($_GET['titular_id']) ? $_GET['titular_id'] : null;
$es_titular = isset($_GET['es_titular']) ? $_GET['es_titular'] : null;

// Verificar que se proporcionó al menos un parámetro de búsqueda
if (!$cedula && !$numero_afiliado && !$titular_id) {
    http_response_code(400);
    echo json_encode(["error" => "Debe proporcionar al menos un parámetro de búsqueda"]);
    exit;
}

try {
    // Construir consulta SQL
    $sql = "SELECT t.*, a.nombre_comercial as aseguradora_nombre FROM titulares t
            LEFT JOIN aseguradoras a ON t.aseguradora_id = a.id
            WHERE 1=1";
    
    $params = [];
    
    if ($cedula) {
        $sql .= " AND t.cedula = :cedula";
        $params[':cedula'] = $cedula;
    }
    
    if ($numero_afiliado) {
        $sql .= " AND t.numero_afiliado = :numero_afiliado";
        $params[':numero_afiliado'] = $numero_afiliado;
    }
    
    if ($titular_id) {
        $sql .= " AND t.id = :titular_id";
        $params[':titular_id'] = $titular_id;
    }
    
    // Si el usuario es una aseguradora, filtrar solo sus titulares
    if ($userData['role'] === 'aseguradora') {
        $stmt = $conn->prepare("SELECT id FROM aseguradoras WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $aseguradora = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aseguradora) {
            $sql .= " AND t.aseguradora_id = :aseguradora_id";
            $params[':aseguradora_id'] = $aseguradora['id'];
        }
    }
    
    // Ejecutar la consulta
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $titular = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($titular) {
        // Si se solicita verificar si es paciente
        if ($es_titular) {
            $stmt = $conn->prepare("SELECT * FROM pacientes WHERE es_titular = 1 AND titular_id = :titular_id");
            $stmt->bindParam(':titular_id', $titular['id']);
            $stmt->execute();
            $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $titular['es_paciente'] = ($paciente !== false);
            
            if ($paciente) {
                $titular['paciente_id'] = $paciente['id'];
            }
        }
        
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