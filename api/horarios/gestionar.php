<?php
// api/horarios/gestionar.php - VERSIÓN CORREGIDA
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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

// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            listarHorarios();
            break;
            
        case 'POST':
            crearHorario();
            break;
            
        case 'PUT':
            actualizarHorario();
            break;
            
        case 'DELETE':
            eliminarHorario();
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido"]);
            break;
    }
} catch(Exception $e) {
    error_log("Error en gestionar.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}

function listarHorarios() {
    global $conn, $userData;
    
    try {
        $doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
        $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
        
        // LOG DE DEBUG
        error_log("=== LISTAR HORARIOS DEBUG ===");
        error_log("GET params: " . json_encode($_GET));
        error_log("doctor_id: " . ($doctor_id ?? 'NULL'));
        error_log("fecha_inicio: " . ($fecha_inicio ?? 'NULL'));
        error_log("User role: " . $userData['role']);
        
        // Si es doctor, solo puede ver sus propios horarios
        if ($userData['role'] === 'doctor') {
            $stmt = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = ?");
            $stmt->execute([$userData['id']]);
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$doctor) {
                error_log("Doctor no encontrado para usuario: " . $userData['id']);
                http_response_code(404);
                echo json_encode(["error" => "Doctor no encontrado"]);
                return;
            }
            
            $doctor_id = $doctor['id'];
            error_log("Doctor ID obtenido: " . $doctor_id);
        }
        
        if (!$doctor_id) {
            error_log("Doctor ID requerido pero no proporcionado");
            http_response_code(400);
            echo json_encode(["error" => "ID de doctor requerido"]);
            return;
        }
        
        // Construir consulta base
        $sql = "SELECT h.id, h.doctor_id, h.tipo_bloque_id, h.fecha_inicio, h.dia_semana, 
                       h.hora_inicio, h.hora_fin, h.duracion_minutos, h.activo, h.notas,
                       tb.nombre as tipo_nombre, tb.color, tb.descripcion as tipo_descripcion
                FROM horarios_doctores h
                LEFT JOIN tipos_bloque_horario tb ON h.tipo_bloque_id = tb.id
                WHERE h.doctor_id = ? AND h.activo = 1";
        
        $params = [$doctor_id];
        
        // Agregar filtro de fecha si se proporciona
        if ($fecha_inicio) {
            $sql .= " AND h.fecha_inicio = ?";
            $params[] = $fecha_inicio;
            error_log("Agregando filtro fecha_inicio: " . $fecha_inicio);
        }
        
        $sql .= " ORDER BY h.fecha_inicio, h.dia_semana, h.hora_inicio";
        
        error_log("SQL: " . $sql);
        error_log("Params: " . json_encode($params));
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Registros encontrados: " . count($horarios));
        
        // Procesar resultados
        foreach ($horarios as &$horario) {
            // Asegurar que los valores booleanos se conviertan correctamente
            $horario['activo'] = (bool)$horario['activo'];
            
            // Calcular duración si no está establecida
            if (!$horario['duracion_minutos']) {
                $horario['duracion_minutos'] = calcularDuracionMinutos($horario['hora_inicio'], $horario['hora_fin']);
            }
            
            // Limpiar campos nulos
            $horario['notas'] = $horario['notas'] ?? '';
            $horario['tipo_descripcion'] = $horario['tipo_descripcion'] ?? '';
        }
        
        error_log("Horarios procesados: " . json_encode($horarios));
        error_log("=== FIN LISTAR HORARIOS DEBUG ===");
        
        http_response_code(200);
        echo json_encode($horarios);
        
    } catch (Exception $e) {
        error_log("Error en listarHorarios: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}

function crearHorario() {
    global $conn, $userData;
    
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        error_log("=== CREAR HORARIO DEBUG ===");
        error_log("Datos recibidos: " . json_encode($data));
        
        // Validar datos requeridos
        $camposRequeridos = ['doctor_id', 'tipo_bloque_id', 'fecha', 'dia_semana', 'hora_inicio', 'hora_fin'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo]) || $data[$campo] === '' || $data[$campo] === null) {
                error_log("Campo requerido faltante: " . $campo);
                http_response_code(400);
                echo json_encode(["error" => "Campo requerido faltante o vacío: $campo"]);
                return;
            }
        }
        
        // Verificar permisos sobre el doctor
        if (!verificarPermisoDoctor($data['doctor_id'])) {
            error_log("Sin permisos para doctor: " . $data['doctor_id']);
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos para gestionar este doctor"]);
            return;
        }
        
        // Validar horarios
        if ($data['hora_inicio'] >= $data['hora_fin']) {
            error_log("Hora inicio >= hora fin");
            http_response_code(400);
            echo json_encode(["error" => "La hora de inicio debe ser menor que la hora de fin"]);
            return;
        }
        
        // En este contexto, 'fecha' debe ser la fecha_inicio de la semana
        $fecha_inicio_semana = $data['fecha'];
        
        error_log("Fecha inicio semana calculada: " . $fecha_inicio_semana);
        
        // Verificar solapamientos
        if (verificarSolapamiento($data['doctor_id'], $fecha_inicio_semana, $data['dia_semana'], $data['hora_inicio'], $data['hora_fin'])) {
            error_log("Solapamiento detectado");
            http_response_code(400);
            echo json_encode(["error" => "Ya existe un horario que se solapa con este período"]);
            return;
        }
        
        // Calcular duración en minutos
        $duracion = calcularDuracionMinutos($data['hora_inicio'], $data['hora_fin']);
        
        $sql = "INSERT INTO horarios_doctores 
                (doctor_id, tipo_bloque_id, fecha_inicio, dia_semana, hora_inicio, hora_fin, duracion_minutos, notas, creado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['doctor_id'],
            $data['tipo_bloque_id'],
            $fecha_inicio_semana,
            $data['dia_semana'],
            $data['hora_inicio'],
            $data['hora_fin'],
            $duracion,
            $data['notas'] ?? '',
            $userData['id']
        ];
        
        error_log("SQL INSERT: " . $sql);
        error_log("Params INSERT: " . json_encode($params));
        
        $stmt = $conn->prepare($sql);
        $resultado = $stmt->execute($params);
        
        if (!$resultado) {
            $errorInfo = $stmt->errorInfo();
            error_log("Error en INSERT: " . json_encode($errorInfo));
            http_response_code(500);
            echo json_encode(["error" => "Error al insertar en la base de datos: " . $errorInfo[2]]);
            return;
        }
        
        $horario_id = $conn->lastInsertId();
        error_log("Horario creado con ID: " . $horario_id);
        
        // Registrar en logs
        registrarLogHorario($horario_id, 'crear', null, $data);
        
        error_log("=== FIN CREAR HORARIO DEBUG ===");
        
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Horario creado exitosamente",
            "id" => $horario_id
        ]);
        
    } catch (Exception $e) {
        error_log("Error en crearHorario: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}

function actualizarHorario() {
    global $conn, $userData;
    
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "ID de horario requerido"]);
            return;
        }
        
        // Obtener horario actual
        $stmt = $conn->prepare("SELECT * FROM horarios_doctores WHERE id = ?");
        $stmt->execute([$data['id']]);
        $horarioActual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$horarioActual) {
            http_response_code(404);
            echo json_encode(["error" => "Horario no encontrado"]);
            return;
        }
        
        // Verificar permisos
        if (!verificarPermisoDoctor($horarioActual['doctor_id'])) {
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos para gestionar este horario"]);
            return;
        }
        
        // Construir campos a actualizar dinámicamente
        $updates = [];
        $params = [];
        
        if (isset($data['tipo_bloque_id'])) {
            $updates[] = "tipo_bloque_id = ?";
            $params[] = $data['tipo_bloque_id'];
        }
        
        if (isset($data['fecha'])) {
            $updates[] = "fecha_inicio = ?";
            $params[] = $data['fecha'];
        }
        
        if (isset($data['dia_semana'])) {
            $updates[] = "dia_semana = ?";
            $params[] = $data['dia_semana'];
        }
        
        if (isset($data['hora_inicio'])) {
            $updates[] = "hora_inicio = ?";
            $params[] = $data['hora_inicio'];
        }
        
        if (isset($data['hora_fin'])) {
            $updates[] = "hora_fin = ?";
            $params[] = $data['hora_fin'];
        }
        
        if (isset($data['notas'])) {
            $updates[] = "notas = ?";
            $params[] = $data['notas'];
        }
        
        if (isset($data['activo'])) {
            $updates[] = "activo = ?";
            $params[] = $data['activo'] ? 1 : 0;
        }
        
        // Validar horarios si se actualizan
        if (isset($data['hora_inicio']) || isset($data['hora_fin'])) {
            $nueva_hora_inicio = $data['hora_inicio'] ?? $horarioActual['hora_inicio'];
            $nueva_hora_fin = $data['hora_fin'] ?? $horarioActual['hora_fin'];
            
            if ($nueva_hora_inicio >= $nueva_hora_fin) {
                http_response_code(400);
                echo json_encode(["error" => "La hora de inicio debe ser menor que la hora de fin"]);
                return;
            }
            
            // Actualizar duración
            $duracion = calcularDuracionMinutos($nueva_hora_inicio, $nueva_hora_fin);
            $updates[] = "duracion_minutos = ?";
            $params[] = $duracion;
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(["error" => "No hay campos para actualizar"]);
            return;
        }
        
        // Agregar ID al final de los parámetros
        $params[] = $data['id'];
        
        $sql = "UPDATE horarios_doctores SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        // Registrar en logs
        registrarLogHorario($data['id'], 'modificar', $horarioActual, $data);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Horario actualizado exitosamente"
        ]);
        
    } catch (Exception $e) {
        error_log("Error en actualizarHorario: " . $e->getMessage());
        throw $e;
    }
}

function eliminarHorario() {
    global $conn, $userData;
    
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "ID de horario requerido"]);
            return;
        }
        
        // Obtener horario actual
        $stmt = $conn->prepare("SELECT * FROM horarios_doctores WHERE id = ?");
        $stmt->execute([$data['id']]);
        $horario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$horario) {
            http_response_code(404);
            echo json_encode(["error" => "Horario no encontrado"]);
            return;
        }
        
        // Verificar permisos
        if (!verificarPermisoDoctor($horario['doctor_id'])) {
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos para gestionar este horario"]);
            return;
        }
        
        // Verificar si hay citas programadas en este horario
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM citas_horarios WHERE horario_id = ? AND estado != 'cancelada'");
        $stmt->execute([$data['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            http_response_code(400);
            echo json_encode(["error" => "No se puede eliminar el horario porque tiene citas programadas"]);
            return;
        }
        
        // Eliminar horario
        $stmt = $conn->prepare("DELETE FROM horarios_doctores WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        // Registrar en logs
        registrarLogHorario($data['id'], 'eliminar', $horario, null);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Horario eliminado exitosamente"
        ]);
        
    } catch (Exception $e) {
        error_log("Error en eliminarHorario: " . $e->getMessage());
        throw $e;
    }
}

// Funciones auxiliares
function verificarPermisoDoctor($doctor_id) {
    global $userData, $conn;
    
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

function calcularDuracionMinutos($hora_inicio, $hora_fin) {
    $inicio = strtotime($hora_inicio);
    $fin = strtotime($hora_fin);
    
    return ($fin - $inicio) / 60;
}

function verificarSolapamiento($doctor_id, $fecha_inicio, $dia_semana, $hora_inicio, $hora_fin, $excluir_id = null) {
    global $conn;
    
    $sql = "SELECT id, hora_inicio, hora_fin FROM horarios_doctores 
            WHERE doctor_id = ? 
            AND fecha_inicio = ? 
            AND dia_semana = ? 
            AND activo = 1
            AND (
                (hora_inicio < ? AND hora_fin > ?)
            )";
    
    $params = [$doctor_id, $fecha_inicio, $dia_semana, $hora_fin, $hora_inicio];
    
    if ($excluir_id) {
        $sql .= " AND id != ?";
        $params[] = $excluir_id;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch() !== false;
}

function registrarLogHorario($horario_id, $accion, $datos_anteriores, $datos_nuevos) {
    global $conn, $userData;
    
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