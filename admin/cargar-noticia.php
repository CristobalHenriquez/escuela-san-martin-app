<?php
// Página para crear una nueva noticia (vista). Envía el formulario al controlador en controllers/cargar-noticia.php
$admin_page_title = 'Crear Nueva Noticia | Escuela';
$admin_page_description = 'Formulario para crear una nueva noticia en el sitio web';

require_once '../includes/conexion.php';
require_once 'config.php';

// Asegurar directorios de uploads
if (!is_dir('../uploads')) {
  mkdir('../uploads', 0755, true);
}

if (!is_dir('../uploads/noticias')) {
  mkdir('../uploads/noticias', 0755, true);
}

if (!is_dir('../uploads/temp')) {
  mkdir('../uploads/temp', 0755, true);
}

// Iniciar sesión y generar token CSRF si hace falta
if (!isset($_SESSION)) {
  session_start();
}

if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Variables para la vista
$error = '';
$success = '';
$titulo = '';
$contenido = '';
$categoria = 'noticia';

// Incluimos el encabezado del admin
include_once 'includes/head.php';
?>

<!-- SweetAlert2 CSS y JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<!-- Encabezado de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-2">Nueva Noticia <i class="bi bi-plus-circle"></i></h1>
    <nav aria-label="breadcrumb">
      <ol class="admin-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><a href="admin.php">Panel</a></li>
        <li class="breadcrumb-item"><a href="noticias.php">Noticias</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nueva noticia</li>
      </ol>
    </nav>
  </div>
  <a href="noticias.php" class="btn btn-admin-secondary">
    <i class="bi bi-arrow-left me-1"></i> Volver a la lista
  </a>
</div>

<!-- Formulario -->
<div class="admin-card">
  <div class="admin-card-body">
    <form action="controllers/cargar-noticia.php" method="POST" enctype="multipart/form-data" id="formCrearNoticia">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <div class="row">
        <div class="col-md-8">
          <div class="mb-4">
            <label class="form-label fw-medium">Título <span class="text-danger">*</span></label>
            <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($titulo) ?>" required>
          </div>

          <div class="mb-4">
            <label class="form-label fw-medium">Contenido <span class="text-danger">*</span></label>
            <!-- Editor Quill -->
            <div id="contenido" style="height: 300px;"><?= $contenido ?></div>
            <!-- Campo oculto para enviar el contenido -->
            <textarea id="contenido_hidden" name="contenido" style="display: none;"><?= htmlspecialchars($contenido) ?></textarea>
          </div>
        </div>

        <div class="col-md-4">
          <div class="admin-card mb-4">
            <div class="admin-card-header">
              <h6 class="admin-card-title mb-0">Configuración</h6>
            </div>
            <div class="admin-card-body">
              <div class="mb-3">
                <label class="form-label fw-medium">Categoría</label>
                <select name="categoria" class="form-select">
                  <option value="noticia" <?= $categoria == 'noticia' ? 'selected' : '' ?>>Noticia</option>
                  <option value="evento" <?= $categoria == 'evento' ? 'selected' : '' ?>>Evento</option>
                  <option value="curso" <?= $categoria == 'curso' ? 'selected' : '' ?>>Curso</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label fw-medium">Fecha de publicación</label>
                <input type="date" name="fecha_publicacion" value="<?= date('Y-m-d'); ?>" class="form-control">
              </div>

              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="visible" id="visible" checked>
                <label class="form-check-label" for="visible">Noticia visible</label>
              </div>
            </div>
          </div>

          <div class="admin-card mb-4">
            <div class="admin-card-header">
              <h6 class="admin-card-title mb-0">Imagen destacada</h6>
            </div>
            <div class="admin-card-body">
              <div class="mb-3">
                <input type="file" name="imagen" class="form-control" id="imageInput" accept="image/*">
                <div class="form-text">Formatos permitidos: JPG, PNG, GIF, WEBP. Se convertirán automáticamente a WebP.</div>
              </div>
              <div id="imagePreview" class="mt-3 text-center d-none">
                <img src="#" alt="Vista previa" class="img-fluid img-thumbnail" style="max-height: 200px;">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="border-top pt-4 mt-4 d-flex justify-content-end">
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-admin-success" id="btnGuardar">
            <i class="bi bi-save me-1"></i> Guardar noticia
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
    });
});
</script>

<!-- Script para vista previa de imagen -->
<script>
  document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
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
  document.getElementById('formCrearNoticia').addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
      title: '¿Crear noticia?',
      showCancelButton: true,
      confirmButtonText: 'Sí, crear',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({ title: 'Guardando...', didOpen: () => { Swal.showLoading(); } });
        this.submit();
      }
    });
  });
</script>

<?php include_once 'includes/footer.php'; ?>
