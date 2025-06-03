<?php
// Servicio de notificaciones por correo electrónico
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cargar PHPMailer mediante Composer
require_once __DIR__ . '/../../vendor/autoload.php';

function enviarNotificacionEmail($destinatario, $asunto, $mensaje) {
    // Configuración de PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor
        $mail->SMTPDebug = 0;                      // Habilitar salida de depuración detallada (0 = desactivado)
        $mail->isSMTP();                           // Enviar usando SMTP
        $mail->Host       = 'smtp.example.com';    // Servidor SMTP
        $mail->SMTPAuth   = true;                  // Habilitar autenticación SMTP
        $mail->Username   = 'notificaciones@lgm.com'; // Usuario SMTP
        $mail->Password   = 'tu_contraseña_aqui';  // Contraseña SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Habilitar cifrado TLS
        $mail->Port       = 587;                   // Puerto TCP
        
        // Configuración del remitente y destinatario
        $mail->setFrom('notificaciones@lgm.com', 'Clínica La Guerra Mendez');
        $mail->addAddress($destinatario);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje;
        $mail->AltBody = strip_tags($mensaje);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Registrar error en log
        error_log("Error al enviar correo: {$mail->ErrorInfo}");
        return false;
    }
}

// Función para notificar creación de cita
function notificarCreacionCita($citaId, $conn) {
    try {
        // Obtener información de la cita
        $stmt = $conn->prepare("
            SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, 
            p.email as paciente_email, e.nombre as especialidad
            FROM citas c
            JOIN pacientes p ON c.paciente_id = p.id
            JOIN especialidades e ON c.especialidad_id = e.id
            WHERE c.id = :cita_id
        ");
        
        $stmt->bindParam(':cita_id', $citaId);
        $stmt->execute();
        $cita = $stmt->fetch();
        
        if (!$cita || !$cita['paciente_email']) {
            return false; // No hay información para notificar o no hay email
        }
        
        // Crear mensaje
        $asunto = "Solicitud de cita recibida - Clínica La Guerra Mendez";
        
        $mensaje = "
            <html>
            <body>
                <h1>Solicitud de cita recibida</h1>
                <p>Estimado/a {$cita['paciente_nombre']} {$cita['paciente_apellido']},</p>
                <p>Hemos recibido su solicitud de cita para la especialidad de <strong>{$cita['especialidad']}</strong>.</p>
                <p>Su número de solicitud es: <strong>#{$cita['id']}</strong></p>
                <p>Pronto nos comunicaremos con usted para confirmar la fecha y hora de su cita.</p>
                <p>Saludos cordiales,<br>
                Equipo de Clínica La Guerra Mendez</p>
            </body>
            </html>
        ";
        
        // Enviar notificación
        $enviado = enviarNotificacionEmail($cita['paciente_email'], $asunto, $mensaje);
        
        // Registrar la notificación
        $stmt = $conn->prepare("
            INSERT INTO notificaciones (cita_id, tipo, estado)
            VALUES (:cita_id, 'email', :estado)
        ");
        
        $estado = $enviado ? 'enviada' : 'fallida';
        $stmt->bindParam(':cita_id', $citaId);
        $stmt->bindParam(':estado', $estado);
        $stmt->execute();
        
        return $enviado;
        
    } catch (PDOException $e) {
        error_log("Error al notificar creación de cita: " . $e->getMessage());
        return false;
    }
}

// Función para notificar confirmación de cita
function notificarConfirmacionCita($citaId, $conn) {
    try {
        // Obtener información detallada de la cita
        $stmt = $conn->prepare("
            SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, 
            p.email as paciente_email, e.nombre as especialidad,
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
        
        if (!$cita || !$cita['paciente_email']) {
            return false; // No hay información para notificar o no hay email
        }
        
        // Formatear fecha y hora
        $fechaHora = new DateTime("{$cita['fecha']} {$cita['hora']}");
        $fechaFormateada = $fechaHora->format('d/m/Y');
        $horaFormateada = $fechaHora->format('h:i A');
        
        // Crear mensaje
        $asunto = "Cita confirmada - Clínica La Guerra Mendez";
        
        $mensaje = "
        <html>
        <body>
            <h1>Cita confirmada</h1>
            <p>Estimado/a {$cita['paciente_nombre']} {$cita['paciente_apellido']},</p>
            <p>Su cita ha sido confirmada con los siguientes detalles:</p>
            
            <table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;'>Especialidad</th>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$cita['especialidad']}</td>
                </tr>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;'>Doctor</th>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$cita['doctor_nombre']}</td>
                </tr>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;'>Fecha</th>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$fechaFormateada}</td>
                </tr>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;'>Hora</th>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$horaFormateada}</td>
                </tr>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;'>Consultorio</th>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$cita['consultorio_nombre']} ({$cita['consultorio_ubicacion']})</td>
                </tr>
            </table>
            
            <p>Por favor, llegue 15 minutos antes de su hora programada.</p>
            <p>Si necesita cancelar o reprogramar su cita, hágalo con al menos 24 horas de anticipación.</p>
            <p>Saludos cordiales,<br>
            Equipo de Clínica La Guerra Mendez</p>
        </body>
        </html>
    ";
    
    // Enviar notificación
    $enviado = enviarNotificacionEmail($cita['paciente_email'], $asunto, $mensaje);
    
    // Registrar la notificación
    $stmt = $conn->prepare("
        INSERT INTO notificaciones (cita_id, tipo, estado)
        VALUES (:cita_id, 'email', :estado)
    ");
    
    $estado = $enviado ? 'enviada' : 'fallida';
    $stmt->bindParam(':cita_id', $citaId);
    $stmt->bindParam(':estado', $estado);
    $stmt->execute();
    
    return $enviado;
    
} catch (PDOException $e) {
    error_log("Error al notificar confirmación de cita: " . $e->getMessage());
    return false;
}
}
?>