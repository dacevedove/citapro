<?php
// api/horarios/disponibles.php
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
    $doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
    $especialidad_id = isset($_GET['especialidad_id']) ? $_GET['especialidad_id'] : null;
    $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
    
    // Calcular día de la semana (1=Lunes, 7=Domingo)
    $diaSemana = date('N', strtotime($fecha));
    
    // Calcular inicio de semana
    $inicioSemana = date('Y-m-d', strtotime($fecha . ' - ' . ($diaSemana - 1) . ' days'));
    
    error_log("=== HORARIOS DISPONIBLES ===");
    error_log("Fecha solicitada: " . $fecha);
    error_log("Día semana calculado: " . $diaSemana);
    error_log("Inicio semana: " . $inicioSemana);
    error_log("Doctor ID: " . ($doctor_id ?? 'NULL'));
    error_log("Especialidad ID: " . ($especialidad_id ?? 'NULL'));
    
    // Construir consulta base
    $sql = "SELECT h.id as horario_id, h.doctor_id, h.fecha_inicio, h.dia_semana, 
                   h.hora_inicio, h.hora_fin, h.duracion_minutos,
                   tb.nombre as tipo_bloque, tb.color,
                   d.id as doctor_id, 
                   CONCAT(u.nombre, ' ', u.apellido) as doctor_nombre,
                   e.nombre as especialidad_nombre
            FROM horarios_doctores h
            JOIN tipos_bloque_horario tb ON h.tipo_bloque_id = tb.id
            JOIN doctores d ON h.doctor_id = d.id
            JOIN usuarios u ON d.usuario_id = u.id
            JOIN especialidades e ON d.especialidad_id = e.id
            WHERE h.activo = 1 
            AND h.fecha_inicio = ? 
            AND h.dia_semana = ?";
    
    $params = [$inicioSemana, $diaSemana];
    
    // Filtrar por doctor específico si se proporciona
    if ($doctor_id) {
        $sql .= " AND h.doctor_id = ?";
        $params[] = $doctor_id;
    }
    
    // Filtrar por especialidad si se proporciona
    if ($especialidad_id) {
        $sql .= " AND d.especialidad_id = ?";
        $params[] = $especialidad_id;
    }
    
    $sql .= " ORDER BY h.hora_inicio";
    
    error_log("SQL: " . $sql);
    error_log("Params: " . json_encode($params));
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Horarios encontrados: " . count($horarios));
    
    // Generar slots de tiempo disponibles para cada horario
    $slotsDisponibles = [];
    
    foreach ($horarios as $horario) {
        // Calcular slots de 30 minutos dentro del horario
        $horaInicio = strtotime($horario['hora_inicio']);
        $horaFin = strtotime($horario['hora_fin']);
        
        $slotActual = $horaInicio;
        while ($slotActual < $horaFin) {
            $horaSlot = date('H:i', $slotActual);
            $fechaHoraCompleta = $fecha . ' ' . $horaSlot;
            
            // Verificar si ya hay una cita programada en este slot
            $stmtCita = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM citas c
                LEFT JOIN citas_horarios ch ON c.id = ch.cita_id
                WHERE c.doctor_id = ? 
                AND c.fecha = ? 
                AND (c.hora = ? OR (ch.hora_inicio <= ? AND ch.hora_fin > ?))
                AND c.estado NOT IN ('cancelada')
            ");
            
            $stmtCita->execute([
                $horario['doctor_id'], 
                $fecha, 
                $horaSlot, 
                $horaSlot, 
                $horaSlot
            ]);
            
            $citaExistente = $stmtCita->fetch(PDO::FETCH_ASSOC);
            
            if ($citaExistente['total'] == 0) {
                $slotsDisponibles[] = [
                    'horario_id' => $horario['horario_id'],
                    'doctor_id' => $horario['doctor_id'],
                    'doctor_nombre' => $horario['doctor_nombre'],
                    'especialidad_nombre' => $horario['especialidad_nombre'],
                    'fecha' => $fecha,
                    'hora' => $horaSlot,
                    'fecha_hora_completa' => $fechaHoraCompleta,
                    'tipo_bloque' => $horario['tipo_bloque'],
                    'color' => $horario['color'],
                    'disponible' => true
                ];
            }
            
            // Avanzar 30 minutos (1800 segundos)
            $slotActual += 1800;
        }
    }
    
    error_log("Slots disponibles generados: " . count($slotsDisponibles));
    error_log("=== FIN HORARIOS DISPONIBLES ===");
    
    http_response_code(200);
    echo json_encode([
        'fecha' => $fecha,
        'dia_semana' => $diaSemana,
        'horarios_base' => $horarios,
        'slots_disponibles' => $slotsDisponibles,
        'total_slots' => count($slotsDisponibles)
    ]);
    
} catch (Exception $e) {
    error_log("Error en horarios disponibles: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>