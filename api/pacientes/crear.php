<?php
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener el cuerpo JSON del request
    $data = json_decode(file_get_contents("php://input"), true);

    // Validar los campos básicos requeridos para todos los tipos de pacientes
    if (!isset($data['nombre'], $data['apellido'], $data['cedula'], $data['telefono'], $data['tipo'])) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Faltan datos básicos requeridos."
        ]);
        exit;
    }

    // Preparar variables comunes
    $nombre = $data['nombre'];
    $apellido = $data['apellido'];
    $cedula = $data['cedula'];
    $telefono = $data['telefono'];
    $email = $data['email'] ?? '';
    $tipo = $data['tipo'];
    
    // SQL base
    $sql = "INSERT INTO pacientes (nombre, apellido, cedula, telefono, email, tipo";
    $valores = " VALUES (:nombre, :apellido, :cedula, :telefono, :email, :tipo";
    
    $parametros = [
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':cedula' => $cedula,
        ':telefono' => $telefono,
        ':email' => $email,
        ':tipo' => $tipo
    ];
    
    // Si es asegurado, validar y agregar campos adicionales
    if ($tipo === 'asegurado') {
        if (!isset($data['titular_id'], $data['parentesco'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Para pacientes asegurados se requiere titular_id y parentesco."
            ]);
            exit;
        }
        
        $titular_id = $data['titular_id'];
        $parentesco = $data['parentesco'];
        
        $sql .= ", titular_id, parentesco";
        $valores .= ", :titular_id, :parentesco";
        
        $parametros[':titular_id'] = $titular_id;
        $parametros[':parentesco'] = $parentesco;
    }
    
    // Cerrar la consulta SQL
    $sql .= ")";
    $valores .= ")";
    
    // Preparar la sentencia completa
    $stmt = $conn->prepare($sql . $valores);
    
    // Asignar todos los parámetros
    foreach ($parametros as $param => $value) {
        $tipo_param = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($param, $value, $tipo_param);
    }
    
    // Ejecutar
    $stmt->execute();
    
    // Responder al frontend
    echo json_encode([
        "status" => "success",
        "message" => "Paciente registrado correctamente"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>