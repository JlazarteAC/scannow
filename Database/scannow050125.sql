-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2025 at 04:47 PM
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
-- Database: `scannow`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_log`
--

CREATE TABLE `admin_log` (
  `admin_log` int(15) NOT NULL,
  `login_id` int(15) NOT NULL,
  `Date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `log_activity` varchar(500) NOT NULL COMMENT 'Detailed description of the activity',
  `page` varchar(255) NOT NULL COMMENT 'Page the user accessed',
  `action_type` varchar(50) NOT NULL COMMENT 'Type of action performed (e.g., VIEW, CREATE, UPDATE, DELETE)',
  `target_entity` varchar(255) DEFAULT NULL COMMENT 'Optional target entity of the action',
  `entity_id` int(11) DEFAULT NULL COMMENT 'ID of the affected entity'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`) VALUES
(1, 'Executives'),
(2, 'Finance and Accounting'),
(3, 'Guidance'),
(4, 'Library'),
(5, 'Clinic'),
(6, 'Human Resource'),
(7, 'Marketing'),
(8, 'SHS'),
(9, 'TVET'),
(10, 'CBT'),
(11, 'CSE'),
(12, 'BAA'),
(13, 'HTM'),
(14, 'SOCI'),
(15, 'Registrar'),
(16, 'QA'),
(17, 'Facilities Management and Operations'),
(18, 'Research Development'),
(19, 'GUEST'),
(21, 'Canteen');

-- --------------------------------------------------------

--
-- Table structure for table `display_color`
--

CREATE TABLE `display_color` (
  `display_color_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `bg_image` varchar(100) NOT NULL,
  `logo_image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `display_color`
--

INSERT INTO `display_color` (`display_color_id`, `department_id`, `bg_image`, `logo_image`) VALUES
(1, 11, 'Image/CCSE-Banner.png', 'Image/CCSE.png'),
(2, 12, 'Image/CBA-Banner.png', 'Image/CBA.png'),
(3, 9, 'Image/Tesda-Banner.png', 'Image/TESDA.png'),
(4, 13, 'Image/HTM-Banner.png', 'Image/HTM.png'),
(5, 8, 'Image/SHS-Banner.png', 'Image/SHS.png');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(15) NOT NULL,
  `username` varchar(64) NOT NULL,
  `password` varchar(25) NOT NULL,
  `role_id` int(15) NOT NULL,
  `first_login` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_entry`
--

CREATE TABLE `log_entry` (
  `id` int(11) NOT NULL,
  `school_id` varchar(50) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_exit`
--

CREATE TABLE `log_exit` (
  `id` int(11) NOT NULL,
  `school_id` varchar(50) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `status` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `role_id` int(15) NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `role_name`, `description`) VALUES
(1, 'SuperAdmin', 'All Access'),
(2, 'Admin', 'Minimal Access');

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `status_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `status_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`status_id`, `department_id`, `status_name`) VALUES
(1, 1, 'President'),
(2, 1, 'Vice President'),
(3, 2, 'Controller'),
(4, 2, 'Accountant'),
(5, 2, 'Cashier'),
(6, 2, 'Accounting Officer'),
(7, 2, 'Accounting Staff'),
(8, 3, 'SAS Head'),
(9, 3, 'SAS Coordinator'),
(10, 3, 'Guidance Councilor'),
(11, 4, 'Chief Librarian'),
(12, 4, 'Librarian'),
(13, 4, 'Library Assistant'),
(14, 5, 'Nurse'),
(15, 5, 'Nurse Assistant'),
(16, 6, 'OIC-HR Generalist'),
(17, 6, 'HR Staff'),
(18, 7, 'Marketing Coordinator'),
(19, 7, 'Admissions Officer'),
(20, 7, 'Marketing Staff'),
(21, 8, 'Student (ABM)'),
(22, 8, 'Student (GAS)'),
(23, 8, 'Student (HUMSS)'),
(24, 8, 'Student (HE Caregiving)'),
(25, 8, 'Student (HE Cookery)'),
(26, 8, 'Student (HE Housekeeping)'),
(27, 8, 'Student (HE Bread and Pastry Production)'),
(28, 8, 'Student (HE Food and Beverage Services)'),
(29, 8, 'Student (HE Tour Guiding and Travel Services)'),
(30, 8, 'Student (ICT Computer Programming)'),
(31, 8, 'Student (IA Consumer Electronics Servicing)'),
(32, 8, 'Teachers (SHS)'),
(33, 8, 'SHS Coordinator'),
(34, 8, 'OIC-SHS Principal'),
(35, 9, 'Student (DP in Information Technology)'),
(36, 9, 'Trainers/Faculty (TVET)'),
(37, 9, 'Processing Officer'),
(38, 9, 'TVET Coordinator'),
(39, 9, 'Assessment Manager'),
(40, 9, 'TVET Director'),
(41, 10, 'DEAN'),
(42, 11, 'Student (BS in Computer Engineering)'),
(43, 11, 'Student (BS in Electronics Technology)'),
(44, 11, 'Student (BS in Information Technology)'),
(45, 11, 'Student (BS in Computer Science)'),
(46, 11, 'Faculty (CSE)'),
(47, 11, 'CSE Coordinator'),
(48, 11, 'CSE Program Chair'),
(49, 12, 'Student (BS in Accountancy)'),
(50, 12, 'Student (BS in Business Administration - Financial Management)'),
(51, 12, 'Student (BS in Business Administration - Marketing Management)'),
(52, 12, 'Student (BS in Business Administration - Operations Management)'),
(53, 12, 'Faculty (BAA)'),
(54, 12, 'BAA Coordinator'),
(55, 12, 'BAA Program Chair'),
(56, 13, 'Student (BS in Hospitality Management)'),
(57, 13, 'Student (BS in Tourism Management)'),
(58, 13, 'Faculty (HTM)'),
(59, 13, 'HTM Coordinator'),
(60, 13, 'HTM Program Chair'),
(61, 14, 'SOCI Director'),
(62, 15, 'Registrar'),
(63, 15, 'Assistant Registrar'),
(64, 15, 'Records Officer'),
(65, 15, 'Clerk'),
(66, 16, 'QA Head'),
(67, 16, 'QA Staff'),
(68, 17, 'Physical Plant & Facilites Manager'),
(69, 17, 'Maintenance'),
(70, 17, 'Inventory'),
(71, 17, 'TSG Team Leader'),
(72, 17, 'TSG Assistant'),
(73, 17, 'Security'),
(74, 18, 'Research Head'),
(75, 19, 'GUEST'),
(76, 21, 'Staff'),
(77, 9, 'Student (DP in Hospitality Technology)');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `school_id` varchar(50) NOT NULL,
  `status_id` int(11) NOT NULL,
  `rfid_signature` int(10) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `image` varchar(255) NOT NULL,
  `enroll_date` date DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `granted_access` tinyint(1) DEFAULT 0,
  `enroll` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `user`
--
DELIMITER $$
CREATE TRIGGER `before_user_update_expiration_date` BEFORE UPDATE ON `user` FOR EACH ROW BEGIN
    -- If expiration_date has passed, set enroll to 0 and granted_access to 0
    IF NEW.expiration_date < CURDATE() THEN
        SET NEW.enroll = 0;
        SET NEW.granted_access = 0;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_user_update_granted_access` BEFORE UPDATE ON `user` FOR EACH ROW BEGIN
    -- If enroll is 1, set granted_access to 1, otherwise set it to 0
    IF NEW.enroll = 1 THEN
        SET NEW.granted_access = 1;
    ELSE
        SET NEW.granted_access = 0;
    END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_log`
--
ALTER TABLE `admin_log`
  ADD PRIMARY KEY (`admin_log`),
  ADD KEY `login_id` (`login_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `display_color`
--
ALTER TABLE `display_color`
  ADD PRIMARY KEY (`display_color_id`),
  ADD KEY `De-Co` (`department_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `role_id_2` (`role_id`);

--
-- Indexes for table `log_entry`
--
ALTER TABLE `log_entry`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `log_exit`
--
ALTER TABLE `log_exit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`status_id`),
  ADD KEY `Dep-Sta` (`department_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`school_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `userxsatus` (`status_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_log`
--
ALTER TABLE `admin_log`
  MODIFY `admin_log` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_entry`
--
ALTER TABLE `log_entry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_exit`
--
ALTER TABLE `log_exit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `role_id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_log`
--
ALTER TABLE `admin_log`
  ADD CONSTRAINT `loginxadmin_log` FOREIGN KEY (`login_id`) REFERENCES `login` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `display_color`
--
ALTER TABLE `display_color`
  ADD CONSTRAINT `De-Co` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`);

--
-- Constraints for table `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `rolexlogin` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `log_entry`
--
ALTER TABLE `log_entry`
  ADD CONSTRAINT `userxentry` FOREIGN KEY (`school_id`) REFERENCES `user` (`school_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `log_exit`
--
ALTER TABLE `log_exit`
  ADD CONSTRAINT `userxexit` FOREIGN KEY (`school_id`) REFERENCES `user` (`school_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `status`
--
ALTER TABLE `status`
  ADD CONSTRAINT `Dep-Sta` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `userxsatus` FOREIGN KEY (`status_id`) REFERENCES `status` (`status_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
