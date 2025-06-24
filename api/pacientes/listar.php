<?php
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Base de la consulta SQL
    $sql = "SELECT p.*, 
           CASE WHEN p.tipo = 'asegurado' THEN 
               (SELECT a.nombre_comercial FROM aseguradoras a 
                JOIN titulares t ON a.id = t.aseguradora_id 
                WHERE t.id = p.titular_id LIMIT 1) 
           ELSE NULL END as aseguradora_nombre,
           (SELECT COUNT(*) FROM pacientes_seguros ps 
            WHERE ps.paciente_id = p.id AND ps.estado = 'activo') as seguros_activos_count
           FROM pacientes p
           LEFT JOIN titulares t ON p.titular_id = t.id
           WHERE 1=1";
    
    $params = [];
    
    // Filtros
    if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
        $busqueda = '%' . $_GET['busqueda'] . '%';
        $sql .= " AND (p.nombre LIKE :busqueda OR p.apellido LIKE :busqueda OR p.cedula LIKE :busqueda)";
        $params[':busqueda'] = $busqueda;
    }
    
    if (isset($_GET['aseguradora_id']) && !empty($_GET['aseguradora_id'])) {
        $sql .= " AND EXISTS (SELECT 1 FROM titulares ti 
                 WHERE ti.id = p.titular_id 
                 AND ti.aseguradora_id = :aseguradora_id)";
        $params[':aseguradora_id'] = $_GET['aseguradora_id'];
    }
    
    if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
        $sql .= " AND p.tipo = :tipo";
        $params[':tipo'] = $_GET['tipo'];
    }
    
    if (isset($_GET['es_titular'])) {
        $es_titular = (int)$_GET['es_titular'];
        if ($es_titular === 1) {
            // Titular es un paciente con es_titular = 1
            $sql .= " AND p.es_titular = 1";
        } else {
            // Beneficiario es un paciente con es_titular = 0 y titular_id no nulo
            $sql .= " AND p.es_titular = 0 AND p.titular_id IS NOT NULL";
        }
    }
    
    // Ordenar por nombre
    $sql .= " ORDER BY p.nombre, p.apellido";
    
    $stmt = $conn->prepare($sql);
    
    // Asignar parámetros
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener última cita para cada paciente
    foreach ($pacientes as &$paciente) {
        $stmtCita = $conn->prepare("
            SELECT c.fecha, c.estado, e.nombre as especialidad
            FROM citas c
            JOIN especialidades e ON c.especialidad_id = e.id
            WHERE c.paciente_id = :paciente_id
            ORDER BY c.fecha DESC, c.hora DESC
            LIMIT 1
        ");
        
        $stmtCita->bindValue(':paciente_id', $paciente['id'], PDO::PARAM_INT);
        $stmtCita->execute();
        $cita = $stmtCita->fetch(PDO::FETCH_ASSOC);
        
        if ($cita) {
            $paciente['ultima_cita'] = $cita;
        }
    }
    
    echo json_encode($pacientes);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>