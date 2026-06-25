# Plan de Adaptación del Admin para EESO 225 "La San Martín"

## Módulos a Mantener y Adaptar

### 1. **Noticias** (Posts actuales)
- ✅ **Mantener tal como está**
- Cambiar categorías: "Noticia", "Evento", "Comunicado", "Curso"
- Adaptar campos específicos para contexto educativo

### 2. **Personal Docente** (Staff actual)
- ✅ **Mantener estructura base**
- Adaptar campos:
  - `nombre` → mantener
  - `cargo` → "Materia/Área" (ej: "Matemáticas", "Lengua", "Historia")
  - `descripcion` → "Biografía profesional"
  - `linkedin` → opcional (mantener)
  - Agregar: `email_institucional`, `telefono`, `especialidad`

### 3. **Módulos a Eliminar**
- ❌ `empresas` - No aplicable para escuela
- ❌ `casos_de_exito` - No necesario
- ❌ `turismo` - No aplicable

### 4. **Módulos Opcionales a Agregar**
- 📚 **Estudiantes destacados** (adaptación de casos de éxito)
- 🏆 **Logros institucionales** 
- 📅 **Calendario académico**
- 📋 **Comunicados oficiales**

## Cambios de Identidad Visual

### Colores Institucionales
```css
:root {
  --color-violeta: #6A1B9A;    /* Creatividad y juventud */
  --color-gris: #616161;       /* Seriedad y compromiso */
  --color-blanco: #FFFFFF;     /* Limpieza y claridad */
}
```

### Tipografías
- **Títulos**: Montserrat Bold
- **Textos**: Roboto

## Estructura de Base de Datos Adaptada

### Tabla `noticias` (mantener posts)
```sql
CREATE TABLE noticias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    contenido TEXT NOT NULL,
    imagen VARCHAR(255),
    categoria ENUM('noticia', 'evento', 'comunicado', 'curso') DEFAULT 'noticia',
    autor_id INT,
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    visible TINYINT(1) DEFAULT 1,
    orden INT DEFAULT 0,
    FOREIGN KEY (autor_id) REFERENCES usuarios(id)
);
```

### Tabla `personal_docente` (adaptar staff)
```sql
CREATE TABLE personal_docente (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    materia VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100),
    email_institucional VARCHAR(100),
    telefono VARCHAR(20),
    biografia TEXT,
    foto VARCHAR(255),
    linkedin VARCHAR(255),
    visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Archivos a Modificar

### 1. **Configuración y Estilos**
- `admin/includes/head.php` - Actualizar colores y tipografías
- `admin/css/admin.css` - Aplicar identidad visual
- `admin/config.php` - Ajustar configuración

### 2. **Módulos de Gestión**
- `admin/posts.php` → `admin/noticias.php`
- `admin/staff.php` → `admin/personal.php`
- Eliminar: `empresas.php`, `casos.php`, `turismo.php`

### 3. **Controladores**
- `admin/controllers/cargar-post.php` → `cargar-noticia.php`
- `admin/controllers/cargar-staff.php` → `cargar-personal.php`
- Eliminar controladores no necesarios

### 4. **Base de Datos**
- Crear script de migración
- Adaptar esquemas existentes

## Ventajas del Admin Existente

✅ **Sistema robusto y probado**
✅ **Seguridad implementada**
✅ **Interfaz moderna y responsive**
✅ **Gestión de archivos avanzada**
✅ **Editor de texto completo**
✅ **Sistema de permisos**
✅ **Paginación y filtros**

## Próximos Pasos

1. **Crear estructura de directorios** para el proyecto de escuela
2. **Copiar archivos del admin** existente
3. **Modificar configuración** y estilos
4. **Adaptar base de datos**
5. **Actualizar módulos** específicos
6. **Probar funcionalidades**
7. **Crear frontend público**

¿Te parece bien este plan de adaptación? ¿Quieres que empecemos con algún módulo específico?
