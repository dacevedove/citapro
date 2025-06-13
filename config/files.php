<?php
// config/files.php - Configuración para manejo de archivos

// Configuración de directorios
define('UPLOAD_BASE_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_WEB_PATH', '/uploads/');

// Configuración específica para fotos de perfil
define('PROFILE_PHOTOS_DIR', UPLOAD_BASE_DIR . 'profile_photos/');
define('PROFILE_PHOTOS_WEB_PATH', UPLOAD_WEB_PATH . 'profile_photos/');

// Configuración para otros tipos de archivos (futuro)
define('DOCUMENTS_DIR', UPLOAD_BASE_DIR . 'documents/');
define('DOCUMENTS_WEB_PATH', UPLOAD_WEB_PATH . 'documents/');

define('MEDICAL_RECORDS_DIR', UPLOAD_BASE_DIR . 'medical_records/');
define('MEDICAL_RECORDS_WEB_PATH', UPLOAD_WEB_PATH . 'medical_records/');

// Configuración de tipos de archivo permitidos
$ALLOWED_IMAGE_TYPES = [
    'image/jpeg',
    'image/jpg', 
    'image/png',
    'image/gif',
    'image/webp'
];

$ALLOWED_DOCUMENT_TYPES = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

// Configuración de tamaños máximos (en bytes)
define('MAX_PROFILE_PHOTO_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_DOCUMENT_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_MEDICAL_RECORD_SIZE', 20 * 1024 * 1024); // 20MB

// Configuración de dimensiones para imágenes
define('PROFILE_PHOTO_MAX_WIDTH', 300);
define('PROFILE_PHOTO_MAX_HEIGHT', 300);

/**
 * Crear directorios necesarios
 */
function createUploadDirectories() {
    $directories = [
        UPLOAD_BASE_DIR,
        PROFILE_PHOTOS_DIR,
        DOCUMENTS_DIR,
        MEDICAL_RECORDS_DIR
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new Exception("No se pudo crear el directorio: $dir");
            }
        }
    }
    
    // Crear archivo .htaccess para seguridad
    $htaccessContent = "
# Denegar acceso directo a archivos PHP
<Files ~ \"\.php$\">
    Order allow,deny
    Deny from all
</Files>

# Permitir solo tipos de archivo específicos
<FilesMatch \"\.(jpg|jpeg|png|gif|webp|pdf|doc|docx|xls|xlsx)$\">
    Order allow,deny
    Allow from all
</FilesMatch>

# Denegar acceso a archivos ocultos
<Files ~ \"^\..*\">
    Order allow,deny
    Deny from all
</Files>
";
    
    file_put_contents(UPLOAD_BASE_DIR . '.htaccess', $htaccessContent);
}

/**
 * Validar tipo de archivo
 */
function validateFileType($filePath, $allowedTypes) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    return in_array($mimeType, $allowedTypes);
}

/**
 * Validar tamaño de archivo
 */
function validateFileSize($fileSize, $maxSize) {
    return $fileSize <= $maxSize;
}

/**
 * Generar nombre único para archivo
 */
function generateUniqueFileName($originalName, $prefix = '', $userId = null) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    
    $fileName = $prefix;
    if ($userId) {
        $fileName .= '_' . $userId;
    }
    $fileName .= '_' . $timestamp . '_' . $random . '.' . $extension;
    
    return $fileName;
}

/**
 * Limpiar nombre de archivo
 */
function sanitizeFileName($fileName) {
    // Remover caracteres especiales
    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
    // Limitar longitud
    $fileName = substr($fileName, 0, 100);
    
    return $fileName;
}

/**
 * Obtener información de archivo
 */
function getFileInfo($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $info = [
        'size' => filesize($filePath),
        'type' => mime_content_type($filePath),
        'modified' => filemtime($filePath),
        'name' => basename($filePath)
    ];
    
    // Si es imagen, obtener dimensiones
    if (strpos($info['type'], 'image/') === 0) {
        $imageInfo = getimagesize($filePath);
        if ($imageInfo) {
            $info['width'] = $imageInfo[0];
            $info['height'] = $imageInfo[1];
        }
    }
    
    return $info;
}

/**
 * Eliminar archivo de forma segura
 */
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}

/**
 * Convertir bytes a formato legible
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0, $c = count($units); $i < $c && $bytes >= 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Verificar si un archivo es una imagen
 */
function isImage($filePath) {
    global $ALLOWED_IMAGE_TYPES;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    return in_array($mimeType, $ALLOWED_IMAGE_TYPES);
}

/**
 * Logs de actividad de archivos
 */
function logFileActivity($userId, $action, $fileName, $filePath = null, $additionalData = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, tabla_afectada, registro_id, accion, datos_nuevos, fecha_accion) 
                               VALUES (:user_id, 'files', :user_id, :action, :data, NOW())");
        
        $logData = [
            'action' => $action,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($additionalData) {
            $logData = array_merge($logData, $additionalData);
        }
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':data', json_encode($logData));
        $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Error logging file activity: " . $e->getMessage());
    }
}

// Inicializar directorios al cargar el archivo
try {
    createUploadDirectories();
} catch (Exception $e) {
    error_log("Error creating upload directories: " . $e->getMessage());
}
?>