<?php
// api/horarios/copiar_semana.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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
if (!$userData) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Verificar permisos (admin, doctor, coordinador)
if (!in_array($userData['role'], ['admin', 'doctor', 'coordinador'])) {
    http_response_code(403);
    echo json_encode(["error" => "No tiene permisos para esta acción"]);
    exit;
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validar datos requeridos
    if (!isset($data['doctor_id']) || !isset($data['fecha_destino'])) {
        http_response_code(400);
        echo json_encode(["error" => "Se requiere doctor_id y fecha_destino"]);
        exit;
    }
    
    $doctor_id = $data['doctor_id'];
    $fecha_destino = $data['fecha_destino'];
    
    // Verificar permisos sobre el doctor
    if (!verificarPermisoDoctor($doctor_id, $userData, $conn)) {
        http_response_code(403);
        echo json_encode(["error" => "No tiene permisos para gestionar este doctor"]);
        exit;
    }
    
    // Calcular fecha de inicio de la semana anterior
    $fecha_destino_obj = new DateTime($fecha_destino);
    $fecha_origen_obj = clone $fecha_destino_obj;
    $fecha_origen_obj->modify('-7 days');
    
    $fecha_origen = $fecha_origen_obj->format('Y-m-d');
    
    error_log("=== COPIAR SEMANA DEBUG ===");
    error_log("Doctor ID: " . $doctor_id);
    error_log("Fecha origen (semana anterior): " . $fecha_origen);
    error_log("Fecha destino (semana actual): " . $fecha_destino);
    
    // Verificar si ya existen horarios en la semana destino
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM horarios_doctores 
        WHERE doctor_id = ? 
        AND fecha_inicio = ? 
        AND activo = 1
    ");
    $stmt->execute([$doctor_id, $fecha_destino]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        http_response_code(400);
        echo json_encode([
            "error" => "Ya existen horarios en la semana destino. Debe eliminarlos primero si desea copiar la semana anterior."
        ]);
        exit;
    }
    
    // Obtener horarios de la semana anterior
    $stmt = $conn->prepare("
        SELECT tipo_bloque_id, dia_semana, hora_inicio, hora_fin, 
               duracion_minutos, notas
        FROM horarios_doctores 
        WHERE doctor_id = ? 
        AND fecha_inicio = ? 
        AND activo = 1
        ORDER BY dia_semana, hora_inicio
    ");
    $stmt->execute([$doctor_id, $fecha_origen]);
    $horariosOrigen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($horariosOrigen)) {
        http_response_code(404);
        echo json_encode([
            "error" => "No se encontraron horarios en la semana anterior para copiar"
        ]);
        exit;
    }
    
    error_log("Horarios encontrados para copiar: " . count($horariosOrigen));
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    try {
        $horariosCreados = 0;
        
        // Preparar statement para inserción
        $insertStmt = $conn->prepare("
            INSERT INTO horarios_doctores 
            (doctor_id, tipo_bloque_id, fecha_inicio, dia_semana, hora_inicio, 
             hora_fin, duracion_minutos, notas, creado_por, activo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        // Copiar cada horario
        foreach ($horariosOrigen as $horario) {
            $insertStmt->execute([
                $doctor_id,
                $horario['tipo_bloque_id'],
                $fecha_destino,
                $horario['dia_semana'],
                $horario['hora_inicio'],
                $horario['hora_fin'],
                $horario['duracion_minutos'],
                $horario['notas'],
                $userData['id']
            ]);
            
            $horariosCreados++;
            
            // Registrar en logs
            $horario_id = $conn->lastInsertId();
            registrarLogHorario($horario_id, 'copiar_semana', null, [
                'origen' => $fecha_origen,
                'destino' => $fecha_destino
            ], $userData, $conn);
        }
        
        // Confirmar transacción
        $conn->commit();
        
        error_log("Horarios copiados exitosamente: " . $horariosCreados);
        error_log("=== FIN COPIAR SEMANA DEBUG ===");
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Se copiaron $horariosCreados bloques de horario de la semana anterior",
            "horarios_copiados" => $horariosCreados
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollBack();
        error_log("Error al copiar horarios: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en copiar_semana.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}

// Funciones auxiliares
function verificarPermisoDoctor($doctor_id, $userData, $conn) {
    // Admin y coordinador pueden gestionar cualquier doctor
    if (in_array($userData['role'], ['admin', 'coordinador'])) {
        return true;
    }
    
    // Doctor solo puede gestionar sus propios horarios
    if ($userData['role'] === 'doctor') {
        $stmt = $conn->prepare("SELECT id FROM doctores WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$doctor_id, $userData['id']]);
        
        return $stmt->fetch() !== false;
    }
    
    return false;
}

function registrarLogHorario($horario_id, $accion, $datos_anteriores, $datos_nuevos, $userData, $conn) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO logs_horarios (horario_id, accion, datos_anteriores, datos_nuevos, usuario_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $horario_id,
            $accion,
            $datos_anteriores ? json_encode($datos_anteriores) : null,
            $datos_nuevos ? json_encode($datos_nuevos) : null,
            $userData['id']
        ]);
    } catch (Exception $e) {
        error_log("Error al registrar log de horario: " . $e->getMessage());
    }
}
?>