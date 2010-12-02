-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 02, 2010 at 04:28 PM
-- Server version: 5.1.41
-- PHP Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `music`
--

-- --------------------------------------------------------

--
-- Table structure for table `songs`
--

CREATE TABLE IF NOT EXISTS `songs` (
  `persistent_id` text NOT NULL,
  `track_id` int(11) DEFAULT NULL,
  `name` text,
  `artist` text,
  `album` text,
  `kind` text,
  `size` int(11) DEFAULT NULL,
  `total_time` int(11) DEFAULT NULL,
  `track_number` int(11) DEFAULT NULL,
  `track_count` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `date_modified` timestamp NULL DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT NULL,
  `bit_rate` int(11) DEFAULT NULL,
  `sample_rate` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `album_rating` int(11) DEFAULT NULL,
  `album_rating_computed` tinyint(1) DEFAULT NULL,
  `play_count` int(11) DEFAULT NULL,
  `play_date` int(11) DEFAULT NULL,
  `play_date_utc` timestamp NULL DEFAULT NULL,
  `normalization` int(11) DEFAULT NULL,
  `compilation` tinyint(1) DEFAULT NULL,
  `podcast` tinyint(1) DEFAULT NULL,
  `unplayed` tinyint(1) DEFAULT NULL COMMENT 'States whether a podcast has been played or not.',
  `track_type` text,
  `location` text,
  `file_folder_count` int(11) DEFAULT NULL,
  `library_folder_count` int(11) DEFAULT NULL,
  `in_library_file_flag` tinyint(1) DEFAULT NULL COMMENT 'Used during import processed. Flagged as true if present in library file. All without true status are removed at end of import.',
  PRIMARY KEY (`persistent_id`(767))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
