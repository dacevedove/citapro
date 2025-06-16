<?php
// api/usuarios/delete_user_photo.php
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

// Verificar que el usuario es admin
if ($userData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Sin permisos para esta acción"]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$input = json_decode(file_get_contents('php://input'), true);
$targetUserId = $input['user_id'] ?? null;

if (!$targetUserId) {
    http_response_code(400);
    echo json_encode(["error" => "ID de usuario requerido"]);
    exit;
}

// Configuración de directorio de uploads
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
$uploadDir = $documentRoot . '/uploads/profile_photos/';
$webPath = '/uploads/profile_photos/';

try {
    // Verificar que el usuario objetivo existe y obtener foto actual
    $stmt = $conn->prepare("SELECT id, foto_perfil FROM usuarios WHERE id = :user_id");
    $stmt->bindParam(':user_id', $targetUserId);
    $stmt->execute();
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        throw new Exception("Usuario no encontrado");
    }
    
    if (!$targetUser['foto_perfil']) {
        throw new Exception("El usuario no tiene foto de perfil para eliminar");
    }
    
    // Construir ruta del archivo
    $filePath = $documentRoot . $targetUser['foto_perfil'];
    
    // Eliminar archivo del servidor
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            throw new Exception("Error al eliminar el archivo del servidor");
        }
    }
    
    // Actualizar base de datos
    $updateStmt = $conn->prepare("UPDATE usuarios SET foto_perfil = NULL WHERE id = :user_id");
    $updateStmt->bindParam(':user_id', $targetUserId);
    $updateStmt->execute();
    
    // Registrar en logs de auditoría
    $logStmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_anteriores, fecha_accion) 
                              VALUES (:admin_id, 'usuarios', :target_id, 'ADMIN_DELETE_PHOTO', :datos_anteriores, NOW())");
    
    $datos_anteriores = json_encode([
        'foto_perfil_eliminada' => $targetUser['foto_perfil'],
        'admin_id' => $userData['id']
    ]);
    
    $logStmt->bindParam(':admin_id', $userData['id']);
    $logStmt->bindParam(':target_id', $targetUserId);
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