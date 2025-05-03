-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 03, 2025 at 02:16 AM
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
-- Database: `mogchs2`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `message`, `date`) VALUES
(1, 'hello', '2025-05-01 15:48:07'),
(2, 'hi', '2025-05-01 15:48:20'),
(3, 'Document processing will be delayed on Friday due to system maintenance.', '2023-06-15 00:00:00'),
(4, 'New document request feature is now available!', '2023-06-10 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `current_login_accounts`
--

CREATE TABLE `current_login_accounts` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `current_login_accounts`
--

INSERT INTO `current_login_accounts` (`id`, `full_name`, `address`, `contact_number`, `email`, `student_number`, `role`) VALUES
(1, 'John Doe', '1234 Elm Street, Springfield', '09123456789', 'johndoe@example.com', 'S12345', 'registrar'),
(2, 'Jane Smith', '5678 Oak Avenue, Shelbyville', '09876543210', 'janesmith@example.com', 'S12346', 'student');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `name`, `description`, `available`) VALUES
(1, 'Transcript of Records', 'Official academic record', 1),
(2, 'Certificate of Enrollment', 'Current enrollment status', 1),
(3, 'Diploma', 'Graduation certificate', 0);

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `id` int(11) NOT NULL,
  `request_number` varchar(20) NOT NULL,
  `student_number` varchar(20) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `course` varchar(50) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `date_requested` date NOT NULL,
  `scheduled_pickup` date DEFAULT NULL,
  `rescheduled_pickup` date DEFAULT NULL,
  `status` enum('Pending','Ready','Received') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_documents`
--

CREATE TABLE `request_documents` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `assessment_year` varchar(10) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_number` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `surname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `strand` varchar(50) DEFAULT NULL,
  `year` varchar(10) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_number`, `first_name`, `middle_initial`, `surname`, `email`, `contact_no`, `address`, `strand`, `year`, `section`) VALUES
('3040912501641', 'John', 'D', 'Doe', 'john.doe@school.edu', '09123456789', '123 Main St, City', 'STEM', '12', 'A');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_days`
--

CREATE TABLE `transaction_days` (
  `id` int(11) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `day_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_days`
--

INSERT INTO `transaction_days` (`id`, `day_of_week`, `start_time`, `end_time`, `day_order`) VALUES
(1, 'Monday', '08:00:00', '17:00:00', 1),
(2, 'Tuesday', '08:00:00', '17:00:00', 2),
(3, 'Wednesday', '08:00:00', '17:00:00', 3),
(4, 'Thursday', '08:00:00', '17:00:00', 4),
(5, 'Friday', '08:00:00', '17:00:00', 5),
(6, 'Monday', '09:00:00', '04:00:00', 1),
(7, 'Wednesday', '10:00:00', '03:00:00', 2),
(8, 'Friday', '08:30:00', '05:00:00', 3);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher','registrar') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'student1', '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b', 'student'),
(2, 'teacher1', '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b', 'teacher'),
(3, 'registrar1', '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b', 'registrar');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `current_login_accounts`
--
ALTER TABLE `current_login_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `request_documents`
--
ALTER TABLE `request_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_number`);

--
-- Indexes for table `transaction_days`
--
ALTER TABLE `transaction_days`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `current_login_accounts`
--
ALTER TABLE `current_login_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_documents`
--
ALTER TABLE `request_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaction_days`
--
ALTER TABLE `transaction_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `request_documents`
--
ALTER TABLE `request_documents`
  ADD CONSTRAINT `request_documents_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`),
  ADD CONSTRAINT `request_documents_ibfk_2` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
