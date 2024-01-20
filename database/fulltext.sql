SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/* Create demo index */
CREATE TABLE IF NOT EXISTS `demo_index` (
  `id` int NOT NULL,
  `name` varchar(250) NOT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT INDEX (`name`)
) ENGINE=Mroonga DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='tokenizer "TokenBigramSplitSymbolAlphaDigit"';
