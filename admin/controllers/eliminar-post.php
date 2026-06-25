<?php
// Este archivo procesa las solicitudes AJAX para eliminar posts
require_once '../../includes/conexion.php';

// Asegurarse de que no haya salida antes de los headers
ob_start();

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
    echo json_encode(['success' => false, 'message' => 'ID de publicación inválido']);
    exit;
}

// Función recursiva para eliminar un directorio y todo su contenido
function eliminarDirectorio($dir)
{
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        $path = $dir . '/' . $file;

        if (is_dir($path)) {
            eliminarDirectorio($path);
        } else {
            unlink($path);
        }
    }

    return rmdir($dir);
}

try {
    // Primero obtenemos la información de la publicación para eliminar la imagen si existe
    $stmt = $db->prepare("SELECT imagen, contenido, titulo FROM posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $post = $resultado->fetch_assoc();
        $titulo = $post['titulo']; // Guardar el título para el mensaje

        // 1. Eliminar la imagen principal si existe
        if (!empty($post['imagen']) && file_exists('../../' . $post['imagen'])) {
            unlink('../../' . $post['imagen']);
        }

        // 2. Eliminar el directorio completo de imágenes del post
        $directorio_post = "../../uploads/posts/{$id}";
        if (is_dir($directorio_post)) {
            // Registrar la eliminación
            error_log("Eliminando directorio de imágenes del post {$id}: {$directorio_post}");

            // Eliminar el directorio y todo su contenido
            eliminarDirectorio($directorio_post);
        }

        // 3. Eliminar la publicación de la base de datos
        $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Publicación "' . htmlspecialchars($titulo) . '" eliminada correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar la publicación: ' . $db->error
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'La publicación no existe o ya fue eliminada'
        ]);
    }
} catch (Exception $e) {
    // Capturar cualquier excepción y devolverla como JSON
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Limpiar cualquier salida en buffer
ob_end_flush();
