<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
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
    !isset($data->id) || !isset($data->nombre) || !isset($data->apellido) || 
    !isset($data->telefono) || !isset($data->especialidad_id)
) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Verificar si existe el doctor
    $stmt = $conn->prepare("
        SELECT d.id, d.usuario_id 
        FROM doctores d
        JOIN usuarios u ON d.usuario_id = u.id
        WHERE d.id = :id
    ");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        throw new Exception("Doctor no encontrado");
    }
    
    // Actualizar información del usuario
    $stmt = $conn->prepare("
        UPDATE usuarios 
        SET nombre = :nombre, apellido = :apellido, telefono = :telefono
        WHERE id = :usuario_id
    ");
    
    $stmt->bindParam(':nombre', $data->nombre);
    $stmt->bindParam(':apellido', $data->apellido);
    $stmt->bindParam(':telefono', $data->telefono);
    $stmt->bindParam(':usuario_id', $doctor['usuario_id']);
    
    $stmt->execute();
    
    // Actualizar información del doctor
    // Modificación: Incluir campo de subespecialidad_id
    $stmt = $conn->prepare("
        UPDATE doctores 
        SET especialidad_id = :especialidad_id, subespecialidad_id = :subespecialidad_id
        WHERE id = :id
    ");
    
    $stmt->bindParam(':especialidad_id', $data->especialidad_id);
    $stmt->bindParam(':id', $data->id);
    
    // Si no se proporcionó subespecialidad, usar NULL
    $subespecialidad_id = isset($data->subespecialidad_id) && !empty($data->subespecialidad_id) ? 
                         $data->subespecialidad_id : null;
    $stmt->bindParam(':subespecialidad_id', $subespecialidad_id);
    
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    http_response_code(200);
    echo json_encode([
        "message" => "Doctor actualizado exitosamente",
        "id" => $data->id
    ]);
    
} catch(Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollBack();
    
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>