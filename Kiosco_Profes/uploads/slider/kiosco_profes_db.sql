-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 29-12-2025 a las 16:07:00
-- Versión del servidor: 8.4.7
-- Versión de PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `kiosco_profes_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `appointments`
--

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_session_id` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `other_subject` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `student_name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `student_contact` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `proof_details` text COLLATE utf8mb4_spanish_ci,
  `created_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_session_id` (`student_session_id`(250)),
  KEY `status` (`status`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura para la tabla administradores
CREATE TABLE IF NOT EXISTS `administradores` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`email` varchar(100) NOT NULL UNIQUE,
`password` varchar(255) NOT NULL,
`nombre` varchar(100) NOT NULL,
`rol` varchar(50) DEFAULT 'admin',
`fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;

INSERT INTO `administradores` (`email`, `password`, `nombre`, `rol`) VALUES ('admin@kiosco.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'san', 'superadmin');

-- Estructura para la tabla subjects_list
CREATE TABLE IF NOT EXISTS `subjects_list` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `color_hex` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura para la tabla slider_content
CREATE TABLE `slider_content` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `image_path` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `appointments`
--

/*INSERT INTO `appointments` (`id`, `student_session_id`, `subject`, `other_subject`, `date`, `time`, `student_name`, `student_contact`, `status`, `proof_details`, `created_at`, `expires_at`) VALUES
(4, 'student_693b4d223f116', 'Ciencias Sociales', '', '2025-12-12', '10:00:00', 'adriana perea', '31477899889', 'PAID', '1234', '2025-12-12 00:26:11', NULL),
(2, 'student_693b4d223f116', 'Química', NULL, '2025-12-11', '10:00:00', 'Carlos Andrés ', '315876890989', 'confirmed', '3456', '2025-12-11 23:46:41', NULL),
(5, 'student_693b4d223f116', 'Matemáticas', '', '2025-12-12', '10:00:00', 'sofia rivera', '3147987665', 'PAID', '6546546', '2025-12-12 00:34:51', NULL),
(6, 'student_693b4d223f116', 'Química', '', '2025-12-12', '10:00:00', 'Carlos López', '355645687', 'PAID', '64565', '2025-12-12 01:36:04', NULL),
(7, 'student_693f21c878403', 'Matemáticas', '', '2025-12-14', '10:00:00', 'Adriana cuaspud', '243454664', 'PAID', '5546565', '2025-12-14 21:17:37', NULL),
(8, 'student_693f21c878403', 'Otro tipo de asesorías', 'icfes', '2025-12-14', '10:00:00', 'sara redondo', '324556677', 'PAID', '6676', '2025-12-14 23:36:58', NULL),
(9, 'student_6941c95932625', 'Matemáticas', '', '2025-12-16', '10:00:00', 'ricardo soto', '4534656765', 'PAID', '4656', '2025-12-16 21:06:49', NULL),
(12, 'student_69497a64c9c01', 'Inglés', '', '2025-12-22', '10:00:00', 'Carlos Andrés ', '676767', 'CANCELLED', NULL, '2025-12-22 17:23:26', NULL),
(14, 'student_69497a64c9c01', 'Matemáticas', '', '2025-12-22', '10:00:00', 'jhon soto', '4656768', 'CANCELLED', '3454', '2025-12-22 18:42:20', NULL),
(16, 'student_69499299dca4c', 'Matemáticas', '', '2025-12-15', '17:22:00', 'Carlos Andrés ', '4765765765', 'PAID', 'ghgg', '2025-12-22 19:22:54', NULL),
(17, 'student_69497a64c9c01', 'Matemáticas', NULL, '2025-12-09', '14:32:00', 'Carlos Andrés Restrepo', '5566876', 'confirmed', '', '2025-12-22 19:29:15', NULL),
(18, 'student_69499f8fc57a5', 'Química', NULL, '2025-12-04', '16:44:00', 'andrea escobar soto', '465667', 'confirmed', '5567', '2025-12-22 14:44:48', NULL),
(22, 'student_69499f8fc57a5', 'Ciencias Sociales', '', '2025-12-22', '10:00:00', 'Carlos Andrés Restrepo', '78878', 'PAID', 'R5556', '2025-12-22 21:26:34', NULL),
(24, 'student_69499f8fc57a5', 'Matemáticas', '', '2025-12-22', '10:00:00', 'nubia vargas', '46766677', 'PAID', '6676', '2025-12-22 22:27:15', NULL),
(30, 'student_695290311e022', 'Matemáticas', '', '2025-12-29', '10:00:00', 'Felipe Sánchez ', '3145666788', 'PAID', '567676', '2025-12-29 15:34:26', NULL),
(26, '997829fb9aeb5421654b59fc8ee1cdc7', 'Matemáticas', NULL, '2025-12-15', '02:13:00', 'Carlos Andrés Restrepo', '3145567878', 'Pendiente', NULL, '2025-12-24 10:13:21', NULL),
(28, 'student_694c22679ebee', 'Inglés', '', '2025-12-24', '10:00:00', 'jhon', '6676767', 'PAID', 'yyyuuuuuuuuu7778\r\n', '2025-12-24 18:30:24', NULL);
COMMIT;*/

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
