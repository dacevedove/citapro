// Archivo: api/coordinador/estadisticas.php
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

// Verificar permisos (admin o coordinador)
if (!in_array($userData['role'], ['admin', 'coordinador'])) {
    http_response_code(403);
    echo json_encode(["error" => "No tiene permisos para esta acción"]);
    exit;
}

try {
    $estadisticas = [];
    
    // Citas pendientes (solicitadas y asignadas)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM citas 
        WHERE estado IN ('solicitada', 'asignada')
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $estadisticas['citasPendientes'] = $result['total'];
    
    // Doctores activos
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT d.id) as total
        FROM doctores d
        JOIN usuarios u ON d.usuario_id = u.id
        WHERE u.esta_activo = 1
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $estadisticas['doctoresActivos'] = $result['total'];
    
    // Horarios programados para hoy
    $hoy = date('Y-m-d');
    $diaSemana = date('N'); // 1=Lunes, 7=Domingo
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM horarios_doctores h
        WHERE h.activo = 1
        AND h.fecha_inicio <= :hoy
        AND DATE_ADD(h.fecha_inicio, INTERVAL 6 DAY) >= :hoy
        AND h.dia_semana = :dia_semana
    ");
    $stmt->bindParam(':hoy', $hoy);
    $stmt->bindParam(':dia_semana', $diaSemana);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $estadisticas['horariosHoy'] = $result['total'];
    
    // Citas de hoy
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM citas
        WHERE DATE(fecha) = :hoy
        AND estado NOT IN ('cancelada')
    ");
    $stmt->bindParam(':hoy', $hoy);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $estadisticas['citasHoy'] = $result['total'];
    
    // Especialidades activas
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM especialidades");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $estadisticas['especialidades'] = $result['total'];
    
    // Tipos de bloque activos
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM tipos_bloque_horario 
        WHERE activo = 1
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $estadisticas['tiposBloqueActivos'] = $result['total'];
    
    http_response_code(200);
    echo json_encode($estadisticas);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>