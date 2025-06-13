<?php
// api/auth/upload_profile_photo.php
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
    error_log(date('Y-m-d H:i:s') . " - UPLOAD_PHOTO: " . $message);
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

logDebug("Usuario autenticado: " . $userData['id']);

// Configuración de directorio de uploads - RUTA ABSOLUTA
$baseDir = dirname(dirname(__FILE__)); // Directorio base del proyecto
$uploadDir = $baseDir . '/uploads/profile_photos/';
$webPath = '/uploads/profile_photos/';

logDebug("Directorio base: " . $baseDir);
logDebug("Directorio de uploads: " . $uploadDir);

// Crear directorio si no existe
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        logDebug("Error: No se pudo crear el directorio: " . $uploadDir);
        http_response_code(500);
        echo json_encode(["error" => "No se pudo crear el directorio de uploads"]);
        exit;
    }
    logDebug("Directorio creado: " . $uploadDir);
}

// Verificar permisos del directorio
if (!is_writable($uploadDir)) {
    logDebug("Error: El directorio no tiene permisos de escritura: " . $uploadDir);
    http_response_code(500);
    echo json_encode(["error" => "El directorio no tiene permisos de escritura"]);
    exit;
}

logDebug("Directorio tiene permisos de escritura");

try {
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
                    $error_msg = "Error desconocido en la subida";
            }
        }
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
        throw new Exception("Tipo de archivo no permitido. Solo se permiten imágenes (JPEG, PNG, GIF, WebP)");
    }
    
    // Validar tamaño de archivo (máximo 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB en bytes
    if ($file['size'] > $maxSize) {
        throw new Exception("El archivo es demasiado grande. Tamaño máximo: 5MB");
    }
    
    // Generar nombre único para el archivo
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'profile_' . $userData['id'] . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    $webFilePath = $webPath . $fileName;
    
    logDebug("Nombre del archivo: " . $fileName);
    logDebug("Ruta completa: " . $filePath);
    logDebug("Ruta web: " . $webFilePath);
    
    // Eliminar foto anterior si existe
    $oldPhotoQuery = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id = :user_id");
    $oldPhotoQuery->bindParam(':user_id', $userData['id']);
    $oldPhotoQuery->execute();
    $oldPhoto = $oldPhotoQuery->fetch(PDO::FETCH_ASSOC);
    
    if ($oldPhoto && $oldPhoto['foto_perfil']) {
        // Construir ruta del archivo anterior
        $oldFilePath = $baseDir . $oldPhoto['foto_perfil'];
        logDebug("Intentando eliminar archivo anterior: " . $oldFilePath);
        if (file_exists($oldFilePath)) {
            if (unlink($oldFilePath)) {
                logDebug("Archivo anterior eliminado correctamente");
            } else {
                logDebug("No se pudo eliminar el archivo anterior");
            }
        } else {
            logDebug("El archivo anterior no existe: " . $oldFilePath);
        }
    }
    
    // Mover archivo subido
    logDebug("Moviendo archivo de " . $file['tmp_name'] . " a " . $filePath);
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception("Error al guardar el archivo. Verifique permisos del directorio.");
    }
    
    logDebug("Archivo movido correctamente");
    
    // Verificar que el archivo se creó correctamente
    if (!file_exists($filePath)) {
        throw new Exception("El archivo no se guardó correctamente");
    }
    
    logDebug("Archivo verificado: " . $filePath . " (" . filesize($filePath) . " bytes)");
    
    // Redimensionar imagen si es necesario (opcional)
    $resizedPath = resizeImage($filePath, 300, 300);
    if ($resizedPath && $resizedPath !== $filePath) {
        // Si se redimensionó correctamente, usar la imagen redimensionada
        if (file_exists($filePath)) {
            unlink($filePath); // Eliminar original
        }
        $filePath = $resizedPath;
        $fileName = basename($resizedPath);
        $webFilePath = $webPath . $fileName;
        logDebug("Imagen redimensionada: " . $fileName);
    }
    
    // Actualizar base de datos
    $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = :foto_perfil WHERE id = :user_id");
    $stmt->bindParam(':foto_perfil', $webFilePath);
    $stmt->bindParam(':user_id', $userData['id']);
    $stmt->execute();
    
    logDebug("Base de datos actualizada con: " . $webFilePath);
    
    // Registrar en logs de auditoría
    $logStmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_nuevos, fecha_accion) 
                              VALUES (:user_id, 'usuarios', :user_id, 'UPDATE_PHOTO', :datos_nuevos, NOW())");
    
    $datos_nuevos = json_encode([
        'foto_perfil' => $webFilePath,
        'archivo_original' => $file['name'],
        'tamano' => $file['size'],
        'ruta_fisica' => $filePath
    ]);
    
    $logStmt->bindParam(':user_id', $userData['id']);
    $logStmt->bindParam(':datos_nuevos', $datos_nuevos);
    $logStmt->execute();
    
    logDebug("Proceso completado exitosamente");
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Foto de perfil actualizada correctamente",
        "photo_url" => $webFilePath,
        "file_name" => $fileName,
        "file_size" => filesize($filePath)
    ]);
    
} catch(Exception $e) {
    logDebug("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

/**
 * Función para redimensionar imágenes
 */
function resizeImage($sourcePath, $targetWidth, $targetHeight) {
    try {
        // Obtener información de la imagen
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $imageType = $imageInfo[2];
        
        // Si la imagen ya es del tamaño correcto o menor, no redimensionar
        if ($sourceWidth <= $targetWidth && $sourceHeight <= $targetHeight) {
            return $sourcePath;
        }
        
        // Calcular nuevas dimensiones manteniendo proporción
        $ratio = min($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        $newWidth = round($sourceWidth * $ratio);
        $newHeight = round($sourceHeight * $ratio);
        
        // Crear imagen fuente
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = imagecreatefromwebp($sourcePath);
                } else {
                    return $sourcePath; // WebP no soportado
                }
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Crear imagen destino
        $targetImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparencia para PNG y GIF
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Redimensionar
        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
        
        // Generar nuevo nombre de archivo
        $pathInfo = pathinfo($sourcePath);
        $newPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_resized.' . $pathInfo['extension'];
        
        // Guardar imagen redimensionada
        $success = false;
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($targetImage, $newPath, 85);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($targetImage, $newPath, 6);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($targetImage, $newPath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    $success = imagewebp($targetImage, $newPath, 85);
                }
                break;
        }
        
        // Limpiar memoria
        imagedestroy($sourceImage);
        imagedestroy($targetImage);
        
        return $success ? $newPath : false;
        
    } catch (Exception $e) {
        logDebug("Error en redimensionamiento: " . $e->getMessage());
        return false;
    }
}
?>