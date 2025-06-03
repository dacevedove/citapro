<?php
// Servicio de notificaciones por SMS
// Se puede utilizar un servicio de SMS como Twilio, Nexmo, etc.

// Para este ejemplo, usaremos Twilio
// Cargar Twilio mediante Composer
require_once __DIR__ . '/../../vendor/autoload.php';
use Twilio\Rest\Client;

function enviarNotificacionSMS($telefono, $mensaje) {
    // Configuración de Twilio
    $twilio_sid = 'tu_twilio_sid';
    $twilio_token = 'tu_twilio_token';
    $twilio_number = 'tu_twilio_number';
    
    try {
        // Inicializar cliente Twilio
        $client = new Client($twilio_sid, $twilio_token);
        
        // Enviar mensaje
        $message = $client->messages->create(
            $telefono,
            [
                'from' => $twilio_number,
                'body' => $mensaje
            ]
        );
        
        // Si llegamos aquí, el mensaje se envió correctamente
        return true;
    } catch (Exception $e) {
        // Registrar error en log
        error_log("Error al enviar SMS: " . $e->getMessage());
        return false;
    }
}

// Función para notificar confirmación de cita por SMS
function notificarConfirmacionCitaSMS($citaId, $conn) {
    try {
        // Obtener información detallada de la cita
        $stmt = $conn->prepare("
            SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, 
            p.telefono as paciente_telefono, e.nombre as especialidad,
            DATE_FORMAT(c.fecha, '%d/%m/%Y') as fecha_formateada,
            DATE_FORMAT(c.hora, '%h:%i %p') as hora_formateada,
            CONCAT(u.nombre, ' ', u.apellido) as doctor_nombre,
            co.nombre as consultorio_nombre, co.ubicacion as consultorio_ubicacion
            FROM citas c
            JOIN pacientes p ON c.paciente_id = p.id
            JOIN especialidades e ON c.especialidad_id = e.id
            JOIN doctores d ON c.doctor_id = d.id
            JOIN usuarios u ON d.usuario_id = u.id
            JOIN consultorios co ON c.consultorio_id = co.id
            WHERE c.id = :cita_id
        ");
        
        $stmt->bindParam(':cita_id', $citaId);
        $stmt->execute();
        $cita = $stmt->fetch();
        
        if (!$cita || !$cita['paciente_telefono']) {
            return false; // No hay información para notificar o no hay teléfono
        }
        
        // Crear mensaje (limitado por longitud de SMS)
        $mensaje = "Clínica La Guerra Mendez: Su cita con Dr. {$cita['doctor_nombre']} ({$cita['especialidad']}) está confirmada para el {$cita['fecha_formateada']} a las {$cita['hora_formateada']} en {$cita['consultorio_nombre']} ({$cita['consultorio_ubicacion']}).";
        
        // Enviar notificación
        $enviado = enviarNotificacionSMS($cita['paciente_telefono'], $mensaje);
        
        // Registrar la notificación
        $stmt = $conn->prepare("
            INSERT INTO notificaciones (cita_id, tipo, estado)
            VALUES (:cita_id, 'sms', :estado)
        ");
        
        $estado = $enviado ? 'enviada' : 'fallida';
        $stmt->bindParam(':cita_id', $citaId);
        $stmt->bindParam(':estado', $estado);
        $stmt->execute();
        
        return $enviado;
        
    } catch (PDOException $e) {
        error_log("Error al notificar confirmación de cita por SMS: " . $e->getMessage());
        return false;
    }
}
?>