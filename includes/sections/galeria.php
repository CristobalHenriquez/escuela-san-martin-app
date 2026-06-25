<?php
/**
 * Sección de Galería - Frontend
 * Muestra imágenes desde assets/images/galeria en una grilla responsive
 */

$galeriaDir = __DIR__ . '/../../assets/images/galeria';
$galeriaUrl = 'assets/images/galeria';
$imagenes = [];

if (is_dir($galeriaDir)) {
    $patron = $galeriaDir . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}';
    $imagenes = glob($patron, GLOB_BRACE) ?: [];
}
?>

<section id="galeria" class="galeria py-5">
    <div class="container" data-aos="fade-up">
        <div class="section-header text-center">
            <h2>Nuestros Momentos</h2>
            <p class="lead mb-4">Un recorrido visual por nuestras actividades, proyectos y espacios.</p>
            
            <div class="galeria-description">
                <div class="description-content mx-auto" style="max-width: 800px;">
                    <div class="info-box p-4 rounded-3 mb-4" style="background: rgba(106, 27, 154, 0.08); border-left: 4px solid var(--color-violeta);">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-images text-primary me-3 mt-1" style="font-size: 1.5rem; color: var(--color-violeta) !important;"></i>
                            <div>
                                <p class="mb-0" style="text-align: justify; line-height: 1.6; color: #444;">
                                    Este carrusel te presenta los momentos más destacados de nuestra vida escolar. Las imágenes se desplazan automáticamente para mostrarte actividades, proyectos y eventos que reflejan el espíritu y la identidad de la E.E.S.O. Nº 225.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($imagenes)): ?>
            <div class="no-images-container text-center p-5">
                <div class="mx-auto" style="max-width: 600px;">
                    <i class="bi bi-image fs-1 text-muted mb-4" style="color: var(--color-violeta) !important; opacity: 0.6;"></i>
                    <h5 class="mb-3" style="color: var(--color-violeta);">Próximamente: Nuestros Momentos Especiales</h5>
                    <p class="text-muted mb-4" style="line-height: 1.6;">
                        Estamos preparando un carrusel dinámico que mostrará las actividades, eventos y logros más destacados de nuestra comunidad educativa. Pronto podrás ver aquí un desfile automático de los momentos que definen la vida escolar en la E.E.S.O. Nº 225.
                    </p>
                    <div class="alert alert-info">
                        <small><i class="bi bi-info-circle me-2"></i>Para administradores: Subí archivos a <code>assets/images/galeria</code> para mostrarlos en el carrusel automático.</small>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="gallery-carousel">
                <div class="carousel-container">
                    <div class="carousel-track">
                        <?php 
                        // Duplicar imágenes para loop infinito
                        $imagenesLoop = array_merge($imagenes, $imagenes);
                        foreach ($imagenesLoop as $index => $rutaAbs):
                            $archivo = basename($rutaAbs);
                            $rutaRel = $galeriaUrl . '/' . $archivo;
                            $alt = ucwords(str_replace(['-', '_'], ' ', preg_replace('/\.[^.]+$/', '', $archivo)));
                        ?>
                            <div class="carousel-slide">
                                <div class="gallery-card">
                                    <img src="<?= htmlspecialchars($rutaRel) ?>" 
                                         alt="<?= htmlspecialchars($alt) ?>" 
                                         class="gallery-thumb">
                                    <div class="gallery-overlay">
                                        <div class="gallery-overlay-content">
                                            <span><?= htmlspecialchars($alt) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <style>
        /* Carrusel de imágenes automático */
        .gallery-carousel {
            overflow: hidden;
            width: 100%;
            margin: 0 auto;
        }

        .carousel-container {
            position: relative;
            width: 100%;
        }

        .carousel-track {
            display: flex;
            animation: scroll-horizontal 20s linear infinite;
            width: calc(200%); /* Ancho duplicado para loop infinito */
        }

        .carousel-slide {
            flex: 0 0 calc(33.333% / 2); /* 3 slides visibles, pero ancho dividido por 2 por el loop */
            padding: 0 15px;
        }

        @keyframes scroll-horizontal {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%); /* Mueve hasta la mitad (las imágenes duplicadas) */
            }
        }

        /* Pausar animación al hover */
        .gallery-carousel:hover .carousel-track {
            animation-play-state: paused;
        }

        /* Estilos de las cards */
        .gallery-card {
            position: relative;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .gallery-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(106, 27, 154, 0.15);
        }

        .gallery-thumb {
            width: 100%;
            height: 220px;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
            display: block;
        }

        .gallery-card:hover .gallery-thumb {
            transform: scale(1.05);
        }

        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(106, 27, 154, 0.9));
            padding: 20px 15px 15px;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }

        .gallery-card:hover .gallery-overlay {
            transform: translateY(0);
        }

        .gallery-overlay-content {
            text-align: center;
            color: white;
        }

        .gallery-overlay-content span {
            font-weight: 600;
            font-size: 0.9rem;
            display: block;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .carousel-slide {
                flex: 0 0 calc(50% / 2); /* 2 slides visibles en tablets */
            }
            
            .gallery-thumb {
                height: 180px;
            }

            .carousel-track {
                animation-duration: 15s; /* Más rápido en móviles */
            }
        }

        @media (max-width: 576px) {
            .carousel-slide {
                flex: 0 0 calc(100% / 2); /* 1 slide visible en móviles */
            }
            
            .gallery-thumb {
                height: 160px;
            }

            .carousel-track {
                animation-duration: 12s; /* Más rápido en móviles */
            }
        }

        /* Indicador visual de que es un carrusel */
        .gallery-carousel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 30px;
            height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0.8), transparent);
            z-index: 10;
            pointer-events: none;
        }

        .gallery-carousel::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 30px;
            height: 100%;
            background: linear-gradient(to left, rgba(255,255,255,0.8), transparent);
            z-index: 10;
            pointer-events: none;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Carrusel automático de imágenes - no necesita JavaScript adicional
            // La animación CSS se encarga del desplazamiento continuo
            console.log('Carrusel de imágenes inicializado');
        });
    </script>
</section>
