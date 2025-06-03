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

// Obtener parámetros de filtro
$aseguradora_id = isset($_GET['aseguradora_id']) ? $_GET['aseguradora_id'] : null;
$estado = isset($_GET['estado']) ? $_GET['estado'] : null;
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : null;

try {
    // Construir consulta SQL
    $sql = "SELECT t.*, a.nombre_comercial as aseguradora_nombre FROM titulares t
            LEFT JOIN aseguradoras a ON t.aseguradora_id = a.id
            WHERE 1=1";
    
    $params = [];
    
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
    } else if ($aseguradora_id) {
        // Si es admin y se proporciona ID de aseguradora
        $sql .= " AND t.aseguradora_id = :aseguradora_id";
        $params[':aseguradora_id'] = $aseguradora_id;
    }
    
    // Filtrar por estado
    if ($estado) {
        $sql .= " AND t.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    // Filtrar por búsqueda
    if ($busqueda) {
        $sql .= " AND (t.nombre LIKE :busqueda OR t.apellido LIKE :busqueda OR t.cedula LIKE :busqueda OR t.numero_afiliado LIKE :busqueda)";
        $params[':busqueda'] = "%$busqueda%";
    }
    
    // Ordenar resultados
    $sql .= " ORDER BY t.nombre, t.apellido";
    
    // Preparar y ejecutar consulta
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $titulares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada titular, obtener información adicional
    foreach ($titulares as &$titular) {
        // Verificar si es paciente
        $stmt = $conn->prepare("SELECT id FROM pacientes WHERE es_titular = 1 AND titular_id = :titular_id");
        $stmt->bindParam(':titular_id', $titular['id']);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $titular['es_paciente'] = ($paciente !== false);
        
        if ($paciente) {
            $titular['paciente_id'] = $paciente['id'];
        }
        
        // Contar beneficiarios
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pacientes WHERE titular_id = :titular_id AND es_titular = 0");
        $stmt->bindParam(':titular_id', $titular['id']);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $titular['total_beneficiarios'] = $result['total'];
    }
    
    http_response_code(200);
    echo json_encode($titulares);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>