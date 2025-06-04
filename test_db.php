<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Para solicitudes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

echo "<h2>Debug del Sistema de Login</h2>";

try {
    include_once 'api/config/database.php';
    
    echo "<h3>1. Verificación de conexión a BD</h3>";
    echo "<p>✅ Conexión exitosa</p>";
    
    echo "<h3>2. Verificación de usuarios</h3>";
    $stmt = $conn->prepare("SELECT id, nombre, apellido, email, role, creado_en FROM usuarios");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($usuarios) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>";
        echo "<th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Fecha</th>";
        echo "</tr>";
        
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
    } else {
        echo "<p style='color: red;'>❌ No hay usuarios en la base de datos</p>";
        
        // Crear usuario admin automáticamente
        echo "<h3>3. Creando usuario admin...</h3>";
        
        $nombre = 'Administrador';
        $apellido = 'Sistema';
        $email = 'admin@lgm.com';
        $password = 'admin123';
        $cedula = 'ADM001';
        $telefono = '0000-0000';
        $role = 'admin';
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $insertStmt = $conn->prepare("
            INSERT INTO usuarios (nombre, apellido, email, password, cedula, telefono, role, creado_en) 
            VALUES (:nombre, :apellido, :email, :password, :cedula, :telefono, :role, NOW())
        ");
        
        $insertStmt->bindParam(':nombre', $nombre);
        $insertStmt->bindParam(':apellido', $apellido);
        $insertStmt->bindParam(':email', $email);
        $insertStmt->bindParam(':password', $hashedPassword);
        $insertStmt->bindParam(':cedula', $cedula);
        $insertStmt->bindParam(':telefono', $telefono);
        $insertStmt->bindParam(':role', $role);
        
        if ($insertStmt->execute()) {
            echo "<p style='color: green;'>✅ Usuario admin creado</p>";
            echo "<p><strong>Email:</strong> admin@lgm.com</p>";
            echo "<p><strong>Contraseña:</strong> admin123</p>";
        }
    }
    
    echo "<h3>4. Prueba de login específica</h3>";
    
    // Simular el login
    $email_test = 'admin@lgm.com';
    $password_test = 'admin123';
    
    $loginStmt = $conn->prepare("SELECT id, nombre, apellido, email, password, role FROM usuarios WHERE email = :email");
    $loginStmt->bindParam(':email', $email_test);
    $loginStmt->execute();
    
    $user = $loginStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p>✅ Usuario encontrado: " . $user['email'] . "</p>";
        
        // Probar verificación de contraseña
        $passwordValid = password_verify($password_test, $user['password']);
        echo "<p>🔐 Verificación de contraseña: " . ($passwordValid ? '✅ VÁLIDA' : '❌ INVÁLIDA') . "</p>";
        
        if (!$passwordValid) {
            echo "<p style='color: orange;'>⚠️ La contraseña no coincide. Actualizando...</p>";
            
            $newHash = password_hash($password_test, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE usuarios SET password = :password WHERE email = :email");
            $updateStmt->bindParam(':password', $newHash);
            $updateStmt->bindParam(':email', $email_test);
            
            if ($updateStmt->execute()) {
                echo "<p style='color: green;'>✅ Contraseña actualizada</p>";
                
                // Verificar nuevamente
                $newVerification = password_verify($password_test, $newHash);
                echo "<p>🔐 Nueva verificación: " . ($newVerification ? '✅ VÁLIDA' : '❌ INVÁLIDA') . "</p>";
            }
        }
        
        echo "<p><strong>Hash almacenado:</strong> " . substr($user['password'], 0, 20) . "...</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Usuario admin@lgm.com NO encontrado</p>";
    }
    
    echo "<h3>5. Prueba del endpoint de login</h3>";
    echo "<p>Ahora prueba el login con:</p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> admin@lgm.com</li>";
    echo "<li><strong>Contraseña:</strong> admin123</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    h2, h3 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 5px; }
    p { margin: 8px 0; }
</style>