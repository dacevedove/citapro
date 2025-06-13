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

// Configuración de directorio de uploads
$uploadDir = '../uploads/profile_photos/';
$webPath = '/uploads/profile_photos/';

// Crear directorio si no existe
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(["error" => "No se pudo crear el directorio de uploads"]);
        exit;
    }
}

try {
    // Verificar si se subió un archivo
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No se recibió ningún archivo o hubo un error en la subida");
    }
    
    $file = $_FILES['photo'];
    
    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
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
    
    // Eliminar foto anterior si existe
    $oldPhotoQuery = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id = :user_id");
    $oldPhotoQuery->bindParam(':user_id', $userData['id']);
    $oldPhotoQuery->execute();
    $oldPhoto = $oldPhotoQuery->fetch(PDO::FETCH_ASSOC);
    
    if ($oldPhoto && $oldPhoto['foto_perfil']) {
        // Construir ruta del archivo anterior
        $oldFilePath = str_replace($webPath, $uploadDir, $oldPhoto['foto_perfil']);
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
    }
    
    // Mover archivo subido
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception("Error al guardar el archivo");
    }
    
    // Redimensionar imagen si es necesario (opcional)
    $resizedPath = resizeImage($filePath, 300, 300);
    if ($resizedPath) {
        // Si se redimensionó correctamente, usar la imagen redimensionada
        if ($resizedPath !== $filePath) {
            unlink($filePath); // Eliminar original
            $filePath = $resizedPath;
            $fileName = basename($resizedPath);
            $webFilePath = $webPath . $fileName;
        }
    }
    
    // Actualizar base de datos
    $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = :foto_perfil WHERE id = :user_id");
    $stmt->bindParam(':foto_perfil', $webFilePath);
    $stmt->bindParam(':user_id', $userData['id']);
    $stmt->execute();
    
    // Registrar en logs de auditoría
    $logStmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_nuevos, fecha_accion) 
                              VALUES (:user_id, 'usuarios', :user_id, 'UPDATE_PHOTO', :datos_nuevos, NOW())");
    
    $datos_nuevos = json_encode([
        'foto_perfil' => $webFilePath,
        'archivo_original' => $file['name'],
        'tamano' => $file['size']
    ]);
    
    $logStmt->bindParam(':user_id', $userData['id']);
    $logStmt->bindParam(':datos_nuevos', $datos_nuevos);
    $logStmt->execute();
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Foto de perfil actualizada correctamente",
        "photo_url" => $webFilePath,
        "file_name" => $fileName
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

/**
 * Función para redimensionar imágenes
 */
function resizeImage($sourcePath, $targetWidth, $targetHeight) {
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
            $sourceImage = imagecreatefromwebp($sourcePath);
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
            $success = imagewebp($targetImage, $newPath, 85);
            break;
    }
    
    // Limpiar memoria
    imagedestroy($sourceImage);
    imagedestroy($targetImage);
    
    return $success ? $newPath : false;
}
?>