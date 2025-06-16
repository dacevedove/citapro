<?php
// api/usuarios/upload_user_photo.php
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

// Función para logging de debug
function logDebug($message) {
    error_log(date('Y-m-d H:i:s') . " - ADMIN_UPLOAD_PHOTO: " . $message);
}

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

// Verificar que el usuario es admin
if ($userData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Sin permisos para esta acción"]);
    exit;
}

// Obtener ID del usuario objetivo
$targetUserId = $_POST['user_id'] ?? null;
if (!$targetUserId) {
    http_response_code(400);
    echo json_encode(["error" => "ID de usuario requerido"]);
    exit;
}

logDebug("Admin " . $userData['id'] . " subiendo foto para usuario " . $targetUserId);

// Configuración de directorio de uploads - RUTA ABSOLUTA
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
$uploadDir = $documentRoot . '/uploads/profile_photos/';
$webPath = '/uploads/profile_photos/';

logDebug("Document root: " . $documentRoot);
logDebug("Directorio de uploads: " . $uploadDir);

// Verificar que el directorio existe y es escribible
if (!file_exists($uploadDir)) {
    logDebug("Error: El directorio no existe: " . $uploadDir);
    http_response_code(500);
    echo json_encode(["error" => "Directorio de uploads no encontrado"]);
    exit;
}

if (!is_writable($uploadDir)) {
    logDebug("Error: El directorio no tiene permisos de escritura: " . $uploadDir);
    http_response_code(500);
    echo json_encode(["error" => "El directorio no tiene permisos de escritura"]);
    exit;
}

try {
    // Verificar que el usuario objetivo existe
    $checkUserStmt = $conn->prepare("SELECT id, foto_perfil FROM usuarios WHERE id = :user_id");
    $checkUserStmt->bindParam(':user_id', $targetUserId);
    $checkUserStmt->execute();
    $targetUser = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        throw new Exception("Usuario objetivo no encontrado");
    }

    // Verificar si se subió un archivo
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "No se recibió ningún archivo";
        if (isset($_FILES['photo']['error'])) {
            switch($_FILES['photo']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_msg = "El archivo es demasiado grande";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_msg = "El archivo se subió parcialmente";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_msg = "No se seleccionó ningún archivo";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_msg = "Falta la carpeta temporal";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_msg = "Error al escribir el archivo en disco";
                    break;
                default:
                    $error_msg = "Error desconocido en la subida: " . $_FILES['photo']['error'];
            }
        }
        logDebug("Error en archivo: " . $error_msg);
        throw new Exception($error_msg);
    }
    
    $file = $_FILES['photo'];
    logDebug("Archivo recibido: " . $file['name'] . " (" . $file['size'] . " bytes)");
    
    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    logDebug("Tipo MIME detectado: " . $mimeType);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception("Tipo de archivo no permitido: " . $mimeType . ". Solo se permiten imágenes (JPEG, PNG, GIF, WebP)");
    }
    
    // Validar tamaño de archivo (máximo 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB en bytes
    if ($file['size'] > $maxSize) {
        throw new Exception("El archivo es demasiado grande. Tamaño máximo: 5MB");
    }
    
    // Generar nombre único para el archivo
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'profile_' . $targetUserId . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    $webFilePath = $webPath . $fileName;
    
    logDebug("Nombre del archivo: " . $fileName);
    logDebug("Ruta completa: " . $filePath);
    logDebug("Ruta web: " . $webFilePath);
    
    // Eliminar foto anterior si existe
    if ($targetUser['foto_perfil']) {
        $oldFilePath = $documentRoot . $targetUser['foto_perfil'];
        logDebug("Intentando eliminar archivo anterior: " . $oldFilePath);
        if (file_exists($oldFilePath)) {
            if (unlink($oldFilePath)) {
                logDebug("Archivo anterior eliminado correctamente");
            } else {
                logDebug("No se pudo eliminar el archivo anterior");
            }
        }
    }
    
    // Mover archivo subido
    logDebug("Moviendo archivo de " . $file['tmp_name'] . " a " . $filePath);
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        $lastError = error_get_last();
        logDebug("Error detallado: " . print_r($lastError, true));
        throw new Exception("Error al guardar el archivo. Verifique permisos del directorio.");
    }
    
    logDebug("Archivo movido correctamente");
    
    // Verificar que el archivo se creó correctamente
    if (!file_exists($filePath)) {
        throw new Exception("El archivo no se guardó correctamente");
    }
    
    $fileSize = filesize($filePath);
    logDebug("Archivo verificado: " . $filePath . " (" . $fileSize . " bytes)");
    
    // Actualizar base de datos
    $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = :foto_perfil WHERE id = :user_id");
    $stmt->bindParam(':foto_perfil', $webFilePath);
    $stmt->bindParam(':user_id', $targetUserId);
    
    if (!$stmt->execute()) {
        logDebug("Error actualizando base de datos: " . print_r($stmt->errorInfo(), true));
        throw new Exception("Error al actualizar la base de datos");
    }
    
    logDebug("Base de datos actualizada con: " . $webFilePath);
    
    // Registrar en logs de auditoría
    try {
        $logStmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_nuevos, fecha_accion) 
                                  VALUES (:admin_id, 'usuarios', :target_id, 'ADMIN_UPDATE_PHOTO', :datos_nuevos, NOW())");
        
        $datos_nuevos = json_encode([
            'foto_perfil' => $webFilePath,
            'archivo_original' => $file['name'],
            'tamano' => $fileSize,
            'admin_id' => $userData['id']
        ]);
        
        $logStmt->bindParam(':admin_id', $userData['id']);
        $logStmt->bindParam(':target_id', $targetUserId);
        $logStmt->bindParam(':datos_nuevos', $datos_nuevos);
        $logStmt->execute();
        
        logDebug("Log de auditoría guardado");
    } catch (Exception $e) {
        logDebug("Error guardando log de auditoría: " . $e->getMessage());
        // No fallar por esto
    }
    
    logDebug("Proceso completado exitosamente");
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Foto de perfil actualizada correctamente",
        "photo_url" => $webFilePath,
        "file_name" => $fileName,
        "file_size" => $fileSize
    ]);
    
} catch(Exception $e) {
    logDebug("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>