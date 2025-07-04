-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 24-06-2025 a las 03:23:14
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultorios`
--

CREATE TABLE `consultorios` (
  `id` int NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ubicacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

CREATE TABLE `especialidades` (
  `id` int NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `citas_horarios`
--
ALTER TABLE `citas_horarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `consultorios`
--
ALTER TABLE `consultorios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `doctores`
--
ALTER TABLE `doctores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `horarios_doctores`
--
ALTER TABLE `horarios_doctores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs_horarios`
--
ALTER TABLE `logs_horarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `temp_asignaciones`
--
ALTER TABLE `temp_asignaciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `temp_doctores_activos`
--
ALTER TABLE `temp_doctores_activos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `temp_horarios_doctores`
--
ALTER TABLE `temp_horarios_doctores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_bloque_horario`
--
ALTER TABLE `tipos_bloque_horario`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `titulares`
--
ALTER TABLE `titulares`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
