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
if (!$userData || $userData['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (
    !isset($data->nombre) || !isset($data->apellido) || !isset($data->cedula) || 
    !isset($data->email) || !isset($data->telefono) || !isset($data->especialidad_id) ||
    !isset($data->password)
) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Verificar si ya existe un usuario con este email o cédula
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email OR cedula = :cedula");
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':cedula', $data->cedula);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        throw new Exception("Ya existe un usuario con este email o cédula");
    }
    
    // Crear usuario para el doctor
    $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO usuarios (nombre, apellido, email, password, cedula, telefono, role) 
        VALUES (:nombre, :apellido, :email, :password, :cedula, :telefono, 'doctor')
    ");
    
    $stmt->bindParam(':nombre', $data->nombre);
    $stmt->bindParam(':apellido', $data->apellido);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':password', $password_hash);
    $stmt->bindParam(':cedula', $data->cedula);
    $stmt->bindParam(':telefono', $data->telefono);
    
    $stmt->execute();
    
    $usuario_id = $conn->lastInsertId();
    
    // Crear doctor
    // Modificación: Incluir campo de subespecialidad_id
    $stmt = $conn->prepare("
        INSERT INTO doctores (usuario_id, especialidad_id, subespecialidad_id) 
        VALUES (:usuario_id, :especialidad_id, :subespecialidad_id)
    ");
    
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->bindParam(':especialidad_id', $data->especialidad_id);
    
    // Si no se proporcionó subespecialidad, usar NULL
    $subespecialidad_id = isset($data->subespecialidad_id) && !empty($data->subespecialidad_id) ? 
                          $data->subespecialidad_id : null;
    $stmt->bindParam(':subespecialidad_id', $subespecialidad_id);
    
    $stmt->execute();
    
    $doctor_id = $conn->lastInsertId();
    
    // Confirmar transacción
    $conn->commit();
    
    http_response_code(201);
    echo json_encode([
        "message" => "Doctor creado exitosamente",
        "id" => $doctor_id
    ]);
    
} catch(Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollBack();
    
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
