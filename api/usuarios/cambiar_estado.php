<?php
// api/usuarios/cambiar_estado.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once '../config/database.php';
include_once '../config/jwt.php';

$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

$userData = validateJWT($jwt);
if (!$userData || $userData['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->user_id) || !isset($data->activo)) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Verificar que el usuario existe
    $stmt = $conn->prepare("SELECT id, esta_activo, role FROM usuarios WHERE id = :user_id");
    $stmt->bindParam(':user_id', $data->user_id);
    $stmt->execute();
    
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        throw new Exception("Usuario no encontrado");
    }
    
    // No permitir desactivar el propio usuario
    if ($data->user_id == $userData['id']) {
        throw new Exception("No puedes desactivar tu propia cuenta");
    }
    
    $estado_anterior = $usuario['esta_activo'];
    
    // Actualizar el estado
    $stmt = $conn->prepare("
        UPDATE usuarios 
        SET esta_activo = :activo 
        WHERE id = :user_id
    ");
    
    $stmt->bindParam(':activo', $data->activo, PDO::PARAM_BOOL);
    $stmt->bindParam(':user_id', $data->user_id);
    $stmt->execute();
    
    // Log de auditoría
    $stmt = $conn->prepare("
        INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_anteriores, datos_nuevos)
        VALUES (:admin_id, 'usuarios', :user_id, 'UPDATE', :datos_anteriores, :datos_nuevos)
    ");
    
    $datos_anteriores = json_encode(['esta_activo' => $estado_anterior]);
    $datos_nuevos = json_encode(['esta_activo' => $data->activo]);
    
    $stmt->bindParam(':admin_id', $userData['id']);
    $stmt->bindParam(':user_id', $data->user_id);
    $stmt->bindParam(':datos_anteriores', $datos_anteriores);
    $stmt->bindParam(':datos_nuevos', $datos_nuevos);
    $stmt->execute();
    
    $conn->commit();
    
    if ($stmt->rowCount() > 0) {
        $accion = $data->activo ? 'activado' : 'desactivado';
        http_response_code(200);
        echo json_encode([
            "message" => "Usuario $accion exitosamente",
            "estado_anterior" => $estado_anterior,
            "estado_nuevo" => $data->activo
        ]);
    } else {
        throw new Exception("No se pudo actualizar el estado del usuario");
    }
    
} catch(Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>