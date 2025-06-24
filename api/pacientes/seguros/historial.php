<?php
// api/pacientes/seguros/historial.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../config/jwt.php';

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

// Obtener parámetros
$seguro_id = isset($_GET['seguro_id']) ? $_GET['seguro_id'] : null;

if (!$seguro_id) {
    http_response_code(400);
    echo json_encode(["error" => "ID del seguro requerido"]);
    exit;
}

try {
    // Verificar que el seguro existe y obtener información del paciente
    $stmt = $conn->prepare("
        SELECT ps.*, p.nombre, p.apellido, p.cedula 
        FROM pacientes_seguros ps
        JOIN pacientes p ON ps.paciente_id = p.id
        WHERE ps.id = :seguro_id
    ");
    $stmt->bindParam(':seguro_id', $seguro_id);
    $stmt->execute();
    $seguro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seguro) {
        http_response_code(404);
        echo json_encode(["error" => "Seguro no encontrado"]);
        exit;
    }
    
    // Verificar permisos según el rol del usuario
    if ($userData['role'] === 'paciente') {
        // Un paciente solo puede ver el historial de sus propios seguros
        $stmt = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $paciente_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$paciente_usuario || $paciente_usuario['id'] != $seguro['paciente_id']) {
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos para ver este historial"]);
            exit;
        }
    } elseif ($userData['role'] === 'aseguradora') {
        // Una aseguradora solo puede ver seguros de sus propios pacientes
        $stmt = $conn->prepare("SELECT id FROM aseguradoras WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $aseguradora_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$aseguradora_usuario || $aseguradora_usuario['id'] != $seguro['aseguradora_id']) {
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos para ver este historial"]);
            exit;
        }
    }
    // Los roles admin, coordinador y doctor pueden ver todos los historiales
    
    // Obtener historial del seguro
    $stmt = $conn->prepare("
        SELECT 
            h.*,
            u.nombre as usuario_nombre,
            u.apellido as usuario_apellido,
            u.role as usuario_role
        FROM pacientes_seguros_historial h
        LEFT JOIN usuarios u ON h.realizado_por = u.id
        WHERE h.paciente_seguro_id = :seguro_id
        ORDER BY h.fecha_accion DESC
    ");
    $stmt->bindParam(':seguro_id', $seguro_id);
    $stmt->execute();
    
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos del historial
    foreach ($historial as &$registro) {
        // Formatear fecha
        $registro['fecha_accion_formateada'] = date('d/m/Y H:i', strtotime($registro['fecha_accion']));
        
        // Formatear usuario
        if ($registro['usuario_nombre']) {
            $registro['usuario_formateado'] = $registro['usuario_nombre'] . ' ' . $registro['usuario_apellido'] . 
                                           ' (' . ucfirst($registro['usuario_role']) . ')';
        } else {
            $registro['usuario_formateado'] = 'Sistema';
        }
        
        // Decodificar JSON si existe
        if ($registro['datos_anteriores']) {
            $registro['datos_anteriores'] = json_decode($registro['datos_anteriores'], true);
        }
        if ($registro['datos_nuevos']) {
            $registro['datos_nuevos'] = json_decode($registro['datos_nuevos'], true);
        }
        
        // Agregar descripción detallada según la acción
        switch ($registro['accion']) {
            case 'activacion':
                $registro['descripcion'] = 'Seguro activado';
                break;
            case 'desactivacion':
                $registro['descripcion'] = 'Seguro desactivado';
                break;
            case 'modificacion':
                $cambios = [];
                if ($registro['estado_anterior'] !== $registro['estado_nuevo']) {
                    $cambios[] = "Estado: {$registro['estado_anterior']} → {$registro['estado_nuevo']}";
                }
                $registro['descripcion'] = 'Seguro modificado' . (!empty($cambios) ? ': ' . implode(', ', $cambios) : '');
                break;
            case 'renovacion':
                $registro['descripcion'] = 'Seguro renovado';
                break;
            case 'suspension':
                $registro['descripcion'] = 'Seguro suspendido';
                break;
            default:
                $registro['descripcion'] = ucfirst($registro['accion']);
        }
    }
    
    // Preparar respuesta
    $response = [
        'seguro_info' => [
            'id' => $seguro['id'],
            'numero_poliza' => $seguro['numero_poliza'],
            'paciente' => $seguro['nombre'] . ' ' . $seguro['apellido'],
            'cedula_paciente' => $seguro['cedula']
        ],
        'historial' => $historial,
        'total_registros' => count($historial)
    ];
    
    http_response_code(200);
    echo json_encode($response);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>