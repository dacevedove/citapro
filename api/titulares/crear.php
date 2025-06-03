<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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
if (!$userData || ($userData['role'] !== 'aseguradora' && $userData['role'] !== 'admin')) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

// Validar datos recibidos
if (
    !isset($data->nombre) || !isset($data->apellido) || !isset($data->cedula) || 
    !isset($data->telefono) || !isset($data->numero_afiliado) || !isset($data->estado)
) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Si es rol de aseguradora, usar su ID asociado
    $aseguradoraId = null;
    
    if ($userData['role'] === 'aseguradora') {
        $stmt = $conn->prepare("SELECT id FROM aseguradoras WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $aseguradora = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$aseguradora) {
            throw new Exception("Aseguradora no encontrada");
        }
        
        $aseguradoraId = $aseguradora['id'];
    } else if ($userData['role'] === 'admin' && isset($data->aseguradora_id)) {
        $aseguradoraId = $data->aseguradora_id;
    } else {
        throw new Exception("ID de aseguradora no especificado");
    }
    
    // Verificar si el titular ya existe
    $stmt = $conn->prepare("SELECT id FROM titulares WHERE cedula = :cedula");
    $stmt->bindParam(':cedula', $data->cedula);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        throw new Exception("Ya existe un titular con esta cédula");
    }
    
    // Insertar titular
    $stmt = $conn->prepare("
        INSERT INTO titulares (nombre, apellido, cedula, telefono, email, estado, numero_afiliado, aseguradora_id) 
        VALUES (:nombre, :apellido, :cedula, :telefono, :email, :estado, :numero_afiliado, :aseguradora_id)
    ");
    
    $stmt->bindParam(':nombre', $data->nombre);
    $stmt->bindParam(':apellido', $data->apellido);
    $stmt->bindParam(':cedula', $data->cedula);
    $stmt->bindParam(':telefono', $data->telefono);
    $email = $data->email ?? null;
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':estado', $data->estado);
    $stmt->bindParam(':numero_afiliado', $data->numero_afiliado);
    $stmt->bindParam(':aseguradora_id', $aseguradoraId);
    
    $stmt->execute();
    
    $titularId = $conn->lastInsertId();
    
    // Si el titular es también paciente
    if (isset($data->es_paciente) && $data->es_paciente) {
        $stmt = $conn->prepare("
            INSERT INTO pacientes (nombre, apellido, cedula, telefono, email, estado, es_titular, titular_id, tipo) 
            VALUES (:nombre, :apellido, :cedula, :telefono, :email, :estado, TRUE, :titular_id, 'asegurado')
        ");
        
        $stmt->bindParam(':nombre', $data->nombre);
        $stmt->bindParam(':apellido', $data->apellido);
        $stmt->bindParam(':cedula', $data->cedula);
        $stmt->bindParam(':telefono', $data->telefono);
        $email = $data->email ?? null;
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':estado', $data->estado);
        $stmt->bindParam(':titular_id', $titularId);
        
        $stmt->execute();
    }
    
    // Confirmar transacción
    $conn->commit();
    
    http_response_code(201);
    echo json_encode([
        "message" => "Titular creado exitosamente",
        "id" => $titularId
    ]);
    
} catch(Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollBack();
    
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>