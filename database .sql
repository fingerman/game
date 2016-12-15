CREATE DATABASE `game` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE game;


CREATE USER 'player1'@'localhost';
SET PASSWORD FOR 'player1'@'localhost' = PASSWORD('1234');
GRANT USAGE ON *.* TO 'player1'@'localhost' IDENTIFIED BY '1234' WITH MAX_QUERIES_PER_HOUR 0
  MAX_CONNECTIONS_PER_HOUR 0
  MAX_UPDATES_PER_HOUR 0
  MAX_USER_CONNECTIONS 0;
GRANT ALL PRIVILEGES ON `game`.* TO 'player1'@'localhost';

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(40) COLLATE utf8_general_ci NOT NULL,
  `salt` varchar(255) COLLATE utf8_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `user_map` (
  `user_id` int(11) NOT NULL,
  `fields_grass` int(11) NOT NULL,
  `fields_wood` int(11) NOT NULL,
  `fields_stone` int(11) NOT NULL,
  `fields_coal` int(11) NOT NULL,
  `fields_iron` int(11) NOT NULL,
  `fields_gold` int(11) NOT NULL,
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `user_resource` (
  `user_id` int(11) NOT NULL,
  `food` int(11) NOT NULL,
  `wood` int(11) NOT NULL,
  `stone` int(11) NOT NULL,
  `coal` int(11) NOT NULL,
  `iron` int(11) NOT NULL,
  `gold` int(11) NOT NULL,
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `build_queue` (
  `user_id` int(11) NOT NULL,
  `building` ENUM('NONE', 'FARM', 'WOODMANS', 'MINERS', 'MARKET', 'BARRACKS', 'SOLDIER', 'CAVALIER' ) DEFAULT 'NONE' NOT NULL,
  `ready` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `building` (
  `user_id` int(11) NOT NULL,
  `building` ENUM('NONE', 'FARM', 'WOODMANS', 'MINERS', 'MARKET', 'BARRACKS', 'SOLDIER', 'CAVALIER' ) DEFAULT 'NONE' NOT NULL,
  `timer` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `trading` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `give_amount` int(11) NOT NULL,
  `give_type` ENUM('NONE', 'FOOD', 'WOOD', 'STONE', 'COAL', 'IRON', 'GOLD') DEFAULT 'NONE' NOT NULL,
  `get_amount` int(11) NOT NULL,
  `get_type` ENUM('NONE', 'FOOD', 'WOOD', 'STONE', 'COAL', 'IRON', 'GOLD') DEFAULT 'NONE' NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` varchar(255) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;