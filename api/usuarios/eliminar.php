<?php
// api/usuarios/eliminar.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivos de configuración
include_once '../config/database.php';
include_once '../config/jwt.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

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

// Verificar que el usuario sea admin
if ($userData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

// Obtener ID del usuario a eliminar
$user_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $user_id = isset($_GET['id']) ? $_GET['id'] : null;
} else {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = isset($data['user_id']) ? $data['user_id'] : null;
}

if (!$user_id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de usuario requerido"]);
    exit;
}

// Prevenir que el admin se elimine a sí mismo
if ($user_id == $userData['id']) {
    http_response_code(400);
    echo json_encode(["error" => "No puede eliminarse a sí mismo"]);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Obtener información del usuario antes de eliminarlo
    $stmt = $conn->prepare("SELECT nombre, apellido, email, role FROM usuarios WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        throw new Exception("Usuario no encontrado");
    }
    
    // Verificar dependencias antes de eliminar
    $dependencias = [];
    
    // Verificar si es doctor y tiene citas
    if ($usuario['role'] === 'doctor') {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM citas WHERE doctor_id IN (SELECT id FROM doctores WHERE usuario_id = :user_id)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            $dependencias[] = "citas médicas";
        }
    }
    
    // Verificar si es aseguradora y tiene titulares
    if ($usuario['role'] === 'aseguradora') {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM titulares WHERE aseguradora_id IN (SELECT id FROM aseguradoras WHERE usuario_id = :user_id)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            $dependencias[] = "titulares de pólizas";
        }
    }
    
    // Si hay dependencias, no permitir eliminación
    if (!empty($dependencias)) {
        throw new Exception("No se puede eliminar el usuario porque tiene " . implode(", ", $dependencias) . " asociados. Desactive el usuario en su lugar.");
    }
    
    // Eliminar registros relacionados primero
    if ($usuario['role'] === 'doctor') {
        $stmt = $conn->prepare("DELETE FROM doctores WHERE usuario_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
    
    if ($usuario['role'] === 'aseguradora') {
        $stmt = $conn->prepare("DELETE FROM aseguradoras WHERE usuario_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
    
    // Registrar la acción en logs de auditoría antes de eliminar
    $stmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_anteriores, fecha_accion) 
                           VALUES (:admin_id, 'usuarios', :user_id, 'DELETE', :datos_anteriores, NOW())");
    
    $datos_anteriores = json_encode($usuario);
    $stmt->bindParam(':admin_id', $userData['id']);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':datos_anteriores', $datos_anteriores);
    $stmt->execute();
    
    // Eliminar el usuario
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $conn->commit();
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Usuario eliminado correctamente",
        "usuario_eliminado" => $usuario['nombre'] . ' ' . $usuario['apellido']
    ]);
    
} catch(Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["error" => "Error al eliminar usuario: " . $e->getMessage()]);
}
?>