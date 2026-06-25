-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 25-06-2026 a las 18:50:08
-- Versión del servidor: 11.8.6-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u978865485_web_escuela`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `casos_de_exito`
--

CREATE TABLE `casos_de_exito` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cargo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estrellas` decimal(2,1) NOT NULL,
  `opinion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `imagen` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logros_estudiantiles`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `logros_estudiantiles`
--

INSERT INTO `logros_estudiantiles` (`id`, `nombre_estudiante`, `curso_division`, `logro`, `descripcion`, `foto`, `fecha_logro`, `visible`, `fecha_creacion`) VALUES
(5, 'Julian Henriquez', '5 C', 'Primer puesto en campeonato nacional de informatica', 'Julián Henríquez es un talentoso estudiante que ganó el primer puesto en la Competencia Nacional de Informática 2024. Desde joven, se apasionó por la programación y, a los 16 años, ya había ganado concursos regionales. Su habilidad para resolver problemas complejos lo llevó a destacarse a nivel nacional.', 'uploads/logros/1764182273_Btn-Reconocimiento-2021.webp', '2025-11-20', 1, '2025-11-20 20:40:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_docente`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personal_docente`
--

INSERT INTO `personal_docente` (`id`, `nombre`, `apellido`, `materia`, `especialidad`, `email_institucional`, `telefono`, `biografia`, `foto`, `linkedin`, `visible`, `fecha_creacion`) VALUES
(1, 'Cristobal', 'Henriquez', 'Docente en Informatica', 'Técnico en Desarrollo de Software | Coordinador IT | DevOps', 'Cristobalhb@live.com', '3415071162', 'Soy un profesional apasionado por la optimización de procesos y la tecnología. Actualmente, me desempeño como Coordinador del Área de Desarrollo IT en la RAMCC (Red Argentina de Municipios frente al Cambio Climático), donde lidero la implementación de soluciones tecnológicas y estrategias DevOps. Mi experiencia abarca desde el desarrollo Full Stack hasta la automatización de infraestructuras con Docker, Python y PHP.\r\n\r\nMis credenciales incluyen Diplomaturas completadas en Diseño UX/UI y Análisis de Datos (otorgadas por la Universidad Tecnológica Nacional - UTN), lo que me permite aportar una visión integral en la gestión de proyectos y el ciclo de vida del software.\r\n\r\nConvencido del valor de la educación, también ejerzo como docente de Informática en la Escuela EESO N° 225 \"Gral. José de San Martín\". Para potenciar mi perfil híbrido, me encuentro cursando la Diplomatura en DevOps (con certificación de la Universidad Nacional de Córdoba) y el Ciclo de Formación Pedagógica en la Universidad FASTA, uniendo la excelencia técnica, la gestión de equipos y la vocación educativa.', 'uploads/personal/1764274293_Disenosintitulo4.webp', '', 1, '2025-11-13 00:26:13'),
(7, 'Gisela', 'Rolón', 'Vicedirectora', 'Educación Creativa y Técnicas Artesanales', '', '', 'Gisela Rolón se desempeña como Vicedirectora Reemplazante. Es Educadora en Educación Creativa y Técnicas Artesanales, habilitada para dictar clases de Educación Artística en los niveles Primario, Secundario y Terciario.\r\nCuenta con 25 años de trayectoria en la escuela, donde se destaca por su compromiso con la educación, la creatividad y el trabajo en equipo. Promueve entornos pedagógicos donde el arte, la expresión y los valores institucionales acompañan el desarrollo integral de los estudiantes.\r\nSu cercanía, dedicación y vocación la convierten en una figura altamente valorada en la comunidad educativa.', 'uploads/personal/1764274763_GiselaVice.webp', '', 1, '2025-11-27 20:19:23'),
(8, 'Marilina', 'Ermini', 'Directora', 'Profesorado de Historia', '', '', 'Marilina Ermini. Realizó sus estudios terciarios en el Profesorado de Historia en el I.E.S. “Olga Cossettini”.\r\n Se incorporó a la Escuela San Martín en\r\n2021 inicialmente como Preceptora. Posteriormente, se radicó junto a su familia en la ciudad, consolidando así su vínculo con la institución y con la comunidad educativa local.\r\nDesde 2021 ejerce el cargo de Directora, primero en carácter de reemplazante y, a partir de septiembre de 2024, como Directora interina.\r\nEn el transcurso de su trayectoria profesional también desarrolló funciones en otras instituciones educativas, entre ellas:\r\n E.E.S.O. N°574, E.E.S.O. N°605 y E.E.T.P. N°459.', 'uploads/personal/1764275680_Marilina.webp', '', 1, '2025-11-27 20:34:40'),
(9, 'Guido', 'Miori', 'Preceptor', 'Profesor de Biología', '', '', 'Profesor de Biología, habilitado para los niveles Primario, Secundario y Terciario.\r\nCon 2 años de trayectoria en el cargo, se caracteriza por su acompañamiento cercano, la promoción del respeto y el estímulo permanente al aprendizaje. Su vocación docente y disposición favorecen un ambiente de convivencia positiva y fortalecen la comunidad educativa.', 'uploads/personal/1764275911_Guido.webp', '', 1, '2025-11-27 20:38:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posts`
--

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

--
-- Volcado de datos para la tabla `posts`
--

INSERT INTO `posts` (`id`, `titulo`, `contenido`, `imagen`, `categoria`, `visible`, `orden`, `fecha_publicacion`, `created_at`, `visitas`) VALUES
(66, 'Seis municipios RAMCC lideran el relevamiento de árboles con la app CenArb', '<p>Santo Tomé, Ramona y Ricardone de la provincia de Santa Fe, junto a Dean Funes, Laboulaye y Piquillín de Córdoba, se destacan como los seis gobiernos locales que más árboles relevaron utilizando CenArb, la aplicación desarrollada por la Red Argentina de Municipios frente al Cambio Climático para la gestión del arbolado urbano.</p><p><br></p><p>Esta herramienta digital, diseñada según las necesidades de los gobiernos locales, permite relevar cada ejemplar con geolocalización precisa, registrando información sobre su especie, dimensiones y estado general. Gracias a estos datos, los municipios pueden planificar intervenciones de mantenimiento, identificar zonas con déficit de cobertura vegetal y promover estrategias de forestación más efectivas, mejorando así la calidad del entorno urbano.</p><p><br></p><p>El desarrollo de CenArb fue impulsado por la Comisión de Arbolado y Biodiversidad de la RAMCC junto con el equipo técnico de la Red, en un trabajo colaborativo que combina innovación tecnológica con la experiencia local. La aplicación permite a cada municipio construir su inventario digital de arbolado, un insumo fundamental para tomar decisiones basadas en evidencia y avanzar hacia políticas públicas que fomenten la biodiversidad, la adaptación climática y la sostenibilidad ambiental.</p><p><br></p><p>El compromiso y la participación activa de estos seis municipios demuestran que la gestión ambiental moderna requiere información precisa y herramientas tecnológicas que acompañen el crecimiento de las ciudades. Su labor refleja un paso concreto hacia modelos de gestión más inteligentes, participativos y alineados con los objetivos globales de acción climática.</p><p>La RAMCC invita a todos los municipios y comunas a sumarse al uso de CenArb. Incorporar esta solución tecnológica permite fortalecer la gestión ambiental local, optimizar la planificación urbana y conservar la biodiversidad, construyendo entre todos ciudades más sostenibles, resilientes y comprometidas con el cuidado del ambiente.</p>', 'uploads/noticias/1762979358_0-orig2.webp', 'noticia', 1, 0, '2025-11-12', '2025-11-12 20:29:19', 292),
(67, 'Por miedo a filtraciones, el Gobierno posterga reuniones y definirá en soledad las próximas reformas', '<p><span style=\"color: rgb(59, 59, 59);\">No habr&iacute;a encuentros del Consejo de Mayo hasta diciembre. &ldquo;Decidimos mantener las charlas en estricta confidencialidad&rdquo;, esgrimieron a Infobae. Hubo versiones de supuestos art&iacute;culos para la iniciativa laboral y de cambios en Educaci&oacute;n</span></p>\r\n<p><img style=\"display: block; margin-left: auto; margin-right: auto;\" src=\"https://www.infobae.com/resizer/v2/HLGIHRYFPNDOBNAFL27JVMCCQU.jpeg?auth=a5a526903f215ae29cc1964afd6b9e399d16b6ebc765f305dc64a9d2ccbed736&amp;smart=true&amp;width=350&amp;height=233&amp;quality=85\" alt=\"El Consejo de Mayo reunido.\" width=\"288\" height=\"192\"></p>\r\n<p><strong style=\"color: rgb(59, 59, 59);\">El Gobierno no volver&aacute; a reunir el Consejo de Mayo</strong><span style=\"color: rgb(59, 59, 59);\">&nbsp;por los pr&oacute;ximos d&iacute;as. El organismo creado por el presidente&nbsp;</span><a style=\"color: rgb(11, 87, 208);\" href=\"https://www.infobae.com/politica/2025/11/19/javier-milei-hablo-de-las-proximas-reformas-el-mundo-podria-llegar-a-hablar-de-crecer-a-tasas-argentinas/\" target=\"_blank\" rel=\"noopener\"><strong><em>Javier Milei</em></strong></a><span style=\"color: rgb(59, 59, 59);\">&nbsp;para debatir las&nbsp;</span><strong style=\"color: rgb(59, 59, 59);\">reformas&nbsp;</strong><span style=\"color: rgb(59, 59, 59);\">explicitadas en el&nbsp;</span><strong style=\"color: rgb(59, 59, 59);\">Pacto de Mayo</strong><span style=\"color: rgb(59, 59, 59);\">&nbsp;no tiene previsto volver a reunirse hasta los primeros d&iacute;as de diciembre, confirm&oacute; una fuente inobjetable a&nbsp;</span><strong style=\"color: rgb(59, 59, 59);\">Infobae</strong><span style=\"color: rgb(59, 59, 59);\">.</span></p>\r\n<p>El Consejo de Mayo ven&iacute;a reuni&eacute;ndose de manera mensual entre los miembros multisectoriales que hab&iacute;an sido nombrados por decreto, y semanalmente con los equipos t&eacute;cnicos del Poder Ejecutivo, de los sectores empresarios y de los sindicatos.&nbsp;<strong>Esta din&aacute;mica se pausar&aacute; hasta nuevo aviso</strong>&nbsp;debido a que en la c&uacute;pula del Gobierno&nbsp;<strong>cay&oacute; mal que trascendieran diferentes propuestas debatidas o presentadas por diferentes partes en el Consejo</strong>; algunas ciertas, otras que no forman parte de las consideraciones de los libertarios.</p>\r\n<p>&ldquo;<strong>Hay filtraciones que no hacen bien a lo que nosotros queremos hacer</strong>, que es presentar las reformas sin ruidos en la previa y propiciar que se aprueben en el Congreso&rdquo;, explic&oacute; una figura clave del Gabinete de Milei. En las &uacute;ltimas semanas, se gener&oacute; debate en la esfera medi&aacute;tica por diferentes iniciativas que hab&iacute;an surgido de las discusiones pol&iacute;ticas y t&eacute;cnicas del Consejo de Mayo. En el oficialismo indican que hay algunas de ellas que son consideradas por la Casa Rosada, pero que otras fueron directamente descartadas por el mismo Gobierno.</p>\r\n<p><img style=\"display: block; margin-left: auto; margin-right: auto;\" src=\"https://www.infobae.com/resizer/v2/5ATS2BRFRVFC5F5SEFKX7JHX4M.jpg?auth=d024aa66c1a113b83febd47076551a8b2d25b205bf07128593ef9d91b1c86d2c&amp;smart=true&amp;width=350&amp;height=197&amp;quality=85\" alt=\"Manuel Adorni presidir&aacute; el Consejo\"></p>\r\n<p>Luego de la salida de&nbsp;<strong>Guillermo Francos</strong>, el Consejo de Mayo pas&oacute; a estar presidido por el actual jefe de Gabinete de Ministros,&nbsp;<strong>Manuel Adorni</strong>. A su vez, el cuerpo est&aacute; integrado por el ministro de Desregulaci&oacute;n,&nbsp;<strong>Federico Sturzenegger</strong>, como representante del Ejecutivo; el gobernador de&nbsp;<strong>Mendoza</strong>,&nbsp;<strong>Alfredo Cornejo</strong>, por las provincias firmantes del Pacto de Mayo; la senadora nacional,&nbsp;<strong>Carolina Losada</strong>, por la C&aacute;mara Alta; el diputado nacional,&nbsp;<strong>Cristian Ritondo</strong>, por la C&aacute;mara Baja; el secretario general de la&nbsp;<strong>UOCRA</strong>,&nbsp;<strong>Gerardo Mart&iacute;nez</strong>, por los sindicatos; y el presidente de la&nbsp;<strong>UIA</strong>,&nbsp;<strong>Mart&iacute;n Rappallini</strong>, por el empresariado.</p>\r\n<p>En los &uacute;ltimos d&iacute;as, trascendi&oacute; que la pr&oacute;xima reuni&oacute;n iba a hacerse el jueves 27 de noviembre, pero&nbsp;<strong>habr&aacute; que esperar unos d&iacute;as m&aacute;s para que se materialice el encuentro</strong>. Adorni tendr&iacute;a contratiempos para poder presidirla y pasar&iacute;a la reuni&oacute;n para m&aacute;s adelante.&nbsp;<strong>&ldquo;Las reuniones pol&iacute;ticas no deber&iacute;an reanudarse hasta los primeros d&iacute;as de diciembre&rdquo;</strong>, coment&oacute; una fuente ministerial. Hasta la redacci&oacute;n de este art&iacute;culo, no hab&iacute;a mensajes referidos al cambio de fecha en el grupo de WhatsApp que tienen el presidente del organismo y sus seis consejeros.</p>\r\n<p>&nbsp;</p>\r\n<h2>La tensi&oacute;n por las filtraciones</h2>\r\n<p>Al Presidente no le gust&oacute; que trascendieran los borradores de algunas de las reformas. La que m&aacute;s fue objeto de discusi&oacute;n medi&aacute;tica en las &uacute;ltimas semanas fue la&nbsp;<strong>laboral</strong>. En la c&uacute;pula del Ejecutivo marcan que hubo iniciativas de las charlas entre los t&eacute;cnicos del Gobierno, los empresarios y el sindicalismo que trascendieron como si fueran a materializarse.</p>\r\n<p>&ldquo;<strong>El Consejo est&aacute; suspendido por todos estos asuntos. Decidimos mantener algunas reuniones en estricta confidencialidad</strong>. Ahora s&iacute; que no se van a enterar ni van a tener informaci&oacute;n de qu&eacute; vamos a hablar&rdquo;, dijo esta ma&ntilde;ana a&nbsp;<strong>Infobae</strong><em>&nbsp;</em>una figura central del Gobierno que estar&aacute; esa mesa de funcionarios que terminar&aacute;n de pulir la reforma laboral y el resto de iniciativas.</p>\r\n<p class=\"ql-align-center\"><img style=\"display: block; margin-left: auto; margin-right: auto;\" src=\"https://www.infobae.com/resizer/v2/HVVGZC6CNNCRFP47DLG7EEBX7U.jpg?auth=08fb71689f5a631d8e205e375d67e646e36cdf202313a7b06ece28d5d56d0904&amp;smart=true&amp;width=350&amp;height=197&amp;quality=85\" alt=\"El secretario de Trabajo, Julio\"></p>\r\n<p>&iquest;Qui&eacute;nes pudieron haber difundido la informaci&oacute;n de las reformas? Uno de los integrantes del Consejo acus&oacute; directamente al Gobierno por haberlo hecho. En un sector de la Casa Rosada tambi&eacute;n propician esta versi&oacute;n. &ldquo;<strong>Es que hay interna en el Gobierno y se operan entre ellos</strong>&rdquo;, dijo otro de los consejeros.&nbsp;<em>Tiempo Argentino</em>&nbsp;revel&oacute; esta ma&ntilde;ana que hay enfrentamientos concretos entre dirigentes.</p>\r\n<p>En el&nbsp;<strong>Ministerio de Capital Humano</strong>&nbsp;son enf&aacute;ticos y marcan que el borrador avanzado de la reforma laboral la tienen el secretario de Trabajo,&nbsp;<strong>Julio Cordero</strong>, y el ministro de Desregulaci&oacute;n y Transformaci&oacute;n del Estado,&nbsp;<strong>Federico Sturzenegger</strong>, por lo que cualquier tipo de iniciativa falsa o contenido tuvo que haber surgido de sectores ajenos al Gobierno. &ldquo;Cualquiera puede dar a conocer versiones. La CGT o la UIA son ejemplos&rdquo;, indica un colaborador oficial.</p>\r\n<p>Ejemplo de esta hip&oacute;tesis es lo que sucedi&oacute; hace unas semanas cuando se indic&oacute; en distintos medios que el Gobierno estaba pensando en proponer un&nbsp;<strong>tope de hasta diez indemnizaciones</strong>. Ese mismo d&iacute;a, m&uacute;ltiples fuentes del Gobierno salieron a decir que era falso.&nbsp;<strong>&ldquo;Fue propuesto por las c&aacute;maras empresariales y fuimos nosotros quienes les dijimos que no. Si lo proponemos, la reforma no va a salir. Es as&iacute; de simple&rdquo;</strong>, enfatizaron.</p>\r\n<p><strong>Hay cuestiones que s&iacute; podr&iacute;an formar parte del proyecto de ley que se presentar&aacute; el 15 de diciembre y que en la c&uacute;pula del Gobierno no gust&oacute; que se supieran</strong>. Para guardar el escepticismo y evitar que sectores opositores a la reforma puedan hacer campa&ntilde;a adelantada sobre el tema, en la Casa Rosada buscan remarcar que todo de lo que salga por estas semanas ser&aacute; falso. &ldquo;Fueron todas mentiras instaladas para tratar de ensuciar al Gobierno&rdquo;, esgrimi&oacute; ayer Milei en un reportaje con&nbsp;<em>Radio Mitre.</em></p>\r\n<p class=\"ql-align-center\"><img style=\"display: block; margin-left: auto; margin-right: auto;\" src=\"https://www.infobae.com/resizer/v2/CXZXNG5B7ZHP7EKVZ6B4GEPGJU.jpg?auth=974115d2e0f14773b99575ea1fa822b5bbc2580a6aac1dd52888dc2625d84fb9&amp;smart=true&amp;width=350&amp;height=233&amp;quality=85\" alt=\"El triunviro de la CGT\"></p>\r\n<p>Recientemente se revel&oacute; que el Gobierno est&aacute; trabajando en una&nbsp;<strong>reforma educativa</strong>, la cual tendr&iacute;a cerca de 136 art&iacute;culos y reformas de diferente tipo vinculadas a la educaci&oacute;n inicial, primaria y secundaria.&nbsp;<strong>Tres integrantes del Consejo de Mayo dijeron estar sorprendidos de que se difundiera la planificaci&oacute;n de este proyecto</strong>, el cual hab&iacute;a permanecido en completa reserva hasta hace algunos d&iacute;as. En el proyecto trabaja el secretario de Educaci&oacute;n, Carlos Torrendell.</p>\r\n<p>Al respecto, altas fuentes del Gobierno vuelven a insistir en que no van a confirmar ninguno de los puntos difundidos por los medios. &ldquo;Hay aspectos que son ciertos, pero otros que no.&nbsp;<strong>Dijeron que vamos a volar el INET</strong>&nbsp;[Instituto Nacional de Educaci&oacute;n T&eacute;cnica]<strong>&nbsp;y no es cierto</strong>&rdquo;, esgrimi&oacute; una funcionaria inobjetable.</p>\r\n<p><strong>Los borradores finales de la reforma laboral y la educativa est&aacute;n muy cerca de cerrarse</strong>. Tres fuentes oficiales de distintas &aacute;reas del Gobierno as&iacute; lo confirmaron a&nbsp;<strong>Infobae</strong>. Esto no quiere decir que lo que est&eacute; escrito all&iacute; es lo que vaya a salir. Incluso, creen que la conformaci&oacute;n del texto final sufrir&aacute; modificaciones en comisiones. &ldquo;La discusi&oacute;n va a estar en el Congreso. Por eso preferimos no comunicar nada ahora y matizar cualquier cosa que podamos llegar a proponer, porque nos puede tensionar las negociaciones posteriores&rdquo;, concluyen una de las personas que conoce la letra chica.</p>', '', 'noticia', 1, 0, '2025-11-20', '2025-11-20 19:55:03', 299);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turismo`
--

CREATE TABLE `turismo` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nombreyapellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expiration` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `nombreyapellido`, `email`, `password`, `activo`, `fecha_creacion`, `fecha_actualizacion`, `password_reset_token`, `password_reset_expiration`, `created_at`) VALUES
(5, 'Administrador', 'admin@eeso225.edu.ar', '$2y$12$tLrwiiNgLoFMVYJnzCwhHu/kflrXcfSjYdDH0vWGQXt1UWvzGDk/q', 1, '2025-11-26 20:44:20', '2025-11-26 20:44:20', NULL, NULL, '2025-11-12 20:20:14'),
(7, 'Cristobal Henriquez', 'cristobalhb@live.com', '$2y$10$1Ci/h5r76zKVdUBjsqKvkuNVM01xzSuRV6DHqinI9nKQtplngLgz6', 1, '2025-11-27 00:31:38', '2025-11-27 19:59:02', NULL, NULL, '2025-11-27 00:31:38');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `casos_de_exito`
--
ALTER TABLE `casos_de_exito`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `logros_estudiantiles`
--
ALTER TABLE `logros_estudiantiles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `personal_docente`
--
ALTER TABLE `personal_docente`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `turismo`
--
ALTER TABLE `turismo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `casos_de_exito`
--
ALTER TABLE `casos_de_exito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT de la tabla `logros_estudiantiles`
--
ALTER TABLE `logros_estudiantiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `personal_docente`
--
ALTER TABLE `personal_docente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT de la tabla `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `turismo`
--
ALTER TABLE `turismo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
