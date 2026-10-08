<?php
/**
 * Archivo de Conexión - EESO 225 San Martín
 * Archivo principal de conexión para el admin existente
 */

// Incluir configuración
require_once __DIR__ . '/../config/database.php';

// Crear conexión global para compatibilidad con admin existente
$db = obtenerConexion();

// Función para obtener conexión PDO (para funciones que requieren PDO)
function conectarDB_PDO() {
    try {
        // Usar las mismas constantes de database.php
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $opciones);
    } catch (PDOException $e) {
        error_log("Error de conexión PDO: " . $e->getMessage());
        return null;
    }
}

// Función para obtener nombre del admin (compatible con admin existente)
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

// Función para verificar autenticación
function verificarAutenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Función para cerrar sesión
function cerrarSesion() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Función para mostrar mensaje de error
function mostrarError($mensaje) {
    $_SESSION['error_message'] = $mensaje;
}

// Función para mostrar mensaje de éxito
function mostrarExito($mensaje) {
    $_SESSION['success_message'] = $mensaje;
}

// Función para obtener mensaje y limpiarlo
function obtenerMensaje($tipo) {
    $clave = $tipo . '_message';
    if (isset($_SESSION[$clave])) {
        $mensaje = $_SESSION[$clave];
        unset($_SESSION[$clave]);
        return $mensaje;
    }
    return null;
}

// Función para redirigir con mensaje
function redirigirConMensaje($url, $mensaje, $tipo = 'success') {
    $_SESSION[$tipo . '_message'] = $mensaje;
    header("Location: $url");
    exit;
}

// Función para formatear fecha para mostrar
function formatearFechaMostrar($fecha) {
    if (empty($fecha)) return '';
    
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}

// Función para formatear fecha para base de datos
function formatearFechaBD($fecha) {
    if (empty($fecha)) return null;
    
    $timestamp = strtotime($fecha);
    return date('Y-m-d', $timestamp);
}

// Función para obtener estadísticas del dashboard
function obtenerEstadisticas() {
    global $db;
    
    $estadisticas = [];
    
    // Contar noticias
    $resultado = $db->query("SELECT COUNT(*) as total FROM posts");
    $estadisticas['noticias'] = $resultado->fetch_assoc()['total'];
    
    // Contar personal docente
    $resultado = $db->query("SELECT COUNT(*) as total FROM personal_docente");
    $estadisticas['personal'] = $resultado->fetch_assoc()['total'];
    
    // Contar logros estudiantiles
    $resultado = $db->query("SELECT COUNT(*) as total FROM logros_estudiantiles");
    $estadisticas['logros'] = $resultado->fetch_assoc()['total'];
    
    return $estadisticas;
}

// Función para obtener noticias recientes
function obtenerNoticiasRecientes($limite = 5) {
    global $db;
    
    $stmt = $db->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    return $resultado->fetch_all(MYSQLI_ASSOC);
}

// Función para obtener personal reciente
function obtenerPersonalReciente($limite = 5) {
    global $db;
    
    $stmt = $db->prepare("SELECT * FROM personal_docente ORDER BY fecha_creacion DESC LIMIT ?");
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    return $resultado->fetch_all(MYSQLI_ASSOC);
}

// Función para limpiar archivos temporales
function limpiarArchivosTemporales() {
    $temp_dir = __DIR__ . '/../uploads/temp/';
    if (is_dir($temp_dir)) {
        $archivos = glob($temp_dir . '*');
        $ahora = time();
        
        foreach ($archivos as $archivo) {
            if (is_file($archivo) && ($ahora - filemtime($archivo)) > 3600) { // 1 hora
                unlink($archivo);
            }
        }
    }
}

// Limpiar archivos temporales al cargar
limpiarArchivosTemporales();
