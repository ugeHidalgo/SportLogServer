-- phpMyAdmin SQL Dump
-- version 4.4.14
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 06, 2015 at 10:02 PM
-- Server version: 5.6.26
-- PHP Version: 5.6.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Trainings`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--
USE Trainings;

CREATE TABLE IF NOT EXISTS `activities` (
  `id` int(11) NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `sportType_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `name`, `sportType_id`) VALUES
(1, 'Nado piscina 25', 1),
(2, 'Nado piscina 50', 1),
(3, 'Nado mar', 1),
(4, 'Nado lago/pantano', 1),
(5, 'Bicicleta de montaÃ±a', 2),
(6, 'Bicicleta de carretera', 2),
(7, 'Rodillos', 2),
(8, 'Bicicleta estÃ¡tica', 2),
(9, 'Carrera a pie por campo/montaÃ±a', 3),
(10, 'Carrera a pie por carretera', 3),
(13, 'Pesas', 6);

-- --------------------------------------------------------

--
-- Table structure for table `rememberMe`
--

CREATE TABLE IF NOT EXISTS `rememberMe` (
  `fieldName` text NOT NULL,
  `fieldValue` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sessionTime` float NOT NULL DEFAULT '0',
  `sessionDist` float NOT NULL DEFAULT '0',
  `sessionTypeId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sessionTypes`
--

CREATE TABLE IF NOT EXISTS `sessionTypes` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `sportTypeId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sportTypes`
--

CREATE TABLE IF NOT EXISTS `sportTypes` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `comment` varchar(500) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sportTypes`
--

INSERT INTO `sportTypes` (`id`, `name`, `comment`) VALUES
(1, 'Swim', 'Nado en piscina, mar, lago, etc..'),
(2, 'Bike', 'Carretera, montaÃ±a, rodillos, etc.'),
(3, 'Run', 'Carrera por montaÃ±a, pista, carretera, etc...'),
(6, 'Gym', 'Abdominales, pesas, etc...');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `user_name` varchar(20) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `second_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_hash` text,
  `api_key` varchar(32) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `birthdate` date NOT NULL,
  `sex` int(11) DEFAULT '2',
  `admin` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_name`, `first_name`, `second_name`, `email`, `password_hash`, `api_key`, `status`, `created_at`, `birthdate`, `sex`, `admin`) VALUES
(2, 'ugeHidalgo', 'Eugenio', 'Hidalgo HernÃ¡ndez', 'ugehidalgo@hotmail.com', '$2a$10$4ab7dd002b7e60f90abdfuSSogoLv2rZHs4a.6wWPLUfOfxXx/i7m', '7d3054788e7547691b1c247d00c3cf7e', 1, '2015-10-18 15:41:03', '1971-04-12', 2, 1),
(3, 'wandaWeir', 'Wanda', 'Weir', 'wandaWeir@companyName.com', '$2a$10$c67d304ff454f748ca867ugsk.14R2gVmvXyvES1RSxPTxD1Cayg.', 'cba13e946c69c1bd649883e3d9d5603b', 1, '2015-10-18 15:48:10', '1974-01-20', 1, 0),
(22, 'ugeHidalgo2', 'Eugenio', 'Hidalgo', 'ugeHidalgo@hotmail.com', '$2a$10$e606a6888a6b6e8221ba5u/2.jl7s4zJG48/6Gy1KXzv0a6K/dXLm', 'dcadcffe881d0802c9b41c0497dfccc6', 0, '2015-10-24 20:44:19', '0000-00-00', 0, 0),
(24, 'uge', 'hidalgo', 'hertnandez', 'mail', '$2a$10$0b310017b730e9e572d09eOQneLRfppXuB9ay9A0GcU3IxrDQifkC', '6bbbc5cddfe68ae2dcf77d642cb17a77', 0, '2015-10-24 20:57:11', '0000-00-00', 0, 1),
(25, 'ugeHidalgo4', 'Eugenio', 'Hidalgo', 'ugeHidalgo@hotmail.com', '$2a$10$c06ddf7ab55dd8208e779OMcrLVfu8T60EpfztZmF1EfPgjft2Mny', '1aec024758a9dbd4186544dbbbf67e9e', 0, '2015-10-29 17:23:55', '0000-00-00', 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rememberMe`
--
ALTER TABLE `rememberMe`
  ADD PRIMARY KEY (`fieldName`(255));

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessionTypes`
--
ALTER TABLE `sessionTypes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sportTypes`
--
ALTER TABLE `sportTypes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_name` (`user_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=14;
--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `sessionTypes`
--
ALTER TABLE `sessionTypes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `sportTypes`
--
ALTER TABLE `sportTypes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=26;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
