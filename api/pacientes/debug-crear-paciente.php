<?php
// api/pacientes/debug-crear-paciente.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

require_once '../config/database.php';
include_once '../config/jwt.php';

// Log inicial
error_log("=== DEBUG CREAR PACIENTE ===");

// Obtener JWT del encabezado
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

error_log("JWT recibido: " . ($jwt ? "SÍ" : "NO"));
error_log("Headers recibidos: " . json_encode($headers));

// Validar token
$userData = validateJWT($jwt);
if (!$userData) {
    error_log("Error: Token inválido");
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

error_log("Usuario autenticado: " . json_encode($userData));

try {
    // Obtener el cuerpo JSON del request
    $input = file_get_contents("php://input");
    error_log("Datos RAW recibidos: " . $input);
    
    $data = json_decode($input, true);
    
    if ($data === null) {
        error_log("Error: JSON inválido");
        http_response_code(400);
        echo json_encode([
            "error" => "JSON inválido",
            "raw_input" => $input
        ]);
        exit;
    }

    error_log("Datos decodificados: " . json_encode($data));

    // Validar los campos básicos requeridos
    $campos_requeridos = ['nombre', 'apellido', 'cedula', 'telefono', 'tipo'];
    $campos_faltantes = [];
    
    foreach ($campos_requeridos as $campo) {
        if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
            $campos_faltantes[] = $campo;
        }
    }
    
    if (!empty($campos_faltantes)) {
        error_log("Error: Campos faltantes: " . json_encode($campos_faltantes));
        http_response_code(400);
        echo json_encode([
            "error" => "Faltan datos básicos requeridos",
            "campos_faltantes" => $campos_faltantes,
            "campos_recibidos" => array_keys($data),
            "valores_recibidos" => $data
        ]);
        exit;
    }

    // Verificar si ya existe un paciente con esa cédula
    $stmt = $conn->prepare("SELECT id FROM pacientes WHERE cedula = :cedula");
    $stmt->bindParam(':cedula', $data['cedula']);
    $stmt->execute();
    if ($stmt->fetch()) {
        error_log("Error: Cédula ya existe");
        http_response_code(400);
        echo json_encode([
            "error" => "Ya existe un paciente con esa cédula",
            "cedula" => $data['cedula']
        ]);
        exit;
    }

    // Preparar variables
    $nombre = trim($data['nombre']);
    $apellido = trim($data['apellido']);
    $cedula = trim($data['cedula']);
    $telefono = trim($data['telefono']);
    $email = isset($data['email']) ? trim($data['email']) : '';
    $tipo = trim($data['tipo']);
    
    error_log("Variables preparadas: nombre=$nombre, apellido=$apellido, cedula=$cedula, telefono=$telefono, email=$email, tipo=$tipo");
    
    // Verificar estructura de tabla
    $stmt = $conn->prepare("DESCRIBE pacientes");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    error_log("Columnas de pacientes: " . json_encode(array_column($columns, 'Field')));
    
    // SQL base para inserción
    $sql = "INSERT INTO pacientes (nombre, apellido, cedula, telefono, email, tipo";
    $valores = " VALUES (:nombre, :apellido, :cedula, :telefono, :email, :tipo";
    
    $parametros = [
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':cedula' => $cedula,
        ':telefono' => $telefono,
        ':email' => $email,
        ':tipo' => $tipo
    ];
    
    // Si es asegurado y se proporcionan datos adicionales (opcional)
    if ($tipo === 'asegurado') {
        if (isset($data['titular_id']) && !empty($data['titular_id'])) {
            $sql .= ", titular_id";
            $valores .= ", :titular_id";
            $parametros[':titular_id'] = $data['titular_id'];
        }
        
        if (isset($data['parentesco']) && !empty($data['parentesco'])) {
            $sql .= ", parentesco";
            $valores .= ", :parentesco";
            $parametros[':parentesco'] = $data['parentesco'];
        }
        
        if (isset($data['es_titular'])) {
            $sql .= ", es_titular";
            $valores .= ", :es_titular";
            $parametros[':es_titular'] = $data['es_titular'] ? 1 : 0;
        }
    }
    
    // Cerrar la consulta SQL
    $sql .= ")";
    $valores .= ")";
    $sql_completo = $sql . $valores;
    
    error_log("SQL a ejecutar: " . $sql_completo);
    error_log("Parámetros: " . json_encode($parametros));
    
    // Preparar y ejecutar la sentencia
    $stmt = $conn->prepare($sql_completo);
    
    foreach ($parametros as $param => $value) {
        $tipo_param = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($param, $value, $tipo_param);
        error_log("Binding $param = $value (tipo: $tipo_param)");
    }
    
    $resultado = $stmt->execute();
    error_log("Resultado de execute(): " . ($resultado ? "TRUE" : "FALSE"));
    
    if (!$resultado) {
        $errorInfo = $stmt->errorInfo();
        error_log("Error SQL: " . json_encode($errorInfo));
        throw new Exception("Error SQL: " . $errorInfo[2]);
    }
    
    $paciente_id = $conn->lastInsertId();
    error_log("Paciente creado con ID: " . $paciente_id);
    
    // Responder al frontend
    echo json_encode([
        "success" => true,
        "message" => "Paciente registrado correctamente (modo debug)",
        "paciente_id" => $paciente_id,
        "debug_info" => [
            "sql_ejecutado" => $sql_completo,
            "parametros" => $parametros,
            "usuario" => $userData
        ]
    ]);

} catch (Exception $e) {
    error_log("Error al crear paciente: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        "error" => "Error en el servidor: " . $e->getMessage(),
        "debug" => true
    ]);
}
?>