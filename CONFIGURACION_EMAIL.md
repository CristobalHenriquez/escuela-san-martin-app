# Configuración del Sistema de Contacto

## 📧 Sistema de Emails Implementado

El sistema de contacto ya está funcionando y tiene las siguientes características:

### ✅ Funcionalidades Implementadas:

1. **Página de contacto independiente** (`contacto.php`)
2. **Navegación desde el navbar** corregida
3. **Formulario con envío AJAX** (sin recargar página)
4. **Validación de campos** obligatorios
5. **Email HTML formateado** con datos del remitente
6. **Respuesta inmediata** al usuario
7. **Botón "Volver al inicio"** en la página de contacto

### 🔧 Para Activar el Envío de Emails:

Editar el archivo `config/email.php` y configurar:

```php
// Cambiar por el email donde querés recibir las consultas
define('EMAIL_DESTINO', 'tu-email@escuela.edu.ar');

// Para Gmail/Google Workspace:
define('SMTP_USER', 'tu-email@gmail.com');
define('SMTP_PASS', 'contraseña-de-aplicacion');
```

### 📋 Configuración Gmail/Google:

1. **Activar verificación en 2 pasos** en tu cuenta Google
2. **Crear contraseña de aplicación**:
   - Ir a Configuración de Google → Seguridad
   - Contraseñas de aplicaciones
   - Crear nueva contraseña para "Correo"
3. **Usar esa contraseña** en `SMTP_PASS`

### 🚀 Estado Actual:

- ✅ Navbar → "Contacto" funciona
- ✅ Formulario envía emails (con configuración)
- ✅ Validación y mensajes de estado
- ✅ Diseño responsive y accesible
- ✅ Integrado con el diseño institucional

El sistema usa `mail()` de PHP por defecto, que funciona en la mayoría de servidores web.

## 📱 Navegación Mejorada:

- ✅ Todos los enlaces del navbar funcionan
- ✅ Botones "Volver al inicio" en todas las páginas internas
- ✅ Breadcrumbs y navegación consistente