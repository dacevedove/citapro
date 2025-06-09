<?php
// api/usuarios/crear.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivos de configuración
include_once '../config/database.php';
include_once '../config/jwt.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
$campos_requeridos = ['nombre', 'apellido', 'email', 'cedula', 'password', 'role'];
foreach ($campos_requeridos as $campo) {
    if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
        http_response_code(400);
        echo json_encode(["error" => "El campo {$campo} es requerido"]);
        exit;
    }
}

$nombre = trim($data['nombre']);
$apellido = trim($data['apellido']);
$email = trim($data['email']);
$cedula = trim($data['cedula']);
$telefono = isset($data['telefono']) ? trim($data['telefono']) : '';
$password = $data['password'];
$role = $data['role'];
$esta_activo = isset($data['esta_activo']) ? $data['esta_activo'] : true;

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["error" => "Formato de email inválido"]);
    exit;
}

// Validar que el rol sea válido
$roles_validos = ['admin', 'doctor', 'aseguradora', 'paciente', 'vertice'];
if (!in_array($role, $roles_validos)) {
    http_response_code(400);
    echo json_encode(["error" => "Rol no válido"]);
    exit;
}

// Validar longitud de contraseña
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(["error" => "La contraseña debe tener al menos 8 caracteres"]);
    exit;
}

try {
    // Verificar si el email ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["error" => "El email ya está registrado"]);
        exit;
    }
    
    // Verificar si la cédula ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE cedula = :cedula");
    $stmt->bindParam(':cedula', $cedula);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["error" => "La cédula ya está registrada"]);
        exit;
    }
    
    // Hashear la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, apellido, email, cedula, telefono, password, role, esta_activo, creado_en) 
                           VALUES (:nombre, :apellido, :email, :cedula, :telefono, :password, :role, :esta_activo, NOW())");
    
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':apellido', $apellido);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':cedula', $cedula);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':password', $password_hash);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':esta_activo', $esta_activo);
    
    $stmt->execute();
    $nuevo_usuario_id = $conn->lastInsertId();
    
    // Registrar la acción en logs de auditoría
    $stmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_nuevos, fecha_accion) 
                           VALUES (:admin_id, 'usuarios', :user_id, 'INSERT', :datos_nuevos, NOW())");
    
    $datos_nuevos = json_encode([
        'nombre' => $nombre,
        'apellido' => $apellido,
        'email' => $email,
        'cedula' => $cedula,
        'role' => $role,
        'esta_activo' => $esta_activo
    ]);
    
    $stmt->bindParam(':admin_id', $userData['id']);
    $stmt->bindParam(':user_id', $nuevo_usuario_id);
    $stmt->bindParam(':datos_nuevos', $datos_nuevos);
    $stmt->execute();
    
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Usuario creado correctamente",
        "user_id" => $nuevo_usuario_id
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al crear usuario: " . $e->getMessage()]);
}
?>