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
if (!$userData || $userData['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener parámetros de filtro
$tipo_solicitante = isset($_GET['tipo_solicitante']) ? $_GET['tipo_solicitante'] : null;
$aseguradora_id = isset($_GET['aseguradora_id']) ? $_GET['aseguradora_id'] : null;
$estado = isset($_GET['estado']) ? $_GET['estado'] : null;
$especialidad_id = isset($_GET['especialidad_id']) ? $_GET['especialidad_id'] : null;

try {
    // Construir consulta SQL base
    $sql = "
        SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, 
        p.telefono as paciente_telefono, p.email as paciente_email,
        e.nombre as especialidad, 
        CONCAT(u.nombre, ' ', u.apellido) as doctor_nombre,
        co.nombre as consultorio_nombre, co.ubicacion as consultorio_ubicacion,
        a.nombre_comercial as aseguradora_nombre
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN especialidades e ON c.especialidad_id = e.id
        LEFT JOIN doctores d ON c.doctor_id = d.id
        LEFT JOIN usuarios u ON d.usuario_id = u.id
        LEFT JOIN consultorios co ON c.consultorio_id = co.id
        LEFT JOIN titulares t ON p.titular_id = t.id
        LEFT JOIN aseguradoras a ON t.aseguradora_id = a.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Añadir filtros si existen
    if ($tipo_solicitante) {
        $sql .= " AND c.tipo_solicitante = :tipo_solicitante";
        $params[':tipo_solicitante'] = $tipo_solicitante;
    }
    
    if ($aseguradora_id && $tipo_solicitante === 'aseguradora') {
        $sql .= " AND t.aseguradora_id = :aseguradora_id";
        $params[':aseguradora_id'] = $aseguradora_id;
    }
    
    if ($estado) {
        $sql .= " AND c.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    if ($especialidad_id) {
        $sql .= " AND c.especialidad_id = :especialidad_id";
        $params[':especialidad_id'] = $especialidad_id;
    }
    
    $sql .= " ORDER BY c.creado_en DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Bindear parámetros
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $citas = $stmt->fetchAll();
    
    http_response_code(200);
    echo json_encode($citas);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>