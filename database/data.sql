SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/* Create user */
CREATE TABLE IF NOT EXISTS `user` (
  `uid` int(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `group_id` int(3) UNSIGNED NOT NULL DEFAULT '4',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `language` varchar(5) NOT NULL DEFAULT 'en_US',
  `timezone` varchar(50) NOT NULL DEFAULT '',
  `online_status` int(10) NOT NULL DEFAULT '0',
  `last_login` int(10) NOT NULL,
  `join_date` int(10) NOT NULL,
  `2FA` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  INDEX (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* Create group list */
CREATE TABLE IF NOT EXISTS `group_list` (
  `id` int(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `param` varchar(150) NOT NULL,
  `level` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  INDEX (`param`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* Foreign key */
ALTER TABLE `user` ADD CONSTRAINT `User_Group` FOREIGN KEY (`group_id`) REFERENCES `group_list`(`id`);
