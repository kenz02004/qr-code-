-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 11:07 PM
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
-- Database: `qr_attendance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_attendance`
--

CREATE TABLE `tbl_attendance` (
  `tbl_attendance_id` int(11) NOT NULL,
  `tbl_user_id` int(11) DEFAULT NULL,
  `tbl_student_id` int(11) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `sequence_number` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_attendance`
--

INSERT INTO `tbl_attendance` (`tbl_attendance_id`, `tbl_user_id`, `tbl_student_id`, `time_in`, `time_out`, `sequence_number`, `status`) VALUES
(32, 1, 33, '2025-05-14 04:31:39', '2025-05-14 04:32:46', 2, 'present');

--
-- Triggers `tbl_attendance`
--
DELIMITER $$
CREATE TRIGGER `before_attendance_insert` BEFORE INSERT ON `tbl_attendance` FOR EACH ROW BEGIN
  DECLARE max_seq INT;
  SELECT COALESCE(MAX(sequence_number), 0) INTO max_seq FROM tbl_attendance WHERE tbl_user_id = NEW.tbl_user_id;
  SET NEW.sequence_number = max_seq + 1;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_class_days`
--

CREATE TABLE `tbl_class_days` (
  `class_date` date NOT NULL,
  `tbl_user_id` int(11) NOT NULL,
  `has_class` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_class_days`
--

INSERT INTO `tbl_class_days` (`class_date`, `tbl_user_id`, `has_class`, `created_at`) VALUES
('2025-04-01', 1, 1, '2025-05-01 09:42:25');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sections`
--

CREATE TABLE `tbl_sections` (
  `id` int(11) NOT NULL,
  `tbl_user_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_sections`
--

INSERT INTO `tbl_sections` (`id`, `tbl_user_id`, `section_name`) VALUES
(1, 1, 'BIT-CT'),
(3, 1, 'BIT-Drafting'),
(4, 1, 'BIT-Electrical'),
(2, 1, 'BIT-WAF'),
(5, 1, 'IE-industrial');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_student`
--

CREATE TABLE `tbl_student` (
  `tbl_student_id` int(11) NOT NULL,
  `tbl_user_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `course_section` varchar(255) NOT NULL,
  `generated_code` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_student`
--

INSERT INTO `tbl_student` (`tbl_student_id`, `tbl_user_id`, `student_name`, `course_section`, `generated_code`) VALUES
(33, 1, 'kent', 'BIT-Electrical', '57c30bde62');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `tbl_user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`tbl_user_id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'kent', '$2y$10$qmz/ReM2sqNn2FWauKj7TuavWxTd66lgB7DaelX5zDiWxMQ9Nn..G', 'kentzacharyfab@gmail.com', '2025-04-10 13:20:06'),
(2, 'admin', '$2y$10$lASazMYojqbOrsXi377ayu6wDolGKlzYyZRZkMFRntGf/Kh.ZwF6u', 'kentfab@gmail.com', '2025-04-21 10:55:46'),
(5, 'admin2', '$2y$10$z7p0sCE.tCI3IGA5VJf9Me95r8JRVqkh1o0xGXc0hDSvDjN8uveF6', 'asdbvnc@gmail.com', '2025-05-14 05:06:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  ADD PRIMARY KEY (`tbl_attendance_id`),
  ADD KEY `fk_attendance_user` (`tbl_user_id`);

--
-- Indexes for table `tbl_class_days`
--
ALTER TABLE `tbl_class_days`
  ADD PRIMARY KEY (`class_date`,`tbl_user_id`),
  ADD KEY `fk_class_days_user` (`tbl_user_id`);

--
-- Indexes for table `tbl_sections`
--
ALTER TABLE `tbl_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_name` (`section_name`),
  ADD UNIQUE KEY `user_section_unique` (`tbl_user_id`,`section_name`);

--
-- Indexes for table `tbl_student`
--
ALTER TABLE `tbl_student`
  ADD PRIMARY KEY (`tbl_student_id`),
  ADD KEY `fk_student_user` (`tbl_user_id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`tbl_user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  MODIFY `tbl_attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `tbl_sections`
--
ALTER TABLE `tbl_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_student`
--
ALTER TABLE `tbl_student`
  MODIFY `tbl_student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `tbl_user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  ADD CONSTRAINT `fk_attendance_user` FOREIGN KEY (`tbl_user_id`) REFERENCES `tbl_users` (`tbl_user_id`);

--
-- Constraints for table `tbl_class_days`
--
ALTER TABLE `tbl_class_days`
  ADD CONSTRAINT `fk_class_days_user` FOREIGN KEY (`tbl_user_id`) REFERENCES `tbl_users` (`tbl_user_id`);

--
-- Constraints for table `tbl_sections`
--
ALTER TABLE `tbl_sections`
  ADD CONSTRAINT `fk_sections_user` FOREIGN KEY (`tbl_user_id`) REFERENCES `tbl_users` (`tbl_user_id`);

--
-- Constraints for table `tbl_student`
--
ALTER TABLE `tbl_student`
  ADD CONSTRAINT `fk_student_user` FOREIGN KEY (`tbl_user_id`) REFERENCES `tbl_users` (`tbl_user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
