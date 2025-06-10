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
    
    // Log para debug
    error_log("Logs audit request - usuario_id: " . $usuario_id . ", accion: " . $accion);
    
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
    
    // Log para debug
    error_log("SQL Query: " . $sql);
    error_log("Params: " . json_encode($params));
    
    // Ejecutar consulta
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log para debug
    error_log("Found " . count($logs) . " logs");
    
    // Formatear datos para mejor legibilidad
    foreach ($logs as &$log) {
        $log['fecha_accion'] = date('d/m/Y H:i:s', strtotime($log['fecha_accion']));
        $log['admin_completo'] = $log['admin_nombre'] . ' ' . $log['admin_apellido'];
        $log['usuario_completo'] = $log['usuario_nombre'] . ' ' . $log['usuario_apellido'];
        
        // Decodificar JSON
        $log['datos_anteriores'] = $log['datos_anteriores'] ? json_decode($log['datos_anteriores'], true) : null;
        $log['datos_nuevos'] = $log['datos_nuevos'] ? json_decode($log['datos_nuevos'], true) : null;
        
        // Crear resumen legible de los cambios
        $log['resumen_cambios'] = generarResumenCambios($log['accion'], $log['datos_anteriores'], $log['datos_nuevos']);
    }
    
    http_response_code(200);
    echo json_encode($logs);
    
} catch(PDOException $e) {
    error_log("Error in logs_auditoria.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}

// Función para generar resúmenes legibles de los cambios
function generarResumenCambios($accion, $datos_anteriores, $datos_nuevos) {
    switch ($accion) {
        case 'INSERT':
            return 'Usuario creado en el sistema';
            
        case 'UPDATE_PASS':
            if (isset($datos_nuevos['password_changed_at'])) {
                try {
                    $fecha = new DateTime($datos_nuevos['password_changed_at']);
                    return 'Cambio de contraseña el ' . $fecha->format('d/m/Y') . ' a las ' . $fecha->format('H:i');
                } catch (Exception $e) {
                    return 'Cambio de contraseña';
                }
            }
            return 'Cambio de contraseña';
            
        case 'RESET_PASS':
            if (isset($datos_nuevos['reset_at'])) {
                try {
                    $fecha = new DateTime($datos_nuevos['reset_at']);
                    return 'Contraseña reseteada el ' . $fecha->format('d/m/Y') . ' a las ' . $fecha->format('H:i');
                } catch (Exception $e) {
                    return 'Contraseña reseteada';
                }
            }
            return 'Contraseña reseteada y generada temporalmente';
            
        case 'UPDATE_EMAIL':
            if (isset($datos_anteriores['email']) && isset($datos_nuevos['email'])) {
                return 'Email cambiado de "' . $datos_anteriores['email'] . '" a "' . $datos_nuevos['email'] . '"';
            }
            return 'Email actualizado';
            
        case 'UPDATE_DATOS':
        case 'UPDATE':
            $cambios = [];
            
            if (!$datos_anteriores || !$datos_nuevos) {
                return 'Datos actualizados';
            }
            
            foreach ($datos_nuevos as $campo => $nuevo_valor) {
                if (isset($datos_anteriores[$campo])) {
                    $valor_anterior = $datos_anteriores[$campo];
                    if ($valor_anterior != $nuevo_valor) {
                        $cambios[] = generarCambioLegible($campo, $valor_anterior, $nuevo_valor);
                    }
                } else {
                    $cambios[] = generarCambioLegible($campo, null, $nuevo_valor);
                }
            }
            
            if (empty($cambios)) {
                return 'Datos actualizados';
            }
            
            return implode(', ', $cambios);
            
        default:
            return 'Acción: ' . $accion;
    }
}

// Función para generar descripciones legibles de cambios individuales
function generarCambioLegible($campo, $valor_anterior, $nuevo_valor) {
    switch ($campo) {
        case 'nombre':
            return $valor_anterior ? 
                "Nombre cambiado de '$valor_anterior' a '$nuevo_valor'" : 
                "Nombre establecido como '$nuevo_valor'";
                
        case 'apellido':
            return $valor_anterior ? 
                "Apellido cambiado de '$valor_anterior' a '$nuevo_valor'" : 
                "Apellido establecido como '$nuevo_valor'";
                
        case 'telefono':
            return $valor_anterior ? 
                "Teléfono cambiado de '$valor_anterior' a '$nuevo_valor'" : 
                "Teléfono establecido como '$nuevo_valor'";
                
        case 'role':
            $roles = [
                'admin' => 'Administrador',
                'doctor' => 'Doctor',
                'aseguradora' => 'Aseguradora',
                'paciente' => 'Paciente',
                'vertice' => 'Vértice'
            ];
            
            $rol_anterior = $roles[$valor_anterior] ?? $valor_anterior;
            $rol_nuevo = $roles[$nuevo_valor] ?? $nuevo_valor;
            
            return "Rol cambiado de '$rol_anterior' a '$rol_nuevo'";
            
        case 'esta_activo':
            if ($nuevo_valor == 1 || $nuevo_valor === true) {
                return 'Usuario activado';
            } else {
                return 'Usuario desactivado';
            }
            
        case 'email':
            return $valor_anterior ? 
                "Email cambiado de '$valor_anterior' a '$nuevo_valor'" : 
                "Email establecido como '$nuevo_valor'";
                
        case 'email_verificado':
            if ($nuevo_valor == 0 || $nuevo_valor === false) {
                return 'Email marcado como no verificado';
            } else {
                return 'Email verificado';
            }
                
        case 'password_changed':
            return 'Contraseña modificada';
            
        case 'password_reset':
            return 'Contraseña reseteada';
            
        default:
            return ucfirst($campo) . " actualizado";
    }
}
?>