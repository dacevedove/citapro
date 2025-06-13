<?php
// api/auth/update_profile.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
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
if (!$userData) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Obtener datos del perfil del usuario
        $stmt = $conn->prepare("
            SELECT id, nombre, apellido, email, cedula, telefono, foto_perfil, role,
                   email_verificado, ultimo_acceso, creado_en
            FROM usuarios 
            WHERE id = :user_id
        ");
        $stmt->bindParam(':user_id', $userData['id']);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("Usuario no encontrado");
        }
        
        // No devolver información sensible
        unset($user['password']);
        
        // Formatear fechas
        if ($user['ultimo_acceso']) {
            $user['ultimo_acceso'] = date('d/m/Y H:i', strtotime($user['ultimo_acceso']));
        }
        $user['creado_en'] = date('d/m/Y H:i', strtotime($user['creado_en']));
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "user" => $user
        ]);
        
    } elseif ($method === 'POST') {
        // Actualizar datos del perfil
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data) {
            throw new Exception("Datos no válidos");
        }
        
        // Verificar que el usuario puede editar este perfil
        if (isset($data['id']) && $data['id'] != $userData['id']) {
            // Solo admin puede editar otros perfiles
            if ($userData['role'] !== 'admin') {
                throw new Exception("No tiene permisos para editar este perfil");
            }
            $target_user_id = $data['id'];
        } else {
            $target_user_id = $userData['id'];
        }
        
        // Obtener datos actuales para auditoría
        $stmt = $conn->prepare("SELECT nombre, apellido, telefono FROM usuarios WHERE id = :user_id");
        $stmt->bindParam(':user_id', $target_user_id);
        $stmt->execute();
        $currentData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Preparar campos a actualizar
        $updates = [];
        $params = [':user_id' => $target_user_id];
        $changes = [];
        
        if (isset($data['nombre']) && $data['nombre'] !== $currentData['nombre']) {
            $updates[] = "nombre = :nombre";
            $params[':nombre'] = trim($data['nombre']);
            $changes['nombre'] = ['anterior' => $currentData['nombre'], 'nuevo' => $params[':nombre']];
        }
        
        if (isset($data['apellido']) && $data['apellido'] !== $currentData['apellido']) {
            $updates[] = "apellido = :apellido";
            $params[':apellido'] = trim($data['apellido']);
            $changes['apellido'] = ['anterior' => $currentData['apellido'], 'nuevo' => $params[':apellido']];
        }
        
        if (isset($data['telefono']) && $data['telefono'] !== $currentData['telefono']) {
            $updates[] = "telefono = :telefono";
            $params[':telefono'] = trim($data['telefono']);
            $changes['telefono'] = ['anterior' => $currentData['telefono'], 'nuevo' => $params[':telefono']];
        }
        
        if (empty($updates)) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "No hay cambios que realizar"
            ]);
            exit;
        }
        
        // Ejecutar actualización
        $sql = "UPDATE usuarios SET " . implode(', ', $updates) . " WHERE id = :user_id";
        $stmt = $conn->prepare($sql);
        
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        
        // Registrar en logs de auditoría
        $logStmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_anteriores, datos_nuevos, fecha_accion) 
                                  VALUES (:admin_id, 'usuarios', :target_id, 'UPDATE_PROFILE', :datos_anteriores, :datos_nuevos, NOW())");
        
        $logStmt->bindParam(':admin_id', $userData['id']);
        $logStmt->bindParam(':target_id', $target_user_id);
        $logStmt->bindParam(':datos_anteriores', json_encode($currentData));
        $logStmt->bindParam(':datos_nuevos', json_encode($changes));
        $logStmt->execute();
        
        // Obtener datos actualizados
        $stmt = $conn->prepare("
            SELECT id, nombre, apellido, email, cedula, telefono, foto_perfil, role
            FROM usuarios 
            WHERE id = :user_id
        ");
        $stmt->bindParam(':user_id', $target_user_id);
        $stmt->execute();
        $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Perfil actualizado correctamente",
            "user" => $updatedUser
        ]);
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>