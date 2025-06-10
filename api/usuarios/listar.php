<?php
// api/usuarios/listar.php
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
    $busqueda = isset($_GET['busqueda']) ? '%' . $_GET['busqueda'] . '%' : null;
    $role = isset($_GET['role']) ? $_GET['role'] : null;
    $activo = isset($_GET['activo']) ? $_GET['activo'] : null;
    
    // Construir consulta SQL
    $sql = "SELECT u.id, u.nombre, u.apellido, u.email, u.cedula, u.telefono, u.role, 
                   u.esta_activo, u.email_verificado, u.ultimo_acceso, u.creado_en,
                   CASE 
                       WHEN u.role = 'doctor' THEN (SELECT e.nombre FROM doctores d 
                                                    JOIN especialidades e ON d.especialidad_id = e.id 
                                                    WHERE d.usuario_id = u.id LIMIT 1)
                       WHEN u.role = 'aseguradora' THEN (SELECT a.nombre_comercial FROM aseguradoras a 
                                                         WHERE a.usuario_id = u.id LIMIT 1)
                       ELSE NULL
                   END as info_adicional
            FROM usuarios u
            WHERE 1=1";
    
    $params = [];
    
    if ($busqueda) {
        $sql .= " AND (u.nombre LIKE :busqueda OR u.apellido LIKE :busqueda OR u.email LIKE :busqueda OR u.cedula LIKE :busqueda)";
        $params[':busqueda'] = $busqueda;
    }
    
    if ($role) {
        $sql .= " AND u.role = :role";
        $params[':role'] = $role;
    }
    
    if ($activo !== null) {
        $sql .= " AND u.esta_activo = :activo";
        $params[':activo'] = $activo;
    }
    
    $sql .= " ORDER BY u.creado_en DESC";
    
    // Ejecutar consulta
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas y agregar información adicional
    foreach ($usuarios as &$usuario) {
        // Formatear último acceso
        if ($usuario['ultimo_acceso']) {
            $fecha_acceso = new DateTime($usuario['ultimo_acceso']);
            $ahora = new DateTime();
            $diferencia = $ahora->diff($fecha_acceso);
            
            if ($diferencia->days == 0) {
                if ($diferencia->h == 0) {
                    if ($diferencia->i == 0) {
                        $usuario['ultimo_acceso'] = 'Hace unos momentos';
                    } else {
                        $usuario['ultimo_acceso'] = 'Hace ' . $diferencia->i . ' minuto' . ($diferencia->i > 1 ? 's' : '');
                    }
                } else {
                    $usuario['ultimo_acceso'] = 'Hace ' . $diferencia->h . ' hora' . ($diferencia->h > 1 ? 's' : '');
                }
            } elseif ($diferencia->days == 1) {
                $usuario['ultimo_acceso'] = 'Ayer a las ' . $fecha_acceso->format('H:i');
            } elseif ($diferencia->days < 7) {
                $usuario['ultimo_acceso'] = 'Hace ' . $diferencia->days . ' día' . ($diferencia->days > 1 ? 's' : '');
            } else {
                $usuario['ultimo_acceso'] = $fecha_acceso->format('d/m/Y H:i');
            }
        } else {
            $usuario['ultimo_acceso'] = 'Nunca';
        }
        
        $usuario['creado_en'] = date('d/m/Y H:i', strtotime($usuario['creado_en']));
        $usuario['esta_activo'] = (bool)$usuario['esta_activo'];
        $usuario['email_verificado'] = (bool)$usuario['email_verificado'];
    }
    
    http_response_code(200);
    echo json_encode($usuarios);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}
?>