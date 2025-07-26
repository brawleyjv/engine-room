-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: VesselData
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `gears`
--

DROP TABLE IF EXISTS `gears`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gears` (
  `EntryID` int(11) NOT NULL AUTO_INCREMENT,
  `VesselID` int(11) NOT NULL DEFAULT 1,
  `Side` enum('Port','Starboard') NOT NULL,
  `EntryDate` date NOT NULL,
  `OilPress` int(3) NOT NULL,
  `Temp` int(3) NOT NULL,
  `Notes` text DEFAULT NULL,
  `RecordedBy` varchar(100) DEFAULT NULL,
  `Timestamp` datetime DEFAULT current_timestamp(),
  `GearHrs` int(11) NOT NULL,
  PRIMARY KEY (`EntryID`),
  KEY `idx_gears_vessel_date` (`VesselID`,`EntryDate`),
  CONSTRAINT `fk_gears_vessel` FOREIGN KEY (`VesselID`) REFERENCES `vessels` (`VesselID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `generators`
--

DROP TABLE IF EXISTS `generators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `generators` (
  `EntryID` int(11) NOT NULL AUTO_INCREMENT,
  `VesselID` int(11) NOT NULL DEFAULT 1,
  `Side` enum('Port','Starboard') NOT NULL,
  `EntryDate` date NOT NULL,
  `FuelPress` int(3) NOT NULL,
  `OilPress` int(3) NOT NULL,
  `WaterTemp` int(3) NOT NULL,
  `Notes` text DEFAULT NULL,
  `RecordedBy` varchar(100) NOT NULL,
  `Timestamp` datetime DEFAULT current_timestamp(),
  `GenHrs` int(11) NOT NULL,
  PRIMARY KEY (`EntryID`),
  KEY `idx_generators_vessel_date` (`VesselID`,`EntryDate`),
  CONSTRAINT `fk_generators_vessel` FOREIGN KEY (`VesselID`) REFERENCES `vessels` (`VesselID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mainengines`
--

DROP TABLE IF EXISTS `mainengines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mainengines` (
  `EntryID` int(11) NOT NULL AUTO_INCREMENT,
  `VesselID` int(11) NOT NULL DEFAULT 1,
  `Side` enum('Port','Starboard') NOT NULL,
  `EntryDate` date NOT NULL,
  `RPM` int(3) NOT NULL,
  `OilPressure` int(3) NOT NULL,
  `WaterTemp` int(3) NOT NULL,
  `Notes` text DEFAULT NULL,
  `RecordedBy` varchar(100) NOT NULL,
  `Timestamp` datetime DEFAULT current_timestamp(),
  `MainHrs` int(11) NOT NULL,
  `FuelPress` int(3) NOT NULL,
  `OilTemp` int(3) NOT NULL,
  PRIMARY KEY (`EntryID`),
  KEY `idx_mainengines_vessel_date` (`VesselID`,`EntryDate`),
  CONSTRAINT `fk_mainengines_vessel` FOREIGN KEY (`VesselID`) REFERENCES `vessels` (`VesselID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
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
  `ResetTokenExpiry` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Username` (`Username`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vessels`
--

DROP TABLE IF EXISTS `vessels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vessels` (
  `VesselID` int(11) NOT NULL AUTO_INCREMENT,
  `VesselName` varchar(100) NOT NULL,
  `VesselType` varchar(50) DEFAULT 'Fishing Vessel',
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
  `GenMax` int(11) DEFAULT 400,
  PRIMARY KEY (`VesselID`),
  UNIQUE KEY `VesselName` (`VesselName`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-25 20:44:21
