<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Para solicitudes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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
if (!$userData || $userData['role'] !== 'vertice') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener parámetros de filtrado
$id = isset($_GET['id']) ? $_GET['id'] : null;
$estatus = isset($_GET['estatus']) ? $_GET['estatus'] : null;
$especialidad_id = isset($_GET['especialidad_id']) ? $_GET['especialidad_id'] : null;
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : null;

try {
    // Construir consulta base
    $sql = "SELECT * FROM temp_solicitudes WHERE 1=1";
    $params = [];
    
    // Aplicar filtros
    if ($id) {
        $sql .= " AND id = :id";
        $params[':id'] = $id;
    }
    
    if ($estatus) {
        $sql .= " AND estatus = :estatus";
        $params[':estatus'] = $estatus;
    }
    
    if ($especialidad_id) {
        $sql .= " AND especialidad_requerida = :especialidad_id";
        $params[':especialidad_id'] = $especialidad_id;
    }
    
    if ($fecha) {
        $sql .= " AND DATE(fecha_solicitud) = :fecha";
        $params[':fecha'] = $fecha;
    }
    
    // Ordenar resultados
    $sql .= " ORDER BY fecha_solicitud DESC, id DESC";
    
    // Preparar y ejecutar consulta
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Registrar en logs para debugging
    if ($id) {
        error_log("Solicitud con ID: " . $id . " - Encontrada: " . (count($solicitudes) > 0 ? "Sí" : "No"));
    }
    
    http_response_code(200);
    echo json_encode([
        "message" => "Solicitudes obtenidas exitosamente",
        "data" => $solicitudes,
        "total" => count($solicitudes),
        "filtros_aplicados" => [
            "id" => $id,
            "estatus" => $estatus,
            "especialidad_id" => $especialidad_id,
            "fecha" => $fecha
        ]
    ]);
    
} catch(PDOException $e) {
    error_log("Error en listar_solicitudes.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => "Error en el servidor: " . $e->getMessage(),
        "data" => []
    ]);
}
?>