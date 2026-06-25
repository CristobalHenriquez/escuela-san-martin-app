-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 25-06-2026 a las 19:44:08
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
-- Base de datos: `u978865485_kiosco2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `balance_diario`
--

CREATE TABLE `balance_diario` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `capital_inicial_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `capital_inicial_transferencia` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ventas_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ventas_transferencia` decimal(10,2) NOT NULL DEFAULT 0.00,
  `compras_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `compras_transferencia` decimal(10,2) NOT NULL DEFAULT 0.00,
  `capital_final_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `capital_final_transferencia` decimal(10,2) NOT NULL DEFAULT 0.00,
  `capital_productos` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `balance_diario`
--

INSERT INTO `balance_diario` (`id`, `fecha`, `capital_inicial_efectivo`, `capital_inicial_transferencia`, `ventas_efectivo`, `ventas_transferencia`, `compras_efectivo`, `compras_transferencia`, `capital_final_efectivo`, `capital_final_transferencia`, `capital_productos`) VALUES
(1, '2026-05-28', 0.00, 0.00, 7000.00, 0.00, 0.00, 0.00, 7000.00, 0.00, 253305.00),
(2, '2026-05-29', 7000.00, 0.00, 25100.00, 21700.00, 0.00, 0.00, 32100.00, 21700.00, 227925.00),
(3, '2026-06-03', 32100.00, 21700.00, 13400.00, 0.00, 0.00, 0.00, 40500.00, 21700.00, 215657.00),
(4, '2026-06-04', 40500.00, 21700.00, 0.00, 0.00, 0.00, 0.00, 64200.00, 40000.00, 198676.00),
(5, '2026-06-05', 64200.00, 40000.00, 20600.00, 13100.00, 0.00, 0.00, 89800.00, 53100.00, 183855.00),
(6, '2026-06-08', 89800.00, 53100.00, 21000.00, 29000.00, 0.00, 10820.00, 110800.00, 71280.00, 242355.00),
(7, '2026-06-10', 110800.00, 71280.00, 34100.00, 40800.00, 0.00, 0.00, 144900.00, 112080.00, 215550.00),
(8, '2026-06-11', 144900.00, 112080.00, 2000.00, 0.00, 0.00, 0.00, 146900.00, 112080.00, 214536.00),
(9, '2026-06-12', 146900.00, 112080.00, 19600.00, 15200.00, 0.00, 0.00, 166500.00, 127280.00, 193186.00),
(10, '2026-06-17', 166500.00, 127280.00, 16900.00, 26900.00, 0.00, 0.00, 183400.00, 154180.00, 156677.00),
(11, '2026-06-24', 183400.00, 154180.00, 33200.00, 20600.00, 110000.00, 0.00, 106600.00, 174780.00, 207757.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capital_liquido`
--

CREATE TABLE `capital_liquido` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `transferencia` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descripcion` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cuenta_id` int(11) DEFAULT NULL,
  `comprobante_path` varchar(255) DEFAULT NULL,
  `comprobante_tipo` enum('webp','pdf') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `capital_liquido`
--

INSERT INTO `capital_liquido` (`id`, `fecha`, `tipo`, `efectivo`, `transferencia`, `descripcion`, `usuario_id`, `created_at`, `cuenta_id`, `comprobante_path`, `comprobante_tipo`) VALUES
(1, '2026-05-27', 'ingreso', 0.00, 177083.00, 'transferencias acumuladas', 1, '2026-05-27 18:35:52', 1, NULL, NULL),
(2, '2026-05-27', 'ingreso', 38600.00, 0.00, 'efectivo acumulado', 1, '2026-05-27 18:36:13', NULL, NULL, NULL),
(3, '2026-05-28', 'egreso', 10000.00, 0.00, 'Apertura de turno #1 - Grupo 2', NULL, '2026-05-28 17:38:53', NULL, NULL, NULL),
(4, '2026-05-28', 'ingreso', 17000.00, 0.00, 'Cierre de turno #1 - Grupo 2', NULL, '2026-05-28 17:41:09', NULL, NULL, NULL),
(5, '2026-05-29', 'egreso', 10000.00, 0.00, 'Apertura de turno #2 - Grupo 2', NULL, '2026-05-29 17:47:20', NULL, NULL, NULL),
(6, '2026-05-29', 'ingreso', 32100.00, 0.00, 'Cierre de turno #2 - Grupo 2', NULL, '2026-05-29 17:54:55', NULL, NULL, NULL),
(7, '2026-05-29', 'ingreso', 0.00, 9900.00, 'Ventas transferencia turno #2 - MP Grupo 1', NULL, '2026-05-29 17:54:55', 1, NULL, NULL),
(8, '2026-05-29', 'egreso', 10000.00, 0.00, 'Apertura de turno #3 - Grupo 2', NULL, '2026-05-29 19:16:49', NULL, NULL, NULL),
(9, '2026-05-29', 'ingreso', 13000.00, 0.00, 'Cierre de turno #3 - Grupo 2', NULL, '2026-05-29 19:23:54', NULL, NULL, NULL),
(10, '2026-05-29', 'ingreso', 0.00, 8800.00, 'Ventas transferencia turno #3 - MP Grupo 1', NULL, '2026-05-29 19:23:54', 1, NULL, NULL),
(11, '2026-05-29', 'egreso', 10000.00, 0.00, 'Apertura de turno #4 - Grupo 2', NULL, '2026-05-29 19:59:31', NULL, NULL, NULL),
(12, '2026-05-29', 'ingreso', 10000.00, 0.00, 'Cierre de turno #4 - Grupo 2', NULL, '2026-05-29 20:00:12', NULL, NULL, NULL),
(13, '2026-05-29', 'ingreso', 0.00, 3000.00, 'Ventas transferencia turno #4 - MP Grupo 1', NULL, '2026-05-29 20:00:12', 1, NULL, NULL),
(14, '2026-05-29', 'ingreso', 7100.00, 0.00, 'Cuadrar caja', 1, '2026-05-29 20:38:28', NULL, NULL, NULL),
(15, '2026-05-29', 'egreso', 14200.00, 0.00, 'Cuadrar caja', 1, '2026-05-29 20:39:08', NULL, NULL, NULL),
(16, '2026-05-29', 'ingreso', 0.00, 13224.00, 'Cuadrar caja', 1, '2026-05-29 20:40:39', 1, NULL, NULL),
(17, '2026-05-29', 'egreso', 0.00, 3072.00, 'Cuadrar caja', 1, '2026-05-29 20:45:43', 1, NULL, NULL),
(18, '2026-05-29', 'ingreso', 7100.00, 0.00, 'Cuadrar caja', 1, '2026-05-29 20:46:46', NULL, NULL, NULL),
(19, '2026-06-03', 'egreso', 5000.00, 0.00, 'Apertura de turno #5 - Grupo 1', 10, '2026-06-03 17:42:34', NULL, NULL, NULL),
(20, '2026-06-03', 'ingreso', 13400.00, 0.00, 'Cierre de turno #5 - Grupo 1', 10, '2026-06-03 17:53:05', NULL, NULL, NULL),
(21, '2026-06-03', 'egreso', 5000.00, 0.00, 'Apertura de turno #6 - Grupo 1', 10, '2026-06-03 20:42:48', NULL, NULL, NULL),
(22, '2026-06-04', 'ingreso', 23700.00, 0.00, 'Cierre de turno #6 - Grupo 1', 1, '2026-06-04 17:39:49', NULL, NULL, NULL),
(23, '2026-06-04', 'ingreso', 0.00, 18300.00, 'Ventas transferencia turno #6 - MP Grupo 1', 1, '2026-06-04 17:39:49', 1, NULL, NULL),
(24, '2026-06-04', 'egreso', 5000.00, 0.00, 'Apertura de turno #7 - Grupo 1', 10, '2026-06-04 18:07:43', NULL, NULL, NULL),
(25, '2026-06-05', 'ingreso', 5000.00, 0.00, 'Cierre de turno #7 - Grupo 1', NULL, '2026-06-05 17:48:47', NULL, NULL, NULL),
(26, '2026-06-05', 'egreso', 500.00, 0.00, 'Apertura de turno #8 - Grupo 1', 1, '2026-06-05 19:31:28', NULL, NULL, NULL),
(27, '2026-06-05', 'ingreso', 21100.00, 0.00, 'Cierre de turno #8 - Grupo 1', 1, '2026-06-05 19:36:14', NULL, NULL, NULL),
(28, '2026-06-05', 'ingreso', 0.00, 13100.00, 'Ventas transferencia turno #8 - MP Grupo 1', 1, '2026-06-05 19:36:14', 1, NULL, NULL),
(29, '2026-06-05', 'egreso', 500.00, 0.00, 'Apertura de turno #9 - Grupo 1', 10, '2026-06-05 20:13:04', NULL, NULL, NULL),
(30, '2026-06-05', 'ingreso', 500.00, 0.00, 'Cierre de turno #9 - Grupo 1', 10, '2026-06-05 20:16:19', NULL, NULL, NULL),
(31, '2026-06-05', 'ingreso', 9100.00, 0.00, 'efectivo acumulado', 1, '2026-06-05 20:32:31', NULL, NULL, NULL),
(32, '2026-06-05', 'ingreso', 0.00, 7658.00, 'transferencias acumuladas', 1, '2026-06-05 20:32:54', 1, NULL, NULL),
(33, '2026-06-06', 'egreso', 90000.00, 0.00, 'Compra: Mercaderia', 10, '2026-06-06 14:29:45', NULL, 'uploads/comprobantes/2026/06/comp_d5a2bc0ab3aa7ca5.webp', 'webp'),
(34, '2026-06-06', 'egreso', 0.00, 21695.00, 'Compra: Mercaderia', 10, '2026-06-06 14:36:44', 1, 'uploads/comprobantes/2026/06/comp_1a288cecb135fc9c.webp', 'webp'),
(35, '2026-06-06', 'egreso', 0.00, 21695.00, 'Compra: Mercaderia', 10, '2026-06-06 14:36:49', 1, 'uploads/comprobantes/2026/06/comp_803e3fb258d20658.webp', 'webp'),
(36, '2026-06-08', 'egreso', 0.00, 10820.00, 'Compra: Bolsas', 10, '2026-06-08 16:15:50', 1, 'uploads/comprobantes/2026/06/comp_b1331bec80440a09.webp', 'webp'),
(37, '2026-06-08', 'egreso', 10000.00, 0.00, 'Apertura de turno #10 - Grupo 1', 10, '2026-06-08 21:45:36', NULL, NULL, NULL),
(38, '2026-06-08', 'ingreso', 31000.00, 0.00, 'Cierre de turno #10 - Grupo 1', 10, '2026-06-08 21:59:02', NULL, NULL, NULL),
(39, '2026-06-08', 'ingreso', 0.00, 29000.00, 'Ventas transferencia turno #10 - MP Grupo 1', 10, '2026-06-08 21:59:02', 1, NULL, NULL),
(40, '2026-06-10', 'egreso', 10000.00, 0.00, 'Apertura de turno #11 - Grupo 1', 10, '2026-06-10 17:50:35', NULL, NULL, NULL),
(41, '2026-06-10', 'ingreso', 32200.00, 0.00, 'Cierre de turno #11 - Grupo 1', 10, '2026-06-10 17:56:25', NULL, NULL, NULL),
(42, '2026-06-10', 'ingreso', 0.00, 16200.00, 'Ventas transferencia turno #11 - MP Grupo 1', 10, '2026-06-10 17:56:25', 1, NULL, NULL),
(43, '2026-06-10', 'egreso', 1000.00, 0.00, 'Apertura de turno #12 - Grupo 1', 10, '2026-06-10 18:28:50', NULL, NULL, NULL),
(44, '2026-06-10', 'ingreso', 12900.00, 0.00, 'Cierre de turno #12 - Grupo 1', 16, '2026-06-10 19:46:41', NULL, NULL, NULL),
(45, '2026-06-10', 'ingreso', 0.00, 24600.00, 'Ventas transferencia turno #12 - MP Grupo 1', 16, '2026-06-10 19:46:41', 1, NULL, NULL),
(46, '2026-06-11', 'egreso', 5000.00, 0.00, 'Apertura de turno #13 - Grupo 1', 16, '2026-06-11 17:36:59', NULL, NULL, NULL),
(47, '2026-06-11', 'ingreso', 7000.00, 0.00, 'Cierre de turno #13 - Grupo 1', 16, '2026-06-11 17:37:26', NULL, NULL, NULL),
(48, '2026-06-12', 'egreso', 10000.00, 0.00, 'Apertura de turno #14 - Grupo 1', 18, '2026-06-12 17:19:18', NULL, NULL, NULL),
(49, '2026-06-12', 'ingreso', 23300.00, 0.00, 'Cierre de turno #14 - Grupo 1', 16, '2026-06-12 17:52:37', NULL, NULL, NULL),
(50, '2026-06-12', 'ingreso', 0.00, 3000.00, 'Ventas transferencia turno #14 - MP Grupo 1', 16, '2026-06-12 17:52:37', 1, NULL, NULL),
(51, '2026-06-12', 'egreso', 5000.00, 0.00, 'Apertura de turno #15 - Grupo 1', 16, '2026-06-12 18:13:17', NULL, NULL, NULL),
(52, '2026-06-12', 'ingreso', 5000.00, 0.00, 'Cierre de turno #15 - Grupo 1', 16, '2026-06-12 18:15:14', NULL, NULL, NULL),
(53, '2026-06-12', 'ingreso', 0.00, 5000.00, 'Ventas transferencia turno #15 - MP Grupo 1', 16, '2026-06-12 18:15:14', 1, NULL, NULL),
(54, '2026-06-12', 'egreso', 10000.00, 0.00, 'Apertura de turno #16 - Grupo 1', 18, '2026-06-12 18:53:37', NULL, NULL, NULL),
(55, '2026-06-12', 'ingreso', 16300.00, 0.00, 'Cierre de turno #16 - Grupo 1', 16, '2026-06-12 19:17:05', NULL, NULL, NULL),
(56, '2026-06-12', 'ingreso', 0.00, 5400.00, 'Ventas transferencia turno #16 - MP Grupo 1', 16, '2026-06-12 19:17:05', 1, NULL, NULL),
(57, '2026-06-12', 'egreso', 5000.00, 0.00, 'Apertura de turno #17 - Grupo 1', 15, '2026-06-12 19:31:18', NULL, NULL, NULL),
(58, '2026-06-12', 'ingreso', 5000.00, 0.00, 'Cierre de turno #17 - Grupo 1', 15, '2026-06-12 19:32:04', NULL, NULL, NULL),
(59, '2026-06-12', 'ingreso', 0.00, 1800.00, 'Ventas transferencia turno #17 - MP Grupo 1', 15, '2026-06-12 19:32:04', 1, NULL, NULL),
(60, '2026-06-12', 'ingreso', 3597.00, 0.00, 'transferencias acumuladas', 1, '2026-06-12 20:41:17', NULL, NULL, NULL),
(61, '2026-06-12', 'egreso', 3597.00, 0.00, 'Cuadrar caja', 1, '2026-06-12 20:43:49', NULL, 'uploads/comprobantes/2026/06/comp_9bbb8dd3d5a632ff.webp', 'webp'),
(62, '2026-06-12', 'ingreso', 0.00, 3597.00, 'transferencias acumuladas', 1, '2026-06-12 20:44:17', 1, NULL, NULL),
(63, '2026-06-17', 'egreso', 10000.00, 0.00, 'Apertura de turno #18 - Grupo 1', 16, '2026-06-17 17:20:02', NULL, NULL, NULL),
(64, '2026-06-17', 'egreso', 10000.00, 0.00, 'Apertura de turno #19 - Grupo 1', 18, '2026-06-17 17:20:24', NULL, NULL, NULL),
(65, '2026-06-17', 'egreso', 10000.00, 0.00, 'Apertura de turno #20 - Grupo 1', 18, '2026-06-17 17:20:25', NULL, NULL, NULL),
(66, '2026-06-17', 'ingreso', 23000.00, 0.00, 'Cierre de turno #20 - Grupo 1', 16, '2026-06-17 17:50:23', NULL, NULL, NULL),
(67, '2026-06-17', 'ingreso', 0.00, 12500.00, 'Ventas transferencia turno #20 - MP Grupo 1', 16, '2026-06-17 17:50:23', 1, NULL, NULL),
(68, '2026-06-17', 'ingreso', 10000.00, 0.00, 'Cierre de turno #19 - Grupo 1', 16, '2026-06-17 17:50:48', NULL, NULL, NULL),
(69, '2026-06-17', 'ingreso', 10000.00, 0.00, 'Cierre de turno #18 - Grupo 1', 16, '2026-06-17 17:52:24', NULL, NULL, NULL),
(70, '2026-06-17', 'egreso', 10000.00, 0.00, 'Apertura de turno #21 - Grupo 1', 16, '2026-06-17 18:48:33', NULL, NULL, NULL),
(71, '2026-06-17', 'ingreso', 13900.00, 0.00, 'Cierre de turno #21 - Grupo 1', 16, '2026-06-17 19:16:34', NULL, NULL, NULL),
(72, '2026-06-17', 'ingreso', 0.00, 14400.00, 'Ventas transferencia turno #21 - MP Grupo 1', 16, '2026-06-17 19:16:34', 1, NULL, NULL),
(73, '2026-06-24', 'egreso', 110000.00, 0.00, 'Compra: Mercaderia', 15, '2026-06-24 13:57:00', NULL, 'uploads/comprobantes/2026/06/comp_73ec0d588ae41bd8.webp', 'webp'),
(74, '2026-06-24', 'egreso', 10000.00, 0.00, 'Apertura de turno #22 - Grupo 1', 18, '2026-06-24 17:18:20', NULL, NULL, NULL),
(75, '2026-06-24', 'ingreso', 27900.00, 0.00, 'Cierre de turno #22 - Grupo 1', 16, '2026-06-24 18:31:35', NULL, NULL, NULL),
(76, '2026-06-24', 'ingreso', 0.00, 19100.00, 'Ventas transferencia turno #22 - MP Grupo 1', 16, '2026-06-24 18:31:35', 1, NULL, NULL),
(77, '2026-06-24', 'egreso', 10000.00, 0.00, 'Apertura de turno #23 - Grupo 1', 13, '2026-06-24 18:51:37', NULL, NULL, NULL),
(78, '2026-06-24', 'ingreso', 25300.00, 0.00, 'Cierre de turno #23 - Grupo 1', 16, '2026-06-24 19:55:11', NULL, NULL, NULL),
(79, '2026-06-24', 'ingreso', 0.00, 1500.00, 'Ventas transferencia turno #23 - MP Grupo 1', 16, '2026-06-24 19:55:11', 1, NULL, NULL),
(80, '2026-06-24', 'egreso', 0.00, 19202.00, 'Cuadrar caja', 1, '2026-06-25 00:23:43', 1, 'uploads/comprobantes/2026/06/comp_164feee7a34734ef.webp', 'webp');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras_mercaderia`
--

CREATE TABLE `compras_mercaderia` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(20) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cuenta_id` int(11) DEFAULT NULL,
  `comprobante_path` varchar(255) DEFAULT NULL,
  `comprobante_tipo` enum('webp','pdf') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `compras_mercaderia`
--

INSERT INTO `compras_mercaderia` (`id`, `fecha`, `monto`, `metodo_pago`, `descripcion`, `usuario_id`, `created_at`, `cuenta_id`, `comprobante_path`, `comprobante_tipo`) VALUES
(1, '2026-06-06', 90000.00, 'efectivo', 'Mercaderia', 10, '2026-06-06 14:29:45', NULL, 'uploads/comprobantes/2026/06/comp_d5a2bc0ab3aa7ca5.webp', 'webp'),
(2, '2026-06-06', 21695.00, 'transferencia', 'Mercaderia', 10, '2026-06-06 14:36:44', 1, 'uploads/comprobantes/2026/06/comp_1a288cecb135fc9c.webp', 'webp'),
(3, '2026-06-06', 21695.00, 'transferencia', 'Mercaderia', 10, '2026-06-06 14:36:49', 1, 'uploads/comprobantes/2026/06/comp_803e3fb258d20658.webp', 'webp'),
(4, '2026-06-08', 10820.00, 'transferencia', 'Bolsas', 10, '2026-06-08 16:15:50', 1, 'uploads/comprobantes/2026/06/comp_b1331bec80440a09.webp', 'webp'),
(5, '2026-06-24', 110000.00, 'efectivo', 'Mercaderia', 15, '2026-06-24 13:57:00', NULL, 'uploads/comprobantes/2026/06/comp_73ec0d588ae41bd8.webp', 'webp');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_pago`
--

CREATE TABLE `cuentas_pago` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('transferencia') NOT NULL DEFAULT 'transferencia',
  `detalle` varchar(255) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cuentas_pago`
--

INSERT INTO `cuentas_pago` (`id`, `nombre`, `tipo`, `detalle`, `orden`, `activo`, `created_at`) VALUES
(1, 'MP Grupo 1', 'transferencia', 'Cuenta Mercado Pago Grupo 1', 1, 1, '2026-03-13 02:21:43'),
(2, 'MP Grupo 2', 'transferencia', 'Cuenta Mercado Pago Grupo 2', 2, 1, '2026-03-13 02:21:43'),
(3, 'MP Grupo 3', 'transferencia', 'Cuenta Mercado Pago Grupo 3', 3, 1, '2026-03-13 02:21:43'),
(4, 'MP Grupo 4', 'transferencia', 'Cuenta Mercado Pago Grupo 4', 4, 1, '2026-03-13 02:21:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id`, `nombre`, `activo`, `created_at`) VALUES
(1, 'Grupo 1', 1, '2026-03-13 02:21:43'),
(2, 'Grupo 2', 1, '2026-03-13 02:21:43'),
(3, 'Grupo 3', 1, '2026-03-13 02:21:43'),
(4, 'Grupo 4', 1, '2026-03-13 02:21:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `nombre` varchar(200) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `costo` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `ref`, `nombre`, `precio`, `costo`, `stock`, `created_at`) VALUES
(4, '', 'Oreos', 2200.00, 1360.00, 0, '2026-03-13 17:27:22'),
(10, '', 'Baggio', 800.00, 300.00, 8, '2026-03-13 17:27:22'),
(17, '', 'Guaymallen', 500.00, 230.00, 0, '2026-03-13 17:27:22'),
(20, '', 'don satur', 2000.00, 1050.00, 10, '2026-03-13 17:27:22'),
(21, '', 'fantoche', 1500.00, 640.00, 15, '2026-03-13 17:27:22'),
(25, '', 'turron', 500.00, 136.00, 20, '2026-03-13 17:27:22'),
(28, 'Pitusas 160gr y 140grs galea', 'Pitusas 160gr y 140grs galea', 1800.00, 850.00, 15, '2026-03-25 18:28:10'),
(30, 'Suavecitas', 'Suavecitas', 1500.00, 759.00, 9, '2026-03-25 18:32:46'),
(31, 'Opera', 'Opera', 800.00, 475.00, 0, '2026-03-25 18:38:21'),
(32, '100grs Palitos', '100grs Palitos', 1000.00, 496.00, 19, '2026-03-25 18:49:55'),
(33, 'Barra Cereal Georgealos', 'Barra Cereal Georgealos', 1300.00, 589.00, 12, '2026-03-25 19:46:57'),
(34, 'Cubanito Oblea', 'Cubanito Oblea', 200.00, 65.00, 54, '2026-03-25 19:50:09'),
(36, 'Baggio 200cc', 'Baggio 200cc', 1000.00, 420.00, 7, '2026-03-25 19:53:07'),
(37, 'Baggio Choco', 'Baggio Choco', 1000.00, 467.00, 3, '2026-03-25 19:55:08'),
(38, 'Chizitos Bolsa', 'Chizitos Bolsa', 1000.00, 420.00, 5, '2026-03-25 19:58:48'),
(40, 'Gomitas Acidas', 'Gomitas Acidas', 100.00, 35.00, 23, '2026-03-27 20:24:54'),
(41, 'Gomitas Comun', 'Gomitas Comun', 50.00, 30.00, 388, '2026-03-27 20:30:18'),
(42, 'PBT JyQ', 'PBT JyQ', 1500.00, 600.00, 0, '2026-03-27 20:40:17'),
(43, 'Savorizada 600', 'Savorizada 600', 1600.00, 812.00, 0, '2026-04-10 20:47:16'),
(48, 'FlymPaff', 'FlymPaff', 200.00, 100.00, 85, '2026-04-17 15:02:52'),
(49, 'Mogul ositos', 'Mogul Ositos', 1000.00, 480.00, 6, '2026-04-29 19:07:32'),
(50, 'Rasta alfajor', 'Rasta Alfajor', 2200.00, 1470.00, 0, '2026-04-29 19:11:06'),
(51, 'Tita', 'Titas', 1000.00, 500.00, 0, '2026-04-29 19:14:10'),
(52, 'Hamblet', 'Hamblet', 1500.00, 628.00, 7, '2026-04-29 19:22:36'),
(53, 'Papas bolsa', 'Papas Bolsa', 1500.00, 933.00, 14, '2026-04-29 19:29:29'),
(54, 'Aquarius', 'Aquarius', 2000.00, 1200.00, 10, '2026-04-29 19:34:09'),
(55, '9 de oro chips', '9 de oro chips', 1500.00, 704.00, 5, '2026-04-29 19:42:31'),
(56, '9 de oro', '9 de oro', 2000.00, 1014.00, 12, '2026-04-29 19:44:08'),
(57, 'Oblea', 'Obleas', 1500.00, 540.00, 6, '2026-04-29 19:45:07'),
(59, 'Bull Dog', 'Bull Dog', 1200.00, 540.00, 16, '2026-05-06 20:00:29'),
(60, 'Masticables', 'Masticables', 100.00, 40.00, 234, '2026-05-06 20:10:03'),
(61, 'Arbanito', 'Arbanito', 1000.00, 670.00, 0, '2026-05-06 20:11:41'),
(62, 'Mogul tubos', 'Mogul Tubos', 1000.00, 430.00, 20, '2026-05-07 00:54:13'),
(63, 'Rasta 70gm', 'Rasta 70gm', 1500.00, 1000.00, 4, '2026-05-09 15:11:49'),
(64, 'Takis', 'Takis', 2500.00, 1900.00, 0, '2026-05-13 17:53:39'),
(65, 'Gomitas ladrillitos', 'Gomitas ladrillitos', 100.00, 33.00, 16, '2026-05-23 16:53:15'),
(66, 'Bocaditos', 'Bocaditos', 300.00, 112.00, 5, '2026-05-23 16:56:03'),
(67, 'Malvavisco', 'Malvavisco', 1000.00, 309.00, 5, '2026-05-23 16:57:53'),
(68, 'Traviata Snack', 'Traviata Snark', 1500.00, 708.00, 12, '2026-05-23 17:00:59'),
(69, 'Guaymallen', 'Guaymallen', 1000.00, 411.00, 9, '2026-05-23 17:03:31'),
(70, 'Mogul extreme', 'Mogul extreme', 1000.00, 401.00, 6, '2026-05-23 17:07:10'),
(71, 'Saladix caja', 'Saladix Caja', 2500.00, 1133.00, 1, '2026-06-08 12:40:30'),
(72, 'Gomitas asidas x 10', 'Gomitas asidas x 10', 1200.00, 50.00, 17, '2026-06-12 17:55:50'),
(73, 'Gomitas asidas x 15', 'Gomitas asidas x 15', 1700.00, 50.00, 12, '2026-06-12 17:56:52'),
(74, 'Gomitas común x 10', 'Gomitas comun x 10', 1200.00, 25.00, 6, '2026-06-12 17:57:58'),
(75, 'Ladrillitos x 5', 'Ladrillito x 5', 700.00, 50.00, 2, '2026-06-12 17:59:03'),
(76, 'Ladrillito x 10', 'Ladrillito x 10', 1200.00, 50.00, 18, '2026-06-12 18:00:01'),
(77, 'Palitos de la selva', 'Palitos de la selva', 200.00, 70.00, 63, '2026-06-24 14:13:28'),
(78, 'Crema surtida', 'Crema surtida', 300.00, 150.00, 29, '2026-06-24 16:14:12'),
(79, 'Taxis chico', 'Taxis Chico', 1500.00, 1325.00, 3, '2026-06-24 18:02:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `semanas_operativas`
--

CREATE TABLE `semanas_operativas` (
  `id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `estado` enum('planificada','activa','cerrada') NOT NULL DEFAULT 'planificada',
  `observaciones` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `semanas_operativas`
--

INSERT INTO `semanas_operativas` (`id`, `fecha_inicio`, `fecha_fin`, `grupo_id`, `estado`, `observaciones`, `created_by`, `closed_by`, `created_at`, `updated_at`) VALUES
(1, '2026-05-27', '2026-05-29', 2, 'cerrada', NULL, 1, 1, '2026-05-27 18:37:39', '2026-05-29 20:50:27'),
(2, '2026-06-01', '2026-06-05', 1, 'cerrada', NULL, 1, 1, '2026-05-29 20:50:50', '2026-06-05 19:14:52'),
(3, '2026-06-05', '2026-12-11', 1, 'activa', NULL, 1, NULL, '2026-06-05 19:30:14', '2026-06-05 19:30:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transacciones`
--

CREATE TABLE `transacciones` (
  `id` int(11) NOT NULL,
  `turno_id` int(11) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `metodo_pago` varchar(20) DEFAULT NULL,
  `importe` decimal(10,2) NOT NULL DEFAULT 0.00,
  `usuario_id` int(11) DEFAULT NULL,
  `cuenta_id` int(11) DEFAULT NULL,
  `venta_uid` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `transacciones`
--

INSERT INTO `transacciones` (`id`, `turno_id`, `fecha`, `tipo`, `producto_id`, `descripcion`, `cantidad`, `metodo_pago`, `importe`, `usuario_id`, `cuenta_id`, `venta_uid`) VALUES
(1, 1, '2026-05-28 14:40:56', 'Venta', 21, NULL, 1, 'Efectivo', 1500.00, NULL, NULL, 'VEN-20260528144056-d0d7bdbe'),
(2, 1, '2026-05-28 14:40:56', 'Venta', 56, NULL, 2, 'Efectivo', 4000.00, NULL, NULL, 'VEN-20260528144056-d0d7bdbe'),
(3, 1, '2026-05-28 14:40:56', 'Venta', 57, NULL, 1, 'Efectivo', 1500.00, NULL, NULL, 'VEN-20260528144056-d0d7bdbe'),
(4, 2, '2026-05-29 14:52:21', 'Venta', 36, NULL, 1, 'Efectivo', 1000.00, NULL, NULL, 'VEN-20260529145221-bda0684d'),
(5, 2, '2026-05-29 14:52:21', 'Venta', 38, NULL, 1, 'Efectivo', 1000.00, NULL, NULL, 'VEN-20260529145221-bda0684d'),
(6, 2, '2026-05-29 14:52:21', 'Venta', 40, NULL, 16, 'Efectivo', 1600.00, NULL, NULL, 'VEN-20260529145221-bda0684d'),
(7, 2, '2026-05-29 14:52:21', 'Venta', 54, NULL, 2, 'Efectivo', 4000.00, NULL, NULL, 'VEN-20260529145221-bda0684d'),
(8, 2, '2026-05-29 14:52:21', 'Venta', 57, NULL, 1, 'Efectivo', 1500.00, NULL, NULL, 'VEN-20260529145221-bda0684d'),
(9, 2, '2026-05-29 14:52:21', 'Venta', 64, NULL, 4, 'Efectivo', 10000.00, NULL, NULL, 'VEN-20260529145221-bda0684d'),
(10, 2, '2026-05-29 14:52:21', 'Venta', 65, NULL, 10, 'Efectivo', 1000.00, NULL, NULL, 'VEN-20260529145221-bda0684d'),
(11, 2, '2026-05-29 14:52:21', 'Venta', 67, NULL, 2, 'Efectivo', 2000.00, NULL, NULL, 'VEN-20260529145221-bda0684d'),
(12, 2, '2026-05-29 14:54:24', 'Venta', 40, NULL, 12, 'Transferencia', 1200.00, NULL, 1, 'VEN-20260529145424-50e2180f'),
(13, 2, '2026-05-29 14:54:24', 'Venta', 48, NULL, 1, 'Transferencia', 200.00, NULL, 1, 'VEN-20260529145424-50e2180f'),
(14, 2, '2026-05-29 14:54:24', 'Venta', 64, NULL, 1, 'Transferencia', 2500.00, NULL, 1, 'VEN-20260529145424-50e2180f'),
(15, 2, '2026-05-29 14:54:24', 'Venta', 65, NULL, 20, 'Transferencia', 2000.00, NULL, 1, 'VEN-20260529145424-50e2180f'),
(16, 2, '2026-05-29 14:54:24', 'Venta', 67, NULL, 1, 'Transferencia', 1000.00, NULL, 1, 'VEN-20260529145424-50e2180f'),
(17, 2, '2026-05-29 14:54:24', 'Venta', 68, NULL, 2, 'Transferencia', 3000.00, NULL, 1, 'VEN-20260529145424-50e2180f'),
(18, 3, '2026-05-29 16:17:38', 'Venta', 21, NULL, 2, 'Efectivo', 3000.00, NULL, NULL, 'VEN-20260529161738-6dc64b17'),
(19, 3, '2026-05-29 16:23:41', 'Venta', 20, NULL, 1, 'Transferencia', 2000.00, NULL, 1, 'VEN-20260529162341-cc6f359b'),
(20, 3, '2026-05-29 16:23:41', 'Venta', 36, NULL, 2, 'Transferencia', 2000.00, NULL, 1, 'VEN-20260529162341-cc6f359b'),
(21, 3, '2026-05-29 16:23:41', 'Venta', 40, NULL, 2, 'Transferencia', 200.00, NULL, 1, 'VEN-20260529162341-cc6f359b'),
(22, 3, '2026-05-29 16:23:41', 'Venta', 53, NULL, 2, 'Transferencia', 3000.00, NULL, 1, 'VEN-20260529162341-cc6f359b'),
(23, 3, '2026-05-29 16:23:41', 'Venta', 65, NULL, 1, 'Transferencia', 100.00, NULL, 1, 'VEN-20260529162341-cc6f359b'),
(24, 3, '2026-05-29 16:23:41', 'Venta', 68, NULL, 1, 'Transferencia', 1500.00, NULL, 1, 'VEN-20260529162341-cc6f359b'),
(25, 4, '2026-05-29 16:59:59', 'Venta', 65, NULL, 30, 'Transferencia', 3000.00, NULL, 1, 'VEN-20260529165959-8971c1c1'),
(26, 5, '2026-06-03 14:49:17', 'Venta', 21, NULL, 1, 'Efectivo', 1500.00, 10, NULL, 'VEN-20260603144917-c4d27687'),
(27, 5, '2026-06-03 14:49:17', 'Venta', 25, NULL, 1, 'Efectivo', 500.00, 10, NULL, 'VEN-20260603144917-c4d27687'),
(28, 5, '2026-06-03 14:49:17', 'Venta', 40, NULL, 6, 'Efectivo', 600.00, 10, NULL, 'VEN-20260603144917-c4d27687'),
(29, 5, '2026-06-03 14:49:17', 'Venta', 48, NULL, 2, 'Efectivo', 400.00, 10, NULL, 'VEN-20260603144917-c4d27687'),
(30, 5, '2026-06-03 14:49:17', 'Venta', 49, NULL, 1, 'Efectivo', 1000.00, 10, NULL, 'VEN-20260603144917-c4d27687'),
(31, 5, '2026-06-03 14:49:17', 'Venta', 63, NULL, 1, 'Efectivo', 1500.00, 10, NULL, 'VEN-20260603144917-c4d27687'),
(32, 5, '2026-06-03 14:51:11', 'Venta', 25, NULL, 1, 'Efectivo', 500.00, 10, NULL, 'VEN-20260603145111-ad9eacb1'),
(33, 5, '2026-06-03 14:51:11', 'Venta', 28, NULL, 1, 'Efectivo', 1800.00, 10, NULL, 'VEN-20260603145111-ad9eacb1'),
(34, 5, '2026-06-03 14:51:11', 'Venta', 37, NULL, 2, 'Efectivo', 2000.00, 10, NULL, 'VEN-20260603145111-ad9eacb1'),
(35, 5, '2026-06-03 14:51:11', 'Venta', 56, NULL, 1, 'Efectivo', 2000.00, 10, NULL, 'VEN-20260603145111-ad9eacb1'),
(36, 5, '2026-06-03 14:51:11', 'Venta', 60, NULL, 6, 'Efectivo', 600.00, 10, NULL, 'VEN-20260603145111-ad9eacb1'),
(37, 5, '2026-06-03 14:51:11', 'Venta', 69, NULL, 1, 'Efectivo', 1000.00, 10, NULL, 'VEN-20260603145111-ad9eacb1'),
(38, 6, '2026-06-03 17:46:46', 'Venta', 17, NULL, 1, 'Efectivo', 500.00, 10, NULL, 'VEN-20260603174646-5a71eadb'),
(39, 6, '2026-06-03 17:46:46', 'Venta', 21, NULL, 2, 'Efectivo', 3000.00, 10, NULL, 'VEN-20260603174646-5a71eadb'),
(40, 6, '2026-06-03 17:46:46', 'Venta', 25, NULL, 3, 'Efectivo', 1500.00, 10, NULL, 'VEN-20260603174646-5a71eadb'),
(41, 6, '2026-06-03 17:46:46', 'Venta', 32, NULL, 2, 'Efectivo', 2000.00, 10, NULL, 'VEN-20260603174646-5a71eadb'),
(42, 6, '2026-06-03 17:46:46', 'Venta', 48, NULL, 7, 'Efectivo', 1400.00, 10, NULL, 'VEN-20260603174646-5a71eadb'),
(43, 6, '2026-06-03 17:46:46', 'Venta', 53, NULL, 1, 'Efectivo', 1500.00, 10, NULL, 'VEN-20260603174646-5a71eadb'),
(44, 6, '2026-06-03 17:46:46', 'Venta', 55, NULL, 1, 'Efectivo', 1500.00, 10, NULL, 'VEN-20260603174646-5a71eadb'),
(46, 6, '2026-06-03 17:46:46', 'Venta', 63, NULL, 1, 'Efectivo', 1500.00, 10, NULL, 'VEN-20260603174646-5a71eadb'),
(47, 6, '2026-06-03 17:46:46', 'Venta', 65, NULL, 5, 'Efectivo', 500.00, 10, NULL, 'VEN-20260603174646-5a71eadb'),
(48, 6, '2026-06-03 17:46:46', 'Venta', 66, NULL, 6, 'Efectivo', 1800.00, 10, NULL, 'VEN-20260603174646-5a71eadb'),
(49, 6, '2026-06-03 17:49:59', 'Venta', 10, NULL, 1, 'Transferencia', 800.00, 10, 1, 'VEN-20260603174959-a28a1cae'),
(50, 6, '2026-06-03 17:49:59', 'Venta', 20, NULL, 1, 'Transferencia', 2000.00, 10, 1, 'VEN-20260603174959-a28a1cae'),
(51, 6, '2026-06-03 17:49:59', 'Venta', 21, NULL, 2, 'Transferencia', 3000.00, 10, 1, 'VEN-20260603174959-a28a1cae'),
(52, 6, '2026-06-03 17:49:59', 'Venta', 30, NULL, 1, 'Transferencia', 1500.00, 10, 1, 'VEN-20260603174959-a28a1cae'),
(53, 6, '2026-06-03 17:49:59', 'Venta', 32, NULL, 3, 'Transferencia', 3000.00, 10, 1, 'VEN-20260603174959-a28a1cae'),
(54, 6, '2026-06-03 17:49:59', 'Venta', 36, NULL, 1, 'Transferencia', 1000.00, 10, 1, 'VEN-20260603174959-a28a1cae'),
(55, 6, '2026-06-03 17:49:59', 'Venta', 40, NULL, 17, 'Transferencia', 1700.00, 10, 1, 'VEN-20260603174959-a28a1cae'),
(56, 6, '2026-06-03 17:49:59', 'Venta', 54, NULL, 1, 'Transferencia', 2000.00, 10, 1, 'VEN-20260603174959-a28a1cae'),
(57, 6, '2026-06-03 17:49:59', 'Venta', 63, NULL, 1, 'Transferencia', 1500.00, 10, 1, 'VEN-20260603174959-a28a1cae'),
(58, 6, '2026-06-03 17:49:59', 'Venta', 66, NULL, 6, 'Transferencia', 1800.00, 10, 1, 'VEN-20260603174959-a28a1cae'),
(59, 8, '2026-06-05 16:34:22', 'Venta', 10, NULL, 2, 'Efectivo', 1600.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(60, 8, '2026-06-05 16:34:22', 'Venta', 21, NULL, 1, 'Efectivo', 1500.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(61, 8, '2026-06-05 16:34:22', 'Venta', 25, NULL, 2, 'Efectivo', 1000.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(62, 8, '2026-06-05 16:34:22', 'Venta', 30, NULL, 1, 'Efectivo', 1500.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(63, 8, '2026-06-05 16:34:22', 'Venta', 32, NULL, 4, 'Efectivo', 4000.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(64, 8, '2026-06-05 16:34:22', 'Venta', 36, NULL, 1, 'Efectivo', 1000.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(65, 8, '2026-06-05 16:34:22', 'Venta', 38, NULL, 1, 'Efectivo', 1000.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(66, 8, '2026-06-05 16:34:22', 'Venta', 52, NULL, 1, 'Efectivo', 1500.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(67, 8, '2026-06-05 16:34:22', 'Venta', 55, NULL, 1, 'Efectivo', 1500.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(68, 8, '2026-06-05 16:34:22', 'Venta', 56, NULL, 1, 'Efectivo', 2000.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(69, 8, '2026-06-05 16:34:22', 'Venta', 69, NULL, 3, 'Efectivo', 3000.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(70, 8, '2026-06-05 16:34:22', 'Venta', 70, NULL, 1, 'Efectivo', 1000.00, 1, NULL, 'VEN-20260605163422-388e43fe'),
(71, 8, '2026-06-05 16:35:52', 'Venta', 10, NULL, 2, 'Transferencia', 1600.00, 1, 1, 'VEN-20260605163552-a5b13783'),
(72, 8, '2026-06-05 16:35:52', 'Venta', 21, NULL, 3, 'Transferencia', 4500.00, 1, 1, 'VEN-20260605163552-a5b13783'),
(73, 8, '2026-06-05 16:35:52', 'Venta', 32, NULL, 4, 'Transferencia', 4000.00, 1, 1, 'VEN-20260605163552-a5b13783'),
(74, 8, '2026-06-05 16:35:52', 'Venta', 38, NULL, 1, 'Transferencia', 1000.00, 1, 1, 'VEN-20260605163552-a5b13783'),
(75, 8, '2026-06-05 16:35:52', 'Venta', 69, NULL, 2, 'Transferencia', 2000.00, 1, 1, 'VEN-20260605163552-a5b13783'),
(76, 10, '2026-06-08 18:50:22', 'Venta', 32, NULL, 2, 'Efectivo', 2000.00, 10, NULL, 'VEN-20260608185022-478ec997'),
(77, 10, '2026-06-08 18:50:22', 'Venta', 33, NULL, 1, 'Efectivo', 1300.00, 10, NULL, 'VEN-20260608185022-478ec997'),
(78, 10, '2026-06-08 18:50:22', 'Venta', 36, NULL, 1, 'Efectivo', 1000.00, 10, NULL, 'VEN-20260608185022-478ec997'),
(79, 10, '2026-06-08 18:50:22', 'Venta', 40, NULL, 3, 'Efectivo', 300.00, 10, NULL, 'VEN-20260608185022-478ec997'),
(80, 10, '2026-06-08 18:50:22', 'Venta', 41, NULL, 14, 'Efectivo', 700.00, 10, NULL, 'VEN-20260608185022-478ec997'),
(81, 10, '2026-06-08 18:50:22', 'Venta', 48, NULL, 7, 'Efectivo', 1400.00, 10, NULL, 'VEN-20260608185022-478ec997'),
(82, 10, '2026-06-08 18:50:22', 'Venta', 56, NULL, 1, 'Efectivo', 2000.00, 10, NULL, 'VEN-20260608185022-478ec997'),
(83, 10, '2026-06-08 18:50:22', 'Venta', 57, NULL, 1, 'Efectivo', 1500.00, 10, NULL, 'VEN-20260608185022-478ec997'),
(84, 10, '2026-06-08 18:50:22', 'Venta', 69, NULL, 1, 'Efectivo', 1000.00, 10, NULL, 'VEN-20260608185022-478ec997'),
(85, 10, '2026-06-08 18:52:51', 'Venta', 36, NULL, 1, 'Transferencia', 1000.00, 10, 1, 'VEN-20260608185251-f85a8492'),
(86, 10, '2026-06-08 18:52:51', 'Venta', 60, NULL, 5, 'Transferencia', 500.00, 10, 1, 'VEN-20260608185251-f85a8492'),
(87, 10, '2026-06-08 18:52:51', 'Venta', 64, NULL, 4, 'Transferencia', 10000.00, 10, 1, 'VEN-20260608185251-f85a8492'),
(88, 10, '2026-06-08 18:52:51', 'Venta', 65, NULL, 35, 'Transferencia', 3500.00, 10, 1, 'VEN-20260608185251-f85a8492'),
(89, 10, '2026-06-08 18:52:51', 'Venta', 68, NULL, 1, 'Transferencia', 1500.00, 10, 1, 'VEN-20260608185251-f85a8492'),
(90, 10, '2026-06-08 18:55:34', 'Venta', 4, NULL, 1, 'Efectivo', 2200.00, 10, NULL, 'VEN-20260608185534-8ea51dfd'),
(91, 10, '2026-06-08 18:55:34', 'Venta', 32, NULL, 1, 'Efectivo', 1000.00, 10, NULL, 'VEN-20260608185534-8ea51dfd'),
(92, 10, '2026-06-08 18:55:34', 'Venta', 36, NULL, 1, 'Efectivo', 1000.00, 10, NULL, 'VEN-20260608185534-8ea51dfd'),
(93, 10, '2026-06-08 18:55:34', 'Venta', 41, NULL, 10, 'Efectivo', 500.00, 10, NULL, 'VEN-20260608185534-8ea51dfd'),
(94, 10, '2026-06-08 18:55:34', 'Venta', 52, NULL, 2, 'Efectivo', 3000.00, 10, NULL, 'VEN-20260608185534-8ea51dfd'),
(95, 10, '2026-06-08 18:55:34', 'Venta', 56, NULL, 1, 'Efectivo', 2000.00, 10, NULL, 'VEN-20260608185534-8ea51dfd'),
(96, 10, '2026-06-08 18:55:34', 'Venta', 65, NULL, 1, 'Efectivo', 100.00, 10, NULL, 'VEN-20260608185534-8ea51dfd'),
(97, 10, '2026-06-08 18:57:33', 'Venta', 32, NULL, 2, 'Transferencia', 2000.00, 10, 1, 'VEN-20260608185733-e7692850'),
(98, 10, '2026-06-08 18:57:33', 'Venta', 40, NULL, 5, 'Transferencia', 500.00, 10, 1, 'VEN-20260608185733-e7692850'),
(99, 10, '2026-06-08 18:57:33', 'Venta', 64, NULL, 4, 'Transferencia', 10000.00, 10, 1, 'VEN-20260608185733-e7692850'),
(100, 11, '2026-06-10 14:53:16', 'Venta', 4, NULL, 1, 'Efectivo', 2200.00, 10, NULL, 'VEN-20260610145316-2469d1bc'),
(101, 11, '2026-06-10 14:53:16', 'Venta', 32, NULL, 3, 'Efectivo', 3000.00, 10, NULL, 'VEN-20260610145316-2469d1bc'),
(102, 11, '2026-06-10 14:53:16', 'Venta', 54, NULL, 1, 'Efectivo', 2000.00, 10, NULL, 'VEN-20260610145316-2469d1bc'),
(103, 11, '2026-06-10 14:53:16', 'Venta', 55, NULL, 2, 'Efectivo', 3000.00, 10, NULL, 'VEN-20260610145316-2469d1bc'),
(104, 11, '2026-06-10 14:53:16', 'Venta', 63, NULL, 3, 'Efectivo', 4500.00, 10, NULL, 'VEN-20260610145316-2469d1bc'),
(105, 11, '2026-06-10 14:53:16', 'Venta', 64, NULL, 1, 'Efectivo', 2500.00, 10, NULL, 'VEN-20260610145316-2469d1bc'),
(106, 11, '2026-06-10 14:53:16', 'Venta', 65, NULL, 40, 'Efectivo', 4000.00, 10, NULL, 'VEN-20260610145316-2469d1bc'),
(107, 11, '2026-06-10 14:53:16', 'Venta', 70, NULL, 1, 'Efectivo', 1000.00, 10, NULL, 'VEN-20260610145316-2469d1bc'),
(108, 11, '2026-06-10 14:56:10', 'Venta', 4, NULL, 1, 'Transferencia', 2200.00, 10, 1, 'VEN-20260610145610-795fa186'),
(109, 11, '2026-06-10 14:56:10', 'Venta', 21, NULL, 1, 'Transferencia', 1500.00, 10, 1, 'VEN-20260610145610-795fa186'),
(110, 11, '2026-06-10 14:56:10', 'Venta', 30, NULL, 1, 'Transferencia', 1500.00, 10, 1, 'VEN-20260610145610-795fa186'),
(111, 11, '2026-06-10 14:56:10', 'Venta', 32, NULL, 2, 'Transferencia', 2000.00, 10, 1, 'VEN-20260610145610-795fa186'),
(112, 11, '2026-06-10 14:56:10', 'Venta', 36, NULL, 1, 'Transferencia', 1000.00, 10, 1, 'VEN-20260610145610-795fa186'),
(113, 11, '2026-06-10 14:56:10', 'Venta', 60, NULL, 18, 'Transferencia', 1800.00, 10, 1, 'VEN-20260610145610-795fa186'),
(114, 11, '2026-06-10 14:56:10', 'Venta', 61, NULL, 2, 'Transferencia', 2000.00, 10, 1, 'VEN-20260610145610-795fa186'),
(115, 11, '2026-06-10 14:56:10', 'Venta', 64, NULL, 1, 'Transferencia', 2500.00, 10, 1, 'VEN-20260610145610-795fa186'),
(116, 11, '2026-06-10 14:56:10', 'Venta', 65, NULL, 17, 'Transferencia', 1700.00, 10, 1, 'VEN-20260610145610-795fa186'),
(117, 12, '2026-06-10 15:29:04', 'Venta', 32, NULL, 1, 'Transferencia', 1000.00, 10, 1, 'VEN-20260610152904-c34e9daa'),
(118, 12, '2026-06-10 15:45:04', 'Venta', 52, NULL, 1, 'Transferencia', 1500.00, 18, 1, 'VEN-20260610154504-b3a2df21'),
(119, 12, '2026-06-10 15:54:26', 'Venta', 21, NULL, 2, 'Transferencia', 3000.00, 18, 1, 'VEN-20260610155426-07108949'),
(120, 12, '2026-06-10 15:54:26', 'Venta', 32, NULL, 1, 'Transferencia', 1000.00, 18, 1, 'VEN-20260610155426-07108949'),
(121, 12, '2026-06-10 15:55:14', 'Venta', 32, NULL, 2, 'Transferencia', 2000.00, 18, 1, 'VEN-20260610155514-7cda87f0'),
(122, 12, '2026-06-10 15:55:14', 'Venta', 65, NULL, 10, 'Transferencia', 1000.00, 18, 1, 'VEN-20260610155514-7cda87f0'),
(123, 12, '2026-06-10 15:56:07', 'Venta', 40, NULL, 10, 'Transferencia', 1000.00, 18, 1, 'VEN-20260610155607-e70eab08'),
(124, 12, '2026-06-10 15:56:07', 'Venta', 65, NULL, 5, 'Transferencia', 500.00, 18, 1, 'VEN-20260610155607-e70eab08'),
(125, 12, '2026-06-10 15:56:44', 'Venta', 65, NULL, 20, 'Transferencia', 2000.00, 18, 1, 'VEN-20260610155644-b827e6de'),
(126, 12, '2026-06-10 16:01:10', 'Venta', 40, NULL, 5, 'Transferencia', 500.00, 18, 1, 'VEN-20260610160110-1b091f5f'),
(127, 12, '2026-06-10 16:03:29', 'Venta', 30, NULL, 1, 'Transferencia', 1500.00, 18, 1, 'VEN-20260610160329-917ed82a'),
(128, 12, '2026-06-10 16:03:29', 'Venta', 56, NULL, 1, 'Transferencia', 2000.00, 18, 1, 'VEN-20260610160329-917ed82a'),
(129, 12, '2026-06-10 16:04:15', 'Venta', 32, NULL, 2, 'Efectivo', 2000.00, 13, NULL, 'VEN-20260610160415-819a2c21'),
(130, 12, '2026-06-10 16:04:15', 'Venta', 34, NULL, 13, 'Efectivo', 2600.00, 13, NULL, 'VEN-20260610160415-819a2c21'),
(131, 12, '2026-06-10 16:04:15', 'Venta', 63, NULL, 2, 'Efectivo', 3000.00, 13, NULL, 'VEN-20260610160415-819a2c21'),
(132, 12, '2026-06-10 16:04:15', 'Venta', 65, NULL, 25, 'Efectivo', 2500.00, 13, NULL, 'VEN-20260610160415-819a2c21'),
(133, 12, '2026-06-10 16:04:15', 'Venta', 69, NULL, 1, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260610160415-819a2c21'),
(134, 12, '2026-06-10 16:05:09', 'Venta', 34, NULL, 13, 'Transferencia', 2600.00, 18, 1, 'VEN-20260610160509-39af5d3d'),
(135, 12, '2026-06-10 16:06:25', 'Venta', 21, NULL, 1, 'Transferencia', 1500.00, 18, 1, 'VEN-20260610160625-403f642a'),
(136, 12, '2026-06-10 16:15:10', 'Venta', 30, NULL, 1, 'Transferencia', 1500.00, 10, 1, 'VEN-20260610161510-9d128b6c'),
(137, 12, '2026-06-10 16:15:10', 'Venta', 56, NULL, 1, 'Transferencia', 2000.00, 10, 1, 'VEN-20260610161510-9d128b6c'),
(138, 12, '2026-06-10 16:19:26', 'Venta', 40, NULL, 8, 'Efectivo', 800.00, 10, NULL, 'VEN-20260610161926-98961ff7'),
(139, 13, '2026-06-11 14:37:15', 'Venta', 56, NULL, 1, 'Efectivo', 2000.00, 16, NULL, 'VEN-20260611143715-2484adfd'),
(140, 14, '2026-06-12 14:23:41', 'Venta', 65, NULL, 20, 'Transferencia', 2000.00, 18, 1, 'VEN-20260612142341-5a2d2005'),
(141, 14, '2026-06-12 14:31:01', 'Venta', 28, NULL, 1, 'Efectivo', 1800.00, 13, NULL, 'VEN-20260612143101-23ae3a05'),
(142, 14, '2026-06-12 14:31:01', 'Venta', 34, NULL, 5, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260612143101-23ae3a05'),
(143, 14, '2026-06-12 14:31:01', 'Venta', 49, NULL, 1, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260612143101-23ae3a05'),
(144, 14, '2026-06-12 14:31:01', 'Venta', 60, NULL, 5, 'Efectivo', 500.00, 13, NULL, 'VEN-20260612143101-23ae3a05'),
(145, 14, '2026-06-12 14:31:01', 'Venta', 69, NULL, 1, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260612143101-23ae3a05'),
(146, 14, '2026-06-12 14:31:01', 'Venta', 71, NULL, 1, 'Efectivo', 2500.00, 13, NULL, 'VEN-20260612143101-23ae3a05'),
(147, 14, '2026-06-12 14:32:31', 'Venta', 32, NULL, 1, 'Transferencia', 1000.00, 18, 1, 'VEN-20260612143231-f9eadade'),
(148, 14, '2026-06-12 14:37:18', 'Venta', 32, NULL, 1, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260612143718-3343b5c3'),
(149, 14, '2026-06-12 14:42:28', 'Venta', 66, NULL, 5, 'Efectivo', 1500.00, 13, NULL, 'VEN-20260612144228-9ebd822c'),
(150, 14, '2026-06-12 14:52:14', 'Venta', 21, NULL, 2, 'Efectivo', 3000.00, 13, NULL, 'VEN-20260612145214-d311e283'),
(151, 15, '2026-06-12 15:14:52', 'Venta', 34, NULL, 10, 'Transferencia', 2000.00, 16, 1, 'VEN-20260612151452-6a279d7f'),
(152, 15, '2026-06-12 15:14:52', 'Venta', 53, NULL, 1, 'Transferencia', 1500.00, 16, 1, 'VEN-20260612151452-6a279d7f'),
(153, 15, '2026-06-12 15:14:52', 'Venta', 66, NULL, 5, 'Transferencia', 1500.00, 16, 1, 'VEN-20260612151452-6a279d7f'),
(154, 16, '2026-06-12 15:54:52', 'Venta', 74, NULL, 1, 'Transferencia', 1200.00, 16, 1, 'VEN-20260612155452-f0d1d451'),
(155, 16, '2026-06-12 15:54:52', 'Venta', 76, NULL, 1, 'Transferencia', 1200.00, 16, 1, 'VEN-20260612155452-f0d1d451'),
(156, 16, '2026-06-12 15:55:51', 'Venta', 28, NULL, 1, 'Efectivo', 1800.00, 18, NULL, 'VEN-20260612155551-f9cb09e5'),
(157, 16, '2026-06-12 15:55:51', 'Venta', 71, NULL, 1, 'Efectivo', 2500.00, 18, NULL, 'VEN-20260612155551-f9cb09e5'),
(158, 16, '2026-06-12 15:56:54', 'Venta', 38, NULL, 1, 'Efectivo', 1000.00, 18, NULL, 'VEN-20260612155654-adb11c83'),
(159, 16, '2026-06-12 15:57:02', 'Venta', 21, NULL, 2, 'Transferencia', 3000.00, 16, 1, 'VEN-20260612155702-b3b25b7b'),
(160, 16, '2026-06-12 16:11:54', 'Venta', 34, NULL, 5, 'Efectivo', 1000.00, 16, NULL, 'VEN-20260612161154-85bf6248'),
(161, 17, '2026-06-12 16:31:44', 'Venta', 28, NULL, 1, 'Transferencia', 1800.00, 15, 1, 'VEN-20260612163144-77e45b55'),
(162, 20, '2026-06-17 14:21:02', 'Venta', 38, NULL, 1, 'Efectivo', 1000.00, 16, NULL, 'VEN-20260617142102-a75d0a5d'),
(163, 20, '2026-06-17 14:23:18', 'Venta', 32, NULL, 1, 'Efectivo', 1000.00, 16, NULL, 'VEN-20260617142318-aeebd4c9'),
(164, 20, '2026-06-17 14:23:18', 'Venta', 63, NULL, 1, 'Efectivo', 1500.00, 16, NULL, 'VEN-20260617142318-aeebd4c9'),
(165, 20, '2026-06-17 14:24:28', 'Venta', 36, NULL, 1, 'Transferencia', 1000.00, 18, 1, 'VEN-20260617142428-3464c174'),
(166, 20, '2026-06-17 14:24:28', 'Venta', 54, NULL, 1, 'Transferencia', 2000.00, 18, 1, 'VEN-20260617142428-3464c174'),
(167, 20, '2026-06-17 14:24:40', 'Venta', 32, NULL, 2, 'Efectivo', 2000.00, 16, NULL, 'VEN-20260617142440-f9372651'),
(168, 20, '2026-06-17 14:24:40', 'Venta', 68, NULL, 1, 'Efectivo', 1500.00, 16, NULL, 'VEN-20260617142440-f9372651'),
(169, 20, '2026-06-17 14:25:06', 'Venta', 32, NULL, 2, 'Efectivo', 2000.00, 16, NULL, 'VEN-20260617142506-375104de'),
(170, 20, '2026-06-17 14:26:11', 'Venta', 32, NULL, 1, 'Transferencia', 1000.00, 17, 1, 'VEN-20260617142611-07e12ceb'),
(171, 20, '2026-06-17 14:26:30', 'Venta', 28, NULL, 1, 'Efectivo', 1800.00, 16, NULL, 'VEN-20260617142630-8f9b16c1'),
(172, 20, '2026-06-17 14:28:41', 'Venta', 32, NULL, 1, 'Transferencia', 1000.00, 18, 1, 'VEN-20260617142841-ec0845f9'),
(173, 20, '2026-06-17 14:29:38', 'Venta', 68, NULL, 1, 'Transferencia', 1500.00, 17, 1, 'VEN-20260617142938-7860d786'),
(174, 20, '2026-06-17 14:31:06', 'Venta', 34, NULL, 6, 'Efectivo', 1200.00, 16, NULL, 'VEN-20260617143106-e6928a99'),
(175, 20, '2026-06-17 14:31:25', 'Venta', 53, NULL, 1, 'Transferencia', 1500.00, 17, 1, 'VEN-20260617143125-d042fab2'),
(176, 20, '2026-06-17 14:31:25', 'Venta', 71, NULL, 1, 'Transferencia', 2500.00, 17, 1, 'VEN-20260617143125-d042fab2'),
(177, 20, '2026-06-17 14:32:37', 'Venta', 54, NULL, 1, 'Transferencia', 2000.00, 18, 1, 'VEN-20260617143237-434cb49b'),
(178, 20, '2026-06-17 14:33:28', 'Venta', 36, NULL, 1, 'Efectivo', 1000.00, 16, NULL, 'VEN-20260617143328-04f1cd71'),
(179, 21, '2026-06-17 15:48:43', 'Venta', 63, NULL, 1, 'Transferencia', 1500.00, 16, 1, 'VEN-20260617154843-a1eb4176'),
(180, 21, '2026-06-17 15:50:22', 'Venta', 76, NULL, 1, 'Transferencia', 1200.00, 16, 1, 'VEN-20260617155022-cd074e21'),
(181, 21, '2026-06-17 15:51:46', 'Venta', 36, NULL, 1, 'Transferencia', 1000.00, 16, 1, 'VEN-20260617155146-e28507f1'),
(182, 21, '2026-06-17 15:51:46', 'Venta', 63, NULL, 1, 'Transferencia', 1500.00, 16, 1, 'VEN-20260617155146-e28507f1'),
(183, 21, '2026-06-17 15:52:59', 'Venta', 75, NULL, 1, 'Transferencia', 700.00, 16, 1, 'VEN-20260617155259-4e8e637c'),
(184, 21, '2026-06-17 15:53:30', 'Venta', 63, NULL, 1, 'Transferencia', 1500.00, 18, 1, 'VEN-20260617155330-7e2ff60d'),
(185, 21, '2026-06-17 15:53:44', 'Venta', 25, NULL, 1, 'Efectivo', 500.00, 16, NULL, 'VEN-20260617155344-eea8c6c7'),
(186, 21, '2026-06-17 15:54:32', 'Venta', 34, NULL, 2, 'Efectivo', 400.00, 16, NULL, 'VEN-20260617155432-3f46d79c'),
(187, 21, '2026-06-17 15:57:01', 'Venta', 30, NULL, 1, 'Efectivo', 1500.00, 16, NULL, 'VEN-20260617155701-3473ee7b'),
(188, 21, '2026-06-17 16:00:31', 'Venta', 32, NULL, 1, 'Transferencia', 1000.00, 18, 1, 'VEN-20260617160031-2d3a7288'),
(189, 21, '2026-06-17 16:00:45', 'Venta', 36, NULL, 1, 'Transferencia', 1000.00, 18, 1, 'VEN-20260617160045-c879c294'),
(190, 21, '2026-06-17 16:01:17', 'Venta', 63, NULL, 1, 'Efectivo', 1500.00, 16, NULL, 'VEN-20260617160117-ce481c02'),
(191, 21, '2026-06-17 16:03:29', 'Venta', 56, NULL, 1, 'Transferencia', 2000.00, 16, 1, 'VEN-20260617160329-8c498545'),
(192, 21, '2026-06-17 16:12:10', 'Venta', 52, NULL, 1, 'Transferencia', 1500.00, 16, 1, 'VEN-20260617161210-d1fe604c'),
(193, 21, '2026-06-17 16:12:10', 'Venta', 53, NULL, 1, 'Transferencia', 1500.00, 16, 1, 'VEN-20260617161210-d1fe604c'),
(194, 22, '2026-06-24 14:22:06', 'Venta', 36, NULL, 1, 'Efectivo', 1000.00, 17, NULL, 'VEN-20260624142206-1a65a21d'),
(195, 22, '2026-06-24 14:22:06', 'Venta', 56, NULL, 1, 'Efectivo', 2000.00, 17, NULL, 'VEN-20260624142206-1a65a21d'),
(196, 22, '2026-06-24 14:22:06', 'Venta', 69, NULL, 1, 'Efectivo', 1000.00, 17, NULL, 'VEN-20260624142206-1a65a21d'),
(197, 22, '2026-06-24 14:22:43', 'Venta', 68, NULL, 1, 'Efectivo', 1500.00, 17, NULL, 'VEN-20260624142243-b2ad03b5'),
(198, 22, '2026-06-24 14:22:47', 'Venta', 10, NULL, 1, 'Efectivo', 800.00, 18, NULL, 'VEN-20260624142247-17a7a7b9'),
(199, 22, '2026-06-24 14:22:47', 'Venta', 21, NULL, 1, 'Efectivo', 1500.00, 18, NULL, 'VEN-20260624142247-17a7a7b9'),
(200, 22, '2026-06-24 14:23:07', 'Venta', 30, NULL, 1, 'Efectivo', 1500.00, 17, NULL, 'VEN-20260624142307-4ea5c421'),
(201, 22, '2026-06-24 14:25:15', 'Venta', 65, NULL, 10, 'Efectivo', 1000.00, 18, NULL, 'VEN-20260624142515-bbe66d02'),
(202, 22, '2026-06-24 14:25:15', 'Venta', 70, NULL, 1, 'Efectivo', 1000.00, 18, NULL, 'VEN-20260624142515-bbe66d02'),
(203, 22, '2026-06-24 14:25:15', 'Venta', 78, NULL, 2, 'Efectivo', 600.00, 18, NULL, 'VEN-20260624142515-bbe66d02'),
(204, 22, '2026-06-24 14:25:38', 'Venta', 78, NULL, 3, 'Efectivo', 900.00, 17, NULL, 'VEN-20260624142538-84356406'),
(205, 22, '2026-06-24 14:25:39', 'Venta', 78, NULL, 3, 'Transferencia', 900.00, 18, 1, 'VEN-20260624142539-52cfd12a'),
(206, 22, '2026-06-24 14:26:27', 'Venta', 32, NULL, 1, 'Transferencia', 1000.00, 18, 1, 'VEN-20260624142627-96412318'),
(207, 22, '2026-06-24 14:26:27', 'Venta', 69, NULL, 1, 'Transferencia', 1000.00, 18, 1, 'VEN-20260624142627-96412318'),
(208, 22, '2026-06-24 14:26:36', 'Venta', 21, NULL, 1, 'Transferencia', 1500.00, 17, 1, 'VEN-20260624142636-4bd2e5f2'),
(209, 22, '2026-06-24 14:26:36', 'Venta', 30, NULL, 1, 'Transferencia', 1500.00, 17, 1, 'VEN-20260624142636-4bd2e5f2'),
(210, 22, '2026-06-24 14:27:18', 'Venta', 73, NULL, 2, 'Transferencia', 3400.00, 17, 1, 'VEN-20260624142718-8aba2126'),
(211, 22, '2026-06-24 14:27:24', 'Venta', 70, NULL, 2, 'Transferencia', 2000.00, 18, 1, 'VEN-20260624142724-edb3e869'),
(212, 22, '2026-06-24 14:27:24', 'Venta', 72, NULL, 2, 'Transferencia', 2400.00, 18, 1, 'VEN-20260624142724-edb3e869'),
(213, 22, '2026-06-24 14:27:41', 'Venta', 21, NULL, 1, 'Efectivo', 1500.00, 17, NULL, 'VEN-20260624142741-708851c6'),
(214, 22, '2026-06-24 14:30:01', 'Venta', 78, NULL, 6, 'Transferencia', 1800.00, 17, 1, 'VEN-20260624143001-ef211de0'),
(215, 22, '2026-06-24 14:30:10', 'Venta', 78, NULL, 2, 'Efectivo', 600.00, 18, NULL, 'VEN-20260624143010-b86ca4a3'),
(216, 22, '2026-06-24 14:30:58', 'Venta', 78, NULL, 2, 'Transferencia', 600.00, 17, 1, 'VEN-20260624143058-23f32108'),
(217, 22, '2026-06-24 14:32:51', 'Venta', 60, NULL, 1, 'Efectivo', 100.00, 17, NULL, 'VEN-20260624143251-055e7d72'),
(218, 22, '2026-06-24 14:33:22', 'Venta', 60, NULL, 14, 'Efectivo', 1400.00, 17, NULL, 'VEN-20260624143322-d1e7f6eb'),
(219, 22, '2026-06-24 14:36:04', 'Venta', 21, NULL, 1, 'Efectivo', 1500.00, 17, NULL, 'VEN-20260624143604-2d17cf0b'),
(220, 22, '2026-06-24 14:38:42', 'Venta', 59, NULL, 1, 'Transferencia', 1200.00, 17, 1, 'VEN-20260624143842-cac4df2f'),
(221, 22, '2026-06-24 15:07:08', 'Venta', 78, NULL, 1, 'Transferencia', 300.00, 16, 1, 'VEN-20260624150708-90efde31'),
(222, 22, '2026-06-24 15:07:08', 'Venta', 79, NULL, 1, 'Transferencia', 1500.00, 16, 1, 'VEN-20260624150708-90efde31'),
(223, 23, '2026-06-24 15:58:06', 'Venta', 63, NULL, 1, 'Transferencia', 1500.00, 15, 1, 'VEN-20260624155806-dfc61eec'),
(224, 23, '2026-06-24 16:00:17', 'Venta', 20, NULL, 1, 'Efectivo', 2000.00, 13, NULL, 'VEN-20260624160017-1a305fea'),
(225, 23, '2026-06-24 16:00:17', 'Venta', 28, NULL, 1, 'Efectivo', 1800.00, 13, NULL, 'VEN-20260624160017-1a305fea'),
(226, 23, '2026-06-24 16:00:17', 'Venta', 32, NULL, 1, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260624160017-1a305fea'),
(227, 23, '2026-06-24 16:00:17', 'Venta', 36, NULL, 1, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260624160017-1a305fea'),
(228, 23, '2026-06-24 16:00:17', 'Venta', 49, NULL, 1, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260624160017-1a305fea'),
(229, 23, '2026-06-24 16:00:17', 'Venta', 63, NULL, 1, 'Efectivo', 1500.00, 13, NULL, 'VEN-20260624160017-1a305fea'),
(230, 23, '2026-06-24 16:00:17', 'Venta', 69, NULL, 1, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260624160017-1a305fea'),
(231, 23, '2026-06-24 16:01:53', 'Venta', 21, NULL, 1, 'Efectivo', 1500.00, 13, NULL, 'VEN-20260624160153-1cefff4d'),
(232, 23, '2026-06-24 16:01:53', 'Venta', 36, NULL, 1, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260624160153-1cefff4d'),
(233, 23, '2026-06-24 16:01:53', 'Venta', 52, NULL, 1, 'Efectivo', 1500.00, 13, NULL, 'VEN-20260624160153-1cefff4d'),
(234, 23, '2026-06-24 16:01:53', 'Venta', 60, NULL, 10, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260624160153-1cefff4d'),
(235, 23, '2026-06-24 16:01:53', 'Venta', 69, NULL, 1, 'Efectivo', 1000.00, 13, NULL, 'VEN-20260624160153-1cefff4d');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id` int(11) NOT NULL,
  `fecha_apertura` datetime NOT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `saldo_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_cierre` decimal(10,2) DEFAULT NULL,
  `grupo_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`id`, `fecha_apertura`, `fecha_cierre`, `saldo_inicial`, `saldo_cierre`, `grupo_id`) VALUES
(1, '2026-05-28 14:38:53', '2026-05-28 14:41:09', 10000.00, 17000.00, 2),
(2, '2026-05-29 14:47:20', '2026-05-29 14:54:55', 10000.00, 32100.00, 2),
(3, '2026-05-29 16:16:49', '2026-05-29 16:23:54', 10000.00, 13000.00, 2),
(4, '2026-05-29 16:59:31', '2026-05-29 17:00:12', 10000.00, 10000.00, 2),
(5, '2026-06-03 14:42:34', '2026-06-03 14:53:05', 5000.00, 13400.00, 1),
(6, '2026-06-03 17:42:48', '2026-06-04 14:39:49', 5000.00, 23700.00, 1),
(7, '2026-06-04 15:07:43', '2026-06-05 14:48:47', 5000.00, 5000.00, 1),
(8, '2026-06-05 16:31:28', '2026-06-05 16:36:14', 500.00, 21100.00, 1),
(9, '2026-06-05 17:13:04', '2026-06-05 17:16:19', 500.00, 500.00, 1),
(10, '2026-06-08 18:45:36', '2026-06-08 18:59:02', 10000.00, 31000.00, 1),
(11, '2026-06-10 14:50:35', '2026-06-10 14:56:25', 10000.00, 32200.00, 1),
(12, '2026-06-10 15:28:50', '2026-06-10 16:46:41', 1000.00, 12900.00, 1),
(13, '2026-06-11 14:36:59', '2026-06-11 14:37:26', 5000.00, 7000.00, 1),
(14, '2026-06-12 14:19:18', '2026-06-12 14:52:37', 10000.00, 23300.00, 1),
(15, '2026-06-12 15:13:17', '2026-06-12 15:15:14', 5000.00, 5000.00, 1),
(16, '2026-06-12 15:53:37', '2026-06-12 16:17:05', 10000.00, 16300.00, 1),
(17, '2026-06-12 16:31:18', '2026-06-12 16:32:04', 5000.00, 5000.00, 1),
(18, '2026-06-17 14:20:02', '2026-06-17 14:52:24', 10000.00, 10000.00, 1),
(19, '2026-06-17 14:20:24', '2026-06-17 14:50:48', 10000.00, 10000.00, 1),
(20, '2026-06-17 14:20:25', '2026-06-17 14:50:23', 10000.00, 23000.00, 1),
(21, '2026-06-17 15:48:33', '2026-06-17 16:16:34', 10000.00, 13900.00, 1),
(22, '2026-06-24 14:18:20', '2026-06-24 15:31:35', 10000.00, 27900.00, 1),
(23, '2026-06-24 15:51:37', '2026-06-24 16:55:11', 10000.00, 25300.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `es_admin` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `grupo_id` int(11) DEFAULT NULL,
  `super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `session_token` varchar(128) DEFAULT NULL,
  `session_token_created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password`, `telefono`, `es_admin`, `activo`, `created_at`, `grupo_id`, `super_admin`, `session_token`, `session_token_created_at`) VALUES
(1, 'Administrador', 'Sistema', 'admin@escuela.com', '$2y$10$g88Juzcp8rx1oSwsRqj66.eI3DtDJ2Blc.hzNrL4kDxgMl1b5pd7q', '', 1, 1, '2026-03-13 01:52:18', NULL, 1, NULL, NULL),
(10, 'Grupo', '1', 'grupo1@kiosco.com', '$2y$10$ozRpCxChhmCSaWsfor0IbuWS79dyrZOGDVdsb.akbmWznAmUVdIC2', '', 0, 1, '2026-04-22 18:10:58', 1, 0, '1d6c456a11cd50671c42b45e1daa9753f8da73d33358921f26de119865e12755', '2026-06-10 14:50:23'),
(12, 'Grupo1-', '2', 'grupo1-2@kiosco.com', '$2y$10$uNO5ixZ.PlRXJonr.r3UJenuyaUaoPsiFBJoBu9qrGRzkCLNJhqLa', '', 0, 1, '2026-05-29 20:48:49', 1, 0, NULL, NULL),
(13, 'Juan', 'Garcilazo', 'juangarcilazo233@gmail.com', '$2y$10$yUn/IQjrp4dxF4FFoB5/1O8ePieOqIekNaLQQEIXlurwkSpm3yiwy', '', 0, 1, '2026-06-10 18:00:30', 1, 0, '7d14f448c0caecb262732bb800faaf76c619d6b0d53fa1e0994a7026c7600250', '2026-06-24 15:51:26'),
(14, 'July', 'Diaz', 'julyydiiaz@icloud.com', '$2y$10$VnZBo2.7AIj1H7bXkmKaIuAoocZXUjXVixkURnd/3aQEh.CdFlRkG', '', 0, 1, '2026-06-10 18:09:20', 1, 0, NULL, NULL),
(15, 'Noelia', 'Burgo', 'sm225.burgo.noelia@gmail.com', '$2y$10$RW7.twFyikfdUC5voBGhUu6k3hIArtYG6WCCQDj3YbIJUQjnAwQIS', '', 0, 1, '2026-06-10 18:10:39', 1, 0, '88f4d1595897c53608333f82c913bba17f1aedad5f0010e1b4a26b9bd1fb1343', '2026-06-24 20:24:21'),
(16, 'Selene', 'Paris', 'sm225.selene.paris@gmail.com', '$2y$10$jjP8GB1nXr2MN5PLZ1taluBtkgaw372GGGAw0ANbFpFNzWa9dLTw2', '', 0, 1, '2026-06-10 18:11:39', 1, 0, '5e1c686efabbde9633d8f0821428ae2d9cd93a8671cd571e1b6c191ba03dce21', '2026-06-24 13:56:12'),
(17, 'Mailen', 'Leguiza', 'leguizamailenagustina@gmail.com', '$2y$10$kOKUKtx6CIWEvG5FrAnG6.wRv5hbW9ka6zk4tmEE9PVwEajO8kauC', '', 0, 1, '2026-06-10 18:12:30', 1, 0, '002740dded01f4fb98d0719117c6b37176599031efa551a22f0842adc1f7d53a', '2026-06-24 14:17:21'),
(18, 'Rodrigo', 'Segovia', 'sm225.segovia.rodri@gmail.com', '$2y$10$BeIKg0kqi63N84TIKQbRLOeoGUGcNA.8aMI84FJlqbTVqWNfI7ex6', '', 0, 1, '2026-06-10 18:13:32', 1, 0, '8e28441ac88e8c3c657d0dbd040bf9a025d36eb6c7869b67fbee611dc27dec2c', '2026-06-24 15:04:08');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `balance_diario`
--
ALTER TABLE `balance_diario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fecha` (`fecha`),
  ADD KEY `idx_balance_fecha` (`fecha`);

--
-- Indices de la tabla `capital_liquido`
--
ALTER TABLE `capital_liquido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_capital_fecha_tipo` (`fecha`,`tipo`),
  ADD KEY `idx_capital_usuario` (`usuario_id`);

--
-- Indices de la tabla `compras_mercaderia`
--
ALTER TABLE `compras_mercaderia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_compras_fecha_metodo` (`fecha`,`metodo_pago`),
  ADD KEY `idx_compras_usuario` (`usuario_id`);

--
-- Indices de la tabla `cuentas_pago`
--
ALTER TABLE `cuentas_pago`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_productos_nombre` (`nombre`);

--
-- Indices de la tabla `semanas_operativas`
--
ALTER TABLE `semanas_operativas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_semanas_rango` (`fecha_inicio`,`fecha_fin`),
  ADD KEY `idx_semanas_estado` (`estado`),
  ADD KEY `idx_semanas_grupo` (`grupo_id`),
  ADD KEY `fk_semanas_created_by` (`created_by`),
  ADD KEY `fk_semanas_closed_by` (`closed_by`);

--
-- Indices de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transacciones_turno` (`turno_id`),
  ADD KEY `idx_transacciones_fecha_tipo_metodo` (`fecha`,`tipo`,`metodo_pago`),
  ADD KEY `idx_transacciones_producto` (`producto_id`),
  ADD KEY `idx_transacciones_usuario` (`usuario_id`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_turnos_apertura_cierre` (`fecha_apertura`,`fecha_cierre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `balance_diario`
--
ALTER TABLE `balance_diario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `capital_liquido`
--
ALTER TABLE `capital_liquido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT de la tabla `compras_mercaderia`
--
ALTER TABLE `compras_mercaderia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cuentas_pago`
--
ALTER TABLE `cuentas_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT de la tabla `semanas_operativas`
--
ALTER TABLE `semanas_operativas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=236;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `capital_liquido`
--
ALTER TABLE `capital_liquido`
  ADD CONSTRAINT `fk_capital_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `compras_mercaderia`
--
ALTER TABLE `compras_mercaderia`
  ADD CONSTRAINT `fk_compras_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `semanas_operativas`
--
ALTER TABLE `semanas_operativas`
  ADD CONSTRAINT `fk_semanas_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_semanas_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_semanas_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`);

--
-- Filtros para la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD CONSTRAINT `fk_trans_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_trans_turno` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_trans_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
