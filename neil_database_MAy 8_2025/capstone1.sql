-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2025 at 06:34 AM
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
-- Database: `capstone1`
--

-- --------------------------------------------------------

--
-- Table structure for table `archive_incident_reports`
--

CREATE TABLE `archive_incident_reports` (
  `id` varchar(20) NOT NULL,
  `date_reported` datetime DEFAULT NULL,
  `place` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reported_by` varchar(255) DEFAULT NULL,
  `reporters_id` int(11) DEFAULT NULL,
  `reported_by_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `approval_date` datetime DEFAULT NULL,
  `facilitator_id` int(11) DEFAULT NULL,
  `resolution_status` enum('Pending','In Progress','Resolved') DEFAULT 'Pending',
  `resolution_notes` text DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archive_incident_witnesses`
--

CREATE TABLE `archive_incident_witnesses` (
  `id` int(11) NOT NULL,
  `incident_report_id` varchar(20) DEFAULT NULL,
  `witness_type` enum('student','staff') NOT NULL,
  `witness_id` varchar(20) DEFAULT NULL,
  `witness_name` varchar(255) DEFAULT NULL,
  `witness_student_name` varchar(100) DEFAULT NULL,
  `witness_course` varchar(100) DEFAULT NULL,
  `witness_year_level` varchar(20) DEFAULT NULL,
  `witness_email` varchar(255) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `section_name` varchar(255) DEFAULT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `adviser_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archive_student_profiles`
--

CREATE TABLE `archive_student_profiles` (
  `profile_id` varchar(20) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `current_address` text DEFAULT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `zipcode` int(5) NOT NULL,
  `houseno_street` int(10) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `birthplace` varchar(100) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `spouse_name` varchar(100) DEFAULT NULL,
  `spouse_occupation` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `semester_first_enrolled` varchar(50) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_contact` varchar(20) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` text DEFAULT NULL,
  `mother_contact` varchar(20) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `guardian_occupation` varchar(100) DEFAULT NULL,
  `siblings` int(11) DEFAULT NULL,
  `birth_order` varchar(100) DEFAULT NULL,
  `family_income` varchar(100) DEFAULT NULL,
  `elementary` text DEFAULT NULL,
  `secondary` varchar(100) DEFAULT NULL,
  `transferees` varchar(100) DEFAULT NULL,
  `course_factors` text DEFAULT NULL,
  `career_concerns` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `suicide_attempt` varchar(3) DEFAULT NULL,
  `suicide_reason` text DEFAULT NULL,
  `problems` text DEFAULT NULL,
  `family_problems` text NOT NULL,
  `fitness_activity` varchar(100) DEFAULT NULL,
  `fitness_frequency` varchar(20) DEFAULT NULL,
  `stress_level` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `signature_path` varchar(100) NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archive_student_profiles`
--

INSERT INTO `archive_student_profiles` (`profile_id`, `student_id`, `course_id`, `last_name`, `first_name`, `middle_name`, `permanent_address`, `current_address`, `province`, `city`, `barangay`, `zipcode`, `houseno_street`, `contact_number`, `email`, `gender`, `birthdate`, `birthplace`, `nationality`, `religion`, `spouse_name`, `spouse_occupation`, `age`, `civil_status`, `year_level`, `semester_first_enrolled`, `father_name`, `father_contact`, `father_occupation`, `mother_name`, `mother_contact`, `mother_occupation`, `guardian_name`, `guardian_relationship`, `guardian_contact`, `guardian_occupation`, `siblings`, `birth_order`, `family_income`, `elementary`, `secondary`, `transferees`, `course_factors`, `career_concerns`, `medications`, `medical_conditions`, `suicide_attempt`, `suicide_reason`, `problems`, `family_problems`, `fitness_activity`, `fitness_frequency`, `stress_level`, `created_at`, `signature_path`, `is_archived`) VALUES
('Stu_pro_000000001', '202102690', 6, 'MOJICA', 'NEIL TRISTHAN', 'N', 'Marahan, Alfonso, Cavite, 4123, Philippines, Barangay 5, Alfonso, Cavite, 4123, Philippines', 'Marahan, Alfonso, Cavite, 4123, Philippines, Barangay 5, Alfonso, Cavite, 4123, Philippines', 'Cavite', 'Alfonso', 'Barangay 5', 4123, 0, '09318762469', 'neiltristhan.mojica@cvsu.edu.ph', 'MALE', '2015-05-11', 'trece', 'filipino', 'Catholic', NULL, NULL, 10, 'Single', 'Second Year', 'First Semester, 2025-2026', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'Lola Remedios', 'Lola', '09299292929', 'Body Builder', 3, 'Second', 'above 50,000', 'Marahan Elementary School; Dito sa Mars; 2014', 'Alfonso National School; Alfonso, Cavite; 2022', '', 'Childhood Dream; Leisure/Enjoyment; Parents Decision/Choice', 'I need more information about my personal traits, interests, skills, and values', 'NO MEDICATIONS', 'NO MEDICAL CONDITIONS', 'no', '', 'Eating Disorder; Depression; Others: hello', 'Eating Disorder; Depression; Others: hello', 'bato', '2-3 Week', 'average', '2025-05-12 06:44:19', '/capstone1/student/uploads/student_signatures/signature_68219a8c72b25.png', 1);

-- --------------------------------------------------------

--
-- Table structure for table `archive_student_violations`
--

CREATE TABLE `archive_student_violations` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `incident_report_id` varchar(20) NOT NULL,
  `violation_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `student_name` varchar(100) NOT NULL,
  `student_course` varchar(100) DEFAULT NULL,
  `student_year_level` varchar(20) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `section_id` int(11) DEFAULT NULL,
  `section_name` varchar(255) DEFAULT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `adviser_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_incident_reports`
--

CREATE TABLE `backup_incident_reports` (
  `id` varchar(20) NOT NULL,
  `date_reported` datetime DEFAULT NULL,
  `place` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reported_by` varchar(255) DEFAULT NULL,
  `reporters_id` int(11) DEFAULT NULL,
  `reported_by_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `approval_date` datetime DEFAULT NULL,
  `facilitator_id` int(11) DEFAULT NULL,
  `resolution_status` enum('Pending','In Progress','Resolved') DEFAULT 'Pending',
  `resolution_notes` text DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_incident_witnesses`
--

CREATE TABLE `backup_incident_witnesses` (
  `id` int(11) NOT NULL,
  `incident_report_id` varchar(20) DEFAULT NULL,
  `witness_type` enum('student','staff') NOT NULL,
  `witness_id` varchar(20) DEFAULT NULL,
  `witness_name` varchar(255) DEFAULT NULL,
  `witness_student_name` varchar(100) DEFAULT NULL,
  `witness_course` varchar(100) DEFAULT NULL,
  `witness_year_level` varchar(20) DEFAULT NULL,
  `witness_email` varchar(255) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `section_name` varchar(255) DEFAULT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `adviser_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_student_violations`
--

CREATE TABLE `backup_student_violations` (
  `id` bigint(20) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `incident_report_id` varchar(20) NOT NULL,
  `violation_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `student_name` varchar(100) NOT NULL,
  `student_course` varchar(100) DEFAULT NULL,
  `student_year_level` varchar(20) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `section_id` int(11) DEFAULT NULL,
  `section_name` varchar(255) DEFAULT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `adviser_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cavite_barangays`
--

CREATE TABLE `cavite_barangays` (
  `id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `barangay_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cavite_barangays`
--

INSERT INTO `cavite_barangays` (`id`, `city_id`, `barangay_name`) VALUES
(1, 2, 'Banaybanay'),
(2, 2, 'Barangay 1'),
(3, 2, 'Barangay 2'),
(4, 2, 'Barangay 3'),
(5, 2, 'Barangay 4'),
(6, 2, 'Barangay 5'),
(7, 2, 'Barangay 6'),
(8, 2, 'Barangay 7'),
(9, 2, 'Barangay 8'),
(10, 2, 'Barangay 9'),
(11, 2, 'Barangay 10'),
(12, 2, 'Barangay 11'),
(13, 2, 'Barangay 12'),
(14, 2, 'Bucal'),
(15, 2, 'Buho'),
(16, 2, 'Dagatan'),
(17, 2, 'Halang'),
(18, 2, 'Loma'),
(19, 2, 'Maitim 1'),
(20, 2, 'Maymangga'),
(21, 2, 'Minantok Kanluran'),
(22, 2, 'Minantok Silangan'),
(23, 2, 'Pangil'),
(24, 2, 'Poblacion 1'),
(25, 2, 'Poblacion 2'),
(26, 2, 'Salaban'),
(27, 2, 'Talon'),
(28, 2, 'Tamacan'),
(32, 1, 'Amuyong'),
(33, 1, 'Barangay 1'),
(34, 1, 'Barangay 2'),
(35, 1, 'Barangay 3'),
(36, 1, 'Barangay 4'),
(37, 1, 'Barangay 5'),
(38, 1, 'Bilog'),
(39, 1, 'Buck Estate'),
(40, 1, 'Esperanza, Ibaba'),
(41, 1, 'Esperanza, Ilaya'),
(42, 1, 'Kaysuyo'),
(43, 1, 'Kaytitinga 1'),
(44, 1, 'Kaytitinga 2'),
(45, 1, 'Kaytitinga 3'),
(46, 1, 'Luksuhin'),
(47, 1, 'Luksuhin Ilaya'),
(48, 1, 'Mangas 1'),
(49, 1, 'Mangas 2'),
(50, 1, 'Marahan 1'),
(51, 1, 'Marahan 2'),
(52, 1, 'Matagbak 1'),
(53, 1, 'Matagbak 2'),
(54, 1, 'Pajo'),
(55, 1, 'Palumlum'),
(56, 1, 'Santa Teresa'),
(57, 1, 'Sikat'),
(58, 1, 'Sinaliw Malaki'),
(59, 1, 'Sinaliw na Munti'),
(60, 1, 'Sulsugin'),
(61, 1, 'Taywanak Ibaba'),
(62, 1, 'Taywanak Ilaya'),
(63, 1, 'Upli'),
(64, 3, 'Aniban I'),
(65, 3, 'Aniban II'),
(66, 3, 'Aniban III'),
(67, 3, 'Banalo'),
(68, 3, 'Bayanan'),
(69, 3, 'Campo Santo'),
(70, 3, 'Dulong Bayan'),
(71, 3, 'Habay I'),
(72, 3, 'Kaingin'),
(73, 3, 'Ligas I'),
(74, 3, 'Ligas II'),
(75, 3, 'Mabolo I'),
(76, 3, 'Mabolo II'),
(77, 3, 'Maliksi I'),
(78, 3, 'Maliksi II'),
(79, 3, 'Maliksi III'),
(80, 3, 'Mambog I'),
(81, 3, 'Mambog II'),
(82, 3, 'Mambog III'),
(83, 3, 'Molino I'),
(84, 3, 'Molino II'),
(85, 3, 'Molino III'),
(86, 3, 'Niog I'),
(87, 3, 'Niog II'),
(88, 3, 'P. F. Espiritu I'),
(89, 3, 'P. F. Espiritu II'),
(90, 3, 'P. F. Espiritu III'),
(91, 3, 'Queens Row Central'),
(92, 3, 'Queens Row East'),
(93, 3, 'Real I'),
(94, 3, 'Real II'),
(95, 3, 'Salinas I'),
(96, 3, 'Salinas II'),
(97, 3, 'San Nicolas I'),
(98, 3, 'San Nicolas II'),
(99, 3, 'Sineguelasan'),
(100, 3, 'Tabing Dagat'),
(101, 3, 'Talaba I'),
(102, 3, 'Talaba II'),
(103, 3, 'Talaba III'),
(104, 3, 'Talaba IV'),
(105, 3, 'Talaba V'),
(106, 3, 'Zapote I'),
(107, 3, 'Zapote II'),
(108, 3, 'Zapote III'),
(109, 3, 'Zapote IV'),
(110, 3, 'Zapote V'),
(127, 4, 'Bancal'),
(128, 4, 'Barangay 1'),
(129, 4, 'Barangay 2'),
(130, 4, 'Barangay 3'),
(131, 4, 'Barangay 4'),
(132, 4, 'Barangay 5'),
(133, 4, 'Barangay 6'),
(134, 4, 'Barangay 7'),
(135, 4, 'Barangay 8'),
(136, 4, 'Cabilang Baybay'),
(137, 4, 'Lantic'),
(138, 4, 'Mabuhay'),
(139, 4, 'Maduya'),
(140, 4, 'Milagrosa'),
(142, 5, 'Barangay 1'),
(143, 5, 'Barangay 10'),
(144, 5, 'Barangay 10-A'),
(145, 5, 'Barangay 10-B'),
(146, 5, 'Barangay 11'),
(147, 5, 'Barangay 12'),
(148, 5, 'Barangay 13'),
(149, 5, 'Barangay 14'),
(150, 5, 'Barangay 15'),
(151, 5, 'Barangay 16'),
(152, 5, 'Barangay 17'),
(153, 5, 'Barangay 18'),
(154, 5, 'Barangay 19'),
(155, 5, 'Barangay 2'),
(156, 5, 'Barangay 20'),
(157, 5, 'Barangay 21'),
(158, 5, 'Barangay 23'),
(159, 5, 'Barangay 24'),
(160, 5, 'Barangay 25'),
(161, 5, 'Barangay 26'),
(162, 5, 'Barangay 27'),
(163, 5, 'Barangay 28'),
(164, 5, 'Barangay 3'),
(165, 5, 'Barangay 30'),
(166, 5, 'Barangay 31'),
(167, 5, 'Barangay 32'),
(168, 5, 'Barangay 33'),
(169, 5, 'Barangay 34'),
(170, 5, 'Barangay 35'),
(171, 5, 'Barangay 4'),
(172, 5, 'Barangay 41'),
(173, 5, 'Barangay 42'),
(174, 5, 'Barangay 43'),
(175, 5, 'Barangay 44'),
(176, 5, 'Barangay 45'),
(177, 5, 'Barangay 5'),
(178, 5, 'Barangay 50'),
(179, 5, 'Barangay 51'),
(180, 5, 'Barangay 52'),
(181, 5, 'Barangay 53'),
(182, 5, 'Barangay 54'),
(183, 5, 'Barangay 55'),
(184, 5, 'Barangay 6'),
(185, 5, 'Barangay 60'),
(186, 5, 'Barangay 61'),
(187, 5, 'Barangay 62'),
(188, 5, 'Barangay 62-A'),
(189, 5, 'Barangay 7'),
(190, 5, 'Barangay 8'),
(191, 5, 'Barangay 9'),
(205, 6, 'Burol'),
(206, 6, 'Burol I'),
(207, 6, 'Burol II'),
(208, 6, 'Datu Esmael'),
(209, 6, 'Emmanuel Bergado I'),
(210, 6, 'Emmanuel Bergado II'),
(211, 6, 'Fatima I'),
(212, 6, 'Fatima II'),
(213, 6, 'H-2'),
(214, 6, 'Langkaan I'),
(215, 6, 'Langkaan II'),
(216, 6, 'Luzviminda I'),
(217, 6, 'Luzviminda II'),
(218, 6, 'Paliparan I'),
(219, 6, 'Paliparan II'),
(220, 6, 'Sabang'),
(221, 6, 'Saint Peter I'),
(222, 6, 'Saint Peter II'),
(223, 6, 'Salawag'),
(224, 6, 'Salitran I'),
(225, 6, 'Salitran II'),
(226, 6, 'Salitran III'),
(227, 6, 'Sampaloc I'),
(228, 6, 'Sampaloc II'),
(229, 6, 'Sampaloc III'),
(230, 6, 'Sampaloc IV'),
(231, 6, 'San Agustin I'),
(232, 6, 'San Agustin II'),
(233, 6, 'San Andres I'),
(234, 6, 'San Andres II'),
(235, 6, 'San Antonio de Padua I'),
(236, 6, 'San Antonio de Padua II'),
(237, 6, 'San Dionisio'),
(238, 6, 'San Esteban'),
(239, 6, 'San Isidro Labrador I'),
(240, 6, 'San Isidro Labrador II'),
(241, 6, 'San Jose'),
(242, 6, 'San Lorenzo Ruiz I'),
(243, 6, 'San Lorenzo Ruiz II'),
(244, 6, 'San Nicolas I'),
(245, 6, 'San Nicolas II'),
(246, 6, 'Santa Cruz I'),
(247, 6, 'Santa Fe'),
(248, 6, 'Santa Lucia'),
(249, 6, 'Santo Cristo'),
(250, 6, 'Victoria Reyes'),
(251, 6, 'Zone I'),
(252, 6, 'Zone II'),
(253, 6, 'Zone III'),
(268, 7, 'Alingaro'),
(269, 7, 'Arnaldo'),
(270, 7, 'Bacao I'),
(271, 7, 'Bacao II'),
(272, 7, 'Bagumbayan'),
(273, 7, 'Biclatan'),
(274, 7, 'Buenavista I'),
(275, 7, 'Buenavista II'),
(276, 7, 'Gov. Ferrer Poblacion'),
(277, 7, 'Javalera'),
(278, 7, 'Manggahan'),
(279, 7, 'Navarro'),
(280, 7, 'Panungyanan'),
(281, 7, 'Pasong Camachile I'),
(282, 7, 'Pasong Camachile II'),
(283, 7, 'Pasong Kawayan I'),
(284, 7, 'Pinagtipunan'),
(285, 7, 'Prinza Poblacion'),
(286, 7, 'San Francisco'),
(287, 7, 'San Juan I'),
(288, 7, 'San Juan II'),
(289, 7, 'Santa Clara'),
(290, 7, 'Santiago'),
(291, 7, 'Tapia'),
(292, 7, 'Tejero'),
(293, 7, 'Vibora Poblacion'),
(299, 8, 'Alapan I-A'),
(300, 8, 'Alapan I-B'),
(301, 8, 'Alapan I-C'),
(302, 8, 'Alapan II-A'),
(303, 8, 'Alapan II-B'),
(304, 8, 'Anabu I-A'),
(305, 8, 'Anabu I-B'),
(306, 8, 'Anabu I-C'),
(307, 8, 'Anabu I-D'),
(308, 8, 'Anabu I-E'),
(309, 8, 'Anabu II-A'),
(310, 8, 'Anabu II-B'),
(311, 8, 'Anabu II-C'),
(312, 8, 'Anabu II-D'),
(313, 8, 'Bayan Luma I'),
(314, 8, 'Bayan Luma II'),
(315, 8, 'Bayan Luma III'),
(316, 8, 'Bayan Luma IV'),
(317, 8, 'Bayan Luma V'),
(318, 8, 'Bucandala I'),
(319, 8, 'Bucandala II'),
(320, 8, 'Bucandala III'),
(321, 8, 'Bucandala IV'),
(322, 8, 'Buhay na Tubig'),
(323, 8, 'Maharlika'),
(324, 8, 'Malagasang I-A'),
(325, 8, 'Malagasang I-B'),
(326, 8, 'Malagasang I-C'),
(327, 8, 'Malagasang I-D'),
(328, 8, 'Malagasang II-A'),
(329, 8, 'Malagasang II-B'),
(330, 8, 'Malagasang II-C'),
(331, 8, 'Malagasang II-D'),
(332, 8, 'Mariano Espeleta I'),
(333, 8, 'Mariano Espeleta II'),
(334, 8, 'Medicion I-A'),
(335, 8, 'Medicion I-B'),
(336, 8, 'Medicion I-C'),
(337, 8, 'Medicion II-A'),
(338, 8, 'Medicion II-B'),
(339, 8, 'Medicion II-C'),
(340, 8, 'Medicion II-D'),
(341, 8, 'Pag-Asa I'),
(342, 8, 'Pag-Asa II'),
(343, 8, 'Palico I'),
(344, 8, 'Palico II'),
(345, 8, 'Pasong Buaya I'),
(346, 8, 'Pasong Buaya II'),
(347, 8, 'Poblacion I-A'),
(348, 8, 'Poblacion I-B'),
(349, 8, 'Poblacion I-C'),
(350, 8, 'Poblacion II-A'),
(351, 8, 'Poblacion II-B'),
(352, 8, 'Poblacion III-A'),
(353, 8, 'Poblacion III-B'),
(354, 8, 'Poblacion IV-A'),
(355, 8, 'Poblacion IV-B'),
(356, 8, 'Poblacion IV-C'),
(357, 8, 'Tanzang Luma I'),
(358, 8, 'Tanzang Luma II'),
(359, 8, 'Tanzang Luma III'),
(360, 8, 'Tanzang Luma IV'),
(361, 8, 'Tanzang Luma VI'),
(362, 8, 'Toclong I-A'),
(363, 8, 'Toclong I-B'),
(364, 8, 'Toclong I-C'),
(365, 8, 'Toclong II-A'),
(366, 8, 'Toclong II-B'),
(426, 9, 'Asisan'),
(427, 9, 'Bagong Tubig'),
(428, 9, 'Calabuso'),
(429, 9, 'Dapdap East'),
(430, 9, 'Dapdap West'),
(431, 9, 'Francisco'),
(432, 9, 'Guinhawa North'),
(433, 9, 'Guinhawa South'),
(434, 9, 'Iruhin East'),
(435, 9, 'Iruhin South'),
(436, 9, 'Iruhin West'),
(437, 9, 'Kaybagal East'),
(438, 9, 'Kaybagal North'),
(439, 9, 'Kaybagal South'),
(440, 9, 'Mag-Asawang Ilat'),
(441, 9, 'Maharlika East'),
(442, 9, 'Maharlika West'),
(443, 9, 'Maitim 2nd Central'),
(444, 9, 'Maitim 2nd West'),
(445, 9, 'Mendez Crossing East'),
(446, 9, 'Mendez Crossing West'),
(447, 9, 'NeoganI'),
(448, 9, 'Patutong Malaki North'),
(449, 9, 'Patutong Malaki South'),
(450, 9, 'Sambong'),
(451, 9, 'Silang Junction North'),
(452, 9, 'Silang Junction South'),
(453, 9, 'Sungay North'),
(454, 9, 'Sungay South'),
(455, 9, 'Tolentino East'),
(456, 9, 'Tolentino West'),
(457, 9, 'Zambal'),
(489, 10, 'Aguado'),
(490, 10, 'Cabezas'),
(491, 10, 'Cabuco'),
(492, 10, 'Conchu'),
(493, 10, 'De Ocampo'),
(494, 10, 'Gregorio'),
(495, 10, 'Inocencio'),
(496, 10, 'Lallana'),
(497, 10, 'Lapidario'),
(498, 10, 'Luciano'),
(499, 10, 'Osorio'),
(500, 10, 'Perez'),
(501, 10, 'San Agustin'),
(504, 11, 'Aldiano Olaes'),
(505, 11, 'Barangay 1 Poblacion'),
(506, 11, 'Barangay 2 Poblacion'),
(507, 11, 'Barangay 3 Poblacion'),
(508, 11, 'Barangay 4 Poblacion'),
(509, 11, 'Barangay 5 Poblacion'),
(510, 11, 'Benjamin Tirona'),
(511, 11, 'Epifanio Malia'),
(512, 11, 'Fiorello Calimag'),
(513, 11, 'Francisco de Castro'),
(514, 11, 'Gavino Maderan'),
(515, 11, 'Gregoria de Jesus'),
(516, 11, 'Inocencio Salud'),
(517, 11, 'Jacinto Lumbreras'),
(518, 11, 'Kapitan Kua'),
(519, 11, 'Marcelino Memije'),
(520, 11, 'Nicolasa Virata'),
(521, 11, 'Pantaleon Granados'),
(522, 11, 'Ramon Cruz'),
(523, 11, 'San Gabriel'),
(524, 11, 'San Jose'),
(525, 11, 'Severino de Las Alas'),
(526, 11, 'Tiniente Tiago'),
(535, 12, 'A. Dalusag'),
(536, 12, 'Batas Dao'),
(537, 12, 'Cabuco'),
(538, 12, 'Castaños Cerca'),
(539, 12, 'Castaños Lejos'),
(540, 12, 'Kabulusan'),
(541, 12, 'Kaymisas'),
(542, 12, 'Lumipa'),
(543, 12, 'Narvaez'),
(544, 12, 'Poblacion I'),
(545, 12, 'Poblacion II'),
(546, 12, 'Poblacion III'),
(547, 12, 'Poblacion IV'),
(548, 12, 'Tabora'),
(550, 13, 'Agus-us'),
(551, 13, 'Alulod'),
(552, 13, 'Banaba Cerca'),
(553, 13, 'Banaba Lejos'),
(554, 13, 'Bancod'),
(555, 13, 'Barangay 1'),
(556, 13, 'Barangay 2'),
(557, 13, 'Barangay 3'),
(558, 13, 'Barangay 4'),
(559, 13, 'Buna Cerca'),
(560, 13, 'Buna Lejos I'),
(561, 13, 'Buna Lejos II'),
(562, 13, 'Calumpang Cerca'),
(563, 13, 'Calumpang Lejos I'),
(564, 13, 'Carasuchi'),
(565, 13, 'Daine I'),
(566, 13, 'Daine II'),
(567, 13, 'Guyam Malaki'),
(568, 13, 'Guyam Munti'),
(569, 13, 'Harasan'),
(570, 13, 'Kayquit I'),
(571, 13, 'Kayquit II'),
(572, 13, 'Kayquit III'),
(573, 13, 'Kaytambog'),
(574, 13, 'Kaytapos'),
(575, 13, 'Limbon'),
(576, 13, 'Lumampong Balagbag'),
(577, 13, 'Lumampong Halayhay'),
(578, 13, 'Mahabangkahoy Cerca'),
(579, 13, 'Mahabangkahoy Lejos'),
(580, 13, 'Mataas na Lupa'),
(581, 13, 'Pulo'),
(582, 13, 'Tambo Balagbag'),
(583, 13, 'Tambo Ilaya'),
(584, 13, 'Tambo Kulit'),
(585, 13, 'Tambo Malaki'),
(613, 14, 'Balsahan-Bisita'),
(614, 14, 'Batong Dalig'),
(615, 14, 'Binakayan-Aplaya'),
(616, 14, 'Binakayan-Kanluran'),
(617, 14, 'Congbalay-Legaspi'),
(618, 14, 'Gahak'),
(619, 14, 'Kaingen'),
(620, 14, 'Magdalo'),
(621, 14, 'Manggahan-Lawin'),
(622, 14, 'Panamitan'),
(623, 14, 'Pulvorista'),
(624, 14, 'Samala-Marquez'),
(625, 14, 'San Sebastian'),
(626, 14, 'Santa Isabel'),
(627, 14, 'Tabon I'),
(628, 14, 'Tabon II'),
(629, 14, 'Tabon III'),
(630, 14, 'Toclong'),
(631, 14, 'Tramo-Bantayan'),
(632, 14, 'Wakas I'),
(633, 14, 'Wakas II'),
(644, 15, 'Baliwag'),
(645, 15, 'Barangay 1'),
(646, 15, 'Barangay 2'),
(647, 15, 'Barangay 3'),
(648, 15, 'Barangay 4'),
(649, 15, 'Bendita I'),
(650, 15, 'Bendita II'),
(651, 15, 'Caluangan'),
(652, 15, 'Kabulusan'),
(653, 15, 'Medina'),
(654, 15, 'Pacheco'),
(655, 15, 'Ramirez'),
(656, 15, 'San Agustin'),
(657, 15, 'Tua'),
(658, 15, 'Urdaneta'),
(659, 16, 'Bucal I'),
(660, 16, 'Bucal II'),
(661, 16, 'Bucal IIIA'),
(662, 16, 'Bucal IIIB'),
(663, 16, 'Bucal IVA'),
(664, 16, 'Bucal IVB'),
(665, 16, 'Caingin Poblacion'),
(666, 16, 'Garita I A'),
(667, 16, 'Garita I B'),
(668, 16, 'Layong Mabilog'),
(669, 16, 'Pantihan I'),
(670, 16, 'Pantihan II'),
(671, 16, 'Pantihan III'),
(672, 16, 'Patungan'),
(673, 16, 'Pinagsanhan I A'),
(674, 16, 'Pinagsanhan I B'),
(675, 16, 'Poblacion I A'),
(676, 16, 'Poblacion I B'),
(677, 16, 'Poblacion II A'),
(678, 16, 'Poblacion II B'),
(679, 16, 'San Miguel I A'),
(680, 16, 'San Miguel I B'),
(681, 16, 'Tulay Kanluran'),
(682, 16, 'Tulay Silangan'),
(690, 17, 'Anuling Cerca I'),
(691, 17, 'Anuling Cerca II'),
(692, 17, 'Anuling Lejos I'),
(693, 17, 'Anuling Lejos II'),
(694, 17, 'Asis I'),
(695, 17, 'Asis II'),
(696, 17, 'Asis III'),
(697, 17, 'Banayad'),
(698, 17, 'Bukal'),
(699, 17, 'Galicia I'),
(700, 17, 'Galicia II'),
(701, 17, 'Miguel Mojica'),
(702, 17, 'Palocpoc I'),
(703, 17, 'Palocpoc II'),
(704, 17, 'Panungyan I'),
(705, 17, 'Panungyan II'),
(706, 17, 'Poblacion I'),
(707, 17, 'Poblacion II'),
(708, 17, 'Poblacion III'),
(709, 17, 'Poblacion IV'),
(710, 17, 'Poblacion V'),
(711, 17, 'Poblacion VI'),
(712, 17, 'Poblacion VII'),
(721, 18, 'Bagong Karsada'),
(722, 18, 'Balsahan'),
(723, 18, 'Bancaan'),
(724, 18, 'Bucana Malaki'),
(725, 18, 'Calubcob'),
(726, 18, 'Capt. C. Nazareno'),
(727, 18, 'Gomez-Zamora'),
(728, 18, 'Halang'),
(729, 18, 'Ibayo Estacion'),
(730, 18, 'Ibayo Silangan'),
(731, 18, 'Kanluran'),
(732, 18, 'Labac'),
(733, 18, 'Mabolo'),
(734, 18, 'Makina'),
(735, 18, 'Malainen Bago'),
(736, 18, 'Malainen Luma'),
(737, 18, 'Muzon'),
(738, 18, 'Palangue 1'),
(739, 18, 'Palangue 2&3'),
(740, 18, 'San Roque'),
(741, 18, 'Santulan'),
(742, 18, 'Sapa'),
(743, 18, 'Timalan Balsahan'),
(744, 18, 'Timalan Concepcion'),
(752, 19, 'Magdiwang'),
(753, 19, 'Poblacion'),
(754, 19, 'Salcedo I'),
(755, 19, 'Salcedo II'),
(756, 19, 'San Antonio I'),
(757, 19, 'San Antonio II'),
(758, 19, 'San Jose I'),
(759, 19, 'San Jose II'),
(760, 19, 'San Juan I'),
(761, 19, 'San Juan II'),
(762, 19, 'San Rafael I'),
(763, 19, 'San Rafael II'),
(764, 19, 'San Rafael III'),
(765, 19, 'San Rafael IV'),
(766, 19, 'Santa Rosa I'),
(767, 19, 'Santa Rosa II'),
(783, 20, 'Bagbag I'),
(784, 20, 'Bagbag II'),
(785, 20, 'Kanluran'),
(786, 20, 'Ligtong II'),
(787, 20, 'Ligtong III'),
(788, 20, 'Muzon I'),
(789, 20, 'Muzon II'),
(790, 20, 'Sapa I'),
(791, 20, 'Sapa II'),
(792, 20, 'Sapa III'),
(793, 20, 'Silangan I'),
(794, 20, 'Silangan II'),
(795, 20, 'Tejeros Convention'),
(796, 20, 'Wawa I'),
(797, 20, 'Wawa II'),
(798, 20, 'Wawa III'),
(814, 21, 'Acacia'),
(815, 21, 'Adlas'),
(816, 21, 'Anahaw I'),
(817, 21, 'Anahaw II'),
(818, 21, 'Balite I'),
(819, 21, 'Balite II'),
(820, 21, 'Balubad'),
(821, 21, 'Barangay I'),
(822, 21, 'Barangay II'),
(823, 21, 'Barangay III'),
(824, 21, 'Barangay IV'),
(825, 21, 'Batas'),
(826, 21, 'Biga I'),
(827, 21, 'Biga II'),
(828, 21, 'Biluso'),
(829, 21, 'Buho'),
(830, 21, 'Bulihan'),
(831, 21, 'Cabangaan'),
(832, 21, 'Hoyo'),
(833, 21, 'Iba'),
(834, 21, 'Ipil I'),
(835, 21, 'Ipil II'),
(836, 21, 'Kaong'),
(837, 21, 'Lalaan I'),
(838, 21, 'Lalaan II'),
(839, 21, 'Litlit'),
(840, 21, 'Lucsuhin'),
(841, 21, 'Maguyam'),
(842, 21, 'Malaking Tatyao'),
(843, 21, 'Mataas na Burol'),
(844, 21, 'Munting Ilog'),
(845, 21, 'Narra I'),
(846, 21, 'Narra II'),
(847, 21, 'Paligawan'),
(848, 21, 'Pooc I'),
(849, 21, 'Pooc II'),
(850, 21, 'Pulong Bunga'),
(851, 21, 'Pulong Saging'),
(852, 21, 'Puting Kahoy'),
(853, 21, 'San Miguel I'),
(854, 21, 'San Miguel II'),
(855, 21, 'San Vicente I'),
(856, 21, 'San Vicente II'),
(857, 21, 'Santol'),
(858, 21, 'Tartaria'),
(859, 21, 'Toledo'),
(860, 21, 'Tubuan I'),
(861, 21, 'Tubuan II'),
(862, 21, 'Tubuan III'),
(863, 21, 'Ulat'),
(864, 21, 'Yakal'),
(877, 22, 'Amaya I'),
(878, 22, 'Amaya II'),
(879, 22, 'Amaya III'),
(880, 22, 'Amaya IV'),
(881, 22, 'Amaya V'),
(882, 22, 'Amaya VI'),
(883, 22, 'Amaya VII'),
(884, 22, 'Bagtas'),
(885, 22, 'Barangay I'),
(886, 22, 'Barangay II'),
(887, 22, 'Barangay III'),
(888, 22, 'Biga'),
(889, 22, 'Bucal'),
(890, 22, 'Bunga'),
(891, 22, 'Calibuyo'),
(892, 22, 'Capipisa'),
(893, 22, 'Daang Amaya I'),
(894, 22, 'Daang Amaya II'),
(895, 22, 'Halayhay'),
(896, 22, 'Julugan I'),
(897, 22, 'Julugan II'),
(898, 22, 'Julugan III'),
(899, 22, 'Julugan IV'),
(900, 22, 'Julugan V'),
(901, 22, 'Julugan VI'),
(902, 22, 'Lambingan'),
(903, 22, 'Mulawin'),
(904, 22, 'Paradahan I'),
(905, 22, 'Paradahan II'),
(906, 22, 'Punta I'),
(907, 22, 'Punta II'),
(908, 22, 'Sahud Ulan'),
(909, 22, 'Sanja Mayor'),
(910, 22, 'Santol'),
(911, 22, 'Tanauan'),
(912, 22, 'Tres Cruses'),
(940, 23, 'Bucana'),
(941, 23, 'Poblacion I'),
(942, 23, 'Poblacion I A'),
(943, 23, 'Poblacion II'),
(944, 23, 'Poblacion III'),
(945, 23, 'San Jose'),
(946, 23, 'San Juan I'),
(947, 23, 'San Juan II'),
(948, 23, 'Sapang I'),
(949, 23, 'Sapang II');

-- --------------------------------------------------------

--
-- Table structure for table `cavite_cities`
--

CREATE TABLE `cavite_cities` (
  `id` int(11) NOT NULL,
  `city_name` varchar(100) NOT NULL,
  `postal_code` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cavite_cities`
--

INSERT INTO `cavite_cities` (`id`, `city_name`, `postal_code`) VALUES
(1, 'Alfonso', '4123'),
(2, 'Amadeo', '4119'),
(3, 'City Of Bacoor', '4102'),
(4, 'City Of Carmona', '4116'),
(5, 'City Of Cavite', '4100'),
(6, 'City Of Dasmariñas', '4114'),
(7, 'City Of General Trias', '4107'),
(8, 'City Of Imus', '4103'),
(9, 'City Of Tagaytay', '4120'),
(10, 'City of Trece Martires', '4109'),
(11, 'Gen. Mariano Alvarez', '4117'),
(12, 'General Emilio Aguinaldo', '4124'),
(13, 'Indang', '4122'),
(14, 'Kawit', '4104'),
(15, 'Magallanes', '4113'),
(16, 'Maragondon', '4112'),
(17, 'Mendez', '4121'),
(18, 'Naic', '4110'),
(19, 'Noveleta', '4105'),
(20, 'Rosario', '4106'),
(21, 'Silang', '4118'),
(22, 'Tanza', '4108'),
(23, 'Ternate', '4111');

-- --------------------------------------------------------

--
-- Table structure for table `counselor_meetings`
--

CREATE TABLE `counselor_meetings` (
  `id` int(11) NOT NULL,
  `referral_id` int(11) DEFAULT NULL,
  `incident_report_id` varchar(20) DEFAULT NULL,
  `meeting_date` datetime DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `persons_present` text DEFAULT NULL,
  `meeting_minutes` text DEFAULT NULL,
  `location` text DEFAULT NULL,
  `prepared_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `meeting_sequence` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `department_id`, `name`, `status`) VALUES
(1, 1, 'BS Information Technology', 'active'),
(2, 1, 'BS Computer Science', 'active'),
(3, 5, 'BS Agricultural and Biosystems Engineering', 'active'),
(4, 2, 'BS Architecture', 'active'),
(5, 2, 'BS Civil Engineering', 'active'),
(6, 3, 'BS Computer Engineering', 'active'),
(7, 3, 'BS Electrical Engineering', 'active'),
(8, 3, 'BS Electronics Engineering', 'active'),
(9, 4, 'BS Industrial Engineering', 'active'),
(10, 4, 'BS Industrial Technology Major in Automotive Technology', 'active'),
(11, 4, 'BS Industrial Technology Major in Electrical Technology', 'active'),
(12, 4, 'BS Industrial Technology Major in Electronics Technology', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `status`) VALUES
(1, 'Department of Information Technology (DIT)', 'active'),
(2, 'Department of Civil Engineering (DCEA)', 'active'),
(3, 'Department of Computer, Electronics, and Electrical Engineering (DCEEE)', 'active'),
(4, 'Department of Industrial Engineering and Technology (DIET)', 'active'),
(5, 'Department of Agriculture and Food Engineering (DAFE)', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `request_id` varchar(50) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `student_number` varchar(20) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `document_request` varchar(100) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `id_presented` varchar(255) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `request_time` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_requests`
--

INSERT INTO `document_requests` (`request_id`, `student_id`, `first_name`, `last_name`, `student_number`, `gender`, `department`, `course`, `document_request`, `purpose`, `id_presented`, `contact_email`, `request_time`, `status`, `is_archived`) VALUES
('REQ_6759a61019e567.98026090', 202011307, 'BAMBY', 'REQUILLO', '202011307', 'FEMALE', 'Department of Information Technology (DIT)', 'BS Information Technology', 'Good Moral', 'Employment', 'School ID', 'bamby@cvsu.edu.ph', '2024-12-11 00:00:00', 'Approved', 0),
('REQ_67f909793bf4b0.31336187', 202102690, 'NEIL TRISTHAN', 'MOJICA', '202102690', 'MALE', 'Department of Computer, Electronics, and Electrical Engineering (DCEEE)', 'BS Computer Engineering', 'Good Moral', 'Employment', 'School ID', 'neiltristhan.mojica@cvsu.edu.ph', '2025-04-11 00:00:00', 'Rejected', 0),
('REQ_6801020dbc46e9.33696378', 202105212, 'JHANNAH BERNADETTE', 'ALBINO', '202105212', 'FEMALE', 'Department of Computer, Electronics, and Electrical Engineering (DCEEE)', 'BS Computer Engineering', 'Good Moral', 'Scholarship Application', 'School ID', 'jana@cvsu.edu.ph', '2025-04-17 00:00:00', 'Rejected', 0),
('REQ_68010275a84a48.68204599', 202105212, 'JHANNAH BERNADETTE', 'ALBINO', '202105212', 'FEMALE', 'Department of Computer, Electronics, and Electrical Engineering (DCEEE)', 'BS Computer Engineering', 'Good Moral', 'Transfer to Another School', 'School ID', 'jana@cvsu.edu.ph', '2025-04-17 00:00:00', 'Rejected', 0),
('REQ_6801d023d1b0c7.42549091', 202105212, 'JHANNAH BERNADETTE', 'ALBINO', '202105212', 'FEMALE', 'Department of Computer, Electronics, and Electrical Engineering (DCEEE)', 'BS Computer Engineering', 'Good Moral', 'Board Examination', 'School ID', 'jana@cvsu.edu.ph', '2025-04-18 00:00:00', 'Rejected', 0),
('REQ_68021ca94f7b38.44965782', 202102690, 'NEIL TRISTHAN', 'MOJICA', '202102690', 'MALE', 'Department of Computer, Electronics, and Electrical Engineering (DCEEE)', 'BS Computer Engineering', 'Good Moral', 'Board Examination', 'School ID', 'neiltristhan.mojica@cvsu.edu.ph', '2025-04-18 00:00:00', 'Pending', 0);

-- --------------------------------------------------------

--
-- Table structure for table `incident_reports`
--

CREATE TABLE `incident_reports` (
  `id` varchar(20) NOT NULL,
  `date_reported` datetime DEFAULT NULL,
  `place` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reported_by` varchar(255) DEFAULT NULL,
  `reporters_id` int(11) DEFAULT NULL,
  `reported_by_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `approval_date` datetime DEFAULT NULL,
  `facilitator_id` int(11) DEFAULT NULL,
  `resolution_status` enum('Pending','In Progress','Resolved') DEFAULT 'Pending',
  `resolution_notes` text DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incident_reports`
--

INSERT INTO `incident_reports` (`id`, `date_reported`, `place`, `description`, `reported_by`, `reporters_id`, `reported_by_type`, `file_path`, `created_at`, `status`, `approval_date`, `facilitator_id`, `resolution_status`, `resolution_notes`, `is_archived`) VALUES
('CEIT-24-25-0001', '2025-05-12 10:32:46', 'neil - May 12, 2025 at 10:31 AM', 'hehehe', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-05-12 02:32:46', 'Settled', '2025-05-14 17:19:18', 1, 'Resolved', 'neillllll', 0),
('CEIT-24-25-0002', '2025-05-12 17:08:06', 'neil - May 12, 2025 at 5:07 PM', 'he', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-05-12 09:08:06', 'Settled', '2025-05-14 19:08:00', 1, 'Resolved', '--punta ka ba school?', 0);

-- --------------------------------------------------------

--
-- Table structure for table `incident_witnesses`
--

CREATE TABLE `incident_witnesses` (
  `id` int(11) NOT NULL,
  `incident_report_id` varchar(20) DEFAULT NULL,
  `witness_type` enum('student','staff') NOT NULL,
  `witness_id` varchar(20) DEFAULT NULL,
  `witness_name` varchar(255) DEFAULT NULL,
  `witness_student_name` varchar(100) DEFAULT NULL,
  `witness_course` varchar(100) DEFAULT NULL,
  `witness_year_level` varchar(20) DEFAULT NULL,
  `witness_email` varchar(255) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `section_name` varchar(255) DEFAULT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `adviser_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incident_witnesses`
--

INSERT INTO `incident_witnesses` (`id`, `incident_report_id`, `witness_type`, `witness_id`, `witness_name`, `witness_student_name`, `witness_course`, `witness_year_level`, `witness_email`, `section_id`, `section_name`, `adviser_id`, `adviser_name`) VALUES
(346, 'CEIT-24-25-0001', 'student', NULL, 'JUDE F BAUTISTA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(347, 'CEIT-24-25-0002', 'student', '202511111', 'ZAIRO ARGAS', 'ZAIRO ARGAS', 'BS Information Technology', 'First Year', NULL, 2503261, 'BS Information Technology - First Year Section 1', 5, 'Miguelee C. Escover');

--
-- Triggers `incident_witnesses`
--
DELIMITER $$
CREATE TRIGGER `before_witness_insert` BEFORE INSERT ON `incident_witnesses` FOR EACH ROW BEGIN
    DECLARE student_fullname VARCHAR(100);
    DECLARE student_course_name VARCHAR(100);
    DECLARE student_year VARCHAR(20);
    
    IF NEW.witness_type = 'student' AND NEW.witness_id IS NOT NULL THEN
        SELECT 
            CONCAT(ts.first_name, ' ', ts.last_name),
            c.name,
            s.year_level
        INTO 
            student_fullname,
            student_course_name,
            student_year
        FROM tbl_student ts
        LEFT JOIN sections s ON ts.section_id = s.id
        LEFT JOIN courses c ON s.course_id = c.id
        WHERE ts.student_id = NEW.witness_id;
        
        SET NEW.witness_student_name = student_fullname;
        SET NEW.witness_course = student_course_name;
        SET NEW.witness_year_level = student_year;
        SET NEW.witness_name = student_fullname;  -- Update the existing witness_name field too
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `id` int(11) NOT NULL,
  `incident_report_id` varchar(20) DEFAULT NULL,
  `meeting_date` datetime DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `persons_present` text DEFAULT NULL,
  `meeting_minutes` text DEFAULT NULL,
  `location` text DEFAULT NULL,
  `prepared_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `meeting_sequence` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meetings`
--

INSERT INTO `meetings` (`id`, `incident_report_id`, `meeting_date`, `venue`, `persons_present`, `meeting_minutes`, `location`, `prepared_by`, `created_at`, `meeting_sequence`) VALUES
(9, 'CEIT-24-25-0080', '2025-02-24 08:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-02-21 11:39:35', 1),
(10, 'CEIT-24-25-0079', '2025-03-03 08:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-03-01 12:56:13', 1),
(11, 'CEIT-24-25-0082', '2025-03-10 08:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-03-08 04:24:20', 1),
(12, 'CEIT-24-25-0083', '2025-03-11 08:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-03-08 04:26:29', 1),
(13, 'CEIT-24-25-0083', '2025-03-04 12:27:00', 'CEIT GUIDANCE OFFICE', '[\"Neil\",\"Hello\"]', 'wala', NULL, 'Gladys G. Perey', '2025-03-08 04:28:23', 2),
(14, 'CEIT-24-25-0090', '2025-03-12 08:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-03-08 04:29:37', 1),
(15, 'CEIT-24-25-0091', '2025-03-17 08:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-03-08 04:31:17', 1),
(16, 'CEIT-24-25-0091', '2025-03-11 12:37:00', 'CEIT GUIDANCE OFFICE', '[\"Neil\"]', 'nnn', NULL, 'Gladys G. Perey', '2025-03-08 04:38:02', 2),
(17, 'CEIT-24-25-0079', '2025-04-10 08:30:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-04-09 05:36:03', 1),
(18, 'CEIT-24-25-0099', '2025-04-10 12:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-04-09 06:41:34', 1),
(19, 'CEIT-24-25-0082', '2025-04-15 08:30:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-04-15 06:14:06', 1),
(20, 'CEIT-24-25-0105', '2025-04-30 12:30:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-04-15 07:49:40', 1),
(21, 'CEIT-24-25-0079', '2025-04-15 12:30:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-04-15 08:00:01', 1),
(22, 'CEIT-24-25-0079', '2025-04-15 09:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-04-15 08:27:10', 1),
(23, 'CEIT-24-25-0105', '2025-04-18 14:18:00', 'CEIT GUIDANCE OFFICE', '[\"Marie\",\"buset\"]', 'bbaam bbaam ang bata', NULL, 'Gladys G. Perey', '2025-04-18 06:18:48', 2),
(24, 'CEIT-24-25-0079', '2025-04-21 08:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-04-19 05:40:47', 1),
(25, 'CEIT-24-25-0090', '2025-05-01 08:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-04-30 15:51:11', 1),
(26, 'CEIT-24-25-0099', '2025-05-01 13:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-05-01 03:59:32', 1),
(27, 'CEIT-24-25-0079', '2025-05-06 08:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-05-01 10:25:38', 1),
(28, 'CEIT-24-25-0001', '2025-05-14 17:18:00', 'CEIT GUIDANCE OFFICE', '[\"neil\"]', 'neillllll', NULL, 'Gladys G. Perey', '2025-05-14 09:19:00', 1),
(29, 'CEIT-24-25-0002', '2025-05-14 18:46:00', 'CEIT GUIDANCE OFFICE', '[\"neil\"]', '--punta ka ba school?', NULL, 'Gladys G. Perey', '2025-05-14 10:47:06', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_type` enum('student','facilitator','adviser','counselor','dean','admin','guard','instructor') NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_type`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(432, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-09 12:52:11'),
(465, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-04-16 10:13:05'),
(466, 'guard', '2', 'Your Incident Report submitted on April 16, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0107', 'view_submitted_incident_reports_guard.php', 0, '2025-04-16 10:13:23'),
(467, 'student', '202102690', 'New document request status update', 'request_form.php', 0, '2025-04-17 04:26:11'),
(468, 'student', '202102690', 'Profile form update required', 'student_profile_form.php', 0, '2025-04-17 04:26:11'),
(470, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0107', 0, '2025-04-17 05:45:34'),
(471, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0107', 0, '2025-04-17 05:45:34'),
(472, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0107', 0, '2025-04-17 05:45:34'),
(473, 'guard', '2', 'The status of an incident report you submitted has been updated to: For Meeting', 'view_submitted_incident_reports_guard.php', 0, '2025-04-17 05:56:26'),
(474, 'student', '202102690', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0107', 0, '2025-04-17 05:56:26'),
(475, 'student', '202105212', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0107', 0, '2025-04-17 05:56:26'),
(476, 'adviser', '1', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0107', 0, '2025-04-17 05:56:26'),
(478, 'student', '202103642', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0108', 0, '2025-04-17 05:57:21'),
(479, 'student', '202102690', 'The status of an incident report you submitted has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0108', 0, '2025-04-17 06:04:39'),
(480, 'student', '202103642', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0108', 0, '2025-04-17 06:04:39'),
(481, 'guard', '2', 'The status of an incident report you submitted has been updated to: For Meeting', 'view_submitted_incident_reports_guard.php', 0, '2025-04-17 06:05:03'),
(482, 'student', '202102690', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0107', 0, '2025-04-17 06:05:03'),
(483, 'student', '202105212', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0107', 0, '2025-04-17 06:05:03'),
(484, 'adviser', '1', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0107', 0, '2025-04-17 06:05:03'),
(485, 'student', '202102690', 'Your request for Good Moral has been Rejected.', NULL, 0, '2025-04-17 06:14:20'),
(486, 'instructor', '2', 'New student violation report submitted', 'view_incident_reports.php', 0, '2025-04-17 09:59:18'),
(487, 'instructor', '2', 'Reminder: Submit pending incident reports', 'instructor_incident_report.php', 0, '2025-04-17 09:59:18'),
(489, 'instructor', '2', 'The status of an incident report you submitted has been updated to: For Meeting', 'view_incident_reports.php?id=CEIT-24-25-0109', 0, '2025-04-17 10:11:59'),
(490, 'student', '202105212', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0109', 0, '2025-04-17 10:11:59'),
(491, 'student', '202106149', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0109', 0, '2025-04-17 10:11:59'),
(492, 'adviser', '1', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0109', 0, '2025-04-17 10:11:59'),
(493, 'student', '202102690', 'Your request for Good Moral has been Rejected.', NULL, 0, '2025-04-17 10:19:49'),
(494, 'student', '202011307', 'Your request for Good Moral has been Approved.', NULL, 0, '2025-04-17 10:25:43'),
(495, 'student', '202102690', 'Your request for Good Moral has been Rejected.', NULL, 0, '2025-04-17 10:27:52'),
(496, 'student', '202105212', 'Your request for Good Moral has been Rejected.', NULL, 0, '2025-04-17 13:29:07'),
(497, 'facilitator', '1', 'New incident report submitted by MR D. INSTRUCTOR', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0110', 0, '2025-04-18 03:48:42'),
(498, 'facilitator', '2', 'New incident report submitted by MR D. INSTRUCTOR', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0110', 0, '2025-04-18 03:48:42'),
(500, 'facilitator', '1', 'New incident report submitted by Student', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0111', 0, '2025-04-18 03:57:02'),
(501, 'facilitator', '2', 'New incident report submitted by Student', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0111', 0, '2025-04-18 03:57:02'),
(503, 'student', '202105212', 'Your request for Good Moral has been Rejected.', NULL, 0, '2025-04-18 03:58:27'),
(504, 'facilitator', '1', 'New document request from Jhannah Bernadette Q. Albino for Good Moral', 'view_document_requests.php?request_id=REQ_6801d023d1b0c7.42549091', 0, '2025-04-18 04:08:03'),
(505, 'facilitator', '2', 'New document request from Jhannah Bernadette Q. Albino for Good Moral', 'view_document_requests.php?request_id=REQ_6801d023d1b0c7.42549091', 0, '2025-04-18 04:08:03'),
(507, 'facilitator', '1', 'New incident report submitted by Facilitator Andy D. Dizon', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0112', 0, '2025-04-18 05:11:29'),
(508, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0113', 0, '2025-04-18 05:13:58'),
(509, 'facilitator', '1', 'New incident report submitted by Adviser Miguelee', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 05:37:02'),
(510, 'facilitator', '2', 'New incident report submitted by Adviser Miguelee', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 05:37:02'),
(512, 'adviser', '1', 'New incident report submitted by Adviser Miguelee', 'view_adviser_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 05:37:02'),
(513, 'adviser', '2', 'New incident report submitted by Adviser Miguelee', 'view_adviser_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 05:37:02'),
(516, 'student', '202511111', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 05:37:57'),
(517, 'student', '202511120', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 05:37:57'),
(520, 'student', '202511111', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 05:44:27'),
(521, 'student', '202511120', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 05:44:27'),
(523, 'adviser', '5', 'New incident report submitted by a student in your section', 'view_student_incident_reports.php', 0, '2025-04-18 06:06:06'),
(524, 'adviser', '5', 'Reminder: Complete section assignments', 'view_section.php', 0, '2025-04-18 06:06:06'),
(526, 'facilitator', '1', 'New incident report submitted by Adviser Miguelee', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 06:09:16'),
(527, 'facilitator', '2', 'New incident report submitted by Adviser Miguelee', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 06:09:16'),
(529, 'adviser', '1', 'New incident report submitted by Adviser Miguelee', 'view_adviser_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 06:09:16'),
(530, 'adviser', '2', 'New incident report submitted by Adviser Miguelee', 'view_adviser_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-04-18 06:09:16'),
(532, 'student', '202105212', 'Your incident report has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0105', 0, '2025-04-18 06:19:08'),
(533, 'adviser', '1', 'An incident report for your student has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0105', 0, '2025-04-18 06:19:08'),
(534, 'instructor', '1', 'New student violation report submitted', 'view_incident_reports.php', 0, '2025-04-18 06:22:44'),
(535, 'instructor', '1', 'Reminder: Submit pending incident reports', 'instructor_incident_report.php', 0, '2025-04-18 06:22:44'),
(536, 'instructor', '1', 'New guidance office announcement', '#', 0, '2025-04-18 06:22:44'),
(537, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-04-18 09:29:11'),
(538, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-04-18 09:29:52'),
(539, 'guard', '2', 'Your Incident Report submitted on April 18, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0115', 'view_submitted_incident_reports_guard.php', 0, '2025-04-18 09:30:04'),
(540, 'facilitator', '1', 'Escalated incident report from CEIT Dean\'s office. Report ID: CEIT-24-25-0115', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0115', 0, '2025-04-18 09:30:04'),
(541, 'facilitator', '2', 'Escalated incident report from CEIT Dean\'s office. Report ID: CEIT-24-25-0115', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0115', 0, '2025-04-18 09:30:04'),
(543, 'facilitator', '1', 'New document request from Neil Tristhan N. Mojica for Good Moral', 'facilitator_requested_documents.php?request_id=REQ_68021ca94f7b38.44965782', 0, '2025-04-18 09:34:33'),
(544, 'facilitator', '2', 'New document request from Neil Tristhan N. Mojica for Good Moral', 'facilitator_requested_documents.php?request_id=REQ_68021ca94f7b38.44965782', 0, '2025-04-18 09:34:33'),
(546, 'facilitator', '1', 'New incident report submitted by Adviser Miguelee', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0116', 0, '2025-04-18 14:29:40'),
(547, 'facilitator', '2', 'New incident report submitted by Adviser Miguelee', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0116', 0, '2025-04-18 14:29:40'),
(550, 'adviser', '2', 'New incident report submitted by Adviser Miguelee', 'view_adviser_incident_reports.php?id=CEIT-24-25-0116', 0, '2025-04-18 14:29:40'),
(552, 'adviser', '1', 'Your advisee NEIL TRISTHAN MOJICA has been involved in an incident report', 'view_adviser_incident_reports.php?id=CEIT-24-25-0116', 0, '2025-04-18 14:29:40'),
(553, 'adviser', '1', 'Your advisee KAYRON MARK BURZON has been involved in an incident report', 'view_adviser_incident_reports.php?id=CEIT-24-25-0116', 0, '2025-04-18 14:29:40'),
(554, 'facilitator', '1', 'New incident report submitted by Adviser Simeons', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0117', 0, '2025-04-18 14:43:11'),
(555, 'facilitator', '2', 'New incident report submitted by Adviser Simeons', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0117', 0, '2025-04-18 14:43:11'),
(557, 'adviser', '2', 'New incident report submitted by Adviser Simeons', 'view_student_incident_reports?id=CEIT-24-25-0117', 0, '2025-04-18 14:43:11'),
(560, 'adviser', '5', 'Your advisee ZAIRO ARGAS has been involved in an incident report', 'view_adviser_incident_reports.php?id=CEIT-24-25-0117', 0, '2025-04-18 14:43:11'),
(561, 'adviser', '5', 'Your advisee LUCIA M BERNARDO has been involved in an incident report', 'view_adviser_incident_reports.php?id=CEIT-24-25-0117', 0, '2025-04-18 14:43:11'),
(562, 'facilitator', '1', 'New incident report submitted by Adviser Simeons', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0118', 0, '2025-04-18 14:44:28'),
(563, 'facilitator', '2', 'New incident report submitted by Adviser Simeons', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0118', 0, '2025-04-18 14:44:28'),
(565, 'adviser', '2', 'New incident report submitted by Adviser Simeons', 'view_student_incident_reports.php?id=CEIT-24-25-0118', 0, '2025-04-18 14:44:28'),
(566, 'adviser', '5', 'New incident report submitted by Adviser Simeons', 'view_student_incident_reports.php?id=CEIT-24-25-0118', 0, '2025-04-18 14:44:28'),
(570, 'facilitator', '1', 'New incident report submitted by Adviser Simeons', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0119', 0, '2025-04-18 14:48:05'),
(571, 'facilitator', '2', 'New incident report submitted by Adviser Simeons', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0119', 0, '2025-04-18 14:48:05'),
(573, 'adviser', '2', 'New incident report submitted by Adviser Simeons', 'view_student_incident_reports.php?id=CEIT-24-25-0119', 0, '2025-04-18 14:48:05'),
(574, 'adviser', '5', 'New incident report submitted by Adviser Simeons', 'view_student_incident_reports.php?id=CEIT-24-25-0119', 0, '2025-04-18 14:48:05'),
(576, 'adviser', '5', 'Your advisee ZAIRO ARGAS has been involved in an incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0119', 0, '2025-04-18 14:48:05'),
(577, 'student', '202102690', 'A meeting has been rescheduled for your incident report on April 21, 2025, 8:00 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0079', 0, '2025-04-19 05:40:47'),
(578, 'adviser', '1', 'A meeting has been rescheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0079', 0, '2025-04-19 05:40:47'),
(579, 'student', '202105212', 'Your request for Good Moral has been Rejected.', NULL, 0, '2025-04-19 05:48:27'),
(580, 'counselor', '7', 'A new referral form has been submitted to you, from CEIT Guidance Office by facilitator: Gladys G Perey', 'view_referrals_page.php?id=80', 0, '2025-04-19 08:24:00'),
(581, 'student', '202105212', 'You have been referred to the guidance counselor office. Kindly wait for further details', 'view_student_referrals.php', 0, '2025-04-19 08:24:00'),
(582, 'counselor', '7', 'A new referral form has been submitted to you, from CEIT Guidance Office by facilitator: Gladys G Perey', 'view_referrals_page.php?id=81', 0, '2025-04-19 08:29:37'),
(583, 'student', '202105212', 'You have been referred to the guidance counselor office. Kindly wait for further details', 'view_student_referrals.php', 0, '2025-04-19 08:29:37'),
(584, 'student', '202105212', 'Your referral has been marked as done by the counselor.', 'view_student_referrals.php', 0, '2025-04-19 11:00:17'),
(585, 'student', '202105212', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=82', 0, '2025-04-19 14:00:24'),
(586, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=82', 0, '2025-04-19 14:00:24'),
(587, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=82', 0, '2025-04-19 14:00:24'),
(588, 'counselor', '8', 'A new incident report has been referred to you.', 'view_referral_details.php?id=82', 0, '2025-04-19 14:00:24'),
(589, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=83', 0, '2025-04-19 14:00:29'),
(590, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=83', 0, '2025-04-19 14:00:29'),
(591, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=83', 0, '2025-04-19 14:00:29'),
(592, 'counselor', '8', 'A new incident report has been referred to you.', 'view_referral_details.php?id=83', 0, '2025-04-19 14:00:29'),
(593, 'student', '202105212', 'Your incident report has been referred to the Guidance Counselor.', 'view_student_referrals.php?id=84', 0, '2025-04-19 15:27:56'),
(594, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=84', 0, '2025-04-19 15:27:56'),
(595, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=84', 0, '2025-04-19 15:27:56'),
(596, 'counselor', '8', 'A new incident report has been referred to you.', 'view_referral_details.php?id=84', 0, '2025-04-19 15:27:56'),
(597, 'counselor', '7', 'A new referral form has been submitted to you, from CEIT Guidance Office by facilitator: Gladys G Perey', 'view_referrals_page.php?id=85', 0, '2025-04-19 16:09:01'),
(598, 'student', '202511111', 'You have been referred to the guidance counselor office. Kindly wait for further details', 'view_student_referrals.php', 0, '2025-04-19 16:09:01'),
(599, 'adviser', '1', 'The status of an incident report you submitted has been updated to: For Meeting', 'view_submitted_incident_reports-adviser.php', 0, '2025-04-19 16:09:50'),
(600, 'student', '202511111', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0119', 0, '2025-04-19 16:09:50'),
(601, 'adviser', '5', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0119', 0, '2025-04-19 16:09:50'),
(602, 'student', '202511111', 'Your incident report has been referred to the Guidance Counselor.', 'view_student_referrals.php?id=86', 0, '2025-04-19 16:10:40'),
(603, 'adviser', '5', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=86', 0, '2025-04-19 16:10:40'),
(604, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=86', 0, '2025-04-19 16:10:40'),
(605, 'counselor', '8', 'A new incident report has been referred to you.', 'view_referral_details.php?id=86', 0, '2025-04-19 16:10:40'),
(606, 'guard', '2', 'Your Incident Report submitted on April 18, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0120', 'view_submitted_incident_reports_guard.php', 0, '2025-04-30 15:32:41'),
(607, 'facilitator', '1', 'Escalated incident report from CEIT Dean\'s office. Report ID: CEIT-24-25-0120', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0120', 0, '2025-04-30 15:32:41'),
(608, 'facilitator', '2', 'Escalated incident report from CEIT Dean\'s office. Report ID: CEIT-24-25-0120', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0120', 0, '2025-04-30 15:32:41'),
(610, 'student', '202102690', 'A meeting has been rescheduled for your incident report on May 1, 2025, 8:00 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0090', 0, '2025-04-30 15:51:11'),
(611, 'adviser', '1', 'A meeting has been rescheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0090', 0, '2025-04-30 15:51:11'),
(612, 'student', '202102690', 'You have a scheduled meeting on March 12, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0090', 0, '2025-04-30 15:51:20'),
(613, 'student', '202102690', 'You have a scheduled meeting on March 12, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0090', 0, '2025-04-30 15:54:43'),
(614, 'student', '202102690', 'You have a scheduled meeting on March 12, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0090', 0, '2025-04-30 16:06:29'),
(615, 'student', '202102690', 'SMS notification sent for meeting', 'view_meeting_details.php?id=CEIT-24-25-0090', 0, '2025-04-30 16:06:38'),
(616, 'student', '202102690', 'A meeting has been rescheduled for your incident report on May 1, 2025, 1:00 PM', 'view_student_incident_reports.php?id=CEIT-24-25-0099', 0, '2025-05-01 03:59:32'),
(617, 'adviser', '1', 'A meeting has been rescheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0099', 0, '2025-05-01 03:59:32'),
(618, 'student', '202102690', 'SMS notification sent for meeting', 'view_meeting_details.php?id=CEIT-24-25-0099', 0, '2025-05-01 03:59:44'),
(619, 'student', '202103642', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=87', 0, '2025-05-01 05:58:02'),
(620, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=87', 0, '2025-05-01 05:58:02'),
(621, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=87', 0, '2025-05-01 05:58:02'),
(622, 'counselor', '8', 'A new incident report has been referred to you.', 'view_referral_details.php?id=87', 0, '2025-05-01 05:58:02'),
(623, 'student', '202106149', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=88', 0, '2025-05-01 05:58:16'),
(624, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=88', 0, '2025-05-01 05:58:16'),
(625, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=88', 0, '2025-05-01 05:58:16'),
(626, 'counselor', '8', 'A new incident report has been referred to you.', 'view_referral_details.php?id=88', 0, '2025-05-01 05:58:16'),
(627, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=89', 0, '2025-05-01 05:58:27'),
(628, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=89', 0, '2025-05-01 05:58:27'),
(629, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=89', 0, '2025-05-01 05:58:27'),
(630, 'counselor', '8', 'A new incident report has been referred to you.', 'view_referral_details.php?id=89', 0, '2025-05-01 05:58:27'),
(631, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=90', 0, '2025-05-01 05:58:33'),
(632, 'counselor', '8', 'A new incident report has been referred to you.', 'view_referral_details.php?id=90', 0, '2025-05-01 05:58:33'),
(633, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0121', 0, '2025-05-01 06:00:43'),
(634, 'facilitator', '1', 'The status of an incident report you submitted has been updated to: For Meeting', 'view_facilitator_incident_reports.php', 0, '2025-05-01 06:01:08'),
(635, 'student', '202102690', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0121', 0, '2025-05-01 06:01:08'),
(636, 'adviser', '1', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0121', 0, '2025-05-01 06:01:08'),
(637, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=91', 0, '2025-05-01 06:01:53'),
(639, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=91', 0, '2025-05-01 06:01:53'),
(640, 'counselor', '8', 'A new incident report has been referred to you.', 'view_referral_details.php?id=91', 0, '2025-05-01 06:01:53'),
(641, 'guard', '2', 'Your Incident Report submitted on April 9, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0122', 'view_submitted_incident_reports_guard.php', 0, '2025-05-01 08:11:08'),
(642, 'facilitator', '1', 'Escalated incident report from CEIT Dean\'s office. Report ID: CEIT-24-25-0122', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0122', 0, '2025-05-01 08:11:08'),
(643, 'facilitator', '2', 'Escalated incident report from CEIT Dean\'s office. Report ID: CEIT-24-25-0122', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0122', 0, '2025-05-01 08:11:08'),
(645, 'student', '202102690', 'A meeting has been rescheduled for your incident report on May 6, 2025, 8:00 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0079', 0, '2025-05-01 10:25:38'),
(646, 'adviser', '1', 'A meeting has been rescheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0079', 0, '2025-05-01 10:25:38'),
(647, 'student', '202102690', 'You have a scheduled meeting on March 3, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0079', 0, '2025-05-01 10:25:56'),
(648, 'student', '202102690', 'You have a scheduled meeting on March 3, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0079', 0, '2025-05-01 10:26:41'),
(649, 'student', '202105212', 'Your request for Good Moral has been Rejected.', NULL, 0, '2025-05-11 07:08:12'),
(650, 'student', '202011307', 'Your request for Good Moral has been Approved.', NULL, 0, '2025-05-11 07:08:45'),
(651, 'student', '202105212', 'Your request for Good Moral has been Rejected.', NULL, 0, '2025-05-11 07:09:14'),
(652, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-11 10:37:49'),
(653, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-11 10:47:18'),
(654, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0003', 0, '2025-05-11 11:38:51'),
(655, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-11 11:52:13'),
(656, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-11 11:52:46'),
(657, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0003', 0, '2025-05-11 11:53:24'),
(658, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-11 12:11:30'),
(659, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-11 12:12:06'),
(660, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0003', 0, '2025-05-11 12:12:45'),
(661, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0004', 0, '2025-05-11 12:17:37'),
(662, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-11 12:42:20'),
(663, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-11 12:42:48'),
(664, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0003', 0, '2025-05-11 12:43:46'),
(665, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0004', 0, '2025-05-11 12:49:37'),
(666, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-11 14:35:44'),
(667, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-11 14:45:38'),
(668, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-11 14:47:30'),
(669, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0003', 0, '2025-05-11 15:15:01'),
(670, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0004', 0, '2025-05-11 15:23:21'),
(671, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-11 15:59:33'),
(672, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-11 16:16:23'),
(673, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-11 23:00:27'),
(674, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-11 23:50:19'),
(675, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-12 01:21:31'),
(676, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-12 01:56:23'),
(677, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-12 02:22:01'),
(678, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-12 02:25:17'),
(679, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-12 02:32:46'),
(680, 'facilitator', '2', 'New incident report submitted by Facilitator Gladys G. Perey', 'view_facilitator_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-12 09:08:06'),
(681, 'student', '202011307', 'Your request for Good Moral has been Approved.', NULL, 0, '2025-05-13 02:41:34'),
(682, 'student', '202011307', 'Your request for Good Moral has been Processing.', NULL, 0, '2025-05-13 02:50:37'),
(683, 'student', '202011307', 'Your request for Good Moral has been Approved.', NULL, 0, '2025-05-13 02:50:46'),
(684, 'student', '202105212', 'Your request for Good Moral has been Rejected.', NULL, 0, '2025-05-13 02:51:03'),
(685, 'student', '202011307', 'Your request for Good Moral has been Approved.', NULL, 0, '2025-05-13 02:59:47'),
(686, 'student', '202105212', 'Your request for Good Moral has been Rejected.', NULL, 0, '2025-05-13 03:00:03'),
(687, 'facilitator', '1', 'The status of an incident report you submitted has been updated to: For Meeting', 'view_facilitator_incident_reports.php', 0, '2025-05-14 09:18:35'),
(688, 'student', '202102690', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-14 09:18:35'),
(689, 'student', '202105212', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-14 09:18:35'),
(690, 'student', '202106149', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-14 09:18:35'),
(691, 'adviser', '1', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-14 09:18:35'),
(692, 'student', '202102690', 'Your incident report has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-14 09:19:18'),
(693, 'adviser', '1', 'An incident report for your student has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-05-14 09:19:18'),
(694, 'facilitator', '1', 'The status of an incident report you submitted has been updated to: For Meeting', 'view_facilitator_incident_reports.php', 0, '2025-05-14 10:46:35'),
(695, 'student', '202102690', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 10:46:35'),
(696, 'adviser', '1', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 10:46:35'),
(697, 'student', '202102690', 'Your incident report has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 10:47:15'),
(698, 'adviser', '1', 'An incident report for your student has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 10:47:15'),
(699, 'facilitator', '1', 'The status of an incident report you submitted has been updated to: For Meeting', 'view_facilitator_incident_reports.php', 0, '2025-05-14 11:06:39'),
(700, 'student', '202102690', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 11:06:39'),
(701, 'adviser', '1', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 11:06:39'),
(702, 'student', '202102690', 'Your incident report has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 11:06:46'),
(703, 'adviser', '1', 'An incident report for your student has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 11:06:46'),
(704, 'facilitator', '1', 'The status of an incident report you submitted has been updated to: For Meeting', 'view_facilitator_incident_reports.php', 0, '2025-05-14 11:07:51'),
(705, 'student', '202102690', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 11:07:51'),
(706, 'adviser', '1', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 11:07:51'),
(707, 'student', '202102690', 'Your incident report has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 11:08:00'),
(708, 'adviser', '1', 'An incident report for your student has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-05-14 11:08:00');

-- --------------------------------------------------------

--
-- Table structure for table `pending_incident_reports`
--

CREATE TABLE `pending_incident_reports` (
  `id` int(11) NOT NULL,
  `guard_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `date_reported` datetime NOT NULL,
  `place` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `reported_by` varchar(255) NOT NULL,
  `reported_by_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_incident_reports`
--

INSERT INTO `pending_incident_reports` (`id`, `guard_id`, `student_id`, `date_reported`, `place`, `description`, `reported_by`, `reported_by_type`, `file_path`, `status`, `created_at`) VALUES
(1, 2, '', '2024-12-17 08:31:11', 'gg - December 17, 2024 at 3:30 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 07:31:11'),
(2, 2, '', '2024-12-17 08:34:20', 'torTrait - December 17, 2024 at 3:33 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 07:34:20'),
(3, 2, '', '2024-12-17 08:44:12', 'torTrait - December 17, 2024 at 3:43 PM', 'bully', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 07:44:12'),
(4, 2, '', '2024-12-17 09:01:00', 'neil - December 17, 2024 at 4:00 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 08:01:00'),
(5, 2, '', '2024-12-17 09:02:01', 'neil - December 17, 2024 at 4:00 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 08:02:01'),
(6, 2, '', '2024-12-17 09:02:32', 'neil - December 17, 2024 at 4:00 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 08:02:32'),
(7, 2, '', '2024-12-17 09:05:59', 'neil - December 17, 2024 at 4:00 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 08:05:59'),
(8, 2, '', '2024-12-17 09:07:03', 'neil - December 17, 2024 at 4:06 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 08:07:03'),
(9, 2, '', '2024-12-17 09:33:00', 'neil - December 17, 2024 at 4:32 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 08:33:00'),
(10, 2, '', '2024-12-17 09:33:44', 'neil - December 17, 2024 at 4:32 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 08:33:44'),
(11, 2, '', '2024-12-17 09:36:53', 'neil - December 17, 2024 at 4:36 PM', 'n', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 08:36:53'),
(12, 2, '', '2024-12-17 09:46:21', 'neil - December 17, 2024 at 4:45 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-17 08:46:21'),
(13, 2, '', '2024-12-18 03:44:21', 'torTrait - December 18, 2024 at 10:43 AM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 02:44:21'),
(14, 2, '', '2024-12-18 03:52:41', 'neil - December 18, 2024 at 10:52 AM', 'bully', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 02:52:41'),
(15, 2, '', '2024-12-18 03:54:46', 'neil - December 18, 2024 at 10:54 AM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 02:54:46'),
(16, 2, '', '2024-12-18 04:27:10', 'nn - December 18, 2024 at 11:27 AM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 03:27:10'),
(17, 2, '', '2024-12-18 04:27:52', 'nn - December 18, 2024 at 11:27 AM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 03:27:52'),
(18, 2, '', '2024-12-18 05:41:06', 'neil - December 18, 2024 at 12:40 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 04:41:06'),
(19, 2, '', '2024-12-18 06:00:42', 'neil - December 18, 2024 at 1:00 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 05:00:42'),
(20, 2, '', '2024-12-18 06:07:49', 'torTrait - December 18, 2024 at 1:07 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 05:07:49'),
(21, 2, '', '2024-12-18 06:12:43', 'neil - December 18, 2024 at 1:12 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 05:12:43'),
(22, 2, '', '2024-12-18 06:19:24', 'neil - December 18, 2024 at 1:18 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 05:19:24'),
(23, 2, '', '2024-12-18 06:30:45', 'neil - December 18, 2024 at 1:30 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 05:30:45'),
(24, 2, '', '2024-12-18 06:35:31', 'neil - December 18, 2024 at 1:35 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 05:35:31'),
(25, 2, '', '2024-12-18 06:48:13', 'neil - December 18, 2024 at 1:47 PM', 'mm', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 05:48:13'),
(26, 2, '', '2024-12-18 07:29:14', 'neil - December 18, 2024 at 2:27 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 06:29:14'),
(27, 2, '', '2024-12-18 07:37:23', 'neil - December 18, 2024 at 2:34 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 06:37:23'),
(28, 2, '', '2024-12-18 07:39:57', 'neil - December 18, 2024 at 2:39 PM', 'neilllll', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 06:39:57'),
(29, 2, '', '2024-12-18 07:46:57', 'neil - December 18, 2024 at 2:46 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 06:46:57'),
(33, 2, '', '2024-12-18 08:47:07', 'neil - December 18, 2024 at 3:46 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 07:47:07'),
(34, 2, '', '2024-12-18 08:48:49', 'neil - December 18, 2024 at 3:47 PM', 'neilss', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 07:48:49'),
(37, 2, '', '2024-12-18 09:12:54', 'neil - December 18, 2024 at 4:02 PM', 'well', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 08:12:54'),
(39, 2, '', '2024-12-18 09:43:39', 'neil - December 18, 2024 at 4:42 PM', 'nnnnnn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 08:43:39'),
(40, 2, '', '2024-12-18 09:45:43', 'neil - December 18, 2024 at 4:44 PM', 'nneil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 08:45:43'),
(41, 2, '', '2024-12-18 10:33:26', 'neil - December 18, 2024 at 5:33 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 09:33:26'),
(43, 2, '', '2024-12-18 10:37:13', 'neil - December 18, 2024 at 5:36 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 09:37:13'),
(45, 2, '', '2024-12-18 10:43:00', 'neil - December 18, 2024 at 5:42 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 09:43:00'),
(46, 2, '', '2024-12-18 10:53:09', 'neil - December 18, 2024 at 5:52 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 09:53:09'),
(47, 2, '', '2024-12-18 10:54:33', 'neil - December 18, 2024 at 5:53 PM', 'neil1234', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 09:54:33'),
(48, 2, '', '2024-12-18 11:00:19', 'neil - December 18, 2024 at 5:59 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 10:00:19'),
(50, 2, '', '2024-12-18 11:10:26', 'neil - December 18, 2024 at 6:09 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 10:10:26'),
(51, 2, '202102690', '2024-12-18 11:23:11', 'neil - December 18, 2024 at 6:22 PM', 'nnnnn123', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 10:23:11'),
(52, 2, '202102690', '2024-12-18 18:28:30', 'neil - December 18, 2024 at 6:27 PM', 'bunnnyy', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 10:28:30'),
(53, 2, '202102690', '2024-12-18 18:37:13', 'neil - December 18, 2024 at 6:36 PM', 'nnnnn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 10:37:13'),
(54, 2, '202106149', '2024-12-18 22:19:06', 'neil - December 18, 2024 at 10:18 PM', 'nnnnnn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 14:19:06'),
(55, 2, '202102690', '2024-12-18 22:27:38', 'neil - December 18, 2024 at 10:26 PM', 'neilpogi', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 14:27:38'),
(56, 2, '202102690', '2024-12-18 22:29:50', 'neil - December 18, 2024 at 10:28 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 14:29:50'),
(57, 2, '202102690', '2024-12-18 22:37:13', 'neil - December 18, 2024 at 10:35 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 14:37:13'),
(58, 2, '202102690', '2024-12-18 22:50:48', 'neil - December 18, 2024 at 10:49 PM', 'nn', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 14:50:48'),
(59, 2, '202102690', '2024-12-18 22:58:52', 'neil - December 18, 2024 at 10:57 PM', 'neil', 'CJ  MOJICA', 'guard', NULL, 'Pending', '2024-12-18 14:58:52'),
(60, 2, '202102690', '2024-12-18 23:16:44', 'neil - December 18, 2024 at 11:15 PM', 'neil', 'RUDYG  CALAY III.', 'guard', NULL, 'Pending', '2024-12-18 15:16:44'),
(61, 2, '202106149', '2024-12-19 10:43:52', 'Saluysoy - December 19, 2024 at 10:41 AM', 'bully', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-19 02:43:52'),
(62, 2, '202106149', '2024-12-19 11:04:41', 'saluysoy - December 19, 2024 at 11:03 AM', 'bully\r\n', 'MR  GUARD', 'guard', '../../uploads/incident_reports_proof/67638d493ef09_sample-stamp-in-rubber-style-red-round-grunge-sample-sign-rubber-stamp-on-white-illustration-free-vector.jpg', 'Pending', '2024-12-19 03:04:41'),
(63, 2, '202102690', '2024-12-19 11:53:23', 'salyusoy - December 19, 2024 at 11:52 AM', 'nnnnn', 'MR  GUARD', 'guard', NULL, 'Escalated', '2024-12-19 03:53:23'),
(64, 2, '202102690', '2024-12-19 12:02:12', 'neil - December 19, 2024 at 12:01 PM', 'nnnn', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-19 04:02:12'),
(65, 2, '202102690', '2024-12-19 12:15:21', 'salyusoy - December 19, 2024 at 12:14 PM', 'nnnnnn', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-19 04:15:21'),
(66, 2, '202102690', '2024-12-19 12:39:07', 'neil - December 19, 2024 at 12:38 PM', 'neil', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-19 04:39:07'),
(70, 2, '202102690', '2024-12-19 14:16:45', 'neil - December 19, 2024 at 2:15 PM', 'nnn', 'MR  GUARD', 'guard', NULL, 'Escalated', '2024-12-19 06:16:45'),
(71, 2, '202102690', '2024-12-19 16:07:57', 'salyusoy - December 19, 2024 at 3:06 PM', 'bully', 'MR  GUARD', 'guard', NULL, 'Escalated', '2024-12-19 08:07:57'),
(72, 2, '202102690', '2024-12-19 16:58:12', 'saluysoy - December 19, 2024 at 4:56 PM', 'mabaho', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-19 08:58:12'),
(73, 2, '202102690', '2024-12-19 17:44:44', 'neil - December 19, 2024 at 5:43 PM', 'sample', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-19 09:44:44'),
(74, 2, '202102690', '2024-12-21 22:09:24', 'neil - December 21, 2024 at 12:00 PM', 'hello', 'MR  GUARD', 'guard', NULL, 'Escalated', '2024-12-21 14:09:24'),
(77, 2, '202102690', '2024-12-21 22:51:24', 'neil - December 21, 2024 at 10:50 PM', 'moj', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-21 14:51:24'),
(78, 2, '202102690', '2024-12-29 16:06:31', 'neilll - December 29, 2024 at 4:05 PM', 'nn', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-29 08:06:31'),
(79, 2, '202102690', '2024-12-29 16:07:58', 'neilll - December 29, 2024 at 4:07 PM', 'nn', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-29 08:07:58'),
(80, 2, '202102690', '2024-12-29 16:20:42', 'neilll - December 29, 2024 at 4:08 PM', 'nn', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-29 08:20:42'),
(81, 2, '202102690', '2024-12-29 16:28:46', 'neilll - December 29, 2024 at 4:27 PM', 'nn', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-29 08:28:46'),
(82, 2, '202102690', '2024-12-29 16:36:30', 'neilll - December 29, 2024 at 4:35 PM', 'nn', 'MR  GUARD', 'guard', NULL, 'Pending', '2024-12-29 08:36:30'),
(88, 2, '202102690', '2025-03-08 16:29:47', 'neil - March 8, 2025 at 4:23 PM', 'nn', 'MR  GUARD', 'guard', NULL, 'Pending', '2025-03-08 08:29:47'),
(89, 2, '202102690', '2025-03-08 16:41:45', 'neil - March 8, 2025 at 4:41 PM', 'nnn', 'MR  GUARD', 'guard', NULL, 'Pending', '2025-03-08 08:41:45'),
(90, 2, '202102690', '2025-03-08 16:57:10', 'neil - March 8, 2025 at 4:56 PM', 'nn', 'MR  GUARD', 'guard', NULL, 'Pending', '2025-03-08 08:57:10'),
(91, 2, '202102690', '2025-03-08 17:01:39', 'neil - March 8, 2025 at 5:01 PM', 'nn', 'MR  GUARD', 'guard', NULL, 'Pending', '2025-03-08 09:01:39'),
(92, 2, '202102690', '2025-03-08 17:03:05', 'neil - March 8, 2025 at 5:02 PM', 'nn', 'MR  GUARD', 'guard', NULL, 'Pending', '2025-03-08 09:03:05'),
(93, 2, '202102690', '2025-03-08 17:08:50', 'neil - March 8, 2025 at 5:08 PM', 'nn', 'MR  GUARD', 'guard', NULL, 'Pending', '2025-03-08 09:08:50'),
(94, 2, '202102690', '2025-03-08 17:14:02', 'neil - March 8, 2025 at 5:13 PM', 'nnn', 'MR  GUARD', 'guard', NULL, 'Pending', '2025-03-08 09:14:02'),
(95, 2, '202102690', '2025-03-08 17:22:19', 'neil - March 8, 2025 at 5:21 PM', 'nnnn', 'MR  GUARD', 'guard', NULL, 'Pending', '2025-03-08 09:22:19'),
(96, 2, '202102690', '2025-03-08 17:44:33', 'neil - March 8, 2025 at 5:43 PM', 'neil', 'MR GUARD', 'guard', NULL, 'Pending', '2025-03-08 09:44:33'),
(97, 2, '202102690', '2025-03-08 18:06:10', 'neil - March 8, 2025 at 6:05 PM', 'neil', 'MR  GUARD', 'guard', NULL, 'Pending', '2025-03-08 10:06:10'),
(98, 2, '202102690', '2025-03-08 19:46:36', 'neil - March 8, 2025 at 7:46 PM', 'nnn', 'MR GUARD', 'guard', NULL, 'Pending', '2025-03-08 11:46:36'),
(99, 2, '202102690', '2025-03-08 19:49:44', 'neil - March 8, 2025 at 7:46 PM', 'nnn', 'MR GUARD', 'guard', NULL, 'Pending', '2025-03-08 11:49:44'),
(100, 2, '202102690', '2025-03-08 19:53:05', 'neil - March 8, 2025 at 7:52 PM', 'neil', 'MR GUARD', 'guard', NULL, 'Pending', '2025-03-08 11:53:05'),
(101, 2, '202102690', '2025-03-08 20:21:12', 'neil - March 8, 2025 at 8:20 PM', 'neeidi', 'MR GUARD', 'guard', NULL, 'Pending', '2025-03-08 12:21:12'),
(102, 2, '202102690', '2025-03-08 20:22:32', 'neil - March 8, 2025 at 8:20 PM', 'neeidi', 'MR GUARD', 'guard', NULL, 'Escalated', '2025-03-08 12:22:32'),
(103, 2, '202102690', '2025-03-08 20:36:55', 'neil - March 8, 2025 at 8:36 PM', 'nn', 'MR GUARD', 'guard', NULL, 'Escalated', '2025-03-08 12:36:55'),
(104, 2, '202102690', '2025-03-09 08:58:09', 'neil - March 9, 2025 at 8:57 AM', 'nnn', 'MR GUARD', 'guard', NULL, 'Pending', '2025-03-09 00:58:09'),
(105, 2, '202102690', '2025-03-09 09:47:42', 'neil - March 9, 2025 at 9:46 AM', 'mm', 'MR GUARD', 'guard', NULL, 'Escalated', '2025-03-09 01:47:42'),
(106, 2, '202102690', '2025-03-09 09:51:03', 'neil - March 9, 2025 at 9:50 AM', 'nn', 'MR GUARD', 'guard', NULL, 'Escalated', '2025-03-09 01:51:03'),
(107, 2, '202102690', '2025-03-09 17:41:31', 'neil - March 9, 2025 at 5:40 PM', 'beee', 'MR GUARD', 'guard', NULL, 'Escalated', '2025-03-09 09:41:31'),
(108, 2, '202102690', '2025-03-09 19:04:02', 'neil - March 9, 2025 at 7:02 PM', 'nnnn', 'MR GUARD', 'guard', NULL, 'Escalated', '2025-03-09 11:04:02'),
(109, 2, '202102690', '2025-03-09 19:06:46', 'neil - March 9, 2025 at 7:06 PM', 'mmmmm', 'MR GUARD', 'guard', NULL, 'Pending', '2025-03-09 11:06:46'),
(110, 2, '202102690', '2025-03-09 20:23:56', 'neil - March 9, 2025 at 8:22 PM', 'nnnnnn', 'MR GUARD', 'guard', NULL, 'Escalated', '2025-03-09 12:23:56'),
(111, 2, '202102690', '2025-03-09 20:52:11', 'neil - March 9, 2025 at 8:51 PM', 'mmm', 'MR GUARD', 'guard', NULL, 'Escalated', '2025-03-09 12:52:11'),
(112, 2, '202102690', '2025-04-09 11:47:44', 'Gate 2 - April 9, 2025 at 11:47 AM', 'nnn', 'MR GUARD', 'guard', NULL, 'Escalated', '2025-04-09 03:47:44'),
(113, 2, '202015172', '2025-04-09 14:34:31', 'Gate 2 - April 9, 2025 at 2:33 PM', 'mahal ni jhon vhic si Cherrie', 'MR GUARD', 'guard', '../../uploads/incident_reports_proof/Untitled.png', 'Escalated', '2025-04-09 06:34:31'),
(114, 2, '202102690', '2025-04-16 18:13:05', 'CEIT Building, Room 201 - April 16, 2025 at 6:12 PM', 'Ako ba \'to haha', 'MR GUARD', 'guard', '../../uploads/incident_reports_proof/cvsu1.jpg', 'Escalated', '2025-04-16 10:13:05'),
(115, 2, '202511111', '2025-04-18 17:29:11', 'Blackpink in your area - April 18, 2025 at 5:28 PM', 'you never know', 'MR GUARD', 'guard', NULL, 'Escalated', '2025-04-18 09:29:11'),
(116, 2, '202511117', '2025-04-18 17:29:52', 'CEIT Building, Room 201 - April 9, 2025 at 5:31 PM', 'efwqvweb', 'MR GUARD', 'guard', NULL, 'Escalated', '2025-04-18 09:29:52');

-- --------------------------------------------------------

--
-- Table structure for table `pending_incident_witnesses`
--

CREATE TABLE `pending_incident_witnesses` (
  `id` int(11) NOT NULL,
  `pending_report_id` int(11) NOT NULL,
  `witness_type` enum('student','staff') NOT NULL,
  `witness_id` varchar(20) DEFAULT NULL,
  `witness_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `witness_email` varchar(255) DEFAULT NULL,
  `witness_course` varchar(100) DEFAULT NULL,
  `witness_year_level` varchar(20) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `section_name` varchar(255) DEFAULT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `adviser_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_incident_witnesses`
--

INSERT INTO `pending_incident_witnesses` (`id`, `pending_report_id`, `witness_type`, `witness_id`, `witness_name`, `created_at`, `witness_email`, `witness_course`, `witness_year_level`, `section_id`, `section_name`, `adviser_id`, `adviser_name`) VALUES
(1, 1, 'staff', NULL, 'neil', '2024-12-17 07:31:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, 'student', '202102884', 'MARK CHRISTIAN TABUZO', '2024-12-17 07:34:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 3, 'student', '202102690', 'RICA MAE A. DUMAGAT', '2024-12-17 07:44:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 4, 'student', '202105212', 'neil', '2024-12-17 08:01:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 5, 'student', '202105212', 'neil', '2024-12-17 08:02:01', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 6, 'student', '202105212', 'neil', '2024-12-17 08:02:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 7, 'student', '202105212', 'neil', '2024-12-17 08:05:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 8, 'student', '202105212', 'neil', '2024-12-17 08:07:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 9, 'student', '202105212', 'neiln', '2024-12-17 08:33:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 10, 'student', '202105212', 'neiln', '2024-12-17 08:33:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 12, 'staff', NULL, 'neil', '2024-12-17 08:46:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 13, 'student', '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 02:44:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 18, 'student', '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 04:41:06', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 21, 'student', '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 05:12:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 24, 'student', '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 05:35:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 25, 'student', '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 05:48:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 26, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 06:29:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 27, 'student', '202105791', 'JUDE F. BAUTISTA', '2024-12-18 06:37:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 28, 'student', '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 06:39:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 29, 'student', '202105791', 'JUDE F. BAUTISTA', '2024-12-18 06:46:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 33, 'staff', NULL, '', '2024-12-18 07:47:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, 34, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 07:48:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 34, 'staff', NULL, '', '2024-12-18 07:48:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, 34, 'staff', NULL, '', '2024-12-18 07:48:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 37, 'staff', NULL, '', '2024-12-18 08:12:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(26, 37, 'student', '202106149', 'neil', '2024-12-18 08:12:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, 39, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 08:43:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, 40, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 08:45:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, 40, 'staff', NULL, '', '2024-12-18 08:45:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 41, 'student', '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 09:33:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 41, 'staff', NULL, '', '2024-12-18 09:33:26', 'hi@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(32, 43, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 09:37:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 43, 'staff', NULL, '', '2024-12-18 09:37:13', 'hi@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(34, 45, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 09:43:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 46, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 09:53:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 47, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 09:54:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 48, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 10:00:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(38, 48, 'staff', NULL, 'neil', '2024-12-18 10:00:19', 'hi123@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(39, 50, 'staff', NULL, 'neil', '2024-12-18 10:10:26', 'hi123@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(40, 51, 'staff', NULL, 'goku', '2024-12-18 10:23:11', 'hi123@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(41, 52, 'staff', NULL, 'goku69', '2024-12-18 10:28:30', 'hi123@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(42, 53, 'student', '202106746', 'EURICA MAE D. BORCE', '2024-12-18 10:37:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(43, 53, 'student', '202105791', 'JUDE F. BAUTISTA', '2024-12-18 10:37:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 53, 'staff', NULL, 'goku69', '2024-12-18 10:37:13', 'hi123@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(45, 54, 'student', '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 14:19:06', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 55, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 14:27:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(47, 55, 'student', '202107777', 'Neil Pogi', '2024-12-18 14:27:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 55, 'staff', NULL, 'goku6943', '2024-12-18 14:27:38', 'hi@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(49, 56, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(50, 56, 'student', '201777777', 'balot', '2024-12-18 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(51, 57, 'student', '202188888', 'RICA MAE A. DUMAGAT', '2024-12-18 14:37:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(52, 57, 'student', '273737373', 'neil', '2024-12-18 14:37:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(53, 58, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 14:50:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(54, 58, 'student', '202666666', 'NEIL TRISTHAN ESTRELLA', '2024-12-18 14:50:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(55, 58, 'staff', NULL, 'SIMEON DAEZ', '2024-12-18 14:50:48', 'hi123@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(56, 59, 'student', '202105791', 'JUDE F. BAUTISTA', '2024-12-18 14:58:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(57, 59, 'student', '291773737', 'NEIL DENZEL BARBACENA', '2024-12-18 14:58:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(58, 59, 'staff', NULL, 'GAKUA 2765', '2024-12-18 14:58:52', 'hi123@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(59, 60, 'student', '202105791', 'JUDE F. BAUTISTA', '2024-12-18 15:16:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(60, 60, 'staff', NULL, 'NEIL HELLO', '2024-12-18 15:16:44', 'hi123@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(61, 61, 'student', '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-19 02:43:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(62, 62, 'student', '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-19 03:04:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(63, 63, 'student', '202188888', 'DENZEL MOJICA', '2024-12-19 03:53:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(64, 64, 'staff', NULL, 'DENZEL MOJICA', '2024-12-19 04:02:12', 'neiltristhan@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(65, 65, 'student', '202102777', 'HELLO', '2024-12-19 04:15:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(66, 65, 'staff', NULL, 'ANYEONG', '2024-12-19 04:15:21', 'neiltristhan123@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(67, 66, 'student', '823636363', 'NEIL MOJ', '2024-12-19 04:39:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(68, 70, 'student', '202373733', 'NEHDDHH', '2024-12-19 06:16:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(69, 70, 'student', NULL, 'NEHDDHH123', '2024-12-19 06:16:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(70, 70, 'staff', NULL, 'ANYEONG', '2024-12-19 06:16:45', 'neiltristhan123@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(71, 71, 'student', NULL, 'DENZEL MOJICA', '2024-12-19 08:07:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(72, 71, 'student', '272737737', 'NEIL MOJ', '2024-12-19 08:07:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(73, 71, 'staff', NULL, 'JONG', '2024-12-19 08:07:57', 'neil@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(74, 72, 'student', '263737373', 'JUDE F. BAUTISTA', '2024-12-19 08:58:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(75, 72, 'student', NULL, 'NEHDDHH', '2024-12-19 08:58:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(76, 72, 'student', '202636636', 'NEIL MOJ', '2024-12-19 08:58:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(77, 73, 'student', '202778888', 'NEIL MOJ', '2024-12-19 09:44:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(78, 73, 'student', NULL, 'DENZEL MOJICA', '2024-12-19 09:44:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(79, 73, 'staff', NULL, 'ANYEONG', '2024-12-19 09:44:44', 'neiltristhan@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(80, 74, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-21 14:09:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(81, 74, 'student', NULL, 'NEHDDHHSSSS', '2024-12-21 14:09:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(84, 77, 'student', '202106149', 'JHON VHIC C. BALLERA', '2024-12-21 14:51:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(85, 78, 'student', NULL, 'NEEN', '2024-12-29 08:06:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(86, 78, 'student', NULL, 'NEIL TRISTHAN N. MOJICA', '2024-12-29 08:06:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(87, 79, 'student', NULL, 'NEEN', '2024-12-29 08:07:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(88, 80, 'staff', NULL, 'NEIL', '2024-12-29 08:20:42', 'neil@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(89, 81, 'student', '263636363', 'NEEN', '2024-12-29 08:28:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(90, 81, 'student', NULL, 'NEEN', '2024-12-29 08:28:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(91, 81, 'staff', NULL, 'NEIL STAFF', '2024-12-29 08:28:46', 'neil@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(92, 82, 'student', '273737373', 'NEEN', '2024-12-29 08:36:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(93, 88, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 08:29:47', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons Daez'),
(94, 89, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 08:41:45', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons Daez'),
(95, 90, 'student', '273555555', 'NEIL MOJ', '2025-03-08 08:57:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(96, 91, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 09:01:39', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(97, 92, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 09:03:05', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(98, 93, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 09:08:50', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(99, 94, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 09:14:02', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(100, 95, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 09:22:19', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(101, 96, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 09:44:33', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(102, 97, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 10:06:10', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(103, 98, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 11:46:36', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(104, 99, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 11:49:44', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(105, 100, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 11:53:06', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(106, 1, 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', '2025-03-08 12:03:46', NULL, 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(107, 101, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 12:21:12', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(108, 102, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 12:22:32', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(109, 103, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-08 12:36:55', NULL, 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(110, 104, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-09 00:58:09', NULL, 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(111, 105, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-09 01:47:42', NULL, 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(112, 105, 'student', NULL, 'NEIL MOJ', '2025-03-09 01:47:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(113, 106, 'student', NULL, 'DENZEL MOJICA', '2025-03-09 01:51:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(114, 106, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-09 01:51:03', NULL, 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(115, 107, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-09 09:41:31', NULL, 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(116, 108, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-09 11:04:02', NULL, 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(117, 109, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-09 11:06:47', NULL, 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(118, 110, 'student', '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-03-09 12:23:56', NULL, 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(119, 110, 'student', NULL, 'NEHDDHH', '2025-03-09 12:23:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(120, 110, 'staff', NULL, 'ANYEONG', '2025-03-09 12:23:56', 'neiltristhan544@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(121, 111, 'student', NULL, 'HELLO', '2025-03-09 12:52:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(122, 111, 'student', NULL, 'HELLO (2)', '2025-03-09 12:52:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(123, 112, 'student', NULL, 'HELLO', '2025-04-09 03:47:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(124, 113, 'student', NULL, 'HELLLLLO PO', '2025-04-09 06:34:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(125, 114, 'staff', NULL, 'NEIL TRISTHANNN', '2025-04-16 10:13:05', 'neiltristhan.mojica@cvsu.edu.ph', NULL, NULL, NULL, NULL, NULL, NULL),
(126, 115, 'staff', NULL, 'JISOO', '2025-04-18 09:29:11', 'instructor2@cvsu.com', NULL, NULL, NULL, NULL, NULL, NULL),
(127, 116, 'staff', NULL, 'JISSOO', '2025-04-18 09:29:52', 'dean1@cvsu.edu.ph', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pending_student_violations`
--

CREATE TABLE `pending_student_violations` (
  `id` int(11) NOT NULL,
  `pending_report_id` int(11) NOT NULL,
  `student_id` varchar(255) DEFAULT NULL,
  `student_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `student_course` varchar(100) DEFAULT NULL,
  `student_year_level` varchar(20) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `section_name` varchar(255) DEFAULT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `adviser_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_student_violations`
--

INSERT INTO `pending_student_violations` (`id`, `pending_report_id`, `student_id`, `student_name`, `created_at`, `student_course`, `student_year_level`, `section_id`, `section_name`, `adviser_id`, `adviser_name`) VALUES
(1, 13, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 02:44:21', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 16, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 03:27:10', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 17, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 03:27:52', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 18, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 04:41:06', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 19, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 05:00:42', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 20, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 05:07:49', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 21, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 05:12:43', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 24, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 05:35:31', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 25, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 05:48:13', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 26, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 06:29:14', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 27, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 06:37:23', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 28, '202105791', 'JUDE F. BAUTISTA', '2024-12-18 06:39:57', NULL, NULL, NULL, NULL, NULL, NULL),
(13, 29, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 06:46:57', NULL, NULL, NULL, NULL, NULL, NULL),
(17, 33, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 07:47:07', NULL, NULL, NULL, NULL, NULL, NULL),
(18, 34, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 07:48:49', NULL, NULL, NULL, NULL, NULL, NULL),
(19, 34, '202102908', 'AIRA LIZETTE B. CABRAL', '2024-12-18 07:48:49', NULL, NULL, NULL, NULL, NULL, NULL),
(22, 37, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 08:12:54', NULL, NULL, NULL, NULL, NULL, NULL),
(23, 39, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 08:43:39', NULL, NULL, NULL, NULL, NULL, NULL),
(24, 40, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 08:45:43', NULL, NULL, NULL, NULL, NULL, NULL),
(25, 41, '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 09:33:26', NULL, NULL, NULL, NULL, NULL, NULL),
(27, 43, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 09:37:13', NULL, NULL, NULL, NULL, NULL, NULL),
(29, 45, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 09:43:00', NULL, NULL, NULL, NULL, NULL, NULL),
(30, 46, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 09:53:09', NULL, NULL, NULL, NULL, NULL, NULL),
(31, 47, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 09:54:33', NULL, NULL, NULL, NULL, NULL, NULL),
(32, 48, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 10:00:19', NULL, NULL, NULL, NULL, NULL, NULL),
(34, 50, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 10:10:26', NULL, NULL, NULL, NULL, NULL, NULL),
(35, 51, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 10:23:11', NULL, NULL, NULL, NULL, NULL, NULL),
(36, 51, '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 10:23:11', NULL, NULL, NULL, NULL, NULL, NULL),
(37, 52, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 10:28:30', NULL, NULL, NULL, NULL, NULL, NULL),
(38, 53, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 10:37:13', NULL, NULL, NULL, NULL, NULL, NULL),
(39, 54, '202106149', 'JHON VHIC C. BALLERA', '2024-12-18 14:19:06', NULL, NULL, NULL, NULL, NULL, NULL),
(40, 55, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 14:27:38', NULL, NULL, NULL, NULL, NULL, NULL),
(41, 56, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 14:29:50', NULL, NULL, NULL, NULL, NULL, NULL),
(42, 57, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 14:37:13', NULL, NULL, NULL, NULL, NULL, NULL),
(43, 57, '202108888', 'neil', '2024-12-18 14:37:13', NULL, NULL, NULL, NULL, NULL, NULL),
(44, 58, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 14:50:48', NULL, NULL, NULL, NULL, NULL, NULL),
(45, 58, '202777777', 'DENZEL MOJICA', '2024-12-18 14:50:48', NULL, NULL, NULL, NULL, NULL, NULL),
(46, 59, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 14:58:52', NULL, NULL, NULL, NULL, NULL, NULL),
(47, 60, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-18 15:16:44', NULL, NULL, NULL, NULL, NULL, NULL),
(48, 60, '273383837', 'DENZEL MOJICA', '2024-12-18 15:16:44', NULL, NULL, NULL, NULL, NULL, NULL),
(49, 61, '202106149', 'JHON VHIC C. BALLERA', '2024-12-19 02:43:52', NULL, NULL, NULL, NULL, NULL, NULL),
(50, 62, '202106149', 'JHON VHIC C. BALLERA', '2024-12-19 03:04:41', NULL, NULL, NULL, NULL, NULL, NULL),
(51, 63, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-19 03:53:23', NULL, NULL, NULL, NULL, NULL, NULL),
(52, 63, '202888888', 'STUDENT1', '2024-12-19 03:53:23', NULL, NULL, NULL, NULL, NULL, NULL),
(53, 64, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-19 04:02:12', NULL, NULL, NULL, NULL, NULL, NULL),
(54, 65, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-19 04:15:21', NULL, NULL, NULL, NULL, NULL, NULL),
(55, 66, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-19 04:39:07', NULL, NULL, NULL, NULL, NULL, NULL),
(56, 66, '404888888', 'NEIL POGI', '2024-12-19 04:39:07', NULL, NULL, NULL, NULL, NULL, NULL),
(61, 70, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-19 06:16:45', NULL, NULL, NULL, NULL, NULL, NULL),
(62, 70, NULL, 'STUDENT1', '2024-12-19 06:16:45', NULL, NULL, NULL, NULL, NULL, NULL),
(63, 70, '328727847', 'NEIL STUDENT', '2024-12-19 06:16:45', NULL, NULL, NULL, NULL, NULL, NULL),
(64, 71, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-19 08:07:57', NULL, NULL, NULL, NULL, NULL, NULL),
(65, 71, '273737373', 'NEIL STUDENT', '2024-12-19 08:07:57', NULL, NULL, NULL, NULL, NULL, NULL),
(66, 71, NULL, 'DENZEL MOJICA', '2024-12-19 08:07:57', NULL, NULL, NULL, NULL, NULL, NULL),
(67, 72, '202102699', 'NEIL POGI', '2024-12-19 08:58:12', NULL, NULL, NULL, NULL, NULL, NULL),
(68, 72, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-19 08:58:12', NULL, NULL, NULL, NULL, NULL, NULL),
(69, 73, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-19 09:44:44', NULL, NULL, NULL, NULL, NULL, NULL),
(70, 73, NULL, 'NEIL STUDENT', '2024-12-19 09:44:44', NULL, NULL, NULL, NULL, NULL, NULL),
(71, 73, '202337777', 'STUDENT1', '2024-12-19 09:44:44', NULL, NULL, NULL, NULL, NULL, NULL),
(72, 74, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-21 14:09:24', NULL, NULL, NULL, NULL, NULL, NULL),
(73, 74, NULL, 'NEIL STUDENT', '2024-12-21 14:09:24', NULL, NULL, NULL, NULL, NULL, NULL),
(74, 74, '202337373', 'STUDENT1', '2024-12-21 14:09:24', NULL, NULL, NULL, NULL, NULL, NULL),
(77, 77, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-21 14:51:24', NULL, NULL, NULL, NULL, NULL, NULL),
(78, 78, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-29 08:06:31', NULL, NULL, NULL, NULL, NULL, NULL),
(79, 79, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-29 08:07:58', NULL, NULL, NULL, NULL, NULL, NULL),
(80, 80, '293939393', 'NEEN', '2024-12-29 08:20:42', NULL, NULL, NULL, NULL, NULL, NULL),
(81, 80, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-29 08:20:42', NULL, NULL, NULL, NULL, NULL, NULL),
(82, 81, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-29 08:28:46', NULL, NULL, NULL, NULL, NULL, NULL),
(83, 81, '273737373', 'NEIL', '2024-12-29 08:28:46', NULL, NULL, NULL, NULL, NULL, NULL),
(84, 81, NULL, 'NEIL', '2024-12-29 08:28:46', NULL, NULL, NULL, NULL, NULL, NULL),
(85, 82, '202102690', 'NEIL TRISTHAN N. MOJICA', '2024-12-29 08:36:30', NULL, NULL, NULL, NULL, NULL, NULL),
(86, 82, NULL, 'NEIL', '2024-12-29 08:36:30', NULL, NULL, NULL, NULL, NULL, NULL),
(87, 82, '277373737', 'NEIL', '2024-12-29 08:36:30', NULL, NULL, NULL, NULL, NULL, NULL),
(88, 88, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 08:29:47', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons Daez'),
(89, 89, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 08:41:45', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons Daez'),
(90, 90, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 08:57:10', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(91, 90, '666666666', 'STUDENT1', '2025-03-08 08:57:10', NULL, NULL, NULL, NULL, NULL, NULL),
(92, 91, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 09:01:39', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(93, 92, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 09:03:05', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(94, 93, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 09:08:50', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(95, 94, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 09:14:02', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(96, 95, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 09:22:19', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(97, 96, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 09:44:33', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(98, 97, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 10:06:10', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(99, 98, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 11:46:36', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(100, 99, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 11:49:44', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(101, 100, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 11:53:06', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(102, 1, '202102690', 'NEIL TRISTHAN MOJICA', '2025-03-08 12:03:45', 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(104, 101, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 12:21:12', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(105, 102, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 12:22:32', 'BS Information Technology', '0', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(106, 103, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-08 12:36:55', 'BS Computer Engineering', '0', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(107, 104, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-09 00:58:09', 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(108, 105, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-09 01:47:42', 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(109, 105, '777777777', 'NEIL TRISTHAN N. MOJICA123', '2025-03-09 01:47:42', NULL, NULL, NULL, NULL, NULL, NULL),
(110, 106, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-09 01:51:03', 'BS Information Technology', 'Irregular', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(111, 107, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-09 09:41:31', 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(112, 108, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-09 11:04:02', 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(113, 109, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-09 11:06:46', 'BS Information Technology', 'Irregular', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(114, 110, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-09 12:23:56', 'BS Information Technology', 'Irregular', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(115, 110, '666666666', 'NEIL STUDENT', '2025-03-09 12:23:56', NULL, NULL, NULL, NULL, NULL, NULL),
(116, 110, NULL, 'NEIL POGI', '2025-03-09 12:23:56', NULL, NULL, NULL, NULL, NULL, NULL),
(117, 111, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-03-09 12:52:11', 'BS Information Technology', 'Irregular', 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(118, 111, NULL, 'NEIL STUDENT', '2025-03-09 12:52:11', NULL, NULL, NULL, NULL, NULL, NULL),
(119, 111, NULL, 'NEIL STUDENT (2)', '2025-03-09 12:52:11', NULL, NULL, NULL, NULL, NULL, NULL),
(120, 112, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-04-09 03:47:44', 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(121, 113, NULL, 'CYNDELL HELLO', '2025-04-09 06:34:31', NULL, NULL, NULL, NULL, NULL, NULL),
(122, 113, '202015172', 'XANDER LEE A. SARITA', '2025-04-09 06:34:31', 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(123, 114, '202102690', 'NEIL TRISTHAN N. MOJICA', '2025-04-16 10:13:05', 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(124, 114, '202105212', 'JHANNAH BERNADETTE Q. ALBINO', '2025-04-16 10:13:05', 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(125, 115, '202511111', 'ZAIRO S. ARGAS', '2025-04-18 09:29:11', 'BS Information Technology', 'First Year', 2503261, 'BS Information Technology - First Year Section 1', 5, 'Miguelee C. Escover'),
(126, 115, '202511120', 'PAULA X. JAVIER', '2025-04-18 09:29:11', 'BS Information Technology', 'First Year', 2503261, 'BS Information Technology - First Year Section 1', 5, 'Miguelee C. Escover'),
(127, 116, NULL, 'BATA 1', '2025-04-18 09:29:52', NULL, NULL, NULL, NULL, NULL, NULL),
(128, 116, '202511117', 'SOFIA U. GABRIEL', '2025-04-18 09:29:52', 'BS Information Technology', 'First Year', 2503261, 'BS Information Technology - First Year Section 1', 5, 'Miguelee C. Escover');

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `course_year` varchar(100) NOT NULL,
  `reason_for_referral` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `violation_details` text DEFAULT NULL,
  `other_concerns` text DEFAULT NULL,
  `faculty_name` varchar(100) NOT NULL,
  `acknowledged_by` varchar(100) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `incident_report_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`id`, `date`, `first_name`, `middle_name`, `last_name`, `course_year`, `reason_for_referral`, `status`, `violation_details`, `other_concerns`, `faculty_name`, `acknowledged_by`, `student_id`, `incident_report_id`) VALUES
(53, '2025-01-03', 'BYRON', 'Q', 'PALOMERAS', 'BS Information Technology - Fourth Year', 'Violation to school rules', 'Done', 'yosi', '', 'Gladys G Perey', 'MR  Counsellor', '202107410', 'CEIT-24-25-0001'),
(54, '2025-01-03', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Information Technology - Fourth Year', 'Violation to school rules', 'Done', 'yosi', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0001'),
(55, '2025-01-03', 'NEIL POGI', '', '', 'Non-CEIT Student', 'Violation to school rules', 'Done', 'yosi', '', 'Gladys G Perey', 'MR  Counsellor', NULL, 'CEIT-24-25-0001'),
(56, '2025-01-03', 'HELLO', '', '', 'Non-CEIT Student', 'Violation to school rules', 'Done', 'yosi', '', 'Gladys G Perey', 'MR  Counsellor', NULL, 'CEIT-24-25-0001'),
(57, '2025-01-03', 'NEIL STUDENT', '', '', 'Non-CEIT Student', 'Violation to school rules', 'Done', 'yosi', '', 'Gladys G Perey', 'MR  Counsellor', NULL, 'CEIT-24-25-0001'),
(58, '2025-01-03', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Information Technology - Fourth Year', 'Behavior maladjustment', 'Done', '', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0002'),
(59, '2025-01-03', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Information Technology - Fourth Year', 'Behavior maladjustment', 'Done', '', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0003'),
(60, '2025-01-04', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Information Technology - Fourth Year', 'Other concern', 'Done', '', 'depress', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0004'),
(61, '2025-01-04', 'STUDENT1', '', '', 'Non-CEIT Student', 'Other concern', 'Done', '', 'depress', 'Gladys G Perey', 'MR  Counsellor', NULL, 'CEIT-24-25-0004'),
(64, '2025-01-04', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Information Technology - Fourth Year', 'Behavior maladjustment', 'Done', '', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0007'),
(65, '2025-01-04', 'NEIL POGI', '', '', 'Non-CEIT Student', 'Behavior maladjustment', 'Done', '', '', 'Gladys G Perey', 'MR  Counsellor', NULL, 'CEIT-24-25-0007'),
(66, '2025-01-04', 'STUDENT1', '', '', 'Non-CEIT Student', 'Behavior maladjustment', 'Done', '', '', 'Gladys G Perey', 'MR  Counsellor', NULL, 'CEIT-24-25-0007'),
(67, '2025-01-04', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Information Technology - Fourth Year', 'Academic concern', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0005'),
(68, '2025-01-04', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Information Technology - Fourth Year', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0006'),
(69, '2025-01-04', 'NEIL TRISTHAN N. MOJICA123', '', '', 'Non-CEIT Student', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', NULL, 'CEIT-24-25-0006'),
(70, '2025-02-21', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Information Technology - Irregular', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0080'),
(71, '2025-02-21', 'GENE ROBERT D. MANGUERA', '', '', 'Non-CEIT Student', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', NULL, 'CEIT-24-25-0080'),
(72, '2025-02-21', 'NEIL TRISTHAN N. MOJICA', '', '', 'Non-CEIT Student', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', NULL, 'CEIT-24-25-0080'),
(73, '2025-03-01', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Information Technology - Irregular', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0081'),
(74, '2025-03-01', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Information Technology - Irregular', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0084'),
(75, '2025-03-01', 'NEIL POGI (2)', '', '', 'Non-CEIT Student', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', NULL, 'CEIT-24-25-0084'),
(76, '2025-03-01', 'NEIL POGI', '', '', 'Non-CEIT Student', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', NULL, 'CEIT-24-25-0084'),
(77, '2025-03-01', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Computer Engineering - Second Year', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0087'),
(78, '2025-04-09', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Computer Engineering - Second Year', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0099'),
(79, '2025-04-19', 'ZAIRO', 'S', 'ARGAS', 'First Year BS Information Technology - 1', 'Academic concern', 'Done', '', '', 'Gladys G Perey', 'Gillian M Hernandez', '202511111', NULL),
(80, '2025-04-19', 'JHANNAH BERNADETTE', 'Q', 'ALBINO', 'Second Year BS Computer Engineering - 2', 'Academic concern', 'Done', '', '', 'Gladys G Perey', 'Gillian M Hernandez', '202105212', NULL),
(81, '2025-04-19', 'JHANNAH BERNADETTE', 'Q', 'ALBINO', 'Second Year BS Computer Engineering - 2', 'Other concern', 'Done', '', 'makulit ang yawaa, eme', 'Gladys G Perey', 'Gillian M Hernandez', '202105212', NULL),
(82, '2025-04-19', 'JHANNAH BERNADETTE', 'Q', 'ALBINO', 'BS Computer Engineering - Second Year', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'Gillian M Hernandez', '202105212', 'CEIT-24-25-0107'),
(83, '2025-04-19', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Computer Engineering - Second Year', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'Gillian M Hernandez', '202102690', 'CEIT-24-25-0107'),
(84, '2025-04-19', 'JHANNAH BERNADETTE', 'Q', 'ALBINO', 'BS Computer Engineering - Second Year', 'Violation to school rules', 'Pending', 'not wearing uniform', '', 'Gladys G Perey', 'Gillian M Hernandez', '202105212', 'CEIT-24-25-0109'),
(85, '2025-04-19', 'ZAIRO', 'S', 'ARGAS', 'First Year BS Information Technology - 1', 'Other concern', 'Pending', '', 'is this yours?', 'Gladys G Perey', 'Gillian M Hernandez', '202511111', NULL),
(86, '2025-04-19', 'ZAIRO', 'S', 'ARGAS', 'BS Information Technology - First Year', 'Other concern', 'Pending', '', 'bastos, eme', 'Gladys G Perey', 'Gillian M Hernandez', '202511111', 'CEIT-24-25-0119'),
(87, '2025-05-01', 'EMILYN', 'C', 'MOJICA', 'BS Computer Engineering - Second Year', 'Academic concern', 'Pending', '', '', 'Gladys G Perey', 'Gillian M Hernandez', '202103642', 'CEIT-24-25-0108'),
(88, '2025-05-01', 'JHON VHIC', 'C', 'BALLERA', 'BS Computer Engineering - Second Year', 'Violation to school rules', 'Pending', 'not wearing uniform', '', 'Gladys G Perey', 'Gillian M Hernandez', '202106149', 'CEIT-24-25-0109'),
(89, '2025-05-01', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Computer Engineering - Second Year', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'Gillian M Hernandez', '202102690', 'CEIT-24-25-0082'),
(90, '2025-05-01', 'NEILS', '', '', 'Non-CEIT Student', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'Gillian M Hernandez', NULL, 'CEIT-24-25-0082'),
(91, '2025-05-01', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Computer Engineering - Second Year', 'Academic concern', 'Pending', '', '', 'Gladys G Perey', 'Gillian M Hernandez', '202102690', 'CEIT-24-25-0121');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `course_id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `section_no` varchar(20) NOT NULL,
  `academic_year` varchar(15) NOT NULL,
  `adviser_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `department_id`, `department_name`, `course_id`, `course_name`, `year_level`, `section_no`, `academic_year`, `adviser_id`, `created_at`, `status`) VALUES
(2503261, 1, 'Department of Information Technology (DIT)', 1, 'BS Information Technology', 'First Year', '1', '2025 - 2026', 5, '2025-04-18 05:16:16', 'active'),
(2518203, 3, 'Department of Computer, Electronics, and Electrical Engineering (DCEEE)', 6, 'BS Computer Engineering', 'First Year', '2', '2025 - 2026', 1, '2025-02-20 04:39:05', 'disabled'),
(2540690, 1, 'Department of Information Technology (DIT)', 1, 'BS Information Technology', 'Irregular', '2', '2025 - 2026', 1, '2025-02-18 15:08:57', 'active'),
(2591209, 1, 'Department of Information Technology (DIT)', 1, 'BS Information Technology', 'Third Year', '2', '2025 - 2026', 1, '2025-02-18 15:08:37', 'disabled'),
(2592996, 5, 'Department of Agriculture and Food Engineering (DAFE)', 3, 'BS Agricultural and Biosystems Engineering', 'First Year', '2', '2026 - 2027', 1, '2025-02-15 05:17:01', 'disabled'),
(2592999, 1, 'Department of Information Technology (DIT)', 1, 'BS Information Technology', 'Second Year', '2', '2024 - 2025', 1, '2025-02-18 15:11:23', 'disabled'),
(2593000, 1, 'Department of Information Technology (DIT)', 1, 'BS Information Technology', 'First Year', '2', '2023 - 2024', 1, '2025-02-18 15:11:51', 'disabled'),
(2593001, 5, 'Department of Agriculture and Food Engineering (DAFE)', 3, 'BS Agricultural and Biosystems Engineering', 'Second Year', '2', '2027 - 2028', 1, '2025-02-18 15:12:22', 'disabled'),
(2593002, 1, 'Department of Information Technology (DIT)', 1, 'BS Information Technology', 'Fourth Year', '2', '2026 - 2027', 1, '2025-02-18 15:23:19', 'disabled'),
(2593003, 3, 'Department of Computer, Electronics, and Electrical Engineering (DCEEE)', 6, 'BS Computer Engineering', 'Second Year', '2', '2026 - 2027', 1, '2025-02-20 06:32:22', 'active');

--
-- Triggers `sections`
--
DELIMITER $$
CREATE TRIGGER `before_insert_section` BEFORE INSERT ON `sections` FOR EACH ROW BEGIN
    DECLARE dept_name VARCHAR(100);
    DECLARE crs_name VARCHAR(100);
    
    SELECT name INTO dept_name FROM departments WHERE id = NEW.department_id;
    SELECT name INTO crs_name FROM courses WHERE id = NEW.course_id;
    
    SET NEW.department_name = dept_name;
    SET NEW.course_name = crs_name;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_section` BEFORE UPDATE ON `sections` FOR EACH ROW BEGIN
    DECLARE dept_name VARCHAR(100);
    DECLARE crs_name VARCHAR(100);
    
    IF NEW.department_id != OLD.department_id THEN
        SELECT name INTO dept_name FROM departments WHERE id = NEW.department_id;
        SET NEW.department_name = dept_name;
    END IF;
    
    IF NEW.course_id != OLD.course_id THEN
        SELECT name INTO crs_name FROM courses WHERE id = NEW.course_id;
        SET NEW.course_name = crs_name;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `profile_id` varchar(20) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `current_address` text DEFAULT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `zipcode` int(5) NOT NULL,
  `houseno_street` int(10) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `birthplace` varchar(100) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `spouse_name` varchar(100) DEFAULT NULL,
  `spouse_occupation` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `semester_first_enrolled` varchar(50) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_contact` varchar(20) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` text DEFAULT NULL,
  `mother_contact` varchar(20) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `guardian_occupation` varchar(100) DEFAULT NULL,
  `siblings` int(11) DEFAULT NULL,
  `birth_order` varchar(100) DEFAULT NULL,
  `family_income` varchar(100) DEFAULT NULL,
  `elementary` text DEFAULT NULL,
  `secondary` varchar(100) DEFAULT NULL,
  `transferees` varchar(100) DEFAULT NULL,
  `course_factors` text DEFAULT NULL,
  `career_concerns` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `suicide_attempt` varchar(3) DEFAULT NULL,
  `suicide_reason` text DEFAULT NULL,
  `problems` text DEFAULT NULL,
  `family_problems` text NOT NULL,
  `fitness_activity` varchar(100) DEFAULT NULL,
  `fitness_frequency` varchar(20) DEFAULT NULL,
  `stress_level` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `signature_path` varchar(100) NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_violations`
--

CREATE TABLE `student_violations` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `incident_report_id` varchar(20) NOT NULL,
  `violation_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `student_name` varchar(100) NOT NULL,
  `student_course` varchar(100) DEFAULT NULL,
  `student_year_level` varchar(20) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `section_id` int(11) DEFAULT NULL,
  `section_name` varchar(255) DEFAULT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `adviser_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_violations`
--

INSERT INTO `student_violations` (`id`, `student_id`, `incident_report_id`, `violation_date`, `status`, `student_name`, `student_course`, `student_year_level`, `is_archived`, `section_id`, `section_name`, `adviser_id`, `adviser_name`) VALUES
(442, '202102690', 'CEIT-24-25-0001', '2025-05-12 10:32:46', 'Settled', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(443, '202105212', 'CEIT-24-25-0001', '2025-05-12 10:32:46', 'Settled', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(444, '202106149', 'CEIT-24-25-0001', '2025-05-12 10:32:46', 'Settled', 'JHON VHIC BALLERA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(445, '202102690', 'CEIT-24-25-0002', '2025-05-12 17:08:06', 'Settled', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez');

--
-- Triggers `student_violations`
--
DELIMITER $$
CREATE TRIGGER `before_student_violation_insert` BEFORE INSERT ON `student_violations` FOR EACH ROW BEGIN
                -- Only set certain fields if they're empty and student_id exists
                IF NEW.student_id IS NOT NULL AND (NEW.student_name IS NULL OR NEW.student_name = '') THEN
                    SELECT CONCAT(ts.first_name, ' ', ts.last_name)
                    INTO @student_fullname
                    FROM tbl_student ts
                    WHERE ts.student_id = NEW.student_id;
                    
                    SET NEW.student_name = @student_fullname;
                END IF;
            END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_admin`
--

CREATE TABLE `tbl_admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_admin`
--

INSERT INTO `tbl_admin` (`id`, `username`, `password`, `email`, `first_name`, `middle_initial`, `last_name`, `profile_picture`, `created_at`, `updated_at`, `reset_token`, `reset_token_expires`) VALUES
(4, 'CEIT_admin', '$2y$10$ffU4DxFkamYbp6Xw.vSlS.3TUmkclUG28rgJcqDWIDbrzDmMyoqB2', 'ceitguidanceoffice@gmail.com', 'CEIT', '', 'Admin', 'admin_profiles/66dfe5c827e3a.jpg', '2024-09-09 21:37:48', '2025-04-09 13:34:14', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_adviser`
--

CREATE TABLE `tbl_adviser` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_adviser`
--

INSERT INTO `tbl_adviser` (`id`, `username`, `password`, `email`, `first_name`, `middle_initial`, `last_name`, `profile_picture`, `created_at`, `updated_at`, `reset_token`, `reset_token_expires`, `status`) VALUES
(1, 'adviser1', '$2y$10$oVguZM/a1yi.sgJAhIkGfeb2VXQCDZzp8RTs8lY3TXzQqLV4DcZym', 'adviser1@cvsu.edu.ph', 'Simeons', 'N', 'Daez', 'adviser_profiles/66a9a6f6a1524.jpg', '2024-07-19 18:01:28', '2025-05-01 13:23:49', NULL, NULL, 'active'),
(2, 'adviser2', '$2y$10$Iown7rmnbTsja5eNsjnNo.1tjtKG38ymg4ktPTTUYqrchzYLszphu', 'adviser2@cvsu.edu.ph', NULL, NULL, NULL, 'adviser_profiles/66b1dbea19489.png', '2024-07-19 18:01:28', '2024-09-09 22:47:37', NULL, NULL, 'active'),
(5, 'Simeon2024', '$2y$10$12SimMUxvgkLerJL6ip2oOzqmSTcSZIZz4IRsFx28DupFBtr.rOvC', 'miguelescover.cvsu@gmail.com', 'Miguelee', 'C', 'Escover', NULL, '2024-10-01 14:16:25', '2025-04-18 13:15:52', NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_adviser_tables`
--

CREATE TABLE `tbl_adviser_tables` (
  `table_id` int(11) NOT NULL,
  `table_name` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_counselor`
--

CREATE TABLE `tbl_counselor` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_counselor`
--

INSERT INTO `tbl_counselor` (`id`, `username`, `password`, `email`, `first_name`, `middle_initial`, `last_name`, `profile_picture`, `created_at`, `updated_at`, `reset_token`, `reset_token_expires`, `status`) VALUES
(7, 'counselor1', '$2y$10$I2UdQpfoRogIlFoK.4iRy.ahtfgaGmfdnojkf6q0lLcEuChVTEyRG', 'counselor@gmail.com', 'Gillian', 'M', 'Hernandez', NULL, '2024-09-09 13:45:07', '2025-04-18 14:51:25', NULL, NULL, 'active'),
(8, 'adviser1', '$2y$10$8L9LgVmzdZZLbhLh0aCYU.njP4zhU974oulziP1lM.T4xMCOVD4oW', 'gwen@cvsu.edu.ph', 'GWYNETH KYLAS', 'N', 'MOJICA', NULL, '2025-04-09 05:16:59', '2025-04-26 07:00:09', NULL, NULL, 'disabled');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_dean`
--

CREATE TABLE `tbl_dean` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_dean`
--

INSERT INTO `tbl_dean` (`id`, `username`, `password`, `email`, `first_name`, `middle_initial`, `last_name`, `profile_picture`, `created_at`, `updated_at`, `reset_token`, `reset_token_expires`, `status`) VALUES
(1, 'dean1', '$2y$10$wjtiVPvV3v6YyEDnXYvmKeNBdTNkEyqaOYJAjrp/kuKA3jVZVEVWK', 'dean1@cvsu.edu.ph', 'William', 'B', 'Buenavidez', 'path/to/profile1.jpg', '2024-07-21 10:40:40', '2025-04-18 09:23:49', NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_facilitator`
--

CREATE TABLE `tbl_facilitator` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_facilitator`
--

INSERT INTO `tbl_facilitator` (`id`, `username`, `password`, `email`, `first_name`, `middle_initial`, `last_name`, `profile_picture`, `created_at`, `updated_at`, `reset_token`, `reset_token_expires`, `status`) VALUES
(1, 'facilitator1', '$2y$10$oVguZM/a1yi.sgJAhIkGfeb2VXQCDZzp8RTs8lY3TXzQqLV4DcZym', 'facilitator1@cvsu.edu.ph', 'Gladys', 'G', 'Perey', 'path/to/profile1.jpg', '2024-07-21 10:40:55', '2024-12-19 00:09:42', NULL, NULL, 'active'),
(2, 'facilitator2', '$2y$10$iQIhHusJnQdCcObv.Tb0gOGbuBz6VYUIhgFK5kLgGYMAMqLsaXO9e', 'facilitator2@cvsu.edu.ph', 'Andy', 'D', 'Dizon', NULL, '2024-09-09 15:07:12', '2025-04-18 05:06:47', NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_guard`
--

CREATE TABLE `tbl_guard` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_guard`
--

INSERT INTO `tbl_guard` (`id`, `username`, `password`, `email`, `first_name`, `middle_initial`, `last_name`, `profile_picture`, `created_at`, `updated_at`, `reset_token`, `reset_token_expires`, `status`) VALUES
(1, 'guard1', '$2y$10$Snjkf3JpaooLNQdndXijy.RUN5TkWxQSxulc.plQLRWcaVM0dZxPa', 'nehepi2158@cpaurl.com', NULL, NULL, NULL, 'path/to/profile1.jpg', '2024-07-21 10:41:17', '2025-04-09 05:34:28', NULL, NULL, 'active'),
(2, 'guard2', '$2y$10$e2klGIFZ9s/qm0WQAxnWWO2q3vOvqNXKIiN8F30E11zeWxSR9sp..', 'guard@cvsu.edu.ph', 'MR', '', 'GUARD', '', '2024-07-30 13:31:23', '2025-03-08 07:14:04', NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_instructor`
--

CREATE TABLE `tbl_instructor` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_instructor`
--

INSERT INTO `tbl_instructor` (`id`, `username`, `password`, `email`, `first_name`, `middle_initial`, `last_name`, `profile_picture`, `created_at`, `updated_at`, `reset_token`, `reset_token_expires`, `status`) VALUES
(1, 'hello', '$2y$10$j/9KuTHH.B0vxYFnIjhuVOS6rmJyJNm5VG7IzIh1RhkowBY6f4lT.', 'instructor1@cvsu.edu.ph', 'Neil Tristhan', 'N', 'Mojica', 'path/to/profile1.jpg', '2024-07-21 18:41:31', '2025-03-01 21:30:18', NULL, NULL, 'active'),
(2, 'instructor2', '$2y$10$OqJU3hrJWuNQq6FvJOCd7eG0pzbInaJTWCqmml1h6S7l6DkRHTqwG', 'instructor2@cvsu.com', 'MR', 'D', 'INSTRUCTOR', NULL, '2024-08-29 15:02:18', '2025-04-17 18:00:50', NULL, NULL, 'active'),
(3, 'instructor3', '$2y$10$9IEQUVi8zbs83zkURRgN5.O69w1vaGMyeBix883BN/xSqxtqM3R2G', 'instructor3@gmail.com', NULL, NULL, NULL, NULL, '2024-09-09 23:12:11', '2024-10-08 12:38:33', NULL, NULL, 'active'),
(4, 'gerami', '$2y$10$jsW6.aYtLrmbcs7SF4qDpue0CzDdi1bXaC59jyGFY8Ad.XLik.UHi', 'gerami@cvsu.edu.ph', NULL, NULL, NULL, NULL, '2024-12-11 14:17:31', '2024-12-19 07:24:28', NULL, NULL, 'active'),
(6, 'Jayson', '$2y$10$HYNhCE/beWgw/Ks8nC.hjOX3h.Qv5VDyk3QzUUIbLrcLyoEYhjDqG', 'jayson@gmail.com', 'Jayson', 'M', 'Cabanglan', NULL, '2024-12-11 22:14:27', '2024-12-19 07:01:03', NULL, NULL, 'disabled'),
(7, 'sample123', '$2y$10$ZFhbHOVvyeKJAzG4PSFZEuVanSHsxomoLsoSkABlsfq/8ThGtsqd2', 'sample@gmail.com', 'Sample', 'B', 'Size', NULL, '2024-12-19 07:25:50', '2024-12-19 08:02:40', NULL, NULL, 'disabled'),
(9, 'Instructor6', '$2y$10$A4Fn1ZhOPxUyUeilH.hueeOt5x3nPpE3AXhtsCOm4TSHGHe3UQ4nO', 'hello1235@cvsu.edu.ph', 'Cyndell', 'N', 'Dadula', NULL, '2025-04-09 12:45:45', '2025-04-09 12:45:45', NULL, NULL, 'active'),
(10, 'denzel123', '$2y$10$tShydzImg.cZbYNFEfaZjuPHW1L8gFhqgNR1rQ2PSUcIeLoAMCVhK', 'denzelanthony.barbacena@cvsu.edu.ph', 'Denzel Anthonny', 'B', 'Barbacena', NULL, '2025-04-26 15:02:01', '2025-04-26 15:02:01', NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_student`
--

CREATE TABLE `tbl_student` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `section_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_student`
--

INSERT INTO `tbl_student` (`id`, `student_id`, `password`, `email`, `profile_picture`, `created_at`, `updated_at`, `section_id`, `first_name`, `middle_name`, `last_name`, `gender`, `reset_token`, `reset_token_expires`, `status`) VALUES
(1100, '202102690', '$2y$10$6KZLpsETqLR4sYjmJ18MieOs1KCwhdhGslaNjt0C12PK5tAOj3XuO', 'neiltristhan.mojica@cvsu.edu.ph', NULL, '2025-02-15 14:42:39', '2025-04-26 14:56:40', 2593003, 'NEIL TRISTHAN', 'N', 'MOJICA', 'MALE', '194f9f926e3b9e38cac19a446fcf706b97800ce16260c662a02a1bfc157ba9c8c3ffae2b16d683a71f6a6fa57cf08c6c061b', '2025-04-26 15:56:40', 'active'),
(1102, '202105212', '$2y$10$4dcE.9r8E23xdAtIuvWLuOIwdLGIDk9p6j2VPYmDroeYGOnE5ARBa', 'jana@cvsu.edu.ph', NULL, '2025-02-18 23:10:15', '2025-04-17 21:27:22', 2593003, 'JHANNAH BERNADETTE', 'Q', 'ALBINO', 'FEMALE', NULL, NULL, 'active'),
(1103, '202106149', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'JHON VHIC', 'C', 'BALLERA', 'MALE', NULL, NULL, 'active'),
(1104, '202105791', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'JUDE', 'F', 'BAUTISTA', 'MALE', NULL, NULL, 'active'),
(1105, '202106746', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'EURICA MAE', 'D', 'BORCE', 'FEMALE', NULL, NULL, 'active'),
(1106, '202011451', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'KAYRON MARK', 'J', 'BURZON', 'MALE', NULL, NULL, 'active'),
(1107, '202102908', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'AIRA LIZETTE', 'B', 'CABRAL', 'FEMALE', NULL, NULL, 'active'),
(1108, '202011511', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'CARIAH KIRSTINE', 'B', 'CATANGUI', 'FEMALE', NULL, NULL, 'active'),
(1109, '202106101', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'GWYNETH KYLA', 'C', 'CIRIACO', 'FEMALE', NULL, NULL, 'active'),
(1110, '202105470', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'JAMES EZEKIEL', 'L', 'DAQUIS', 'MALE', NULL, NULL, 'active'),
(1111, '202106114', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'ANGEL MARIE', 'N', 'DEDASE', 'FEMALE', NULL, NULL, 'active'),
(1112, '202106047', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'JULIUS RUIZ', 'M', 'DILLERA', 'MALE', NULL, NULL, 'active'),
(1113, '202105723', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'SHAWN RAVEN', 'L', 'FERRER', 'MALE', NULL, NULL, 'active'),
(1114, '202106240', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'ARRIANE FAITH', 'A', 'HEMBRADOR', 'FEMALE', NULL, NULL, 'active'),
(1115, '202106259', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'LORENZO DANIEL', 'A', 'JARATA', 'MALE', NULL, NULL, 'active'),
(1116, '202104158', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'MICHAEL', 'E', 'LAGATIC', 'MALE', NULL, NULL, 'active'),
(1117, '202105204', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'KARL CHESTER', 'C', 'LODANA', 'MALE', NULL, NULL, 'active'),
(1118, '202102768', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'JOHN PATRICK', 'B', 'LUCAÃAS', 'MALE', NULL, NULL, 'active'),
(1119, '202102881', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'MARK KEN FELIX', 'P', 'MADRID', 'MALE', NULL, NULL, 'active'),
(1120, '202102884', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'JOHN JAYSON', 'T', 'MAGBOO', 'MALE', NULL, NULL, 'active'),
(1121, '202011997', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'KRISTIAN CALEB', 'A', 'MAGULING', 'MALE', NULL, NULL, 'active'),
(1122, '202108636', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'GENE ROBERT', 'D', 'MANGUERA', 'MALE', NULL, NULL, 'active'),
(1123, '202013770', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'MARISSA', 'S', 'MANUBAY', 'FEMALE', NULL, NULL, 'active'),
(1124, '202106470', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'CLARK FERNANDO POE', 'G', 'MIRA', 'MALE', NULL, NULL, 'active'),
(1125, '202103642', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'EMILYN', 'C', 'MOJICA', 'FEMALE', NULL, NULL, 'active'),
(1126, '202106258', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'CRISTINE MAY', 'C', 'MONCHEZ', 'FEMALE', NULL, NULL, 'active'),
(1127, '202105592', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'JAISSEN YVES', 'S', 'NAZARENO', 'MALE', NULL, NULL, 'active'),
(1128, '202014202', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'ANGEL', 'M', 'OCMEN', 'FEMALE', NULL, NULL, 'active'),
(1129, '202106271', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'MILLARD JOHN', 'C', 'ORTILLANO', 'MALE', NULL, NULL, 'active'),
(1130, '202104364', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'PRINCESS NICOLE', 'A', 'PADILLA', 'FEMALE', NULL, NULL, 'active'),
(1131, '202107410', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'BYRON', 'Q', 'PALOMERAS', 'MALE', NULL, NULL, 'active'),
(1132, '202013012', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'JOLLO', 'R', 'PANALIGAN', 'MALE', NULL, NULL, 'active'),
(1133, '202011307', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'BAMBY', 'B', 'REQUILLO', 'FEMALE', NULL, NULL, 'active'),
(1134, '202015172', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'XANDER LEE', 'A', 'SARITA', 'MALE', NULL, NULL, 'active'),
(1135, '202106707', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'DARLENE', 'R', 'SOLTES', 'FEMALE', NULL, NULL, 'active'),
(1136, '202106281', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'JOHN DAVE', 'C', 'SUYAT', 'MALE', NULL, NULL, 'active'),
(1137, '202014937', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'MARK CHRISTIAN', 'D', 'TABUZO', 'MALE', NULL, NULL, 'active'),
(1138, '202105206', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'JOHNBERT CHRISTIENE', 'D', 'TAGLE', 'MALE', NULL, NULL, 'active'),
(1139, '202105700', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'IAN GABRIELLE', 'B', 'TEODORO', 'MALE', NULL, NULL, 'active'),
(1142, '202511111', NULL, NULL, NULL, '2025-04-18 13:24:40', '2025-04-18 13:24:40', 2503261, 'ZAIRO', 'S', 'ARGAS', 'MALE', NULL, NULL, 'active'),
(1143, '202511112', NULL, NULL, NULL, '2025-04-18 13:24:40', '2025-04-18 13:24:40', 2503261, 'LUCIA M', '', 'BERNARDO', 'MALE', NULL, NULL, 'active'),
(1144, '202511113', NULL, NULL, NULL, '2025-04-18 13:24:40', '2025-04-18 13:24:40', 2503261, 'MARIA', 'J', 'CARLOS', 'FEMALE', NULL, NULL, 'active'),
(1145, '202511114', NULL, NULL, NULL, '2025-04-18 13:24:40', '2025-04-18 13:24:40', 2503261, 'ANA', 'P', 'DAVID', 'FEMALE', NULL, NULL, 'active'),
(1146, '202511115', NULL, NULL, NULL, '2025-04-18 13:24:40', '2025-04-18 13:24:40', 2503261, 'ELENA', 'R', 'EDUARDO', 'MALE', NULL, NULL, 'active'),
(1147, '202511116', NULL, NULL, NULL, '2025-04-18 13:24:40', '2025-04-18 13:24:40', 2503261, 'ISABEL', 'T', 'FELIPE', 'MALE', NULL, NULL, 'active'),
(1148, '202511117', NULL, NULL, NULL, '2025-04-18 13:24:40', '2025-04-18 13:24:40', 2503261, 'SOFIA', 'U', 'GABRIEL', 'FEMALE', NULL, NULL, 'active'),
(1149, '202511118', NULL, NULL, NULL, '2025-04-18 13:24:40', '2025-04-18 13:24:40', 2503261, 'CAMILA', 'V', 'HUGO', 'FEMALE', NULL, NULL, 'active'),
(1150, '202511119', NULL, NULL, NULL, '2025-04-18 13:24:40', '2025-04-18 13:24:40', 2503261, 'LAURA', 'W', 'IGNACIO', 'MALE', NULL, NULL, 'active'),
(1151, '202511120', NULL, NULL, NULL, '2025-04-18 13:24:40', '2025-04-18 13:24:40', 2503261, 'PAULA', 'X', 'JAVIER', 'FEMALE', NULL, NULL, 'active'),
(1153, '202014188', NULL, NULL, NULL, '2025-04-26 08:49:37', '2025-04-26 08:49:37', 2503261, 'NOVIE GRACE', 'Y', 'OBEAL', 'FEMALE', NULL, NULL, 'active'),
(1154, '202114909', NULL, NULL, NULL, '2025-04-26 09:00:33', '2025-04-26 09:00:33', 2503261, 'SARAH MAE', 'V', 'ERNI', 'FEMALE', NULL, NULL, 'active'),
(1156, '202105706', NULL, NULL, NULL, '2025-04-26 13:59:55', '2025-04-26 13:59:55', 2503261, 'MIGUEL JUAN', 'C', 'ESCOVER', 'MALE', NULL, NULL, 'active'),
(1157, '202014909', NULL, NULL, NULL, '2025-04-26 14:00:31', '2025-04-26 14:00:31', 2503261, 'SARAH MAE', 'V', 'ERNI', 'FEMALE', NULL, NULL, 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `archive_incident_reports`
--
ALTER TABLE `archive_incident_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archive_incident_witnesses`
--
ALTER TABLE `archive_incident_witnesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_witnesses_ibfk_1` (`incident_report_id`),
  ADD KEY `incident_witnesses_ibfk_2` (`witness_id`);

--
-- Indexes for table `archive_student_profiles`
--
ALTER TABLE `archive_student_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `archive_student_violations`
--
ALTER TABLE `archive_student_violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_violations_ibfk_2` (`incident_report_id`),
  ADD KEY `student_violations_ibfk_1` (`student_id`);

--
-- Indexes for table `backup_incident_reports`
--
ALTER TABLE `backup_incident_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `backup_incident_witnesses`
--
ALTER TABLE `backup_incident_witnesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_witnesses_ibfk_1` (`incident_report_id`),
  ADD KEY `incident_witnesses_ibfk_2` (`witness_id`);

--
-- Indexes for table `backup_student_violations`
--
ALTER TABLE `backup_student_violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_violations_ibfk_2` (`incident_report_id`),
  ADD KEY `student_violations_ibfk_1` (`student_id`);

--
-- Indexes for table `cavite_barangays`
--
ALTER TABLE `cavite_barangays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `cavite_cities`
--
ALTER TABLE `cavite_cities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `counselor_meetings`
--
ALTER TABLE `counselor_meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `referral_id` (`referral_id`),
  ADD KEY `incident_report_id` (`incident_report_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `incident_witnesses`
--
ALTER TABLE `incident_witnesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_witnesses_ibfk_1` (`incident_report_id`),
  ADD KEY `incident_witnesses_ibfk_2` (`witness_id`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_report_id` (`incident_report_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_type_user_id_index` (`user_type`,`user_id`);

--
-- Indexes for table `pending_incident_reports`
--
ALTER TABLE `pending_incident_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guard_id` (`guard_id`);

--
-- Indexes for table `pending_incident_witnesses`
--
ALTER TABLE `pending_incident_witnesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pending_report_id` (`pending_report_id`),
  ADD KEY `witness_id` (`witness_id`);

--
-- Indexes for table `pending_student_violations`
--
ALTER TABLE `pending_student_violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pending_report_id` (`pending_report_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_referral_status` (`status`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_incident_student` (`incident_report_id`,`student_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_section` (`department_id`,`course_id`,`year_level`,`section_no`,`academic_year`),
  ADD KEY `fk_sections_course` (`course_id`),
  ADD KEY `fk_sections_adviser` (`adviser_id`),
  ADD KEY `idx_section_search` (`department_id`,`course_id`,`year_level`,`section_no`,`status`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `student_violations`
--
ALTER TABLE `student_violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_violations_ibfk_2` (`incident_report_id`),
  ADD KEY `student_violations_ibfk_1` (`student_id`);

--
-- Indexes for table `tbl_admin`
--
ALTER TABLE `tbl_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tbl_adviser`
--
ALTER TABLE `tbl_adviser`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tbl_adviser_tables`
--
ALTER TABLE `tbl_adviser_tables`
  ADD PRIMARY KEY (`table_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `tbl_counselor`
--
ALTER TABLE `tbl_counselor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tbl_dean`
--
ALTER TABLE `tbl_dean`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tbl_facilitator`
--
ALTER TABLE `tbl_facilitator`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tbl_guard`
--
ALTER TABLE `tbl_guard`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tbl_instructor`
--
ALTER TABLE `tbl_instructor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tbl_student`
--
ALTER TABLE `tbl_student`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_section` (`student_id`,`section_id`),
  ADD UNIQUE KEY `unique_active_email` (`email`,`status`),
  ADD UNIQUE KEY `idx_unique_active_student` (`student_id`,`status`,`section_id`),
  ADD KEY `fk_section` (`section_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `archive_incident_witnesses`
--
ALTER TABLE `archive_incident_witnesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=346;

--
-- AUTO_INCREMENT for table `archive_student_violations`
--
ALTER TABLE `archive_student_violations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=442;

--
-- AUTO_INCREMENT for table `backup_incident_witnesses`
--
ALTER TABLE `backup_incident_witnesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=682166;

--
-- AUTO_INCREMENT for table `backup_student_violations`
--
ALTER TABLE `backup_student_violations`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=442;

--
-- AUTO_INCREMENT for table `cavite_barangays`
--
ALTER TABLE `cavite_barangays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=955;

--
-- AUTO_INCREMENT for table `cavite_cities`
--
ALTER TABLE `cavite_cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `counselor_meetings`
--
ALTER TABLE `counselor_meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `incident_witnesses`
--
ALTER TABLE `incident_witnesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=348;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=709;

--
-- AUTO_INCREMENT for table `pending_incident_reports`
--
ALTER TABLE `pending_incident_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `pending_incident_witnesses`
--
ALTER TABLE `pending_incident_witnesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `pending_student_violations`
--
ALTER TABLE `pending_student_violations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2593006;

--
-- AUTO_INCREMENT for table `student_violations`
--
ALTER TABLE `student_violations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=446;

--
-- AUTO_INCREMENT for table `tbl_admin`
--
ALTER TABLE `tbl_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_adviser`
--
ALTER TABLE `tbl_adviser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_adviser_tables`
--
ALTER TABLE `tbl_adviser_tables`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tbl_counselor`
--
ALTER TABLE `tbl_counselor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_dean`
--
ALTER TABLE `tbl_dean`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_facilitator`
--
ALTER TABLE `tbl_facilitator`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_guard`
--
ALTER TABLE `tbl_guard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_instructor`
--
ALTER TABLE `tbl_instructor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_student`
--
ALTER TABLE `tbl_student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1158;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cavite_barangays`
--
ALTER TABLE `cavite_barangays`
  ADD CONSTRAINT `cavite_barangays_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cavite_cities` (`id`);

--
-- Constraints for table `counselor_meetings`
--
ALTER TABLE `counselor_meetings`
  ADD CONSTRAINT `counselor_meetings_ibfk_1` FOREIGN KEY (`referral_id`) REFERENCES `referrals` (`id`),
  ADD CONSTRAINT `counselor_meetings_ibfk_2` FOREIGN KEY (`incident_report_id`) REFERENCES `incident_reports` (`id`);

--
-- Constraints for table `incident_witnesses`
--
ALTER TABLE `incident_witnesses`
  ADD CONSTRAINT `incident_witnesses_ibfk_1` FOREIGN KEY (`incident_report_id`) REFERENCES `incident_reports` (`id`),
  ADD CONSTRAINT `incident_witnesses_ibfk_2` FOREIGN KEY (`witness_id`) REFERENCES `tbl_student` (`student_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
