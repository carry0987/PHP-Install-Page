SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/* Create global config */
CREATE TABLE IF NOT EXISTS `global_config` (
    `id` int(3) UNSIGNED NOT NULL AUTO_INCREMENT,
    `param` varchar(150) NOT NULL,
    `value` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX (`param`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuration Table';

INSERT INTO `global_config` (`id`, `param`, `value`) VALUES
(1, 'demo_config', 'a:5:{s:8:"web_name";s:4:"Demo";s:15:"web_description";s:17:"Welcome To Demo !";s:12:"web_language";s:5:"en_US";s:12:"web_timezone";s:11:"Asia/Taipei";s:11:"maintenance";i:0;}');

INSERT INTO `group_list` (`id`, `param`, `level`) VALUES
(1, 'founder', 4),
(2, 'administrator', 3),
(3, 'vip', 2),
(4, 'normal', 1);
