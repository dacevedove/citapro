<?php
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener el cuerpo JSON del request
    $data = json_decode(file_get_contents("php://input"), true);

    // Validar que se recibió el ID
    if (!isset($data['id']) || !isset($data['nombre'], $data['apellido'], $data['cedula'], $data['telefono'], $data['tipo'])) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Faltan datos básicos requeridos."
        ]);
        exit;
    }

    // Preparar variables comunes
    $id = $data['id'];
    $nombre = $data['nombre'];
    $apellido = $data['apellido'];
    $cedula = $data['cedula'];
    $telefono = $data['telefono'];
    $email = $data['email'] ?? '';
    $tipo = $data['tipo'];
    
    // SQL base
    $sql = "UPDATE pacientes SET nombre = :nombre, apellido = :apellido, cedula = :cedula, 
            telefono = :telefono, email = :email, tipo = :tipo";
    
    $parametros = [
        ':id' => $id,
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':cedula' => $cedula,
        ':telefono' => $telefono,
        ':email' => $email,
        ':tipo' => $tipo
    ];
    
    // Si es asegurado y tiene titular_id y parentesco, actualizar esos campos
    if ($tipo === 'asegurado' && isset($data['titular_id'], $data['parentesco'])) {
        $sql .= ", titular_id = :titular_id, parentesco = :parentesco";
        $parametros[':titular_id'] = $data['titular_id'];
        $parametros[':parentesco'] = $data['parentesco'];
    }
    
    // Completar la consulta SQL
    $sql .= " WHERE id = :id";
    
    // Preparar la sentencia
    $stmt = $conn->prepare($sql);
    
    // Asignar todos los parámetros
    foreach ($parametros as $param => $value) {
        $tipo_param = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($param, $value, $tipo_param);
    }
    
    // Ejecutar
    $stmt->execute();
    
    // Si el paciente es titular y tiene aseguradora_id, actualizar también la aseguradora
    if ($tipo === 'asegurado' && isset($data['es_titular']) && $data['es_titular'] == 1 && isset($data['aseguradora_id'])) {
        // Verificar si hay un registro en titulares donde el titular_id coincide con el valor es_titular del paciente
        // Primero necesitamos encontrar el ID del titular correspondiente a este paciente
        $stmtTitular = $conn->prepare("SELECT t.id FROM titulares t JOIN pacientes p ON t.cedula = p.cedula WHERE p.id = :paciente_id");
        $stmtTitular->bindParam(':paciente_id', $id, PDO::PARAM_INT);
        $stmtTitular->execute();
        
        if ($titular = $stmtTitular->fetch(PDO::FETCH_ASSOC)) {
            // Actualizar el registro en titulares
            $stmtUpdate = $conn->prepare("UPDATE titulares SET aseguradora_id = :aseguradora_id WHERE id = :titular_id");
            $stmtUpdate->bindParam(':aseguradora_id', $data['aseguradora_id'], PDO::PARAM_INT);
            $stmtUpdate->bindParam(':titular_id', $titular['id'], PDO::PARAM_INT);
            $stmtUpdate->execute();
        }
    }
    
    // Verificar si se actualizó algún registro
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Paciente actualizado correctamente"
        ]);
    } else {
        echo json_encode([
            "status" => "warning",
            "message" => "No se encontró el paciente o no hubo cambios"
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>