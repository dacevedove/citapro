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

// Obtener parámetros de filtrado
$especialidad_id = isset($_GET['especialidad_id']) ? $_GET['especialidad_id'] : null;

try {
    // Construir consulta base
    $sql = "
        SELECT 
            d.id, 
            u.nombre, 
            u.apellido, 
            u.cedula, 
            u.email, 
            u.telefono,
            u.foto_perfil,
            d.especialidad_id,
            d.subespecialidad_id,
            e.nombre as especialidad_nombre,
            s.nombre as subespecialidad_nombre,
            (SELECT COUNT(*) FROM citas c WHERE c.doctor_id = d.id AND c.estado IN ('asignada', 'confirmada')) as citas_pendientes,
            (SELECT COUNT(*) FROM citas c WHERE c.doctor_id = d.id AND c.estado = 'completada') as citas_completadas
        FROM doctores d
        JOIN usuarios u ON d.usuario_id = u.id
        JOIN especialidades e ON d.especialidad_id = e.id
        LEFT JOIN subespecialidades s ON d.subespecialidad_id = s.id
    ";
    
    // Aplicar filtros
    $params = [];
    $whereClause = [];
    
    if ($especialidad_id) {
        $whereClause[] = "d.especialidad_id = :especialidad_id";
        $params[':especialidad_id'] = $especialidad_id;
    }
    
    // Agregar cláusula WHERE si hay filtros
    if (count($whereClause) > 0) {
        $sql .= " WHERE " . implode(" AND ", $whereClause);
    }
    
    // Ordenar resultados
    $sql .= " ORDER BY u.nombre, u.apellido";
    
    // Preparar y ejecutar consulta
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $doctores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode($doctores);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>