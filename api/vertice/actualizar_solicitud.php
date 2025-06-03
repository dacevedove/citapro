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

// Obtener datos enviados
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (
    !isset($data->id) || 
    !isset($data->numero_id) || 
    !isset($data->cedula_titular) || 
    !isset($data->cedula_paciente) || 
    !isset($data->nombre_paciente) || 
    !isset($data->telefono) || 
    !isset($data->fecha_nacimiento) || 
    !isset($data->sexo) || 
    !isset($data->especialidad_requerida)
) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Verificar que la solicitud existe
    $stmt = $conn->prepare("SELECT id FROM temp_solicitudes WHERE id = :id");
    $stmt->bindParam(":id", $data->id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Solicitud no encontrada"]);
        exit;
    }
    
    // Actualizar la solicitud
    $stmt = $conn->prepare("
        UPDATE temp_solicitudes SET 
            numero_id = :numero_id,
            cedula_titular = :cedula_titular,
            cedula_paciente = :cedula_paciente,
            nombre_paciente = :nombre_paciente,
            telefono = :telefono,
            fecha_nacimiento = :fecha_nacimiento,
            sexo = :sexo,
            especialidad_requerida = :especialidad_requerida,
            actualizado_en = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    
    // Bind de parámetros
    $stmt->bindParam(":id", $data->id);
    $stmt->bindParam(":numero_id", $data->numero_id);
    $stmt->bindParam(":cedula_titular", $data->cedula_titular);
    $stmt->bindParam(":cedula_paciente", $data->cedula_paciente);
    $stmt->bindParam(":nombre_paciente", $data->nombre_paciente);
    $stmt->bindParam(":telefono", $data->telefono);
    $stmt->bindParam(":fecha_nacimiento", $data->fecha_nacimiento);
    $stmt->bindParam(":sexo", $data->sexo);
    $stmt->bindParam(":especialidad_requerida", $data->especialidad_requerida);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            "message" => "Solicitud actualizada exitosamente",
            "id" => $data->id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al actualizar la solicitud"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error en el servidor: " . $e->getMessage()
    ]);
}
?>