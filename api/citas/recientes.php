// Archivo: api/citas/recientes.php
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

try {
    $limite = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $limite = min($limite, 50); // Máximo 50
    
    $sql = "
        SELECT c.*, 
               p.nombre as paciente_nombre, 
               p.apellido as paciente_apellido,
               p.telefono as paciente_telefono,
               p.email as paciente_email,
               e.nombre as especialidad,
               CONCAT(u.nombre, ' ', u.apellido) as doctor_nombre,
               co.nombre as consultorio_nombre,
               co.ubicacion as consultorio_ubicacion
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN especialidades e ON c.especialidad_id = e.id
        LEFT JOIN doctores d ON c.doctor_id = d.id
        LEFT JOIN usuarios u ON d.usuario_id = u.id
        LEFT JOIN consultorios co ON c.consultorio_id = co.id
    ";
    
    $params = [];
    
    // Filtrar según rol del usuario
    if ($userData['role'] === 'doctor') {
        // Solo citas del doctor
        $stmt = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doctor) {
            $sql .= " WHERE c.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctor['id'];
        } else {
            echo json_encode([]);
            exit;
        }
    } elseif ($userData['role'] === 'aseguradora') {
        // Solo citas creadas por la aseguradora
        $sql .= " WHERE c.creado_por = :creado_por";
        $params[':creado_por'] = $userData['id'];
    } elseif ($userData['role'] === 'paciente') {
        // Solo citas del paciente
        $stmt = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($paciente) {
            $sql .= " WHERE c.paciente_id = :paciente_id";
            $params[':paciente_id'] = $paciente['id'];
        } else {
            echo json_encode([]);
            exit;
        }
    }
    // Para admin y coordinador, mostrar todas las citas
    
    $sql .= " ORDER BY c.creado_en DESC LIMIT :limite";
    
    $stmt = $conn->prepare($sql);
    
    // Bindear parámetros
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    
    $stmt->execute();
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas
    foreach ($citas as &$cita) {
        if ($cita['fecha']) {
            $cita['fecha_formateada'] = date('d/m/Y', strtotime($cita['fecha']));
        }
        if ($cita['hora']) {
            $cita['hora_formateada'] = date('H:i', strtotime($cita['hora']));
        }
        $cita['creado_en_formateado'] = date('d/m/Y H:i', strtotime($cita['creado_en']));
        $cita['actualizado_en_formateado'] = date('d/m/Y H:i', strtotime($cita['actualizado_en']));
        
        // Calcular tiempo transcurrido
        $tiempoTranscurrido = time() - strtotime($cita['creado_en']);
        if ($tiempoTranscurrido < 3600) {
            $cita['tiempo_transcurrido'] = 'Hace ' . floor($tiempoTranscurrido / 60) . ' minutos';
        } elseif ($tiempoTranscurrido < 86400) {
            $cita['tiempo_transcurrido'] = 'Hace ' . floor($tiempoTranscurrido / 3600) . ' horas';
        } else {
            $cita['tiempo_transcurrido'] = 'Hace ' . floor($tiempoTranscurrido / 86400) . ' días';
        }
    }
    
    http_response_code(200);
    echo json_encode($citas);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>