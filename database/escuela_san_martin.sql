-- Base de datos adaptada para EESO 225 "La San Martín"
-- Basada en la estructura existente

-- ==============================================
-- TABLAS QUE MANTENEMOS SIN CAMBIOS
-- ==============================================

-- 1. POSTS (Noticias Escolares) - MANTENER TAL COMO ESTÁ
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. USERS (Sistema de Autenticación) - MANTENER TAL COMO ESTÁ
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nombreyapellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expiration` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==============================================
-- TABLAS QUE ADAPTAMOS
-- ==============================================

-- 3. STAFF → PERSONAL_DOCENTE (Adaptar campos)
CREATE TABLE `personal_docente` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `materia` varchar(100) NOT NULL,  -- Antes era 'cargo'
  `especialidad` varchar(100) DEFAULT NULL,  -- Nueva
  `email_institucional` varchar(100) DEFAULT NULL,  -- Nueva
  `telefono` varchar(20) DEFAULT NULL,  -- Nueva
  `biografia` text NOT NULL,  -- Antes era 'descripcion'
  `foto` varchar(255) NOT NULL,  -- Antes era 'imagen'
  `linkedin` varchar(255) DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. CASOS_DE_EXITO → LOGROS_ESTUDIANTILES (Adaptar para contexto educativo)
CREATE TABLE `logros_estudiantiles` (
  `id` int(11) NOT NULL,
  `nombre_estudiante` varchar(100) NOT NULL,  -- Antes era 'nombre'
  `curso_division` varchar(50) NOT NULL,  -- Antes era 'cargo'
  `logro` varchar(200) NOT NULL,  -- Nueva: tipo de logro
  `descripcion` text NOT NULL,  -- Antes era 'opinion'
  `foto` varchar(255) DEFAULT NULL,  -- Antes era 'imagen'
  `fecha_logro` date DEFAULT NULL,  -- Nueva
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==============================================
-- TABLAS OPCIONALES PARA EL FUTURO
-- ==============================================

-- 5. CALENDARIO_ACADEMICO (Nueva tabla opcional)
CREATE TABLE `calendario_academico` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `tipo_evento` enum('examen','feriado','actividad','reunion') NOT NULL,
  `visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. COMUNICADOS_OFICIALES (Nueva tabla opcional)
CREATE TABLE `comunicados_oficiales` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` text NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `prioridad` enum('alta','media','baja') DEFAULT 'media',
  `archivo_adjunto` varchar(255) DEFAULT NULL,
  `visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==============================================
-- ÍNDICES Y AUTO_INCREMENT
-- ==============================================

-- Índices para posts
ALTER TABLE `posts` ADD PRIMARY KEY (`id`);

-- Índices para users
ALTER TABLE `users` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `email` (`email`);

-- Índices para personal_docente
ALTER TABLE `personal_docente` ADD PRIMARY KEY (`id`);

-- Índices para logros_estudiantiles
ALTER TABLE `logros_estudiantiles` ADD PRIMARY KEY (`id`);

-- Índices para calendario_academico
ALTER TABLE `calendario_academico` ADD PRIMARY KEY (`id`);

-- Índices para comunicados_oficiales
ALTER TABLE `comunicados_oficiales` ADD PRIMARY KEY (`id`);

-- AUTO_INCREMENT
ALTER TABLE `posts` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `personal_docente` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `logros_estudiantiles` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `calendario_academico` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `comunicados_oficiales` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
