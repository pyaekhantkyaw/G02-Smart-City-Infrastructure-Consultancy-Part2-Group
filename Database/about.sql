-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 14, 2026 at 04:04 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `urbansync`
--

-- --------------------------------------------------------

--
-- Table structure for table `about`
--

CREATE TABLE `about` (
  `member_id` int(11) NOT NULL,
  `member_name` varchar(100) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `project1_contribution` text NOT NULL,
  `project2_contribution` text NOT NULL,
  `quote_mm` varchar(500) DEFAULT NULL,
  `quote_translation` varchar(500) DEFAULT NULL,
  `dream_job` varchar(150) DEFAULT NULL,
  `coding_snack` varchar(150) DEFAULT NULL,
  `hometown` varchar(100) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about`
--

INSERT INTO `about` (`member_id`, `member_name`, `student_id`, `linkedin_url`, `project1_contribution`, `project2_contribution`, `quote_mm`, `quote_translation`, `dream_job`, `coding_snack`, `hometown`, `display_order`) VALUES
(1, 'Pyae Khant Kyaw', '106628822', 'https://www.linkedin.com/in/pyae-khant-kyaw-591726390/', 'Built the layout and styled the index.html and apply.html pages, implemented accessible definition lists, structured tables, figure captions, and configured CSS hover rules.', 'Developed the PHP job application system using apply.php, application_validation.php, and process_eoi.php, while also collaborating with Oakkar to build manage.php. Additionally, implemented robust server-side validation, input sanitisation, CSRF protection, and duplicate application prevention, while fully integrating MySQL to securely store and manage EOI records.', 'ကြိုးစားရင် ဘာမဆို ဖြစ်နိုင်ပါတယ်။', 'If you try hard, anything is possible.', 'Data Scientist/ ML Engineer', 'Fruits', 'Myanmar', 1),
(2, 'Han Lin Aung', '106391247', 'https://www.linkedin.com/in/han-lin-aung-30025931a/', 'Designed and developed the about.html page, configured navigation links, header elements, search box, and table cell merging.', 'Developed the database-driven about.php page to display team contributions, implemented reusable PHP components using header.inc and footer.inc, configured database connectivity through settings.php, and maintained the homepage, navigation, styling, and overall website integration.', 'ပညာရွှေအိုး လူမခိုး။', 'Knowledge is a treasure chest that no one can steal.', 'Software Developer', 'Potato Chips & Energy Drink', 'Myanmar', 2),
(3, 'Oakkar Aung', '106628835', 'https://www.linkedin.com/in/oakkar-aung-b70077332/', 'Authored the explore.html and related pages, prepared detailed position descriptions, implemented the aside layout, and organized job requirements lists.', 'Developed the HR management system using login.php, logout.php, and manager_functions.php, implemented manager authentication, session management, and CRUD operations for searching, sorting, updating, and deleting EOI records, and configured the users database table. Develop manage.php together with Pyae.', 'ညီညွတ်ခြင်းသည် အားဖြစ်၏။', 'Unity is strength.', 'Cybersecurity Specialist', 'Chocolate cookie', 'Myanmar', 3),
(4, 'Min Khant Kyaw', '106677086', 'https://www.linkedin.com/in/min-khant-kyaw-52973a392/', 'Created the jobs.html page, implemented the aside layout and HTML5 form validation patterns, accessible fieldsets, and Flexbox form layout.', 'Developed dynamic job listings using jobs.php and MySQL, implemented database-driven job search functionality, created and populated the jobs database table, and maintained the Explore section and related Smart City information pages.', 'ဇွဲ၊ လုံ့လ၊ ဝီရိယ ရှိပါစေ။', 'Persevere with patience and effort.', 'Cybersecurity Specialist', 'Chocolate (Oreo)', 'Myanmar', 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about`
--
ALTER TABLE `about`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about`
--
ALTER TABLE `about`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
