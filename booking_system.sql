-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2026 at 09:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `booking_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancel','cancel_by_admin') DEFAULT 'pending',
  `short_description` varchar(255) NOT NULL,
  `detail` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `first_name`, `last_name`, `room_id`, `booking_date`, `start_time`, `end_time`, `status`, `short_description`, `detail`) VALUES
(1, 1, 'วิเชียรมาศ', 'บุญไทย', 1, '2026-08-26', '2026-08-26 11:05:00', '2026-08-26 11:30:00', 'approved', 'ประชุม', ''),
(2, 1, 'Nalintip', 'Srithamma', 1, '2026-08-26', '2026-08-26 16:23:00', '2026-08-26 16:24:00', 'approved', 'ประชุม', ''),
(3, 1, 'Nalintip', 'Srithamma', 1, '2026-08-26', '2026-08-26 16:37:00', '2026-08-26 16:38:00', 'approved', 'ประชุม', ''),
(4, 1, 'Nalintip', 'Srithamma', 1, '2026-08-26', '2026-08-26 16:41:00', '2026-08-26 16:42:00', 'approved', 'ประชุม', ''),
(5, 1, 'นลิน', 'Srithamma', 1, '2026-08-26', '2026-08-26 16:45:00', '2026-08-26 16:47:00', 'approved', 'vdf', ''),
(6, 1, 'นลิน', 'หวานใจ', 1, '2026-08-26', '2026-08-26 16:49:00', '2026-08-26 16:50:00', 'approved', 'ada', ''),
(7, 1, 'Nalintip', 'Srithamma', 1, '2026-08-26', '2026-08-26 16:53:00', '2026-08-26 16:54:00', 'cancel_by_admin', 'ประชุม', ''),
(8, 1, 'Nalintip', 'Srithamma', 1, '2026-08-26', '2026-08-26 16:53:00', '2026-08-26 16:55:00', 'approved', 'ประชุม', ''),
(9, 1, 'Nalintip', 'Srithamma', 1, '2026-08-26', '2026-08-26 17:11:00', '2026-08-26 17:13:00', 'approved', 'ประชุม', ''),
(10, 1, 'Nalintip', 'Srithamma', 1, '2026-08-26', '2026-08-26 17:14:00', '2026-08-26 17:15:00', 'approved', 'ประชุม', ''),
(11, 1, 'Nalintip', 'Srithamma', 1, '2026-08-26', '2026-08-26 19:37:00', '2026-08-26 19:38:00', 'approved', 'ประชุม', ''),
(12, 2, 'ddd', 'dddd', 1, '2026-08-26', '2026-08-26 19:40:00', '2026-08-26 19:41:00', 'approved', 'dddd', ''),
(13, 1, 'ddd', 'drgfgs', 1, '2026-08-26', '2026-08-26 19:42:00', '2026-08-26 19:43:00', 'approved', 'drgerg', ''),
(14, 1, 'Nalintip', 'Srithamma', 1, '2026-08-27', '2026-08-27 09:06:00', '2026-08-27 09:07:00', 'approved', 'ประชุม', ''),
(15, 1, 'นลิน', 'ดีใจ', 1, '2026-08-27', '2026-08-27 09:11:00', '2026-08-27 09:12:00', 'approved', 'vdf', ''),
(16, 1, 'นลินทิพย์', 'ศรีธรรมา', 1, '2026-08-27', '2026-08-27 09:22:00', '2026-08-27 09:23:00', 'approved', 'สอบโปรเจค', ''),
(17, 1, 'Nalintip', 'Srithamma', 1, '2026-08-27', '2026-08-27 17:20:00', '2026-08-27 17:30:00', 'cancel_by_admin', 'ประชุม', ''),
(18, 1, 'Nalintip', 'Srithamma', 1, '2026-08-27', '2026-08-27 09:31:00', '2026-08-27 09:32:00', 'approved', 'ประชุม', ''),
(19, 1, 'นลิน', 'ใจดี', 1, '2026-08-27', '2026-08-27 10:42:00', '2026-08-27 10:43:00', 'approved', 'ประชุม', ''),
(20, 1, 'นลิน', 'ดี', 1, '2026-08-27', '2026-08-27 10:47:00', '2026-08-27 10:48:00', 'approved', 'ประชุม', ''),
(21, 1, 'Nalintip', 'Srithamma', 1, '2026-08-27', '2026-08-27 10:50:00', '2026-08-27 10:53:00', 'approved', 'ประชุม', ''),
(22, 1, 'Nalintip', 'Srithamma', 1, '2026-08-27', '2026-08-27 10:56:00', '2026-08-27 10:58:00', 'approved', 'ประชุม', ''),
(23, 1, 'Nalintip', 'Srithamma', 1, '2026-08-27', '2026-08-27 11:41:00', '2026-08-27 11:43:00', 'approved', 'ประชุม', ''),
(24, 1, 'Nalintip', 'Srithamma', 1, '2026-09-04', '2026-09-04 00:01:00', '2026-09-04 00:03:00', 'approved', 'สอน', ''),
(25, 2, 'Nalintip', 'Srithamma', 1, '2026-09-04', '2026-09-04 00:04:00', '2026-09-04 00:05:00', 'approved', 'ประชุม', ''),
(26, 1, 'Nalintip', 'Srithamma', 1, '2026-09-21', '2026-09-21 14:35:00', '2026-09-21 14:36:00', 'approved', 'สอน', ''),
(27, 1, 'Nalintip', 'Srithamma', 1, '2026-09-21', '2026-09-21 14:44:00', '2026-09-21 14:45:00', 'approved', 'ประชุม', ''),
(28, 1, 'Nalintip', 'Srithamma', 1, '2026-09-21', '2026-09-21 14:46:00', '2026-09-21 14:47:00', 'approved', 'ประชุม', ''),
(29, 1, 'Nalintip', 'Srithamma', 1, '2026-09-21', '2026-09-21 15:24:00', '2026-09-21 15:25:00', 'approved', 'ประชุม', ''),
(30, 1, 'Nalintip', 'Srithamma', 1, '2026-09-21', '2026-09-21 15:25:00', '2026-09-21 15:26:00', 'approved', 'สอน', ''),
(31, 1, 'Nalintip', 'Srithamma', 1, '2026-09-21', '2026-09-21 15:26:00', '2026-09-21 15:27:00', 'approved', 'สอน', ''),
(32, 1, 'นลิน', 'ดีใจ', 1, '2026-09-21', '2026-09-21 15:37:00', '2026-09-21 15:38:00', 'approved', 'ประชุม', ''),
(33, 1, 'Nalintip', 'Srithamma', 1, '2026-09-28', '2026-09-28 17:37:00', '2026-09-28 17:38:00', 'cancel_by_admin', 'ประชุม', 'ประชุม'),
(34, 1, 'Nalintip', 'Srithamma', 2, '2026-09-28', '2026-09-28 17:40:00', '2026-09-28 17:41:00', 'cancel_by_admin', 'สอน', ''),
(35, 1, 'Nalintip', 'Srithamma', 1, '2026-09-28', '2026-09-28 17:38:00', '2026-09-28 17:39:00', 'cancel_by_admin', 'ประชุม', ''),
(36, 1, 'Nalintip', 'Srithamma', 1, '2026-09-28', '2026-09-28 17:40:00', '2026-09-28 17:41:00', 'cancel_by_admin', 'ประชุม', ''),
(37, 1, 'Nalintip', 'Srithamma', 1, '2026-09-28', '2026-09-28 17:54:00', '2026-09-28 17:55:00', 'cancel_by_admin', 'ประชุม', ''),
(38, 1, 'Nalintip', 'Srithamma', 1, '2026-09-28', '2026-09-28 18:03:00', '2026-09-28 18:05:00', 'approved', 'ประชุม', ''),
(39, 1, 'จารุวรรณ', 'ชินอาจ', 1, '2026-09-28', '2026-09-28 18:09:00', '2026-09-28 18:11:00', 'approved', 'ประชุม', ''),
(40, 1, 'จารุวรรณ', 'ชินอาจ', 1, '2026-09-28', '2026-09-28 18:17:00', '2026-09-28 18:19:00', 'cancel_by_admin', 'ประชุม', ''),
(41, 1, 'จารุวรรณ', 'ชินอาจ', 1, '2026-09-28', '2026-09-28 18:19:00', '2026-09-28 18:21:00', 'approved', 'ประชุม', ''),
(42, 1, 'จารุวรรณ', 'ชินอาจ', 2, '2026-09-28', '2026-09-28 18:52:00', '2026-09-28 18:53:00', 'approved', 'ประชุม', ''),
(43, 1, 'จารุวรรณ', 'ชินอาจ', 2, '2026-09-28', '2026-09-28 18:57:00', '2026-09-28 18:58:00', 'approved', 'ประชุม', ''),
(44, 1, 'Nalintip', 'Srithamma', 2, '2026-09-28', '2026-09-28 18:59:00', '2026-09-28 19:00:00', 'cancel_by_admin', 'ประชุม', ''),
(45, 1, 'จารุวรรณ', 'ชินอาจ', 2, '2026-09-28', '2026-09-28 19:20:00', '2026-09-28 19:21:00', 'approved', 'ประชุม', '');

-- --------------------------------------------------------

--
-- Table structure for table `booking_equipments`
--

CREATE TABLE `booking_equipments` (
  `booking_id` int(11) NOT NULL,
  `node_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_equipments`
--

INSERT INTO `booking_equipments` (`booking_id`, `node_id`) VALUES
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 7),
(1, 8),
(1, 9),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 7),
(2, 8),
(2, 9),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(3, 7),
(3, 8),
(3, 9),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(4, 7),
(4, 8),
(4, 9),
(5, 2),
(5, 3),
(5, 4),
(5, 5),
(5, 7),
(5, 8),
(5, 9),
(6, 7),
(7, 2),
(7, 3),
(7, 4),
(7, 5),
(7, 7),
(7, 8),
(7, 9),
(8, 2),
(8, 3),
(8, 4),
(8, 5),
(8, 7),
(8, 8),
(8, 9),
(9, 2),
(9, 3),
(9, 4),
(9, 5),
(9, 7),
(9, 8),
(9, 9),
(10, 2),
(10, 3),
(10, 7),
(10, 8),
(10, 9),
(11, 4),
(12, 2),
(13, 3),
(14, 4),
(15, 7),
(17, 2),
(17, 3),
(17, 4),
(17, 5),
(17, 7),
(17, 8),
(17, 9),
(18, 2),
(18, 3),
(18, 4),
(18, 5),
(18, 7),
(18, 8),
(18, 9),
(19, 2),
(19, 3),
(19, 4),
(19, 5),
(19, 7),
(19, 8),
(19, 9),
(20, 2),
(20, 8),
(21, 5),
(22, 2),
(22, 3),
(22, 4),
(22, 5),
(22, 7),
(22, 8),
(22, 9),
(23, 2),
(23, 3),
(23, 4),
(23, 5),
(23, 7),
(23, 8),
(23, 9),
(26, 1),
(26, 7),
(27, 1),
(28, 1),
(29, 3),
(32, 7),
(33, 2),
(33, 3),
(33, 4),
(33, 5),
(34, 1),
(34, 7),
(35, 2),
(35, 3),
(35, 4),
(35, 5),
(36, 2),
(36, 3),
(36, 4),
(36, 5),
(37, 2),
(37, 3),
(37, 4),
(37, 5),
(38, 2),
(38, 3),
(38, 4),
(38, 5),
(39, 2),
(39, 3),
(39, 4),
(39, 5),
(40, 2),
(40, 3),
(40, 4),
(40, 5),
(41, 2),
(41, 3),
(41, 4),
(41, 5),
(42, 1),
(42, 7),
(43, 1),
(43, 7),
(44, 1),
(44, 7),
(45, 1),
(45, 7);

-- --------------------------------------------------------

--
-- Table structure for table `building`
--

CREATE TABLE `building` (
  `building_id` int(11) NOT NULL,
  `building_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `building`
--

INSERT INTO `building` (`building_id`, `building_name`) VALUES
(1, 'CP09');

-- --------------------------------------------------------

--
-- Table structure for table `device_channel`
--

CREATE TABLE `device_channel` (
  `id` int(11) NOT NULL,
  `node_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `installation_node`
--

CREATE TABLE `installation_node` (
  `room_id` int(11) NOT NULL,
  `node_id` int(11) NOT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `installation_node`
--

INSERT INTO `installation_node` (`room_id`, `node_id`, `status`) VALUES
(1, 2, 0),
(1, 3, 0),
(1, 4, 0),
(1, 5, 0),
(1, 6, 0),
(1, 8, 0),
(1, 9, 0),
(1, 10, 0),
(2, 1, 0),
(2, 7, 0);

-- --------------------------------------------------------

--
-- Table structure for table `node`
--

CREATE TABLE `node` (
  `node_id` int(11) NOT NULL,
  `node_name` varchar(255) DEFAULT NULL,
  `node_type` enum('led','projector','air_conditioner','audio_equipment','computer','door') DEFAULT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `node_status` enum('active','inactive') DEFAULT 'inactive',
  `computer_role` enum('teacher','student') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `node`
--

INSERT INTO `node` (`node_id`, `node_name`, `node_type`, `detail`, `node_status`, `computer_role`) VALUES
(1, 'led', 'led', 'ไฟห้องCP9425', 'active', NULL),
(2, 'Projector9604', 'projector', 'โปรเจคเตอร์9604', 'active', ''),
(3, 'Air_conditioner_9604', 'air_conditioner', 'เครื่องปรับอากาศ9604', 'active', ''),
(4, 'AudioEquipment9604', 'audio_equipment', 'ปลั๊กสำหรับเครื่องเสียงcp9604', 'active', ''),
(5, 'Computer9604_tch', 'computer', 'คอมพิวเตอร์อาจารย์ตัวที่ 1', 'active', 'teacher'),
(6, 'Computer9604_st', 'computer', 'คอมพิวเตอร์นักศึกษาตัวที่ 1', 'active', 'student'),
(7, 'Door9425', 'door', 'ประตูห้อง9425', 'active', NULL),
(8, 'Projector9425_2', 'projector', 'โปรเจคเตอร์9425_2', 'inactive', NULL),
(9, 'Air_conditioner_9425_2', 'air_conditioner', 'เครื่องปรับอากาศ9425_2', 'inactive', NULL),
(10, 'LED9425', 'led', 'ไฟห้อง9425', 'inactive', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(255) DEFAULT NULL,
  `floor_number` int(11) DEFAULT NULL,
  `room_type` varchar(50) DEFAULT NULL,
  `room_status` enum('available','occupied','maintenance') DEFAULT 'available',
  `building_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`room_id`, `room_name`, `floor_number`, `room_type`, `room_status`, `building_id`) VALUES
(1, 'CP9604', 6, 'LAB', 'available', NULL),
(2, 'CP9425', 4, 'LAB', 'available', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sso_tokens`
--

CREATE TABLE `sso_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sso_tokens`
--

INSERT INTO `sso_tokens` (`id`, `user_id`, `token_hash`, `expires_at`, `used`, `created_at`) VALUES
(1, 1, '405e6fc623c7079c7202954a739bc8ffc49cbec86bdba353ef758d1c301b6008', '2026-08-30 10:30:50', 0, '2026-08-30 15:29:50'),
(2, 1, '93500fd85d66f548589615201f6fcc383043e6542f5adacd4aab5abb9e6c5c65', '2026-08-30 10:31:22', 0, '2026-08-30 15:30:22'),
(3, 1, '27008ac612bc9badf48c4070e5780f9680f19dda68195d1b4ef1a4f79579e205', '2026-08-30 10:31:38', 0, '2026-08-30 15:30:38'),
(4, 1, 'd01262c9ab144a5918db5759d90d8152aa85cf319e6ee2cd19b62e6f3ba3d676', '2026-08-30 10:36:12', 0, '2026-08-30 15:35:12'),
(5, 1, '12a50132f0d1875a3a1cf7db8003dd0c91d0ee74d26ba6cf1c668887bde483d7', '2026-08-30 10:36:14', 0, '2026-08-30 15:35:14'),
(6, 1, '316169a27ba91722cb040a5d2b20f9f3b712d3d395ad1e02482be3add68de8cb', '2026-08-30 10:36:19', 0, '2026-08-30 15:35:19'),
(7, 1, 'ad60f3faa6537e22827a1d87920e17994bf12ffcd88de29a1f14d80f3ccc8682', '2026-08-30 10:36:19', 0, '2026-08-30 15:35:19'),
(8, 1, '50588ea379782025d7feb0ac900b95ba7438f3720e400bbe61ec8c29088f19ef', '2026-08-30 10:36:23', 0, '2026-08-30 15:35:23'),
(9, 1, '6c45b10f70a9f67805e1fddf5fa3f868fd20a43a68e5d42b8be7af55f0604d4c', '2026-08-30 10:36:27', 0, '2026-08-30 15:35:27'),
(10, 1, '486964c38014af1dacdbeace3a21d31f4cc21d6f1d4670ee4f4df52b3e392fe1', '2026-08-30 10:36:28', 0, '2026-08-30 15:35:28'),
(11, 1, '13e8929782944f4372c89c2d4f2af68bed2a2285e39f226a9f0ca7f64886b588', '2026-08-30 10:36:33', 0, '2026-08-30 15:35:33'),
(12, 1, 'd3741014136b834d5d4b56aec9cd9c6dcecbc54287ae3e6b7c77e26542daa09a', '2026-08-30 10:37:58', 0, '2026-08-30 15:36:58'),
(13, 1, '62ee2a01815037ee5c2863ac783f92fc0de2ce0b243c1418db6095aed80af0fb', '2026-08-30 10:38:35', 0, '2026-08-30 15:37:35'),
(14, 1, 'aa3821f6f0b3e5064a5243965c23c088e7579c6314340420d6834c45cfb8f4a4', '2026-08-30 10:39:32', 0, '2026-08-30 15:38:32'),
(15, 1, '80e73176841b21c62a812ebeeff1b87e1f40ca4c770e2b2d305c2fa3da235039', '2026-08-30 10:42:42', 0, '2026-08-30 15:41:42'),
(16, 1, '017297927eec30940e87818ae3d08907b829614b9fc676390b25533ea74ee3c7', '2026-08-30 10:43:48', 0, '2026-08-30 15:42:48'),
(17, 1, '66e63bd28c65276f000ac9fcb51cfb2ad8c4f0c9de10fdfeefdb5cdc646e4251', '2026-08-30 10:44:04', 0, '2026-08-30 15:43:04'),
(18, 1, 'e027f633bfd10daa6069aade4e96076befe0dcb67e2d5fa30b1ec11aae758ab6', '2026-08-30 10:54:28', 0, '2026-08-30 15:53:28'),
(19, 1, '0fa9768fc6e3b559a76cf33ce71225c266ce205bab35fab1c9d2a29581cd408a', '2026-08-30 10:59:46', 0, '2026-08-30 15:58:46'),
(20, 1, 'f400ff2e94458b83527a21388f30347ba77fbda2883a73c839e272d306020bcd', '2026-08-30 11:00:15', 0, '2026-08-30 15:59:15'),
(21, 1, '78808eba0779c06ce37c45c4acd3fdba73fad6e23cbf937e1bd8c140ac2f7ee4', '2026-08-30 11:03:13', 0, '2026-08-30 16:02:13'),
(22, 1, '2400606e70ef023faf1cd409ff9203128f41d1ba18c050017661f331bc270454', '2026-08-30 11:04:53', 0, '2026-08-30 16:03:53'),
(23, 1, 'cec5224a3a8756ae9d35a9917a3049113570c3027f8f871a3ca4c2069bada617', '2026-08-30 11:04:56', 0, '2026-08-30 16:03:56'),
(24, 1, 'f3d5e2efaf109f6919e16d209d9918a4cf651664c7a9c9653a98e5a45f6b82bc', '2026-08-30 11:06:03', 0, '2026-08-30 16:05:03'),
(25, 1, '4720bb970111f944cf1c38060b36ce55aaea1ffa6857c90b1e3677739fe8a550', '2026-08-30 11:06:19', 0, '2026-08-30 16:05:19'),
(26, 1, '81419bad6642e9835cea4947409f622b84e103354bc0f300033921d6eb148c0d', '2026-08-30 11:09:55', 0, '2026-08-30 16:08:55'),
(27, 1, 'cd593b91679b887cf44e375f6bf04c190a6b27f9588811799fd3d621dd32718a', '2026-08-30 11:13:01', 0, '2026-08-30 16:12:01'),
(28, 1, '09d8b5efc79382aa360a9ca5654dd1b3dd9f3811c74db48f2272cc539bb8ef3a', '2026-08-30 11:15:37', 0, '2026-08-30 16:14:37'),
(29, 1, '344746488d25d6bf333fb5a1b3fe278f94be9c752ffa4cba5d65726a2ce64843', '2026-08-30 11:15:47', 0, '2026-08-30 16:14:47'),
(30, 1, '19953f7f7ef7a605bc0c5b9ae5c98ebb57ec335e59898d26a2f0e7dbcfb232b4', '2026-08-30 16:18:36', 0, '2026-08-30 16:17:36'),
(31, 1, '0db7db6a166543262c7ce56c0506f9134dcf2b3e2ae69ee7fded62b36efdd71c', '2026-08-30 16:18:54', 1, '2026-08-30 16:17:54'),
(32, 1, 'd9468b981f7b80e29edb48841e66d094abfbdbfa52d73c856c915581a60f2931', '2026-08-30 16:20:04', 1, '2026-08-30 16:19:04'),
(33, 1, 'ab480a69e1ac910b4fc76f4bdf9a83cd8025f35c8b2436a97f12faad9810e91a', '2026-08-30 16:23:02', 1, '2026-08-30 16:22:02'),
(34, 1, '014a136d26f632a541e4ae8c88eb8c798538730016872b33cf79b2a551b8b60c', '2026-08-30 16:24:03', 1, '2026-08-30 16:23:03'),
(35, 1, 'abcec561f64b793ce4f624ae2452e15474f541b4ed61696cf6a7f250fb349479', '2026-08-30 16:24:46', 1, '2026-08-30 16:23:46'),
(36, 1, '1baf175b4fb6b49a30fa600f07c18dd3534d8641f7f3b0033890d19b06cbe8df', '2026-08-30 16:26:15', 1, '2026-08-30 16:25:15'),
(37, 1, 'a1beb5d0adc8abca0ec4818d92723469594970febe8400218600cd069c57d00d', '2026-08-30 16:36:53', 1, '2026-08-30 16:35:53'),
(38, 1, '0df62e5bea78915668cce617987fe0c74f6b886cfeb01f85e2591a6f1bb219b5', '2026-08-30 16:40:27', 1, '2026-08-30 16:39:27'),
(39, 1, 'ac785ba3e3d8afafd1ae2676a88e5c20d1313e47ea62330380f1385e2684a3e9', '2026-08-30 16:48:35', 1, '2026-08-30 16:47:35'),
(40, 1, '5de7d94221ee501e73637f6ac90dea809f87c9c01a4c068b4ae9b7c58ef01ee7', '2026-08-30 16:48:56', 1, '2026-08-30 16:47:56'),
(41, 1, 'd67302c6f7b2649f46344d573a3d6c3cb63ea45c5a68266ccd55bd2d55c7fa47', '2026-08-30 16:50:27', 1, '2026-08-30 16:49:27'),
(42, 1, '7ca25d20a3d9c5ef85d227d0f7c0127d0191e95bb59afc2a0b5a99624e9ba646', '2026-08-30 16:52:03', 1, '2026-08-30 16:51:03'),
(43, 1, 'f8ab4cfc2ab160b5013ac471f9a249279394c547ebcb9abefd4309ece6649cf2', '2026-08-30 17:05:05', 1, '2026-08-30 17:04:05'),
(44, 1, '6167e3520444d36b6f5f3d7f40b610aab0306e560093717c9a2b2188e76cf6cd', '2026-08-30 17:21:40', 1, '2026-08-30 17:20:40'),
(45, 1, '992de9da240901eaa6584d3b7aa73b8aa74be762ed4253ef827768b0e3198dff', '2026-08-30 17:21:46', 1, '2026-08-30 17:20:46'),
(46, 1, 'f73335df5a892911a5c35c1b20d799b1467aabe3ed7ad00e571eea63f6374f6d', '2026-09-03 19:22:35', 1, '2026-09-03 19:21:35'),
(47, 1, 'd63029d256a1fb8ae8702bd027386bf34e354ab7d4fd46d43537347f9e3d7575', '2026-09-03 19:22:48', 1, '2026-09-03 19:21:48'),
(48, 1, '1e3a530a3c8bc4cd91c3c5a6e25a76dcd294f354be61156a8d411a2e30a481d8', '2026-09-04 00:07:14', 1, '2026-09-04 00:06:14'),
(49, 1, '688bcb59e7692a5e02680460c0c1ffcde765ece47b3aae7c337838da9fe7397d', '2026-09-17 14:54:51', 0, '2026-09-17 14:53:51'),
(50, 1, 'a23b2d8e13f791f14c9813b1036ece01af52e2d8af8c073438c7c26aa581420c', '2026-09-17 14:56:18', 1, '2026-09-17 14:55:18'),
(51, 1, '1149035de44e98a4b1e0e96971009da9df3fd8cfad7415e4231ee61569f46347', '2026-09-21 12:51:27', 1, '2026-09-21 12:50:27'),
(52, 1, 'f1d58e5f09c4a00378b83c01f2b84ad39ba21d8b81f9852c2a063dac397ff645', '2026-09-21 13:14:46', 1, '2026-09-21 13:13:46'),
(53, 1, 'a9d0a9c3313636c1fb215da21f2e1f16b2953bee94b789d2c71e0dfde5e7072e', '2026-09-28 16:29:56', 1, '2026-09-28 16:28:56'),
(54, 1, '64e4cec03d2e9daeef9b02927e81fa516be4ce46bc066df4a8888bac74231d1e', '2026-09-28 17:03:26', 1, '2026-09-28 17:02:26'),
(55, 1, '15c03f4eb72a9012e7132a373ac01fa817943e0888e5e4ee5306bf0164fd5e0d', '2026-09-28 17:29:07', 1, '2026-09-28 17:28:07'),
(56, 1, 'daa8e8975eea78fd4fd33d73e9b872e9a9a7ed6a1932e8cfc182322ccbed81de', '2026-09-28 17:34:31', 1, '2026-09-28 17:33:31'),
(57, 1, 'ba0bee0ab3809b4b9489c4d15c8ed2f03d270ff0a2ae57f154f940206c96fe8b', '2026-09-28 18:05:17', 1, '2026-09-28 18:04:17'),
(58, 1, '6d62c2d1c873e41b218fc70e512f1a099328256fb34d35bf0ac442fe2617ba4e', '2026-09-28 19:39:38', 1, '2026-09-28 19:38:38'),
(59, 1, '8b12a2bb3a6420a22515cf4151c5f9ced350ba7007fe71e2964e2536c660cd3e', '2026-09-29 21:48:09', 0, '2026-09-29 21:47:09'),
(60, 1, '0f464a4ce35ccdb32e0e2671613c2ad7646a222c2091c7b44b08ca3b2f1a6446', '2026-09-29 21:49:20', 1, '2026-09-29 21:48:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `email`, `phone`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'jaruwan chinart', 'jaruwan.ch@gmail.com', '0954545862', '$2y$10$YKJp4q2nJ.fuupGKHgvVY.IuJ75OYAZNE.m/VyWyoyWdhcHJjvBJO', 'admin', 'active', '2026-09-03 23:19:24', '2026-09-03 23:24:37'),
(2, 'user', 'nalintip srithamma', 'nalintip.s@gmail.com', '0954831468', '$2y$10$wn36RMKwhfxb1APsSz5g6.jGtaorMFl.51I2M2Zz7ka.MYc2YXvKq', 'user', 'active', '2026-09-03 23:19:24', '2026-09-03 23:24:59'),
(3, 'deejai', 'deejai  jaidee', 'deejai.jai@gmail.com', '0955555487', '$2y$10$beI1FbxOTjF1h93yfNTqoeU.gjQ2U4Uoer9GvsbcaGRz7OXuamZJK', 'user', 'active', '2026-09-03 23:23:27', '2026-09-03 23:25:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_booking_to_room_new` (`room_id`);

--
-- Indexes for table `booking_equipments`
--
ALTER TABLE `booking_equipments`
  ADD PRIMARY KEY (`booking_id`,`node_id`),
  ADD KEY `fk_be_node` (`node_id`);

--
-- Indexes for table `building`
--
ALTER TABLE `building`
  ADD PRIMARY KEY (`building_id`);

--
-- Indexes for table `device_channel`
--
ALTER TABLE `device_channel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `node_id` (`node_id`);

--
-- Indexes for table `installation_node`
--
ALTER TABLE `installation_node`
  ADD PRIMARY KEY (`room_id`,`node_id`),
  ADD KEY `node_id` (`node_id`);

--
-- Indexes for table `node`
--
ALTER TABLE `node`
  ADD PRIMARY KEY (`node_id`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `fk_room_building` (`building_id`);

--
-- Indexes for table `sso_tokens`
--
ALTER TABLE `sso_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `unique_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `sso_tokens`
--
ALTER TABLE `sso_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_booking_to_room_new` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `booking_equipments`
--
ALTER TABLE `booking_equipments`
  ADD CONSTRAINT `fk_be_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_be_node` FOREIGN KEY (`node_id`) REFERENCES `node` (`node_id`) ON DELETE CASCADE;

--
-- Constraints for table `device_channel`
--
ALTER TABLE `device_channel`
  ADD CONSTRAINT `device_channel_ibfk_1` FOREIGN KEY (`node_id`) REFERENCES `node` (`node_id`);

--
-- Constraints for table `installation_node`
--
ALTER TABLE `installation_node`
  ADD CONSTRAINT `installation_node_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `installation_node_ibfk_2` FOREIGN KEY (`node_id`) REFERENCES `node` (`node_id`) ON DELETE CASCADE;

--
-- Constraints for table `room`
--
ALTER TABLE `room`
  ADD CONSTRAINT `fk_room_building` FOREIGN KEY (`building_id`) REFERENCES `building` (`building_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sso_tokens`
--
ALTER TABLE `sso_tokens`
  ADD CONSTRAINT `sso_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
