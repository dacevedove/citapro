<?php
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar que se proporciona un ID
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Se requiere el ID del titular"
        ]);
        exit;
    }

    $id = $_GET['id'];
    
    // Preparar la consulta SQL para obtener el titular con datos de la aseguradora
    $stmt = $conn->prepare("
        SELECT t.*, a.nombre_comercial as aseguradora_nombre 
        FROM titulares t
        LEFT JOIN aseguradoras a ON t.aseguradora_id = a.id
        WHERE t.id = :id
    ");
    
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $titular = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$titular) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Titular no encontrado"
        ]);
        exit;
    }
    
    echo json_encode($titular);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>