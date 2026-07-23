# Proyecto Web EESO 225 "La San Martín"

## Descripción del Proyecto

Sistema web desarrollado en PHP nativo para la gestión de contenido de la escuela EESO 225 "La San Martín". El proyecto incluye un CRM para la publicación de noticias y gestión del personal docente, trabajado en conjunto con los estudiantes de la institución.

## Características Principales

- **Gestión de Noticias**: Sistema completo para crear, editar y publicar noticias
- **Directorio de Personal**: Gestión de información del personal docente
- **Identidad Visual**: Diseño basado en los colores institucionales
- **Responsive**: Adaptable a diferentes dispositivos
- **Seguridad**: Sistema de autenticación para administradores

## Tecnologías Utilizadas

- **Backend**: PHP 8.0+
- **Base de Datos**: MySQL (phpMyAdmin)
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework CSS**: Bootstrap 5
- **Servidor**: Apache/Nginx

## Colores Institucionales

- **Violeta**: #6A1B9A (Creatividad y juventud)
- **Gris**: #616161 (Seriedad y compromiso)
- **Blanco**: #FFFFFF (Limpieza y claridad)

## Tipografías

- **Títulos**: Montserrat Bold
- **Textos**: Roboto

## Estructura del Proyecto

```
proyecto-web-escuela/
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── admin.css
│   ├── js/
│   │   ├── main.js
│   │   └── admin.js
│   ├── images/
│   │   ├── logo/
│   │   ├── noticias/
│   │   └── personal/
│   └── fonts/
├── config/
│   ├── database.php
│   └── config.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── navbar.php
│   └── functions.php
├── admin/
│   ├── index.php
│   ├── login.php
│   ├── noticias/
│   │   ├── index.php
│   │   ├── crear.php
│   │   ├── editar.php
│   │   └── eliminar.php
│   └── personal/
│       ├── index.php
│       ├── crear.php
│       ├── editar.php
│       └── eliminar.php
├── public/
│   ├── index.php
│   ├── noticias.php
│   ├── personal.php
│   └── contacto.php
├── database/
│   ├── schema.sql
│   └── sample_data.sql
├── uploads/
│   ├── noticias/
│   └── personal/
└── .htaccess
```

## ✅ Estado del Proyecto - COMPLETADO

### ✅ Fase 1: Análisis y Planificación (COMPLETADA)
- [x] Analizar admin existente
- [x] Revisar estructura de base de datos
- [x] Definir adaptaciones necesarias
- [x] Crear plan de trabajo detallado

### ✅ Fase 2: Configuración Inicial (COMPLETADA)
- [x] Crear estructura de directorios
- [x] Adaptar base de datos para escuela
- [x] Copiar archivos del admin existente
- [x] Configurar archivos de conexión
- [x] Importar base de datos en phpMyAdmin

### ✅ Fase 3: Adaptación del Admin (COMPLETADA)
- [x] Actualizar identidad visual (colores institucionales)
- [x] Modificar módulos específicos:
  - [x] Posts → Noticias escolares
  - [x] Staff → Personal docente
  - [x] Casos de éxito → Logros estudiantiles
- [x] Eliminar módulos no necesarios (empresas, turismo)
- [x] Actualizar controladores y vistas

### ✅ Fase 4: Identidad Visual (COMPLETADA)
- [x] Aplicar colores institucionales (#6A1B9A, #616161, #FFFFFF)
- [x] Implementar tipografías (Montserrat Bold, Roboto)
- [x] Actualizar logos y branding
- [x] Crear estilos CSS personalizados

### 🚀 Fase 5: Frontend Público (PRÓXIMA)
- [ ] Diseñar página principal
- [ ] Crear página de noticias
- [ ] Crear página de personal docente
- [ ] Implementar diseño responsive
- [ ] Integrar con el admin

### 🔧 Fase 6: Testing y Optimización (FINAL)
- [ ] Pruebas de funcionalidad
- [ ] Optimización de rendimiento
- [ ] Corrección de errores
- [ ] Documentación final
- [ ] Capacitación del personal

## 🚀 Instalación y Configuración

### 🐳 Opción recomendada: levantar con Docker

Esta opción no depende de MAMP, XAMPP ni de un repositorio GitHub anterior. Sirve para que cada alumno copie el proyecto, lo levante localmente y trabaje con el mismo entorno.

#### Requisitos
- Docker Desktop instalado y abierto

#### Comandos
```bash
docker compose up -d --build
```

#### URLs locales
- Sitio web: http://localhost:8081
- Panel admin: http://localhost:8081/admin/login.php
- Kiosco (POS escolar): http://localhost:8081/Kiosco/login.php
- MySQL local: `localhost:3308`

phpMyAdmin es opcional y se levanta solo cuando hace falta:
```bash
docker compose --profile tools up -d phpmyadmin
```

URL: http://localhost:8082

#### Base de datos local
Docker crea automáticamente:
- Base principal: `escuela_san_martin`
- Usuario principal: `escuela`
- Contraseña principal: `escuela`
- Base para Kiosco: `kiosco`
- Usuario para Kiosco: `kiosco`
- Contraseña para Kiosco: `kiosco`

Los dumps `u978865485_web_escuela.sql` y `u978865485_kiosco2.sql` se importan automáticamente la primera vez que se crea el volumen de MySQL.

Si necesitás reiniciar la base desde cero:
```bash
docker compose down -v
docker compose up -d --build
```

#### Crear o resetear usuario administrador
Desde la carpeta del proyecto:
```bash
docker compose exec app php scripts/crear-usuario.php
```

También podés usar los scripts de `scripts/` para revisar usuarios o resetear contraseñas dentro del contenedor.

#### Credenciales locales iniciales
- Admin escuela: `admin@eeso225.edu.ar` / `admin123`
- Admin Kiosco: `admin@escuela.com` / `admin123`

Estas credenciales son solo para desarrollo local. Cambiarlas antes de usar el sistema con datos reales.

#### Estado de GitHub
Esta copia local no está asociada a ningún repositorio GitHub si no existe la carpeta `.git`. Para confirmar:
```bash
git remote -v
```

Si en otra copia aparece un remoto viejo y querés desvincularlo:
```bash
git remote remove origin
```

### 📋 Requisitos Previos
- **Servidor web**: Apache con mod_rewrite habilitado
- **PHP**: Versión 7.4 o superior
- **MySQL**: Versión 5.7 o superior
- **Extensiones PHP**: PDO, GD, mbstring, openssl

### 🔧 Pasos de Instalación

#### 1. **Configurar Base de Datos**
```sql
-- Crear base de datos
CREATE DATABASE escuela_san_martin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Importar estructura
-- Usar el archivo: database/escuela_san_martin.sql
```

#### 2. **Configurar Conexión**
Editar `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'escuela_san_martin');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

#### 3. **Configurar Servidor Web**
- Copiar archivos al directorio web
- Configurar Apache para usar `.htaccess`
- Verificar permisos de escritura en `uploads/`

#### 4. **Crear Usuario Administrador (recomendado: CLI local)**
```bash
# Ejecutar desde la carpeta del proyecto (CLI):
# php scripts/crear-usuario-admin.php
# El script pedirá nombre, email y contraseña (puede generar una contraseña aleatoria).
```

#### 5. **Acceder al Panel de Administración**
```bash
# URL: http://tu-dominio/admin/login.php
# Usar las credenciales creadas en el paso anterior
```

-### ⚠️ Importante
- Después de crear el usuario con `scripts/crear-usuario-admin.php`, elimine o mueva el script fuera del servidor.
- **Cambiar** la contraseña creada después del primer login
- Evite dejar scripts de creación en el docroot en entornos accesibles por web.
- **Configurar** backup automático de la base de datos

## 🎯 Características del Sistema

### ✅ Panel de Administración Completo
- **Dashboard** con estadísticas en tiempo real
- **Gestión de noticias** escolares con categorías
- **Gestión de personal docente** con fotos y biografías
- **Gestión de logros estudiantiles** con fechas
- **Sistema de autenticación** seguro
- **Interfaz responsive** para móviles

### 🎨 Identidad Visual Institucional
- **Colores oficiales**: Violeta (#6A1B9A), Gris (#616161), Blanco (#FFFFFF)
- **Tipografías**: Montserrat Bold (títulos), Roboto (texto)
- **Logo institucional** integrado
- **Diseño moderno** y profesional

### 🔒 Seguridad Implementada
- **Tokens CSRF** en todos los formularios
- **Validación** de archivos subidos
- **Log de actividad** del sistema
- **Sesiones** con timeout automático
- **Contraseñas** encriptadas

### 📱 Funcionalidades Técnicas
- **Redimensionamiento automático** de imágenes
- **Conversión a WebP** para optimización
- **Paginación** inteligente
- **Filtros** avanzados de búsqueda
- **Modales** para crear/editar
- **Confirmaciones** de eliminación

## 📖 Guía de Uso

### 🔐 Acceso al Sistema
1. Ir a `admin/login.php`
2. Usar las credenciales del administrador
3. Acceder al dashboard principal

### 📰 Gestión de Noticias
1. **Crear noticia**: Botón "Nueva Noticia"
2. **Categorías**: Noticia, Evento, Curso
3. **Imágenes**: Se redimensionan automáticamente
4. **Visibilidad**: Controlar si aparece en el sitio público

### 👥 Gestión de Personal Docente
1. **Crear personal**: Botón "Nuevo Personal Docente"
2. **Campos obligatorios**: Nombre, apellido, materia, biografía, foto
3. **Campos opcionales**: Especialidad, email, teléfono, LinkedIn
4. **Fotos**: Se convierten a formato cuadrado optimizado

### 🏆 Gestión de Logros Estudiantiles
1. **Crear logro**: Botón "Nuevo Logro Estudiantil"
2. **Información**: Estudiante, curso/división, tipo de logro
3. **Descripción**: Detalles del logro o reconocimiento
4. **Fecha**: Opcional, para ordenamiento cronológico

### 🔧 Mantenimiento
- **Backup**: Realizar copias de seguridad regulares
- **Actualizaciones**: Mantener PHP y MySQL actualizados
- **Logs**: Revisar logs de error periódicamente
- **Usuarios**: Gestionar accesos del personal

## Configuración de Base de Datos

### Base de Datos Adaptada para EESO 225 "La San Martín"

La base de datos ha sido adaptada desde el sistema existente, manteniendo las mejores prácticas y optimizaciones.

### Tablas Principales:

#### 1. **posts** (Noticias Escolares)
```sql
CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `categoria` enum('noticia','evento','curso') NOT NULL,
  `visible` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `fecha_publicacion` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visitas` int(11) DEFAULT 0
);
```

#### 2. **personal_docente** (Personal Docente)
```sql
CREATE TABLE `personal_docente` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `materia` varchar(100) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `email_institucional` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `biografia` text NOT NULL,
  `foto` varchar(255) NOT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
);
```

#### 3. **logros_estudiantiles** (Logros Estudiantiles)
```sql
CREATE TABLE `logros_estudiantiles` (
  `id` int(11) NOT NULL,
  `nombre_estudiante` varchar(100) NOT NULL,
  `curso_division` varchar(50) NOT NULL,
  `logro` varchar(200) NOT NULL,
  `descripcion` text NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `fecha_logro` date DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
);
```

#### 4. **users** (Sistema de Autenticación)
```sql
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nombreyapellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expiration` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);
```

### Archivo SQL Completo:
El archivo completo está disponible en: `database/escuela_san_martin.sql`

## 🚀 CI/CD y Deploy

El pipeline vive en `.github/workflows/deploy.yml`:

- **`validate`**: corre automáticamente en cada push/PR a `main` (lint PHP, `composer install` de Kiosco, build de la imagen Docker). Es la validación antes de mergear.
- **`deploy`**: nunca se dispara solo. Se ejecuta a mano desde GitHub → pestaña **Actions → Deploy → Run workflow** (rama `main`). Antes de sincronizar, hace un backup remoto de producción (subido también como *artifact* del run) y recién después sube los archivos por `rsync` — la base de datos de producción nunca se toca.

## 🔙 Rollback de producción

Si un deploy rompe algo, se puede restaurar el backup previo (tomado automáticamente antes de cada deploy) desde GitHub: pestaña **Actions → Rollback → Run workflow**. Dejar el campo `backup_file` vacío restaura el backup más reciente; si se necesita uno específico, revisar los artifacts de deploys anteriores (`pre-deploy-backup-<sha>`) o listar `~/backups/` en el servidor por SSH.

## Contribuidores

- Equipo de desarrollo de EESO 225 "La San Martín"
- Estudiantes participantes del proyecto

## Licencia

Este proyecto es desarrollado para uso educativo de la EESO 225 "La San Martín".

## Contacto

Para más información sobre el proyecto, contactar con la administración de la escuela.
