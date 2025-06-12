<?php
// api/usuarios/actualizar_rol.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivos de configuración
include_once '../config/database.php';
include_once '../config/jwt.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

// Obtener datos de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

// Validar datos requeridos
if (!isset($data['user_id']) || !isset($data['nuevo_role'])) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

$user_id = $data['user_id'];
$nuevo_role = $data['nuevo_role'];
$esta_activo = isset($data['esta_activo']) ? $data['esta_activo'] : null;

// Validar que el rol sea válido
$roles_validos = ['admin', 'doctor', 'aseguradora', 'paciente', 'coordinador', 'vertice'];
if (!in_array($nuevo_role, $roles_validos)) {
    http_response_code(400);
    echo json_encode(["error" => "Rol no válido"]);
    exit;
}

// Prevenir que el admin se quite sus propios permisos
if ($user_id == $userData['id'] && $nuevo_role !== 'admin') {
    http_response_code(400);
    echo json_encode(["error" => "No puede cambiar su propio rol de administrador"]);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Obtener información actual del usuario
    $stmt = $conn->prepare("SELECT role FROM usuarios WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $usuario_actual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario_actual) {
        throw new Exception("Usuario no encontrado");
    }
    
    $role_anterior = $usuario_actual['role'];
    
    // Actualizar el rol del usuario
    $sql = "UPDATE usuarios SET role = :nuevo_role";
    $params = [':user_id' => $user_id, ':nuevo_role' => $nuevo_role];
    
    if ($esta_activo !== null) {
        $sql .= ", esta_activo = :esta_activo";
        $params[':esta_activo'] = $esta_activo ? 1 : 0;
    }
    
    $sql .= " WHERE id = :user_id";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->execute();
    
    // Si el rol cambió de doctor a otro, eliminar registro de la tabla doctores
    if ($role_anterior === 'doctor' && $nuevo_role !== 'doctor') {
        $stmt = $conn->prepare("DELETE FROM doctores WHERE usuario_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
    
    // Si el rol cambió de aseguradora a otro, eliminar registro de la tabla aseguradoras
    if ($role_anterior === 'aseguradora' && $nuevo_role !== 'aseguradora') {
        $stmt = $conn->prepare("DELETE FROM aseguradoras WHERE usuario_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
    
    // Registrar la acción en logs de auditoría
    $stmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_anteriores, datos_nuevos, fecha_accion) 
                           VALUES (:admin_id, 'usuarios', :user_id, 'UPDATE', :datos_anteriores, :datos_nuevos, NOW())");
    
    $datos_anteriores = json_encode(['role' => $role_anterior]);
    $datos_nuevos = json_encode(['role' => $nuevo_role, 'esta_activo' => $esta_activo]);
    
    $stmt->bindParam(':admin_id', $userData['id']);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':datos_anteriores', $datos_anteriores);
    $stmt->bindParam(':datos_nuevos', $datos_nuevos);
    $stmt->execute();
    
    $conn->commit();
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Rol actualizado correctamente",
        "role_anterior" => $role_anterior,
        "role_nuevo" => $nuevo_role
    ]);
    
} catch(Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["error" => "Error al actualizar rol: " . $e->getMessage()]);
}
?>