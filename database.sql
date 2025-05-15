-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for project_leave
CREATE DATABASE IF NOT EXISTS `project_leave` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `project_leave`;

-- Dumping structure for table project_leave.admin
CREATE TABLE IF NOT EXISTS `admin` (
  `adminid` int NOT NULL AUTO_INCREMENT,
  `id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`adminid`),
  KEY `FK_admin_employees` (`id`),
  CONSTRAINT `FK_admin_employees` FOREIGN KEY (`id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.delegation
CREATE TABLE IF NOT EXISTS `delegation` (
  `delegation_id` int NOT NULL AUTO_INCREMENT,
  `empid` int NOT NULL,
  `subdepartid` int NOT NULL,
  PRIMARY KEY (`delegation_id`),
  KEY `empid` (`empid`),
  KEY `subdepartid` (`subdepartid`),
  CONSTRAINT `delegation_ibfk_1` FOREIGN KEY (`empid`) REFERENCES `employees` (`id`),
  CONSTRAINT `delegation_ibfk_2` FOREIGN KEY (`subdepartid`) REFERENCES `subdepart` (`subdepartid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.employees
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pic` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prefix` int DEFAULT NULL,
  `gender` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` int DEFAULT NULL,
  `staffstatus` int DEFAULT NULL,
  `startwork` date DEFAULT NULL,
  `startappoint` date DEFAULT NULL,
  `signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `position` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_employees_position` (`position`),
  KEY `FK_employees_prefix` (`prefix`),
  KEY `FK_employees_staffstatus` (`staffstatus`),
  KEY `FK_employees_subdepart` (`department`),
  CONSTRAINT `FK_employees_position` FOREIGN KEY (`position`) REFERENCES `position` (`positionid`),
  CONSTRAINT `FK_employees_prefix` FOREIGN KEY (`prefix`) REFERENCES `prefix` (`prefixid`),
  CONSTRAINT `FK_employees_staffstatus` FOREIGN KEY (`staffstatus`) REFERENCES `staffstatus` (`staffid`),
  CONSTRAINT `FK_employees_subdepart` FOREIGN KEY (`department`) REFERENCES `subdepart` (`subdepartid`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.headepart
CREATE TABLE IF NOT EXISTS `headepart` (
  `headepartid` int NOT NULL AUTO_INCREMENT,
  `headepartname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`headepartid`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.holiday
CREATE TABLE IF NOT EXISTS `holiday` (
  `holidayid` int NOT NULL AUTO_INCREMENT,
  `holidayname` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `holidayday` date DEFAULT NULL,
  PRIMARY KEY (`holidayid`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.leaveday
CREATE TABLE IF NOT EXISTS `leaveday` (
  `leavedayid` int NOT NULL AUTO_INCREMENT,
  `empid` int NOT NULL DEFAULT '0',
  `leavetype` int DEFAULT NULL,
  `staffstatus` int DEFAULT NULL,
  `day` int DEFAULT NULL,
  `stackleaveday` int DEFAULT NULL,
  `pending_deduction_days` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`leavedayid`),
  KEY `leavetype` (`leavetype`),
  KEY `staffstatus` (`staffstatus`),
  KEY `leaveday_ibfk_3` (`empid`),
  CONSTRAINT `leaveday_ibfk_1` FOREIGN KEY (`leavetype`) REFERENCES `leavetype` (`leavetypeid`),
  CONSTRAINT `leaveday_ibfk_2` FOREIGN KEY (`staffstatus`) REFERENCES `staffstatus` (`staffid`),
  CONSTRAINT `leaveday_ibfk_3` FOREIGN KEY (`empid`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=691 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.leaves
CREATE TABLE IF NOT EXISTS `leaves` (
  `leavesid` int NOT NULL AUTO_INCREMENT,
  `employeesid` int DEFAULT NULL,
  `leavetype` int DEFAULT NULL,
  `leavestart` date DEFAULT NULL,
  `leaveend` date DEFAULT NULL,
  `day` int DEFAULT NULL,
  `send_date` date DEFAULT NULL,
  `send_cancel` date DEFAULT NULL,
  `approver1` int DEFAULT NULL,
  `approved_date1` date DEFAULT NULL,
  `approved_cancel1` date NOT NULL DEFAULT '0000-00-00',
  `approver2` int DEFAULT NULL,
  `approved_date2` date DEFAULT NULL,
  `approver3` int DEFAULT NULL,
  `approved_date3` date DEFAULT NULL,
  `approved_cancel3` date NOT NULL DEFAULT '0000-00-00',
  `leavestatus` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `note` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reason` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`leavesid`),
  KEY `FK_leaves_employees` (`employeesid`),
  KEY `FK_leaves_leavetype` (`leavetype`),
  KEY `FK_leaves_employees_2` (`approver1`),
  KEY `FK_leaves_employees_3` (`approver2`),
  KEY `FK_leaves_employees_4` (`approver3`),
  CONSTRAINT `FK_leaves_employees` FOREIGN KEY (`employeesid`) REFERENCES `employees` (`id`),
  CONSTRAINT `FK_leaves_employees_2` FOREIGN KEY (`approver1`) REFERENCES `employees` (`id`),
  CONSTRAINT `FK_leaves_employees_3` FOREIGN KEY (`approver2`) REFERENCES `employees` (`id`),
  CONSTRAINT `FK_leaves_employees_4` FOREIGN KEY (`approver3`) REFERENCES `employees` (`id`),
  CONSTRAINT `FK_leaves_leavetype` FOREIGN KEY (`leavetype`) REFERENCES `leavetype` (`leavetypeid`)
) ENGINE=InnoDB AUTO_INCREMENT=407 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.leavetype
CREATE TABLE IF NOT EXISTS `leavetype` (
  `leavetypeid` int NOT NULL AUTO_INCREMENT,
  `leavetypename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `staffid` int NOT NULL,
  `leaveofyear` int NOT NULL,
  `stackleaveday` int NOT NULL,
  `workage` int NOT NULL,
  `workageday` int NOT NULL,
  `nameform` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `minleaveday` int DEFAULT NULL,
  `workage_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`leavetypeid`),
  KEY `staffid` (`staffid`),
  CONSTRAINT `leavetype_ibfk_1` FOREIGN KEY (`staffid`) REFERENCES `staffstatus` (`staffid`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.position
CREATE TABLE IF NOT EXISTS `position` (
  `positionid` int NOT NULL AUTO_INCREMENT,
  `positionname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `roleid` int DEFAULT NULL,
  PRIMARY KEY (`positionid`),
  KEY `FK_position_role` (`roleid`),
  CONSTRAINT `FK_position_role` FOREIGN KEY (`roleid`) REFERENCES `role` (`roleid`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.prefix
CREATE TABLE IF NOT EXISTS `prefix` (
  `prefixid` int NOT NULL AUTO_INCREMENT,
  `prefixname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`prefixid`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.role
CREATE TABLE IF NOT EXISTS `role` (
  `roleid` int NOT NULL AUTO_INCREMENT,
  `rolename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `level` int DEFAULT NULL,
  PRIMARY KEY (`roleid`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.staffstatus
CREATE TABLE IF NOT EXISTS `staffstatus` (
  `staffid` int NOT NULL AUTO_INCREMENT,
  `staffname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`staffid`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.subdepart
CREATE TABLE IF NOT EXISTS `subdepart` (
  `subdepartid` int NOT NULL AUTO_INCREMENT,
  `headepartid` int DEFAULT NULL,
  `subdepartname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`subdepartid`),
  KEY `headepartid` (`headepartid`),
  CONSTRAINT `subdepart_ibfk_1` FOREIGN KEY (`headepartid`) REFERENCES `headepart` (`headepartid`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.vacationday_updates
CREATE TABLE IF NOT EXISTS `vacationday_updates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table project_leave.year
CREATE TABLE IF NOT EXISTS `year` (
  `yaerid` int NOT NULL AUTO_INCREMENT,
  `yearstart1` date DEFAULT NULL,
  `yearend1` date DEFAULT NULL,
  `yearstart2` date DEFAULT NULL,
  `yearend2` date DEFAULT NULL,
  `update` date DEFAULT NULL,
  PRIMARY KEY (`yaerid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
