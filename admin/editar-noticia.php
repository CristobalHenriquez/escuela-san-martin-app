<?php
// Página para editar una noticia existente
$admin_page_title = 'Editar Noticia | EESO 225 "La San Martín"';
$admin_page_description = 'Formulario para editar una noticia existente';

require_once '../includes/conexion.php';
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
verificarAutenticacion();

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: noticias.php?error=id_invalido');
    exit;
}

$id = intval($_GET['id']);

// Obtener datos de la noticia
$stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    header('Location: noticias.php?error=no_existe');
    exit;
}

$noticia = $resultado->fetch_assoc();

// Corregir formato de fecha si es inválido
if ($noticia['fecha_publicacion'] == '0000-00-00' || empty($noticia['fecha_publicacion'])) {
    $noticia['fecha_publicacion'] = date('Y-m-d');
} else {
    $timestamp = strtotime($noticia['fecha_publicacion']);
    if ($timestamp !== false) {
        $noticia['fecha_publicacion'] = date('Y-m-d', $timestamp);
    } else {
        $noticia['fecha_publicacion'] = date('Y-m-d');
    }
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Error de seguridad: token CSRF inválido.';
    }
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = $_POST['contenido'] ?? '';
    $categoria = $_POST['categoria'] ?? 'noticia';
    $visible = !empty($_POST['visible']) ? 1 : 0;
    $orden = intval($_POST['orden'] ?? 0);
    $fecha = !empty($_POST['fecha_publicacion']) ? $_POST['fecha_publicacion'] : date('Y-m-d');
    
    // Validación básica
    if (isset($error)) {
        // No continuar con el procesamiento si falla la protección CSRF.
    } elseif (empty($titulo)) {
        $error = "El título es obligatorio";
    } elseif (empty($contenido)) {
        $error = "El contenido es obligatorio";
    } elseif (!in_array($categoria, ['noticia', 'evento', 'curso'], true)) {
        $error = "La categoría seleccionada no es válida";
    } else {
        // Mantener la imagen actual por defecto
        $imagen = $noticia['imagen'];
        
        // Procesar nueva imagen si se subió
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "../uploads/noticias";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($_FILES['imagen']['name'], PATHINFO_FILENAME));
            $webp_filename = $filename . '.webp';
            $webp_path = $upload_dir . '/' . $webp_filename;
            $imagen = "uploads/noticias/" . $webp_filename;
            
            // Validar y normalizar la imagen igual que en el alta.
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($_FILES['imagen']['tmp_name']);
            
            if ($_FILES['imagen']['size'] > 10 * 1024 * 1024) {
                $error = "La imagen no puede superar los 10 MB";
            } elseif (in_array($file_type, $allowed_types, true)) {
                if (!redimensionarImagen($_FILES['imagen']['tmp_name'], $webp_path, IMAGEN_NOTICIA_ANCHO, IMAGEN_NOTICIA_ALTO)) {
                    $error = "Error al procesar la imagen";
                }
            } else {
                $error = "Formato de imagen no válido";
            }
        }
        
        if (!isset($error)) {
            // Actualizar en la base de datos
            $stmt = $db->prepare("UPDATE posts SET titulo = ?, contenido = ?, imagen = ?, categoria = ?, visible = ?, orden = ?, fecha_publicacion = ? WHERE id = ?");
            $stmt->bind_param("ssssiisi", $titulo, $contenido, $imagen, $categoria, $visible, $orden, $fecha, $id);
            
            if ($stmt->execute()) {
                header('Location: noticias.php?success=actualizada');
                exit;
            } else {
                $error = "Error al actualizar la noticia: " . $db->error;
            }
        }
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Incluimos el encabezado del admin
include_once 'includes/head.php';
?>

<link rel="stylesheet" href="../assets/css/admin-news.css">

<!-- SweetAlert2 CSS y JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<?php if (isset($error)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: '¡Error!',
        text: '<?= addslashes($error) ?>',
        confirmButtonColor: '#dc3545'
    });
});
</script>
<?php endif; ?>

<!-- Encabezado de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2">Editar Noticia <i class="bi bi-pencil-square"></i></h1>
        <nav aria-label="breadcrumb">
            <ol class="admin-breadcrumb breadcrumb">
                <li class="breadcrumb-item"><a href="admin.php">Panel</a></li>
                <li class="breadcrumb-item"><a href="noticias.php">Noticias</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar noticia</li>
            </ol>
        </nav>
    </div>
    <a href="noticias.php" class="btn btn-admin-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver a la lista
    </a>
</div>

<!-- Formulario -->
<div class="admin-card news-editor-card">
    <div class="admin-card-body">
        <form action="editar-noticia.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" id="formEditarNoticia">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-4">
                        <label for="titulo" class="form-label fw-medium">Título <span class="text-danger">*</span></label>
                        <input type="text" name="titulo" id="titulo" class="form-control" value="<?= htmlspecialchars($noticia['titulo']) ?>" maxlength="180" required>
                    </div>

                    <div class="mb-4">
                        <label for="contenido" class="form-label fw-medium">Contenido <span class="text-danger">*</span></label>
                        <!-- Editor TinyMCE -->
                        <textarea id="contenido" name="contenido" class="tinymce-editor" required aria-describedby="contenidoAyuda"><?= htmlspecialchars($noticia['contenido']) ?></textarea>
                        <div id="contenidoAyuda" class="form-text mt-2">Podés agregar títulos, listas, enlaces e imágenes dentro del texto.</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="admin-card news-editor-panel mb-4">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title mb-0">Configuración</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Categoría</label>
                                <select name="categoria" class="form-select">
                                    <option value="noticia" <?= $noticia['categoria'] == 'noticia' ? 'selected' : '' ?>>Noticia</option>
                                    <option value="evento" <?= $noticia['categoria'] == 'evento' ? 'selected' : '' ?>>Evento</option>
                                    <option value="curso" <?= $noticia['categoria'] == 'curso' ? 'selected' : '' ?>>Curso</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Fecha de publicación</label>
                                <input type="date" name="fecha_publicacion" value="<?= $noticia['fecha_publicacion'] ?>" class="form-control">
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="visible" id="visible" <?= $noticia['visible'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="visible">Noticia visible</label>
                            </div>
                        </div>
                    </div>

                    <div class="admin-card news-editor-panel mb-4">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title mb-0">Imagen destacada</h6>
                        </div>
                        <div class="admin-card-body">
                            <?php if (!empty($noticia['imagen'])): ?>
                                <div class="mb-3 text-center">
                                    <img src="../<?= htmlspecialchars($noticia['imagen']) ?>" alt="Imagen actual" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                    <p class="form-text mt-2">Imagen actual</p>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <input type="file" name="imagen" class="form-control" id="imageInput" accept="image/jpeg,image/png,image/gif,image/webp">
                                <div class="form-text">JPG, PNG, GIF o WEBP. Máximo recomendado: 10 MB. Se convertirá a WEBP.</div>
                            </div>
                            <div id="imagePreview" class="mt-3 text-center d-none">
                                    <img src="#" alt="Vista previa de la imagen seleccionada" class="img-fluid image-preview-frame">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-top pt-4 mt-4 d-flex justify-content-end">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-admin-success" id="btnGuardar">
                        <i class="bi bi-save me-1"></i> Guardar cambios
                    </button>
                    <a href="noticias.php" class="btn btn-admin-secondary">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- TinyMCE Editor -->
<script src="../assets/vendor/tinymce/js/tinymce/tinymce.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuración simplificada de TinyMCE
    tinymce.init({
        selector: '#contenido',
        height: 400,
        menubar: false,
        branding: false,
        
        plugins: [
            'lists', 'link', 'image', 'preview', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'wordcount'
        ],
        
        toolbar: 'undo redo | bold italic underline | ' +
                'alignleft aligncenter alignright | ' +
                'bullist numlist | ' +
                'link image media table | code fullscreen',
        
        // Configuración simple de imágenes
        image_advtab: true,
        images_upload_url: 'controllers/upload-imagen.php',
        images_upload_credentials: true,
        
        file_picker_types: 'image',
        images_file_types: 'jpeg,jpg,png,gif,webp',
        
        content_style: `
            body { 
                font-family: Arial, sans-serif; 
                font-size: 14px;
                line-height: 1.6;
            }
            img { 
                max-width: 100%; 
                height: auto;
            }
        `,
        
        placeholder: 'Escribe aquí el contenido de la noticia...'
    }).catch(function(error) {
        console.error('No se pudo iniciar el editor:', error);
    });
    
    // Script para vista previa de imagen
    document.getElementById('imageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.size > 10 * 1024 * 1024) {
            e.target.value = '';
            Swal.fire({ icon: 'error', title: 'Imagen demasiado grande', text: 'Elegí una imagen de hasta 10 MB.' });
            return;
        }
        if (file) {
            const reader = new FileReader();
            const preview = document.getElementById('imagePreview');
            const previewImg = preview.querySelector('img');

            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.classList.remove('d-none');
            }

            reader.readAsDataURL(file);
        }
    });

    // Confirmación antes de enviar
    document.getElementById('formEditarNoticia').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Sincronizar contenido de TinyMCE antes de enviar
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        const contenido = document.getElementById('contenido').value.replace(/<[^>]*>/g, '').trim();
        if (!this.checkValidity() || !contenido) {
            this.classList.add('was-validated');
            Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Completá el título y el contenido de la noticia.' });
            return;
        }
        
        Swal.fire({
            title: '¿Guardar cambios?',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Guardando...', didOpen: () => { Swal.showLoading(); } });
                this.submit();
            }
        });
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>
