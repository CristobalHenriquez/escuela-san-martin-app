<?php
/**
 * Controlador para subir imágenes desde TinyMCE - EESO 225 "La San Martín"
 * Maneja la subida de imágenes insertadas en el editor de texto
 */

header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar si hay archivos
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No se recibió ningún archivo o hubo un error']);
    exit;
}

$file = $_FILES['file'];

// Configuración
$upload_dir = '../../uploads/editor/';
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$max_file_size = 5 * 1024 * 1024; // 5MB

// Crear directorio si no existe
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'No se pudo crear el directorio de uploads']);
        exit;
    }
}

// Validar tipo de archivo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG, GIF y WebP.']);
    exit;
}

// Validar tamaño
if ($file['size'] > $max_file_size) {
    echo json_encode(['success' => false, 'error' => 'El archivo es demasiado grande. Máximo 5MB.']);
    exit;
}

// Generar nombre único para el archivo
$file_extension = '';
switch ($mime_type) {
    case 'image/jpeg':
    case 'image/jpg':
        $file_extension = '.jpg';
        break;
    case 'image/png':
        $file_extension = '.png';
        break;
    case 'image/gif':
        $file_extension = '.gif';
        break;
    case 'image/webp':
        $file_extension = '.webp';
        break;
}

$filename = 'editor_' . time() . '_' . uniqid() . $file_extension;
$file_path = $upload_dir . $filename;

// Mover archivo
if (move_uploaded_file($file['tmp_name'], $file_path)) {
    // Optimizar imagen si es necesario
    optimizeImage($file_path, $mime_type);
    
    // URL relativa para el editor
    $file_url = 'uploads/editor/' . $filename;
    
    echo json_encode([
        'success' => true,
        'location' => $file_url,
        'filename' => $filename,
        'size' => filesize($file_path),
        'type' => $mime_type
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo']);
}

/**
 * Función para optimizar imágenes
 */
function optimizeImage($file_path, $mime_type) {
    $max_width = 1200;
    $max_height = 800;
    $quality = 85;
    
    // Obtener dimensiones actuales
    list($width, $height) = getimagesize($file_path);
    
    // Si la imagen es más grande que los límites, redimensionar
    if ($width > $max_width || $height > $max_height) {
        // Calcular nuevas dimensiones manteniendo proporción
        $ratio = min($max_width / $width, $max_height / $height);
        $new_width = intval($width * $ratio);
        $new_height = intval($height * $ratio);
        
        // Crear imagen según el tipo
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $source = imagecreatefromjpeg($file_path);
                $dest = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($dest, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($dest, $file_path, $quality);
                imagedestroy($source);
                imagedestroy($dest);
                break;
                
            case 'image/png':
                $source = imagecreatefrompng($file_path);
                $dest = imagecreatetruecolor($new_width, $new_height);
                
                // Preservar transparencia
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
                $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
                imagefill($dest, 0, 0, $transparent);
                
                imagecopyresampled($dest, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagepng($dest, $file_path, 8);
                imagedestroy($source);
                imagedestroy($dest);
                break;
                
            case 'image/gif':
                $source = imagecreatefromgif($file_path);
                $dest = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($dest, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagegif($dest, $file_path);
                imagedestroy($source);
                imagedestroy($dest);
                break;
        }
    }
}
?>