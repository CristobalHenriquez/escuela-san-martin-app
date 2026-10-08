<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: noticias.php');
    exit;
}

$pdo = conectarDB();
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND visible = 1");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: noticias.php');
    exit;
}

// Incrementar contador de visitas
$stmt = $pdo->prepare("UPDATE posts SET visitas = visitas + 1 WHERE id = ?");
$stmt->execute([$id]);

$page_title = htmlspecialchars($post['titulo']) . ' - ' . ESCUELA_NOMBRE_CORTO;
require_once __DIR__ . '/header.php';

// Función para obtener el tiempo de lectura estimado
function tiempoLectura($contenido) {
    $palabras = str_word_count(strip_tags($contenido));
    $minutos = ceil($palabras / 200); // Promedio 200 palabras por minuto
    return $minutos;
}

// Función para obtener noticias relacionadas
function noticiasRelacionadas($pdo, $categoriaActual, $idActual) {
    $stmt = $pdo->prepare("SELECT id, titulo, imagen, fecha_publicacion FROM posts WHERE categoria = ? AND id != ? AND visible = 1 ORDER BY fecha_publicacion DESC LIMIT 3");
    $stmt->execute([$categoriaActual, $idActual]);
    return $stmt->fetchAll();
}

$tiempoLectura = tiempoLectura($post['contenido']);
$relacionadas = noticiasRelacionadas($pdo, $post['categoria'], $post['id']);
?>

<!-- Estilos personalizados para la noticia -->
<style>
.noticia-header {
    background: #fff;
    padding: 2.25rem 0 1rem 0;
    border-bottom: 1px solid #e9ecef;
}

.breadcrumb {
    background: transparent !important;
    padding: 0 !important;
    margin-bottom: 1rem !important;
}

.breadcrumb-item a {
    color: var(--color-violeta) !important;
    text-decoration: none;
    font-weight: 500;
}

.breadcrumb-item a:hover {
    color: var(--color-violeta-claro) !important;
}

.breadcrumb-item.active {
    color: #6c757d !important;
}

.noticia-categoria {
    background: linear-gradient(45deg, var(--color-violeta), var(--color-violeta-claro));
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 1.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.noticia-titulo {
    font-size: 2.4rem !important;
    font-weight: 700 !important;
    line-height: 1.2 !important;
    color: #2b2b2b !important;
    margin: 0 0 1.5rem 0 !important;
}

.noticia-meta {
    background: #f8f9fb;
    border-radius: 10px;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
    border-left: 4px solid var(--color-violeta);
}

.noticia-meta-item {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
    color: #495057;
    font-weight: 500;
}

.noticia-meta i {
    margin-right: 0.5rem;
    color: var(--color-violeta);
    font-size: 1rem;
}

.separador-meta {
    margin: 0 0.75rem;
    color: #6c757d;
}

.noticia-contenido {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #333;
    margin-top: 2rem;
}

.noticia-contenido p {
    margin-bottom: 1.5rem;
}

.noticia-contenido h1, 
.noticia-contenido h2, 
.noticia-contenido h3 {
    color: var(--color-violeta);
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.noticia-imagen-principal {
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin: 2rem 0;
    width: 100%;
    height: auto;
}

.noticias-relacionadas {
    background: linear-gradient(135deg, #f8f9fb 0%, #e9ecef 100%);
    border-radius: 20px;
    padding: 4rem 2rem 3rem;
    margin-top: 4rem;
    position: relative;
    overflow: hidden;
}

.noticias-relacionadas::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--color-violeta), var(--color-violeta-claro), #28a745);
}

.section-title {
    color: var(--color-violeta);
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.section-subtitle {
    font-size: 1.1rem;
    margin-bottom: 0;
}

.related-news-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
}

.related-news-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(106, 27, 154, 0.15);
}

.related-news-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.related-news-link:hover {
    text-decoration: none;
    color: inherit;
}

.related-card-header {
    position: relative;
    overflow: hidden;
    height: 220px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    cursor: pointer;
}

.related-card-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.related-news-card:hover .related-card-img {
    transform: scale(1.08);
}

.related-card-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f1f3f4, #e8eaed);
    position: relative;
}

.placeholder-content {
    text-align: center;
    color: #6c757d;
}

.placeholder-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 0.5rem;
}

.placeholder-text {
    font-size: 0.9rem;
    font-weight: 500;
}

.related-card-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    background: rgba(106, 27, 154, 0.9);
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.related-news-card:hover .related-card-overlay {
    transform: translate(-50%, -50%) scale(1);
}

.related-card-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    height: calc(100% - 220px);
}

.related-card-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.85rem;
}

.related-date {
    color: #6c757d;
    font-weight: 500;
}

.related-category {
    background: linear-gradient(45deg, var(--color-violeta), var(--color-violeta-claro));
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.related-card-title {
    margin-bottom: 1rem;
    flex-grow: 1;
    line-height: 1.4;
}

.related-link {
    color: #2c3e50;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
    transition: color 0.3s ease;
}

.related-link:hover {
    color: var(--color-violeta);
    text-decoration: none;
}

.related-card-footer {
    margin-top: auto;
}

.btn-read-more {
    background: linear-gradient(45deg, var(--color-violeta), var(--color-violeta-claro));
    color: white;
    padding: 0.6rem 1.2rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.btn-read-more::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0.3));
    transition: left 0.3s ease;
}

.btn-read-more:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(106, 27, 154, 0.3);
    color: white;
    text-decoration: none;
}

.btn-read-more:hover::before {
    left: 100%;
}

.btn-read-more i {
    transition: transform 0.3s ease;
}

.btn-read-more:hover i {
    transform: translateX(3px);
}

/* Animaciones AOS personalizadas */
[data-aos="fade-up"] {
    transform: translate3d(0, 40px, 0);
    opacity: 0;
}

[data-aos="fade-up"].aos-animate {
    transform: translate3d(0, 0, 0);
    opacity: 1;
}

/* Responsive Design */
@media (max-width: 992px) {
    .related-card-header {
        height: 200px;
    }
    
    .section-title {
        font-size: 1.75rem;
    }
    
    .noticias-relacionadas {
        padding: 3rem 1.5rem 2.5rem;
    }
}

@media (max-width: 768px) {
    .related-card-header {
        height: 180px;
    }
    
    .related-card-meta {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
    
    .section-title {
        font-size: 1.5rem;
    }
    
    .section-subtitle {
        font-size: 1rem;
    }
    
    .noticias-relacionadas {
        padding: 2.5rem 1rem 2rem;
        margin-top: 3rem;
    }
    
    .related-card-body {
        padding: 1.25rem;
    }
}

.noticia-relacionada {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    border: 1px solid #e9ecef;
}

.noticia-relacionada:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    text-decoration: none;
}

.botones-accion {
    background-color: #f8f9fb;
    border-radius: 12px;
    padding: 1.5rem;
    margin: 2rem 0;
    border-left: 4px solid var(--color-violeta);
}

.btn-compartir {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.botones-navegacion {
    background: white;
    border-top: 1px solid #e9ecef;
    padding: 1.5rem 0;
    margin-top: 2rem;
}

/* Botones institucionales */
.btn-violeta {
    background: var(--color-violeta);
    border: none;
    color: #fff;
}
.btn-violeta:hover { background: var(--color-violeta-claro); color: #fff; }

.btn-outline-violeta {
    color: var(--color-violeta);
    border: 2px solid var(--color-violeta);
    background: transparent;
}
.btn-outline-violeta:hover {
    background: var(--color-violeta);
    color: #fff;
}

.rel-thumb { height: 180px; object-fit: cover; }

@media (max-width: 768px) {
    .noticia-header {
        padding: 1.5rem 0 1rem 0;
    }
    
    .noticia-titulo {
        font-size: 1.8rem !important;
    }
    
    .noticia-contenido {
        font-size: 1rem;
    }
    
    .noticia-meta {
        padding: 1rem;
    }
    
    .noticia-meta-item {
        font-size: 0.875rem;
    }
    
    .separador-meta {
        display: none;
    }
    
    .meta-mobile-break {
        width: 100%;
        margin: 0.5rem 0;
    }
}
</style>

<link rel="stylesheet" href="assets/css/noticia.css">

<!-- Header de la noticia -->
<section class="noticia-header">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php">
                                <i class="bi bi-house-door"></i> Inicio
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="noticias.php">Noticias</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?= ucfirst($post['categoria']) ?>
                        </li>
                    </ol>
                </nav>
                
                <!-- Botón volver al inicio -->
                <div class="mb-3">
                    <a href="index.php" class="btn btn-outline-violeta">
                        <i class="bi bi-arrow-left me-2"></i>Volver al inicio
                    </a>
                </div>

                <!-- Imagen principal -->
                <?php if (!empty($post['imagen'])): ?>
                    <img src="<?= htmlspecialchars($post['imagen']) ?>"
                         alt="<?= htmlspecialchars($post['titulo']) ?>"
                         class="img-fluid noticia-imagen-principal w-100">
                <?php endif; ?>

                <!-- Categoría -->
                <span class="noticia-categoria"><?= ucfirst($post['categoria']) ?></span>

                <!-- Título -->
                <h1 class="noticia-titulo"><?= htmlspecialchars($post['titulo']) ?></h1>
                
                <!-- Descripción institucional breve -->
                <div class="noticia-institucional mb-3">
                    <p class="text-muted" style="font-style: italic; font-size: 0.95rem;">
                        <i class="bi bi-building me-1" style="color: var(--color-violeta);"></i>
                        E.E.S.O. Nº 225 General José de San Martín - Compartiendo nuestras novedades con la comunidad educativa
                    </p>
                </div>
                
                <!-- Meta información -->
                <div class="noticia-meta">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex flex-wrap align-items-center">
                                <div class="noticia-meta-item">
                                    <i class="bi bi-calendar3"></i>
                                    <span><?= htmlspecialchars(date('d \d\e F \d\e Y', strtotime($post['fecha_publicacion'] ?? $post['created_at'] ?? date('Y-m-d')))) ?></span>
                                </div>
                                <span class="separador-meta">•</span>
                                <div class="meta-mobile-break d-md-none"></div>
                                <div class="noticia-meta-item">
                                    <i class="bi bi-clock"></i>
                                    <span><?= $tiempoLectura ?> min de lectura</span>
                                </div>
                                <span class="separador-meta">•</span>
                                <div class="noticia-meta-item">
                                    <i class="bi bi-eye"></i>
                                    <span><?= $post['visitas'] + 1 ?> visitas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contenido principal -->
<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <article>
                <!-- Contenido -->
                <div class="noticia-contenido">
                    <?= $post['contenido'] ?>
                </div>

                <!-- Botones de acción -->
                <div class="botones-accion">
                    <h5 class="mb-3"><i class="bi bi-share"></i> Compartir esta noticia</h5>
                    <div class="d-flex flex-wrap">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>" 
                           target="_blank" class="btn btn-outline-violeta btn-compartir" rel="noopener">
                            <i class="bi bi-facebook"></i> Facebook
                        </a>
                        <a href="https://wa.me/?text=<?= urlencode($post['titulo'] . " - http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>" 
                           target="_blank" class="btn btn-success btn-compartir" rel="noopener">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                        <button class="btn btn-secondary btn-compartir" onclick="copiarURL()">
                            <i class="bi bi-clipboard"></i> Copiar enlace
                        </button>
                    </div>
                </div>

                <!-- Navegación entre noticias -->
                <div class="botones-navegacion">
                    <div class="d-flex justify-content-between">
                        <a href="noticias.php" class="btn btn-outline-violeta">
                            <i class="bi bi-arrow-left"></i> Volver a noticias
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-house-door"></i> Ir al inicio
                        </a>
                    </div>
                </div>
            </article>
        </div>
    </div>

    <!-- Noticias relacionadas -->
    <?php if (!empty($relacionadas)): ?>
    <section class="noticias-relacionadas">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="text-center mb-5">
                        <h3 class="section-title">
                            <i class="bi bi-collection text-primary me-2"></i>
                            Noticias relacionadas
                        </h3>
                        <p class="text-muted section-subtitle">Descubre más contenido que te puede interesar</p>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach ($relacionadas as $index => $relacionada): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                    <article class="related-news-card h-100">
                        <a href="noticia.php?id=<?= $relacionada['id'] ?>" class="related-news-link">
                            <div class="related-card-header">
                                <?php if (!empty($relacionada['imagen'])): ?>
                                    <img src="<?= htmlspecialchars($relacionada['imagen']) ?>" 
                                         class="related-card-img" 
                                         alt="<?= htmlspecialchars($relacionada['titulo']) ?>"
                                         loading="lazy"
                                    >
                                    <div class="related-card-overlay">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="related-card-placeholder">
                                        <div class="placeholder-content">
                                            <i class="bi bi-newspaper placeholder-icon"></i>
                                            <span class="placeholder-text">Imagen no disponible</span>
                                        </div>
                                        <div class="related-card-overlay">
                                            <i class="bi bi-arrow-right-circle"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="related-card-body">
                            <div class="related-card-meta">
                                <span class="related-date">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d M Y', strtotime($relacionada['fecha_publicacion'])) ?>
                                </span>
                                <span class="related-category">
                                    <i class="bi bi-tag me-1"></i>
                                    Noticia
                                </span>
                            </div>
                            <h5 class="related-card-title">
                                <a href="noticia.php?id=<?= $relacionada['id'] ?>" class="related-link">
                                    <?= htmlspecialchars($relacionada['titulo']) ?>
                                </a>
                            </h5>
                            <div class="related-card-footer">
                                <a href="noticia.php?id=<?= $relacionada['id'] ?>" class="btn-read-more">
                                    Leer artículo
                                    <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<script>
function copiarURL() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        // Mostrar mensaje de éxito
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> ¡Copiado!';
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-success');
        
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-secondary');
        }, 2000);
    });
}

// Mejorar la experiencia de lectura
document.addEventListener('DOMContentLoaded', function() {
    // Agregar animación suave al scroll
    const enlaces = document.querySelectorAll('a[href^="#"]');
    enlaces.forEach(enlace => {
        enlace.addEventListener('click', function(e) {
            e.preventDefault();
            const destino = document.querySelector(this.getAttribute('href'));
            if (destino) {
                destino.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Agregar efecto de zoom a las imágenes del contenido
    const imagenes = document.querySelectorAll('.noticia-contenido img');
    imagenes.forEach(img => {
        img.style.cursor = 'pointer';
        img.addEventListener('click', function() {
            // Crear modal simple para zoom
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                cursor: pointer;
            `;
            
            const imgZoom = document.createElement('img');
            imgZoom.src = this.src;
            imgZoom.style.cssText = 'max-width: 90%; max-height: 90%; border-radius: 8px;';
            
            modal.appendChild(imgZoom);
            document.body.appendChild(modal);
            
            modal.addEventListener('click', function() {
                document.body.removeChild(modal);
            });
        });
    });
});
</script>

<!-- AOS Library para animaciones -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar AOS solo si estamos en la sección de noticias relacionadas
    if (document.querySelector('.noticias-relacionadas')) {
        AOS.init({
            duration: 600,
            easing: 'ease-in-out',
            once: true,
            offset: 50
        });
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
