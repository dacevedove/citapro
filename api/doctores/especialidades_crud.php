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
if (!$userData || $userData['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtener ID de especialidad de los parámetros URL (si existe)
$id = isset($_GET['id']) ? $_GET['id'] : null;

try {
    switch ($method) {
        case 'GET':
            // Listar especialidades (todas o una específica)
            if ($id) {
                // Obtener una especialidad específica
                $stmt = $conn->prepare("SELECT id, nombre, descripcion FROM especialidades WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $especialidad = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$especialidad) {
                    http_response_code(404);
                    echo json_encode(["error" => "Especialidad no encontrada"]);
                    exit;
                }
                
                http_response_code(200);
                echo json_encode($especialidad);
            } else {
                // Listar todas las especialidades
                $stmt = $conn->prepare("SELECT id, nombre, descripcion FROM especialidades ORDER BY nombre");
                $stmt->execute();
                $especialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                http_response_code(200);
                echo json_encode($especialidades);
            }
            break;
            
        case 'POST':
            // Crear nueva especialidad
            $data = json_decode(file_get_contents("php://input"));
            
            if (!isset($data->nombre)) {
                http_response_code(400);
                echo json_encode(["error" => "Nombre de especialidad requerido"]);
                exit;
            }
            
            $stmt = $conn->prepare("INSERT INTO especialidades (nombre, descripcion) VALUES (:nombre, :descripcion)");
            $stmt->bindParam(':nombre', $data->nombre);
            $descripcion = isset($data->descripcion) ? $data->descripcion : null;
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->execute();
            
            $id = $conn->lastInsertId();
            
            http_response_code(201);
            echo json_encode([
                "message" => "Especialidad creada exitosamente",
                "id" => $id
            ]);
            break;
            
        case 'PUT':
            // Actualizar especialidad existente
            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "ID de especialidad requerido"]);
                exit;
            }
            
            $data = json_decode(file_get_contents("php://input"));
            
            if (!isset($data->nombre)) {
                http_response_code(400);
                echo json_encode(["error" => "Nombre de especialidad requerido"]);
                exit;
            }
            
            // Verificar si la especialidad existe
            $stmt = $conn->prepare("SELECT id FROM especialidades WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(["error" => "Especialidad no encontrada"]);
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE especialidades SET nombre = :nombre, descripcion = :descripcion WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nombre', $data->nombre);
            $descripcion = isset($data->descripcion) ? $data->descripcion : null;
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->execute();
            
            http_response_code(200);
            echo json_encode([
                "message" => "Especialidad actualizada exitosamente",
                "id" => $id
            ]);
            break;
            
        case 'DELETE':
            // Eliminar especialidad
            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "ID de especialidad requerido"]);
                exit;
            }
            
            // Verificar si la especialidad existe
            $stmt = $conn->prepare("SELECT id FROM especialidades WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(["error" => "Especialidad no encontrada"]);
                exit;
            }
            
            // Verificar si hay doctores o subespecialidades asociadas
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM doctores WHERE especialidad_id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $doctores = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($doctores['total'] > 0) {
                http_response_code(400);
                echo json_encode(["error" => "No se puede eliminar la especialidad porque hay doctores asociados"]);
                exit;
            }
            
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM subespecialidades WHERE especialidad_id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $subespecialidades = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($subespecialidades['total'] > 0) {
                http_response_code(400);
                echo json_encode(["error" => "No se puede eliminar la especialidad porque hay subespecialidades asociadas"]);
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM especialidades WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            http_response_code(200);
            echo json_encode([
                "message" => "Especialidad eliminada exitosamente"
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido"]);
            break;
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>