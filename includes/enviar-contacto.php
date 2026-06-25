<?php
/**
 * Controlador de Contact Form
 * Procesa el envío de emails desde el formulario de contacto
 */

require_once __DIR__ . '/../config/email.php';

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Función simple de envío de email
function enviarEmail($datos) {
    // Validar datos
    $nombre = filter_var(trim($datos['name']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($datos['email']), FILTER_VALIDATE_EMAIL);
    $asunto = filter_var(trim($datos['subject']), FILTER_SANITIZE_STRING);
    $mensaje = filter_var(trim($datos['message']), FILTER_SANITIZE_STRING);
    
    if (!$nombre || !$email || !$asunto || !$mensaje) {
        return ['success' => false, 'error' => 'Todos los campos son obligatorios y deben ser válidos'];
    }
    
    // Preparar email
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . EMAIL_REMITENTE,
        'Reply-To: ' . $email,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $asunto_completo = EMAIL_ASUNTO_PREFIJO . $asunto;
    
    $contenido_html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Nuevo mensaje desde la web</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
            <h2 style='color: #6A1B9A; text-align: center;'>Nuevo mensaje desde la web</h2>
            
            <div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #6A1B9A;'>Datos del remitente:</h3>
                <p><strong>Nombre:</strong> " . htmlspecialchars($nombre) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                <p><strong>Asunto:</strong> " . htmlspecialchars($asunto) . "</p>
            </div>
            
            <div style='background: #fff; padding: 15px; border-left: 4px solid #6A1B9A;'>
                <h3 style='margin-top: 0; color: #6A1B9A;'>Mensaje:</h3>
                <p>" . nl2br(htmlspecialchars($mensaje)) . "</p>
            </div>
            
            <div style='text-align: center; margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px;'>
                <small style='color: #666;'>
                    Mensaje recibido el " . date('d/m/Y H:i:s') . " desde la web de EESO 225 \"La San Martín\"
                </small>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Intentar envío
    try {
        if (mail(EMAIL_DESTINO, $asunto_completo, $contenido_html, implode("\r\n", $headers))) {
            return ['success' => true, 'message' => 'Mensaje enviado correctamente'];
        } else {
            return ['success' => false, 'error' => 'Error al enviar el email'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()];
    }
}

// Procesar formulario
try {
    $resultado = enviarEmail($_POST);
    
    // Si es AJAX, responder JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    }
    
    // Si no es AJAX, redirigir con mensaje
    if ($resultado['success']) {
        header('Location: /contacto.php?status=success');
    } else {
        header('Location: /contacto.php?status=error&msg=' . urlencode($resultado['error']));
    }
    
} catch (Exception $e) {
    header('Location: /contacto.php?status=error&msg=' . urlencode('Error del servidor'));
}
exit;