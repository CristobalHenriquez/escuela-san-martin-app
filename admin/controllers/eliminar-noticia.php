<?php
// Controlador para eliminar noticias escolares
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
    echo json_encode(['success' => false, 'message' => 'ID de noticia inválido']);
    exit;
}

try {
    // Primero obtenemos la información de la noticia para eliminar la imagen si existe
    $stmt = $db->prepare("SELECT titulo, imagen FROM posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $noticia = $resultado->fetch_assoc();
        $titulo = $noticia['titulo']; // Guardar el título para el mensaje

        // Eliminar la imagen si existe
        if (!empty($noticia['imagen']) && file_exists('../../' . $noticia['imagen'])) {
            unlink('../../' . $noticia['imagen']);
        }

        // Eliminar la noticia de la base de datos
        $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Noticia "' . htmlspecialchars($titulo) . '" eliminada correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar la noticia: ' . $db->error
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'La noticia no existe o ya fue eliminada'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

