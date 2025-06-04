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
    // Log para debugging
    error_log("Intento de login para email: " . $data->email);
    
    // Buscar el usuario en la base de datos
    $stmt = $conn->prepare("SELECT id, nombre, apellido, email, password, cedula, telefono, role FROM usuarios WHERE email = :email");
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log para debugging
    error_log("Usuario encontrado: " . ($user ? 'Sí' : 'No'));
    
    // Verificar si se encontró el usuario
    if (!$user) {
        // Log detallado para debugging
        error_log("USUARIO NO ENCONTRADO - Email buscado: " . $data->email);
        
        // Verificar cuántos usuarios hay en total
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios");
        $countStmt->execute();
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        error_log("Total de usuarios en BD: " . $count['total']);
        
        // Listar los primeros 3 emails para comparar
        $listStmt = $conn->prepare("SELECT email FROM usuarios LIMIT 3");
        $listStmt->execute();
        $emails = $listStmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Emails existentes: " . implode(', ', $emails));
        
        http_response_code(401); // Unauthorized
        echo json_encode([
            "error" => "Usuario no encontrado", 
            "email_buscado" => $data->email,
            "total_usuarios" => $count['total'],
            "emails_existentes" => $emails
        ]);
        exit;
    }
    
    // Log la contraseña hasheada para debugging
    error_log("Contraseña hasheada en BD: " . substr($user['password'], 0, 10) . "...");
    
    // Verificar contraseña
    $passwordIsValid = password_verify($data->password, $user['password']);
    
    // Log para debugging
    error_log("Contraseña válida: " . ($passwordIsValid ? 'Sí' : 'No'));
    
    // PARA DEBUGGING: También probar con contraseña directa (SOLO PARA DESARROLLO)
    if (!$passwordIsValid) {
        // Verificar si la contraseña coincide directamente (para cuentas de prueba)
        $passwordIsValid = ($data->password === $user['password']);
        error_log("Verificación directa: " . ($passwordIsValid ? 'Sí' : 'No'));
    }
    
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
        
        // Log exitoso
        error_log("Login exitoso para: " . $user['email']);
        
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
    // Log el error
    error_log("Error PDO en login: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>