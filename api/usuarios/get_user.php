<?php
// /lgm/api/usuarios/get_user.php
require_once '../config/database.php';
require_once '../utils/jwt_helper.php';

// Verificar token y obtener usuario
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Token no proporcionado']);
    exit;
}

try {
    $decoded = JWTHelper::decode($token);
    $user_id = isset($_GET['id']) ? $_GET['id'] : $decoded->user_id;
    
    // Verificar si el usuario actual puede acceder a este perfil
    if ($decoded->user_id != $user_id && $decoded->role != 'admin') {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $conn = Database::getConnection();
    $stmt = $conn->prepare('SELECT id, nombre, apellido, email, cedula, telefono, role FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Token inválido o expirado']);
}
?>

<?php
// /lgm/api/usuarios/update_user.php
require_once '../config/database.php';
require_once '../utils/jwt_helper.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

// Obtener datos del cuerpo
$data = json_decode(file_get_contents("php://input"), true);
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Token no proporcionado']);
    exit;
}

try {
    $decoded = JWTHelper::decode($token);
    $user_id = isset($data['id']) ? $data['id'] : null;
    
    // Verificar permisos
    if (!$user_id || ($decoded->user_id != $user_id && $decoded->role != 'admin')) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    // Validar datos
    $nombre = isset($data['nombre']) ? $data['nombre'] : '';
    $apellido = isset($data['apellido']) ? $data['apellido'] : '';
    $telefono = isset($data['telefono']) ? $data['telefono'] : '';
    
    if (empty($nombre) || empty($apellido)) {
        echo json_encode(['success' => false, 'message' => 'Campos requeridos no proporcionados']);
        exit;
    }
    
    // Actualizar usuario
    $conn = Database::getConnection();
    $stmt = $conn->prepare('UPDATE usuarios SET nombre = ?, apellido = ?, telefono = ? WHERE id = ?');
    $stmt->bind_param('sssi', $nombre, $apellido, $telefono, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario']);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Token inválido o expirado']);
}
?>

<?php
// /lgm/api/usuarios/change_password.php
require_once '../config/database.php';
require_once '../utils/jwt_helper.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

// Obtener datos del cuerpo
$data = json_decode(file_get_contents("php://input"), true);
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Token no proporcionado']);
    exit;
}

try {
    $decoded = JWTHelper::decode($token);
    $user_id = isset($data['id']) ? $data['id'] : null;
    
    // Verificar permisos
    if (!$user_id || ($decoded->user_id != $user_id && $decoded->role != 'admin')) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    // Validar datos
    $current_password = isset($data['current_password']) ? $data['current_password'] : '';
    $new_password = isset($data['new_password']) ? $data['new_password'] : '';
    
    if (empty($current_password) || empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Campos requeridos no proporcionados']);
        exit;
    }
    
    // Verificar contraseña actual
    $conn = Database::getConnection();
    $stmt = $conn->prepare('SELECT password FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    $usuario = $result->fetch_assoc();
    
    if (!password_verify($current_password, $usuario['password'])) {
        echo json_encode(['success' => false, 'message' => 'Contraseña actual incorrecta']);
        exit;
    }
    
    // Actualizar contraseña
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
    $stmt->bind_param('si', $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar contraseña']);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Token inválido o expirado: ' . $e->getMessage()]);
}
?>