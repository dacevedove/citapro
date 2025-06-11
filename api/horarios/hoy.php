// Archivo: api/horarios/hoy.php
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

// Verificar permisos
if (!in_array($userData['role'], ['admin', 'coordinador', 'doctor'])) {
    http_response_code(403);
    echo json_encode(["error" => "No tiene permisos para esta acción"]);
    exit;
}

try {
    $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
    $diaSemana = date('N', strtotime($fecha)); // 1=Lunes, 7=Domingo
    
    // Calcular el inicio de la semana que contiene la fecha
    $inicioSemana = date('Y-m-d', strtotime($fecha . ' - ' . ($diaSemana - 1) . ' days'));
    $finSemana = date('Y-m-d', strtotime($inicioSemana . ' + 6 days'));
    
    $sql = "
        SELECT h.*, 
               CONCAT(u.nombre, ' ', u.apellido) as doctor_nombre,
               tb.nombre as tipo_nombre,
               tb.color,
               e.nombre as especialidad_nombre
        FROM horarios_doctores h
        JOIN doctores d ON h.doctor_id = d.id
        JOIN usuarios u ON d.usuario_id = u.id
        JOIN tipos_bloque_horario tb ON h.tipo_bloque_id = tb.id
        JOIN especialidades e ON d.especialidad_id = e.id
        WHERE h.activo = 1
        AND h.fecha_inicio = :inicio_semana
        AND h.dia_semana = :dia_semana
    ";
    
    $params = [
        ':inicio_semana' => $inicioSemana,
        ':dia_semana' => $diaSemana
    ];
    
    // Si es doctor, solo mostrar sus propios horarios
    if ($userData['role'] === 'doctor') {
        $stmt = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doctor) {
            $sql .= " AND h.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctor['id'];
        } else {
            // Si es doctor pero no tiene registro, no mostrar nada
            echo json_encode([]);
            exit;
        }
    }
    
    $sql .= " ORDER BY h.hora_inicio";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->execute();
    
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos
    foreach ($horarios as &$horario) {
        $horario['fecha_completa'] = $fecha;
        $horario['duracion_minutos'] = calcularDuracionMinutos($horario['hora_inicio'], $horario['hora_fin']);
        
        // Verificar si hay citas programadas en este horario
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM citas_horarios ch
            WHERE ch.horario_id = :horario_id
            AND ch.estado NOT IN ('cancelada')
        ");
        $stmt->bindParam(':horario_id', $horario['id']);
        $stmt->execute();
        $citasResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $horario['citas_programadas'] = $citasResult['total'];
        
        // Calcular slots disponibles (cada 30 minutos)
        $duracionTotal = $horario['duracion_minutos'];
        $slotsTotal = $duracionTotal / 30;
        $horario['slots_total'] = $slotsTotal;
        $horario['slots_disponibles'] = $slotsTotal - $horario['citas_programadas'];
    }
    
    http_response_code(200);
    echo json_encode($horarios);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}

function calcularDuracionMinutos($hora_inicio, $hora_fin) {
    $inicio = strtotime($hora_inicio);
    $fin = strtotime($hora_fin);
    return ($fin - $inicio) / 60;
}
?>