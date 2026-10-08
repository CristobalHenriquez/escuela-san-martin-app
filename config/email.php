<?php
/**
 * Configuración de Email para Formulario de Contacto
 * EESO 225 San Martín
 * 
 * INSTRUCCIONES PARA CONFIGURAR EL EMAIL:
 * 
 * 1. Cambiar EMAIL_DESTINO por el email donde querés recibir las consultas
 * 2. Para Gmail/Google Workspace:
 *    - Activar verificación en 2 pasos
 *    - Crear contraseña de aplicación específica
 *    - Usar esa contraseña en SMTP_PASS
 * 
 * 3. Para otros proveedores, cambiar SMTP_HOST y SMTP_PORT
 */

// Email donde llegan las consultas del formulario
define('EMAIL_DESTINO', 'contacto@eeso225.edu.ar');

// Email que aparece como remitente
define('EMAIL_REMITENTE', 'noreply@eeso225.edu.ar');

// Configuración SMTP (opcional - para envío más confiable)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', ''); // Tu email de Gmail/Google Workspace
define('SMTP_PASS', ''); // Contraseña de aplicación

// Configuración adicional
define('EMAIL_ASUNTO_PREFIJO', '[Web EESO 225] ');

// Activar/desactivar envío de email de confirmación al usuario
define('ENVIAR_CONFIRMACION', true);

?>