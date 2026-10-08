<?php
/**
 * Archivo de Autenticación - EESO 225 San Martín
 * Funciones para manejo de autenticación del admin
 */

// Incluir configuración
require_once __DIR__ . '/../config/config.php';

// Función para verificar autenticación
if (!function_exists('verificarAutenticacion')) {
    function verificarAutenticacion() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: login.php');
            exit;
        }
    }
}

// Función para obtener nombre del admin
if (!function_exists('obtenerNombreAdmin')) {
    function obtenerNombreAdmin() {
        if (!isset($_SESSION['usuario_id'])) {
            return 'Usuario';
        }
        
        global $db;
        $stmt = $db->prepare("SELECT nombreyapellido FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($row = $resultado->fetch_assoc()) {
            return $row['nombreyapellido'];
        }
        
        return 'Usuario';
    }
}

// Función para verificar si el usuario es admin
if (!function_exists('esAdmin')) {
    function esAdmin() {
        return isset($_SESSION['usuario_id']) && isset($_SESSION['es_admin']) && $_SESSION['es_admin'];
    }
}

// Función para requerir login de admin
if (!function_exists('requerirAdmin')) {
    function requerirAdmin() {
        if (!esAdmin()) {
            header('Location: login.php');
            exit;
        }
    }
}

// Función para obtener datos del usuario actual
if (!function_exists('obtenerUsuarioActual')) {
    function obtenerUsuarioActual() {
        if (!isset($_SESSION['usuario_id'])) {
            return null;
        }
        
        global $db;
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        return $resultado->fetch_assoc();
    }
}

// Función para cerrar sesión
if (!function_exists('cerrarSesion')) {
    function cerrarSesion() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

// Función para mostrar mensaje de error
if (!function_exists('mostrarError')) {
    function mostrarError($mensaje) {
        $_SESSION['error_message'] = $mensaje;
    }
}

// Función para mostrar mensaje de éxito
if (!function_exists('mostrarExito')) {
    function mostrarExito($mensaje) {
        $_SESSION['success_message'] = $mensaje;
    }
}

// Función para obtener mensaje y limpiarlo
if (!function_exists('obtenerMensaje')) {
    function obtenerMensaje($tipo) {
        $clave = $tipo . '_message';
        if (isset($_SESSION[$clave])) {
            $mensaje = $_SESSION[$clave];
            unset($_SESSION[$clave]);
            return $mensaje;
        }
        return null;
    }
}

// Función para redirigir con mensaje
if (!function_exists('redirigirConMensaje')) {
    function redirigirConMensaje($url, $mensaje, $tipo = 'success') {
        $_SESSION[$tipo . '_message'] = $mensaje;
        header("Location: $url");
        exit;
    }
}

// Función para log de actividad
if (!function_exists('logActividad')) {
    function logActividad($accion, $detalles = '') {
        if (!DEBUG) return;
        
        $usuario = obtenerUsuarioActual();
        $usuario_nombre = $usuario ? $usuario['nombreyapellido'] : 'Sistema';
        
        $log = date('Y-m-d H:i:s') . " - {$usuario_nombre} - {$accion}";
        if ($detalles) {
            $log .= " - {$detalles}";
        }
        
        error_log($log);
    }
}

