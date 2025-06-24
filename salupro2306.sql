-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 24-06-2025 a las 03:09:06
-- Versión del servidor: 8.0.36-28
-- Versión de PHP: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `citas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aseguradoras`
--

CREATE TABLE `aseguradoras` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `nombre_comercial` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` enum('activo','inactivo') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aseguradoras`
--

INSERT INTO `aseguradoras` (`id`, `usuario_id`, `nombre_comercial`, `estado`) VALUES
(1, 3, 'Vertice Seguros', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` int NOT NULL,
  `paciente_id` int NOT NULL,
  `doctor_id` int DEFAULT NULL,
  `especialidad_id` int NOT NULL,
  `consultorio_id` int DEFAULT NULL,
  `horario_id` int DEFAULT NULL,
  `tipo_bloque_id` int DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `estado` enum('solicitada','asignada','confirmada','cancelada','completada') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'solicitada',
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `asignacion_libre` tinyint(1) DEFAULT '0',
  `tipo_solicitante` enum('aseguradora','direccion_medica','paciente') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `creado_por` int NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `paciente_seguro_id` int DEFAULT NULL COMMENT 'Seguro específico usado para esta cita'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `paciente_id`, `doctor_id`, `especialidad_id`, `consultorio_id`, `horario_id`, `tipo_bloque_id`, `fecha`, `hora`, `estado`, `descripcion`, `asignacion_libre`, `tipo_solicitante`, `creado_por`, `creado_en`, `actualizado_en`, `paciente_seguro_id`) VALUES
(1, 2, 1, 3, NULL, NULL, NULL, NULL, NULL, 'asignada', 'Dolor en dedo', 0, 'direccion_medica', 1, '2025-06-10 03:33:55', '2025-06-10 03:33:55', NULL),
(2, 1, 2, 1, NULL, NULL, NULL, NULL, NULL, 'asignada', 'Mareos constantes y dolor en el pecho esporádico', 0, 'direccion_medica', 1, '2025-06-12 20:39:23', '2025-06-12 20:39:23', NULL),
(3, 2, 4, 3, NULL, NULL, NULL, '2025-06-14', '07:00:00', 'asignada', 'Motivo de prueba', 0, 'direccion_medica', 1, '2025-06-12 20:54:27', '2025-06-13 04:14:59', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas_horarios`
--

CREATE TABLE `citas_horarios` (
  `id` int NOT NULL,
  `horario_id` int NOT NULL,
  `cita_id` int NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` enum('reservada','confirmada','completada','cancelada') COLLATE utf8mb4_general_ci DEFAULT 'reservada',
  `creado_por` int NOT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `citas_horarios`
--

INSERT INTO `citas_horarios` (`id`, `horario_id`, `cita_id`, `hora_inicio`, `hora_fin`, `estado`, `creado_por`, `creado_en`) VALUES
(1, 23, 3, '07:00:00', '07:30:00', 'reservada', 1, '2025-06-13 04:14:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultorios`
--

CREATE TABLE `consultorios` (
  `id` int NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ubicacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `consultorios`
--

INSERT INTO `consultorios` (`id`, `nombre`, `ubicacion`) VALUES
(1, '110', 'Torre D, Piso 1, consultorio 110.'),
(2, '112', 'Torre D, Piso 1, consultorio 112.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctores`
--

CREATE TABLE `doctores` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `especialidad_id` int NOT NULL,
  `subespecialidad_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `doctores`
--

INSERT INTO `doctores` (`id`, `usuario_id`, `especialidad_id`, `subespecialidad_id`) VALUES
(1, 2, 3, NULL),
(2, 6, 1, NULL),
(3, 7, 2, NULL),
(4, 8, 3, NULL),
(5, 9, 3, 2),
(6, 10, 3, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

CREATE TABLE `especialidades` (
  `id` int NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialidades`
--

INSERT INTO `especialidades` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Cardiología', 'Especialista en problemas del corazón'),
(2, 'Cirugía General', 'Intervenciones quirúrgicas'),
(3, 'Medicina General', 'Atención médica primaria');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_doctores`
--

CREATE TABLE `horarios_doctores` (
  `id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `tipo_bloque_id` int NOT NULL,
  `fecha_inicio` date NOT NULL,
  `dia_semana` tinyint NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `duracion_minutos` int NOT NULL DEFAULT '30',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `notas` text COLLATE utf8mb4_general_ci,
  `creado_por` int NOT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horarios_doctores`
--

INSERT INTO `horarios_doctores` (`id`, `doctor_id`, `tipo_bloque_id`, `fecha_inicio`, `dia_semana`, `hora_inicio`, `hora_fin`, `duracion_minutos`, `activo`, `notas`, `creado_por`, `creado_en`, `actualizado_en`) VALUES
(18, 2, 1, '2025-06-09', 4, '07:00:00', '09:00:00', 120, 1, '', 1, '2025-06-13 03:44:38', '2025-06-13 03:44:51'),
(20, 1, 1, '2025-06-09', 6, '07:00:00', '08:00:00', 60, 1, '', 1, '2025-06-13 04:12:09', '2025-06-13 04:12:09'),
(21, 1, 1, '2025-06-09', 6, '08:00:00', '09:00:00', 60, 1, '', 1, '2025-06-13 04:12:17', '2025-06-13 04:12:17'),
(22, 1, 1, '2025-06-09', 6, '09:00:00', '10:00:00', 60, 1, '', 1, '2025-06-13 04:12:32', '2025-06-13 04:12:32'),
(23, 4, 1, '2025-06-09', 6, '07:00:00', '08:00:00', 60, 1, '', 1, '2025-06-13 04:13:55', '2025-06-13 04:13:55'),
(24, 4, 1, '2025-06-09', 6, '08:00:00', '09:00:00', 60, 1, '', 1, '2025-06-13 04:14:00', '2025-06-13 04:14:00'),
(25, 4, 1, '2025-06-09', 6, '09:00:00', '10:00:00', 60, 1, '', 1, '2025-06-13 04:14:05', '2025-06-13 04:14:05'),
(26, 4, 1, '2025-06-09', 6, '10:00:00', '11:00:00', 60, 1, '', 1, '2025-06-13 04:14:09', '2025-06-13 04:14:09'),
(27, 4, 2, '2025-06-09', 6, '11:00:00', '12:00:00', 60, 1, '', 1, '2025-06-13 04:14:14', '2025-06-13 04:14:14'),
(28, 4, 2, '2025-06-09', 6, '12:00:00', '13:00:00', 60, 1, '', 1, '2025-06-13 04:14:20', '2025-06-13 04:14:20'),
(29, 4, 1, '2025-06-09', 4, '08:00:00', '09:00:00', 60, 1, '', 1, '2025-06-13 13:13:27', '2025-06-13 13:13:27'),
(30, 1, 2, '2025-06-16', 1, '08:00:00', '09:00:00', 60, 1, '', 1, '2025-06-16 03:51:39', '2025-06-16 03:51:39'),
(31, 3, 1, '2025-06-16', 1, '07:00:00', '08:00:00', 60, 1, '', 1, '2025-06-16 19:50:52', '2025-06-16 19:50:52'),
(32, 1, 1, '2025-06-16', 1, '09:00:00', '10:00:00', 60, 1, '', 1, '2025-06-17 18:43:19', '2025-06-17 18:43:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_auditoria`
--

CREATE TABLE `logs_auditoria` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `tabla_afectada` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `registro_id` int NOT NULL,
  `accion` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `datos_anteriores` json DEFAULT NULL,
  `datos_nuevos` json DEFAULT NULL,
  `direccion_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `navegador` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `fecha_accion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `logs_auditoria`
--

INSERT INTO `logs_auditoria` (`id`, `usuario_id`, `tabla_afectada`, `registro_id`, `accion`, `datos_anteriores`, `datos_nuevos`, `direccion_ip`, `navegador`, `fecha_accion`) VALUES
(1, 1, 'usuarios', 3, 'UPDATE', '{\"role\": \"aseguradora\"}', '{\"role\": \"aseguradora\", \"esta_activo\": false}', NULL, NULL, '2025-06-09 21:30:28'),
(2, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 02:13:56'),
(3, 1, 'usuarios', 3, 'UPDATE', '{\"role\": \"aseguradora\"}', '{\"role\": \"aseguradora\", \"esta_activo\": true}', NULL, NULL, '2025-06-10 02:14:07'),
(4, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 02:27:39'),
(5, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 02:29:21'),
(6, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 02:34:07'),
(7, 1, 'usuarios', 2, 'UPDATE_PASS', '{\"id\": 2, \"role\": \"doctor\", \"email\": \"dali@lgm.com\", \"cedula\": \"7856988\", \"nombre\": \"Salvador\", \"apellido\": \"Dalí\", \"telefono\": \"04144569988\", \"creado_en\": \"2025-06-04 05:03:55\", \"esta_activo\": 1, \"ultimo_acceso\": null, \"email_verificado\": 0}', '{\"password_changed\": true, \"password_changed_at\": \"2025-06-10 02:34:22\"}', '149.88.111.74', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 02:34:22'),
(8, 2, 'usuarios', 2, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 02:39:36'),
(9, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 02:39:44'),
(10, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 02:40:59'),
(11, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 02:41:54'),
(12, 1, 'usuarios', 2, 'UPDATE_EMAIL', '{\"id\": 2, \"role\": \"doctor\", \"email\": \"dali@lgm.com\", \"cedula\": \"7856988\", \"nombre\": \"Salvador\", \"apellido\": \"Dalí\", \"telefono\": \"04144569988\", \"creado_en\": \"2025-06-04 05:03:55\", \"esta_activo\": 1, \"ultimo_acceso\": \"2025-06-10 04:39:36\", \"email_verificado\": 0}', '{\"email\": \"doctor@lgm.com\", \"email_verificado\": 0}', '149.88.111.74', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 02:42:05'),
(13, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 02:51:28'),
(14, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 02:53:48'),
(15, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 02:55:47'),
(16, 1, 'usuarios', 2, 'UPDATE_EMAIL', '{\"id\": 2, \"role\": \"doctor\", \"email\": \"doctor@lgm.com\", \"cedula\": \"7856988\", \"nombre\": \"Salvador\", \"apellido\": \"Dalí\", \"telefono\": \"04144569988\", \"creado_en\": \"2025-06-04 05:03:55\", \"esta_activo\": 1, \"ultimo_acceso\": \"2025-06-10 04:39:36\", \"email_verificado\": 0}', '{\"email\": \"dali@lgm.com\", \"email_verificado\": 0}', '149.88.111.74', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 02:56:00'),
(17, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 03:14:31'),
(18, 1, 'usuarios', 2, 'UPDATE_EMAIL', '{\"id\": 2, \"role\": \"doctor\", \"email\": \"dali@lgm.com\", \"cedula\": \"7856988\", \"nombre\": \"Salvador\", \"apellido\": \"Dalí\", \"telefono\": \"04144569988\", \"creado_en\": \"2025-06-04 05:03:55\", \"esta_activo\": 1, \"ultimo_acceso\": \"2025-06-10 04:39:36\", \"email_verificado\": 0}', '{\"email\": \"doctor@lgm.com\", \"email_verificado\": 0}', '149.88.111.74', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 03:14:41'),
(19, 1, 'usuarios', 2, 'UPDATE_PASS', '{\"id\": 2, \"role\": \"doctor\", \"email\": \"doctor@lgm.com\", \"cedula\": \"7856988\", \"nombre\": \"Salvador\", \"apellido\": \"Dalí\", \"telefono\": \"04144569988\", \"creado_en\": \"2025-06-04 05:03:55\", \"esta_activo\": 1, \"ultimo_acceso\": \"2025-06-10 04:39:36\", \"email_verificado\": 0}', '{\"password_changed\": true, \"password_changed_at\": \"2025-06-10 03:15:15\"}', '149.88.111.74', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 03:15:15'),
(20, 2, 'usuarios', 2, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 03:34:30'),
(21, 3, 'usuarios', 3, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 03:35:01'),
(22, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 03:36:42'),
(24, 1, 'usuarios', 4, 'INSERT', NULL, '{\"role\": \"vertice\", \"email\": \"jose@lgm.com\", \"cedula\": \"19299999\", \"nombre\": \"Jose\", \"apellido\": \"Aquino\", \"esta_activo\": \"1\"}', NULL, NULL, '2025-06-10 03:37:58'),
(25, 4, 'usuarios', 4, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 03:38:10'),
(26, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '190.120.252.46', NULL, '2025-06-10 14:35:18'),
(27, 3, 'usuarios', 3, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '190.120.252.46', NULL, '2025-06-10 14:37:18'),
(28, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 18:54:40'),
(29, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-10 21:27:35'),
(30, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.136', NULL, '2025-06-11 20:56:57'),
(31, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.133', NULL, '2025-06-11 21:10:24'),
(32, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.133', NULL, '2025-06-11 21:16:46'),
(33, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.133', NULL, '2025-06-11 21:17:29'),
(34, 1, 'usuarios', 5, 'INSERT', NULL, '{\"role\": \"paciente\", \"email\": \"coordinador@lgm.com\", \"cedula\": \"19039299\", \"nombre\": \"Francisco\", \"apellido\": \"Guillen\", \"esta_activo\": \"1\"}', NULL, NULL, '2025-06-11 21:19:23'),
(35, 5, 'usuarios', 5, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.133', NULL, '2025-06-11 21:20:23'),
(36, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.136', NULL, '2025-06-12 01:19:02'),
(37, 1, 'usuarios', 5, 'UPDATE_DATOS', '{\"id\": 5, \"role\": \"coordinador\", \"email\": \"coordinador@lgm.com\", \"cedula\": \"19039299\", \"nombre\": \"Francisco\", \"apellido\": \"Guillen\", \"telefono\": \"04124887007\", \"creado_en\": \"2025-06-11 23:19:23\", \"esta_activo\": 1, \"ultimo_acceso\": \"2025-06-11 23:20:23\", \"email_verificado\": 0}', '{\"role\": \"paciente\", \"nombre\": \"Francisco\", \"apellido\": \"Guillen\", \"telefono\": \"04124887007\", \"esta_activo\": 1}', '149.88.111.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 01:19:13'),
(38, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.136', NULL, '2025-06-12 01:21:25'),
(39, 1, 'usuarios', 5, 'UPDATE_DATOS', '{\"id\": 5, \"role\": \"coordinador\", \"email\": \"coordinador@lgm.com\", \"cedula\": \"19039299\", \"nombre\": \"Francisco\", \"apellido\": \"Guillen\", \"telefono\": \"04124887007\", \"creado_en\": \"2025-06-11 23:19:23\", \"esta_activo\": 1, \"ultimo_acceso\": \"2025-06-11 23:20:23\", \"email_verificado\": 0}', '{\"role\": \"paciente\", \"nombre\": \"Francisco\", \"apellido\": \"Guillen\", \"telefono\": \"04124887007\", \"esta_activo\": 1}', '149.88.111.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 02:07:30'),
(40, 1, 'usuarios', 5, 'UPDATE_DATOS', '{\"id\": 5, \"role\": \"paciente\", \"email\": \"coordinador@lgm.com\", \"cedula\": \"19039299\", \"nombre\": \"Francisco\", \"apellido\": \"Guillen\", \"telefono\": \"04124887007\", \"creado_en\": \"2025-06-11 23:19:23\", \"esta_activo\": 1, \"ultimo_acceso\": \"2025-06-11 23:20:23\", \"email_verificado\": 0}', '{\"role\": \"coordinador\", \"nombre\": \"Francisco\", \"apellido\": \"Guillen\", \"telefono\": \"04124887007\", \"esta_activo\": 1}', '149.88.111.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 02:09:11'),
(41, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.136', NULL, '2025-06-12 02:13:21'),
(42, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.136', NULL, '2025-06-12 02:17:48'),
(43, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.136', NULL, '2025-06-12 02:26:30'),
(44, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.136', NULL, '2025-06-12 02:28:50'),
(45, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.136', NULL, '2025-06-12 02:33:22'),
(46, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '190.8.165.124', NULL, '2025-06-12 12:51:30'),
(47, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-12 15:26:11'),
(48, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '38.183.114.158', NULL, '2025-06-12 19:31:52'),
(49, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '45.182.141.228', NULL, '2025-06-12 20:34:29'),
(50, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '143.105.146.208', NULL, '2025-06-12 21:54:43'),
(51, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '143.105.146.208', NULL, '2025-06-12 23:03:40'),
(52, 1, 'usuarios', 4, 'UPDATE_DATOS', '{\"id\": 4, \"role\": \"vertice\", \"email\": \"jose@lgm.com\", \"cedula\": \"19299999\", \"nombre\": \"Jose\", \"apellido\": \"Aquino\", \"telefono\": \"04144965598\", \"creado_en\": \"2025-06-10 05:37:58\", \"esta_activo\": 1, \"ultimo_acceso\": \"2025-06-10 05:38:10\", \"email_verificado\": 0}', '{\"esta_activo\": 0}', '143.105.146.208', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 23:04:51'),
(53, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.77', NULL, '2025-06-13 00:07:18'),
(54, 2, 'usuarios', 2, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.77', NULL, '2025-06-13 00:07:44'),
(55, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 01:37:44'),
(56, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 01:38:30'),
(57, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 01:50:52'),
(58, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 01:55:07'),
(59, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 02:06:13'),
(60, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 02:41:59'),
(61, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 02:45:11'),
(62, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 02:46:53'),
(63, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 02:47:50'),
(64, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 03:02:41'),
(65, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 03:06:37'),
(66, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 03:11:26'),
(67, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 03:29:29'),
(68, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 03:34:23'),
(69, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 03:39:26'),
(70, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 03:44:24'),
(71, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 04:01:19'),
(72, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.74', NULL, '2025-06-13 04:11:03'),
(73, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '190.8.165.124', NULL, '2025-06-13 13:10:21'),
(74, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.210', NULL, '2025-06-13 14:19:50'),
(75, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.210', NULL, '2025-06-13 14:21:07'),
(76, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '92.178.69.237', NULL, '2025-06-13 16:15:03'),
(77, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.234', NULL, '2025-06-13 18:18:06'),
(78, 1, 'usuarios', 1, 'UPDATE_PHOTO', NULL, '{\"tamano\": 1465832, \"foto_perfil\": \"/uploads/profile_photos/profile_1_1749838818_resized.png\", \"archivo_original\": \"ChatGPT Image 13 jun 2025, 02_19_37 p.m..png\"}', NULL, NULL, '2025-06-13 18:20:18'),
(79, 1, 'usuarios', 1, 'UPDATE_PHOTO', NULL, '{\"tamano\": 1465832, \"foto_perfil\": \"/uploads/profile_photos/profile_1_1749839041_resized.png\", \"archivo_original\": \"ChatGPT Image 13 jun 2025, 02_19_37 p.m..png\"}', NULL, NULL, '2025-06-13 18:24:01'),
(80, 1, 'usuarios', 1, 'DELETE_PHOTO', '{\"foto_perfil_eliminada\": \"/uploads/profile_photos/profile_1_1749839041_resized.png\"}', NULL, NULL, NULL, '2025-06-13 18:24:22'),
(81, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.234', NULL, '2025-06-13 18:24:50'),
(82, 1, 'usuarios', 1, 'UPDATE_PHOTO', NULL, '{\"tamano\": 1465832, \"foto_perfil\": \"/uploads/profile_photos/profile_1_1749839116_resized.png\", \"archivo_original\": \"ChatGPT Image 13 jun 2025, 02_19_37 p.m..png\"}', NULL, NULL, '2025-06-13 18:25:16'),
(83, 1, 'usuarios', 1, 'UPDATE_PHOTO', NULL, '{\"tamano\": 1465832, \"foto_perfil\": \"/uploads/profile_photos/profile_1_1749839414_resized.png\", \"ruta_fisica\": \"/home/salucitas/htdocs/citas.salu.pro/api/uploads/profile_photos/profile_1_1749839414_resized.png\", \"archivo_original\": \"ChatGPT Image 13 jun 2025, 02_19_37 p.m..png\"}', NULL, NULL, '2025-06-13 18:30:14'),
(84, 1, 'usuarios', 1, 'DELETE_PHOTO', '{\"foto_perfil_eliminada\": \"/uploads/profile_photos/profile_1_1749839414_resized.png\"}', NULL, NULL, NULL, '2025-06-13 18:39:17'),
(85, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.234', NULL, '2025-06-13 18:39:33'),
(86, 1, 'usuarios', 1, 'UPDATE_PHOTO', NULL, '{\"tamano\": 1465832, \"foto_perfil\": \"/uploads/profile_photos/profile_1_1749840038_resized.png\", \"ruta_fisica\": \"/home/salucitas/htdocs/citas.salu.pro/api/uploads/profile_photos/profile_1_1749840038_resized.png\", \"archivo_original\": \"ChatGPT Image 13 jun 2025, 02_19_37 p.m..png\"}', NULL, NULL, '2025-06-13 18:40:39'),
(87, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.101', NULL, '2025-06-13 18:51:04'),
(88, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.101', NULL, '2025-06-13 19:35:02'),
(89, 1, 'usuarios', 1, 'DELETE_PHOTO', '{\"foto_perfil_eliminada\": \"/uploads/profile_photos/profile_1_1749840038_resized.png\"}', NULL, NULL, NULL, '2025-06-13 19:35:20'),
(90, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.101', NULL, '2025-06-13 19:45:54'),
(91, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.72', NULL, '2025-06-16 00:14:21'),
(92, 1, 'usuarios', 1, 'UPDATE_PHOTO', NULL, '{\"tamano\": 1465832, \"foto_perfil\": \"/uploads/profile_photos/profile_1_1750032877_resized.png\", \"ruta_fisica\": \"/home/salucitas/htdocs/citas.salu.pro/uploads/profile_photos/profile_1_1750032877_resized.png\", \"archivo_original\": \"ChatGPT Image 13 jun 2025, 02_19_37 p.m..png\"}', NULL, NULL, '2025-06-16 00:14:37'),
(93, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.72', NULL, '2025-06-16 00:18:05'),
(94, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.72', NULL, '2025-06-16 00:25:33'),
(95, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.72', NULL, '2025-06-16 00:48:22'),
(96, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.72', NULL, '2025-06-16 00:48:31'),
(97, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.72', NULL, '2025-06-16 00:52:28'),
(98, 1, 'usuarios', 1, 'UPDATE_PHOTO', NULL, '{\"tamano\": 2461424, \"foto_perfil\": \"/uploads/profile_photos/profile_1_1750035413_resized.png\", \"ruta_fisica\": \"/home/salucitas/htdocs/citas.salu.pro/uploads/profile_photos/profile_1_1750035413_resized.png\", \"archivo_original\": \"ChatGPT Image 15 jun 2025, 08_55_44 p.m..png\"}', NULL, NULL, '2025-06-16 00:56:53'),
(99, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.72', NULL, '2025-06-16 01:37:55'),
(100, 1, 'usuarios', 1, 'UPDATE_PHOTO', NULL, '{\"tamano\": 2303704, \"foto_perfil\": \"/uploads/profile_photos/profile_1_1750037887_resized.png\", \"ruta_fisica\": \"/home/salucitas/htdocs/citas.salu.pro/uploads/profile_photos/profile_1_1750037887_resized.png\", \"archivo_original\": \"ChatGPT Image 15 jun 2025, 08_55_34 p.m..png\"}', NULL, NULL, '2025-06-16 01:38:07'),
(101, 1, 'usuarios', 1, 'SELECT', NULL, '{\"accion\": \"login_exitoso\"}', '149.88.111.72', NULL, '2025-06-16 02:19:31'),
(102, 1, 'usuarios', 1, 'UPDATE_PHOTO', NULL, '{\"tamano\": 1465832, \"foto_perfil\": \"/uploads/profile_photos/profile_1_1750040489_resized.png\", \"ruta_fisica\": \"/home/salucitas/htdocs/citas.salu.pro/uploads/profile_photos/profile_1_1750040489_resized.png\", \"archivo_original\": \"ChatGPT Image 13 jun 2025, 02_19_37 p.m..png\"}', NULL, NULL, '2025-06-16 02:21:29'),
(103, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 02:32:48'),
(104, 1, 'usuarios', 8, 'ADMIN_UPDATE_PHOTO', NULL, '{\"tamano\": 2303704, \"admin_id\": \"1\", \"foto_perfil\": \"/uploads/profile_photos/profile_8_1750041467.png\", \"archivo_original\": \"ChatGPT Image 15 jun 2025, 08_55_34 p.m..png\"}', NULL, NULL, '2025-06-16 02:37:47'),
(105, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 03:16:10'),
(106, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 03:19:03'),
(107, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 03:22:16'),
(108, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 03:35:27'),
(109, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 03:38:44'),
(110, 1, 'usuarios', 7, 'ADMIN_UPDATE_PHOTO', NULL, '{\"tamano\": 2461424, \"admin_id\": \"1\", \"foto_perfil\": \"/uploads/profile_photos/profile_7_1750045323.png\", \"archivo_original\": \"ChatGPT Image 15 jun 2025, 08_55_44 p.m..png\"}', NULL, NULL, '2025-06-16 03:42:03'),
(111, 1, 'usuarios', 6, 'ADMIN_UPDATE_PHOTO', NULL, '{\"tamano\": 2470349, \"admin_id\": \"1\", \"foto_perfil\": \"/uploads/profile_photos/profile_6_1750045459.png\", \"archivo_original\": \"ChatGPT Image 15 jun 2025, 08_55_46 p.m..png\"}', NULL, NULL, '2025-06-16 03:44:19'),
(112, 1, 'usuarios', 6, 'UPDATE_DATOS', '{\"id\": 6, \"role\": \"doctor\", \"email\": \"doctor2@lgm.com\", \"cedula\": \"12345876\", \"nombre\": \"Jean-Michel\", \"apellido\": \"Baskiat\", \"telefono\": \"04245679302\", \"creado_en\": \"2025-06-12 22:36:30\", \"esta_activo\": 1, \"foto_perfil\": \"/uploads/profile_photos/profile_6_1750045459.png\", \"ultimo_acceso\": null, \"email_verificado\": 0}', '{\"nombre\": \"Elizabeth\", \"apellido\": \"Chaplin\", \"telefono\": \"04245679302\", \"esta_activo\": 1}', '149.88.111.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 03:44:25'),
(113, 1, 'usuarios', 5, 'ADMIN_UPDATE_PHOTO', NULL, '{\"tamano\": 2582130, \"admin_id\": \"1\", \"foto_perfil\": \"/uploads/profile_photos/profile_5_1750045643.png\", \"archivo_original\": \"ChatGPT Image 15 jun 2025, 11_46_32 p.m..png\"}', NULL, NULL, '2025-06-16 03:47:23'),
(114, 1, 'usuarios', 3, 'ADMIN_UPDATE_PHOTO', NULL, '{\"tamano\": 2329686, \"admin_id\": \"1\", \"foto_perfil\": \"/uploads/profile_photos/profile_3_1750045663.png\", \"archivo_original\": \"ChatGPT Image 15 jun 2025, 11_45_59 p.m..png\"}', NULL, NULL, '2025-06-16 03:47:43'),
(115, 1, 'usuarios', 2, 'ADMIN_UPDATE_PHOTO', NULL, '{\"tamano\": 3025545, \"admin_id\": \"1\", \"foto_perfil\": \"/uploads/profile_photos/profile_2_1750046210.png\", \"archivo_original\": \"ChatGPT Image 15 jun 2025, 11_56_37 p.m..png\"}', NULL, NULL, '2025-06-16 03:56:50'),
(116, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 03:57:35'),
(117, 1, 'usuarios', 4, 'ADMIN_UPDATE_PHOTO', NULL, '{\"tamano\": 2384324, \"admin_id\": \"1\", \"foto_perfil\": \"/uploads/profile_photos/profile_4_1750046276.png\", \"archivo_original\": \"ChatGPT Image 15 jun 2025, 11_48_06 p.m..png\"}', NULL, NULL, '2025-06-16 03:57:56'),
(118, 1, 'usuarios', 4, 'UPDATE_DATOS', '{\"id\": 4, \"role\": \"vertice\", \"email\": \"jose@lgm.com\", \"cedula\": \"19299999\", \"nombre\": \"Jose\", \"apellido\": \"Aquino\", \"telefono\": \"04144965598\", \"creado_en\": \"2025-06-10 05:37:58\", \"esta_activo\": 0, \"foto_perfil\": \"/uploads/profile_photos/profile_4_1750046276.png\", \"ultimo_acceso\": \"2025-06-10 05:38:10\", \"email_verificado\": 0}', '{\"nombre\": \"Beatriz\", \"apellido\": \"Aquino\", \"telefono\": \"04144965598\", \"esta_activo\": 0}', '149.88.111.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 03:58:07'),
(119, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '143.105.147.112', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 13:28:55'),
(120, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 14:37:33'),
(121, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 14:47:58'),
(122, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 15:01:26'),
(123, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 16:06:21'),
(124, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 17:21:57'),
(125, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 17:23:46'),
(126, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 18:17:34'),
(127, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 18:22:03'),
(128, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 19:33:39'),
(129, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '190.8.165.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 19:50:13'),
(130, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 13:10:41'),
(131, 1, 'usuarios', 9, 'ADMIN_UPDATE_PHOTO', NULL, '{\"tamano\": 412082, \"admin_id\": \"1\", \"foto_perfil\": \"/uploads/profile_photos/profile_9_1750167757.png\", \"archivo_original\": \"doctoragpt.png\"}', NULL, NULL, '2025-06-17 13:42:37'),
(132, 1, 'usuarios', 4, 'UPDATE_DATOS', '{\"id\": 4, \"role\": \"vertice\", \"email\": \"jose@lgm.com\", \"cedula\": \"19299999\", \"nombre\": \"Beatriz\", \"apellido\": \"Aquino\", \"telefono\": \"04144965598\", \"creado_en\": \"2025-06-10 05:37:58\", \"esta_activo\": 0, \"foto_perfil\": \"/uploads/profile_photos/profile_4_1750046276.png\", \"ultimo_acceso\": \"2025-06-10 05:38:10\", \"email_verificado\": 0}', '{\"role\": \"coordinador\", \"nombre\": \"Beatriz\", \"apellido\": \"Aquino\", \"telefono\": \"04144965598\", \"esta_activo\": 0}', '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 13:45:36'),
(133, 1, 'usuarios', 4, 'UPDATE_DATOS', '{\"id\": 4, \"role\": \"coordinador\", \"email\": \"jose@lgm.com\", \"cedula\": \"19299999\", \"nombre\": \"Beatriz\", \"apellido\": \"Aquino\", \"telefono\": \"04144965598\", \"creado_en\": \"2025-06-10 05:37:58\", \"esta_activo\": 0, \"foto_perfil\": \"/uploads/profile_photos/profile_4_1750046276.png\", \"ultimo_acceso\": \"2025-06-10 05:38:10\", \"email_verificado\": 0}', '{\"nombre\": \"Beatriz\", \"apellido\": \"Aquino\", \"telefono\": \"04144965598\", \"esta_activo\": 1}', '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 13:45:47'),
(134, 1, 'usuarios', 10, 'ADMIN_UPDATE_PHOTO', NULL, '{\"tamano\": 447029, \"admin_id\": \"1\", \"foto_perfil\": \"/uploads/profile_photos/profile_10_1750168003.png\", \"archivo_original\": \"doctoragpt2.png\"}', NULL, NULL, '2025-06-17 13:46:43'),
(135, 10, 'usuarios', 10, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 13:47:17'),
(136, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 13:47:39'),
(137, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 13:49:18'),
(138, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 13:50:50'),
(139, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 13:58:56'),
(140, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 14:00:56'),
(141, 2, 'usuarios', 2, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 14:40:29'),
(142, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 16:20:30'),
(143, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 16:51:29'),
(144, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 16:53:07'),
(145, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 16:55:36'),
(146, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 18:39:08'),
(147, 2, 'usuarios', 2, 'LOGIN', NULL, NULL, '149.88.111.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 18:46:11'),
(148, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 19:01:08'),
(149, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 19:16:40'),
(150, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 19:23:45'),
(151, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 03:54:52'),
(152, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 03:58:22'),
(153, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 04:00:00'),
(154, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 04:04:43'),
(155, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 04:06:39'),
(156, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 04:10:04'),
(157, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 04:13:18'),
(158, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 04:26:21'),
(159, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 04:28:59'),
(160, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 04:33:44'),
(161, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 15:24:25'),
(162, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 15:28:15'),
(163, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 15:33:39'),
(164, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '149.88.111.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 13:20:49'),
(165, 1, 'usuarios', 1, 'LOGIN', NULL, NULL, '190.8.165.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 19:17:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_horarios`
--

CREATE TABLE `logs_horarios` (
  `id` int NOT NULL,
  `horario_id` int NOT NULL,
  `accion` enum('crear','modificar','eliminar','activar','desactivar') COLLATE utf8mb4_general_ci NOT NULL,
  `datos_anteriores` json DEFAULT NULL,
  `datos_nuevos` json DEFAULT NULL,
  `usuario_id` int NOT NULL,
  `fecha_accion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `logs_horarios`
--

INSERT INTO `logs_horarios` (`id`, `horario_id`, `accion`, `datos_anteriores`, `datos_nuevos`, `usuario_id`, `fecha_accion`) VALUES
(16, 18, 'crear', NULL, '{\"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"08:00\", \"doctor_id\": 2, \"dia_semana\": 4, \"hora_inicio\": \"07:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-13 03:44:38'),
(17, 18, 'modificar', '{\"id\": 18, \"notas\": \"\", \"activo\": 1, \"hora_fin\": \"08:00:00\", \"creado_en\": \"2025-06-13 05:44:38\", \"doctor_id\": 2, \"creado_por\": 1, \"dia_semana\": 4, \"hora_inicio\": \"07:00:00\", \"fecha_inicio\": \"2025-06-09\", \"actualizado_en\": \"2025-06-13 05:44:38\", \"tipo_bloque_id\": 1, \"duracion_minutos\": 60}', '{\"id\": 18, \"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"09:00\", \"doctor_id\": 2, \"dia_semana\": 4, \"hora_inicio\": \"07:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-13 03:44:51'),
(20, 20, 'crear', NULL, '{\"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"08:00\", \"doctor_id\": 1, \"dia_semana\": 6, \"hora_inicio\": \"07:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-13 04:12:09'),
(21, 21, 'crear', NULL, '{\"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"09:00\", \"doctor_id\": 1, \"dia_semana\": 6, \"hora_inicio\": \"08:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-13 04:12:17'),
(22, 22, 'crear', NULL, '{\"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"10:00\", \"doctor_id\": 1, \"dia_semana\": 6, \"hora_inicio\": \"09:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-13 04:12:32'),
(23, 23, 'crear', NULL, '{\"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"08:00\", \"doctor_id\": 4, \"dia_semana\": 6, \"hora_inicio\": \"07:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-13 04:13:55'),
(24, 24, 'crear', NULL, '{\"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"09:00\", \"doctor_id\": 4, \"dia_semana\": 6, \"hora_inicio\": \"08:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-13 04:14:00'),
(25, 25, 'crear', NULL, '{\"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"10:00\", \"doctor_id\": 4, \"dia_semana\": 6, \"hora_inicio\": \"09:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-13 04:14:05'),
(26, 26, 'crear', NULL, '{\"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"11:00\", \"doctor_id\": 4, \"dia_semana\": 6, \"hora_inicio\": \"10:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-13 04:14:09'),
(27, 27, 'crear', NULL, '{\"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"12:00\", \"doctor_id\": 4, \"dia_semana\": 6, \"hora_inicio\": \"11:00\", \"tipo_bloque_id\": 2}', 1, '2025-06-13 04:14:14'),
(28, 28, 'crear', NULL, '{\"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"13:00\", \"doctor_id\": 4, \"dia_semana\": 6, \"hora_inicio\": \"12:00\", \"tipo_bloque_id\": 2}', 1, '2025-06-13 04:14:20'),
(29, 29, 'crear', NULL, '{\"fecha\": \"2025-06-09\", \"notas\": \"\", \"hora_fin\": \"09:00\", \"doctor_id\": 4, \"dia_semana\": 4, \"hora_inicio\": \"08:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-13 13:13:27'),
(30, 30, 'crear', NULL, '{\"fecha\": \"2025-06-16\", \"notas\": \"\", \"hora_fin\": \"09:00\", \"doctor_id\": 1, \"dia_semana\": 1, \"hora_inicio\": \"08:00\", \"tipo_bloque_id\": 2}', 1, '2025-06-16 03:51:39'),
(31, 31, 'crear', NULL, '{\"fecha\": \"2025-06-16\", \"notas\": \"\", \"hora_fin\": \"08:00\", \"doctor_id\": 3, \"dia_semana\": 1, \"hora_inicio\": \"07:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-16 19:50:52'),
(32, 32, 'crear', NULL, '{\"fecha\": \"2025-06-16\", \"notas\": \"\", \"hora_fin\": \"10:00\", \"doctor_id\": 1, \"dia_semana\": 1, \"hora_inicio\": \"09:00\", \"tipo_bloque_id\": 1}', 1, '2025-06-17 18:43:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int NOT NULL,
  `cita_id` int NOT NULL,
  `tipo` enum('email','sms') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` enum('pendiente','enviada','fallida') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cedula` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `es_titular` tinyint(1) DEFAULT '0',
  `titular_id` int DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `tipo` enum('asegurado','particular') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'asegurado',
  `parentesco` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id`, `nombre`, `apellido`, `cedula`, `telefono`, `email`, `estado`, `es_titular`, `titular_id`, `usuario_id`, `tipo`, `parentesco`) VALUES
(1, 'Jorge', 'Guillén', '21562369', '04122365555', 'jguillen@lgm.com', NULL, 0, NULL, NULL, 'particular', NULL),
(2, 'Daniel', 'Acevedo', '20029490', '04124887008', 'daniel.acevedo.vasaitis@gmail.com', 'activo', 1, 1, NULL, 'asegurado', NULL),
(3, 'Cristina', 'Acevedo', '26029490', '041244887008', 'cristina@lgm.com', NULL, 0, 1, NULL, 'asegurado', 'hijo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes_seguros`
--

CREATE TABLE `pacientes_seguros` (
  `id` int NOT NULL,
  `paciente_id` int NOT NULL,
  `aseguradora_id` int NOT NULL,
  `numero_poliza` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_cobertura` enum('principal','secundario','complementario') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'principal',
  `estado` enum('activo','inactivo','suspendido') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'activo',
  `fecha_inicio` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `beneficiario_principal` tinyint(1) DEFAULT '1' COMMENT 'Si es el beneficiario principal de esta póliza',
  `parentesco` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Parentesco con el titular',
  `cedula_titular` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nombre_titular` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `creado_por` int NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes_seguros_historial`
--

CREATE TABLE `pacientes_seguros_historial` (
  `id` int NOT NULL,
  `paciente_seguro_id` int NOT NULL,
  `accion` enum('activacion','desactivacion','renovacion','modificacion','suspension') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado_anterior` enum('activo','inactivo','suspendido') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_nuevo` enum('activo','inactivo','suspendido') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `datos_anteriores` json DEFAULT NULL,
  `datos_nuevos` json DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `realizado_por` int NOT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subespecialidades`
--

CREATE TABLE `subespecialidades` (
  `id` int NOT NULL,
  `especialidad_id` int NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `subespecialidades`
--

INSERT INTO `subespecialidades` (`id`, `especialidad_id`, `nombre`, `descripcion`) VALUES
(1, 2, 'Cirugía Plástica', 'Reconstrucción y mejora estética'),
(2, 3, 'Infectología', 'Se ocupa del sistema inmunitario y de enfermedades relacionadas con la inmunidad, como alergias y enfermedades autoinmunes. ');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temp_asignaciones`
--

CREATE TABLE `temp_asignaciones` (
  `id` int NOT NULL,
  `solicitud_id` int NOT NULL,
  `turno_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('asignada','notificada','completada','cancelada') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'asignada',
  `asignado_por` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temp_doctores_activos`
--

CREATE TABLE `temp_doctores_activos` (
  `id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_por` int NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `temp_doctores_activos`
--

INSERT INTO `temp_doctores_activos` (`id`, `doctor_id`, `activo`, `creado_por`, `creado_en`, `actualizado_en`) VALUES
(1, 1, 1, 4, '2025-06-10 03:38:33', '2025-06-10 03:38:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temp_horarios_backup`
--

CREATE TABLE `temp_horarios_backup` (
  `id` int NOT NULL DEFAULT '0',
  `doctor_id` int NOT NULL,
  `dia_semana` int NOT NULL COMMENT '1=Lunes, 2=Martes, etc.',
  `fecha` date DEFAULT NULL COMMENT 'Para horarios específicos en fechas concretas',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `creado_por` int NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temp_horarios_doctores`
--

CREATE TABLE `temp_horarios_doctores` (
  `id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `dia_semana` int NOT NULL COMMENT '1=Lunes, 2=Martes, etc.',
  `fecha` date DEFAULT NULL COMMENT 'Para horarios específicos en fechas concretas',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `creado_por` int NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `temp_horarios_doctores`
--

INSERT INTO `temp_horarios_doctores` (`id`, `doctor_id`, `dia_semana`, `fecha`, `hora_inicio`, `hora_fin`, `creado_por`, `creado_en`, `actualizado_en`) VALUES
(3, 1, 1, NULL, '10:00:00', '11:00:00', 4, '2025-06-10 03:38:47', '2025-06-10 03:38:47'),
(4, 1, 2, NULL, '11:00:00', '12:00:00', 4, '2025-06-10 03:38:48', '2025-06-10 03:38:48'),
(5, 1, 3, NULL, '12:00:00', '13:00:00', 4, '2025-06-10 03:38:48', '2025-06-10 03:38:48'),
(6, 1, 2, NULL, '12:00:00', '13:00:00', 4, '2025-06-10 03:38:49', '2025-06-10 03:38:49'),
(7, 1, 1, NULL, '13:00:00', '14:00:00', 4, '2025-06-10 03:38:49', '2025-06-10 03:38:49'),
(8, 1, 1, NULL, '14:00:00', '15:00:00', 4, '2025-06-10 03:38:49', '2025-06-10 03:38:49'),
(9, 1, 3, NULL, '14:00:00', '15:00:00', 4, '2025-06-10 03:38:49', '2025-06-10 03:38:49'),
(10, 1, 3, NULL, '10:00:00', '11:00:00', 4, '2025-06-10 03:38:50', '2025-06-10 03:38:50'),
(11, 1, 5, NULL, '08:00:00', '09:00:00', 4, '2025-06-10 03:38:50', '2025-06-10 03:38:50'),
(12, 1, 5, NULL, '09:00:00', '10:00:00', 4, '2025-06-10 03:38:51', '2025-06-10 03:38:51'),
(13, 1, 1, NULL, '08:00:00', '09:00:00', 4, '2025-06-10 03:38:58', '2025-06-10 03:38:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temp_notificaciones`
--

CREATE TABLE `temp_notificaciones` (
  `id` int NOT NULL,
  `asignacion_id` int NOT NULL,
  `cita_id` int DEFAULT NULL,
  `paciente_id` int DEFAULT NULL,
  `tipo` enum('whatsapp','email','sms') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` enum('pendiente','enviada','fallida','cancelada') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `mensaje` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `respuesta_api` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `intentos` int NOT NULL DEFAULT '0',
  `creado_por` int NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temp_solicitudes`
--

CREATE TABLE `temp_solicitudes` (
  `id` int NOT NULL,
  `numero_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_solicitud` date NOT NULL,
  `cedula_titular` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cedula_paciente` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_paciente` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `sexo` enum('M','F') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `especialidad_requerida` int DEFAULT NULL,
  `estatus` enum('pendiente','procesada','rechazada') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `creado_por` int NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temp_turnos_disponibles`
--

CREATE TABLE `temp_turnos_disponibles` (
  `id` int NOT NULL,
  `horario_id` int NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` enum('disponible','reservado','completado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `temp_turnos_disponibles`
--

INSERT INTO `temp_turnos_disponibles` (`id`, `horario_id`, `hora_inicio`, `hora_fin`, `estado`) VALUES
(5, 3, '10:00:00', '10:30:00', 'disponible'),
(6, 3, '10:30:00', '11:00:00', 'disponible'),
(7, 4, '11:00:00', '11:30:00', 'disponible'),
(8, 4, '11:30:00', '12:00:00', 'disponible'),
(9, 5, '12:00:00', '12:30:00', 'disponible'),
(10, 5, '12:30:00', '13:00:00', 'disponible'),
(11, 6, '12:00:00', '12:30:00', 'disponible'),
(12, 6, '12:30:00', '13:00:00', 'disponible'),
(13, 7, '13:00:00', '13:30:00', 'disponible'),
(14, 7, '13:30:00', '14:00:00', 'disponible'),
(15, 8, '14:00:00', '14:30:00', 'disponible'),
(16, 8, '14:30:00', '15:00:00', 'disponible'),
(17, 9, '14:00:00', '14:30:00', 'disponible'),
(18, 9, '14:30:00', '15:00:00', 'disponible'),
(19, 10, '10:00:00', '10:30:00', 'disponible'),
(20, 10, '10:30:00', '11:00:00', 'disponible'),
(21, 11, '08:00:00', '08:30:00', 'disponible'),
(22, 11, '08:30:00', '09:00:00', 'disponible'),
(23, 12, '09:00:00', '09:30:00', 'disponible'),
(24, 12, '09:30:00', '10:00:00', 'disponible'),
(25, 13, '08:00:00', '08:30:00', 'disponible'),
(26, 13, '08:30:00', '09:00:00', 'disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_bloque_horario`
--

CREATE TABLE `tipos_bloque_horario` (
  `id` int NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `color` varchar(7) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '#007bff',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_por` int NOT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_bloque_horario`
--

INSERT INTO `tipos_bloque_horario` (`id`, `nombre`, `descripcion`, `color`, `activo`, `creado_por`, `creado_en`, `actualizado_en`) VALUES
(1, 'APS', 'Pacientes APS', '#28a745', 1, 1, '2025-06-10 20:15:46', '2025-06-10 20:15:46'),
(2, 'Consulta Privada', 'Pacientes particulares', '#007bff', 1, 1, '2025-06-10 20:15:46', '2025-06-10 20:15:46'),
(3, 'Interconsulta', 'Pacientes referidos por otro doctor', '#ffc107', 1, 1, '2025-06-10 20:15:46', '2025-06-10 20:15:46'),
(4, 'Jornada Especial', 'Jornada especial de atención', '#ee53ae', 0, 1, '2025-06-12 02:18:27', '2025-06-12 12:52:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `titulares`
--

CREATE TABLE `titulares` (
  `id` int NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cedula` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_afiliado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aseguradora_id` int DEFAULT NULL,
  `usuario_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `titulares`
--

INSERT INTO `titulares` (`id`, `nombre`, `apellido`, `cedula`, `telefono`, `email`, `estado`, `numero_afiliado`, `aseguradora_id`, `usuario_id`) VALUES
(1, 'Daniel', 'Acevedo', '20029490', '04124887008', 'daniel.acevedo.vasaitis@gmail.com', 'activo', '1', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cedula` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_perfil` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ruta de la foto de perfil del usuario',
  `role` enum('admin','doctor','aseguradora','paciente','vertice','coordinador') COLLATE utf8mb4_general_ci NOT NULL,
  `esta_activo` tinyint(1) DEFAULT '1',
  `email_verificado` tinyint(1) DEFAULT '0',
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password`, `cedula`, `telefono`, `foto_perfil`, `role`, `esta_activo`, `email_verificado`, `ultimo_acceso`, `creado_en`) VALUES
(1, 'Daniel', 'Acevedo', 'admin@lgm.com', '$2y$12$nPNrkn6wltoDgGwZCsty7OwhBPIJHlmjKJCdUUUu5SxKDE05oNEyW', '20029490', '04124887008', '/uploads/profile_photos/profile_1_1750040489_resized.png', 'admin', 1, 0, '2025-06-23 19:17:37', '2025-06-04 02:57:06'),
(2, 'Salvador', 'Dalí', 'doctor@lgm.com', '$2y$12$PTC73w/ZT4ttfgF2Y6Ycm.Dxr2b6QCsSpYgchyivTBtriOAlbEz9m', '7856988', '04144569988', '/uploads/profile_photos/profile_2_1750046210.png', 'doctor', 1, 0, '2025-06-17 18:46:11', '2025-06-04 03:03:55'),
(3, 'Carlos', 'Tuozzo', 'vertice@lgm.com', '$2y$12$RhI7TxZSw/GFUK0IZEQyzeiQfL7c.UfT7rakirmyRY.j47W1jeh1K', '123456', '04124887008', '/uploads/profile_photos/profile_3_1750045663.png', 'aseguradora', 1, 0, '2025-06-10 14:37:18', '2025-06-09 13:33:51'),
(4, 'Beatriz', 'Aquino', 'jose@lgm.com', '$2y$12$tlTAbHHWJRPwWa7rWUzzVOnXFiG0OFmflaKSev2IwyR2qq1iyuQam', '19299999', '04144965598', '/uploads/profile_photos/profile_4_1750046276.png', 'coordinador', 1, 0, '2025-06-10 03:38:10', '2025-06-10 03:37:58'),
(5, 'Francisco', 'Guillen', 'coordinador@lgm.com', '$2y$12$nMja/wUuWeuuSTnPXXTXOuuWwiBqa7Ev6E89wD6NdADICQ25IE45K', '19039299', '04124887007', '/uploads/profile_photos/profile_5_1750045643.png', 'coordinador', 1, 0, '2025-06-11 21:20:23', '2025-06-11 21:19:23'),
(6, 'Elizabeth', 'Chaplin', 'doctor2@lgm.com', '$2y$12$SBdH1KmJlbuNNsQktxny8eoaoG8SpIW9ZBgARgFhxG4twdVIFoxGC', '12345876', '04245679302', '/uploads/profile_photos/profile_6_1750045459.png', 'doctor', 1, 0, NULL, '2025-06-12 20:36:30'),
(7, 'Marcel', 'Duchamp', 'doctor3@lgm.com', '$2y$12$bt7jTSVpChltrdb2KiA8fuswwNaOSOuIkLBjcioNaKYPB/pwV0fsK', '18029490', '04129584526', '/uploads/profile_photos/profile_7_1750045323.png', 'doctor', 1, 0, NULL, '2025-06-12 20:56:45'),
(8, 'Paul', 'Cezzane', 'doctor4@lgm.com', '$2y$12$ClrNntnZvQzlC/oN57j8teQJn.Et4D2k2Eq13pYvuZ4jan5T2KmEa', '12394932', '041248877008', '/uploads/profile_photos/profile_8_1750041467.png', 'doctor', 1, 0, NULL, '2025-06-13 04:13:38'),
(9, 'Camille', 'Claudel', 'doctor5@lgm.com', '$2y$12$FCvLcnlLqU7fDwrp8CDW0OJscc27RI6SQHLAafvBoW7tcf11bK0Qa', '7658291', '04248596598', '/uploads/profile_photos/profile_9_1750167757.png', 'doctor', 1, 0, NULL, '2025-06-17 13:36:22'),
(10, 'Natalia', 'Goncharova', 'doctor6@lgm.com', '$2y$12$ufVstnSnKU8Qb8sicCdcjeETGVCLfvASrkkzZyfI39DeGoggNn7ii', '12498394', '04148732948', '/uploads/profile_photos/profile_10_1750168003.png', 'doctor', 1, 0, '2025-06-17 13:47:17', '2025-06-17 13:44:35');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_pacientes_seguros_completo`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_pacientes_seguros_completo` (
`actualizado_en` timestamp
,`aseguradora_id` int
,`aseguradora_nombre` varchar(100)
,`beneficiario_principal` tinyint(1)
,`cedula_titular` varchar(20)
,`creado_en` timestamp
,`dias_vencimiento` int
,`estado` enum('activo','inactivo','suspendido')
,`estado_vigencia` varchar(10)
,`fecha_inicio` date
,`fecha_vencimiento` date
,`id` int
,`nombre_titular` varchar(200)
,`numero_poliza` varchar(50)
,`observaciones` text
,`paciente_apellido` varchar(100)
,`paciente_cedula` varchar(20)
,`paciente_id` int
,`paciente_nombre` varchar(100)
,`parentesco` varchar(50)
,`tipo_cobertura` enum('principal','secundario','complementario')
);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `aseguradoras`
--
ALTER TABLE `aseguradoras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `especialidad_id` (`especialidad_id`),
  ADD KEY `consultorio_id` (`consultorio_id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `idx_citas_horario` (`horario_id`),
  ADD KEY `idx_citas_tipo_bloque` (`tipo_bloque_id`),
  ADD KEY `fk_cita_seguro` (`paciente_seguro_id`);

--
-- Indices de la tabla `citas_horarios`
--
ALTER TABLE `citas_horarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cita_horario` (`cita_id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `idx_horario_tiempo` (`horario_id`,`hora_inicio`,`hora_fin`);

--
-- Indices de la tabla `consultorios`
--
ALTER TABLE `consultorios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `doctores`
--
ALTER TABLE `doctores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `especialidad_id` (`especialidad_id`),
  ADD KEY `doctores_ibfk_3` (`subespecialidad_id`);

--
-- Indices de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `horarios_doctores`
--
ALTER TABLE `horarios_doctores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_bloque_id` (`tipo_bloque_id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `idx_doctor_fecha` (`doctor_id`,`fecha_inicio`),
  ADD KEY `idx_fecha_dia` (`fecha_inicio`,`dia_semana`);

--
-- Indices de la tabla `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_logs_fecha` (`fecha_accion`),
  ADD KEY `idx_logs_tabla` (`tabla_afectada`);

--
-- Indices de la tabla `logs_horarios`
--
ALTER TABLE `logs_horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_horario_fecha` (`horario_id`,`fecha_accion`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cita_id` (`cita_id`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `titular_id` (`titular_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `pacientes_seguros`
--
ALTER TABLE `pacientes_seguros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_paciente_aseguradora` (`paciente_id`,`aseguradora_id`),
  ADD KEY `idx_numero_poliza` (`numero_poliza`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `fk_paciente_seguro` (`paciente_id`),
  ADD KEY `fk_aseguradora_seguro` (`aseguradora_id`),
  ADD KEY `fk_creado_por_seguro` (`creado_por`),
  ADD KEY `idx_tipo_cobertura` (`tipo_cobertura`),
  ADD KEY `idx_cedula_titular` (`cedula_titular`),
  ADD KEY `idx_fecha_vencimiento` (`fecha_vencimiento`),
  ADD KEY `idx_beneficiario_principal` (`beneficiario_principal`);

--
-- Indices de la tabla `pacientes_seguros_historial`
--
ALTER TABLE `pacientes_seguros_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seguro_historial` (`paciente_seguro_id`),
  ADD KEY `fk_usuario_historial` (`realizado_por`),
  ADD KEY `idx_fecha_accion` (`fecha_accion`);

--
-- Indices de la tabla `subespecialidades`
--
ALTER TABLE `subespecialidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `especialidad_id` (`especialidad_id`);

--
-- Indices de la tabla `temp_asignaciones`
--
ALTER TABLE `temp_asignaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicitud_id` (`solicitud_id`),
  ADD KEY `turno_id` (`turno_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `asignado_por` (`asignado_por`);

--
-- Indices de la tabla `temp_doctores_activos`
--
ALTER TABLE `temp_doctores_activos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doctor_id` (`doctor_id`),
  ADD KEY `creado_por` (`creado_por`);

--
-- Indices de la tabla `temp_horarios_doctores`
--
ALTER TABLE `temp_horarios_doctores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `creado_por` (`creado_por`);

--
-- Indices de la tabla `temp_notificaciones`
--
ALTER TABLE `temp_notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asignacion_id` (`asignacion_id`),
  ADD KEY `cita_id` (`cita_id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `creado_por` (`creado_por`);

--
-- Indices de la tabla `temp_solicitudes`
--
ALTER TABLE `temp_solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `especialidad_requerida` (`especialidad_requerida`);

--
-- Indices de la tabla `temp_turnos_disponibles`
--
ALTER TABLE `temp_turnos_disponibles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `horario_id` (`horario_id`);

--
-- Indices de la tabla `tipos_bloque_horario`
--
ALTER TABLE `tipos_bloque_horario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_nombre_tipo_bloque` (`nombre`),
  ADD KEY `creado_por` (`creado_por`);

--
-- Indices de la tabla `titulares`
--
ALTER TABLE `titulares`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `aseguradora_id` (`aseguradora_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `idx_usuarios_role` (`role`),
  ADD KEY `idx_usuarios_activo` (`esta_activo`),
  ADD KEY `idx_usuarios_foto_perfil` (`foto_perfil`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `aseguradoras`
--
ALTER TABLE `aseguradoras`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `citas_horarios`
--
ALTER TABLE `citas_horarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `consultorios`
--
ALTER TABLE `consultorios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `doctores`
--
ALTER TABLE `doctores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `horarios_doctores`
--
ALTER TABLE `horarios_doctores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT de la tabla `logs_horarios`
--
ALTER TABLE `logs_horarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pacientes_seguros`
--
ALTER TABLE `pacientes_seguros`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes_seguros_historial`
--
ALTER TABLE `pacientes_seguros_historial`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `subespecialidades`
--
ALTER TABLE `subespecialidades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `temp_asignaciones`
--
ALTER TABLE `temp_asignaciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `temp_doctores_activos`
--
ALTER TABLE `temp_doctores_activos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `temp_horarios_doctores`
--
ALTER TABLE `temp_horarios_doctores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `temp_notificaciones`
--
ALTER TABLE `temp_notificaciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `temp_solicitudes`
--
ALTER TABLE `temp_solicitudes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `temp_turnos_disponibles`
--
ALTER TABLE `temp_turnos_disponibles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `tipos_bloque_horario`
--
ALTER TABLE `tipos_bloque_horario`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `titulares`
--
ALTER TABLE `titulares`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_pacientes_seguros_completo`
--
DROP TABLE IF EXISTS `v_pacientes_seguros_completo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`citas`@`%` SQL SECURITY DEFINER VIEW `v_pacientes_seguros_completo`  AS SELECT `ps`.`id` AS `id`, `ps`.`paciente_id` AS `paciente_id`, `p`.`nombre` AS `paciente_nombre`, `p`.`apellido` AS `paciente_apellido`, `p`.`cedula` AS `paciente_cedula`, `ps`.`aseguradora_id` AS `aseguradora_id`, `a`.`nombre_comercial` AS `aseguradora_nombre`, `ps`.`numero_poliza` AS `numero_poliza`, `ps`.`tipo_cobertura` AS `tipo_cobertura`, `ps`.`estado` AS `estado`, `ps`.`fecha_inicio` AS `fecha_inicio`, `ps`.`fecha_vencimiento` AS `fecha_vencimiento`, `ps`.`beneficiario_principal` AS `beneficiario_principal`, `ps`.`parentesco` AS `parentesco`, `ps`.`cedula_titular` AS `cedula_titular`, `ps`.`nombre_titular` AS `nombre_titular`, `ps`.`observaciones` AS `observaciones`, `ps`.`creado_en` AS `creado_en`, `ps`.`actualizado_en` AS `actualizado_en`, (case when (`ps`.`fecha_vencimiento` is null) then 'indefinido' when (`ps`.`fecha_vencimiento` > curdate()) then 'vigente' when (`ps`.`fecha_vencimiento` = curdate()) then 'vence_hoy' else 'vencido' end) AS `estado_vigencia`, (to_days(`ps`.`fecha_vencimiento`) - to_days(curdate())) AS `dias_vencimiento` FROM ((`pacientes_seguros` `ps` join `pacientes` `p` on((`ps`.`paciente_id` = `p`.`id`))) join `aseguradoras` `a` on((`ps`.`aseguradora_id` = `a`.`id`))) ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `aseguradoras`
--
ALTER TABLE `aseguradoras`
  ADD CONSTRAINT `aseguradoras_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctores` (`id`),
  ADD CONSTRAINT `citas_ibfk_3` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`),
  ADD CONSTRAINT `citas_ibfk_4` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`),
  ADD CONSTRAINT `citas_ibfk_5` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `citas_ibfk_6` FOREIGN KEY (`horario_id`) REFERENCES `horarios_doctores` (`id`),
  ADD CONSTRAINT `citas_ibfk_7` FOREIGN KEY (`tipo_bloque_id`) REFERENCES `tipos_bloque_horario` (`id`),
  ADD CONSTRAINT `fk_cita_seguro` FOREIGN KEY (`paciente_seguro_id`) REFERENCES `pacientes_seguros` (`id`);

--
-- Filtros para la tabla `citas_horarios`
--
ALTER TABLE `citas_horarios`
  ADD CONSTRAINT `citas_horarios_ibfk_1` FOREIGN KEY (`horario_id`) REFERENCES `horarios_doctores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `citas_horarios_ibfk_2` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `citas_horarios_ibfk_3` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `doctores`
--
ALTER TABLE `doctores`
  ADD CONSTRAINT `doctores_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctores_ibfk_2` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`),
  ADD CONSTRAINT `doctores_ibfk_3` FOREIGN KEY (`subespecialidad_id`) REFERENCES `subespecialidades` (`id`);

--
-- Filtros para la tabla `horarios_doctores`
--
ALTER TABLE `horarios_doctores`
  ADD CONSTRAINT `horarios_doctores_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `horarios_doctores_ibfk_2` FOREIGN KEY (`tipo_bloque_id`) REFERENCES `tipos_bloque_horario` (`id`),
  ADD CONSTRAINT `horarios_doctores_ibfk_3` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  ADD CONSTRAINT `logs_auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `logs_horarios`
--
ALTER TABLE `logs_horarios`
  ADD CONSTRAINT `logs_horarios_ibfk_1` FOREIGN KEY (`horario_id`) REFERENCES `horarios_doctores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `logs_horarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`);

--
-- Filtros para la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`titular_id`) REFERENCES `titulares` (`id`),
  ADD CONSTRAINT `pacientes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pacientes_seguros`
--
ALTER TABLE `pacientes_seguros`
  ADD CONSTRAINT `fk_aseguradora_seguro` FOREIGN KEY (`aseguradora_id`) REFERENCES `aseguradoras` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_creado_por_seguro` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_paciente_seguro` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pacientes_seguros_historial`
--
ALTER TABLE `pacientes_seguros_historial`
  ADD CONSTRAINT `fk_seguro_historial` FOREIGN KEY (`paciente_seguro_id`) REFERENCES `pacientes_seguros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_usuario_historial` FOREIGN KEY (`realizado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `subespecialidades`
--
ALTER TABLE `subespecialidades`
  ADD CONSTRAINT `subespecialidades_ibfk_1` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `temp_asignaciones`
--
ALTER TABLE `temp_asignaciones`
  ADD CONSTRAINT `temp_asignaciones_ibfk_1` FOREIGN KEY (`solicitud_id`) REFERENCES `temp_solicitudes` (`id`),
  ADD CONSTRAINT `temp_asignaciones_ibfk_2` FOREIGN KEY (`turno_id`) REFERENCES `temp_turnos_disponibles` (`id`),
  ADD CONSTRAINT `temp_asignaciones_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `doctores` (`id`),
  ADD CONSTRAINT `temp_asignaciones_ibfk_4` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `temp_doctores_activos`
--
ALTER TABLE `temp_doctores_activos`
  ADD CONSTRAINT `temp_doctores_activos_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctores` (`id`),
  ADD CONSTRAINT `temp_doctores_activos_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `temp_horarios_doctores`
--
ALTER TABLE `temp_horarios_doctores`
  ADD CONSTRAINT `temp_horarios_doctores_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctores` (`id`),
  ADD CONSTRAINT `temp_horarios_doctores_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `temp_horarios_doctores_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `temp_doctores_activos` (`doctor_id`);

--
-- Filtros para la tabla `temp_notificaciones`
--
ALTER TABLE `temp_notificaciones`
  ADD CONSTRAINT `temp_notificaciones_ibfk_1` FOREIGN KEY (`asignacion_id`) REFERENCES `temp_asignaciones` (`id`),
  ADD CONSTRAINT `temp_notificaciones_ibfk_2` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`),
  ADD CONSTRAINT `temp_notificaciones_ibfk_3` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `temp_notificaciones_ibfk_4` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `temp_solicitudes`
--
ALTER TABLE `temp_solicitudes`
  ADD CONSTRAINT `temp_solicitudes_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `temp_solicitudes_ibfk_2` FOREIGN KEY (`especialidad_requerida`) REFERENCES `especialidades` (`id`);

--
-- Filtros para la tabla `temp_turnos_disponibles`
--
ALTER TABLE `temp_turnos_disponibles`
  ADD CONSTRAINT `temp_turnos_disponibles_ibfk_1` FOREIGN KEY (`horario_id`) REFERENCES `temp_horarios_doctores` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tipos_bloque_horario`
--
ALTER TABLE `tipos_bloque_horario`
  ADD CONSTRAINT `tipos_bloque_horario_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `titulares`
--
ALTER TABLE `titulares`
  ADD CONSTRAINT `titulares_ibfk_1` FOREIGN KEY (`aseguradora_id`) REFERENCES `aseguradoras` (`id`),
  ADD CONSTRAINT `titulares_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
