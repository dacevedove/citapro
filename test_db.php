<?php
// Script para verificar la conexión a la base de datos y usuarios

echo "<h2>Verificación de Base de Datos</h2>";

try {
    include_once 'api/config/database.php';
    echo "<p>✓ Conexión a la base de datos: EXITOSA</p>";
    
    // Mostrar información de la conexión
    echo "<p>Base de datos conectada</p>";
    
    // Listar todos los usuarios
    echo "<h3>Usuarios en la base de datos:</h3>";
    $stmt = $conn->prepare("SELECT id, nombre, apellido, email, role, creado_en FROM usuarios ORDER BY id");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($usuarios) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Fecha Creación</th></tr>";
        
        foreach ($usuarios as $usuario) {
            echo "<tr>";
            echo "<td>" . $usuario['id'] . "</td>";
            echo "<td>" . $usuario['nombre'] . " " . $usuario['apellido'] . "</td>";
            echo "<td>" . $usuario['email'] . "</td>";
            echo "<td>" . $usuario['role'] . "</td>";
            echo "<td>" . $usuario['creado_en'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p>Total de usuarios: " . count($usuarios) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ No se encontraron usuarios en la base de datos</p>";
        echo "<p>Necesitas ejecutar el archivo SQL o crear usuarios manualmente</p>";
    }
    
    // Probar login específico
    echo "<h3>Prueba de búsqueda de usuario admin:</h3>";
    $email_test = 'admin@lgm.com';
    $stmt = $conn->prepare("SELECT id, email, role FROM usuarios WHERE email = :email");
    $stmt->bindParam(':email', $email_test);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p>✓ Usuario admin encontrado: " . $admin['email'] . " (ID: " . $admin['id'] . ", Rol: " . $admin['role'] . ")</p>";
    } else {
        echo "<p style='color: red;'>❌ Usuario admin NO encontrado</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    p { margin: 10px 0; }
</style>