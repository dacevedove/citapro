<?php
// api/usuarios/editar_completo.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivos de configuración
include_once '../config/database.php';
include_once '../config/jwt.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

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

// Obtener datos de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

// Validar datos requeridos
if (!isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID de usuario requerido"]);
    exit;
}

$user_id = $data['user_id'];
$accion = $data['accion'] ?? 'actualizar_datos'; // actualizar_datos, cambiar_email, cambiar_password

// Prevenir que el admin se modifique ciertos aspectos críticos
if ($user_id == $userData['id'] && in_array($accion, ['cambiar_email', 'desactivar'])) {
    http_response_code(400);
    echo json_encode(["error" => "No puede modificar aspectos críticos de su propia cuenta"]);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Obtener información actual del usuario
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $usuario_actual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario_actual) {
        throw new Exception("Usuario no encontrado");
    }
    
    // Almacenar datos anteriores para auditoría
    $datos_anteriores = $usuario_actual;
    unset($datos_anteriores['password']); // No guardar contraseña en logs
    
    $response = [];
    $datos_nuevos = [];
    
    switch ($accion) {
        case 'actualizar_datos':
            // Actualizar datos básicos (nombre, apellido, teléfono, rol, estado)
            $updates = [];
            $params = [':user_id' => $user_id];
            
            if (isset($data['nombre'])) {
                $updates[] = "nombre = :nombre";
                $params[':nombre'] = trim($data['nombre']);
                $datos_nuevos['nombre'] = $params[':nombre'];
            }
            
            if (isset($data['apellido'])) {
                $updates[] = "apellido = :apellido";
                $params[':apellido'] = trim($data['apellido']);
                $datos_nuevos['apellido'] = $params[':apellido'];
            }
            
            if (isset($data['telefono'])) {
                $updates[] = "telefono = :telefono";
                $params[':telefono'] = trim($data['telefono']);
                $datos_nuevos['telefono'] = $params[':telefono'];
            }
            
            if (isset($data['role']) && $data['role'] !== $usuario_actual['role']) {
                // Validar rol
                $roles_validos = ['admin', 'doctor', 'aseguradora', 'paciente', 'vertice'];
                if (!in_array($data['role'], $roles_validos)) {
                    throw new Exception("Rol no válido");
                }
                
                // Prevenir que el admin se quite sus propios permisos
                if ($user_id == $userData['id'] && $data['role'] !== 'admin') {
                    throw new Exception("No puede cambiar su propio rol de administrador");
                }
                
                $updates[] = "role = :role";
                $params[':role'] = $data['role'];
                $datos_nuevos['role'] = $params[':role'];
            }
            
            if (isset($data['esta_activo']) && $data['esta_activo'] !== $usuario_actual['esta_activo']) {
                // Prevenir que el admin se desactive a sí mismo
                if ($user_id == $userData['id'] && !$data['esta_activo']) {
                    throw new Exception("No puede desactivarse a sí mismo");
                }
                
                $updates[] = "esta_activo = :esta_activo";
                $params[':esta_activo'] = $data['esta_activo'] ? 1 : 0;
                $datos_nuevos['esta_activo'] = $params[':esta_activo'];
            }
            
            if (!empty($updates)) {
                $sql = "UPDATE usuarios SET " . implode(', ', $updates) . " WHERE id = :user_id";
                $stmt = $conn->prepare($sql);
                
                foreach ($params as $param => $value) {
                    $stmt->bindValue($param, $value);
                }
                
                $stmt->execute();
                $response['message'] = 'Datos actualizados correctamente';
            } else {
                $response['message'] = 'No hay cambios que realizar';
            }
            break;
            
        case 'cambiar_email':
            if (!isset($data['nuevo_email'])) {
                throw new Exception("Nuevo email requerido");
            }
            
            $nuevo_email = trim($data['nuevo_email']);
            
            // Validar formato de email
            if (!filter_var($nuevo_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Formato de email inválido");
            }
            
            // Verificar que el email no esté en uso
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :user_id");
            $stmt->bindParam(':email', $nuevo_email);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("El email ya está en uso por otro usuario");
            }
            
            // Obtener email anterior para los logs
            $email_anterior = $usuario_actual['email'];
            
            // Actualizar email
            $stmt = $conn->prepare("UPDATE usuarios SET email = :email, email_verificado = 0 WHERE id = :user_id");
            $stmt->bindParam(':email', $nuevo_email);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $datos_nuevos = [
                'email' => $nuevo_email,
                'email_verificado' => 0
            ];
            
            // Agregar el email anterior a los datos para el log
            $datos_anteriores['email'] = $email_anterior;
            
            $response['message'] = 'Email actualizado correctamente';
            break;
            
        case 'cambiar_password':
            if (!isset($data['nueva_password'])) {
                throw new Exception("Nueva contraseña requerida");
            }
            
            $nueva_password = $data['nueva_password'];
            
            // Validar longitud de contraseña
            if (strlen($nueva_password) < 8) {
                throw new Exception("La contraseña debe tener al menos 8 caracteres");
            }
            
            // Hashear nueva contraseña
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            
            // Actualizar contraseña
            $stmt = $conn->prepare("UPDATE usuarios SET password = :password WHERE id = :user_id");
            $stmt->bindParam(':password', $password_hash);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $datos_nuevos = [
                'password_changed' => true,
                'password_changed_at' => date('Y-m-d H:i:s')
            ];
            
            $response['message'] = 'Contraseña actualizada correctamente';
            break;
            
        case 'resetear_password':
            // Generar contraseña temporal
            $password_temporal = 'Temp' . rand(1000, 9999) . '!';
            $password_hash = password_hash($password_temporal, PASSWORD_DEFAULT);
            
            // Actualizar contraseña
            $stmt = $conn->prepare("UPDATE usuarios SET password = :password WHERE id = :user_id");
            $stmt->bindParam(':password', $password_hash);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $datos_nuevos = [
                'password_reset' => true,
                'password_temporal' => $password_temporal,
                'reset_at' => date('Y-m-d H:i:s')
            ];
            
            $response['message'] = 'Contraseña reseteada correctamente';
            $response['password_temporal'] = $password_temporal;
            break;
            
        default:
            throw new Exception("Acción no válida");
    }
    
    // Registrar en logs de auditoría
    $stmt = $conn->prepare("INSERT INTO logs_auditoria 
                           (usuario_id, tabla_afectada, registro_id, accion, datos_anteriores, datos_nuevos, direccion_ip, navegador, fecha_accion) 
                           VALUES (:admin_id, 'usuarios', :user_id, :accion_log, :datos_anteriores, :datos_nuevos, :ip, :navegador, NOW())");
    
    // Usar nombres de acción más cortos
    $acciones_log = [
        'actualizar_datos' => 'UPDATE_DATOS',
        'cambiar_email' => 'UPDATE_EMAIL',
        'cambiar_password' => 'UPDATE_PASS',
        'resetear_password' => 'RESET_PASS'
    ];
    
    $accion_log = $acciones_log[$accion] ?? 'UPDATE';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $navegador = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255); // Limitar también el navegador
    
    $stmt->bindParam(':admin_id', $userData['id']);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':accion_log', $accion_log);
    $stmt->bindParam(':datos_anteriores', json_encode($datos_anteriores));
    $stmt->bindParam(':datos_nuevos', json_encode($datos_nuevos));
    $stmt->bindParam(':ip', $ip);
    $stmt->bindParam(':navegador', $navegador);
    $stmt->execute();
    
    // Si cambió el rol, manejar tablas relacionadas
    if (isset($datos_nuevos['role']) && $datos_anteriores['role'] !== $datos_nuevos['role']) {
        // Eliminar registros de la tabla anterior si es necesario
        if ($datos_anteriores['role'] === 'doctor' && $datos_nuevos['role'] !== 'doctor') {
            $stmt = $conn->prepare("DELETE FROM doctores WHERE usuario_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        }
        
        if ($datos_anteriores['role'] === 'aseguradora' && $datos_nuevos['role'] !== 'aseguradora') {
            $stmt = $conn->prepare("DELETE FROM aseguradoras WHERE usuario_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        }
    }
    
    $conn->commit();
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => $response['message'],
        "accion" => $accion,
        "password_temporal" => $response['password_temporal'] ?? null
    ]);
    
} catch(Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["error" => "Error al editar usuario: " . $e->getMessage()]);
}
?>