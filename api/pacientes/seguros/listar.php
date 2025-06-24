<?php
// api/pacientes/seguros/listar.php
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
    $stmt = $conn->prepare("SELECT id FROM pacientes WHERE id = :paciente_id");
    $stmt->bindParam(':paciente_id', $paciente_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Paciente no encontrado"]);
        exit;
    }
    
    // Consultar seguros del paciente con JOIN a las tablas relacionadas
    $stmt = $conn->prepare("
        SELECT 
            ps.*,
            a.nombre_comercial as aseguradora_nombre,
            CASE 
                WHEN ps.fecha_vencimiento IS NULL THEN 'indefinido'
                WHEN ps.fecha_vencimiento < CURDATE() THEN 'vencido'
                WHEN ps.fecha_vencimiento = CURDATE() THEN 'vence_hoy'
                ELSE 'vigente'
            END as estado_vigencia,
            CASE 
                WHEN ps.fecha_vencimiento IS NULL THEN NULL
                ELSE DATEDIFF(ps.fecha_vencimiento, CURDATE())
            END as dias_vencimiento
        FROM pacientes_seguros ps
        LEFT JOIN aseguradoras a ON ps.aseguradora_id = a.id
        WHERE ps.paciente_id = :paciente_id 
        ORDER BY ps.tipo_cobertura ASC, ps.estado ASC, ps.fecha_inicio DESC
    ");
    $stmt->bindParam(':paciente_id', $paciente_id);
    $stmt->execute();
    
    $seguros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos
    foreach ($seguros as &$seguro) {
        $seguro['estado_vigencia_texto'] = [
            'vigente' => 'Vigente',
            'vence_hoy' => 'Vence hoy',
            'vencido' => 'Vencido',
            'indefinido' => 'Sin vencimiento'
        ][$seguro['estado_vigencia']] ?? $seguro['estado_vigencia'];
        
        $seguro['tipo_cobertura_texto'] = [
            'principal' => 'Principal',
            'secundario' => 'Secundario',
            'complementario' => 'Complementario'
        ][$seguro['tipo_cobertura']] ?? $seguro['tipo_cobertura'];
    }
    
    http_response_code(200);
    echo json_encode($seguros);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>