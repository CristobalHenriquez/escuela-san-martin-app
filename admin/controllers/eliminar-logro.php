<?php
// Controlador para eliminar logros estudiantiles
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
    echo json_encode(['success' => false, 'message' => 'ID de logro estudiantil inválido']);
    exit;
}

try {
    // Primero obtenemos la información del logro estudiantil para eliminar la foto si existe
    $stmt = $db->prepare("SELECT nombre_estudiante, foto FROM logros_estudiantiles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $logro = $resultado->fetch_assoc();
        $nombre_estudiante = $logro['nombre_estudiante']; // Guardar el nombre para el mensaje

        // Eliminar la foto si existe
        if (!empty($logro['foto']) && file_exists('../../' . $logro['foto'])) {
            unlink('../../' . $logro['foto']);
        }

        // Eliminar el logro estudiantil de la base de datos
        $stmt = $db->prepare("DELETE FROM logros_estudiantiles WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Logro estudiantil de "' . htmlspecialchars($nombre_estudiante) . '" eliminado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar el logro estudiantil: ' . $db->error
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'El logro estudiantil no existe o ya fue eliminado'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

