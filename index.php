<?php
// Proteger archivo de acceso directo
defined('BASEPATH') or define('BASEPATH', true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clínica La Guerra Mendez - Sistema de Gestión de Citas</title>
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos de carga iniciales */
        .preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }
        
        .preloader.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solidrgb(51, 40, 38);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <!-- CSRF Token para seguridad -->
    <meta name="csrf-token" content="<?php echo bin2hex(random_bytes(32)); ?>">
        <!-- Estilos personalizados -->
        <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <!-- Preloader para la carga inicial -->
    <div class="preloader">
        <div class="spinner"></div>
    </div>
    
    <!-- Contenedor para la aplicación Vue -->
    <div id="app"></div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (file_exists(__DIR__ . '/assets/dist/.vite/manifest.json')): ?>
        <!-- Producción: cargar recursos compilados -->
        <?php
        $manifest = json_decode(file_get_contents(__DIR__ . '/assets/dist/.vite/manifest.json'), true);
        $entry = $manifest['assets/js/main.js'];
        ?>
        <script type="module" src="assets/dist/<?php echo $entry['file']; ?>"></script>
        <?php if (isset($entry['css'])): ?>
            <?php foreach ($entry['css'] as $css): ?>
                <link rel="stylesheet" href="assets/dist/<?php echo $css; ?>">
            <?php endforeach; ?>
        <?php endif; ?>
    <?php else: ?>
        <!-- Desarrollo: cargar recursos via Vite -->
        <script type="module" src="http://localhost:5173/@vite/client"></script>
        <script type="module" src="http://localhost:5173/assets/js/main.js"></script>
    <?php endif; ?>
    <script>
        // Ocultar preloader cuando la app esté cargada
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.querySelector('.preloader').classList.add('hidden');
            }, 500);
        });
    </script>
</body>
</html>
