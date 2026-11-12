-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2026 at 05:29 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_reference` varchar(5) NOT NULL,
  `job_category` varchar(50) NOT NULL DEFAULT 'Uncategorized',
  `job_title` varchar(100) NOT NULL,
  `salary` varchar(80) DEFAULT NULL,
  `reports_to` varchar(150) DEFAULT NULL,
  `job_description` text NOT NULL,
  `responsibilities` text DEFAULT NULL,
  `essential_requirements` text DEFAULT NULL,
  `preferable_requirements` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`job_reference`, `job_category`, `job_title`, `salary`, `reports_to`, `job_description`, `responsibilities`, `essential_requirements`, `preferable_requirements`, `location`, `closing_date`, `status`) VALUES
('CSA03', 'Urban Services', 'Cybersecurity Analyst', 'AUD6,500 - AUD9,000 per month', 'Senior Cybersecurity Consultant', 'The Cybersecurity Analyst helps protect UrbanSync and its clients from cybersecurity threats by monitoring systems, identifying vulnerabilities, and supporting security improvements.\r\n\r\nYou will work with IT teams to investigate suspicious activities, assess security risks, and maintain the safety of digital infrastructure.', 'Monitor networks and systems for suspicious activities.\r\nIdentify and investigate potential security threats.\r\nPerform basic vulnerability assessments.\r\nAssist in implementing security measures and policies.\r\nPrepare security reports and document incidents.', 'A degree or relevant study in Cybersecurity, Information Technology, Computer Science, or a related field.\r\nBasic knowledge of networking and operating systems.\r\nUnderstanding of cybersecurity principles and common threats.\r\nAnalytical and problem-solving skills.\r\nGood communication and teamwork skills.', 'Knowledge of security tools such as Wireshark or Nmap.\r\nBasic scripting knowledge in Python or Bash.\r\nUnderstanding of firewalls and intrusion detection systems.\r\nRelevant cybersecurity certifications such as CompTIA Security+.', 'Kuala Lumpur', '2026-12-31', 'Open'),
('EGS02', 'Energy', 'Energy Systems Consultant', 'AUD7,500 - AUD10,000 per month', 'Principal Infrastructure Consultant', 'The Energy Systems Consultant supports clients in monitoring energy performance and improving the efficiency of digital infrastructure systems.\r\n\r\nYou will work with project teams to review energy data, identify improvement opportunities, and contribute to smart infrastructure solutions.', 'Review energy consumption and monitoring data.\r\nAssist in developing energy performance reports.\r\nIdentify opportunities to improve energy efficiency.\r\nSupport smart infrastructure planning and implementation.\r\nPresent project findings to clients and project stakeholders.', 'A degree or relevant study in Engineering, Information Technology, Environmental Science, Data Analytics, or a related field.\r\nStrong analytical and numerical skills.\r\nGood understanding of data and reporting.\r\nStrong communication and teamwork skills.\r\nAbility to work effectively with clients and project teams.', 'Experience with Power BI, Excel, or similar reporting tools.\r\nUnderstanding of energy monitoring systems.\r\nInterest in sustainable infrastructure and smart-city development.', 'Kuala Lumpur', '2026-12-31', 'Open'),
('TRS01', 'Transport', 'Smart Mobility Data Analyst', 'AUD6,000 - AUD8,500 per month', 'Senior Smart Mobility Consultant', 'The Smart Mobility Data Analyst supports transport and mobility projects by analysing data and producing insights that help councils and industry partners make better decisions.\r\n\r\nYou will work with transport datasets, dashboards, and reporting tools to identify trends and improve urban mobility planning.', 'Analyse transport and mobility datasets.\r\nPrepare reports and dashboards for project stakeholders.\r\nIdentify patterns and trends in transport data.\r\nSupport consultants with data-driven recommendations.\r\nCommunicate findings clearly to technical and non-technical stakeholders.', 'A degree or relevant study in Data Analytics, Information Technology, Computer Science, Statistics, or a related field.\r\nBasic to intermediate SQL skills.\r\nKnowledge of Excel or similar data analysis tools.\r\nStrong analytical and problem-solving skills.\r\nGood written and verbal communication skills.', 'Experience with Power BI or another visualisation platform.\r\nKnowledge of Python or another programming language.\r\nInterest in smart cities, transport, or urban technology.', 'Kuala Lumpur', '2026-12-31', 'Open');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_reference`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
