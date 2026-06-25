# Estructura de Directorios - Proyecto EESO 225 "La San Martín"

## Estructura Propuesta:

```
proyecto-web-escuela/
├── 📁 admin/                          # Panel de administración
│   ├── 📁 controllers/                 # Controladores PHP
│   │   ├── cargar-noticia.php         # Crear noticias
│   │   ├── editar-noticia.php         # Editar noticias
│   │   ├── eliminar-noticia.php       # Eliminar noticias
│   │   ├── cargar-personal.php        # Crear personal docente
│   │   ├── editar-personal.php        # Editar personal docente
│   │   ├── eliminar-personal.php      # Eliminar personal docente
│   │   └── cargar-logro.php           # Crear logros estudiantiles
│   ├── 📁 includes/                    # Archivos comunes
│   │   ├── head.php                   # Encabezado HTML
│   │   ├── footer.php                 # Pie de página
│   │   ├── navbar.php                 # Navegación
│   │   ├── sweetalert.php             # Alertas
│   │   └── functions.php              # Funciones auxiliares
│   ├── 📁 css/                        # Estilos del admin
│   │   └── admin.css                  # Estilos personalizados
│   ├── 📁 js/                         # JavaScript del admin
│   │   └── admin.js                   # Funciones JS
│   ├── admin.php                      # Dashboard principal
│   ├── login.php                      # Página de login
│   ├── noticias.php                   # Gestión de noticias
│   ├── personal.php                   # Gestión de personal docente
│   ├── logros.php                     # Gestión de logros estudiantiles
│   ├── config.php                     # Configuración del admin
│   └── upload.php                     # Subida de archivos
├── 📁 public/                         # Sitio web público
│   ├── index.php                      # Página principal
│   ├── noticias.php                   # Lista de noticias
│   ├── noticia.php                    # Noticia individual
│   ├── personal.php                   # Lista de personal docente
│   ├── logros.php                     # Lista de logros estudiantiles
│   └── contacto.php                   # Página de contacto
├── 📁 includes/                       # Archivos comunes del sitio
│   ├── conexion.php                   # Conexión a base de datos
│   ├── header.php                     # Encabezado público
│   ├── footer.php                     # Pie público
│   ├── navbar.php                     # Navegación pública
│   └── functions.php                  # Funciones del sitio
├── 📁 assets/                         # Recursos estáticos
│   ├── 📁 css/                        # Estilos del sitio
│   │   ├── style.css                  # Estilos principales
│   │   ├── bootstrap.min.css          # Bootstrap
│   │   └── custom.css                 # Estilos personalizados
│   ├── 📁 js/                         # JavaScript del sitio
│   │   ├── main.js                    # JavaScript principal
│   │   ├── bootstrap.min.js           # Bootstrap JS
│   │   └── custom.js                  # JavaScript personalizado
│   ├── 📁 images/                     # Imágenes del sitio
│   │   ├── 📁 logo/                   # Logos de la escuela
│   │   ├── 📁 noticias/               # Imágenes de noticias
│   │   ├── 📁 personal/               # Fotos del personal
│   │   └── 📁 logros/                 # Imágenes de logros
│   └── 📁 fonts/                      # Fuentes personalizadas
│       ├── montserrat/                # Fuente Montserrat
│       └── roboto/                    # Fuente Roboto
├── 📁 uploads/                        # Archivos subidos
│   ├── 📁 noticias/                   # Imágenes de noticias
│   ├── 📁 personal/                   # Fotos del personal
│   ├── 📁 logros/                     # Imágenes de logros
│   └── 📁 temp/                       # Archivos temporales
├── 📁 database/                       # Archivos de base de datos
│   ├── escuela_san_martin.sql          # Esquema de BD
│   └── sample_data.sql                # Datos de ejemplo
├── 📁 config/                         # Archivos de configuración
│   ├── database.php                   # Configuración de BD
│   └── config.php                     # Configuración general
├── .htaccess                          # Configuración Apache
├── README.md                          # Documentación del proyecto
└── PLAN_ADAPTACION.md                 # Plan de adaptación
```

## Archivos Clave a Crear/Copiar:

### 1. **Desde el Admin Existente:**
- `admin/includes/head.php` → Adaptar colores y tipografías
- `admin/includes/footer.php` → Mantener estructura
- `admin/includes/sweetalert.php` → Mantener funcionalidad
- `admin/config.php` → Adaptar configuración
- `admin/upload.php` → Mantener sistema de uploads

### 2. **Adaptaciones Necesarias:**
- `admin/posts.php` → `admin/noticias.php`
- `admin/staff.php` → `admin/personal.php`
- `admin/casos.php` → `admin/logros.php`
- Controladores correspondientes

### 3. **Nuevos Archivos:**
- `public/index.php` → Página principal del sitio
- `public/noticias.php` → Lista pública de noticias
- `public/personal.php` → Lista pública de personal
- `includes/conexion.php` → Conexión a BD
- `assets/css/custom.css` → Estilos con identidad visual

## Ventajas de esta Estructura:

✅ **Organización clara** - Separación admin/sitio público
✅ **Escalable** - Fácil agregar nuevos módulos
✅ **Mantenible** - Archivos organizados por función
✅ **Segura** - Admin separado del sitio público
✅ **Optimizada** - Recursos estáticos organizados

¿Te parece bien esta estructura? ¿Quieres que empecemos a crearla?
