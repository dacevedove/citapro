<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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

// Verificar permisos (admin, doctor, coordinador)
if (!in_array($userData['role'], ['admin', 'doctor', 'coordinador'])) {
    http_response_code(403);
    echo json_encode(["error" => "No tiene permisos para esta acción"]);
    exit;
}

// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            listarTipos();
            break;
            
        case 'POST':
            crearTipo();
            break;
            
        case 'PUT':
            actualizarTipo();
            break;
            
        case 'DELETE':
            eliminarTipo();
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido"]);
            break;
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}

function listarTipos() {
    global $conn;
    
    $sql = "SELECT tb.*, u.nombre as creado_por_nombre, u.apellido as creado_por_apellido
            FROM tipos_bloque_horario tb
            LEFT JOIN usuarios u ON tb.creado_por = u.id
            ORDER BY tb.nombre";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas
    foreach ($tipos as &$tipo) {
        $tipo['creado_en'] = date('d/m/Y H:i', strtotime($tipo['creado_en']));
        $tipo['actualizado_en'] = date('d/m/Y H:i', strtotime($tipo['actualizado_en']));
        $tipo['activo'] = (bool)$tipo['activo'];
    }
    
    http_response_code(200);
    echo json_encode($tipos);
}

function crearTipo() {
    global $conn, $userData;
    
    // Solo admin puede crear nuevos tipos
    if ($userData['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["error" => "Solo los administradores pueden crear tipos de bloque"]);
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    // Validar datos requeridos
    if (!isset($data->nombre) || empty(trim($data->nombre))) {
        http_response_code(400);
        echo json_encode(["error" => "El nombre es requerido"]);
        return;
    }
    
    // Validar que el nombre no exista
    $stmt = $conn->prepare("SELECT id FROM tipos_bloque_horario WHERE nombre = :nombre");
    $stmt->bindParam(':nombre', $data->nombre);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(["error" => "Ya existe un tipo de bloque con este nombre"]);
        return;
    }
    
    // Validar color (formato hexadecimal)
    $color = isset($data->color) ? $data->color : '#007bff';
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
        $color = '#007bff';
    }
    
    $stmt = $conn->prepare("
        INSERT INTO tipos_bloque_horario (nombre, descripcion, color, activo, creado_por) 
        VALUES (:nombre, :descripcion, :color, :activo, :creado_por)
    ");
    
    $stmt->bindParam(':nombre', $data->nombre);
    $descripcion = isset($data->descripcion) ? $data->descripcion : null;
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->bindParam(':color', $color);
    // Corregir el manejo del campo activo
    $activo = isset($data->activo) ? ($data->activo ? 1 : 0) : 1;
    $stmt->bindParam(':activo', $activo, PDO::PARAM_INT);
    $stmt->bindParam(':creado_por', $userData['id']);
    
    $stmt->execute();
    
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Tipo de bloque creado exitosamente",
        "id" => $conn->lastInsertId()
    ]);
}

function actualizarTipo() {
    global $conn, $userData;
    
    $data = json_decode(file_get_contents("php://input"));
    
    // Validar datos requeridos
    if (!isset($data->id)) {
        http_response_code(400);
        echo json_encode(["error" => "ID requerido"]);
        return;
    }
    
    // Verificar que el tipo existe
    $stmt = $conn->prepare("SELECT * FROM tipos_bloque_horario WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    $tipoExistente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tipoExistente) {
        http_response_code(404);
        echo json_encode(["error" => "Tipo de bloque no encontrado"]);
        return;
    }
    
    // Solo admin puede modificar completamente, otros solo pueden cambiar estado
    if ($userData['role'] !== 'admin' && isset($data->nombre)) {
        http_response_code(403);
        echo json_encode(["error" => "Solo los administradores pueden modificar los detalles del tipo"]);
        return;
    }
    
    // Construir query de actualización dinámicamente
    $updates = [];
    $params = [':id' => $data->id];
    
    if (isset($data->nombre) && $userData['role'] === 'admin') {
        // Verificar que el nombre no exista en otro registro
        $stmt = $conn->prepare("SELECT id FROM tipos_bloque_horario WHERE nombre = :nombre AND id != :id");
        $stmt->bindParam(':nombre', $data->nombre);
        $stmt->bindParam(':id', $data->id);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(["error" => "Ya existe otro tipo de bloque con este nombre"]);
            return;
        }
        
        $updates[] = "nombre = :nombre";
        $params[':nombre'] = $data->nombre;
    }
    
    if (isset($data->descripcion) && $userData['role'] === 'admin') {
        $updates[] = "descripcion = :descripcion";
        $params[':descripcion'] = $data->descripcion;
    }
    
    if (isset($data->color) && $userData['role'] === 'admin') {
        $color = $data->color;
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
            $color = $tipoExistente['color'];
        }
        $updates[] = "color = :color";
        $params[':color'] = $color;
    }
    
    if (isset($data->activo)) {
        $updates[] = "activo = :activo";
        // Corregir la conversión del valor booleano
        $params[':activo'] = $data->activo ? 1 : 0;
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(["error" => "No hay campos para actualizar"]);
        return;
    }
    
    $sql = "UPDATE tipos_bloque_horario SET " . implode(", ", $updates) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    
    // Debug: Log de la consulta para verificar
    error_log("SQL Query: " . $sql);
    error_log("Params: " . json_encode($params));
    
    foreach ($params as $param => $value) {
        if ($param === ':activo') {
            // Usar bindValue con PDO::PARAM_INT para el campo activo
            $stmt->bindValue($param, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($param, $value);
        }
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Error en la ejecución: " . implode(", ", $stmt->errorInfo()));
        http_response_code(500);
        echo json_encode(["error" => "Error al actualizar el tipo de bloque"]);
        return;
    }
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Tipo de bloque actualizado exitosamente"
    ]);
}

function eliminarTipo() {
    global $conn, $userData;
    
    // Solo admin puede eliminar
    if ($userData['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["error" => "Solo los administradores pueden eliminar tipos de bloque"]);
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->id)) {
        http_response_code(400);
        echo json_encode(["error" => "ID requerido"]);
        return;
    }
    
    // Verificar que no haya horarios asociados
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM horarios_doctores WHERE tipo_bloque_id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        http_response_code(400);
        echo json_encode(["error" => "No se puede eliminar el tipo porque tiene horarios asociados"]);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM tipos_bloque_horario WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Tipo de bloque eliminado exitosamente"
        ]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Tipo de bloque no encontrado"]);
    }
}
?>