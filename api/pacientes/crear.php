<?php
// api/pacientes/crear.php
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
    // Obtener el cuerpo JSON del request
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    // Log para debugging
    error_log("Crear paciente - Datos recibidos: " . $input);
    
    if ($data === null) {
        http_response_code(400);
        echo json_encode([
            "error" => "JSON inválido"
        ]);
        exit;
    }

    // Validar los campos básicos requeridos para todos los tipos de pacientes
    if (!isset($data['nombre']) || !isset($data['apellido']) || !isset($data['cedula']) || 
        !isset($data['telefono']) || !isset($data['tipo'])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Faltan datos básicos requeridos",
            "requeridos" => ["nombre", "apellido", "cedula", "telefono", "tipo"],
            "recibidos" => array_keys($data)
        ]);
        exit;
    }

    // Preparar variables comunes
    $nombre = $data['nombre'];
    $apellido = $data['apellido'];
    $cedula = $data['cedula'];
    $telefono = $data['telefono'];
    $email = $data['email'] ?? '';
    $tipo = $data['tipo'];
    
    // SQL base
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
        // Solo agregar titular_id y parentesco si se proporcionan
        if (isset($data['titular_id'])) {
            $titular_id = $data['titular_id'];
            $sql .= ", titular_id";
            $valores .= ", :titular_id";
            $parametros[':titular_id'] = $titular_id;
        }
        
        if (isset($data['parentesco'])) {
            $parentesco = $data['parentesco'];
            $sql .= ", parentesco";
            $valores .= ", :parentesco";
            $parametros[':parentesco'] = $parentesco;
        }
        
        // Agregar es_titular como campo opcional
        if (isset($data['es_titular'])) {
            $es_titular = $data['es_titular'];
            $sql .= ", es_titular";
            $valores .= ", :es_titular";
            $parametros[':es_titular'] = $es_titular;
        }
    }
    
    // Cerrar la consulta SQL
    $sql .= ")";
    $valores .= ")";
    
    // Preparar la sentencia completa
    $stmt = $conn->prepare($sql . $valores);
    
    // Asignar todos los parámetros
    foreach ($parametros as $param => $value) {
        $tipo_param = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($param, $value, $tipo_param);
    }
    
    // Ejecutar
    $stmt->execute();
    
    // Responder al frontend
    echo json_encode([
        "status" => "success",
        "message" => "Paciente registrado correctamente"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>