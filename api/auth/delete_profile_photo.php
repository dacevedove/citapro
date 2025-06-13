<?php
// api/auth/delete_profile_photo.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
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
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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

// Configuración de directorio de uploads
$uploadDir = '../uploads/profile_photos/';
$webPath = '/uploads/profile_photos/';

try {
    // Obtener foto actual del usuario
    $stmt = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id = :user_id");
    $stmt->bindParam(':user_id', $userData['id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("Usuario no encontrado");
    }
    
    if (!$user['foto_perfil']) {
        throw new Exception("No hay foto de perfil para eliminar");
    }
    
    // Construir ruta del archivo
    $filePath = str_replace($webPath, $uploadDir, $user['foto_perfil']);
    
    // Eliminar archivo del servidor
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            throw new Exception("Error al eliminar el archivo del servidor");
        }
    }
    
    // Actualizar base de datos
    $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = NULL WHERE id = :user_id");
    $stmt->bindParam(':user_id', $userData['id']);
    $stmt->execute();
    
    // Registrar en logs de auditoría
    $logStmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_anteriores, fecha_accion) 
                              VALUES (:user_id, 'usuarios', :user_id, 'DELETE_PHOTO', :datos_anteriores, NOW())");
    
    $datos_anteriores = json_encode([
        'foto_perfil_eliminada' => $user['foto_perfil']
    ]);
    
    $logStmt->bindParam(':user_id', $userData['id']);
    $logStmt->bindParam(':datos_anteriores', $datos_anteriores);
    $logStmt->execute();
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Foto de perfil eliminada correctamente"
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>