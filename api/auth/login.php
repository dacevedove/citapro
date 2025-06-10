<?php
// api/auth/login.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../config/jwt.php';

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

// Obtener datos de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

// Validar datos requeridos
if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Email y contraseña son requeridos"]);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

try {
    // Buscar usuario por email
    $stmt = $conn->prepare("SELECT id, nombre, apellido, email, password, role, esta_activo 
                           FROM usuarios 
                           WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(["error" => "Credenciales inválidas"]);
        exit;
    }
    
    // Verificar contraseña
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["error" => "Credenciales inválidas"]);
        exit;
    }
    
    // Verificar que el usuario esté activo
    if (!$user['esta_activo']) {
        http_response_code(403);
        echo json_encode(["error" => "Usuario inactivo"]);
        exit;
    }
    
    // Actualizar último acceso
    $stmt = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    
    // Generar token JWT
    $token = generateJWT([
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'nombre' => $user['nombre'],
        'apellido' => $user['apellido']
    ]);
    
    // Registrar el login en logs de auditoría
    $stmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_nuevos, direccion_ip, fecha_accion) 
                           VALUES (:user_id, 'usuarios', :user_id, 'SELECT', :datos_nuevos, :ip, NOW())");
    
    $datos_nuevos = json_encode(['accion' => 'login_exitoso']);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->bindParam(':datos_nuevos', $datos_nuevos);
    $stmt->bindParam(':ip', $ip);
    $stmt->execute();
    
    // Remover contraseña de la respuesta
    unset($user['password']);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "token" => $token,
        "user" => $user,
        "message" => "Login exitoso"
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>