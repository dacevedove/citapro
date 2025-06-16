<?php
// Archivo: api/citas/asignar.php (Actualizado para incluir tipo_bloque_id)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
if (!$userData) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Verificar permisos
if (!in_array($userData['role'], ['admin', 'coordinador'])) {
    http_response_code(403);
    echo json_encode(["error" => "No tiene permisos para asignar citas"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

error_log("=== ASIGNAR CITA ===");
error_log("Datos recibidos: " . json_encode($data));

// Validar datos recibidos
if (!isset($data['cita_id']) || !isset($data['especialidad_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos: cita_id y especialidad_id son requeridos"]);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Verificar que la cita existe y está en estado solicitada
    $stmt = $conn->prepare("SELECT * FROM citas WHERE id = ? AND estado = 'solicitada'");
    $stmt->execute([$data['cita_id']]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cita) {
        throw new Exception("Cita no encontrada o no está en estado solicitada");
    }
    
    // Caso 1: Asignación a doctor específico con horario específico
    if (isset($data['doctor_id']) && isset($data['fecha']) && isset($data['hora'])) {
        error_log("Caso 1: Asignación específica");
        
        // Verificar que el doctor existe y tiene la especialidad correcta
        $stmt = $conn->prepare("
            SELECT d.*, u.nombre, u.apellido 
            FROM doctores d 
            JOIN usuarios u ON d.usuario_id = u.id 
            WHERE d.id = ? AND d.especialidad_id = ?
        ");
        $stmt->execute([$data['doctor_id'], $data['especialidad_id']]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$doctor) {
            throw new Exception("Doctor no encontrado o no tiene la especialidad requerida");
        }
        
        // Verificar que el horario esté disponible
        $diaSemana = date('N', strtotime($data['fecha']));
        $inicioSemana = date('Y-m-d', strtotime($data['fecha'] . ' - ' . ($diaSemana - 1) . ' days'));
        
        // Verificar que existe un horario del doctor para esa fecha/hora
        $stmt = $conn->prepare("
            SELECT h.* FROM horarios_doctores h
            WHERE h.doctor_id = ? 
            AND h.fecha_inicio = ? 
            AND h.dia_semana = ?
            AND h.hora_inicio <= ? 
            AND h.hora_fin > ?
            AND h.activo = 1
        ");
        $stmt->execute([
            $data['doctor_id'], 
            $inicioSemana, 
            $diaSemana, 
            $data['hora'], 
            $data['hora']
        ]);
        
        $horarioDisponible = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$horarioDisponible) {
            throw new Exception("No hay horario disponible para el doctor en la fecha/hora especificada");
        }
        
        // Verificar que no haya conflicto con otras citas
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total FROM citas 
            WHERE doctor_id = ? 
            AND fecha = ? 
            AND hora = ? 
            AND estado NOT IN ('cancelada') 
            AND id != ?
        ");
        $stmt->execute([$data['doctor_id'], $data['fecha'], $data['hora'], $data['cita_id']]);
        $conflicto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($conflicto['total'] > 0) {
            throw new Exception("Ya existe una cita programada para esa fecha y hora");
        }
        
        // Actualizar la cita
        $stmt = $conn->prepare("
            UPDATE citas SET 
                especialidad_id = ?,
                doctor_id = ?,
                fecha = ?,
                hora = ?,
                tipo_bloque_id = ?,
                asignacion_libre = 0,
                estado = 'asignada'
            WHERE id = ?
        ");
        
        $tipoBloque = isset($data['tipo_bloque_id']) ? $data['tipo_bloque_id'] : $horarioDisponible['tipo_bloque_id'];
        
        $stmt->execute([
            $data['especialidad_id'],
            $data['doctor_id'],
            $data['fecha'],
            $data['hora'],
            $tipoBloque,
            $data['cita_id']
        ]);
        
        // Crear registro en citas_horarios
        $stmt = $conn->prepare("
            INSERT INTO citas_horarios (horario_id, cita_id, hora_inicio, hora_fin, estado, creado_por) 
            VALUES (?, ?, ?, ?, 'reservada', ?)
        ");
        
        // Calcular hora fin (30 minutos después)
        $horaFin = date('H:i:s', strtotime($data['hora'] . ' + 30 minutes'));
        
        $stmt->execute([
            $horarioDisponible['id'],
            $data['cita_id'],
            $data['hora'],
            $horaFin,
            $userData['id']
        ]);
        
        $mensaje = "Cita asignada a Dr. " . $doctor['nombre'] . " " . $doctor['apellido'] . 
                   " para el " . date('d/m/Y', strtotime($data['fecha'])) . " a las " . 
                   date('H:i', strtotime($data['hora']));
    }
    // Caso 2: Asignación a doctor específico sin horario específico
    elseif (isset($data['doctor_id']) && !isset($data['asignacion_libre'])) {
        error_log("Caso 2: Asignación a doctor específico");
        
        $tipoBloque = isset($data['tipo_bloque_id']) ? $data['tipo_bloque_id'] : null;
        
        $stmt = $conn->prepare("
            UPDATE citas SET 
                especialidad_id = ?,
                doctor_id = ?,
                tipo_bloque_id = ?,
                asignacion_libre = 0,
                estado = 'asignada'
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['especialidad_id'],
            $data['doctor_id'],
            $tipoBloque,
            $data['cita_id']
        ]);
        
        $mensaje = "Cita asignada a doctor específico";
    }
    // Caso 3: Asignación libre
    else {
        error_log("Caso 3: Asignación libre");
        
        $tipoBloque = isset($data['tipo_bloque_id']) ? $data['tipo_bloque_id'] : null;
        
        $stmt = $conn->prepare("
            UPDATE citas SET 
                especialidad_id = ?,
                doctor_id = NULL,
                tipo_bloque_id = ?,
                asignacion_libre = 1,
                estado = 'asignada'
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['especialidad_id'],
            $tipoBloque,
            $data['cita_id']
        ]);
        
        $mensaje = "Cita asignada como libre para cualquier doctor de la especialidad";
    }
    
    // Actualizar notas si se proporcionan
    if (isset($data['notas']) && !empty($data['notas'])) {
        $stmt = $conn->prepare("UPDATE citas SET descripcion = CONCAT(descripcion, '\n\nNotas de asignación: ', ?) WHERE id = ?");
        $stmt->execute([$data['notas'], $data['cita_id']]);
    }
    
    // Log de auditoría
    $logStmt = $conn->prepare("
        INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_nuevos, direccion_ip) 
        VALUES (:usuario_id, 'citas', :registro_id, 'asignar', :datos_nuevos, :ip)
    ");
    
    $logStmt->bindParam(':usuario_id', $userData['id']);
    $logStmt->bindParam(':registro_id', $data['cita_id']);
    
    $datosNuevos = json_encode([
        'especialidad_id' => $data['especialidad_id'],
        'doctor_id' => $data['doctor_id'] ?? null,
        'tipo_bloque_id' => $data['tipo_bloque_id'] ?? null,
        'fecha' => $data['fecha'] ?? null,
        'hora' => $data['hora'] ?? null,
        'asignacion_libre' => $data['asignacion_libre'] ?? false,
        'notas' => $data['notas'] ?? null
    ]);
    $logStmt->bindParam(':datos_nuevos', $datosNuevos);
    
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logStmt->bindParam(':ip', $ip);
    
    $logStmt->execute();
    
    $conn->commit();
    
    error_log("Cita asignada exitosamente: " . $mensaje);
    error_log("=== FIN ASIGNAR CITA ===");
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => $mensaje,
        "cita_id" => $data['cita_id']
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error al asignar cita: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}
?>