<?php
// api/auth/login.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

// Validar datos requeridos
if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Email y contraseña son requeridos"]);
    exit;
}

$email = $data['email'];
$password = $data['password'];

try {
    // Buscar usuario por email - INCLUIR FOTO_PERFIL
    $stmt = $conn->prepare("
        SELECT id, nombre, apellido, email, password, cedula, telefono, role, 
               esta_activo, email_verificado, foto_perfil, ultimo_acceso, creado_en
        FROM usuarios 
        WHERE email = :email 
        LIMIT 1
    ");
    
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar si el usuario existe
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
    
    // Verificar si el usuario está activo
    if (!$user['esta_activo']) {
        http_response_code(403);
        echo json_encode(["error" => "Cuenta desactivada. Contacte al administrador."]);
        exit;
    }
    
    // Actualizar último acceso
    $updateStmt = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id");
    $updateStmt->bindParam(':id', $user['id']);
    $updateStmt->execute();
    
    // Generar JWT
    $userData = [
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
    
    $jwt = generateJWT($userData);
    
    // Preparar datos del usuario para respuesta (sin contraseña)
    unset($user['password']);
    
    // Formatear fecha de último acceso
    $user['ultimo_acceso'] = date('Y-m-d H:i:s'); // Fecha actual ya que acabamos de actualizarla
    
    // Registrar login en logs de auditoría
    try {
        $logStmt = $conn->prepare("
            INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, direccion_ip, navegador, fecha_accion) 
            VALUES (:user_id, 'usuarios', :user_id, 'LOGIN', :ip, :user_agent, NOW())
        ");
        
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $logStmt->bindParam(':user_id', $user['id']);
        $logStmt->bindParam(':ip', $ip);
        $logStmt->bindParam(':user_agent', $userAgent);
        $logStmt->execute();
    } catch (Exception $e) {
        // No fallar por error de log
        error_log("Error guardando log de login: " . $e->getMessage());
    }
    
    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Login exitoso",
        "token" => $jwt,
        "user" => $user
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en la base de datos"]);
    error_log("Error de BD en login: " . $e->getMessage());
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error interno del servidor"]);
    error_log("Error en login: " . $e->getMessage());
}
?>