<?php
// api/usuarios/logs_auditoria.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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

// Verificar que el usuario sea admin
if ($userData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

try {
    // Obtener parámetros de filtro
    $usuario_id = isset($_GET['usuario_id']) ? $_GET['usuario_id'] : null;
    $accion = isset($_GET['accion']) ? $_GET['accion'] : null;
    $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
    $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;
    $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 50;
    
    // Construir consulta SQL
    $sql = "SELECT l.*, 
                   u1.nombre as admin_nombre, u1.apellido as admin_apellido,
                   u2.nombre as usuario_nombre, u2.apellido as usuario_apellido, u2.email as usuario_email
            FROM logs_auditoria l
            LEFT JOIN usuarios u1 ON l.usuario_id = u1.id
            LEFT JOIN usuarios u2 ON l.registro_id = u2.id AND l.tabla_afectada = 'usuarios'
            WHERE l.tabla_afectada = 'usuarios'";
    
    $params = [];
    
    if ($usuario_id) {
        $sql .= " AND l.registro_id = :usuario_id";
        $params[':usuario_id'] = $usuario_id;
    }
    
    if ($accion) {
        $sql .= " AND l.accion LIKE :accion";
        $params[':accion'] = '%' . $accion . '%';
    }
    
    if ($fecha_desde) {
        $sql .= " AND DATE(l.fecha_accion) >= :fecha_desde";
        $params[':fecha_desde'] = $fecha_desde;
    }
    
    if ($fecha_hasta) {
        $sql .= " AND DATE(l.fecha_accion) <= :fecha_hasta";
        $params[':fecha_hasta'] = $fecha_hasta;
    }
    
    $sql .= " ORDER BY l.fecha_accion DESC LIMIT :limite";
    
    // Ejecutar consulta
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para mejor legibilidad
    foreach ($logs as &$log) {
        $log['fecha_accion'] = date('d/m/Y H:i:s', strtotime($log['fecha_accion']));
        $log['admin_completo'] = $log['admin_nombre'] . ' ' . $log['admin_apellido'];
        $log['usuario_completo'] = $log['usuario_nombre'] . ' ' . $log['usuario_apellido'];
        
        // Decodificar JSON
        $log['datos_anteriores'] = $log['datos_anteriores'] ? json_decode($log['datos_anteriores'], true) : null;
        $log['datos_nuevos'] = $log['datos_nuevos'] ? json_decode($log['datos_nuevos'], true) : null;
        
        // Crear resumen legible de los cambios
        $log['resumen_cambios'] = $log['accion'];
        if ($log['datos_anteriores'] && $log['datos_nuevos']) {
            $cambios = [];
            foreach ($log['datos_nuevos'] as $campo => $nuevo_valor) {
                if (isset($log['datos_anteriores'][$campo])) {
                    $valor_anterior = $log['datos_anteriores'][$campo];
                    if ($valor_anterior != $nuevo_valor) {
                        $cambios[] = ucfirst($campo) . ": '{$valor_anterior}' → '{$nuevo_valor}'";
                    }
                } else {
                    $cambios[] = ucfirst($campo) . ": '{$nuevo_valor}'";
                }
            }
            if (!empty($cambios)) {
                $log['resumen_cambios'] = implode(', ', $cambios);
            }
        }
    }
    
    http_response_code(200);
    echo json_encode($logs);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>