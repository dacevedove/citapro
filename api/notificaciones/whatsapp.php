<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Para solicitudes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir archivos de configuración
include_once '../../config/database.php';
include_once '../../config/jwt.php';

// Obtener JWT del encabezado
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Validar token
$userData = validateJWT($jwt);
if (!$userData || !in_array($userData['role'], ['admin', 'vertice', 'doctor', 'aseguradora'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Función para registrar una notificación en la base de datos
function registrarNotificacion($asignacionId, $citaId, $pacienteId, $telefono, $mensaje, $creadoPor) {
    try {
        $conn = Database::getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO temp_notificaciones 
            (asignacion_id, cita_id, paciente_id, tipo, telefono, mensaje, estado, creado_por) 
            VALUES 
            (:asignacion_id, :cita_id, :paciente_id, 'whatsapp', :telefono, :mensaje, 'pendiente', :creado_por)
        ");
        
        $stmt->bindParam(':asignacion_id', $asignacionId);
        $stmt->bindParam(':cita_id', $citaId);
        $stmt->bindParam(':paciente_id', $pacienteId);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':mensaje', $mensaje);
        $stmt->bindParam(':creado_por', $creadoPor);
        
        $stmt->execute();
        
        return [
            'success' => true,
            'id' => $conn->lastInsertId(),
            'message' => 'Notificación registrada exitosamente'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al registrar la notificación: ' . $e->getMessage()
        ];
    }
}

// Función para enviar mensaje por WhatsApp
function enviarWhatsApp($notificacionId, $telefono, $datos, $template = "cita_confirmacion") {
    try {
        $conn = Database::getConnection();
        
        // Actualizar registro para indicar intento de envío
        $stmt = $conn->prepare("
            UPDATE temp_notificaciones 
            SET intentos = intentos + 1 
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $notificacionId);
        $stmt->execute();
        
        // Configuración de WhatsApp Business API
        $whatsappConfig = [
            'api_key' => getenv('WHATSAPP_API_KEY') ?: 'EAAymkiPY9ZBABO5ejVFXRm4EyXLvGb46VQU2AUc0rDKVEhLrxclcixk2fAAdRMZCdPRzbfUnkpBP7xZCjGaE0Eg1ByTrYP2Tcpnl8EkjpE7no35zuIXggvOfW4oasBKy2O1xHc35jEWxpliaCjJnjR7CUmzb58BpvWEbwZBO5vxAZANkkBer1XCtoJq1SBY6ImN0v2prZByDmZAhhlmCUzJhTgXguRfRlNZAhgvXrk47',
            'phone_number_id' => getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '642735748921935',
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
                        'parameters' => $datos
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
        $enviado = false;
        $mensaje = '';
        
        if ($err) {
            $mensaje = "Error en la solicitud cURL: " . $err;
        } else {
            $responseData = json_decode($response, true);
            if ($httpCode >= 200 && $httpCode < 300) {
                $enviado = true;
                $mensaje = "Mensaje enviado exitosamente";
            } else {
                $mensaje = "Error al enviar el mensaje: " . ($responseData['error']['message'] ?? 'Error desconocido');
            }
        }
        
        // Actualizar el estado en la base de datos
        $estado = $enviado ? 'enviada' : 'fallida';
        $stmt = $conn->prepare("
            UPDATE temp_notificaciones 
            SET estado = :estado, respuesta_api = :respuesta 
            WHERE id = :id
        ");
        
        $respuestaJSON = json_encode([
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $err
        ]);
        
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':respuesta', $respuestaJSON);
        $stmt->bindParam(':id', $notificacionId);
        $stmt->execute();
        
        return [
            'success' => $enviado,
            'message' => $mensaje,
            'http_code' => $httpCode,
            'response' => $responseData ?? null,
            'error' => $err ?: null
        ];
        
    } catch (Exception $e) {
        // Actualizar estado a fallido en caso de excepción
        $conn = Database::getConnection();
        $estado = 'fallida';
        $stmt = $conn->prepare("
            UPDATE temp_notificaciones 
            SET estado = :estado, respuesta_api = :respuesta 
            WHERE id = :id
        ");
        
        $respuestaJSON = json_encode(['error' => $e->getMessage()]);
        
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':respuesta', $respuestaJSON);
        $stmt->bindParam(':id', $notificacionId);
        $stmt->execute();
        
        return [
            'success' => false,
            'message' => 'Error al enviar WhatsApp: ' . $e->getMessage()
        ];
    }
}

// Función para obtener detalles de una cita
function obtenerDetallesCita($citaId) {
    try {
        $conn = Database::getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                c.id, c.fecha, c.hora, 
                p.id as paciente_id, p.nombre as nombre_paciente, p.apellido as apellido_paciente, p.telefono,
                d.id as doctor_id, 
                u.nombre as nombre_doctor, u.apellido as apellido_doctor,
                e.id as especialidad_id, e.nombre as especialidad,
                co.id as consultorio_id, co.nombre as consultorio, co.ubicacion
            FROM citas c
            JOIN pacientes p ON c.paciente_id = p.id
            JOIN doctores d ON c.doctor_id = d.id
            JOIN usuarios u ON d.usuario_id = u.id
            JOIN especialidades e ON c.especialidad_id = e.id
            LEFT JOIN consultorios co ON c.consultorio_id = co.id
            WHERE c.id = :cita_id
        ");
        
        $stmt->bindParam(':cita_id', $citaId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        return null;
    }
}

// Procesar solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obtener datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"));
    
    // Validar datos requeridos
    if (!isset($data->asignacion_id) || !isset($data->cita_id)) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incompletos. Se requiere asignacion_id y cita_id"]);
        exit;
    }
    
    // Obtener detalles de la cita
    $cita = obtenerDetallesCita($data->cita_id);
    
    if (!$cita) {
        http_response_code(404);
        echo json_encode(["error" => "No se encontró la cita especificada"]);
        exit;
    }
    
    // Verificar si hay un número de teléfono
    if (empty($cita['telefono'])) {
        http_response_code(400);
        echo json_encode(["error" => "El paciente no tiene número de teléfono registrado"]);
        exit;
    }
    
    // Formatear fecha y hora para el mensaje
    $fecha = date('d/m/Y', strtotime($cita['fecha']));
    $hora = date('g:i A', strtotime($cita['hora']));
    
    // Ubicación del consultorio
    $ubicacion = $cita['consultorio'] ? "{$cita['consultorio']} - {$cita['ubicacion']}" : "Consultorio principal";
    
    // Crear mensaje para WhatsApp
    $mensaje = json_encode([
        [
            "type" => "text",
            "text" => "{$cita['nombre_paciente']} {$cita['apellido_paciente']}"
        ],
        [
            "type" => "text",
            "text" => "Dr. {$cita['nombre_doctor']} {$cita['apellido_doctor']}"
        ],
        [
            "type" => "text",
            "text" => $cita['especialidad']
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
    ]);
    
    // Registrar la notificación en la base de datos
    $registroResult = registrarNotificacion(
        $data->asignacion_id,
        $data->cita_id,
        $cita['paciente_id'],
        $cita['telefono'],
        $mensaje,
        $userData['id']
    );
    
    if (!$registroResult['success']) {
        http_response_code(500);
        echo json_encode([
            "error" => $registroResult['message'],
            "details" => "Error al registrar la notificación"
        ]);
        exit;
    }
    
    // Enviar el mensaje por WhatsApp
    $notificacionId = $registroResult['id'];
    $envioResult = enviarWhatsApp(
        $notificacionId,
        $cita['telefono'],
        json_decode($mensaje, true),
        isset($data->template) ? $data->template : "cita_confirmacion"
    );
    
    // Devolver resultado completo
    $response = [
        "notification_id" => $notificacionId,
        "registration" => $registroResult,
        "whatsapp" => $envioResult,
        "success" => $envioResult['success'],
        "message" => $envioResult['success'] 
            ? "Notificación WhatsApp enviada exitosamente" 
            : "Error al enviar la notificación WhatsApp: " . $envioResult['message']
    ];
    
    http_response_code($envioResult['success'] ? 200 : 500);
    echo json_encode($response);
    
} else {
    // Si no es una solicitud POST, mostrar información del endpoint
    http_response_code(200);
    echo json_encode([
        "info" => "API de notificaciones WhatsApp",
        "version" => "2.0",
        "endpoint" => "/api/notificaciones/whatsapp.php",
        "method" => "POST",
        "required_parameters" => [
            "asignacion_id" => "ID de la asignación",
            "cita_id" => "ID de la cita"
        ],
        "optional_parameters" => [
            "template" => "Nombre de la plantilla (por defecto: cita_confirmacion)"
        ],
        "description" => "Envía notificaciones WhatsApp de citas a pacientes y registra el seguimiento"
    ]);
}