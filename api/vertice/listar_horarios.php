<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
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
if (!$userData || $userData['role'] !== 'vertice') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Obtener parámetros
$doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;

if (!$doctor_id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de doctor no proporcionado"]);
    exit;
}

try {
    // Si tenemos rango de fechas, usarlo para filtrar
    if ($fecha_inicio && $fecha_fin) {
        // Obtener horarios con fechas específicas en este rango
        $sql = "SELECT * FROM temp_horarios_doctores 
                WHERE doctor_id = :doctor_id 
                AND fecha IS NOT NULL 
                AND fecha BETWEEN :fecha_inicio AND :fecha_fin";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->execute();
        
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si estamos buscando la semana actual (comprobando fecha inicio <= hoy y fecha fin >= hoy),
        // también incluimos los horarios recurrentes (sin fecha)
        $hoy = date('Y-m-d');
        $es_semana_actual = ($fecha_inicio <= $hoy && $fecha_fin >= $hoy);
        
        if ($es_semana_actual) {
            $sql = "SELECT * FROM temp_horarios_doctores 
                    WHERE doctor_id = :doctor_id AND fecha IS NULL";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':doctor_id', $doctor_id);
            $stmt->execute();
            
            $horarios_recurrentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combinar ambos arrays
            $horarios = array_merge($horarios, $horarios_recurrentes);
        }
    } else {
        // Si no hay fechas, devolver todos los horarios
        $sql = "SELECT * FROM temp_horarios_doctores WHERE doctor_id = :doctor_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->execute();
        
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Limpiar cualquier resultado de depuración anterior
    error_log("========== NUEVA CONSULTA ==========");
    error_log("Doctor ID: " . $doctor_id);
    error_log("Fecha inicio: " . $fecha_inicio);
    error_log("Fecha fin: " . $fecha_fin);
    error_log("Cantidad de horarios: " . count($horarios));
    
    http_response_code(200);
    echo json_encode([
        "data" => $horarios,
        "meta" => [
            "doctor_id" => $doctor_id,
            "fecha_inicio" => $fecha_inicio,
            "fecha_fin" => $fecha_fin,
            "total" => count($horarios)
        ]
    ]);
} catch(Exception $e) {
    error_log("Error en listar_horarios.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>