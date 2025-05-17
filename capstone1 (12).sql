-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2025 at 11:23 AM
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
('REQ_6759a61019e567.98026090', 202011307, 'BAMBY', 'REQUILLO', '202011307', 'FEMALE', 'Department of Information Technology (DIT)', 'BS Information Technology', 'Good Moral', 'Employment', 'School ID', 'bamby@cvsu.edu.ph', '2024-12-11 00:00:00', 'Pending', 0);

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
('CEIT-24-25-0001', '2025-01-03 16:53:04', 'salyusoy - January 3, 2025 at 4:51 PM', 'nnn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-03 08:53:04', 'Referred', '2025-01-03 16:53:20', 1, 'Pending', NULL, 0),
('CEIT-24-25-0002', '2025-01-03 17:33:41', 'neil - January 3, 2025 at 5:01 PM', 'neih', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-03 09:33:41', 'Referred', '2025-01-03 17:33:58', 1, 'Pending', NULL, 0),
('CEIT-24-25-0003', '2025-01-03 17:35:22', 'salyusoy - January 3, 2025 at 5:35 PM', 'n', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-03 09:35:22', 'Referred', '2025-01-03 17:37:32', 1, 'Pending', NULL, 0),
('CEIT-24-25-0004', '2025-01-03 17:36:13', 'saluysoy - January 3, 2025 at 5:35 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-03 09:36:13', 'Referred', '2025-01-03 17:37:24', 1, 'Pending', NULL, 0),
('CEIT-24-25-0005', '2025-01-03 17:36:43', 'Saluysoy - January 3, 2025 at 5:36 PM', 'neu', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-03 09:36:43', 'Referred', '2025-01-03 17:37:06', 1, 'Pending', NULL, 0),
('CEIT-24-25-0006', '2025-01-04 10:06:30', 'saluysoy - January 4, 2025 at 10:06 AM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-04 02:06:30', 'Referred', '2025-01-04 18:11:31', 1, 'Pending', NULL, 0),
('CEIT-24-25-0007', '2025-01-04 13:55:27', 'salyusoy - January 4, 2025 at 1:54 PM', 'hello', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-04 05:55:27', 'Referred', '2025-01-04 13:56:37', 1, 'Pending', NULL, 0),
('CEIT-24-25-0008', '2025-01-05 12:11:28', 'neil - January 5, 2025 at 12:10 PM', 'hello', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-05 04:11:28', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0009', '2025-01-31 13:25:06', 'neil - January 31, 2025 at 1:23 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 05:25:06', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0010', '2025-01-31 13:26:26', 'neil - January 31, 2025 at 1:23 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 05:26:26', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0011', '2025-01-31 13:27:12', 'neil - January 31, 2025 at 1:23 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 05:27:12', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0012', '2025-01-31 13:29:27', 'neil - January 31, 2025 at 1:23 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 05:29:27', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0013', '2025-01-31 13:29:57', 'neil - January 31, 2025 at 1:29 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 05:29:57', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0014', '2025-01-31 13:30:43', 'neil - January 31, 2025 at 1:30 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 05:30:43', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0015', '2025-01-31 13:31:05', 'neil - January 31, 2025 at 1:30 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 05:31:05', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0016', '2025-01-31 13:34:41', 'neil - January 31, 2025 at 1:30 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 05:34:41', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0017', '2025-01-31 13:47:40', 'neil - January 31, 2025 at 1:47 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 05:47:40', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0018', '2025-01-31 13:49:09', 'neil - January 31, 2025 at 1:47 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 05:49:09', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0019', '2025-01-31 13:51:56', 'neil - January 31, 2025 at 1:47 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 05:51:56', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0020', '2025-01-31 14:00:37', 'neil - January 31, 2025 at 1:47 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 06:00:37', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0021', '2025-01-31 14:14:23', 'neil - January 31, 2025 at 2:08 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 06:14:23', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0022', '2025-01-31 14:16:49', 'neil - January 31, 2025 at 2:08 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 06:16:49', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0023', '2025-01-31 14:18:17', 'neil - January 31, 2025 at 2:08 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 06:18:17', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0024', '2025-01-31 14:24:20', 'neil - January 31, 2025 at 2:08 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 06:24:20', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0025', '2025-01-31 14:27:01', 'neil - January 31, 2025 at 2:26 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 06:27:01', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0026', '2025-01-31 14:30:10', 'neil - January 31, 2025 at 2:30 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 06:30:10', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0027', '2025-01-31 14:44:21', 'neil - January 31, 2025 at 2:43 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 06:44:21', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0028', '2025-01-31 15:00:34', 'salyusoy - January 31, 2025 at 2:52 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 07:00:34', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0029', '2025-01-31 15:01:30', 'salyusoy - January 31, 2025 at 2:52 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 07:01:30', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0030', '2025-01-31 15:07:53', 'salyusoy - January 31, 2025 at 2:52 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 07:07:53', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0031', '2025-01-31 15:21:50', 'salyusoy - January 31, 2025 at 2:52 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 07:21:50', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0032', '2025-01-31 15:27:25', 'salyusoy - January 31, 2025 at 2:52 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 07:27:25', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0033', '2025-01-31 15:28:51', 'neil - January 31, 2025 at 3:27 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 07:28:51', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0034', '2025-01-31 16:05:43', 'neil - January 31, 2025 at 3:27 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 08:05:43', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0035', '2025-01-31 16:05:57', 'neil - January 31, 2025 at 3:27 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 08:05:57', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0036', '2025-01-31 16:28:48', 'neil - January 31, 2025 at 3:27 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 08:28:48', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0037', '2025-01-31 16:31:38', 'neil - January 31, 2025 at 3:27 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 08:31:38', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0038', '2025-01-31 16:32:10', 'neil - January 31, 2025 at 3:27 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 08:32:10', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0039', '2025-01-31 16:36:12', 'neil - January 31, 2025 at 4:35 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 08:36:12', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0040', '2025-01-31 16:49:01', 'neil - January 31, 2025 at 4:46 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 08:49:01', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0041', '2025-01-31 16:50:04', 'neil - January 31, 2025 at 4:46 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 08:50:04', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0042', '2025-01-31 16:50:29', 'neil - January 31, 2025 at 4:46 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 08:50:29', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0043', '2025-01-31 17:01:04', 'neil - January 31, 2025 at 4:46 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 09:01:04', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0044', '2025-01-31 17:04:12', 'neil - January 31, 2025 at 4:46 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 09:04:12', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0045', '2025-01-31 17:16:01', 'neil - January 31, 2025 at 4:46 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 09:16:01', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0046', '2025-01-31 17:21:14', 'neil - January 31, 2025 at 4:46 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 09:21:14', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0047', '2025-01-31 17:40:39', 'neil - January 31, 2025 at 4:46 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 09:40:39', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0048', '2025-01-31 17:41:26', 'neil - January 31, 2025 at 5:40 PM', 'nn', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-01-31 09:41:26', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0049', '2025-01-31 17:44:41', 'neil - January 31, 2025 at 5:44 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-01-31 09:44:41', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0050', '2025-02-15 20:01:02', 'neil - February 15, 2025 at 8:01 PM', 'nya', 'NEIL TRISTHAN N. MOJICA', 202102690, 'student', NULL, '2025-02-15 12:01:39', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0051', '2025-02-15 20:03:52', 'Gate 1 - February 15, 2025 at 8:03 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:03:52', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0052', '2025-02-15 20:07:35', 'Gate 1 - February 15, 2025 at 8:03 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:07:35', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0053', '2025-02-15 20:09:21', 'Gate 1 - February 15, 2025 at 8:03 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:09:21', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0054', '2025-02-15 20:09:55', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:09:55', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0055', '2025-02-15 20:12:08', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:12:08', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0056', '2025-02-15 20:19:15', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', ' ', 1, 'adviser', NULL, '2025-02-15 12:19:15', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0057', '2025-02-15 20:27:58', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:27:58', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0058', '2025-02-15 20:28:26', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:28:26', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0059', '2025-02-15 20:30:58', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:30:58', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0060', '2025-02-15 20:33:59', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:33:59', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0061', '2025-02-15 20:35:47', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:35:47', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0062', '2025-02-15 20:37:44', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:37:44', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0063', '2025-02-15 20:37:49', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:37:49', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0064', '2025-02-15 20:40:19', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:40:19', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0065', '2025-02-15 20:42:49', 'Gate 1 - February 15, 2025 at 8:09 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:42:49', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0066', '2025-02-15 20:43:55', 'gg - February 15, 2025 at 8:43 PM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 12:43:55', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0067', '2025-02-15 21:28:24', 'neil - February 15, 2025 at 9:28 PM', 'neiii', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 13:28:24', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0068', '2025-02-15 21:29:39', 'neil - February 15, 2025 at 9:28 PM', 'neiii', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-15 13:29:39', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0069', '2025-02-16 10:44:01', 'neil - February 16, 2025 at 10:27 AM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-16 02:44:01', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0070', '2025-02-16 11:02:56', 'neil - February 16, 2025 at 11:02 AM', 'nn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-16 03:02:56', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0071', '2025-02-16 17:02:38', 'neil - February 16, 2025 at 11:11 AM', 'helloss', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-16 09:02:38', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0072', '2025-02-18 12:26:39', 'neil - February 18, 2025 at 12:26 PM', 'nnnnn', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-18 04:26:39', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0073', '2025-02-18 12:33:56', 'salyusoy - February 18, 2025 at 12:33 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-18 04:33:56', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0074', '2025-02-20 13:23:41', 'neil - February 20, 2025 at 1:23 PM', 'wala\r\n', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-02-20 05:23:41', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0075', '2025-02-20 13:27:33', 'Gate 1 - February 20, 2025 at 1:27 PM', 'latest', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-02-20 05:27:33', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0076', '2025-02-20 13:32:36', 'gg - February 20, 2025 at 1:32 PM', 'latest', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-02-20 05:32:36', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0077', '2025-02-20 13:45:08', 'gg - February 20, 2025 at 1:41 PM', 'neil', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-02-20 05:45:08', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0078', '2025-02-20 14:34:03', 'Gate 1 - February 20, 2025 at 2:33 PM', 'neil', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-02-20 06:34:03', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0079', '2025-02-21 13:02:16', 'Gate 1 - February 21, 2025 at 1:00 PM', 'neil', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-02-21 05:02:16', 'Rescheduled', '2025-03-01 20:55:01', 1, 'Pending', NULL, 0),
('CEIT-24-25-0080', '2025-02-21 19:03:30', 'Gate 1 - February 21, 2025 at 7:02 PM', 'hello', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-02-21 11:03:30', 'Referred', '2025-02-21 19:38:37', 1, 'Pending', NULL, 0),
('CEIT-24-25-0081', '2025-02-21 19:04:52', 'Gate 1 - February 21, 2025 at 7:04 PM', 'hello', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-02-21 11:04:52', 'Referred', '2025-03-01 20:49:46', 1, 'Pending', NULL, 0),
('CEIT-24-25-0082', '2025-03-01 20:44:54', 'Gate 1 - March 1, 2025 at 8:44 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-03-01 12:44:54', 'For Meeting', '2025-03-01 20:53:47', 1, 'Pending', NULL, 0),
('CEIT-24-25-0083', '2025-03-01 20:46:20', 'Gate 1 - March 1, 2025 at 8:46 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-03-01 12:46:20', 'Settled', '2025-03-08 12:28:43', 1, 'Resolved', 'wala', 0),
('CEIT-24-25-0084', '2025-03-01 21:12:40', 'neil - March 1, 2025 at 9:10 PM', 'neilss', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-03-01 13:12:40', 'Referred', '2025-03-01 21:13:25', 1, 'Pending', NULL, 0),
('CEIT-24-25-0085', '2025-03-01 21:22:04', 'Gate 1 - March 1, 2025 at 9:21 PM', 'neil', 'Simeons N. Daez', 1, 'adviser', NULL, '2025-03-01 13:22:04', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0086', '2025-03-01 21:35:52', 'gg - March 1, 2025 at 9:35 PM', 'ls', 'Neil Tristhan N. Mojica', 1, 'instructor', NULL, '2025-03-01 13:35:52', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0087', '2025-03-01 22:02:12', 'Gate 1 - March 1, 2025 at 10:01 PM', 'js', 'Gladys G. Perey', 1, 'facilitator', NULL, '2025-03-01 14:02:12', 'Referred', '2025-03-01 22:03:11', 1, 'Pending', NULL, 0),
('CEIT-24-25-0088', '2025-03-08 07:43:55', 'neil - March 8, 2025 at 7:43 AM', 'neil', 'Neil Tristhan N. Mojica', 1, 'instructor', NULL, '2025-03-07 23:43:55', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0089', '2025-03-08 10:42:32', 'neil - March 8, 2025 at 10:42 AM', 'hie', 'NEIL TRISTHAN N. MOJICA', 202102690, 'student', NULL, '2025-03-08 02:42:32', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0090', '2025-03-08 11:42:06', 'salyusoy - March 8, 2025 at 11:41 AM', 'neil', 'NEIL TRISTHAN N. MOJICA', 202102690, 'student', NULL, '2025-03-08 03:42:06', 'For Meeting', '2025-03-08 12:23:28', 1, 'Pending', NULL, 0),
('CEIT-24-25-0091', '2025-03-08 12:30:26', 'salyusoy - March 8, 2025 at 12:30 PM', 'nn', 'NEIL TRISTHAN N. MOJICA', 202102690, 'student', NULL, '2025-03-08 04:30:26', 'Settled', '2025-03-08 12:38:12', 1, 'Resolved', 'nnn', 0),
('CEIT-24-25-0092', '2025-03-08 13:51:11', 'neil - March 8, 2025 at 1:50 PM', 'neil', 'NEIL TRISTHAN N. MOJICA', 202102690, 'student', NULL, '2025-03-08 05:51:11', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0093', '2025-03-08 20:22:32', 'neil - March 8, 2025 at 8:20 PM', 'neeidi', 'MR GUARD', 2, 'guard', NULL, '2025-03-08 12:31:43', 'Pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0094', '2025-03-08 20:36:55', 'neil - March 8, 2025 at 8:36 PM', 'nn', 'MR GUARD', 2, 'guard', NULL, '2025-03-08 12:37:16', 'Pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0095', '2025-03-09 09:47:42', 'neil - March 9, 2025 at 9:46 AM', 'mm', 'MR GUARD', 2, 'guard', NULL, '2025-03-09 01:48:05', 'Pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0096', '2025-03-09 09:51:03', 'neil - March 9, 2025 at 9:50 AM', 'nn', 'MR GUARD', 2, 'guard', NULL, '2025-03-09 01:51:57', 'Pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0097', '2025-03-09 20:23:56', 'neil - March 9, 2025 at 8:22 PM', 'nnnnnn', 'MR GUARD', 2, 'guard', NULL, '2025-03-09 12:36:45', 'Pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0098', '2025-03-09 19:04:02', 'neil - March 9, 2025 at 7:02 PM', 'nnnn', 'MR GUARD', 2, 'guard', NULL, '2025-03-16 03:51:21', 'Pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0099', '2025-03-09 20:52:11', 'neil - March 9, 2025 at 8:51 PM', 'mmm', 'MR GUARD', 2, 'guard', NULL, '2025-03-16 03:52:05', 'For Meeting', '2025-04-09 14:02:56', 1, 'Pending', NULL, 0),
('CEIT-24-25-0100', '2025-03-09 17:41:31', 'neil - March 9, 2025 at 5:40 PM', 'beee', 'MR GUARD', 2, 'guard', NULL, '2025-03-16 03:54:31', 'Pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0101', '2025-04-09 12:12:28', 'Gate 1 - April 9, 2025 at 12:11 PM', 'bwisit na bata', 'Neil Tristhan N. Mojica', 1, 'instructor', '../../uploads/incident_reports_proof/Week 5  - Daily Journal (2).docx', '2025-04-09 04:12:28', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0102', '2025-04-09 13:42:17', 'Gate 1 - April 9, 2025 at 1:41 PM', 'burzonnnn', 'NEIL TRISTHAN N. MOJICA', 202102690, 'student', NULL, '2025-04-09 05:42:17', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0103', '2025-04-09 13:55:55', 'library - April 9, 2025 at 1:55 PM', 'o i a a i u i ', 'Gladys G. Perey', 1, 'facilitator', '../../uploads/incident_reports_proof/pexels-polina-tankilevitch-5469025.jpg', '2025-04-09 05:55:55', 'pending', NULL, NULL, 'Pending', NULL, 0),
('CEIT-24-25-0104', '2025-04-09 14:19:37', 'Saluysoy - April 9, 2025 at 2:18 PM', 'sigma boy', 'Simeons N. Daez', 1, 'adviser', '../../uploads/incident_reports_proof/pexels-polina-tankilevitch-5469025.jpg', '2025-04-09 06:19:37', 'pending', NULL, NULL, 'Pending', NULL, 0);

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
(198, 'CEIT-24-25-0001', 'staff', NULL, 'JUDE F. BAUTISTA', NULL, NULL, NULL, 'neiltristhan@gmail.com', NULL, NULL, NULL, NULL),
(199, 'CEIT-24-25-0001', 'student', NULL, 'HELLO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(200, 'CEIT-24-25-0002', 'student', NULL, 'EY', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(201, 'CEIT-24-25-0003', 'student', NULL, 'HINNN', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(202, 'CEIT-24-25-0004', 'student', NULL, 'NEHDDHH', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(203, 'CEIT-24-25-0005', 'student', NULL, 'LAST', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(204, 'CEIT-24-25-0006', 'student', NULL, 'HELLO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(205, 'CEIT-24-25-0007', 'student', NULL, 'JUDE F. BAUTISTA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(209, 'CEIT-24-25-0008', 'student', NULL, 'HELLO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(210, 'CEIT-24-25-0008', 'staff', NULL, 'HELLOS', NULL, NULL, NULL, 'neiltristhan@gmail.com', NULL, NULL, NULL, NULL),
(211, 'CEIT-24-25-0008', 'staff', NULL, 'HELLOS', NULL, NULL, NULL, 'neiltristhan544@gmail.com', NULL, NULL, NULL, NULL),
(212, 'CEIT-24-25-0013', 'student', NULL, 'NEIL MOJ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(213, 'CEIT-24-25-0020', 'student', NULL, 'NEIL MOJ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(214, 'CEIT-24-25-0020', 'student', NULL, 'NEIL MOJ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(215, 'CEIT-24-25-0024', 'student', NULL, 'JUDE F. BAUTISTA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(216, 'CEIT-24-25-0025', 'student', NULL, 'NEIL MOJ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(217, 'CEIT-24-25-0025', 'student', NULL, 'NEIL MOJ (2)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(218, 'CEIT-24-25-0025', 'student', NULL, 'NEIL MOJ (3)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(219, 'CEIT-24-25-0026', 'staff', NULL, 'JUDE F. BAUTISTA', NULL, NULL, NULL, 'neiltristhan544@gmail.com', NULL, NULL, NULL, NULL),
(220, 'CEIT-24-25-0026', 'staff', NULL, 'JUDE F. BAUTISTA (2)', NULL, NULL, NULL, 'neiltristhan123@gmail.com', NULL, NULL, NULL, NULL),
(221, 'CEIT-24-25-0027', 'student', NULL, 'NEIL MOJ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(222, 'CEIT-24-25-0027', 'student', NULL, 'NEIL MOJ (2)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(223, 'CEIT-24-25-0027', 'student', NULL, 'NEIL MOJ (3)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(224, 'CEIT-24-25-0038', 'student', NULL, 'NEIL TRISTHAN N. MOJICA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(225, 'CEIT-24-25-0047', 'student', NULL, 'NEIL MOJ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(226, 'CEIT-24-25-0047', 'student', NULL, 'NEIL MOJ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(227, 'CEIT-24-25-0048', 'student', NULL, 'DENZEL MOJICA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(228, 'CEIT-24-25-0049', 'student', NULL, 'NEHDDHH', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(229, 'CEIT-24-25-0068', 'student', NULL, 'DENZEL MOJICA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(230, 'CEIT-24-25-0069', 'student', NULL, 'HELLO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(231, 'CEIT-24-25-0070', 'student', NULL, 'JUDE F. BAUTISTA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(232, 'CEIT-24-25-0071', 'student', NULL, 'HELLO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(233, 'CEIT-24-25-0072', 'student', NULL, 'NEIL TRISTHAN N. MOJICA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(234, 'CEIT-24-25-0072', 'student', NULL, 'NEIL TRISTHAN N. MOJICA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(235, 'CEIT-24-25-0073', 'student', NULL, 'NEHDDHH', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(236, 'CEIT-24-25-0073', 'student', NULL, 'NEHDDHH', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(237, 'CEIT-24-25-0074', 'student', NULL, 'HELLO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(238, 'CEIT-24-25-0074', 'student', NULL, 'HELLO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(239, 'CEIT-24-25-0075', 'student', NULL, 'JOLLO PANALIGAN', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(240, 'CEIT-24-25-0076', 'student', NULL, 'BONGGOY', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(241, 'CEIT-24-25-0076', 'student', NULL, 'BONGGOY', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(245, 'CEIT-24-25-0077', 'student', '202102690', 'NEIL TRISTHAN MOJICA', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'First Year', NULL, 2518203, 'BS Computer Engineering - First Year Section 2', 1, 'Simeons N. Daez'),
(246, 'CEIT-24-25-0078', 'student', NULL, 'NEIL TRISTHAN MOJICA', 'NEIL TRISTHAN MOJICA', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(247, 'CEIT-24-25-0079', 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(248, 'CEIT-24-25-0080', 'student', NULL, 'NEIL', 'NEIL', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(249, 'CEIT-24-25-0081', 'student', NULL, 'NEIL TRISTHAN N. MOJICA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(250, 'CEIT-24-25-0081', 'student', NULL, 'JOLLO PANALIGAN', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(251, 'CEIT-24-25-0082', 'staff', NULL, 'NEIL', NULL, NULL, NULL, 'hi123@gmail.com', NULL, NULL, NULL, NULL),
(252, 'CEIT-24-25-0083', 'student', NULL, 'JOLLO PANALIGAN', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(253, 'CEIT-24-25-0084', 'student', NULL, 'HELLO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(254, 'CEIT-24-25-0084', 'student', NULL, 'HELLO (2)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(255, 'CEIT-24-25-0085', 'student', NULL, 'JOLLO PANALIGAN', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(256, 'CEIT-24-25-0086', 'student', NULL, 'NEIL', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(257, 'CEIT-24-25-0086', 'student', NULL, 'NEIL (2)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(258, 'CEIT-24-25-0087', 'student', NULL, 'NEIL', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(259, 'CEIT-24-25-0088', 'student', NULL, 'NEIL MOJ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(260, 'CEIT-24-25-0089', 'student', NULL, 'NEHDDHH', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(261, 'CEIT-24-25-0090', 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(262, 'CEIT-24-25-0090', 'student', NULL, 'NEHDDHH2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(263, 'CEIT-24-25-0091', 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(264, 'CEIT-24-25-0092', 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(265, 'CEIT-24-25-0093', 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(266, 'CEIT-24-25-0094', 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(267, 'CEIT-24-25-0095', 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(268, 'CEIT-24-25-0095', 'student', NULL, 'NEIL MOJ', 'NEIL MOJ', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(269, 'CEIT-24-25-0096', 'student', NULL, 'DENZEL MOJICA', 'DENZEL MOJICA', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(270, 'CEIT-24-25-0096', 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(271, 'CEIT-24-25-0097', 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(272, 'CEIT-24-25-0097', 'student', NULL, 'NEHDDHH', 'NEHDDHH', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(273, 'CEIT-24-25-0097', 'staff', NULL, 'ANYEONG', 'ANYEONG', NULL, NULL, 'neiltristhan544@gmail.com', NULL, NULL, NULL, NULL),
(274, 'CEIT-24-25-0098', 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(275, 'CEIT-24-25-0099', 'student', NULL, 'HELLO', 'HELLO', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(276, 'CEIT-24-25-0099', 'student', NULL, 'HELLO (2)', 'HELLO (2)', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(277, 'CEIT-24-25-0100', 'student', '202105212', 'JHANNAH BERNADETTE ALBINO', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(278, 'CEIT-24-25-0101', 'student', '202105723', 'SHAWN RAVEN FERRER', 'SHAWN RAVEN FERRER', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(279, 'CEIT-24-25-0102', 'student', '202011451', 'KAYRON MARK BURZON', 'KAYRON MARK BURZON', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(280, 'CEIT-24-25-0103', 'student', '202106746', 'EURICA MAE BORCE', 'EURICA MAE BORCE', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(281, 'CEIT-24-25-0104', 'student', '202105206', 'JOHNBERT CHRISTIENE TAGLE', 'JOHNBERT CHRISTIENE TAGLE', 'BS Computer Engineering', 'Second Year', NULL, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez');

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
(18, 'CEIT-24-25-0099', '2025-04-10 12:00:00', 'CEIT GUIDANCE Office', '', '', '', '', '2025-04-09 06:41:34', 1);

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
(1, 'student', '202011307', 'New document request status update', 'request_form.php', 0, '2024-12-11 06:46:23'),
(2, 'student', '202011307', 'Profile form update required', 'student_profile_form.php', 0, '2024-12-11 06:46:24'),
(3, 'student', '202011307', 'New announcement from the Guidance Office', '#', 0, '2024-12-11 06:46:24'),
(4, 'student', '202011307', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2024-12-11 07:05:28'),
(5, 'adviser', '5', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2024-12-11 07:05:28'),
(6, 'student', '202011307', 'A meeting has been scheduled for your incident report on December 23, 2024, 1:00 PM', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2024-12-11 07:06:06'),
(7, 'adviser', '5', 'A meeting has been scheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2024-12-11 07:06:06'),
(8, 'adviser', '5', 'Meeting notification sent for student BAMBY REQUILLO', 'view_meeting_details.php?id=CEIT-24-25-0001', 0, '2024-12-11 07:06:26'),
(9, 'student', '202011307', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=1', 0, '2024-12-11 07:11:50'),
(10, 'adviser', '5', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=1', 0, '2024-12-11 07:11:50'),
(11, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=1', 0, '2024-12-11 07:11:50'),
(12, 'student', '202011451', 'New document request status update', 'request_form.php', 0, '2024-12-11 14:56:45'),
(13, 'student', '202011451', 'Profile form update required', 'student_profile_form.php', 0, '2024-12-11 14:56:45'),
(14, 'student', '202011451', 'New announcement from the Guidance Office', '#', 0, '2024-12-11 14:56:45'),
(15, 'student', '202102690', 'New document request status update', 'request_form.php', 0, '2024-12-15 15:13:54'),
(16, 'student', '202102690', 'Profile form update required', 'student_profile_form.php', 0, '2024-12-15 15:13:54'),
(17, 'student', '202102690', 'New announcement from the Guidance Office', '#', 0, '2024-12-15 15:13:54'),
(18, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 07:31:11'),
(19, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 07:34:20'),
(20, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 07:44:12'),
(21, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 08:01:00'),
(22, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 08:02:01'),
(23, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 08:02:32'),
(24, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 08:05:59'),
(25, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 08:07:03'),
(26, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 08:33:00'),
(27, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 08:33:44'),
(28, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 08:36:53'),
(29, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-17 08:46:21'),
(30, 'student', '202102690', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0023', 0, '2024-12-17 09:26:15'),
(31, 'student', '202105212', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0023', 0, '2024-12-17 09:26:15'),
(32, 'adviser', '1', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0023', 0, '2024-12-17 09:26:15'),
(33, 'student', '202102690', 'Your incident report has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0023', 0, '2024-12-17 09:32:02'),
(34, 'adviser', '1', 'An incident report for your student has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0023', 0, '2024-12-17 09:32:02'),
(35, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 02:44:22'),
(36, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 02:52:41'),
(37, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 02:54:46'),
(38, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 03:27:10'),
(39, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 03:27:52'),
(40, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 04:41:06'),
(41, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 05:00:42'),
(42, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 05:07:49'),
(43, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 05:12:43'),
(44, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 05:19:24'),
(45, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 05:30:45'),
(46, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 05:35:31'),
(47, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 05:48:13'),
(48, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 06:29:14'),
(49, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 06:37:23'),
(50, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 06:39:57'),
(51, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 06:46:57'),
(52, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 07:47:07'),
(53, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 07:48:49'),
(54, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 08:12:54'),
(55, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 08:43:39'),
(56, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 08:45:43'),
(57, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 09:33:26'),
(58, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 09:37:13'),
(59, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 09:43:00'),
(60, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 09:53:09'),
(61, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 09:54:33'),
(62, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 10:00:19'),
(63, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 10:10:26'),
(64, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 10:23:11'),
(65, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 10:28:30'),
(66, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 10:37:13'),
(67, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 14:19:06'),
(68, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 14:27:38'),
(69, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 14:29:50'),
(70, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 14:37:13'),
(71, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 14:50:48'),
(72, 'dean', '', 'A new incident report has been submitted by CJ  MOJICA that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 14:58:52'),
(73, 'dean', '', 'A new incident report has been submitted by RUDYG  CALAY III. that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-18 15:16:44'),
(74, 'dean', '', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-19 02:43:52'),
(75, 'dean', '', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-19 03:04:41'),
(76, 'dean', '', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-19 03:53:23'),
(77, '', '2', 'Your Incident Report submitted on December 19, 2024 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0034', 'view_submitted_incident_reports_guard.php', 0, '2024-12-19 03:54:26'),
(78, 'student', '202102690', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0034', 0, '2024-12-19 03:55:57'),
(79, 'student', '202102691', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0034', 0, '2024-12-19 03:55:57'),
(80, 'adviser', '1', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0034', 0, '2024-12-19 03:55:57'),
(81, 'student', '202102690', 'A meeting has been scheduled for your incident report on December 19, 2024, 8:00 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0034', 0, '2024-12-19 03:56:21'),
(82, 'adviser', '1', 'A meeting has been scheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0034', 0, '2024-12-19 03:56:21'),
(83, 'student', '202102690', 'A meeting has been rescheduled for your incident report on December 19, 2024, 9:30 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0034', 0, '2024-12-19 03:59:19'),
(84, 'adviser', '1', 'A meeting has been rescheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0034', 0, '2024-12-19 03:59:19'),
(85, 'dean', '', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-19 04:02:12'),
(86, 'dean', '', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-19 04:15:21'),
(87, 'dean', '', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-19 04:39:07'),
(88, 'dean', '', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-19 06:16:45'),
(89, '', '2', 'Your Incident Report submitted on December 19, 2024 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0035', 'view_submitted_incident_reports_guard.php', 0, '2024-12-19 07:53:12'),
(90, 'dean', '', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-19 08:07:57'),
(91, '', '2', 'Your Incident Report submitted on December 19, 2024 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0036', 'view_submitted_incident_reports_guard.php', 0, '2024-12-19 08:37:50'),
(92, 'dean', '', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-19 08:58:12'),
(93, 'dean', '', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-19 09:44:44'),
(94, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-21 14:09:24'),
(95, 'guard', '2', 'Your Incident Report submitted on December 21, 2024 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0070', 'view_submitted_incident_reports_guard.php', 0, '2024-12-21 14:20:04'),
(96, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-21 14:51:24'),
(97, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0070', 0, '2024-12-21 15:28:16'),
(98, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0070', 0, '2024-12-21 15:28:16'),
(99, 'student', '202102690', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0070', 0, '2024-12-21 15:28:16'),
(100, 'adviser', '1', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0070', 0, '2024-12-21 15:28:16'),
(101, 'student', '202108636', 'New document request status update', 'request_form.php', 0, '2024-12-29 06:44:53'),
(102, 'student', '202108636', 'Profile form update required', 'student_profile_form.php', 0, '2024-12-29 06:44:53'),
(103, 'student', '202108636', 'New announcement from the Guidance Office', '#', 0, '2024-12-29 06:44:53'),
(104, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-29 08:06:31'),
(105, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-29 08:07:58'),
(106, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-29 08:20:42'),
(107, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-29 08:28:46'),
(108, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2024-12-29 08:36:30'),
(109, 'student', '202102690', 'A meeting has been scheduled for your incident report on January 2, 2025, 8:00 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0070', 0, '2025-01-02 01:43:46'),
(110, 'adviser', '1', 'A meeting has been scheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0070', 0, '2025-01-02 01:43:46'),
(111, 'adviser', '1', 'Meeting notification sent for student NEIL TRISTHAN MOJICA', 'view_meeting_details.php?id=CEIT-24-25-0070', 0, '2025-01-02 01:44:00'),
(112, 'student', '202102690', 'You have a scheduled meeting on January 2, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0070', 0, '2025-01-02 01:44:13'),
(113, 'student', '202102690', 'You have a scheduled meeting on January 2, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0070', 0, '2025-01-02 01:44:22'),
(114, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=2', 0, '2025-01-02 01:48:18'),
(115, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=2', 0, '2025-01-02 01:48:19'),
(116, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=2', 0, '2025-01-02 01:48:19'),
(117, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0140', 0, '2025-01-02 01:49:40'),
(118, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0140', 0, '2025-01-02 01:49:40'),
(119, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0138', 0, '2025-01-02 01:51:25'),
(120, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0138', 0, '2025-01-02 01:51:25'),
(121, 'student', '2', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0138', 0, '2025-01-02 01:51:25'),
(122, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0138', 0, '2025-01-02 01:51:25'),
(123, 'student', '202102690', 'A meeting has been scheduled for your incident report on January 2, 2025, 8:30 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0138', 0, '2025-01-02 01:54:47'),
(124, 'adviser', '1', 'A meeting has been scheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0138', 0, '2025-01-02 01:54:47'),
(125, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=3', 0, '2025-01-02 02:05:46'),
(126, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=4', 0, '2025-01-02 04:32:07'),
(127, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0142', 0, '2025-01-02 04:41:26'),
(128, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0142', 0, '2025-01-02 04:41:26'),
(129, 'student', '2', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0142', 0, '2025-01-02 04:41:26'),
(130, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0142', 0, '2025-01-02 04:41:26'),
(131, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=9', 0, '2025-01-02 07:39:15'),
(132, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=10', 0, '2025-01-02 07:41:48'),
(133, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=11', 0, '2025-01-02 07:42:11'),
(134, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=11', 0, '2025-01-02 07:42:11'),
(135, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=11', 0, '2025-01-02 07:42:11'),
(136, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=12', 0, '2025-01-02 07:55:09'),
(137, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=12', 0, '2025-01-02 07:55:09'),
(138, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=12', 0, '2025-01-02 07:55:09'),
(139, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=13', 0, '2025-01-02 07:55:27'),
(140, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=14', 0, '2025-01-02 07:56:08'),
(141, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0137', 0, '2025-01-02 08:10:20'),
(142, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0137', 0, '2025-01-02 08:10:20'),
(143, 'student', '2', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0137', 0, '2025-01-02 08:10:20'),
(144, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0137', 0, '2025-01-02 08:10:20'),
(145, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=15', 0, '2025-01-02 08:11:13'),
(146, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=15', 0, '2025-01-02 08:11:13'),
(147, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=15', 0, '2025-01-02 08:11:13'),
(148, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=16', 0, '2025-01-02 08:11:26'),
(149, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=17', 0, '2025-01-02 08:12:15'),
(150, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0136', 0, '2025-01-02 08:17:11'),
(151, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0136', 0, '2025-01-02 08:17:11'),
(152, 'student', '2', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0136', 0, '2025-01-02 08:17:11'),
(153, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0136', 0, '2025-01-02 08:17:11'),
(154, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=18', 0, '2025-01-02 08:18:18'),
(155, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=18', 0, '2025-01-02 08:18:18'),
(156, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=18', 0, '2025-01-02 08:18:18'),
(157, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=19', 0, '2025-01-02 08:18:31'),
(158, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=20', 0, '2025-01-02 08:19:15'),
(159, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=21', 0, '2025-01-02 08:23:03'),
(160, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=21', 0, '2025-01-02 08:23:03'),
(161, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=21', 0, '2025-01-02 08:23:03'),
(162, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0134', 0, '2025-01-02 10:06:53'),
(163, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0134', 0, '2025-01-02 10:06:53'),
(164, 'student', '2', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0134', 0, '2025-01-02 10:06:53'),
(165, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0134', 0, '2025-01-02 10:06:53'),
(166, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=22', 0, '2025-01-02 10:08:20'),
(167, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=22', 0, '2025-01-02 10:08:20'),
(168, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=22', 0, '2025-01-02 10:08:20'),
(169, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=23', 0, '2025-01-02 10:08:36'),
(170, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=24', 0, '2025-01-02 10:08:44'),
(171, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0131', 0, '2025-01-03 04:35:43'),
(172, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0131', 0, '2025-01-03 04:35:43'),
(173, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0131', 0, '2025-01-03 04:35:43'),
(174, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=25', 0, '2025-01-03 04:36:42'),
(175, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=25', 0, '2025-01-03 04:36:42'),
(176, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=25', 0, '2025-01-03 04:36:42'),
(177, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=26', 0, '2025-01-03 04:36:53'),
(178, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0141', 0, '2025-01-03 04:57:56'),
(179, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0141', 0, '2025-01-03 04:57:56'),
(180, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0141', 0, '2025-01-03 04:57:56'),
(181, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=27', 0, '2025-01-03 04:58:21'),
(182, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=27', 0, '2025-01-03 04:58:21'),
(183, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=27', 0, '2025-01-03 04:58:21'),
(184, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=28', 0, '2025-01-03 04:58:34'),
(185, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0130', 0, '2025-01-03 04:59:20'),
(186, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0130', 0, '2025-01-03 04:59:20'),
(187, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0130', 0, '2025-01-03 04:59:20'),
(188, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=29', 0, '2025-01-03 04:59:37'),
(189, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=29', 0, '2025-01-03 04:59:37'),
(190, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=29', 0, '2025-01-03 04:59:37'),
(191, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=30', 0, '2025-01-03 04:59:46'),
(192, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0128', 0, '2025-01-03 05:00:14'),
(193, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0128', 0, '2025-01-03 05:00:14'),
(194, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0128', 0, '2025-01-03 05:00:14'),
(195, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=31', 0, '2025-01-03 05:00:33'),
(196, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=31', 0, '2025-01-03 05:00:33'),
(197, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=31', 0, '2025-01-03 05:00:33'),
(198, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=32', 0, '2025-01-03 05:00:50'),
(199, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0127', 0, '2025-01-03 05:19:31'),
(200, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0127', 0, '2025-01-03 05:19:31'),
(201, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0127', 0, '2025-01-03 05:19:31'),
(202, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=33', 0, '2025-01-03 05:19:59'),
(203, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=33', 0, '2025-01-03 05:19:59'),
(204, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=33', 0, '2025-01-03 05:19:59'),
(205, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=34', 0, '2025-01-03 05:20:16'),
(206, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0127', 0, '2025-01-03 05:24:01'),
(207, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0127', 0, '2025-01-03 05:24:01'),
(208, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0127', 0, '2025-01-03 05:24:01'),
(209, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0126', 0, '2025-01-03 05:25:46'),
(210, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0126', 0, '2025-01-03 05:25:46'),
(211, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0126', 0, '2025-01-03 05:25:46'),
(212, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=35', 0, '2025-01-03 05:26:08'),
(213, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=35', 0, '2025-01-03 05:26:08'),
(214, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=35', 0, '2025-01-03 05:26:08'),
(215, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=36', 0, '2025-01-03 05:27:42'),
(216, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0119', 0, '2025-01-03 05:30:25'),
(217, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0119', 0, '2025-01-03 05:30:25'),
(218, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0119', 0, '2025-01-03 05:30:25'),
(219, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=37', 0, '2025-01-03 05:30:47'),
(220, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=37', 0, '2025-01-03 05:30:47'),
(221, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=37', 0, '2025-01-03 05:30:47'),
(222, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=38', 0, '2025-01-03 05:31:02'),
(223, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0118', 0, '2025-01-03 05:35:16'),
(224, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0118', 0, '2025-01-03 05:35:16'),
(225, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0118', 0, '2025-01-03 05:35:16'),
(226, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=39', 0, '2025-01-03 05:35:45'),
(227, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=39', 0, '2025-01-03 05:35:45'),
(228, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=39', 0, '2025-01-03 05:35:45'),
(229, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=40', 0, '2025-01-03 05:35:55'),
(230, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0125', 0, '2025-01-03 05:38:12'),
(231, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0125', 0, '2025-01-03 05:38:12'),
(232, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0125', 0, '2025-01-03 05:38:12'),
(233, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0124', 0, '2025-01-03 05:38:52'),
(234, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0124', 0, '2025-01-03 05:38:52'),
(235, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0124', 0, '2025-01-03 05:38:52'),
(236, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0123', 0, '2025-01-03 05:44:37'),
(237, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0123', 0, '2025-01-03 05:44:37'),
(238, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0123', 0, '2025-01-03 05:44:37'),
(239, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0115', 0, '2025-01-03 05:46:47'),
(240, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0115', 0, '2025-01-03 05:46:47'),
(241, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0115', 0, '2025-01-03 05:46:47'),
(242, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=41', 0, '2025-01-03 05:47:04'),
(243, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=41', 0, '2025-01-03 05:47:04'),
(244, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=41', 0, '2025-01-03 05:47:04'),
(245, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=42', 0, '2025-01-03 05:47:14'),
(246, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-01-03 06:04:26'),
(247, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-01-03 06:04:26'),
(248, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0114', 0, '2025-01-03 06:04:26'),
(249, 'student', '202106707', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=43', 0, '2025-01-03 06:05:34'),
(250, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=43', 0, '2025-01-03 06:05:34'),
(251, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=43', 0, '2025-01-03 06:05:34'),
(252, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=44', 0, '2025-01-03 06:05:43'),
(253, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0116', 0, '2025-01-03 06:10:33'),
(254, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0116', 0, '2025-01-03 06:10:33'),
(255, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0116', 0, '2025-01-03 06:10:33'),
(256, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=45', 0, '2025-01-03 06:11:00'),
(257, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=45', 0, '2025-01-03 06:11:00'),
(258, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=45', 0, '2025-01-03 06:11:00'),
(259, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=46', 0, '2025-01-03 06:11:10'),
(260, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0143', 0, '2025-01-03 06:14:32'),
(261, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0143', 0, '2025-01-03 06:14:32'),
(262, 'student', '2', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0143', 0, '2025-01-03 06:14:32'),
(263, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0143', 0, '2025-01-03 06:14:32'),
(264, 'student', '202107410', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=47', 0, '2025-01-03 06:14:53'),
(265, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=47', 0, '2025-01-03 06:14:53'),
(266, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=47', 0, '2025-01-03 06:14:53'),
(267, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=48', 0, '2025-01-03 06:15:02'),
(268, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=48', 0, '2025-01-03 06:15:02'),
(269, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=48', 0, '2025-01-03 06:15:02'),
(270, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=49', 0, '2025-01-03 06:15:13'),
(271, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0144', 0, '2025-01-03 06:17:23'),
(272, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0144', 0, '2025-01-03 06:17:23'),
(273, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=50', 0, '2025-01-03 06:17:48'),
(274, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=50', 0, '2025-01-03 06:17:48'),
(275, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=50', 0, '2025-01-03 06:17:48'),
(276, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0146', 0, '2025-01-03 06:23:44'),
(277, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0146', 0, '2025-01-03 06:23:44'),
(278, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0146', 0, '2025-01-03 06:23:44'),
(279, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=51', 0, '2025-01-03 06:24:00'),
(280, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=51', 0, '2025-01-03 06:24:00'),
(281, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=51', 0, '2025-01-03 06:24:00'),
(282, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=52', 0, '2025-01-03 06:24:09'),
(283, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-01-03 08:53:20'),
(284, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-01-03 08:53:20'),
(285, 'student', '2', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-01-03 08:53:20'),
(286, 'student', '3', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-01-03 08:53:20'),
(287, 'student', '4', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-01-03 08:53:20'),
(288, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0001', 0, '2025-01-03 08:53:20'),
(289, 'student', '202107410', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=53', 0, '2025-01-03 08:54:10'),
(290, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=53', 0, '2025-01-03 08:54:10'),
(291, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=53', 0, '2025-01-03 08:54:10'),
(292, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=54', 0, '2025-01-03 08:54:19'),
(293, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=54', 0, '2025-01-03 08:54:19'),
(294, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=54', 0, '2025-01-03 08:54:19'),
(295, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=55', 0, '2025-01-03 08:54:27'),
(296, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=56', 0, '2025-01-03 08:54:34'),
(297, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=57', 0, '2025-01-03 08:54:43'),
(298, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-01-03 09:33:58'),
(299, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0002', 0, '2025-01-03 09:33:58'),
(300, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=58', 0, '2025-01-03 09:34:17'),
(301, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=58', 0, '2025-01-03 09:34:17'),
(302, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=58', 0, '2025-01-03 09:34:17'),
(303, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0005', 0, '2025-01-03 09:37:06'),
(304, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0005', 0, '2025-01-03 09:37:06'),
(305, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0004', 0, '2025-01-03 09:37:24'),
(306, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0004', 0, '2025-01-03 09:37:24'),
(307, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0004', 0, '2025-01-03 09:37:24'),
(308, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0003', 0, '2025-01-03 09:37:32'),
(309, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0003', 0, '2025-01-03 09:37:32'),
(310, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=59', 0, '2025-01-03 10:02:59'),
(311, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=59', 0, '2025-01-03 10:02:59');
INSERT INTO `notifications` (`id`, `user_type`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(312, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=59', 0, '2025-01-03 10:02:59'),
(313, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=60', 0, '2025-01-04 03:11:48'),
(314, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=60', 0, '2025-01-04 03:11:48'),
(315, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=60', 0, '2025-01-04 03:11:48'),
(316, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=61', 0, '2025-01-04 03:48:32'),
(317, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0007', 0, '2025-01-04 05:56:37'),
(318, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0007', 0, '2025-01-04 05:56:37'),
(319, 'student', '2', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0007', 0, '2025-01-04 05:56:37'),
(320, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0007', 0, '2025-01-04 05:56:37'),
(321, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0008', 0, '2025-01-04 06:08:47'),
(322, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0008', 0, '2025-01-04 06:08:47'),
(323, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0008', 0, '2025-01-04 06:08:47'),
(324, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=62', 0, '2025-01-04 06:12:52'),
(325, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=62', 0, '2025-01-04 06:12:52'),
(326, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=62', 0, '2025-01-04 06:12:52'),
(327, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=63', 0, '2025-01-04 06:13:02'),
(328, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=64', 0, '2025-01-04 06:13:42'),
(329, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=64', 0, '2025-01-04 06:13:42'),
(330, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=64', 0, '2025-01-04 06:13:42'),
(331, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=65', 0, '2025-01-04 06:13:52'),
(332, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=66', 0, '2025-01-04 06:14:00'),
(333, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=67', 0, '2025-01-04 09:29:28'),
(334, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=67', 0, '2025-01-04 09:29:28'),
(335, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=67', 0, '2025-01-04 09:29:28'),
(336, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0006', 0, '2025-01-04 10:11:31'),
(337, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0006', 0, '2025-01-04 10:11:31'),
(338, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0006', 0, '2025-01-04 10:11:31'),
(339, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=68', 0, '2025-01-04 10:11:58'),
(340, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=68', 0, '2025-01-04 10:11:58'),
(341, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=68', 0, '2025-01-04 10:11:58'),
(342, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=69', 0, '2025-01-04 10:12:06'),
(343, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0080', 0, '2025-02-21 11:38:37'),
(344, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0080', 0, '2025-02-21 11:38:37'),
(345, 'student', '2', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0080', 0, '2025-02-21 11:38:37'),
(346, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0080', 0, '2025-02-21 11:38:37'),
(347, 'student', '202102690', 'A meeting has been scheduled for your incident report on February 24, 2025, 8:00 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0080', 0, '2025-02-21 11:39:35'),
(348, 'adviser', '1', 'A meeting has been scheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0080', 0, '2025-02-21 11:39:35'),
(349, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=70', 0, '2025-02-21 11:40:04'),
(350, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=70', 0, '2025-02-21 11:40:04'),
(351, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=70', 0, '2025-02-21 11:40:04'),
(352, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=71', 0, '2025-02-21 11:40:13'),
(353, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=72', 0, '2025-02-21 11:40:23'),
(354, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0083', 0, '2025-03-01 12:48:03'),
(355, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0083', 0, '2025-03-01 12:48:03'),
(356, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0081', 0, '2025-03-01 12:49:46'),
(357, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0081', 0, '2025-03-01 12:49:46'),
(358, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0082', 0, '2025-03-01 12:53:47'),
(359, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0082', 0, '2025-03-01 12:53:47'),
(360, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0082', 0, '2025-03-01 12:53:47'),
(361, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0079', 0, '2025-03-01 12:55:01'),
(362, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0079', 0, '2025-03-01 12:55:01'),
(363, 'student', '202102690', 'A meeting has been scheduled for your incident report on March 3, 2025, 8:00 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0079', 0, '2025-03-01 12:56:13'),
(364, 'adviser', '1', 'A meeting has been scheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0079', 0, '2025-03-01 12:56:13'),
(365, 'adviser', '1', 'Meeting notification sent for student NEIL TRISTHAN MOJICA', 'view_meeting_details.php?id=CEIT-24-25-0079', 0, '2025-03-01 12:56:33'),
(366, 'student', '202102690', 'You have a scheduled meeting on March 3, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0079', 0, '2025-03-01 12:56:43'),
(367, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0084', 0, '2025-03-01 13:13:25'),
(368, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0084', 0, '2025-03-01 13:13:25'),
(369, 'student', '2', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0084', 0, '2025-03-01 13:13:25'),
(370, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0084', 0, '2025-03-01 13:13:25'),
(371, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=73', 0, '2025-03-01 13:13:57'),
(372, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=73', 0, '2025-03-01 13:13:57'),
(373, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=73', 0, '2025-03-01 13:13:57'),
(374, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=74', 0, '2025-03-01 13:14:51'),
(375, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=74', 0, '2025-03-01 13:14:51'),
(376, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=74', 0, '2025-03-01 13:14:51'),
(377, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=75', 0, '2025-03-01 13:15:02'),
(378, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=76', 0, '2025-03-01 13:15:13'),
(379, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0087', 0, '2025-03-01 14:03:11'),
(380, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0087', 0, '2025-03-01 14:03:11'),
(381, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=77', 0, '2025-03-01 14:05:36'),
(382, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=77', 0, '2025-03-01 14:05:36'),
(383, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=77', 0, '2025-03-01 14:05:36'),
(384, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0090', 0, '2025-03-08 04:23:28'),
(385, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0090', 0, '2025-03-08 04:23:28'),
(386, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0090', 0, '2025-03-08 04:23:28'),
(387, 'student', '202102690', 'A meeting has been scheduled for your incident report on March 10, 2025, 8:00 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0082', 0, '2025-03-08 04:24:20'),
(388, 'adviser', '1', 'A meeting has been scheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0082', 0, '2025-03-08 04:24:20'),
(389, 'student', '202102690', 'A meeting has been scheduled for your incident report on March 11, 2025, 8:00 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0083', 0, '2025-03-08 04:26:29'),
(390, 'adviser', '1', 'A meeting has been scheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0083', 0, '2025-03-08 04:26:29'),
(391, 'adviser', '1', 'Meeting notification sent for student NEIL TRISTHAN MOJICA', 'view_meeting_details.php?id=CEIT-24-25-0083', 0, '2025-03-08 04:26:42'),
(392, 'student', '202102690', 'You have a scheduled meeting on March 11, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0083', 0, '2025-03-08 04:26:54'),
(393, 'student', '202102690', 'You have a scheduled meeting on March 11, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0083', 0, '2025-03-08 04:26:59'),
(394, 'student', '202102690', 'Your incident report has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0083', 0, '2025-03-08 04:28:43'),
(395, 'adviser', '1', 'An incident report for your student has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0083', 0, '2025-03-08 04:28:43'),
(396, 'student', '202102690', 'A meeting has been scheduled for your incident report on March 12, 2025, 8:00 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0090', 0, '2025-03-08 04:29:37'),
(397, 'adviser', '1', 'A meeting has been scheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0090', 0, '2025-03-08 04:29:37'),
(398, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0091', 0, '2025-03-08 04:31:01'),
(399, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0091', 0, '2025-03-08 04:31:01'),
(400, 'student', '202102690', 'A meeting has been scheduled for your incident report on March 17, 2025, 8:00 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0091', 0, '2025-03-08 04:31:17'),
(401, 'adviser', '1', 'A meeting has been scheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0091', 0, '2025-03-08 04:31:17'),
(402, 'student', '202102690', 'Your incident report has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0091', 0, '2025-03-08 04:38:12'),
(403, 'adviser', '1', 'An incident report for your student has been marked as settled.', 'view_student_incident_reports.php?id=CEIT-24-25-0091', 0, '2025-03-08 04:38:12'),
(404, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2025-03-08 08:29:47'),
(405, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2025-03-08 08:41:45'),
(406, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2025-03-08 08:57:10'),
(407, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2025-03-08 09:01:39'),
(408, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2025-03-08 09:03:05'),
(409, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2025-03-08 09:08:50'),
(410, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2025-03-08 09:14:02'),
(411, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2025-03-08 09:22:19'),
(412, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-08 09:44:33'),
(413, 'dean', '1', 'A new incident report has been submitted by MR  GUARD that requires your review.', 'dean_view_incident_reports_from-Guards.php', 0, '2025-03-08 10:06:10'),
(414, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-08 11:46:36'),
(415, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-08 11:49:44'),
(416, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-08 11:53:06'),
(417, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-08 12:21:12'),
(418, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-08 12:22:32'),
(419, 'guard', '2', 'Your Incident Report submitted on March 8, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0093', 'view_submitted_incident_reports_guard.php', 0, '2025-03-08 12:31:43'),
(420, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-08 12:36:55'),
(421, 'guard', '2', 'Your Incident Report submitted on March 8, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0094', 'view_submitted_incident_reports_guard.php', 0, '2025-03-08 12:37:16'),
(422, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-09 00:58:09'),
(423, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-09 01:47:42'),
(424, 'guard', '2', 'Your Incident Report submitted on March 9, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0095', 'view_submitted_incident_reports_guard.php', 0, '2025-03-09 01:48:05'),
(425, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-09 01:51:03'),
(426, 'guard', '2', 'Your Incident Report submitted on March 9, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0096', 'view_submitted_incident_reports_guard.php', 0, '2025-03-09 01:51:57'),
(427, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-09 09:41:31'),
(428, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-09 11:04:02'),
(429, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-09 11:06:47'),
(430, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-09 12:23:56'),
(431, 'guard', '2', 'Your Incident Report submitted on March 9, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0097', 'view_submitted_incident_reports_guard.php', 0, '2025-03-09 12:36:46'),
(432, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-03-09 12:52:11'),
(433, 'guard', '2', 'Your Incident Report submitted on March 9, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0098', 'view_submitted_incident_reports_guard.php', 0, '2025-03-16 03:51:21'),
(434, 'guard', '2', 'Your Incident Report submitted on March 9, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0099', 'view_submitted_incident_reports_guard.php', 0, '2025-03-16 03:52:05'),
(435, 'guard', '2', 'Your Incident Report submitted on March 9, 2025 has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: CEIT-24-25-0100', 'view_submitted_incident_reports_guard.php', 0, '2025-03-16 03:54:31'),
(436, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-04-09 03:47:44'),
(437, 'student', '202102690', 'A meeting has been rescheduled for your incident report on April 10, 2025, 8:30 AM', 'view_student_incident_reports.php?id=CEIT-24-25-0079', 0, '2025-04-09 05:36:03'),
(438, 'adviser', '1', 'A meeting has been rescheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0079', 0, '2025-04-09 05:36:03'),
(439, 'student', '202102690', 'You have a scheduled meeting on March 3, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0079', 0, '2025-04-09 05:36:21'),
(440, 'student', '202102690', 'You have a scheduled meeting on March 3, 2025 at 8:00 AM', 'view_meeting_details.php?id=CEIT-24-25-0079', 0, '2025-04-09 05:36:51'),
(441, 'student', '0', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0099', 0, '2025-04-09 06:02:56'),
(442, 'student', '1', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0099', 0, '2025-04-09 06:02:56'),
(443, 'student', '2', 'Your incident report has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0099', 0, '2025-04-09 06:02:56'),
(444, 'adviser', '0', 'An incident report for your student has been updated to: For Meeting', 'view_student_incident_reports.php?id=CEIT-24-25-0099', 0, '2025-04-09 06:02:56'),
(445, 'student', '202102690', 'Your incident report has been referred to the Guidance Counselor.', 'view_referral_details.php?id=78', 0, '2025-04-09 06:04:14'),
(446, 'adviser', '1', 'An incident report for your student has been referred to the Guidance Counselor.', 'view_referral_details.php?id=78', 0, '2025-04-09 06:04:14'),
(447, 'counselor', '7', 'A new incident report has been referred to you.', 'view_referral_details.php?id=78', 0, '2025-04-09 06:04:14'),
(448, 'counselor', '8', 'A new incident report has been referred to you.', 'view_referral_details.php?id=78', 0, '2025-04-09 06:04:14'),
(449, 'dean', '1', 'A new incident report has been submitted by MR GUARD that requires your review.', 'dean_view_incident_reports.php', 0, '2025-04-09 06:34:31'),
(450, 'student', '202102690', 'A meeting has been scheduled for your incident report on April 10, 2025, 12:00 PM', 'view_student_incident_reports.php?id=CEIT-24-25-0099', 0, '2025-04-09 06:41:34'),
(451, 'adviser', '1', 'A meeting has been scheduled for your student\'s incident report', 'view_student_incident_reports.php?id=CEIT-24-25-0099', 0, '2025-04-09 06:41:34');

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
(112, 2, '202102690', '2025-04-09 11:47:44', 'Gate 2 - April 9, 2025 at 11:47 AM', 'nnn', 'MR GUARD', 'guard', NULL, 'Pending', '2025-04-09 03:47:44'),
(113, 2, '202015172', '2025-04-09 14:34:31', 'Gate 2 - April 9, 2025 at 2:33 PM', 'mahal ni jhon vhic si Cherrie', 'MR GUARD', 'guard', '../../uploads/incident_reports_proof/Untitled.png', 'Pending', '2025-04-09 06:34:31');

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
(124, 113, 'student', NULL, 'HELLLLLO PO', '2025-04-09 06:34:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
(122, 113, '202015172', 'XANDER LEE A. SARITA', '2025-04-09 06:34:31', 'BS Computer Engineering', 'Second Year', 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez');

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
(78, '2025-04-09', 'NEIL TRISTHAN', 'N', 'MOJICA', 'BS Computer Engineering - Second Year', 'Behavior maladjustment', 'Pending', '', '', 'Gladys G Perey', 'MR  Counsellor', '202102690', 'CEIT-24-25-0099');

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

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`profile_id`, `student_id`, `course_id`, `last_name`, `first_name`, `middle_name`, `permanent_address`, `current_address`, `province`, `city`, `barangay`, `zipcode`, `houseno_street`, `contact_number`, `email`, `gender`, `birthdate`, `birthplace`, `nationality`, `religion`, `spouse_name`, `spouse_occupation`, `age`, `civil_status`, `year_level`, `semester_first_enrolled`, `father_name`, `father_contact`, `father_occupation`, `mother_name`, `mother_contact`, `mother_occupation`, `guardian_name`, `guardian_relationship`, `guardian_contact`, `guardian_occupation`, `siblings`, `birth_order`, `family_income`, `elementary`, `secondary`, `transferees`, `course_factors`, `career_concerns`, `medications`, `medical_conditions`, `suicide_attempt`, `suicide_reason`, `problems`, `family_problems`, `fitness_activity`, `fitness_frequency`, `stress_level`, `created_at`, `signature_path`, `is_archived`) VALUES
('Stu_pro_000000001', '202102690', 1, 'MOJICA', 'NEIL TRISTHAN', 'N', 'Marahan, Alfonso, Cavite, 4123, Philippines, Marahan 1, Alfonso, Cavite, 4123, Philippines', 'Marahan, Alfonso, Cavite, 4123, Philippines, Marahan 1, Alfonso, Cavite, 4123, Philippines', 'Cavite', 'Alfonso', 'Marahan 1', 4123, 0, '09107978629', 'neiltristhan.mojica@cvsu.edu.ph', 'MALE', '2003-09-18', 'trece', 'Filipino', 'Catholic', NULL, NULL, 21, 'Single', 'First Year', 'First Semester, 2023-2024', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'Lola Remedios', 'Lola', '09299292929', 'Body Builder', 1, 'Youngest', 'above 50,000', 'Marahan Elementary School; Dito sa Mars; 2014', 'Alfonso National School; Alfonso, Cavite; 2022', '', 'Financial Security after graduation; Childhood Dream; Leisure/Enjoyment; Other: juntesa ang lola mo', 'I need more information about certain course/s and occupation/s: pusher; Others: hello', 'NO MEDICATIONS', 'NO MEDICAL CONDITIONS', 'no', '', 'NO PROBLEMS', '', 'bato', 'Everyday', 'average', '2024-12-26 10:43:11', '/capstone1/student/uploads/student_signatures/signature_676d343d11f4b.png', 0);

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
(219, '202102690', 'CEIT-24-25-0001', '2025-01-03 16:53:04', 'Done', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(220, NULL, 'CEIT-24-25-0001', '2025-01-03 16:53:04', 'Done', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(221, NULL, 'CEIT-24-25-0001', '2025-01-03 16:53:04', 'Done', 'NEIL POGI', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(222, NULL, 'CEIT-24-25-0001', '2025-01-03 16:53:04', 'Done', 'HELLO', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(223, '202107410', 'CEIT-24-25-0001', '2025-01-03 16:53:04', 'Done', 'BYRON PALOMERAS', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(224, '202102690', 'CEIT-24-25-0002', '2025-01-03 17:33:41', 'Done', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(225, '202102690', 'CEIT-24-25-0003', '2025-01-03 17:35:22', 'Done', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(226, NULL, 'CEIT-24-25-0004', '2025-01-03 17:36:13', 'Done', 'STUDENT1', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(227, '202102690', 'CEIT-24-25-0004', '2025-01-03 17:36:13', 'Done', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(228, '202102690', 'CEIT-24-25-0005', '2025-01-03 17:36:43', 'Done', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(229, NULL, 'CEIT-24-25-0006', '2025-01-04 10:06:30', 'For Meeting', 'NEIL TRISTHAN N. MOJICA123', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(230, '202102690', 'CEIT-24-25-0006', '2025-01-04 10:06:30', 'For Meeting', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(231, '202102690', 'CEIT-24-25-0007', '2025-01-04 13:55:27', 'For Meeting', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(232, NULL, 'CEIT-24-25-0007', '2025-01-04 13:55:27', 'For Meeting', 'NEIL POGI', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(233, NULL, 'CEIT-24-25-0007', '2025-01-04 13:55:27', 'For Meeting', 'STUDENT1', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(240, '202102690', 'CEIT-24-25-0008', '2025-01-05 12:11:28', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(242, NULL, 'CEIT-24-25-0008', '2025-01-05 12:11:28', 'pending', 'HELLO', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(243, '202102690', 'CEIT-24-25-0013', '2025-01-31 13:29:57', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(244, '202102690', 'CEIT-24-25-0014', '2025-01-31 13:30:43', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(245, '202102690', 'CEIT-24-25-0015', '2025-01-31 13:31:05', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(246, '202102690', 'CEIT-24-25-0016', '2025-01-31 13:34:41', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(247, '202102690', 'CEIT-24-25-0017', '2025-01-31 13:47:40', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(248, '202102690', 'CEIT-24-25-0018', '2025-01-31 13:49:09', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(249, '202102690', 'CEIT-24-25-0019', '2025-01-31 13:51:56', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(250, '202102690', 'CEIT-24-25-0020', '2025-01-31 14:00:37', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(251, NULL, 'CEIT-24-25-0020', '2025-01-31 14:00:37', 'pending', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(252, NULL, 'CEIT-24-25-0020', '2025-01-31 14:00:37', 'pending', 'NEIL STUDENT (2)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(253, NULL, 'CEIT-24-25-0024', '2025-01-31 14:24:20', 'pending', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(254, NULL, 'CEIT-24-25-0024', '2025-01-31 14:24:20', 'pending', 'NEIL STUDENT (2)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(255, '202102690', 'CEIT-24-25-0024', '2025-01-31 14:24:20', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(256, '202102690', 'CEIT-24-25-0025', '2025-01-31 14:27:01', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(257, NULL, 'CEIT-24-25-0025', '2025-01-31 14:27:01', 'pending', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(258, NULL, 'CEIT-24-25-0025', '2025-01-31 14:27:01', 'pending', 'NEIL STUDENT (2)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(259, NULL, 'CEIT-24-25-0025', '2025-01-31 14:27:01', 'pending', 'NEIL STUDENT (3)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(260, '202102690', 'CEIT-24-25-0026', '2025-01-31 14:30:10', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(261, '202102690', 'CEIT-24-25-0027', '2025-01-31 14:44:21', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(262, NULL, 'CEIT-24-25-0027', '2025-01-31 14:44:21', 'pending', 'NEIL TRISTHAN N. MOJICA (2)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(263, NULL, 'CEIT-24-25-0027', '2025-01-31 14:44:21', 'pending', 'NEIL TRISTHAN N. MOJICA (3)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(264, NULL, 'CEIT-24-25-0027', '2025-01-31 14:44:21', 'pending', 'NEIL TRISTHAN N. MOJICA (4)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(265, '202102690', 'CEIT-24-25-0028', '2025-01-31 15:00:34', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(266, '202102690', 'CEIT-24-25-0029', '2025-01-31 15:01:30', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(267, '202102690', 'CEIT-24-25-0030', '2025-01-31 15:07:53', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(268, '202102690', 'CEIT-24-25-0031', '2025-01-31 15:21:50', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(269, '202102690', 'CEIT-24-25-0032', '2025-01-31 15:27:25', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(270, '202102690', 'CEIT-24-25-0033', '2025-01-31 15:28:51', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(271, '202102690', 'CEIT-24-25-0034', '2025-01-31 16:05:43', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(272, '202102690', 'CEIT-24-25-0035', '2025-01-31 16:05:57', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(273, '202102690', 'CEIT-24-25-0036', '2025-01-31 16:28:48', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(274, '202102690', 'CEIT-24-25-0037', '2025-01-31 16:31:38', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(275, '202102690', 'CEIT-24-25-0038', '2025-01-31 16:32:10', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(276, NULL, 'CEIT-24-25-0038', '2025-01-31 16:32:10', 'pending', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(277, NULL, 'CEIT-24-25-0038', '2025-01-31 16:32:10', 'pending', 'NEIL STUDENT (2)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(278, '202102690', 'CEIT-24-25-0039', '2025-01-31 16:36:12', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(279, '202102690', 'CEIT-24-25-0040', '2025-01-31 16:49:01', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(280, '202102690', 'CEIT-24-25-0041', '2025-01-31 16:50:04', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(281, '202102690', 'CEIT-24-25-0042', '2025-01-31 16:50:29', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(282, '202102690', 'CEIT-24-25-0043', '2025-01-31 17:01:04', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(283, '202102690', 'CEIT-24-25-0044', '2025-01-31 17:04:12', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(284, '202102690', 'CEIT-24-25-0045', '2025-01-31 17:16:01', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(285, '202102690', 'CEIT-24-25-0046', '2025-01-31 17:21:14', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(286, '202102690', 'CEIT-24-25-0047', '2025-01-31 17:40:39', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(287, NULL, 'CEIT-24-25-0047', '2025-01-31 17:40:39', 'pending', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(288, NULL, 'CEIT-24-25-0047', '2025-01-31 17:40:39', 'pending', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(289, '202102690', 'CEIT-24-25-0048', '2025-01-31 17:41:26', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(290, NULL, 'CEIT-24-25-0048', '2025-01-31 17:41:26', 'pending', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(291, NULL, 'CEIT-24-25-0048', '2025-01-31 17:41:26', 'pending', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(292, '202102690', 'CEIT-24-25-0049', '2025-01-31 17:44:41', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Fourth Year', 0, NULL, NULL, NULL, NULL),
(293, NULL, 'CEIT-24-25-0049', '2025-01-31 17:44:41', 'pending', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(294, NULL, 'CEIT-24-25-0049', '2025-01-31 17:44:41', 'pending', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(295, '202102690', 'CEIT-24-25-0069', '2025-02-16 10:44:01', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Agricultural and Biosystems Engineering', 'Second Year', 0, NULL, NULL, NULL, NULL),
(296, '202102690', 'CEIT-24-25-0070', '2025-02-16 11:02:56', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Agricultural and Biosystems Engineering', 'Second Year', 0, NULL, NULL, NULL, NULL),
(297, '202102690', 'CEIT-24-25-0071', '2025-02-16 17:02:38', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Agricultural and Biosystems Engineering', 'Second Year', 0, NULL, NULL, NULL, NULL),
(298, '202102690', 'CEIT-24-25-0072', '2025-02-18 12:26:39', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Agricultural and Biosystems Engineering', 'First Year', 0, NULL, NULL, NULL, NULL),
(299, '202102690', 'CEIT-24-25-0073', '2025-02-18 12:33:56', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Agricultural and Biosystems Engineering', 'First Year', 0, 2592996, 'BS Agricultural and Biosystems Engineering - First Year Section 2', 1, 'Simeons N. Daez'),
(300, '202102690', 'CEIT-24-25-0074', '2025-02-20 13:23:41', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'First Year', 0, 2518203, 'BS Computer Engineering - First Year Section 2', 1, 'Simeons N. Daez'),
(301, '202102690', 'CEIT-24-25-0075', '2025-02-20 13:27:33', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'First Year', 0, NULL, NULL, NULL, NULL),
(302, '202102690', 'CEIT-24-25-0076', '2025-02-20 13:32:36', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'First Year', 0, 2518203, 'BS Computer Engineering - First Year Section 2', 1, 'Simeons N. Daez'),
(306, NULL, 'CEIT-24-25-0077', '2025-02-20 13:45:08', 'pending', 'JOLLO PANALIGAN', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(307, '202105791', 'CEIT-24-25-0077', '2025-02-20 13:45:08', 'pending', 'JUDE BAUTISTA', 'BS Computer Engineering', 'First Year', 0, 2518203, 'BS Computer Engineering - First Year Section 2', 1, 'Simeons N. Daez'),
(308, NULL, 'CEIT-24-25-0078', '2025-02-20 14:34:03', 'pending', 'NEIL TRISTHAN MOJICA', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(309, '202102690', 'CEIT-24-25-0078', '2025-02-20 14:34:03', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(310, '202102690', 'CEIT-24-25-0079', '2025-02-21 13:02:16', 'For Meeting', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(311, '202102690', 'CEIT-24-25-0080', '2025-02-21 19:03:30', 'For Meeting', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(312, NULL, 'CEIT-24-25-0080', '2025-02-21 19:03:30', 'For Meeting', 'NEIL TRISTHAN N. MOJICA', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(313, NULL, 'CEIT-24-25-0080', '2025-02-21 19:03:30', 'For Meeting', 'GENE ROBERT D. MANGUERA', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(314, '202102690', 'CEIT-24-25-0081', '2025-02-21 19:04:52', 'For Meeting', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(315, '202102690', 'CEIT-24-25-0082', '2025-03-01 20:44:54', 'For Meeting', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(316, NULL, 'CEIT-24-25-0082', '2025-03-01 20:44:54', 'For Meeting', 'NEILS', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(317, '202102690', 'CEIT-24-25-0083', '2025-03-01 20:46:20', 'Settled', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(318, '202102690', 'CEIT-24-25-0084', '2025-03-01 21:12:40', 'For Meeting', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(319, NULL, 'CEIT-24-25-0084', '2025-03-01 21:12:40', 'For Meeting', 'NEIL POGI', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(320, NULL, 'CEIT-24-25-0084', '2025-03-01 21:12:40', 'For Meeting', 'NEIL POGI (2)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(321, '202102690', 'CEIT-24-25-0085', '2025-03-01 21:22:04', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(322, NULL, 'CEIT-24-25-0085', '2025-03-01 21:22:04', 'pending', 'NEIL', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(323, NULL, 'CEIT-24-25-0085', '2025-03-01 21:22:04', 'pending', 'NEIL (2)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(324, '202102690', 'CEIT-24-25-0086', '2025-03-01 21:35:52', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(325, NULL, 'CEIT-24-25-0086', '2025-03-01 21:35:52', 'pending', 'NEIL', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(326, NULL, 'CEIT-24-25-0086', '2025-03-01 21:35:52', 'pending', 'NEIL (2)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(327, '202102690', 'CEIT-24-25-0087', '2025-03-01 22:02:12', 'For Meeting', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(328, '202102690', 'CEIT-24-25-0088', '2025-03-08 07:43:55', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(329, '202102690', 'CEIT-24-25-0089', '2025-03-08 10:42:32', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(330, '202102690', 'CEIT-24-25-0090', '2025-03-08 11:42:06', 'For Meeting', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(331, NULL, 'CEIT-24-25-0090', '2025-03-08 11:42:06', 'For Meeting', 'STUDENT1', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(332, '202102690', 'CEIT-24-25-0091', '2025-03-08 12:30:26', 'Settled', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(333, '202102690', 'CEIT-24-25-0092', '2025-03-08 13:51:11', 'pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(334, '202102690', 'CEIT-24-25-0093', '2025-03-08 20:22:32', 'Pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, '0'),
(335, '202102690', 'CEIT-24-25-0094', '2025-03-08 20:36:55', 'Pending', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, '0'),
(336, '202102690', 'CEIT-24-25-0095', '2025-03-09 09:47:42', 'Pending', 'NEIL TRISTHAN MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(337, NULL, 'CEIT-24-25-0095', '2025-03-09 09:47:42', 'Pending', 'NEIL TRISTHAN N. MOJICA123', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(338, '202102690', 'CEIT-24-25-0096', '2025-03-09 09:51:03', 'Pending', 'NEIL TRISTHAN MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(339, '202102690', 'CEIT-24-25-0097', '2025-03-09 20:23:56', 'Pending', 'NEIL TRISTHAN N. MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(340, '666666666', 'CEIT-24-25-0097', '2025-03-09 20:23:56', 'Pending', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(341, NULL, 'CEIT-24-25-0097', '2025-03-09 20:23:56', 'Pending', 'NEIL POGI', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(342, '202102690', 'CEIT-24-25-0098', '2025-03-09 19:04:02', 'Pending', 'NEIL TRISTHAN N. MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(343, '202102690', 'CEIT-24-25-0099', '2025-03-09 20:52:11', 'For Meeting', 'NEIL TRISTHAN N. MOJICA', 'BS Information Technology', 'Irregular', 0, 2540690, 'BS Information Technology - Irregular Section 2', 1, 'Simeons N. Daez'),
(344, NULL, 'CEIT-24-25-0099', '2025-03-09 20:52:11', 'For Meeting', 'NEIL STUDENT', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(345, NULL, 'CEIT-24-25-0099', '2025-03-09 20:52:11', 'For Meeting', 'NEIL STUDENT (2)', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(346, '202102690', 'CEIT-24-25-0100', '2025-03-09 17:41:31', 'Pending', 'NEIL TRISTHAN N. MOJICA', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(347, '202105212', 'CEIT-24-25-0101', '2025-04-09 12:12:28', 'pending', 'JHANNAH BERNADETTE ALBINO', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(348, '202106746', 'CEIT-24-25-0102', '2025-04-09 13:42:17', 'pending', 'EURICA MAE BORCE', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(349, '202011451', 'CEIT-24-25-0103', '2025-04-09 13:55:55', 'pending', 'KAYRON MARK BURZON', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(350, '202105700', 'CEIT-24-25-0104', '2025-04-09 14:19:37', 'pending', 'IAN GABRIELLE TEODORO', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(351, '202014937', 'CEIT-24-25-0104', '2025-04-09 14:19:37', 'pending', 'MARK CHRISTIAN TABUZO', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez'),
(352, '202106707', 'CEIT-24-25-0104', '2025-04-09 14:19:37', 'pending', 'DARLENE SOLTES', 'BS Computer Engineering', 'Second Year', 0, 2593003, 'BS Computer Engineering - Second Year Section 2', 1, 'Simeons N. Daez');

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
(1, 'adviser1', '$2y$10$cwwg2MRebtKUkZrJgIPxSu5KwQqJHL1GyIo4GJkguTh048hqxx.Zu', 'adviser1@cvsu.edu.ph', 'Simeons', 'N', 'Daez', 'adviser_profiles/66a9a6f6a1524.jpg', '2024-07-19 18:01:28', '2025-01-31 12:09:37', NULL, NULL, 'active'),
(2, 'adviser2', '$2y$10$Iown7rmnbTsja5eNsjnNo.1tjtKG38ymg4ktPTTUYqrchzYLszphu', 'adviser2@cvsu.edu.ph', NULL, NULL, NULL, 'adviser_profiles/66b1dbea19489.png', '2024-07-19 18:01:28', '2024-09-09 22:47:37', NULL, NULL, 'active'),
(5, 'Simeon2024', '$2y$10$BqtAbxe2juRWPtv8G8bs0u/WL4C1gl1Nr.NfSlCThuWWDh6ZlFigW', 'miguelescover.cvsu@gmail.com', NULL, NULL, NULL, NULL, '2024-10-01 14:16:25', '2024-11-22 23:12:09', NULL, NULL, 'active');

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
(7, 'counselor1', '$2y$10$I2UdQpfoRogIlFoK.4iRy.ahtfgaGmfdnojkf6q0lLcEuChVTEyRG', 'counselor@gmail.com', 'MR', '', 'Counsellor', NULL, '2024-09-09 13:45:07', '2025-01-02 01:35:36', NULL, NULL, 'active'),
(8, 'adviser1', '$2y$10$8L9LgVmzdZZLbhLh0aCYU.njP4zhU974oulziP1lM.T4xMCOVD4oW', 'gwen@cvsu.edu.ph', 'GWYNETH KYLAS', 'N', 'MOJICA', NULL, '2025-04-09 05:16:59', '2025-04-09 05:16:59', NULL, NULL, 'active');

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
(1, 'dean1', '$2y$10$wjtiVPvV3v6YyEDnXYvmKeNBdTNkEyqaOYJAjrp/kuKA3jVZVEVWK', '=', 'MR MS', 'D', 'DEAN', 'path/to/profile1.jpg', '2024-07-21 10:40:40', '2025-04-09 05:31:09', NULL, NULL, 'active');

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
(2, 'facilitator2', '$2y$10$iQIhHusJnQdCcObv.Tb0gOGbuBz6VYUIhgFK5kLgGYMAMqLsaXO9e', 'facilitator2@cvsu.edu.ph', NULL, NULL, NULL, NULL, '2024-09-09 15:07:12', '2024-12-11 14:48:31', NULL, NULL, 'active');

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
(2, 'instructor2', '$2y$10$2E1m/kMUANqAQsV2TalycuKdR2LBrrCzJoFEdHUrKh3QayT16AJNC', 'instructor2@cvsu.com', 'MR', 'D', 'INSTRUCTOR', NULL, '2024-08-29 15:02:18', '2024-12-29 17:31:38', NULL, NULL, 'active'),
(3, 'instructor3', '$2y$10$9IEQUVi8zbs83zkURRgN5.O69w1vaGMyeBix883BN/xSqxtqM3R2G', 'instructor3@gmail.com', NULL, NULL, NULL, NULL, '2024-09-09 23:12:11', '2024-10-08 12:38:33', NULL, NULL, 'active'),
(4, 'gerami', '$2y$10$jsW6.aYtLrmbcs7SF4qDpue0CzDdi1bXaC59jyGFY8Ad.XLik.UHi', 'gerami@cvsu.edu.ph', NULL, NULL, NULL, NULL, '2024-12-11 14:17:31', '2024-12-19 07:24:28', NULL, NULL, 'active'),
(6, 'Jayson', '$2y$10$HYNhCE/beWgw/Ks8nC.hjOX3h.Qv5VDyk3QzUUIbLrcLyoEYhjDqG', 'jayson@gmail.com', 'Jayson', 'M', 'Cabanglan', NULL, '2024-12-11 22:14:27', '2024-12-19 07:01:03', NULL, NULL, 'disabled'),
(7, 'sample123', '$2y$10$ZFhbHOVvyeKJAzG4PSFZEuVanSHsxomoLsoSkABlsfq/8ThGtsqd2', 'sample@gmail.com', 'Sample', 'B', 'Size', NULL, '2024-12-19 07:25:50', '2024-12-19 08:02:40', NULL, NULL, 'disabled'),
(9, 'Instructor6', '$2y$10$A4Fn1ZhOPxUyUeilH.hueeOt5x3nPpE3AXhtsCOm4TSHGHe3UQ4nO', 'hello1235@cvsu.edu.ph', 'Cyndell', 'N', 'Dadula', NULL, '2025-04-09 12:45:45', '2025-04-09 12:45:45', NULL, NULL, 'active');

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
(1100, '202102690', '$2y$10$6KZLpsETqLR4sYjmJ18MieOs1KCwhdhGslaNjt0C12PK5tAOj3XuO', 'neiltristhan.mojica@cvsu.edu.ph', NULL, '2025-02-15 14:42:39', '2025-04-09 11:45:00', 2593003, 'NEIL TRISTHAN', 'N', 'MOJICA', 'MALE', NULL, NULL, 'active'),
(1102, '202105212', NULL, NULL, NULL, '2025-02-18 23:10:15', '2025-02-20 14:32:22', 2593003, 'JHANNAH BERNADETTE', 'Q', 'ALBINO', 'FEMALE', NULL, NULL, 'active'),
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
(1139, '202105700', NULL, NULL, NULL, '2025-02-18 23:10:16', '2025-02-20 14:32:22', 2593003, 'IAN GABRIELLE', 'B', 'TEODORO', 'MALE', NULL, NULL, 'active');

--
-- Indexes for dumped tables
--

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=282;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=452;

--
-- AUTO_INCREMENT for table `pending_incident_reports`
--
ALTER TABLE `pending_incident_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `pending_incident_witnesses`
--
ALTER TABLE `pending_incident_witnesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `pending_student_violations`
--
ALTER TABLE `pending_student_violations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2593004;

--
-- AUTO_INCREMENT for table `student_violations`
--
ALTER TABLE `student_violations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=353;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_student`
--
ALTER TABLE `tbl_student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1142;

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
