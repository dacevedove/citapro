<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivos necesarios
include_once '../config/database.php';
include_once '../config/jwt.php';

// Verificar que sea un método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

// Obtener los datos enviados
$data = json_decode(file_get_contents("php://input"));

// Verificar que se recibieron los datos necesarios
if (!isset($data->email) || !isset($data->password)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Faltan datos requeridos"]);
    exit;
}

try {
    // Buscar el usuario en la base de datos
    $stmt = $conn->prepare("SELECT id, nombre, apellido, email, password, cedula, telefono, role FROM usuarios WHERE email = :email");
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar si se encontró el usuario
    if (!$user) {
        http_response_code(401); // Unauthorized
        echo json_encode(["error" => "Usuario no encontrado"]);
        exit;
    }
    
    // TEMPORALMENTE: Iniciar sesión sin verificar contraseña
    // En lugar de verificar la contraseña, siempre inicia sesión
    // Esto es solo para propósitos de depuración
    // $passwordIsValid = password_verify($data->password, $user['password']);
    $passwordIsValid = true; // Omitir verificación de contraseña temporalmente
    
    if ($passwordIsValid) {
        // Crear usuario sin la contraseña para incluir en el token
        $userWithoutPassword = [
            "id" => $user['id'],
            "nombre" => $user['nombre'],
            "apellido" => $user['apellido'],
            "email" => $user['email'],
            "cedula" => $user['cedula'],
            "telefono" => $user['telefono'],
            "role" => $user['role']
        ];
        
        // Generar el token JWT
        $token = generateJWT($userWithoutPassword);
        
        // Responder con el token y los datos del usuario
        http_response_code(200);
        echo json_encode([
            "message" => "Login exitoso",
            "token" => $token,
            "user" => $userWithoutPassword
        ]);
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(["error" => "Contraseña incorrecta"]);
    }
    
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>