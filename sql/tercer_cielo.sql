-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-11-2025 a las 00:05:26
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tercer_cielo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `tipo_enlace` enum('ninguno','producto','categoria') DEFAULT 'ninguno',
  `enlace_id` int(11) DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `banners`
--

INSERT INTO `banners` (`id`, `imagen`, `tipo_enlace`, `enlace_id`, `habilitado`, `orden`, `fecha_creacion`) VALUES
(1, 'uploads/banners/banner_1763558910_691dc5fe7a925.png', 'ninguno', NULL, 1, 1, '2025-11-19 13:28:30'),
(2, 'uploads/banners/banner_1763560306_691dcb72c542d.png', 'categoria', 1, 1, 2, '2025-11-19 13:51:46'),
(3, 'uploads/banners/banner_1763562124_691dd28c9e500.png', 'producto', 2, 1, 3, '2025-11-19 14:22:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `imagen`, `habilitado`) VALUES
(1, 'Herramientas', '/tercer_cielo/public/uploads/categorias/691e588f251b3_1763596431.jpg', 1),
(2, 'Pinturas', '/tercer_cielo/public/uploads/categorias/691e58b0df9f3_1763596464.jpg', 1),
(3, 'Plomería', '/tercer_cielo/public/uploads/categorias/691e58cb68902_1763596491.jpg', 1),
(4, 'Electricidad', '/tercer_cielo/public/uploads/categorias/691e586ae3555_1763596394.jpg', 1),
(5, 'Construcción', '/tercer_cielo/public/uploads/categorias/691e5834ab7ce_1763596340.png', 1),
(6, 'Cerrajería', '/tercer_cielo/public/uploads/categorias/691e57b60d704_1763596214.jpg', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `envios`
--

CREATE TABLE `envios` (
  `id` int(11) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `provincia` varchar(100) NOT NULL,
  `distrito` varchar(100) NOT NULL,
  `precio_domicilio_olva` decimal(10,2) DEFAULT 0.00,
  `precio_agencia_olva` decimal(10,2) DEFAULT 0.00,
  `precio_domicilio_shalom` decimal(10,2) DEFAULT 0.00,
  `precio_agencia_shalom` decimal(10,2) DEFAULT 0.00,
  `habilitado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `envios`
--

INSERT INTO `envios` (`id`, `departamento`, `provincia`, `distrito`, `precio_domicilio_olva`, `precio_agencia_olva`, `precio_domicilio_shalom`, `precio_agencia_shalom`, `habilitado`) VALUES
(1, 'Lima', 'Lima', 'San Isidro', 20.00, 15.00, 15.00, 10.00, 1),
(2, 'Lima', 'Lima', 'Miraflores', 20.00, 15.00, 15.00, 10.00, 1),
(3, 'Lima', 'Lima', 'Surco', 20.00, 15.00, 15.00, 10.00, 1),
(4, 'Arequipa', 'Arequipa', 'Cayma', 20.00, 15.00, 15.00, 10.00, 1),
(5, 'Arequipa', 'Arequipa', 'Yanahuara', 20.00, 15.00, 15.00, 10.00, 1),
(6, 'Cusco', 'Cusco', 'Cusco', 20.00, 15.00, 15.00, 10.00, 1),
(7, 'Cusco', 'Cusco', 'San Sebastián', 20.00, 15.00, 15.00, 10.00, 1),
(8, 'Piura', 'Sullana', 'Sullana', 20.00, 15.00, 15.00, 10.00, 1),
(9, 'Piura', 'Morropon', 'Morropon', 20.00, 15.00, 15.00, 10.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_pedidos`
--

CREATE TABLE `historial_pedidos` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `estado_anterior` varchar(50) NOT NULL DEFAULT 'pendiente',
  `estado_nuevo` varchar(50) NOT NULL,
  `fecha_cambio` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_pedidos`
--

INSERT INTO `historial_pedidos` (`id`, `pedido_id`, `estado_anterior`, `estado_nuevo`, `fecha_cambio`) VALUES
(1, 32, 'pendiente', '', '2025-11-05 00:00:49'),
(2, 32, 'pendiente', '', '2025-11-05 00:07:40'),
(3, 32, 'pendiente', 'enviado', '2025-11-05 00:11:01'),
(4, 32, 'enviado', 'entregado', '2025-11-05 00:12:10'),
(5, 31, 'pendiente', '', '2025-11-05 00:12:30'),
(6, 31, 'pendiente', 'enviado', '2025-11-05 00:15:03'),
(7, 31, 'enviado', 'entregado', '2025-11-05 00:16:07'),
(8, 30, 'pendiente', '', '2025-11-05 00:19:24'),
(9, 30, 'pendiente', 'enviado', '2025-11-05 00:20:09'),
(10, 30, 'enviado', 'entregado', '2025-11-05 00:21:40'),
(11, 29, 'pendiente', '', '2025-11-05 00:23:59'),
(12, 29, 'pendiente', 'enviado', '2025-11-05 00:24:11'),
(13, 29, 'enviado', 'entregado', '2025-11-05 00:24:23'),
(14, 28, 'pendiente', '', '2025-11-05 00:25:02'),
(15, 28, 'pendiente', 'enviado', '2025-11-05 00:25:24'),
(16, 28, 'enviado', '', '2025-11-05 00:25:35'),
(17, 28, 'pendiente', 'entregado', '2025-11-05 00:26:20'),
(18, 27, 'pendiente', '', '2025-11-05 00:28:32'),
(19, 26, 'pendiente', '', '2025-11-05 00:29:30'),
(20, 26, 'pendiente', '', '2025-11-05 00:29:43'),
(21, 26, 'pendiente', 'pendiente', '2025-11-05 00:29:52'),
(22, 26, 'pendiente', 'enviado', '2025-11-05 00:30:11'),
(23, 26, 'enviado', 'entregado', '2025-11-05 00:30:26'),
(24, 25, 'pendiente', 'aprobado', '2025-11-05 00:36:14'),
(25, 25, 'aprobado', 'enviado', '2025-11-05 00:37:15'),
(26, 25, 'enviado', 'entregado', '2025-11-05 00:38:07'),
(27, 33, 'pendiente', 'aprobado', '2025-11-05 00:47:38'),
(28, 33, 'aprobado', 'enviado', '2025-11-05 00:48:34'),
(29, 33, 'enviado', 'entregado', '2025-11-05 00:49:49'),
(30, 22, 'pendiente', 'aprobado', '2025-11-05 00:53:13'),
(31, 22, 'aprobado', 'enviado', '2025-11-05 00:53:21'),
(32, 34, 'pendiente', 'aprobado', '2025-11-05 00:55:46'),
(33, 34, 'aprobado', 'enviado', '2025-11-05 00:55:56'),
(34, 34, 'enviado', 'entregado', '2025-11-05 00:56:54'),
(35, 35, 'pendiente', 'rechazado', '2025-11-05 00:58:37'),
(36, 35, 'rechazado', 'aprobado', '2025-11-05 00:59:16'),
(37, 35, 'aprobado', 'enviado', '2025-11-05 00:59:51'),
(38, 35, 'enviado', 'entregado', '2025-11-05 01:00:30'),
(39, 36, 'pendiente', 'rechazado', '2025-11-05 19:42:33'),
(40, 37, 'pendiente', 'rechazado', '2025-11-05 19:57:50'),
(41, 38, 'pendiente', 'aprobado', '2025-11-05 22:59:10'),
(42, 39, 'pendiente', 'aprobado', '2025-11-16 20:22:45'),
(43, 39, 'aprobado', 'pendiente', '2025-11-16 20:27:30'),
(44, 39, 'pendiente', 'rechazado', '2025-11-16 20:28:32'),
(45, 39, 'rechazado', 'enviado', '2025-11-16 20:28:51'),
(47, 40, 'pendiente', 'rechazado', '2025-11-16 20:38:16'),
(49, 40, 'rechazado', 'enviado', '2025-11-16 20:39:55'),
(50, 40, 'enviado', 'entregado', '2025-11-16 20:42:05'),
(53, 41, 'pendiente', 'rechazado', '2025-11-16 21:06:32'),
(56, 43, 'pendiente', 'enviado', '2025-11-16 21:16:18'),
(62, 44, 'pendiente', 'aprobado', '2025-11-16 21:31:39'),
(63, 42, 'pendiente', 'aprobado', '2025-11-16 21:34:45'),
(64, 45, 'pendiente', 'aprobado', '2025-11-16 21:36:56'),
(65, 45, 'aprobado', 'pendiente', '2025-11-16 21:38:19'),
(66, 45, 'pendiente', 'aprobado', '2025-11-16 21:38:27'),
(67, 45, 'aprobado', 'pendiente', '2025-11-16 21:40:39'),
(68, 45, 'pendiente', 'aprobado', '2025-11-16 21:40:44'),
(69, 44, 'aprobado', 'pendiente', '2025-11-16 21:42:11'),
(70, 44, 'pendiente', 'aprobado', '2025-11-16 21:42:14'),
(71, 44, 'aprobado', 'pendiente', '2025-11-16 21:43:45'),
(72, 44, 'pendiente', 'aprobado', '2025-11-16 21:43:48'),
(73, 44, 'aprobado', 'pendiente', '2025-11-16 21:46:26'),
(74, 44, 'pendiente', 'aprobado', '2025-11-16 21:46:30'),
(75, 46, 'pendiente', 'aprobado', '2025-11-16 21:47:31'),
(76, 46, 'aprobado', 'pendiente', '2025-11-16 21:50:00'),
(77, 46, 'pendiente', 'aprobado', '2025-11-16 21:50:04'),
(78, 46, 'aprobado', 'pendiente', '2025-11-16 21:51:00'),
(79, 46, 'pendiente', 'aprobado', '2025-11-16 21:51:05'),
(80, 46, 'aprobado', 'pendiente', '2025-11-16 22:03:34'),
(81, 46, 'pendiente', 'aprobado', '2025-11-16 22:03:38'),
(82, 44, 'aprobado', 'pendiente', '2025-11-16 22:23:43'),
(83, 44, 'pendiente', 'aprobado', '2025-11-16 22:23:47'),
(84, 44, 'aprobado', 'pendiente', '2025-11-16 22:27:31'),
(89, 46, 'aprobado', 'pendiente', '2025-11-16 22:33:06'),
(92, 46, 'pendiente', 'aprobado', '2025-11-16 22:40:00'),
(93, 46, 'aprobado', 'pendiente', '2025-11-16 22:41:51'),
(95, 46, 'pendiente', 'aprobado', '2025-11-16 22:43:27'),
(96, 47, 'pendiente', 'aprobado', '2025-11-16 22:51:55'),
(97, 47, 'aprobado', 'rechazado', '2025-11-16 22:53:07'),
(98, 47, 'rechazado', 'enviado', '2025-11-16 22:53:31'),
(99, 47, 'enviado', 'entregado', '2025-11-16 22:53:57'),
(100, 48, 'pendiente', 'aprobado', '2025-11-16 23:02:55'),
(101, 48, 'aprobado', 'enviado', '2025-11-16 23:03:50'),
(102, 48, 'enviado', 'entregado', '2025-11-16 23:04:10'),
(103, 49, 'pendiente', 'aprobado', '2025-11-16 23:06:57'),
(104, 49, 'aprobado', 'pendiente', '2025-11-16 23:10:48'),
(105, 49, 'pendiente', 'entregado', '2025-11-16 23:11:06'),
(106, 49, 'entregado', 'aprobado', '2025-11-16 23:11:19'),
(107, 47, 'entregado', 'aprobado', '2025-11-16 23:12:44'),
(108, 47, 'aprobado', 'enviado', '2025-11-16 23:13:12'),
(109, 47, 'enviado', 'aprobado', '2025-11-16 23:17:05'),
(110, 47, 'aprobado', 'enviado', '2025-11-16 23:17:25'),
(111, 47, 'enviado', 'entregado', '2025-11-16 23:18:07'),
(112, 51, 'pendiente', 'aprobado', '2025-11-19 17:40:16'),
(113, 52, 'pendiente', 'rechazado', '2025-11-19 17:45:21'),
(114, 51, 'aprobado', 'enviado', '2025-11-19 17:52:09'),
(115, 51, 'enviado', 'entregado', '2025-11-19 17:52:32'),
(119, 58, 'pendiente', 'aprobado', '2025-11-19 23:22:02'),
(140, 94, 'pendiente', 'aprobado', '2025-11-19 23:55:37'),
(141, 94, 'aprobado', 'enviado', '2025-11-19 23:56:08'),
(142, 94, 'enviado', 'entregado', '2025-11-19 23:57:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id`, `id_producto`, `tipo`, `cantidad`, `fecha`) VALUES
(1, 1, 'salida', 1, '2025-10-20 22:32:21'),
(2, 1, 'salida', 1, '2025-10-20 22:32:40'),
(3, 1, 'salida', 1, '2025-10-20 22:34:17'),
(4, 1, 'salida', 1, '2025-10-23 14:12:10'),
(5, 1, 'salida', 1, '2025-10-23 14:12:35'),
(6, 2, 'salida', 1, '2025-10-29 22:26:52'),
(7, 3, 'salida', 1, '2025-10-29 22:26:52'),
(8, 21, 'entrada', 5, '2025-11-06 00:57:50'),
(9, 26, 'entrada', 2, '2025-11-17 01:28:32'),
(10, 26, 'salida', 2, '2025-11-17 01:28:51'),
(11, 5, 'entrada', 2, '2025-11-17 01:38:16'),
(13, 5, 'salida', 2, '2025-11-17 01:39:55'),
(14, 21, 'entrada', 1, '2025-11-17 02:06:32'),
(15, 32, 'entrada', 1, '2025-11-17 03:53:07'),
(16, 32, 'salida', 1, '2025-11-17 03:53:31'),
(17, 31, 'entrada', 2, '2025-11-19 22:45:21'),
(18, 1, 'entrada', 10, '2025-11-20 04:11:28'),
(19, 1, 'salida', 5, '2025-11-20 04:11:28'),
(20, 1, 'entrada', 20, '2025-11-20 04:11:28'),
(21, 1, 'salida', 10, '2025-11-20 04:11:28'),
(22, 1, 'entrada', 5, '2025-11-20 04:11:28'),
(24, 1, 'entrada', 5, '2025-11-20 04:11:28'),
(25, 1, 'entrada', 5, '2025-11-20 04:11:28'),
(26, 1, 'salida', 3, '2025-11-20 04:11:28'),
(27, 1, 'entrada', 10, '2025-11-20 04:16:01'),
(28, 1, 'salida', 5, '2025-11-20 04:16:01'),
(29, 1, 'entrada', 20, '2025-11-20 04:16:01'),
(30, 1, 'salida', 10, '2025-11-20 04:16:01'),
(31, 1, 'entrada', 5, '2025-11-20 04:16:01'),
(33, 1, 'entrada', 5, '2025-11-20 04:16:01'),
(34, 1, 'entrada', 5, '2025-11-20 04:16:01'),
(35, 1, 'salida', 3, '2025-11-20 04:16:01'),
(36, 1, 'entrada', 10, '2025-11-20 04:16:15'),
(37, 1, 'salida', 5, '2025-11-20 04:16:15'),
(38, 1, 'entrada', 20, '2025-11-20 04:16:15'),
(39, 1, 'salida', 10, '2025-11-20 04:16:15'),
(40, 1, 'entrada', 5, '2025-11-20 04:16:15'),
(42, 1, 'entrada', 5, '2025-11-20 04:16:15'),
(43, 1, 'entrada', 5, '2025-11-20 04:16:15'),
(44, 1, 'salida', 3, '2025-11-20 04:16:15'),
(45, 1, 'entrada', 10, '2025-11-20 04:21:40'),
(46, 1, 'salida', 5, '2025-11-20 04:21:40'),
(47, 1, 'entrada', 20, '2025-11-20 04:21:40'),
(48, 1, 'salida', 10, '2025-11-20 04:21:40'),
(49, 1, 'entrada', 5, '2025-11-20 04:21:40'),
(51, 1, 'entrada', 5, '2025-11-20 04:21:40'),
(52, 1, 'entrada', 5, '2025-11-20 04:21:40'),
(53, 1, 'salida', 3, '2025-11-20 04:21:40'),
(54, 1, 'entrada', 10, '2025-11-20 04:21:52'),
(55, 1, 'salida', 5, '2025-11-20 04:21:52'),
(56, 1, 'entrada', 20, '2025-11-20 04:21:52'),
(57, 1, 'salida', 10, '2025-11-20 04:21:52'),
(58, 1, 'entrada', 5, '2025-11-20 04:21:52'),
(60, 1, 'entrada', 5, '2025-11-20 04:21:52'),
(61, 1, 'entrada', 5, '2025-11-20 04:21:52'),
(62, 1, 'salida', 3, '2025-11-20 04:21:52'),
(63, 1, 'entrada', 10, '2025-11-20 04:23:16'),
(64, 1, 'salida', 5, '2025-11-20 04:23:16'),
(65, 1, 'entrada', 20, '2025-11-20 04:23:16'),
(66, 1, 'salida', 10, '2025-11-20 04:23:16'),
(67, 1, 'entrada', 5, '2025-11-20 04:23:16'),
(69, 1, 'entrada', 5, '2025-11-20 04:23:16'),
(70, 1, 'entrada', 5, '2025-11-20 04:23:16'),
(71, 1, 'salida', 3, '2025-11-20 04:23:16'),
(72, 1, 'entrada', 10, '2025-11-20 04:23:30'),
(73, 1, 'salida', 5, '2025-11-20 04:23:30'),
(74, 1, 'entrada', 20, '2025-11-20 04:23:30'),
(75, 1, 'salida', 10, '2025-11-20 04:23:30'),
(76, 1, 'entrada', 5, '2025-11-20 04:23:30'),
(78, 1, 'entrada', 5, '2025-11-20 04:23:30'),
(79, 1, 'entrada', 5, '2025-11-20 04:23:30'),
(80, 1, 'salida', 3, '2025-11-20 04:23:30'),
(81, 1, 'entrada', 10, '2025-11-20 04:26:26'),
(82, 1, 'salida', 5, '2025-11-20 04:26:26'),
(83, 1, 'entrada', 20, '2025-11-20 04:26:26'),
(84, 1, 'salida', 10, '2025-11-20 04:26:26'),
(85, 1, 'entrada', 5, '2025-11-20 04:26:26'),
(87, 1, 'entrada', 5, '2025-11-20 04:26:26'),
(88, 1, 'entrada', 5, '2025-11-20 04:26:26'),
(89, 1, 'salida', 3, '2025-11-20 04:26:26'),
(90, 1, 'entrada', 10, '2025-11-20 04:26:39'),
(91, 1, 'salida', 5, '2025-11-20 04:26:39'),
(92, 1, 'entrada', 20, '2025-11-20 04:26:39'),
(93, 1, 'salida', 10, '2025-11-20 04:26:39'),
(94, 1, 'entrada', 5, '2025-11-20 04:26:39'),
(96, 1, 'entrada', 5, '2025-11-20 04:26:39'),
(97, 1, 'entrada', 5, '2025-11-20 04:26:39'),
(98, 1, 'salida', 3, '2025-11-20 04:26:39'),
(99, 1, 'entrada', 10, '2025-11-20 04:29:23'),
(100, 1, 'salida', 5, '2025-11-20 04:29:23'),
(101, 1, 'entrada', 20, '2025-11-20 04:29:23'),
(102, 1, 'salida', 10, '2025-11-20 04:29:23'),
(103, 1, 'entrada', 5, '2025-11-20 04:29:23'),
(105, 1, 'entrada', 5, '2025-11-20 04:29:23'),
(106, 1, 'entrada', 5, '2025-11-20 04:29:23'),
(107, 1, 'salida', 3, '2025-11-20 04:29:23'),
(108, 1, 'entrada', 10, '2025-11-20 04:30:22'),
(109, 1, 'salida', 5, '2025-11-20 04:30:22'),
(110, 1, 'entrada', 20, '2025-11-20 04:30:22'),
(111, 1, 'salida', 10, '2025-11-20 04:30:22'),
(112, 1, 'entrada', 5, '2025-11-20 04:30:22'),
(114, 1, 'entrada', 5, '2025-11-20 04:30:22'),
(115, 1, 'entrada', 5, '2025-11-20 04:30:22'),
(116, 1, 'salida', 3, '2025-11-20 04:30:22'),
(117, 1, 'entrada', 10, '2025-11-20 04:30:39'),
(118, 1, 'salida', 5, '2025-11-20 04:30:39'),
(119, 1, 'entrada', 20, '2025-11-20 04:30:39'),
(120, 1, 'salida', 10, '2025-11-20 04:30:39'),
(121, 1, 'entrada', 5, '2025-11-20 04:30:39'),
(123, 1, 'entrada', 5, '2025-11-20 04:30:39'),
(124, 1, 'entrada', 5, '2025-11-20 04:30:39'),
(125, 1, 'salida', 3, '2025-11-20 04:30:39'),
(126, 1, 'entrada', 10, '2025-11-20 04:31:37'),
(127, 1, 'salida', 5, '2025-11-20 04:31:37'),
(128, 1, 'entrada', 20, '2025-11-20 04:31:37'),
(129, 1, 'salida', 10, '2025-11-20 04:31:37'),
(130, 1, 'entrada', 5, '2025-11-20 04:31:37'),
(132, 1, 'entrada', 5, '2025-11-20 04:31:37'),
(133, 1, 'entrada', 5, '2025-11-20 04:31:37'),
(134, 1, 'salida', 3, '2025-11-20 04:31:37'),
(135, 1, 'entrada', 10, '2025-11-20 04:31:50'),
(136, 1, 'salida', 5, '2025-11-20 04:31:50'),
(137, 1, 'entrada', 20, '2025-11-20 04:31:50'),
(138, 1, 'salida', 10, '2025-11-20 04:31:50'),
(139, 1, 'entrada', 5, '2025-11-20 04:31:50'),
(141, 1, 'entrada', 5, '2025-11-20 04:31:50'),
(142, 1, 'entrada', 5, '2025-11-20 04:31:50'),
(143, 1, 'salida', 3, '2025-11-20 04:31:50'),
(144, 1, 'entrada', 10, '2025-11-20 04:35:34'),
(145, 1, 'salida', 5, '2025-11-20 04:35:34'),
(146, 1, 'entrada', 20, '2025-11-20 04:35:34'),
(147, 1, 'salida', 10, '2025-11-20 04:35:34'),
(148, 1, 'entrada', 5, '2025-11-20 04:35:34'),
(150, 1, 'entrada', 5, '2025-11-20 04:35:34'),
(151, 1, 'entrada', 5, '2025-11-20 04:35:34'),
(152, 1, 'salida', 3, '2025-11-20 04:35:34'),
(153, 1, 'entrada', 10, '2025-11-20 05:04:20'),
(154, 1, 'salida', 5, '2025-11-20 05:04:21'),
(155, 1, 'entrada', 20, '2025-11-20 05:04:21'),
(156, 1, 'salida', 10, '2025-11-20 05:04:21'),
(157, 1, 'entrada', 5, '2025-11-20 05:04:21'),
(159, 1, 'entrada', 5, '2025-11-20 05:04:21'),
(160, 1, 'entrada', 5, '2025-11-20 05:04:21'),
(161, 1, 'salida', 3, '2025-11-20 05:04:21'),
(162, 1, 'entrada', 10, '2025-11-20 05:05:03'),
(163, 1, 'salida', 5, '2025-11-20 05:05:03'),
(164, 1, 'entrada', 20, '2025-11-20 05:05:03'),
(165, 1, 'salida', 10, '2025-11-20 05:05:03'),
(166, 1, 'entrada', 5, '2025-11-20 05:05:03'),
(168, 1, 'entrada', 5, '2025-11-20 05:05:03'),
(169, 1, 'entrada', 5, '2025-11-20 05:05:03'),
(170, 1, 'salida', 3, '2025-11-20 05:05:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `metodo_pago` enum('yape','plin','contraentrega','transferencia') DEFAULT NULL,
  `metodo_envio` enum('envio','recojo') DEFAULT 'envio',
  `tipo_envio` varchar(20) DEFAULT 'domicilio',
  `agencia_envio` varchar(20) DEFAULT 'olva',
  `direccion_envio` text DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `comprobante` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente_pago','pendiente','aprobado','rechazado','enviado','entregado') DEFAULT 'pendiente',
  `creado_en` datetime DEFAULT current_timestamp(),
  `precio_envio` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `codigo`, `usuario_id`, `total`, `metodo_pago`, `metodo_envio`, `tipo_envio`, `agencia_envio`, `direccion_envio`, `telefono`, `comprobante`, `estado`, `creado_en`, `precio_envio`) VALUES
(2, 'PED-2025-001', 95, 15.50, '', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-001.jpg', 'pendiente_pago', '2025-10-30 01:40:06', 0.00),
(3, 'PED-2025-002', 95, 5.00, '', 'envio', 'domicilio', 'olva', 'direccion de prueba', '966535611', 'uploads/comprobantes/PED-2025-002.jpg', 'pendiente_pago', '2025-10-30 08:01:31', 0.00),
(4, 'PED-2025-003', 95, 497.00, '', 'envio', 'domicilio', 'olva', 'direccion de prueba, Miraflores', '966535611', 'uploads/comprobantes/PED-2025-003.jpg', 'pendiente_pago', '2025-10-30 08:26:40', 0.00),
(5, 'PED-2025-004', 95, 27.50, '', 'envio', 'domicilio', 'olva', 'direccion de prueba, Cayma', '966535611', 'uploads/comprobantes/PED-2025-004.jpg', 'pendiente_pago', '2025-10-30 08:30:32', 0.00),
(6, 'PED-2025-005', 101, 2.50, '', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-005.jpg', 'pendiente_pago', '2025-10-30 09:54:07', 0.00),
(7, 'PED-2025-006', 101, 40.50, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, San Sebastián', '966535611', 'uploads/comprobantes/PED-2025-006.jpg', 'pendiente_pago', '2025-10-30 10:02:48', 0.00),
(8, 'PED-2025-007', 101, 18.00, '', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-007.jpg', 'pendiente_pago', '2025-10-30 10:06:37', 0.00),
(9, 'PED-2025-008', 101, 27.50, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-008.jpg', 'pendiente_pago', '2025-10-30 10:07:39', 0.00),
(10, 'PED-2025-009', 101, 37.50, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cayma', '948583748', 'uploads/comprobantes/PED-2025-009.jpg', 'pendiente_pago', '2025-10-30 10:36:18', 0.00),
(11, 'PED-2025-010', 101, 70.00, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cayma', '948583748', 'uploads/comprobantes/PED-2025-010.jpg', 'pendiente_pago', '2025-10-30 10:36:51', 0.00),
(12, 'PED-2025-011', 101, 27.50, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-011.jpg', 'pendiente_pago', '2025-10-30 10:47:17', 0.00),
(13, 'PED-2025-012', 101, 70.00, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-012.jpg', 'pendiente_pago', '2025-10-30 10:53:59', 0.00),
(14, 'PED-2025-013', 101, 45.00, '', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-013.jpg', 'pendiente_pago', '2025-10-30 10:57:39', 0.00),
(15, 'PED-2025-014', 101, 33.00, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-014.jpg', 'pendiente_pago', '2025-10-30 10:58:55', 0.00),
(16, 'PED-2025-015', 101, 50.50, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cusco', '966535611', 'uploads/comprobantes/PED-2025-015.jpg', 'pendiente_pago', '2025-10-30 11:03:36', 0.00),
(17, 'PED-2025-016', 101, 15.50, '', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-016.jpg', 'pendiente_pago', '2025-10-30 11:04:31', 0.00),
(18, 'PED-2025-017', 101, 15.50, '', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-017.jpg', 'pendiente_pago', '2025-10-30 11:09:35', 0.00),
(19, 'PED-2025-018', 101, 1198.00, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Arequipa, Arequipa, Yanahuara', '966535611', 'uploads/comprobantes/PED-2025-018.jpg', 'pendiente_pago', '2025-10-30 11:10:14', 0.00),
(20, 'PED-2025-019', 101, 2.50, '', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-019.jpg', 'pendiente_pago', '2025-10-30 11:30:15', 0.00),
(21, 'PED-2025-020', 101, 37.50, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cusco, Cusco, Cusco', '966535611', 'uploads/comprobantes/PED-2025-020.jpg', 'pendiente_pago', '2025-10-30 11:37:04', 0.00),
(22, 'PED-2025-021', 101, 2.50, '', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-021.jpg', 'enviado', '2025-10-30 11:42:39', 0.00),
(23, 'PED-2025-022', 101, 30.50, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Arequipa, Arequipa, Yanahuara', '966535611', 'uploads/comprobantes/PED-2025-022.jpg', 'pendiente_pago', '2025-10-30 12:28:26', 0.00),
(24, 'PED-2025-023', 95, 120.00, '', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-023.jpg', 'pendiente_pago', '2025-11-04 18:53:31', 0.00),
(25, 'PED-2025-024', 95, 8.00, '', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-024.jpg', 'entregado', '2025-11-04 19:01:30', 0.00),
(26, 'PED-2025-025', 95, 37.50, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cusco, Cusco, Cusco', '966535611', 'uploads/comprobantes/PED-2025-025.jpg', 'entregado', '2025-11-04 19:03:46', 0.00),
(27, 'PED-2025-026', 95, 45.00, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cusco, Cusco, Cusco', '966535611', 'uploads/comprobantes/PED-2025-026.jpg', 'aprobado', '2025-11-04 19:09:27', 35.00),
(28, 'PED-2025-027', 95, 59.00, '', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cusco, Cusco, Cusco', '966535611', 'uploads/comprobantes/PED-2025-027.jpg', 'entregado', '2025-11-04 19:16:22', 35.00),
(29, 'PED-2025-028', 95, 268.00, 'yape', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Arequipa, Arequipa, Yanahuara', '966535611', 'uploads/comprobantes/PED-2025-028.jpg', 'entregado', '2025-11-04 19:23:44', 28.00),
(30, 'PED-2025-029', 95, 8.00, 'plin', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-029.jpg', 'entregado', '2025-11-04 19:25:30', 0.00),
(31, 'PED-2025-030', 95, 27.50, 'plin', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Arequipa, Arequipa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-030.jpg', 'entregado', '2025-11-04 19:26:12', 25.00),
(32, 'PED-2025-031', 95, 49.00, 'yape', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Arequipa, Arequipa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-031.jpg', 'entregado', '2025-11-04 23:37:39', 25.00),
(33, 'PED-2025-032', 95, 106.00, 'plin', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Cusco, Cusco, Cusco', '966535611', 'uploads/comprobantes/PED-2025-032.jpg', 'entregado', '2025-11-05 00:46:41', 35.00),
(34, 'PED-2025-033', 95, 33.00, 'yape', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Arequipa, Arequipa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-033.jpg', 'entregado', '2025-11-05 00:55:19', 25.00),
(35, 'PED-2025-034', 95, 45.00, 'yape', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-034.jpg', 'entregado', '2025-11-05 00:58:13', 0.00),
(36, 'PED-2025-035', 95, 764.50, 'plin', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Lima, Lima, Surco', '966535611', 'uploads/comprobantes/PED-2025-035.jpg', 'rechazado', '2025-11-05 19:40:40', 15.00),
(37, 'PED-2025-036', 95, 749.50, 'yape', 'recojo', 'domicilio', 'olva', 'Av. Principal 123, San Isidro, Lima - Ref: Frente al Parque Kennedy', '966535611', 'uploads/comprobantes/PED-2025-036.jpg', 'rechazado', '2025-11-05 19:56:41', 0.00),
(38, 'PED-2025-037', 95, 2.50, 'yape', 'recojo', 'domicilio', 'olva', 'Av. Guardia Civil mza. A lote. 1 urb. Villa Universitaria (Frente a. Violeta Ruesta) Piura - Piura - Castilla', '966535611', 'uploads/comprobantes/PED-2025-037.jpg', 'aprobado', '2025-11-05 22:55:19', 0.00),
(39, 'PED-2025-038', 95, 168.00, 'yape', 'envio', 'agencia', 'shalom', 'Recojo en agencia Shalom - Cusco, Cusco, Cusco', '966535611', 'uploads/comprobantes/PED-2025-038.jpg', 'enviado', '2025-11-16 20:15:11', 10.00),
(40, 'PED-2025-039', 95, 20.00, 'plin', 'envio', 'domicilio', 'shalom', 'Calle miguel checa, Arequipa, Arequipa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-039.jpg', 'entregado', '2025-11-16 20:34:58', 15.00),
(41, 'PED-2025-040', 95, 164.90, 'plin', 'envio', 'agencia', 'olva', 'Cusco, Cusco, Cusco', '966535611', 'uploads/comprobantes/PED-2025-040.jpg', 'rechazado', '2025-11-16 20:43:33', 15.00),
(42, 'PED-2025-041', 95, 793.60, 'yape', 'envio', 'agencia', 'shalom', 'Arequipa, Arequipa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-041.jpg', 'aprobado', '2025-11-16 21:04:52', 10.00),
(43, 'PED-2025-042', 95, 8.90, 'yape', 'recojo', 'tienda', '', 'Av. Guardia Civil mza. A lote. 1 urb. Villa Universitaria (Frente a. Violeta Ruesta) Piura - Piura - Castilla', '966535611', 'uploads/comprobantes/PED-2025-042.jpg', 'enviado', '2025-11-16 21:14:58', 0.00),
(44, 'PED-2025-043', 95, 22.50, 'yape', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Arequipa, Arequipa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-043.jpg', 'pendiente', '2025-11-16 21:27:19', 20.00),
(45, 'PED-2025-044', 95, 234.90, 'yape', 'envio', 'agencia', 'olva', 'Arequipa, Arequipa, Yanahuara', '966535611', 'uploads/comprobantes/PED-2025-044.jpg', 'aprobado', '2025-11-16 21:36:41', 15.00),
(46, 'PED-2025-045', 95, 59.90, 'yape', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Piura, Morropon, Morropon', '966535611', 'uploads/comprobantes/PED-2025-045.jpg', 'aprobado', '2025-11-16 21:47:19', 20.00),
(47, 'PED-2025-046', 95, 234.90, 'yape', 'envio', 'agencia', 'olva', 'Arequipa, Arequipa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-046.jpg', 'entregado', '2025-11-16 22:50:00', 15.00),
(48, 'PED-2025-047', 95, 39.00, 'yape', 'envio', 'domicilio', 'shalom', 'Calle miguel checa, Cusco, Cusco, Cusco', '966535611', 'uploads/comprobantes/PED-2025-047.jpg', 'entregado', '2025-11-16 23:01:39', 15.00),
(49, 'PED-2025-048', 95, 5.00, 'yape', 'recojo', 'tienda', '', 'Av. Guardia Civil mza. A lote. 1 urb. Villa Universitaria (Frente a. Violeta Ruesta) Piura - Piura - Castilla', '966535611', 'uploads/comprobantes/PED-2025-048.jpg', 'aprobado', '2025-11-16 23:05:58', 0.00),
(50, 'PED-2025-049', 95, 94.00, 'yape', 'envio', 'agencia', 'olva', 'Piura, Sullana, Sullana', '966535611', 'uploads/comprobantes/PED-2025-049.jpg', 'pendiente_pago', '2025-11-19 07:45:21', 15.00),
(51, 'PED-2025-050', 95, 32.80, 'plin', 'envio', 'agencia', 'olva', 'Arequipa, Arequipa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-050.png', 'entregado', '2025-11-19 17:38:26', 15.00),
(52, 'PED-2025-051', 95, 32.80, 'yape', 'envio', 'domicilio', 'shalom', 'Calle miguel checa, Cusco, Cusco, Cusco', '966535611', 'uploads/comprobantes/PED-2025-051.jpg', 'rechazado', '2025-11-19 17:41:58', 15.00),
(58, NULL, 2, 100.00, NULL, 'envio', 'domicilio', 'olva', NULL, NULL, NULL, 'aprobado', '2025-11-19 23:22:02', 0.00),
(94, 'PED-2025-053', 95, 298.70, 'yape', 'envio', 'domicilio', 'olva', 'Calle miguel checa, Arequipa, Arequipa, Cayma', '966535611', 'uploads/comprobantes/PED-2025-053.jpg', 'entregado', '2025-11-19 23:54:51', 20.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_detalles`
--

CREATE TABLE `pedido_detalles` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido_detalles`
--

INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `nombre`, `precio`, `cantidad`) VALUES
(2, 2, 4, 'Tubo PVC', 8.00, 1),
(3, 2, 5, 'Cable Eléctrico', 2.50, 3),
(4, 3, 5, 'Cable Eléctrico', 2.50, 2),
(5, 4, 5, 'Cable Eléctrico', 2.50, 194),
(6, 5, 5, 'Cable Eléctrico', 2.50, 1),
(7, 6, 5, 'Cable Eléctrico', 2.50, 1),
(8, 7, 5, 'Cable Eléctrico', 2.50, 1),
(9, 8, 5, 'Cable Eléctrico', 2.50, 1),
(10, 8, 1, 'Martillo', 15.50, 1),
(11, 9, 5, 'Cable Eléctrico', 2.50, 1),
(12, 10, 5, 'Cable Eléctrico', 2.50, 5),
(13, 11, 3, 'Pintura Blanca', 45.00, 1),
(14, 12, 5, 'Cable Eléctrico', 2.50, 1),
(15, 13, 3, 'Pintura Blanca', 45.00, 1),
(16, 14, 3, 'Pintura Blanca', 45.00, 1),
(17, 15, 4, 'Tubo PVC', 8.00, 1),
(18, 16, 1, 'Martillo', 15.50, 1),
(19, 17, 1, 'Martillo', 15.50, 1),
(20, 18, 1, 'Martillo', 15.50, 1),
(21, 19, 3, 'Pintura Blanca', 45.00, 26),
(22, 20, 5, 'Cable Eléctrico', 2.50, 1),
(23, 21, 5, 'Cable Eléctrico', 2.50, 1),
(24, 22, 5, 'Cable Eléctrico', 2.50, 1),
(25, 23, 5, 'Cable Eléctrico', 2.50, 1),
(26, 24, 2, 'Taladro', 120.00, 1),
(27, 25, 4, 'Tubo PVC', 8.00, 1),
(28, 26, 5, 'Cable Eléctrico', 2.50, 1),
(29, 27, 5, 'Cable Eléctrico', 2.50, 4),
(30, 28, 4, 'Tubo PVC', 8.00, 3),
(31, 29, 2, 'Taladro', 120.00, 2),
(32, 30, 4, 'Tubo PVC', 8.00, 1),
(33, 31, 5, 'Cable Eléctrico', 2.50, 1),
(34, 32, 4, 'Tubo PVC', 8.00, 3),
(35, 33, 5, 'Cable Eléctrico', 2.50, 1),
(36, 33, 4, 'Tubo PVC', 8.00, 1),
(37, 33, 3, 'Pintura Blanca', 45.00, 1),
(38, 33, 1, 'Martillo', 15.50, 1),
(39, 34, 4, 'Tubo PVC', 8.00, 1),
(40, 35, 3, 'Pintura Blanca', 45.00, 1),
(41, 36, 21, 'Atornillador Inalámbrico Bosch 1/4\" 3.6 V', 149.90, 5),
(42, 37, 21, 'Atornillador Inalámbrico Bosch 1/4\" 3.6 V', 149.90, 5),
(43, 38, 5, 'Cable Eléctrico', 2.50, 1),
(44, 39, 26, 'Arena Fina Seleccionada – m³', 79.00, 2),
(45, 40, 5, 'Cable Eléctrico de Cobre 2.5mm² Calibre 14', 2.50, 2),
(46, 41, 21, 'Atornillador Inalámbrico Bosch 1/4\" 3.6 V', 149.90, 1),
(47, 42, 22, 'Candado Laminado Acero 68.2 mm', 39.90, 1),
(48, 42, 5, 'Cable Eléctrico de Cobre 2.5mm² Calibre 14', 2.50, 2),
(49, 42, 26, 'Arena Fina Seleccionada – m³', 79.00, 1),
(50, 42, 32, 'Carretilla de acero 90 L – rueda neumática', 219.90, 3),
(51, 43, 31, 'Clavos para concreto 1″ – caja 1 kg', 8.90, 1),
(52, 44, 5, 'Cable Eléctrico de Cobre 2.5mm² Calibre 14', 2.50, 1),
(53, 45, 32, 'Carretilla de acero 90 L – rueda neumática', 219.90, 1),
(54, 46, 22, 'Candado Laminado Acero 68.2 mm', 39.90, 1),
(55, 47, 32, 'Carretilla de acero 90 L – rueda neumática', 219.90, 1),
(56, 48, 4, 'Tubo PVC de Presión 1\" (1 pulgada)', 8.00, 3),
(57, 49, 5, 'Cable Eléctrico de Cobre 2.5mm² Calibre 14', 2.50, 2),
(58, 50, 26, 'Arena Fina Seleccionada – m³', 79.00, 1),
(59, 51, 31, 'Clavos para concreto 1″ – caja 1 kg', 8.90, 2),
(60, 52, 31, 'Clavos para concreto 1″ – caja 1 kg', 8.90, 2),
(76, 94, 32, 'Carretilla de acero 90 L – rueda neumática', 219.90, 1),
(77, 94, 31, 'Clavos para concreto 1″ – caja 1 kg', 8.90, 1),
(78, 94, 30, 'Panel Drywall 1.20 × 2.40 m – espesor 12.5 mm', 49.90, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `porcentaje_descuento` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `id_categoria` int(11) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `precio`, `precio_anterior`, `porcentaje_descuento`, `stock`, `id_categoria`, `imagen`, `habilitado`) VALUES
(1, 'Martillo', 'Martillo de punta fina', 15.50, NULL, NULL, 50, 1, '/tercer_cielo/public/uploads/productos/691e5bc74b8d3_1763597255.png', 1),
(2, 'Taladro Eléctrico de Impacto 500W', 'Taladro eléctrico de 500W con función de impacto, ideal para perforar madera, metal y concreto. Diseño ergonómico, gatillo de velocidad variable y mandril de 13 mm para mayor control y precisión.', 96.00, 120.00, 20, 5, 1, '/tercer_cielo/public/uploads/productos/691e5c70168a6_1763597424.jpg', 1),
(3, 'Pintura Blanca Acrílica Satinada 4L', 'Pintura blanca acrílica de acabado satinado, excelente cobertura y resistencia. Ideal para interiores y exteriores, seca rápido y ofrece una superficie lavable y duradera. Presentación en lata de 4 litros.', 45.00, NULL, NULL, 0, 2, '/tercer_cielo/public/uploads/productos/691e5c44acc27_1763597380.jpg', 1),
(4, 'Tubo PVC de Presión 1\" (1 pulgada)', 'Tubo de PVC rígido de 1 pulgada de diámetro, ideal para conducción de agua potable, desagües o instalaciones sanitarias. Resistente a la corrosión, de alta durabilidad y fácil instalación.', 8.00, NULL, NULL, 85, 3, '/tercer_cielo/public/uploads/productos/691e5c891eae5_1763597449.jpg', 1),
(5, 'Cable Eléctrico de Cobre 2.5mm² Calibre 14', 'Cable eléctrico de cobre flexible de 2.5 mm², ideal para instalaciones residenciales e industriales. Ofrece excelente conductividad y resistencia al calor, garantizando seguridad y durabilidad en sistemas eléctricos.', 2.50, NULL, NULL, 70, 4, '/tercer_cielo/public/uploads/productos/691e5a468b97d_1763596870.jpg', 1),
(21, 'Atornillador Inalámbrico Bosch 1/4\" 3.6 V', 'El Atornillador Bosch Go inalámbrico de 3,6V BIVOLT con batería integrada y autonomía de 1,5Ah es la evolución del destornillador manual! El primer atornillador inteligente del mundo con el innovador Sistema Push&Go.', 149.90, NULL, NULL, 15, 1, '/tercer_cielo/public/uploads/productos/691e5a21c93d2_1763596833.jpg', 1),
(22, 'Candado Laminado Acero 68.2 mm', 'Protege tus pertenencias con el Candado Laminado SM Modelo QC0265 de 68.2mm. Este resistente candado de acero laminado con arco endurecido cromado es ideal para casetas, rejas, puertas metálicas y más. Con un mecanismo de bloqueo de pines y 2 llaves niqueladas, garantiza seguridad y durabilidad. Su acabado brillante lo hace perfecto para exteriores, con nivel de corrosión medio. Con dimensiones de 6.8x9.5x3 cm y peso de 0.647 kg, es fácil de transportar y usar. ¡Protege tus espacios con este candado confiable!', 39.90, NULL, NULL, 8, 6, '/tercer_cielo/public/uploads/productos/691e5add77768_1763597021.jpg', 1),
(23, 'Cemento Portland Tipo I – Bolsa 42.5 kg', 'Cemento Portland tipo I de uso estructural, clase 42.5N, endurecimiento normal, diámetro de clinker ≤ 3 % y finura mínima 320 m²/kg. Ideal para hormigón armado en muros, losas y columnas.', 32.50, NULL, NULL, 80, 5, '/tercer_cielo/public/uploads/productos/691e5b037fe7f_1763597059.jpg', 1),
(24, 'Ladrillo Cerámico “King Kong” 18 huecos – unidad', 'Ladrillo cerámico de alta densidad (~1,800 kg/m³), 18 huecos de 40 mm, resistencia a compresión ≥ 8 MPa, absorbción ≤ 15 %. Para muros portantes y divisiones internas.', 1.20, NULL, NULL, 500, 5, '/tercer_cielo/public/uploads/productos/691e5b8f65f9d_1763597199.jpg', 1),
(25, 'Varilla de acero corrugado Ø 3/8″ (9 m)', 'Varilla de acero corrugado para refuerzo de hormigón, Ø 3/8″ (≈10 mm), módulo de elasticidad 200 GPa, adherencia según norma ACI. Longitud 9 m estándar.', 39.90, NULL, NULL, 200, 5, '/tercer_cielo/public/uploads/productos/691e5cc8730c3_1763597512.jpg', 1),
(26, 'Arena Fina Seleccionada – m³', 'Arena fina lavada para mezcla de concreto y mortero, contenido de finos ≤ 10 %, libre de arcillas y materia orgánica. Ideal para tarrajeos y concretos de acabado.', 79.00, NULL, NULL, 36, 5, '/tercer_cielo/public/uploads/productos/691e56fa09e94_1763596026.jpg', 1),
(27, 'Yeso en polvo – Bolsa 25 kg', 'Yeso en polvo tipo hemihidratado, pureza ≥ 95 %, tiempo de fraguado ~25 min, retención de agua ~60%. Para enlucidos y molduras interiores.', 27.90, NULL, NULL, 35, 5, '/tercer_cielo/public/uploads/productos/691e5ce87c94e_1763597544.jpg', 1),
(28, 'Malla electrosoldada 6 mm – panel 3×2 m', 'Malla electrosoldada de alambre Ø 6 mm, celda 150×150 mm, acero galvanizado Zn100 g/m², para refuerzo de losas y placas de concreto. Resistencia ≥ 550 MPa.', 189.00, NULL, NULL, 25, 5, '/tercer_cielo/public/uploads/productos/691e5baa2f567_1763597226.jpg', 1),
(29, 'Clavos de acero 2″ – caja 1 kg', 'Clavos de acero templado tipo vestir, longitud 2″ (~50 mm), cabeza plana de 20 mm, punta diamantada, acabado electrocincado para resistencia a la corrosión. Uso en carpintería y estructuras de madera.', 5.90, NULL, NULL, 100, 5, '/tercer_cielo/public/uploads/productos/691e5b3aa4a4c_1763597114.jpg', 1),
(30, 'Panel Drywall 1.20 × 2.40 m – espesor 12.5 mm', 'Panel de yeso laminado (drywall) estándar, dimensiones 1.20 m × 2.40 m, espesor 12.5 mm, núcleo yeso modificado, capa de papel específico. Para divisiones interiores y cielos rasos.', 49.90, NULL, NULL, 49, 5, '/tercer_cielo/public/uploads/productos/691e5c01d3048_1763597313.jpg', 1),
(31, 'Clavos para concreto 1″ – caja 1 kg', 'Clavos para concreto, acero templado, longitud 1″ (~25 mm), punta de expansión, recubrimiento galvanizado, recomendados para fijar anclajes ligeros en hormigón.', 8.90, NULL, NULL, 76, 5, '/tercer_cielo/public/uploads/productos/691e5b56d59c0_1763597142.jpg', 1),
(32, 'Carretilla de acero 90 L – rueda neumática', 'Carretilla de acero pintado, capacidad 90 litros, bandeja dimensional 780×430 mm, rueda neumática Ø 400 mm, altura de trabajo 950 mm. Ideal para transporte de materiales de obra.', 219.90, NULL, NULL, 6, 5, '/tercer_cielo/public/uploads/productos/691e575d39a4c_1763596125.jpg', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_imagenes`
--

CREATE TABLE `producto_imagenes` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `url_imagen` varchar(255) NOT NULL,
  `orden` int(11) DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto_imagenes`
--

INSERT INTO `producto_imagenes` (`id`, `producto_id`, `url_imagen`, `orden`, `creado_en`) VALUES
(5, 2, '/tercer_cielo/public/uploads/productos/691e5e4f82940_1763597903.png', 1, '2025-11-20 00:18:25'),
(6, 2, '/tercer_cielo/public/uploads/productos/691e5e58e48f1_1763597912.png', 2, '2025-11-20 00:18:33'),
(7, 2, '/tercer_cielo/public/uploads/productos/691e5e5ddfb66_1763597917.png', 3, '2025-11-20 00:18:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `rol` enum('admin','cliente') DEFAULT 'cliente',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `session_id` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `telefono`, `password`, `rol`, `created_at`, `reset_token`, `reset_expires`, `activo`, `session_id`) VALUES
(1, 'Admin Tercer Cielo', 'admin@tercercielo.com', '987654321', '$2y$10$jUngHrg9hHKjvEhye9X1XeKPUiHx.2La43fBK0n9ipwpe/5sEayim', 'admin', '2025-10-20 21:06:01', NULL, NULL, 1, 'fttmn308d36dc9t80flg03sojg'),
(2, 'Cliente Test', 'cliente@test.com', '123456789', '$2y$10$Y24caBznFTVcCgY8g16V0OjmLVfF44Qgl3K8X.OJ93Wbp9ALTOlXa', 'cliente', '2025-11-20 04:16:02', NULL, NULL, 1, NULL),
(95, 'Jeferson David Rueda Chumacero', 'jedarchdj@gmail.com', '966535611', '$2y$10$XA0a1mtHebiQWs/kM/aSMOCHl37jGmqdtbUYDR2Ev.X2U9sFmVDIa', 'cliente', '2025-10-29 21:58:51', 'c4b6e72c358101632b84573901e2aeffb82d04ac9a5729db0d70afa454b56bc0', '2025-11-17 05:01:31', 1, NULL),
(101, 'jeferson rueda', 'jeferson.rueda2004@gmail.com', '966535611', '$2y$10$T1uWvDRPI2/FRF/4qHedzeb8GQtepjzOn8tX/yB7J.j8bwojHHJTi', 'cliente', '2025-10-30 14:31:52', NULL, NULL, 1, NULL),
(104, 'Jeferson David', 'jedarchd@gmail.com', '966535611', '$2y$10$Bj6YB2IwYiPL40yi7CbewuGkYsNDA3S5LP9LvPsY0uvh79Ibvs7zC', 'cliente', '2025-11-17 04:34:47', NULL, NULL, 1, NULL),
(105, 'Empleado Tercer Cielo', 'empleado@gmail.com', '948583748', '$2y$10$HEWKYgUUGcbyWgfp5aMBReM2tOzExtsRliZbteE74uUtbJmhuzWg6', 'admin', '2025-11-19 14:51:45', NULL, NULL, 1, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categorias_habilitado` (`habilitado`);

--
-- Indices de la tabla `envios`
--
ALTER TABLE `envios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departamento` (`departamento`,`provincia`,`distrito`),
  ADD KEY `idx_envios_habilitado` (`habilitado`);

--
-- Indices de la tabla `historial_pedidos`
--
ALTER TABLE `historial_pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `pedido_detalles`
--
ALTER TABLE `pedido_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `idx_productos_habilitado` (`habilitado`);

--
-- Indices de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

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
-- AUTO_INCREMENT de la tabla `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `envios`
--
ALTER TABLE `envios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `historial_pedidos`
--
ALTER TABLE `historial_pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT de la tabla `pedido_detalles`
--
ALTER TABLE `pedido_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_pedidos`
--
ALTER TABLE `historial_pedidos`
  ADD CONSTRAINT `historial_pedidos_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedido_detalles`
--
ALTER TABLE `pedido_detalles`
  ADD CONSTRAINT `pedido_detalles_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`);

--
-- Filtros para la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD CONSTRAINT `producto_imagenes_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
