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
include_once '../../config/database.php';
include_once '../../config/jwt.php';

// Obtener JWT del encabezado
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Validar token
$userData = validateJWT($jwt);
if (!$userData || !in_array($userData['role'], ['admin', 'vertice'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

try {
    $conn = Database::getConnection();
    
    // Parámetros de filtro
    $estado = isset($_GET['estado']) ? $_GET['estado'] : null;
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
    $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    
    // Construir consulta base
    $sql = "
        SELECT tn.*, 
               u.nombre as creador_nombre, u.apellido as creador_apellido,
               c.fecha as fecha_cita, c.hora as hora_cita,
               p.nombre as paciente_nombre, p.apellido as paciente_apellido,
               d.id as doctor_id, ud.nombre as doctor_nombre, ud.apellido as doctor_apellido,
               e.nombre as especialidad
        FROM temp_notificaciones tn
        LEFT JOIN usuarios u ON tn.creado_por = u.id
        LEFT JOIN citas c ON tn.cita_id = c.id
        LEFT JOIN pacientes p ON tn.paciente_id = p.id
        LEFT JOIN doctores d ON c.doctor_id = d.id
        LEFT JOIN usuarios ud ON d.usuario_id = ud.id
        LEFT JOIN especialidades e ON c.especialidad_id = e.id
    ";
    
    // Agregar filtros
    $where = [];
    $params = [];
    
    if ($estado) {
        $where[] = "tn.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    if ($tipo) {
        $where[] = "tn.tipo = :tipo";
        $params[':tipo'] = $tipo;
    }
    
    if ($fecha) {
        $where[] = "DATE(tn.creado_en) = :fecha";
        $params[':fecha'] = $fecha;
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    // Ordenar y limitar resultados
    $sql .= " ORDER BY tn.creado_en DESC LIMIT :limit";
    $params[':limit'] = $limit;
    
    // Preparar y ejecutar consulta
    $stmt = $conn->prepare($sql);
    
    // Bindear parámetros
    foreach ($params as $key => $value) {
        if ($key === ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para mejor visualización
    $result = [];
    foreach ($notificaciones as $notificacion) {
        // Procesar respuesta_api para hacerla legible
        $respuestaApi = null;
        if ($notificacion['respuesta_api']) {
            $respuestaApi = json_decode($notificacion['respuesta_api'], true);
        }
        
        // Crear objeto formateado
        $item = [
            'id' => $notificacion['id'],
            'tipo' => $notificacion['tipo'],
            'estado' => $notificacion['estado'],
            'intentos' => $notificacion['intentos'],
            'fecha_envio' => $notificacion['creado_en'],
            'paciente' => [
                'id' => $notificacion['paciente_id'],
                'nombre' => $notificacion['paciente_nombre'] . ' ' . $notificacion['paciente_apellido'],
                'telefono' => $notificacion['telefono'],
                'email' => $notificacion['email']
            ],
            'cita' => [
                'id' => $notificacion['cita_id'],
                'fecha' => $notificacion['fecha_cita'],
                'hora' => $notificacion['hora_cita'],
                'doctor' => [
                    'id' => $notificacion['doctor_id'],
                    'nombre' => 'Dr. ' . $notificacion['doctor_nombre'] . ' ' . $notificacion['doctor_apellido']
                ],
                'especialidad' => $notificacion['especialidad']
            ],
            'creado_por' => [
                'id' => $notificacion['creado_por'],
                'nombre' => $notificacion['creador_nombre'] . ' ' . $notificacion['creador_apellido']
            ],
            'mensaje' => $notificacion['mensaje'],
            'respuesta_api' => $respuestaApi
        ];
        
        $result[] = $item;
    }
    
    // Enviar respuesta
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'count' => count($result),
        'data' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al obtener las notificaciones: ' . $e->getMessage()
    ]);
}