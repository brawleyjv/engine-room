-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2025 at 07:54 PM
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
-- Database: `vesseldata`
--

-- --------------------------------------------------------

--
-- Table structure for table `gears`
--

CREATE TABLE `gears` (
  `EntryID` int(11) NOT NULL,
  `VesselID` int(11) NOT NULL DEFAULT 1,
  `Side` enum('Port','Starboard','Center Main') NOT NULL,
  `EntryDate` date NOT NULL,
  `OilPress` int(3) NOT NULL,
  `Temp` int(3) NOT NULL,
  `Notes` text DEFAULT NULL,
  `RecordedBy` varchar(100) DEFAULT NULL,
  `Timestamp` datetime DEFAULT current_timestamp(),
  `GearHrs` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gears`
--

INSERT INTO `gears` (`EntryID`, `VesselID`, `Side`, `EntryDate`, `OilPress`, `Temp`, `Notes`, `RecordedBy`, `Timestamp`, `GearHrs`) VALUES
(15, 3, 'Port', '2025-07-24', 340, 108, '', 'Jack Brawley', '2025-07-25 16:25:31', 19736),
(16, 3, 'Starboard', '2025-07-24', 340, 110, '', 'Jack Brawley', '2025-07-25 16:25:56', 19671),
(17, 3, 'Port', '2025-07-25', 365, 115, '', 'Jack Brawley', '2025-07-25 16:46:50', 19760),
(18, 3, 'Starboard', '2025-07-25', 360, 140, '', 'Jack Brawley', '2025-07-25 16:47:09', 19695),
(19, 3, 'Port', '2025-07-23', 360, 136, '', 'Barley Johns', '2025-07-25 16:50:46', 19711),
(20, 3, 'Starboard', '2025-07-23', 362, 137, '', 'Barley Johns', '2025-07-25 16:53:28', 19646),
(21, 3, 'Port', '2025-07-22', 365, 141, '', 'Barley Johns', '2025-07-25 17:04:14', 19687),
(22, 3, 'Starboard', '2025-07-22', 365, 141, '', 'Barley Johns', '2025-07-25 17:04:37', 19622),
(23, 3, 'Port', '2025-07-21', 340, 122, '', 'Barley Johns', '2025-07-25 17:07:08', 19665),
(24, 3, 'Starboard', '2025-07-21', 320, 125, '', 'Barley Johns', '2025-07-25 17:07:32', 19600),
(25, 3, 'Port', '2025-07-20', 379, 135, '', 'Barley Johns', '2025-07-25 17:19:16', 19640),
(26, 3, 'Starboard', '2025-07-20', 378, 133, '', 'Barley Johns', '2025-07-25 17:19:43', 19575),
(27, 3, 'Port', '2025-07-19', 345, 125, '', 'Barley Johns', '2025-07-25 17:23:08', 19616),
(28, 3, 'Starboard', '2025-07-19', 340, 121, '', 'Barley Johns', '2025-07-25 17:23:37', 19551),
(29, 3, 'Port', '2025-07-18', 378, 139, '', '2', '2025-07-25 19:15:27', 19593),
(30, 3, 'Starboard', '2025-07-18', 375, 134, '', '2', '2025-07-25 19:15:58', 19528),
(31, 3, 'Port', '2025-07-17', 370, 136, '', '2', '2025-07-25 19:22:27', 19568),
(32, 3, 'Starboard', '2025-07-17', 365, 128, '', '2', '2025-07-25 19:22:57', 19503),
(33, 3, 'Port', '2025-07-16', 340, 119, '', '2', '2025-07-25 19:26:21', 19546),
(34, 3, 'Starboard', '2025-07-16', 340, 118, '', '2', '2025-07-25 19:26:56', 19481),
(35, 3, 'Port', '2025-07-15', 340, 116, '', '2', '2025-07-25 19:30:46', 19532),
(36, 3, 'Starboard', '2025-07-15', 340, 114, '', '2', '2025-07-25 19:31:12', 19464),
(37, 3, 'Port', '2025-07-14', 340, 118, '', '2', '2025-07-25 19:33:26', 19521),
(38, 3, 'Starboard', '2025-07-14', 340, 116, '', '2', '2025-07-25 19:33:48', 19452),
(39, 3, 'Port', '2025-07-11', 340, 115, '', '2', '2025-07-25 19:52:39', 19500),
(40, 3, 'Starboard', '2025-07-11', 340, 111, '', '2', '2025-07-25 19:53:36', 19431),
(41, 3, 'Port', '2025-07-10', 360, 125, '', '2', '2025-07-25 19:57:29', 19476),
(42, 3, 'Starboard', '2025-07-10', 358, 118, '', '2', '2025-07-25 19:57:53', 19407),
(43, 3, 'Port', '2025-07-09', 360, 130, '', '2', '2025-07-25 20:02:25', 19451),
(44, 3, 'Starboard', '2025-07-09', 360, 130, '', '2', '2025-07-25 20:02:56', 19383),
(45, 3, 'Port', '2025-07-08', 340, 110, '', '2', '2025-07-25 20:05:31', 19434),
(46, 3, 'Starboard', '2025-07-08', 340, 110, '', '2', '2025-07-25 20:06:02', 19365),
(47, 3, 'Port', '2025-06-22', 360, 105, '', '2', '2025-07-25 20:15:28', 19383),
(48, 3, 'Starboard', '2025-06-22', 360, 112, '', '2', '2025-07-25 20:15:55', 19315),
(49, 3, 'Port', '2025-07-26', 360, 118, '', '2', '2025-07-26 17:39:20', 19784),
(50, 3, 'Starboard', '2025-07-26', 345, 112, '', '2', '2025-07-26 17:39:44', 19719),
(51, 4, 'Port', '2025-07-27', 55, 555, '', '2', '2025-07-27 10:47:50', 555555),
(52, 4, 'Starboard', '2025-07-27', 66, 222, '', '2', '2025-07-27 10:48:07', 666666),
(53, 4, 'Center Main', '2025-07-27', 22, 222, '', '2', '2025-07-27 10:55:25', 5555),
(54, 3, 'Port', '2025-07-31', 358, 106, '', '2', '2025-07-31 17:21:19', 19856),
(55, 3, 'Starboard', '2025-07-31', 345, 111, '', '2', '2025-07-31 17:21:39', 19791);

-- --------------------------------------------------------

--
-- Table structure for table `generators`
--

CREATE TABLE `generators` (
  `EntryID` int(11) NOT NULL,
  `VesselID` int(11) NOT NULL DEFAULT 1,
  `Side` enum('Port','Starboard','Center Main') NOT NULL,
  `EntryDate` date NOT NULL,
  `FuelPress` int(3) NOT NULL,
  `OilPress` int(3) NOT NULL,
  `WaterTemp` int(3) NOT NULL,
  `Notes` text DEFAULT NULL,
  `RecordedBy` varchar(100) NOT NULL,
  `Timestamp` datetime DEFAULT current_timestamp(),
  `GenHrs` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `generators`
--

INSERT INTO `generators` (`EntryID`, `VesselID`, `Side`, `EntryDate`, `FuelPress`, `OilPress`, `WaterTemp`, `Notes`, `RecordedBy`, `Timestamp`, `GenHrs`) VALUES
(16, 3, 'Starboard', '2025-07-24', 65, 59, 168, '', 'Jack Brawley', '2025-07-25 16:24:56', 14712),
(17, 3, 'Starboard', '2025-07-25', 63, 59, 168, '', 'Jack Brawley', '2025-07-25 16:46:15', 14731),
(18, 3, 'Starboard', '2025-07-24', 63, 59, 168, '', 'Barley Johns', '2025-07-25 16:50:17', 14687),
(19, 3, 'Starboard', '2025-07-22', 66, 58, 168, '', 'Barley Johns', '2025-07-25 17:03:38', 14663),
(20, 3, 'Starboard', '2025-07-21', 64, 59, 172, '', 'Barley Johns', '2025-07-25 17:06:42', 14639),
(21, 3, 'Starboard', '2025-07-20', 64, 59, 168, '', 'Barley Johns', '2025-07-25 17:18:29', 14615),
(22, 3, 'Starboard', '2025-07-19', 62, 60, 167, '', 'Barley Johns', '2025-07-25 17:22:40', 14591),
(23, 3, 'Starboard', '2025-07-18', 63, 59, 168, '', '2', '2025-07-25 19:14:42', 14567),
(24, 3, 'Starboard', '2025-07-17', 67, 60, 167, '', '2', '2025-07-25 19:21:56', 14543),
(25, 3, 'Starboard', '2025-07-16', 64, 60, 167, '', '2', '2025-07-25 19:25:57', 14519),
(26, 3, 'Port', '2025-07-15', 64, 58, 165, '', '2', '2025-07-25 19:29:52', 14103),
(27, 3, 'Port', '2025-07-14', 64, 58, 165, '', '2', '2025-07-25 19:33:04', 14075),
(28, 3, 'Port', '2025-07-13', 64, 58, 167, '', '2', '2025-07-25 19:40:35', 14051),
(29, 3, 'Port', '2025-07-12', 64, 58, 167, '', '2', '2025-07-25 19:41:15', 14027),
(30, 3, 'Port', '2025-07-11', 64, 58, 167, '', '2', '2025-07-25 19:51:38', 14003),
(31, 3, 'Port', '2025-07-10', 64, 58, 165, '', '2', '2025-07-25 19:56:59', 13979),
(32, 3, 'Port', '2025-07-09', 65, 59, 165, '', '2', '2025-07-25 20:01:52', 13955),
(33, 3, 'Port', '2025-07-08', 64, 58, 167, '', '2', '2025-07-25 20:05:03', 13931),
(34, 3, 'Starboard', '2025-06-22', 66, 60, 167, '', '2', '2025-07-25 20:13:57', 14437),
(35, 3, 'Starboard', '2025-07-26', 66, 59, 168, '', '2', '2025-07-26 17:38:48', 14755),
(36, 3, 'Port', '2025-07-30', 64, 58, 165, '', '2', '2025-07-31 00:15:40', 14171),
(37, 3, 'Port', '2025-07-31', 64, 58, 165, '', '2', '2025-07-31 17:20:59', 14195);

-- --------------------------------------------------------

--
-- Table structure for table `mainengines`
--

CREATE TABLE `mainengines` (
  `EntryID` int(11) NOT NULL,
  `VesselID` int(11) NOT NULL DEFAULT 1,
  `Side` enum('Port','Starboard','Center Main') NOT NULL,
  `EntryDate` date NOT NULL,
  `RPM` int(3) NOT NULL,
  `OilPressure` int(3) NOT NULL,
  `WaterTemp` int(3) NOT NULL,
  `Notes` text DEFAULT NULL,
  `RecordedBy` varchar(100) NOT NULL,
  `Timestamp` datetime DEFAULT current_timestamp(),
  `MainHrs` int(11) NOT NULL,
  `FuelPress` int(3) NOT NULL,
  `OilTemp` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mainengines`
--

INSERT INTO `mainengines` (`EntryID`, `VesselID`, `Side`, `EntryDate`, `RPM`, `OilPressure`, `WaterTemp`, `Notes`, `RecordedBy`, `Timestamp`, `MainHrs`, `FuelPress`, `OilTemp`) VALUES
(24, 3, 'Port', '2025-07-24', 940, 49, 174, '', 'Jack Brawley', '2025-07-25 16:23:33', 19736, 93, 192),
(25, 3, 'Starboard', '2025-07-24', 950, 49, 170, '', 'Jack Brawley', '2025-07-25 16:24:13', 19671, 92, 185),
(26, 3, 'Port', '2025-07-25', 1740, 69, 177, '', 'Jack Brawley', '2025-07-25 16:44:43', 19760, 115, 221),
(27, 3, 'Starboard', '2025-07-25', 1740, 74, 177, '', 'Jack Brawley', '2025-07-25 16:45:40', 19695, 104, 209),
(28, 3, 'Port', '2025-07-23', 1570, 64, 177, '', 'Barley Johns', '2025-07-25 16:48:51', 19711, 108, 216),
(29, 3, 'Starboard', '2025-07-23', 1570, 70, 176, '', 'Barley Johns', '2025-07-25 16:49:44', 19646, 102, 202),
(30, 3, 'Port', '2025-07-22', 1730, 67, 177, '', 'Barley Johns', '2025-07-25 17:00:41', 19687, 112, 225),
(31, 3, 'Starboard', '2025-07-22', 1730, 74, 176, '', 'Barley Johns', '2025-07-25 17:03:01', 19622, 104, 208),
(32, 3, 'Port', '2025-07-21', 650, 37, 177, '', 'Barley Johns', '2025-07-25 17:05:23', 19665, 90, 183),
(33, 3, 'Starboard', '2025-07-21', 650, 37, 170, '', 'Barley Johns', '2025-07-25 17:06:03', 19600, 86, 178),
(34, 3, 'Port', '2025-07-20', 1740, 70, 177, '', 'Barley Johns', '2025-07-25 17:17:09', 19640, 118, 217),
(35, 3, 'Starboard', '2025-07-20', 1740, 75, 176, '', 'Barley Johns', '2025-07-25 17:17:54', 19575, 106, 212),
(36, 3, 'Port', '2025-07-19', 1020, 50, 174, '', 'Barley Johns', '2025-07-25 17:21:07', 19616, 97, 188),
(37, 3, 'Starboard', '2025-07-19', 1020, 50, 172, '', 'Barley Johns', '2025-07-25 17:22:11', 19551, 92, 185),
(40, 3, 'Port', '2025-07-18', 1740, 60, 177, '', '2', '2025-07-25 19:06:02', 19593, 110, 224),
(41, 3, 'Starboard', '2025-07-18', 1740, 73, 176, '', '2', '2025-07-25 19:14:07', 19528, 105, 210),
(42, 3, 'Port', '2025-07-17', 1740, 67, 177, '', '2', '2025-07-25 19:20:51', 19568, 111, 223),
(43, 3, 'Starboard', '2025-07-17', 1730, 73, 177, '', '2', '2025-07-25 19:21:24', 19503, 104, 210),
(44, 3, 'Port', '2025-07-16', 1220, 72, 145, '', '2', '2025-07-25 19:24:53', 19546, 104, 154),
(45, 3, 'Starboard', '2025-07-16', 1220, 63, 177, '', '2', '2025-07-25 19:25:32', 19481, 97, 179),
(46, 3, 'Port', '2025-07-15', 1160, 55, 165, '', '2', '2025-07-25 19:28:48', 19532, 99, 188),
(47, 3, 'Starboard', '2025-07-15', 1150, 55, 172, '', '2', '2025-07-25 19:29:18', 19469, 96, 190),
(48, 3, 'Port', '2025-07-14', 1120, 57, 156, '', '2', '2025-07-25 19:32:11', 19521, 97, 182),
(49, 3, 'Starboard', '2025-07-14', 1130, 55, 179, '', '2', '2025-07-25 19:32:42', 19452, 95, 188),
(50, 3, 'Port', '2025-07-11', 840, 46, 145, '', '2', '2025-07-25 19:42:06', 19500, 91, 164),
(51, 3, 'Starboard', '2025-07-11', 830, 43, 170, '', '2', '2025-07-25 19:50:05', 19431, 90, 179),
(52, 3, 'Port', '2025-07-10', 1340, 100, 176, '', '2', '2025-07-25 19:55:07', 19476, 100, 207),
(53, 3, 'Starboard', '2025-07-10', 1340, 60, 176, '', '2', '2025-07-25 19:56:07', 19407, 90, 197),
(54, 3, 'Port', '2025-07-09', 1520, 63, 177, '', '2', '2025-07-25 20:00:14', 19451, 103, 211),
(55, 3, 'Starboard', '2025-07-09', 1509, 68, 176, '', '2', '2025-07-25 20:01:17', 19383, 102, 200),
(56, 3, 'Port', '2025-07-08', 650, 37, 174, '', '2', '2025-07-25 20:03:42', 19434, 90, 182),
(57, 3, 'Starboard', '2025-07-08', 650, 37, 170, '', '2', '2025-07-25 20:04:24', 19365, 87, 178),
(58, 3, 'Port', '2025-06-22', 1220, 55, 177, '', '2', '2025-07-25 20:12:37', 19383, 110, 201),
(59, 3, 'Starboard', '2025-06-22', 1220, 56, 176, '', '2', '2025-07-25 20:13:16', 19315, 96, 196),
(60, 3, 'Port', '2025-07-26', 1350, 58, 179, '', '2', '2025-07-26 17:37:58', 19784, 104, 208),
(61, 3, 'Starboard', '2025-07-26', 1350, 62, 174, '', '2', '2025-07-26 17:38:25', 19719, 99, 197),
(62, 4, 'Center Main', '2025-07-27', 900, 40, 180, '', '2', '2025-07-27 09:40:10', 1000, 22, 210),
(63, 4, 'Center Main', '2025-07-27', 922, 40, 158, '', '2', '2025-07-27 10:08:25', 100000, 20, 200),
(64, 4, 'Port', '2025-07-27', 800, 42, 188, '', '2', '2025-07-27 10:13:34', 20000, 22, 222),
(65, 4, 'Center Main', '2025-07-27', 500, 45, 188, '', '2', '2025-07-27 10:17:42', 555555, 25, 230),
(66, 4, 'Center Main', '2025-07-27', 800, 55, 180, '', '2', '2025-07-27 10:23:53', 666666, 22, 222),
(67, 4, 'Center Main', '2025-07-27', 555, 22, 222, '', '2', '2025-07-27 10:37:42', 111111, 33, 333),
(68, 4, 'Center Main', '2025-07-27', 1800, 45, 180, '', '2', '2025-07-27 10:40:29', 66257, 25, 210),
(69, 4, 'Center Main', '2025-07-27', 1800, 45, 180, 'TEST DIRECT INSERT', '2', '2025-07-27 10:42:12', 99999, 25, 210),
(70, 4, 'Center Main', '2025-07-27', 1800, 45, 180, 'MANUAL PREPARED TEST', '2', '2025-07-27 10:42:12', 88888, 25, 210),
(71, 4, 'Center Main', '2025-07-27', 1800, 45, 180, 'SCHEMA FIX TEST', '2', '2025-07-27 10:46:41', 77777, 25, 210),
(72, 4, 'Center Main', '2025-07-27', 1800, 45, 180, 'SCHEMA FIX TEST', '2', '2025-07-27 10:53:46', 77777, 25, 210),
(73, 4, 'Port', '2025-07-28', 500, 38, 185, '', '1', '2025-07-27 20:48:23', 555555, 22, 200),
(74, 4, 'Center Main', '2025-07-27', 1800, 45, 180, 'TEST DIRECT INSERT', '2', '2025-07-30 12:43:40', 99999, 25, 210),
(75, 4, 'Center Main', '2025-07-27', 1800, 45, 180, 'MANUAL PREPARED TEST', '2', '2025-07-30 12:43:40', 88888, 25, 210),
(76, 4, 'Port', '2025-07-31', 1260, 57, 177, '', '1', '2025-07-31 17:19:22', 19856, 101, 206),
(77, 3, 'Port', '2025-07-31', 1260, 57, 177, '', '2', '2025-07-31 17:20:07', 19856, 101, 206),
(78, 3, 'Starboard', '2025-07-31', 1260, 59, 176, '', '2', '2025-07-31 17:20:39', 19791, 97, 194);

-- --------------------------------------------------------

--
-- Table structure for table `navigation_data`
--

CREATE TABLE `navigation_data` (
  `id` int(11) NOT NULL,
  `vessel_id` int(11) NOT NULL,
  `vessel_name` varchar(255) NOT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `eta` datetime DEFAULT NULL,
  `vessel_timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `IsAdmin` tinyint(1) DEFAULT 0,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `LastLogin` timestamp NULL DEFAULT NULL,
  `ResetToken` varchar(100) DEFAULT NULL,
  `ResetTokenExpiry` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Username`, `Email`, `PasswordHash`, `FirstName`, `LastName`, `IsAdmin`, `IsActive`, `CreatedDate`, `LastLogin`, `ResetToken`, `ResetTokenExpiry`) VALUES
(1, 'admin', 'admin@vessel.local', '$2y$10$ugTuP6EfjQqsiYjSyghi5e9Dxpv7jdzCMwQNPMPpmPM0UZDhuyKNK', 'Admin', 'User', 1, 1, '2025-07-25 23:29:26', '2025-07-28 01:47:07', NULL, NULL),
(2, 'jvbrawley', 'brawley.jv@gmail.com', '$2y$10$i3o/npSrEfvyBeDeflT1FuNg82PnbYDmtL3pFwvwwcJ.QX3TNAJ0G', 'Jack', 'Brawley', 1, 1, '2025-07-25 23:46:50', '2025-08-01 01:18:49', NULL, NULL),
(3, 'barley', 'barley@somewhere.com', '$2y$10$ryCV1ktrVeZ18vq9eVYUj.gFWtU.tsICrooqoTLDz4rYmecN8rNEe', 'Barley', 'Johns', 0, 1, '2025-07-26 00:11:57', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vessels`
--

CREATE TABLE `vessels` (
  `VesselID` int(11) NOT NULL,
  `VesselName` varchar(100) NOT NULL,
  `VesselType` varchar(50) DEFAULT 'Fishing Vessel',
  `EngineConfig` enum('standard','three_engine') DEFAULT 'standard',
  `Owner` varchar(100) DEFAULT NULL,
  `YearBuilt` year(4) DEFAULT NULL,
  `Length` decimal(6,2) DEFAULT NULL,
  `CreatedDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(1) DEFAULT 1,
  `Notes` text DEFAULT NULL,
  `RPMMin` int(11) DEFAULT 650,
  `RPMMax` int(11) DEFAULT 1750,
  `TempMin` int(11) DEFAULT 20,
  `TempMax` int(11) DEFAULT 400,
  `PressureMin` int(11) DEFAULT 20,
  `PressureMax` int(11) DEFAULT 400,
  `GenMin` int(11) DEFAULT 20,
  `GenMax` int(11) DEFAULT 400
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vessels`
--

INSERT INTO `vessels` (`VesselID`, `VesselName`, `VesselType`, `EngineConfig`, `Owner`, `YearBuilt`, `Length`, `CreatedDate`, `IsActive`, `Notes`, `RPMMin`, `RPMMax`, `TempMin`, `TempMax`, `PressureMin`, `PressureMax`, `GenMin`, `GenMax`) VALUES
(3, 'Rusty Zeller', 'Towboat', 'standard', 'Florida Marine Transporters', '2021', 120.00, '2025-07-25 20:59:27', 1, 'Formerly Dave B Fate', 650, 1750, 20, 380, 20, 400, 20, 185),
(4, 'Test Vessel', 'Towboat', 'three_engine', 'imagine towing', '0000', 44.00, '2025-07-27 14:25:11', 1, 'test vessel', 300, 900, 20, 400, 20, 400, 20, 400);

-- --------------------------------------------------------

--
-- Table structure for table `vessel_logs`
--

CREATE TABLE `vessel_logs` (
  `id` int(11) NOT NULL,
  `vessel_id` int(11) NOT NULL,
  `vessel_name` varchar(255) NOT NULL,
  `log_entry` text NOT NULL,
  `logged_by` varchar(100) NOT NULL,
  `vessel_timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `gears`
--
ALTER TABLE `gears`
  ADD PRIMARY KEY (`EntryID`),
  ADD KEY `idx_gears_vessel_date` (`VesselID`,`EntryDate`);

--
-- Indexes for table `generators`
--
ALTER TABLE `generators`
  ADD PRIMARY KEY (`EntryID`),
  ADD KEY `idx_generators_vessel_date` (`VesselID`,`EntryDate`);

--
-- Indexes for table `mainengines`
--
ALTER TABLE `mainengines`
  ADD PRIMARY KEY (`EntryID`),
  ADD KEY `idx_mainengines_vessel_date` (`VesselID`,`EntryDate`);

--
-- Indexes for table `navigation_data`
--
ALTER TABLE `navigation_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vessel_id` (`vessel_id`),
  ADD KEY `idx_vessel_name` (`vessel_name`),
  ADD KEY `idx_vessel_timestamp` (`vessel_timestamp`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `vessels`
--
ALTER TABLE `vessels`
  ADD PRIMARY KEY (`VesselID`),
  ADD UNIQUE KEY `VesselName` (`VesselName`);

--
-- Indexes for table `vessel_logs`
--
ALTER TABLE `vessel_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vessel_id` (`vessel_id`),
  ADD KEY `idx_vessel_name` (`vessel_name`),
  ADD KEY `idx_vessel_timestamp` (`vessel_timestamp`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gears`
--
ALTER TABLE `gears`
  MODIFY `EntryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `generators`
--
ALTER TABLE `generators`
  MODIFY `EntryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `mainengines`
--
ALTER TABLE `mainengines`
  MODIFY `EntryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `navigation_data`
--
ALTER TABLE `navigation_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vessels`
--
ALTER TABLE `vessels`
  MODIFY `VesselID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vessel_logs`
--
ALTER TABLE `vessel_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `gears`
--
ALTER TABLE `gears`
  ADD CONSTRAINT `fk_gears_vessel` FOREIGN KEY (`VesselID`) REFERENCES `vessels` (`VesselID`) ON DELETE CASCADE;

--
-- Constraints for table `generators`
--
ALTER TABLE `generators`
  ADD CONSTRAINT `fk_generators_vessel` FOREIGN KEY (`VesselID`) REFERENCES `vessels` (`VesselID`) ON DELETE CASCADE;

--
-- Constraints for table `mainengines`
--
ALTER TABLE `mainengines`
  ADD CONSTRAINT `fk_mainengines_vessel` FOREIGN KEY (`VesselID`) REFERENCES `vessels` (`VesselID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
