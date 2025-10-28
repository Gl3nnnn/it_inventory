-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2025 at 03:56 AM
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
-- Database: `it_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_tag` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `action`, `asset_name`, `asset_tag`, `user_id`, `username`, `details`, `timestamp`) VALUES
(1, 'Asset Added', 'qweewqqweqw', 'saas', 3, 'admin', 'Asset added to inventory with status: Assigned', '2025-10-17 06:58:21'),
(2, 'Maintenance Scheduled', 'qweewqqweqw', 'saas', 3, 'admin', 'Maintenance type: Inspection, Scheduled: 2025-10-17', '2025-10-17 07:18:21'),
(3, 'Asset Added', 'qweewqqweqw', '121212', 3, 'admin', 'Asset added to inventory with status: Assigned', '2025-10-18 04:56:14'),
(4, 'Asset Added', 'qweewqqweqwsdfgsdfsdf', 'yeesssssss', 3, 'admin', 'Asset added to inventory with status: Under Repair', '2025-10-18 04:56:35'),
(5, 'Status Changed', 'qweewqqweqwsdfgsdfsdf', 'yeesssssss', 3, 'admin', 'Status changed from \'Under Repair\' to \'Assigned\'', '2025-10-18 04:57:07'),
(6, 'Asset Added', 'wdaw', 'fdfdfge4t45545345', 3, 'admin', 'Asset added to inventory with status: Assigned', '2025-10-18 05:34:21'),
(7, 'Asset Added', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset added to inventory with status: Assigned', '2025-10-18 05:35:41'),
(8, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 05:36:54'),
(9, 'Asset Added', 'hjkhjkjkhhjk', '566t343', 3, 'admin', 'Asset added to inventory with status: Assigned', '2025-10-18 05:37:14'),
(10, 'Asset Added', 'hjkhjkjkhhjksfv', '566t343p', 3, 'admin', 'Asset added to inventory with status: Assigned', '2025-10-18 05:44:58'),
(11, 'Asset Deleted', 'hjkhjkjkhhjksfv', '566t343p', 3, 'admin', 'Asset permanently removed from inventory', '2025-10-18 05:51:25'),
(12, 'Asset Deleted', 'hjkhjkjkhhjk', '566t343', 3, 'admin', 'Asset permanently removed from inventory', '2025-10-18 05:51:28'),
(13, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 05:51:37'),
(14, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 05:52:18'),
(15, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 05:54:11'),
(16, 'Asset Added', 'hjkhjkjkhhjk', '566tffffff', 3, 'admin', 'Asset added to inventory with status: Under Repair', '2025-10-18 05:54:28'),
(17, 'Asset Updated', 'hjkhjkjkhhjk', '566tffffff', 3, 'admin', 'Asset details updated', '2025-10-18 05:54:46'),
(18, 'Asset Updated', 'hjkhjkjkhhjk', '566tffffff', 3, 'admin', 'Asset details updated', '2025-10-18 05:54:55'),
(19, 'Asset Updated', 'hjkhjkjkhhjk', '566tffffff', 3, 'admin', 'Asset details updated', '2025-10-18 05:55:07'),
(20, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 05:56:10'),
(21, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 05:56:34'),
(22, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 05:59:52'),
(23, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 06:00:08'),
(24, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 06:00:17'),
(25, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 06:01:15'),
(26, 'Asset Updated', 'hjkhjkjkhhjk', '566tffffff', 3, 'admin', 'Asset details updated', '2025-10-18 06:01:46'),
(27, 'Asset Updated', 'wdaw', 'fdfdfge4t45545345', 3, 'admin', 'Asset details updated', '2025-10-18 06:01:58'),
(28, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 06:08:14'),
(29, 'Asset Updated', 'hjkhjkjkhhjk', '566t', 3, 'admin', 'Asset details updated', '2025-10-18 06:08:21'),
(30, 'Asset QR Scanned', 'Razer DeathAdder V2', 'IT-010', 3, 'admin', 'Asset looked up via QR scanner', '2025-10-20 07:43:37'),
(31, 'Asset QR Scanned', 'Razer DeathAdder V2', 'IT-010', 3, 'admin', 'Asset looked up via QR scanner', '2025-10-20 07:44:28'),
(32, 'Asset Viewed', 'Razer DeathAdder V2', 'IT-010', 3, 'admin', 'Asset details viewed from QR scanner', '2025-10-20 07:52:04'),
(33, 'Asset QR Scanned', 'Razer DeathAdder V2', 'IT-010', 3, 'admin', 'Asset looked up via QR scanner', '2025-10-20 07:52:21'),
(34, 'Asset Viewed', 'Razer DeathAdder V2', 'IT-010', 3, 'admin', 'Asset details viewed from QR scanner', '2025-10-20 07:52:23'),
(35, 'Asset Viewed', 'Razer DeathAdder V2', 'IT-010', 3, 'admin', 'Asset details viewed from QR scanner', '2025-10-20 07:52:46'),
(36, 'Asset Viewed', 'Razer DeathAdder V2', 'IT-010', 3, 'admin', 'Asset details viewed from QR scanner', '2025-10-20 07:53:10'),
(37, 'Asset QR Scanned', 'Razer DeathAdder V2', 'IT-010', 3, 'admin', 'Asset looked up via QR scanner', '2025-10-20 07:54:10'),
(38, 'Asset Viewed', 'Razer DeathAdder V2', 'IT-010', 3, 'admin', 'Asset details viewed from QR scanner', '2025-10-20 07:54:12'),
(39, 'Asset QR Scanned', 'qweewqqweqw', '121212', 3, 'admin', 'Asset looked up via QR scanner', '2025-10-20 09:11:50'),
(40, 'Asset QR Scanned', 'qweewqqweqw', '121212', 3, 'admin', 'Asset looked up via QR scanner', '2025-10-20 09:11:59'),
(41, 'Asset QR Scanned', 'qweewqqweqw', '121212', 3, 'admin', 'Asset looked up via QR scanner', '2025-10-20 09:12:05');

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `asset_tag` varchar(100) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `status` enum('In Storage','Assigned','Under Repair','Disposed') NOT NULL DEFAULT 'In Storage',
  `acquisition_date` date DEFAULT NULL,
  `item_lifespan` int(11) DEFAULT NULL,
  `disposal_method` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo_path` varchar(255) DEFAULT NULL,
  `document_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`document_paths`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `asset_tag`, `asset_name`, `category`, `location`, `status`, `acquisition_date`, `item_lifespan`, `disposal_method`, `created_at`, `photo_path`, `document_paths`) VALUES
(2, 'saas', 'qweewqqweqw', 'asdasasassa', 'weqweqwe', 'Assigned', '2025-10-08', 111, 'Recycle', '2025-10-17 06:58:21', NULL, NULL),
(3, '121212', 'qweewqqweqw', 'asdasasassa', 'weqweqwe', 'Assigned', '2025-10-08', 111, 'Sell', '2025-10-18 04:56:14', NULL, NULL),
(4, 'yeesssssss', 'qweewqqweqwsdfgsdfsdf', 'asd', 'weqweqwe', 'Assigned', '2025-10-08', 111, 'Sell', '2025-10-18 04:56:35', NULL, NULL),
(5, 'IT-001', 'Dell XPS 15 Laptop', 'Laptop', 'Room 101', 'Assigned', '2025-09-04', 36, NULL, '2025-09-03 23:00:00', NULL, NULL),
(6, 'IT-002', 'HP EliteBook 840 G8', 'Laptop', 'Room 102', 'Assigned', '2025-09-04', 36, NULL, '2025-09-03 23:05:00', NULL, NULL),
(7, 'IT-003', 'MacBook Pro 16\"', 'Laptop', 'Room 103', 'Assigned', '2025-09-04', 36, NULL, '2025-09-03 23:10:00', NULL, NULL),
(8, 'IT-004', 'Lenovo ThinkPad X1', 'Laptop', 'Room 104', 'Assigned', '2025-09-04', 36, NULL, '2025-09-03 23:15:00', NULL, NULL),
(9, 'IT-005', 'Dell 27\" Monitor', 'Monitor', 'Room 101', 'Assigned', '2025-09-04', 60, NULL, '2025-09-03 23:20:00', NULL, NULL),
(10, 'IT-006', 'Samsung 32\" Curved Monitor', 'Monitor', 'Room 102', 'Assigned', '2025-09-04', 60, NULL, '2025-09-03 23:25:00', NULL, NULL),
(11, 'IT-007', 'LG 24\" IPS Monitor', 'Monitor', 'Room 103', 'Assigned', '2025-09-04', 60, NULL, '2025-09-03 23:30:00', NULL, NULL),
(12, 'IT-008', 'ASUS ROG Monitor 27\"', 'Monitor', 'Room 104', 'Assigned', '2025-09-04', 60, NULL, '2025-09-03 23:35:00', NULL, NULL),
(13, 'IT-009', 'Logitech MX Master 3', 'Mouse', 'Room 101', 'Assigned', '2025-09-04', 24, NULL, '2025-09-03 23:40:00', NULL, NULL),
(14, 'IT-010', 'Razer DeathAdder V2', 'Mouse', 'Room 102', 'Assigned', '2025-09-04', 24, NULL, '2025-09-03 23:45:00', NULL, NULL),
(15, 'IT-011', 'Microsoft Sculpt Mouse', 'Mouse', 'Room 103', 'Assigned', '2025-09-04', 24, NULL, '2025-09-03 23:50:00', NULL, NULL),
(16, 'IT-012', 'Apple Magic Mouse 2', 'Mouse', 'Room 104', 'Assigned', '2025-09-04', 24, NULL, '2025-09-03 23:55:00', NULL, NULL),
(17, 'IT-013', 'Logitech K380 Keyboard', 'Keyboard', 'Room 101', 'Assigned', '2025-09-04', 24, NULL, '2025-09-04 00:00:00', NULL, NULL),
(18, 'IT-014', 'Mechanical Gaming Keyboard', 'Keyboard', 'Room 102', 'Assigned', '2025-09-04', 24, NULL, '2025-09-04 00:05:00', NULL, NULL),
(19, 'IT-015', 'Microsoft Surface Keyboard', 'Keyboard', 'Room 103', 'Assigned', '2025-09-04', 24, NULL, '2025-09-04 00:10:00', NULL, NULL),
(20, 'IT-016', 'Apple Magic Keyboard', 'Keyboard', 'Room 104', 'Assigned', '2025-09-04', 24, NULL, '2025-09-04 00:15:00', NULL, NULL),
(21, 'IT-017', 'Cisco Router 2901', 'Network Equipment', 'Server Room', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 00:20:00', NULL, NULL),
(22, 'IT-018', 'Ubiquiti UniFi AP AC', 'Network Equipment', 'Server Room', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 00:25:00', NULL, NULL),
(23, 'IT-019', 'TP-Link Gigabit Switch', 'Network Equipment', 'Server Room', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 00:30:00', NULL, NULL),
(24, 'IT-020', 'Netgear Nighthawk Router', 'Network Equipment', 'Server Room', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 00:35:00', NULL, NULL),
(25, 'IT-021', 'HP LaserJet Pro M182nw', 'Printer', 'Room 105', 'Assigned', '2025-09-04', 48, NULL, '2025-09-04 00:40:00', NULL, NULL),
(26, 'IT-022', 'Brother MFC-L2710DW', 'Printer', 'Room 106', 'Assigned', '2025-09-04', 48, NULL, '2025-09-04 00:45:00', NULL, NULL),
(27, 'IT-023', 'Epson EcoTank ET-8550', 'Printer', 'Room 107', 'Assigned', '2025-09-04', 48, NULL, '2025-09-04 00:50:00', NULL, NULL),
(28, 'IT-024', 'Canon PIXMA TR8520', 'Printer', 'Room 108', 'Assigned', '2025-09-04', 48, NULL, '2025-09-04 00:55:00', NULL, NULL),
(29, 'IT-025', 'iPad Pro 12.9\"', 'Tablet', 'Room 201', 'Assigned', '2025-09-04', 36, NULL, '2025-09-04 01:00:00', NULL, NULL),
(30, 'IT-026', 'Samsung Galaxy Tab S8', 'Tablet', 'Room 202', 'Assigned', '2025-09-04', 36, NULL, '2025-09-04 01:05:00', NULL, NULL),
(31, 'IT-027', 'Microsoft Surface Pro 8', 'Tablet', 'Room 203', 'Assigned', '2025-09-04', 36, NULL, '2025-09-04 01:10:00', NULL, NULL),
(32, 'IT-028', 'Lenovo Tab P12 Pro', 'Tablet', 'Room 204', 'Assigned', '2025-09-04', 36, NULL, '2025-09-04 01:15:00', NULL, NULL),
(33, 'IT-029', 'Dell PowerEdge R750', 'Server', 'Data Center', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 01:20:00', NULL, NULL),
(34, 'IT-030', 'HP ProLiant DL380', 'Server', 'Data Center', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 01:25:00', NULL, NULL),
(35, 'IT-031', 'Supermicro Server', 'Server', 'Data Center', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 01:30:00', NULL, NULL),
(36, 'IT-032', 'IBM System x3650', 'Server', 'Data Center', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 01:35:00', NULL, NULL),
(37, 'IT-033', 'Western Digital 4TB HDD', 'Storage', 'Server Room', 'Assigned', '2025-09-04', 36, NULL, '2025-09-04 01:40:00', NULL, NULL),
(38, 'IT-034', 'Samsung 970 EVO 1TB SSD', 'Storage', 'Server Room', 'Assigned', '2025-09-04', 36, NULL, '2025-09-04 01:45:00', NULL, NULL),
(39, 'IT-035', 'Seagate Barracuda 2TB', 'Storage', 'Server Room', 'Assigned', '2025-09-04', 36, NULL, '2025-09-04 01:50:00', NULL, NULL),
(40, 'IT-036', 'Crucial MX500 500GB', 'Storage', 'Server Room', 'Assigned', '2025-09-04', 36, NULL, '2025-09-04 01:55:00', NULL, NULL),
(41, 'IT-037', 'Logitech Webcam C920', 'Webcam', 'Room 101', 'Assigned', '2025-09-04', 24, NULL, '2025-09-04 02:00:00', NULL, NULL),
(42, 'IT-038', 'Microsoft LifeCam HD-3000', 'Webcam', 'Room 102', 'Assigned', '2025-09-04', 24, NULL, '2025-09-04 02:05:00', NULL, NULL),
(43, 'IT-039', 'Razer Kiyo Streaming Webcam', 'Webcam', 'Room 103', 'Assigned', '2025-09-04', 24, NULL, '2025-09-04 02:10:00', NULL, NULL),
(44, 'IT-040', 'Acer Predator XB273U', 'Monitor', 'Room 105', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 02:15:00', NULL, NULL),
(45, 'IT-041', 'BenQ PD2700U', 'Monitor', 'Room 106', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 02:20:00', NULL, NULL),
(46, 'IT-042', 'ViewSonic VX3276-4K', 'Monitor', 'Room 107', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 02:25:00', NULL, NULL),
(47, 'IT-043', 'AOC CU32V2', 'Monitor', 'Room 108', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 02:30:00', NULL, NULL),
(48, 'IT-044', 'Jabra Speak 710', 'Speaker', 'Conference Room A', 'Assigned', '2025-09-04', 24, NULL, '2025-09-04 02:35:00', NULL, NULL),
(49, 'IT-045', 'Sony SRS-XB43', 'Speaker', 'Conference Room B', 'Assigned', '2025-09-04', 24, NULL, '2025-09-04 02:40:00', NULL, NULL),
(50, 'IT-046', 'Bose SoundLink Color', 'Speaker', 'Conference Room C', 'Assigned', '2025-09-04', 24, NULL, '2025-09-04 02:45:00', NULL, NULL),
(51, 'IT-047', 'Anker Soundcore 2', 'Speaker', 'Conference Room D', 'Assigned', '2025-09-04', 24, NULL, '2025-09-04 02:50:00', NULL, NULL),
(52, 'IT-048', 'Cisco IP Phone 8841', 'Phone', 'Room 301', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 02:55:00', NULL, NULL),
(53, 'IT-049', 'Polycom VVX 401', 'Phone', 'Room 302', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 03:00:00', NULL, NULL),
(54, 'IT-050', 'Yealink T46S', 'Phone', 'Room 303', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 03:05:00', NULL, NULL),
(55, 'IT-051', 'Grandstream GXP1625', 'Phone', 'Room 304', 'Assigned', '2025-09-04', 60, NULL, '2025-09-04 03:10:00', NULL, NULL),
(56, 'fdfdfge4t45545345', 'wdaw', 'das', 'asdsa', 'Assigned', '2025-10-18', 1, 'Recycle', '2025-10-18 05:34:21', 'uploads/assets/photos/asset_photo_68f32d56d421f_1760767318.gif', '[{\"name\":\"asset_stickers (44).pdf\",\"path\":\"uploads\\/assets\\/documents\\/asset_doc_68f326dd119b9_1760765661_0.pdf\",\"size\":154118,\"type\":\"application\\/pdf\"}]'),
(57, '566t', 'hjkhjkjkhhjk', 'hjkhjk', 'jhjkhjhjk', 'Assigned', '2025-10-18', 23, 'Recycle', '2025-10-18 05:35:41', 'uploads/assets/photos/asset_photo_68f32ed5a02b0_1760767701.jpg', NULL),
(60, '566tffffff', 'hjkhjkjkhhjk', 'hjkhjk', 'jhjkhjhjk', 'Under Repair', '2025-10-18', 23, 'Recycle', '2025-10-18 05:54:28', 'uploads/assets/photos/asset_photo_68f32d4a7c705_1760767306.jpg', '[{\"name\":\"ITD-03-Management-of-I.T.-Inrastructure5.pdf\",\"path\":\"uploads\\/assets\\/documents\\/asset_doc_68f32bbb3be80_1760766907_0.pdf\",\"size\":797341,\"type\":\"application\\/pdf\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `maintenance_type` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Scheduled',
  `priority` varchar(50) NOT NULL DEFAULT 'Medium',
  `assigned_to` varchar(255) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`id`, `asset_id`, `maintenance_type`, `description`, `scheduled_date`, `completed_date`, `status`, `priority`, `assigned_to`, `cost`, `notes`, `created_by`, `created_at`) VALUES
(1, 2, 'Inspection', 'asdasdasd', '2025-10-17', NULL, 'Overdue', 'Critical', 'sad', 1.00, '', 3, '2025-10-17 07:18:21');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `link`, `type`, `is_read`, `created_at`) VALUES
(22, 3, 'Maintenance Today', 'You have 1 maintenance tasks scheduled for today', 'maintenance.php?date_from=2025-10-17&date_to=2025-10-17', 'info', 1, '2025-10-17 07:52:46'),
(23, 3, 'Overdue Maintenance', 'You have 1 overdue maintenance tasks that need attention', 'maintenance.php?status=Overdue', 'danger', 1, '2025-10-18 02:22:32'),
(24, 3, 'Overdue Maintenance', 'You have 1 overdue maintenance tasks that need attention', 'maintenance.php?status=Overdue', 'danger', 0, '2025-10-18 02:38:52'),
(25, 3, 'Overdue Maintenance', 'You have 1 overdue maintenance tasks that need attention', 'maintenance.php?status=Overdue', 'danger', 0, '2025-10-20 07:26:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `display_name` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `language` varchar(10) DEFAULT 'en'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `display_name`, `profile_picture`, `language`) VALUES
(3, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-09-04 06:28:44', 'Kanrisha', 'profile_3_1760759081.gif', 'en');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
