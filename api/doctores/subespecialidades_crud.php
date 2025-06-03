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

// Obtener ID de subespecialidad de los parámetros URL (si existe)
$id = isset($_GET['id']) ? $_GET['id'] : null;
$especialidad_id = isset($_GET['especialidad_id']) ? $_GET['especialidad_id'] : null;

try {
    switch ($method) {
        case 'GET':
            // Listar subespecialidades (todas, por especialidad o una específica)
            if ($id) {
                // Obtener una subespecialidad específica
                $stmt = $conn->prepare("
                    SELECT s.id, s.nombre, s.descripcion, s.especialidad_id, e.nombre as especialidad_nombre 
                    FROM subespecialidades s
                    JOIN especialidades e ON s.especialidad_id = e.id
                    WHERE s.id = :id
                ");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $subespecialidad = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$subespecialidad) {
                    http_response_code(404);
                    echo json_encode(["error" => "Subespecialidad no encontrada"]);
                    exit;
                }
                
                http_response_code(200);
                echo json_encode($subespecialidad);
            } else if ($especialidad_id) {
                // Listar subespecialidades por especialidad
                $stmt = $conn->prepare("
                    SELECT s.id, s.nombre, s.descripcion, s.especialidad_id, e.nombre as especialidad_nombre 
                    FROM subespecialidades s
                    JOIN especialidades e ON s.especialidad_id = e.id
                    WHERE s.especialidad_id = :especialidad_id
                    ORDER BY s.nombre
                ");
                $stmt->bindParam(':especialidad_id', $especialidad_id);
                $stmt->execute();
                $subespecialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                http_response_code(200);
                echo json_encode($subespecialidades);
            } else {
                // Listar todas las subespecialidades
                $stmt = $conn->prepare("
                    SELECT s.id, s.nombre, s.descripcion, s.especialidad_id, e.nombre as especialidad_nombre 
                    FROM subespecialidades s
                    JOIN especialidades e ON s.especialidad_id = e.id
                    ORDER BY e.nombre, s.nombre
                ");
                $stmt->execute();
                $subespecialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                http_response_code(200);
                echo json_encode($subespecialidades);
            }
            break;
            
        case 'POST':
            // Crear nueva subespecialidad
            $data = json_decode(file_get_contents("php://input"));
            
            if (!isset($data->nombre) || !isset($data->especialidad_id)) {
                http_response_code(400);
                echo json_encode(["error" => "Nombre y especialidad son requeridos"]);
                exit;
            }
            
            // Verificar si la especialidad existe
            $stmt = $conn->prepare("SELECT id FROM especialidades WHERE id = :id");
            $stmt->bindParam(':id', $data->especialidad_id);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(["error" => "Especialidad no encontrada"]);
                exit;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO subespecialidades (especialidad_id, nombre, descripcion) 
                VALUES (:especialidad_id, :nombre, :descripcion)
            ");
            $stmt->bindParam(':especialidad_id', $data->especialidad_id);
            $stmt->bindParam(':nombre', $data->nombre);
            $descripcion = isset($data->descripcion) ? $data->descripcion : null;
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->execute();
            
            $id = $conn->lastInsertId();
            
            http_response_code(201);
            echo json_encode([
                "message" => "Subespecialidad creada exitosamente",
                "id" => $id
            ]);
            break;
            
        case 'PUT':
            // Actualizar subespecialidad existente
            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "ID de subespecialidad requerido"]);
                exit;
            }
            
            $data = json_decode(file_get_contents("php://input"));
            
            if (!isset($data->nombre) || !isset($data->especialidad_id)) {
                http_response_code(400);
                echo json_encode(["error" => "Nombre y especialidad son requeridos"]);
                exit;
            }
            
            // Verificar si la subespecialidad existe
            $stmt = $conn->prepare("SELECT id FROM subespecialidades WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(["error" => "Subespecialidad no encontrada"]);
                exit;
            }
            
            // Verificar si la especialidad existe
            $stmt = $conn->prepare("SELECT id FROM especialidades WHERE id = :id");
            $stmt->bindParam(':id', $data->especialidad_id);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(["error" => "Especialidad no encontrada"]);
                exit;
            }
            
            $stmt = $conn->prepare("
                UPDATE subespecialidades 
                SET nombre = :nombre, descripcion = :descripcion, especialidad_id = :especialidad_id 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nombre', $data->nombre);
            $stmt->bindParam(':especialidad_id', $data->especialidad_id);
            $descripcion = isset($data->descripcion) ? $data->descripcion : null;
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->execute();
            
            http_response_code(200);
            echo json_encode([
                "message" => "Subespecialidad actualizada exitosamente",
                "id" => $id
            ]);
            break;
            
        case 'DELETE':
            // Eliminar subespecialidad
            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "ID de subespecialidad requerido"]);
                exit;
            }
            
            // Verificar si la subespecialidad existe
            $stmt = $conn->prepare("SELECT id FROM subespecialidades WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(["error" => "Subespecialidad no encontrada"]);
                exit;
            }
            
            // Verificar si hay doctores asociados
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM doctores WHERE subespecialidad_id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $doctores = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($doctores['total'] > 0) {
                http_response_code(400);
                echo json_encode(["error" => "No se puede eliminar la subespecialidad porque hay doctores asociados"]);
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM subespecialidades WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            http_response_code(200);
            echo json_encode([
                "message" => "Subespecialidad eliminada exitosamente"
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