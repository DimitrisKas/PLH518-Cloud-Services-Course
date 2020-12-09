-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2020 at 05:25 PM
-- Server version: 10.4.16-MariaDB
-- PHP Version: 7.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project1`
--
CREATE DATABASE IF NOT EXISTS Project1;
USE Project1;

-- --------------------------------------------------------

--
-- Table structure for table `cinemas`
--

CREATE TABLE `Cinemas` (
  `ID` varchar(10) NOT NULL,
  `OWNER` varchar(20) NOT NULL,
  `NAME` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cinemas`
--

INSERT INTO `Cinemas` (`ID`, `OWNER`, `NAME`) VALUES
('c104885885', 'u958215285', 'Village'),
('c633159382', 'u721317857', 'Ellinis'),
('c755793336', 'u958215285', 'Ster Cinemas'),
('c981054497', 'u721317857', 'Odeon');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `Favorites` (
  `ID` varchar(10) NOT NULL,
  `USERID` varchar(10) NOT NULL,
  `MOVIEID` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `favorites`
--

INSERT INTO `Favorites` (`ID`, `USERID`, `MOVIEID`) VALUES
('f609630709', 'u902651969', 'm683408917');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `Movies` (
  `ID` varchar(10) NOT NULL,
  `TITLE` varchar(50) NOT NULL,
  `STARTDATE` date DEFAULT NULL,
  `ENDDATE` date DEFAULT NULL,
  `CINEMANAME` varchar(20) DEFAULT NULL,
  `CATEGORY` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `movies`
--

INSERT INTO `Movies` (`ID`, `TITLE`, `STARTDATE`, `ENDDATE`, `CINEMANAME`, `CATEGORY`) VALUES
('m258735884', 'The Godfather', '2020-11-30', '2020-12-07', 'Odeon', 'Drama'),
('m319840796', 'Fight Club', '2020-11-30', '2020-12-05', 'Ster Cinemas', 'Action'),
('m339893498', 'The Godfather 2', '2020-12-07', '2020-12-14', 'Odeon', 'Drama'),
('m427020072', 'The Nice Guys', '2020-11-30', '2020-12-07', 'Ster Cinemas', 'Comedy'),
('m683408917', 'A third movie', '2020-11-30', '2020-11-30', 'Village', 'Drama'),
('m754035299', 'The Godfather 3', '2020-12-14', '2020-12-21', 'Odeon', 'Drama'),
('m899680287', 'Lord Of The Rings', '2020-11-30', '2020-12-07', 'Ellinis', 'Fantasy');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `Users` (
  `ID` varchar(10) NOT NULL,
  `NAME` varchar(20) DEFAULT NULL,
  `SURNAME` varchar(20) DEFAULT NULL,
  `USERNAME` varchar(20) NOT NULL,
  `PASSWORD` varchar(20) NOT NULL,
  `EMAIL` varchar(50) NOT NULL,
  `ROLE` enum('ADMIN','CINEMAOWNER','USER') DEFAULT NULL,
  `CONFIRMED` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `Users` (`ID`, `NAME`, `SURNAME`, `USERNAME`, `PASSWORD`, `EMAIL`, `ROLE`, `CONFIRMED`) VALUES
('u052010974', 'Dimitris', 'Kastrinakis', 'dkastrinakis', '1234', 'dk@email.com', 'ADMIN', 1),
('u691867084', 'Bob', 'Bobby', 'user2', '1234', 'dk@email2.com', 'USER', 1),
('u721317857', 'user5Name', 'Surname', 'user8', '1234', 'user5@hotmail.com', 'CINEMAOWNER', 1),
('u830153143', 'Fereniki', 'Moschogiannaki', 'fereniki', '12345', 'tade@gmail.com', 'USER', 1),
('u902651969', '', 'hey', 'user1', '1234', 'user1@gmail', 'ADMIN', 1),
('u958215285', '', '', 'user6', '1234', 'user6@gmail', 'CINEMAOWNER', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cinemas`
--
ALTER TABLE `Cinemas`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `Favorites`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `favorites_ibfk_1` (`USERID`),
  ADD KEY `favorites_ibfk_2` (`MOVIEID`);

--
-- Indexes for table `movies`
--
ALTER TABLE `Movies`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `USERNAME` (`USERNAME`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favorites`
--
ALTER TABLE `Favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`USERID`) REFERENCES `Users` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`MOVIEID`) REFERENCES `Movies` (`ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
