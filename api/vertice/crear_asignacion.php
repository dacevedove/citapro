<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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
if (!$userData || $userData['role'] !== 'vertice') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (!isset($data->solicitud_id) || !isset($data->turno_id) || !isset($data->doctor_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Iniciar transacción
    // Obtenemos la conexión dependiendo de cómo está implementada en database.php
    // Verificamos si existe la función getConnection() o si la conexión ya está disponible como $conn
    if (function_exists('getConnection')) {
        $conn = getConnection();
    } elseif (class_exists('Database')) {
        $conn = Database::getConnection();
    } elseif (!isset($conn)) {
        // Si no podemos obtener la conexión, lanzamos una excepción
        throw new Exception("No se pudo establecer la conexión con la base de datos");
    }
    
    $conn->beginTransaction();
    
    // Verificar si la solicitud existe y está pendiente
    $stmt = $conn->prepare("
        SELECT id, estatus, nombre_paciente, telefono, cedula_paciente, especialidad_requerida
        FROM temp_solicitudes 
        WHERE id = :solicitud_id
    ");
    $stmt->bindParam(':solicitud_id', $data->solicitud_id);
    $stmt->execute();
    
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$solicitud) {
        throw new Exception("La solicitud no existe");
    }
    if ($solicitud['estatus'] !== 'pendiente') {
        throw new Exception("La solicitud ya no está pendiente");
    }
    
    // Verificar si el turno existe y está disponible
    $stmt = $conn->prepare("
        SELECT td.id, td.estado, td.hora_inicio, td.hora_fin, hd.fecha, hd.dia_semana
        FROM temp_turnos_disponibles td
        JOIN temp_horarios_doctores hd ON td.horario_id = hd.id
        WHERE td.id = :turno_id
    ");
    $stmt->bindParam(':turno_id', $data->turno_id);
    $stmt->execute();
    
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$turno) {
        throw new Exception("El turno no existe");
    }
    if ($turno['estado'] !== 'disponible') {
        throw new Exception("El turno seleccionado no está disponible");
    }
    
    // Verificar si el doctor existe y está activo
    $stmt = $conn->prepare("
        SELECT d.id, u.nombre, u.apellido, e.nombre as especialidad
        FROM doctores d
        JOIN usuarios u ON d.usuario_id = u.id
        JOIN especialidades e ON d.especialidad_id = e.id
        JOIN temp_doctores_activos tda ON d.id = tda.doctor_id
        WHERE d.id = :doctor_id AND tda.activo = 1
    ");
    $stmt->bindParam(':doctor_id', $data->doctor_id);
    $stmt->execute();
    
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doctor) {
        throw new Exception("El doctor no existe o no está activo");
    }
    
    // Obtener datos del consultorio
    $stmt = $conn->prepare("
        SELECT c.id, c.nombre, c.ubicacion 
        FROM consultorios c
        JOIN doctores d ON d.id = :doctor_id
        LIMIT 1
    ");
    $stmt->bindParam(':doctor_id', $data->doctor_id);
    $stmt->execute();
    
    $consultorio = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$consultorio) {
        // Si no hay consultorio asociado, asignamos uno genérico
        $consultorio = [
            'id' => 1, // ID por defecto
            'nombre' => 'Consultorio Principal',
            'ubicacion' => 'Planta Baja'
        ];
    }
    
    // Crear cita en la tabla principal
    $stmt = $conn->prepare("
        INSERT INTO citas 
        (paciente_id, doctor_id, especialidad_id, consultorio_id, fecha, hora, estado, 
        descripcion, asignacion_libre, tipo_solicitante, creado_por) 
        VALUES 
        (:paciente_id, :doctor_id, :especialidad_id, :consultorio_id, :fecha, :hora, 
        'asignada', :descripcion, 0, 'aseguradora', :creado_por)
    ");
    
    // Buscar o crear el paciente primero
    $pacienteId = null;
    
    // Buscar paciente por cédula
    $stmtBuscarPaciente = $conn->prepare("
        SELECT id FROM pacientes WHERE cedula = :cedula
    ");
    $stmtBuscarPaciente->bindParam(':cedula', $solicitud['cedula_paciente']);
    $stmtBuscarPaciente->execute();
    $pacienteExistente = $stmtBuscarPaciente->fetch(PDO::FETCH_ASSOC);
    
    if ($pacienteExistente) {
        $pacienteId = $pacienteExistente['id'];
    } else {
        // Extraer nombre y apellido del nombre completo
        $nombreCompleto = explode(' ', $solicitud['nombre_paciente'], 2);
        $nombre = $nombreCompleto[0];
        $apellido = isset($nombreCompleto[1]) ? $nombreCompleto[1] : '';
        
        // Crear nuevo paciente
        $stmtCrearPaciente = $conn->prepare("
            INSERT INTO pacientes 
            (nombre, apellido, cedula, telefono, email, tipo) 
            VALUES 
            (:nombre, :apellido, :cedula, :telefono, '', 'asegurado')
        ");
        
        $stmtCrearPaciente->bindParam(':nombre', $nombre);
        $stmtCrearPaciente->bindParam(':apellido', $apellido);
        $stmtCrearPaciente->bindParam(':cedula', $solicitud['cedula_paciente']);
        $stmtCrearPaciente->bindParam(':telefono', $solicitud['telefono']);
        $stmtCrearPaciente->execute();
        
        $pacienteId = $conn->lastInsertId();
    }
    
    // Determinar la fecha correcta
    $fechaCita = $turno['fecha'];
    if (!$fechaCita && $turno['dia_semana']) {
        // Si no hay fecha específica pero hay día de semana, calcular la próxima fecha
        $fechaActual = new DateTime();
        $diaSemanaActual = $fechaActual->format('N'); // 1=lunes, 7=domingo
        $diasParaAgregar = ($turno['dia_semana'] - $diaSemanaActual + 7) % 7;
        if ($diasParaAgregar === 0) $diasParaAgregar = 7; // Si hoy es el día, ir a la próxima semana
        $fechaActual->add(new DateInterval("P{$diasParaAgregar}D"));
        $fechaCita = $fechaActual->format('Y-m-d');
    }
    
    // Preparar datos para la cita
    $descripcion = "Cita asignada automáticamente a partir de solicitud #{$solicitud['id']}";
    
    $stmt->bindParam(':paciente_id', $pacienteId);
    $stmt->bindParam(':doctor_id', $data->doctor_id);
    $stmt->bindParam(':especialidad_id', $solicitud['especialidad_requerida']);
    $stmt->bindParam(':consultorio_id', $consultorio['id']);
    $stmt->bindParam(':fecha', $fechaCita);
    $stmt->bindParam(':hora', $turno['hora_inicio']);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->bindParam(':creado_por', $userData['id']);
    
    $stmt->execute();
    
    $citaId = $conn->lastInsertId();
    
    // Crear asignación en la tabla temporal
    $stmt = $conn->prepare("
        INSERT INTO temp_asignaciones 
        (solicitud_id, turno_id, doctor_id, estado, asignado_por) 
        VALUES 
        (:solicitud_id, :turno_id, :doctor_id, 'asignada', :asignado_por)
    ");
    
    $stmt->bindParam(':solicitud_id', $data->solicitud_id);
    $stmt->bindParam(':turno_id', $data->turno_id);
    $stmt->bindParam(':doctor_id', $data->doctor_id);
    $stmt->bindParam(':asignado_por', $userData['id']);
    
    $stmt->execute();
    
    $asignacionId = $conn->lastInsertId();
    
    // Actualizar estado del turno
    $stmt = $conn->prepare("
        UPDATE temp_turnos_disponibles 
        SET estado = 'reservado' 
        WHERE id = :turno_id
    ");
    $stmt->bindParam(':turno_id', $data->turno_id);
    $stmt->execute();
    
    // Actualizar estado de la solicitud
    $stmt = $conn->prepare("
        UPDATE temp_solicitudes 
        SET estatus = 'procesada' 
        WHERE id = :solicitud_id
    ");
    $stmt->bindParam(':solicitud_id', $data->solicitud_id);
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    // Devolver respuesta sin intentar enviar notificación por ahora
    http_response_code(201);
    echo json_encode([
        "message" => "Asignación creada exitosamente",
        "id" => $asignacionId,
        "cita_id" => $citaId,
        "telefono" => $solicitud['telefono']
    ]);
    
} catch(Exception $e) {
    // Revertir cambios en caso de error
    if (isset($conn)) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}