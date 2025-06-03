<?php
// Configurar cabeceras para respuesta HTML
header("Content-Type: text/html; charset=UTF-8");

// Inicializar variables para mostrar resultados
$resultado = "";
$estado = "";

// Función para consultar plantillas disponibles
function consultarPlantillasDisponibles() {
    // Configuración de WhatsApp Business API
    $whatsappConfig = [
        'api_key' => getenv('WHATSAPP_API_KEY') ?: 'your_api_key_here',
        'phone_number_id' => getenv('WHATSAPP_PHONE_NUMBER_ID') ?: 'your_phone_number_id',
        'version' => 'v17.0',
        'base_url' => 'https://graph.facebook.com'
    ];
    
    // URL para consultar las plantillas disponibles
    $url = "{$whatsappConfig['base_url']}/{$whatsappConfig['version']}/{$whatsappConfig['phone_number_id']}/message_templates";
    
    // Realizar solicitud con cURL
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $whatsappConfig['api_key'],
            'Content-Type: application/json'
        ],
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        return [
            'success' => false,
            'message' => "Error al consultar plantillas: " . $err,
            'templates' => []
        ];
    } else {
        $responseData = json_decode($response, true);
        return [
            'success' => true,
            'message' => "Plantillas consultadas exitosamente",
            'templates' => $responseData['data'] ?? []
        ];
    }
}

// Función para enviar mensaje por WhatsApp
function enviarWhatsApp($telefono, $mensaje, $template = "hello_world") {
    // Configuración de WhatsApp Business API
    $whatsappConfig = [
        'api_key' => getenv('WHATSAPP_API_KEY') ?: 'your_api_key_here',
        'phone_number_id' => getenv('WHATSAPP_PHONE_NUMBER_ID') ?: 'your_phone_number_id',
        'version' => 'v17.0',
        'base_url' => 'https://graph.facebook.com'
    ];
    
    // Formatear el número de teléfono
    $formattedPhone = preg_replace('/[^0-9]/', '', $telefono);
    
    // Asegurarse de que el número tenga código de país (Panamá = 507)
    if (strlen($formattedPhone) <= 8) {
        $formattedPhone = "507" . $formattedPhone;
    }
    
    // Agregar "+" al inicio si no existe
    if (substr($formattedPhone, 0, 1) !== '+') {
        $formattedPhone = "+" . $formattedPhone;
    }
    
    // Preparar datos para la API de WhatsApp
    $postData = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $formattedPhone,
        'type' => 'template',
        'template' => [
            'name' => $template,
            'language' => [
                'code' => 'es'
            ],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => $mensaje
                ]
            ]
        ]
    ];
    
    // URL para enviar mensajes
    $url = "{$whatsappConfig['base_url']}/{$whatsappConfig['version']}/{$whatsappConfig['phone_number_id']}/messages";
    
    // Realizar solicitud con cURL
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $whatsappConfig['api_key'],
            'Content-Type: application/json'
        ],
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // Preparar respuesta
    if ($err) {
        return [
            'success' => false,
            'message' => "Error en la solicitud cURL: " . $err,
            'http_code' => $httpCode,
            'response' => null,
            'error' => $err
        ];
    } else {
        $responseData = json_decode($response, true);
        $success = ($httpCode >= 200 && $httpCode < 300);
        $message = $success 
            ? "Mensaje enviado exitosamente" 
            : "Error al enviar el mensaje: " . ($responseData['error']['message'] ?? 'Error desconocido');
        
        return [
            'success' => $success,
            'message' => $message,
            'http_code' => $httpCode,
            'response' => $responseData,
            'error' => null
        ];
    }
}

// Consultar plantillas disponibles
$plantillas = [];
$plantillasResult = consultarPlantillasDisponibles();
if ($plantillasResult['success']) {
    $plantillas = $plantillasResult['templates'];
}

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_prueba'])) {
    // Obtener datos del formulario
    $telefono = $_POST['telefono'] ?? '';
    $nombre_paciente = $_POST['nombre_paciente'] ?? '';
    $apellido_paciente = $_POST['apellido_paciente'] ?? '';
    $nombre_doctor = $_POST['nombre_doctor'] ?? '';
    $apellido_doctor = $_POST['apellido_doctor'] ?? '';
    $especialidad = $_POST['especialidad'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $ubicacion = $_POST['ubicacion'] ?? '';
    $template = $_POST['template'] ?? 'hello_world';
    
    // Crear mensaje para WhatsApp
    $mensaje = [
        [
            "type" => "text",
            "text" => "$nombre_paciente $apellido_paciente"
        ],
        [
            "type" => "text",
            "text" => "Dr. $nombre_doctor $apellido_doctor"
        ],
        [
            "type" => "text",
            "text" => $especialidad
        ],
        [
            "type" => "text",
            "text" => $fecha
        ],
        [
            "type" => "text",
            "text" => $hora
        ],
        [
            "type" => "text",
            "text" => $ubicacion
        ]
    ];
    
    // Para "hello_world" simplificamos el mensaje
    if ($template === 'hello_world') {
        $mensaje = [
            [
                "type" => "text",
                "text" => "$nombre_paciente $apellido_paciente"
            ]
        ];
    }
    
    // Enviar el mensaje
    $result = enviarWhatsApp($telefono, $mensaje, $template);
    
    // Preparar resultado para mostrar
    $estado = $result['success'] ? 'success' : 'error';
    $resultado = "<h3>Resultado del envío:</h3>";
    $resultado .= "<p><strong>Estado:</strong> " . ($result['success'] ? 'Éxito' : 'Error') . "</p>";
    $resultado .= "<p><strong>Mensaje:</strong> " . htmlspecialchars($result['message']) . "</p>";
    $resultado .= "<p><strong>Código HTTP:</strong> " . htmlspecialchars($result['http_code']) . "</p>";
    $resultado .= "<p><strong>Respuesta:</strong> <pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "</pre></p>";
    
    if ($result['error']) {
        $resultado .= "<p><strong>Error:</strong> " . htmlspecialchars($result['error']) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Envío de WhatsApp</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #2980b9;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .templates-list {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
        }
        .template-item {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Prueba de Envío de WhatsApp</h1>
    
    <?php if (count($plantillas) > 0): ?>
    <div class="templates-list">
        <h2>Plantillas Disponibles</h2>
        <?php foreach ($plantillas as $plantilla): ?>
        <div class="template-item">
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($plantilla['name']); ?></p>
            <p><strong>Idioma:</strong> <?php echo htmlspecialchars($plantilla['language'] ?? 'No especificado'); ?></p>
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($plantilla['status'] ?? 'No especificado'); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php elseif (isset($plantillasResult) && !$plantillasResult['success']): ?>
    <div class="result error">
        <p>Error al consultar plantillas: <?php echo htmlspecialchars($plantillasResult['message']); ?></p>
    </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="form-group">
            <label for="telefono">Teléfono del paciente:</label>
            <input type="text" id="telefono" name="telefono" required placeholder="Ej: 6123-4567">
        </div>
        
        <div class="form-group">
            <label for="nombre_paciente">Nombre del paciente:</label>
            <input type="text" id="nombre_paciente" name="nombre_paciente" required>
        </div>
        
        <div class="form-group">
            <label for="apellido_paciente">Apellido del paciente:</label>
            <input type="text" id="apellido_paciente" name="apellido_paciente" required>
        </div>
        
        <div class="form-group">
            <label for="nombre_doctor">Nombre del doctor:</label>
            <input type="text" id="nombre_doctor" name="nombre_doctor" required>
        </div>
        
        <div class="form-group">
            <label for="apellido_doctor">Apellido del doctor:</label>
            <input type="text" id="apellido_doctor" name="apellido_doctor" required>
        </div>
        
        <div class="form-group">
            <label for="especialidad">Especialidad:</label>
            <input type="text" id="especialidad" name="especialidad" required>
        </div>
        
        <div class="form-group">
            <label for="fecha">Fecha (dd/mm/aaaa):</label>
            <input type="text" id="fecha" name="fecha" required placeholder="Ej: 01/05/2025">
        </div>
        
        <div class="form-group">
            <label for="hora">Hora:</label>
            <input type="text" id="hora" name="hora" required placeholder="Ej: 3:30 PM">
        </div>
        
        <div class="form-group">
            <label for="ubicacion">Ubicación:</label>
            <input type="text" id="ubicacion" name="ubicacion" required>
        </div>
        
        <div class="form-group">
            <label for="template">Plantilla de WhatsApp:</label>
            <select id="template" name="template">
                <option value="hello_world">hello_world (plantilla predeterminada)</option>
                <?php foreach ($plantillas as $plantilla): ?>
                <option value="<?php echo htmlspecialchars($plantilla['name']); ?>"><?php echo htmlspecialchars($plantilla['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" name="enviar_prueba">Enviar Prueba</button>
    </form>
    
    <?php if ($resultado): ?>
    <div class="result <?php echo $estado; ?>">
        <?php echo $resultado; ?>
    </div>
    <?php endif; ?>
</body>
</html>