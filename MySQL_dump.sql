-- smWiki database dump Copyright (C) 2012, smWiki Dev Team
-- replace {db_prefix} with your database prefix

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `wiki_content`
--

CREATE TABLE IF NOT EXISTS `wiki_content` (
  `id_revision` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `id_editor` int(11) NOT NULL,
  `unparsed_content` text NOT NULL,
  `parsed_content` text NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id_revision`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17 ;

--
-- Dumping data for table `wiki_content`
--

INSERT INTO `wiki_content` (`id_revision`, `name`, `id_editor`, `unparsed_content`, `parsed_content`, `time`) VALUES
(1, 'Main Page', 0, '[h1]Title level 1[/h1]\nhello world', '<h1>Title level 1</h1><br />\nhello world', 25);

-- --------------------------------------------------------

--
-- Table structure for table `wiki_urls`
--

CREATE TABLE IF NOT EXISTS `wiki_urls` (
  `urlname` varchar(512) NOT NULL,
  `realname` varchar(128) NOT NULL,
  `latest_revision` int(11) NOT NULL,
  PRIMARY KEY (`urlname`,`realname`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `wiki_urls`
--

INSERT INTO `wiki_urls` (`urlname`, `realname`, `latest_revision`) VALUES
('Main_Page', 'Main Page', 1);