<?php
// Este archivo procesa las solicitudes AJAX para eliminar miembros del staff
require_once '../../includes/conexion.php';

// Iniciar sesión si no está iniciada
if (!isset($_SESSION)) {
    session_start();
}

// Establecer el tipo de contenido como JSON
header('Content-Type: application/json');

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que se proporcionó un ID y token CSRF
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

// Validar token CSRF
if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Error de seguridad. Token inválido.']);
    exit;
}

// Verificar que se proporcionó un ID válido
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de miembro del staff inválido']);
    exit;
}

try {
    // Primero obtenemos la información del miembro para eliminar la imagen si existe
    $stmt = $db->prepare("SELECT nombre, imagen FROM staff WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $miembro = $resultado->fetch_assoc();
        $nombre = $miembro['nombre']; // Guardar el nombre para el mensaje

        // Eliminar la imagen si existe
        if (!empty($miembro['imagen']) && file_exists('../../' . $miembro['imagen'])) {
            unlink('../../' . $miembro['imagen']);
        }

        // Eliminar el miembro del staff de la base de datos
        $stmt = $db->prepare("DELETE FROM staff WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Miembro del staff "' . htmlspecialchars($nombre) . '" eliminado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar el miembro del staff: ' . $db->error
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'El miembro del staff no existe o ya fue eliminado'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
