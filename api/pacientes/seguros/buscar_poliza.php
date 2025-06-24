<?php
// api/pacientes/seguros/buscar-poliza.php
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
$numero_poliza = isset($_GET['numero_poliza']) ? $_GET['numero_poliza'] : null;
$aseguradora_id = isset($_GET['aseguradora_id']) ? $_GET['aseguradora_id'] : null;
$incluir_inactivos = isset($_GET['incluir_inactivos']) ? filter_var($_GET['incluir_inactivos'], FILTER_VALIDATE_BOOLEAN) : false;

if (!$numero_poliza) {
    http_response_code(400);
    echo json_encode(["error" => "Número de póliza requerido"]);
    exit;
}

try {
    // Construir consulta base
    $sql = "
        SELECT 
            ps.*,
            p.nombre as paciente_nombre,
            p.apellido as paciente_apellido,
            p.cedula as paciente_cedula,
            p.telefono as paciente_telefono,
            p.email as paciente_email,
            a.nombre_comercial as aseguradora_nombre,
            CASE 
                WHEN ps.fecha_vencimiento IS NULL THEN 'indefinido'
                WHEN ps.fecha_vencimiento < CURDATE() THEN 'vencido'
                WHEN ps.fecha_vencimiento = CURDATE() THEN 'vence_hoy'
                ELSE 'vigente'
            END as estado_vigencia,
            CASE 
                WHEN ps.fecha_vencimiento IS NULL THEN NULL
                ELSE DATEDIFF(ps.fecha_vencimiento, CURDATE())
            END as dias_vencimiento,
            CASE 
                WHEN ps.tipo_cobertura = 'principal' THEN 'Principal'
                WHEN ps.tipo_cobertura = 'secundario' THEN 'Secundario'
                WHEN ps.tipo_cobertura = 'complementario' THEN 'Complementario'
                ELSE ps.tipo_cobertura
            END as tipo_cobertura_texto
        FROM pacientes_seguros ps
        JOIN pacientes p ON ps.paciente_id = p.id
        JOIN aseguradoras a ON ps.aseguradora_id = a.id
        WHERE ps.numero_poliza = :numero_poliza
    ";
    
    $params = [':numero_poliza' => $numero_poliza];
    
    // Filtrar por aseguradora si se especifica
    if ($aseguradora_id) {
        $sql .= " AND ps.aseguradora_id = :aseguradora_id";
        $params[':aseguradora_id'] = $aseguradora_id;
    }
    
    // Filtrar por estado si no se incluyen inactivos
    if (!$incluir_inactivos) {
        $sql .= " AND ps.estado = 'activo'";
    }
    
    // Filtrar según el rol del usuario
    if ($userData['role'] === 'aseguradora') {
        // Una aseguradora solo puede buscar en sus propios seguros
        $stmt = $conn->prepare("SELECT id FROM aseguradoras WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $aseguradora_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aseguradora_usuario) {
            $sql .= " AND ps.aseguradora_id = :user_aseguradora_id";
            $params[':user_aseguradora_id'] = $aseguradora_usuario['id'];
        } else {
            // Si no tiene aseguradora asociada, no puede ver nada
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos para realizar búsquedas"]);
            exit;
        }
    } elseif ($userData['role'] === 'paciente') {
        // Un paciente solo puede buscar sus propios seguros
        $stmt = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userData['id']);
        $stmt->execute();
        $paciente_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($paciente_usuario) {
            $sql .= " AND ps.paciente_id = :user_paciente_id";
            $params[':user_paciente_id'] = $paciente_usuario['id'];
        } else {
            // Si no tiene paciente asociado, no puede ver nada
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos para realizar búsquedas"]);
            exit;
        }
    }
    // Los roles admin, coordinador y doctor pueden buscar en todos los seguros
    
    $sql .= " ORDER BY ps.tipo_cobertura ASC, ps.fecha_inicio DESC";
    
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $seguros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($seguros)) {
        http_response_code(404);
        echo json_encode([
            "error" => "No se encontraron seguros con el número de póliza especificado",
            "numero_poliza" => $numero_poliza
        ]);
        exit;
    }
    
    // Formatear datos adicionales
    foreach ($seguros as &$seguro) {
        // Calcular edad del paciente si hay fecha de nacimiento
        if (isset($seguro['fecha_nacimiento']) && $seguro['fecha_nacimiento']) {
            $nacimiento = new DateTime($seguro['fecha_nacimiento']);
            $hoy = new DateTime();
            $seguro['edad'] = $hoy->diff($nacimiento)->y;
        }
        
        // Formatear fechas
        if ($seguro['fecha_inicio']) {
            $seguro['fecha_inicio_formateada'] = date('d/m/Y', strtotime($seguro['fecha_inicio']));
        }
        if ($seguro['fecha_vencimiento']) {
            $seguro['fecha_vencimiento_formateada'] = date('d/m/Y', strtotime($seguro['fecha_vencimiento']));
        }
        
        // Información de contacto del paciente
        $seguro['paciente_info'] = [
            'nombre_completo' => $seguro['paciente_nombre'] . ' ' . $seguro['paciente_apellido'],
            'cedula' => $seguro['paciente_cedula'],
            'telefono' => $seguro['paciente_telefono'],
            'email' => $seguro['paciente_email']
        ];
        
        // Estado del seguro con descripción
        $seguro['estado_descripcion'] = [
            'activo' => 'Activo',
            'inactivo' => 'Inactivo',
            'suspendido' => 'Suspendido'
        ][$seguro['estado']] ?? $seguro['estado'];
        
        // Vigencia con descripción
        $seguro['vigencia_descripcion'] = [
            'vigente' => 'Vigente',
            'vence_hoy' => 'Vence hoy',
            'vencido' => 'Vencido',
            'indefinido' => 'Sin vencimiento'
        ][$seguro['estado_vigencia']] ?? $seguro['estado_vigencia'];
        
        // Alertas
        $seguro['alertas'] = [];
        if ($seguro['estado_vigencia'] === 'vencido') {
            $seguro['alertas'][] = [
                'tipo' => 'error',
                'mensaje' => 'Seguro vencido'
            ];
        } elseif ($seguro['estado_vigencia'] === 'vence_hoy') {
            $seguro['alertas'][] = [
                'tipo' => 'warning',
                'mensaje' => 'Seguro vence hoy'
            ];
        } elseif ($seguro['dias_vencimiento'] !== null && $seguro['dias_vencimiento'] <= 30 && $seguro['dias_vencimiento'] > 0) {
            $seguro['alertas'][] = [
                'tipo' => 'info',
                'mensaje' => "Vence en {$seguro['dias_vencimiento']} días"
            ];
        }
        
        if ($seguro['estado'] !== 'activo') {
            $seguro['alertas'][] = [
                'tipo' => 'warning',
                'mensaje' => "Seguro {$seguro['estado_descripcion']}"
            ];
        }
    }
    
    // Preparar respuesta
    $response = [
        'encontrados' => count($seguros),
        'numero_poliza' => $numero_poliza,
        'seguros' => $seguros
    ];
    
    // Si solo hay un resultado, incluir información adicional
    if (count($seguros) === 1) {
        $seguro_unico = $seguros[0];
        
        // Obtener últimas citas con este seguro
        $stmt = $conn->prepare("
            SELECT c.*, e.nombre as especialidad_nombre, 
                   CONCAT(u.nombre, ' ', u.apellido) as doctor_nombre
            FROM citas c
            LEFT JOIN especialidades e ON c.especialidad_id = e.id
            LEFT JOIN doctores d ON c.doctor_id = d.id
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            WHERE c.paciente_seguro_id = :seguro_id
            ORDER BY c.fecha DESC, c.hora DESC
            LIMIT 5
        ");
        $stmt->bindParam(':seguro_id', $seguro_unico['id']);
        $stmt->execute();
        $citas_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['citas_recientes'] = $citas_recientes;
        
        // Obtener historial reciente del seguro
        $stmt = $conn->prepare("
            SELECT h.*, CONCAT(u.nombre, ' ', u.apellido) as usuario_nombre
            FROM pacientes_seguros_historial h
            LEFT JOIN usuarios u ON h.realizado_por = u.id
            WHERE h.paciente_seguro_id = :seguro_id
            ORDER BY h.fecha_accion DESC
            LIMIT 3
        ");
        $stmt->bindParam(':seguro_id', $seguro_unico['id']);
        $stmt->execute();
        $historial_reciente = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['historial_reciente'] = $historial_reciente;
    }
    
    http_response_code(200);
    echo json_encode($response);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>